<?php

/*
 * This file is part of the Mercure Component project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\Component\Mercure\Jwt;

use Jose\Component\Core\Algorithm;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\Algorithm\ES384;
use Jose\Component\Signature\Algorithm\ES512;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\HS384;
use Jose\Component\Signature\Algorithm\HS512;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\Algorithm\RS384;
use Jose\Component\Signature\Algorithm\RS512;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Internal\AuthorizationDetailsClaims;
use Symfony\Component\Mercure\Internal\JwtLifetime;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Builds Mercure protocol 1.0 JSON Web Tokens (an RFC 9068 access token carrying
 * an RFC 9396 "authorization_details" claim) using "web-token/jwt-library".
 *
 * This factory only supports the Mercure protocol 1.0 wire format. For the legacy
 * 0.x "mercure" claim, use {@see LcobucciFactory}.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class WebTokenFactory implements TokenFactoryInterface
{
    /**
     * @var array<string, class-string<Algorithm>>
     */
    public const SIGN_ALGORITHMS = [
        'hmac.sha256' => HS256::class,
        'hmac.sha384' => HS384::class,
        'hmac.sha512' => HS512::class,
        'ecdsa.sha256' => ES256::class,
        'ecdsa.sha384' => ES384::class,
        'ecdsa.sha512' => ES512::class,
        'rsa.sha256' => RS256::class,
        'rsa.sha384' => RS384::class,
        'rsa.sha512' => RS512::class,
    ];

    private readonly string $algorithm;
    private readonly JWSBuilder $jwsBuilder;
    private readonly ?int $jwtLifetime;

    private function __construct(
        private readonly JWK $jwk,
        Algorithm $algorithmInstance,
        ?int $jwtLifetime,
    ) {
        $this->algorithm = $algorithmInstance->name();
        $this->jwsBuilder = new JWSBuilder(new AlgorithmManager([$algorithmInstance]));
        $this->jwtLifetime = JwtLifetime::resolve($jwtLifetime);
    }

    /**
     * @param non-empty-string $secret
     * @param int|null         $jwtLifetime If not null, an "exp" claim is always set to now + $jwtLifetime (in seconds), defaults to "session.cookie_lifetime" or 3600 if "session.cookie_lifetime" is set to 0.
     */
    public static function fromSecret(string $secret, string $algorithm = 'hmac.sha256', ?int $jwtLifetime = 0, string $passphrase = ''): self
    {
        $algorithmInstance = self::resolveAlgorithm($algorithm);
        $jwk = str_starts_with($algorithm, 'hmac.')
            ? JWKFactory::createFromSecret($secret)
            : JWKFactory::createFromKey($secret, '' === $passphrase ? null : $passphrase);

        return new self($jwk, $algorithmInstance, $jwtLifetime);
    }

    /**
     * Fetches the signing key from a JSON Web Key Set (JWKS) endpoint instead of a static secret,
     * useful when key material is rotated by an external key server.
     *
     * The key is selected once, when this method is called, and reused for the lifetime of the
     * returned instance: nothing here re-fetches or revalidates the JWKS afterward. Under a
     * persistent-worker deployment (e.g. FrankenPHP), a factory built this way keeps signing with
     * the same key for as long as the worker lives, even after the key is rotated or revoked
     * upstream; size the worker's lifetime accordingly, or rebuild the factory periodically.
     *
     * Deliberately does not use "web-token/jwt-library"'s own JKUFactory/UrlKeySetFactory: they
     * unconditionally instantiate Symfony\Component\Cache\Adapter\NullAdapter, a class from
     * "symfony/cache", a package the library never declares as a dependency.
     *
     * @param string|null $keyId       Selects a specific key by its "kid" member; required when the key set holds more than one key matching $algorithm
     * @param int|null    $jwtLifetime See {@see self::fromSecret()}
     */
    public static function fromJwksUri(string $jwksUri, ?HttpClientInterface $httpClient = null, string $algorithm = 'hmac.sha256', ?string $keyId = null, ?int $jwtLifetime = 0): self
    {
        $algorithmInstance = self::resolveAlgorithm($algorithm);

        try {
            $content = ($httpClient ?? HttpClient::create())->request('GET', $jwksUri)->getContent();
        } catch (ExceptionInterface $exception) {
            throw new RuntimeException(\sprintf('Failed to fetch the JWK Set at "%s".', $jwksUri), 0, $exception);
        }

        $jwk = JWKSet::createFromJson($content)->selectKey('sig', $algorithmInstance, null !== $keyId ? ['kid' => $keyId] : []);

        if (null === $jwk) {
            throw new InvalidArgumentException(\sprintf(
                'No signing key matching algorithm "%s"%s was found in the JWK Set at "%s".',
                $algorithm,
                null !== $keyId ? \sprintf(' and key ID "%s"', $keyId) : '',
                $jwksUri
            ));
        }

        return new self($jwk, $algorithmInstance, $jwtLifetime);
    }

    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string
    {
        $additionalClaims = AuthorizationDetailsClaims::build($subscribe, $publish, $additionalClaims, $this->jwtLifetime);

        foreach (['exp', 'iat', 'nbf'] as $dateClaim) {
            if (isset($additionalClaims[$dateClaim]) && $additionalClaims[$dateClaim] instanceof \DateTimeInterface) {
                $additionalClaims[$dateClaim] = $additionalClaims[$dateClaim]->getTimestamp();
            }
        }

        $jws = $this->jwsBuilder->create()
            ->withPayload(json_encode($additionalClaims, \JSON_THROW_ON_ERROR))
            ->addSignature($this->jwk, ['alg' => $this->algorithm, 'typ' => 'at+jwt'])
            ->build();

        return (new CompactSerializer())->serialize($jws, 0);
    }

    private static function resolveAlgorithm(string $algorithm): Algorithm
    {
        if (!class_exists(JWSBuilder::class)) {
            throw new \LogicException('You cannot use "Symfony\Component\Mercure\Jwt\WebTokenFactory" as the "web-token/jwt-library" package is not installed. Try running "composer require web-token/jwt-library".');
        }

        if (!\array_key_exists($algorithm, self::SIGN_ALGORITHMS)) {
            throw InvalidArgumentException::forInvalidAlgorithm($algorithm, array_keys(self::SIGN_ALGORITHMS));
        }

        $algorithmClass = self::SIGN_ALGORITHMS[$algorithm];

        return new $algorithmClass();
    }
}
