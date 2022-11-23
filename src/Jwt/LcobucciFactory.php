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

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token\RegisteredClaims;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;

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

    private $configurations;
    private $jwtLifetime;

    /**
     * @param non-empty-string $secret
     * @param int|null         $jwtLifetime If not null, an "exp" claim is always set to now + $jwtLifetime (in seconds), defaults to "session.cookie_lifetime" or 3600 if "session.cookie_lifetime" is set to 0.
     */
    public function __construct(string $secret, string $algorithm = 'hmac.sha256', ?int $jwtLifetime = 0, string $passphrase = '')
    {
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

        $this->jwtLifetime = 0 === $jwtLifetime ? ((int) \ini_get('session.cookie_lifetime') ?: 3600) : $jwtLifetime;
    }

    /**
     * {@inheritdoc}
     */
    public function create(?array $subscribe = [], ?array $publish = [], array $additionalClaims = []): string
    {
        $builder = $this->configurations->builder();

        if (null !== $this->jwtLifetime && !\array_key_exists('exp', $additionalClaims)) {
            $additionalClaims['exp'] = new \DateTimeImmutable("+{$this->jwtLifetime} seconds");
        }

        $tokens = [];
        if (null !== $publish) {
            $tokens['publish'] = (array) $publish;
        }
        if (null !== $subscribe) {
            $tokens['subscribe'] = (array) $subscribe;
        }

        $additionalClaims['mercure'] = array_merge($tokens, $additionalClaims['mercure'] ?? []);

        foreach ($additionalClaims as $name => $value) {
            switch ($name) {
                case RegisteredClaims::AUDIENCE:
                    $builder = $builder->permittedFor(...(array) $value);
                    break;
                case RegisteredClaims::EXPIRATION_TIME:
                    if (null !== $value) {
                        $builder = $builder->expiresAt($value);
                    }
                    break;
                case RegisteredClaims::ISSUED_AT:
                    $builder = $builder->issuedAt($value);
                    break;
                case RegisteredClaims::ISSUER:
                    $builder = $builder->issuedBy($value);
                    break;
                case RegisteredClaims::SUBJECT:
                    $builder = $builder->relatedTo($value);
                    break;
                case RegisteredClaims::ID:
                    $builder = $builder->identifiedBy($value);
                    break;
                case RegisteredClaims::NOT_BEFORE:
                    $builder = $builder->canOnlyBeUsedAfter($value);
                    break;
                default:
                    $builder = $builder->withClaim($name, $value);
            }
        }

        return $builder
            ->getToken($this->configurations->signer(), $this->configurations->signingKey())
            ->toString();
    }
}
