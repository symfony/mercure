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

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\ClaimsFormatter;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token\RegisteredClaims;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\MatcherInput;
use Symfony\Component\Mercure\ProtocolVersion;

/**
 * Builds Mercure JSON Web Tokens using "lcobucci/jwt", for either the legacy 0.x
 * "mercure" claim or the Mercure protocol 1.0 "authorization_details" claim
 * (an RFC 9068 access token).
 */
final class LcobucciFactory implements TokenFactoryInterface
{
    /**
     * @var array<string, class-string<Signer>>
     */
    public const SIGN_ALGORITHMS = [
        'hmac.sha256' => Signer\Hmac\Sha256::class,
        'hmac.sha384' => Signer\Hmac\Sha384::class,
        'hmac.sha512' => Signer\Hmac\Sha512::class,
        'ecdsa.sha256' => Signer\Ecdsa\Sha256::class,
        'ecdsa.sha384' => Signer\Ecdsa\Sha384::class,
        'ecdsa.sha512' => Signer\Ecdsa\Sha512::class,
        'rsa.sha256' => Signer\Rsa\Sha256::class,
        'rsa.sha384' => Signer\Rsa\Sha384::class,
        'rsa.sha512' => Signer\Rsa\Sha512::class,
    ];

    private Configuration $configurations;
    private ?int $jwtLifetime;

    /**
     * @param non-empty-string $secret
     * @param int|null         $jwtLifetime If not null, an "exp" claim is always set to now + $jwtLifetime (in seconds), defaults to "session.cookie_lifetime" or 3600 if "session.cookie_lifetime" is set to 0.
     */
    public function __construct(
        string $secret,
        string $algorithm = 'hmac.sha256',
        ?int $jwtLifetime = 0,
        string $passphrase = '',
        private readonly ProtocolVersion $protocolVersion = ProtocolVersion::Legacy,
    ) {
        if (!class_exists(Key\InMemory::class)) {
            throw new \LogicException('You cannot use "Symfony\Component\Mercure\Token\LcobucciFactory" as the "lcobucci/jwt" package is not installed. Try running "composer require lcobucci/jwt".');
        }

        if (!\array_key_exists($algorithm, self::SIGN_ALGORITHMS)) {
            throw InvalidArgumentException::forInvalidAlgorithm($algorithm, array_keys(self::SIGN_ALGORITHMS));
        }

        $signerClass = self::SIGN_ALGORITHMS[$algorithm];
        $signer = is_a($signerClass, Signer\Ecdsa::class, true) && method_exists(Signer\Ecdsa::class, 'create') ? $signerClass::create() : new $signerClass();

        $this->configurations = Configuration::forSymmetricSigner(
            $signer,
            Key\InMemory::plainText($secret, $passphrase)
        );

        $this->jwtLifetime = JwtClaims::resolveLifetime($jwtLifetime);
    }

    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string
    {
        return ProtocolVersion::V1 === $this->protocolVersion
            ? $this->createV1($subscribe, $publish, $additionalClaims)
            : $this->createLegacy($subscribe, $publish, $additionalClaims);
    }

    private function createLegacy(?array $subscribe, ?array $publish, array $additionalClaims): string
    {
        if (null !== $this->jwtLifetime && !\array_key_exists('exp', $additionalClaims)) {
            $additionalClaims['exp'] = new \DateTimeImmutable("+{$this->jwtLifetime} seconds");
        }

        $tokens = [];
        if (null !== $publish) {
            $tokens['publish'] = MatcherInput::flattenToExactOrFail($publish);
        }
        if (null !== $subscribe) {
            $tokens['subscribe'] = MatcherInput::flattenToExactOrFail($subscribe);
        }

        $additionalClaims['mercure'] = array_merge($tokens, $additionalClaims['mercure'] ?? []);

        return $this->buildToken($this->configurations->builder(), $additionalClaims);
    }

    private function createV1(?array $subscribe, ?array $publish, array $additionalClaims): string
    {
        $additionalClaims = JwtClaims::buildAuthorizationDetails($subscribe, $publish, $additionalClaims, $this->jwtLifetime);

        // RFC 9068 "iat"/"exp" are NumericDate values; "lcobucci/jwt"'s default claim formatter
        // emits them as floats with microsecond precision instead, which some resource servers
        // reject. Force plain Unix timestamps, matching WebTokenFactory, with our own formatter
        // rather than Encoding\ChainedFormatter::withUnixTimestampDates(): that method isn't
        // available on every "lcobucci/jwt" version this factory supports.
        $builder = $this->configurations->builder($this->integerTimestampFormatter());

        return $this->buildToken($builder, $additionalClaims, ['typ' => 'at+jwt']);
    }

    private function integerTimestampFormatter(): ClaimsFormatter
    {
        return new class implements ClaimsFormatter {
            public function formatClaims(array $claims): array
            {
                foreach ([RegisteredClaims::ISSUED_AT, RegisteredClaims::EXPIRATION_TIME, RegisteredClaims::NOT_BEFORE] as $name) {
                    if (isset($claims[$name]) && $claims[$name] instanceof \DateTimeInterface) {
                        $claims[$name] = $claims[$name]->getTimestamp();
                    }
                }

                if (isset($claims[RegisteredClaims::AUDIENCE]) && 1 === \count($claims[RegisteredClaims::AUDIENCE])) {
                    $claims[RegisteredClaims::AUDIENCE] = current($claims[RegisteredClaims::AUDIENCE]);
                }

                return $claims;
            }
        };
    }

    /**
     * @param mixed[]               $additionalClaims
     * @param array<string, string> $headers
     */
    private function buildToken(Builder $builder, array $additionalClaims, array $headers = []): string
    {
        foreach ($headers as $name => $value) {
            $builder = $builder->withHeader($name, $value);
        }

        foreach ($additionalClaims as $name => $value) {
            $builder = $this->applyClaim($builder, $name, $value);
        }

        return $builder
            ->getToken($this->configurations->signer(), $this->configurations->signingKey())
            ->toString();
    }

    private function applyClaim(Builder $builder, string $name, mixed $value): Builder
    {
        return match ($name) {
            RegisteredClaims::AUDIENCE => $builder->permittedFor(...(array) $value),
            RegisteredClaims::EXPIRATION_TIME => null !== $value ? $builder->expiresAt($value) : $builder,
            RegisteredClaims::ISSUED_AT => $builder->issuedAt($value),
            RegisteredClaims::ISSUER => $builder->issuedBy($value),
            RegisteredClaims::SUBJECT => $builder->relatedTo($value),
            RegisteredClaims::ID => $builder->identifiedBy($value),
            RegisteredClaims::NOT_BEFORE => $builder->canOnlyBeUsedAfter($value),
            default => $builder->withClaim($name, $value),
        };
    }
}
