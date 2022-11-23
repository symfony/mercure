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

namespace Symfony\Component\Mercure;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Exception\RuntimeException;

/**
 * Manages the "mercureAuthorization" cookies.
 */
final class Authorization
{
    private const MERCURE_AUTHORIZATION_COOKIE_NAME = 'mercureAuthorization';

    private $registry;
    private $cookieLifetime;
    /**
     * @var Cookie::SAMESITE_*|null|null
     */
    private $cookieSameSite;

    /**
     * @param int|null                $cookieLifetime in seconds, 0 for the current session, null to default to the value of "session.cookie_lifetime" or 3600 if "session.cookie_lifetime" is set to 0. The "exp" field of the JWT will be set accordingly if not set explicitly, defaults to 1h in case of session cookies.
     * @param Cookie::SAMESITE_*|null $cookieSameSite
     */
    public function __construct(HubRegistry $registry, ?int $cookieLifetime = null, ?string $cookieSameSite = Cookie::SAMESITE_STRICT)
    {
        $this->registry = $registry;
        $this->cookieLifetime = $cookieLifetime ?? (int) \ini_get('session.cookie_lifetime');
        $this->cookieSameSite = $cookieSameSite;
    }

    /**
     * Sets mercureAuthorization cookie for the given hub.
     *
     * @param string[]|string|null $subscribe        a topic or a list of topics that the authorization cookie will allow subscribing to
     * @param string[]|string|null $publish          a list of topics that the authorization cookie will allow publishing to
     * @param array<string, mixed> $additionalClaims an array of additional claims for the JWT
     * @param string|null          $hub              the hub to generate the cookie for
     */
    public function setCookie(Request $request, $subscribe = [], $publish = [], array $additionalClaims = [], ?string $hub = null): void
    {
        $this->updateCookies($request, $hub, $this->createCookie($request, $subscribe, $publish, $additionalClaims, $hub));
    }

    /**
     * Clears the mercureAuthorization cookie for the given hub.
     *
     * @param string|null $hub the hub to clear the cookie for
     */
    public function clearCookie(Request $request, ?string $hub = null): void
    {
        $this->updateCookies($request, $hub, $this->createClearCookie($request, $hub));
    }

    /**
     * Creates mercureAuthorization cookie for the given hub.
     *
     * @param string[]|string|null $subscribe        a list of topics that the authorization cookie will allow subscribing to
     * @param string[]|string|null $publish          a list of topics that the authorization cookie will allow publishing to
     * @param array<string, mixed> $additionalClaims an array of additional claims for the JWT
     * @param string|null          $hub              the hub to generate the cookie for
     */
    public function createCookie(Request $request, $subscribe = [], $publish = [], array $additionalClaims = [], ?string $hub = null): Cookie
    {
        $hubInstance = $this->registry->getHub($hub);
        $tokenFactory = $hubInstance->getFactory();
        if (null === $tokenFactory) {
            $message = sprintf('The %s hub does not contain a token factory.', $hub ? "\"$hub\"" : 'default');
            throw new InvalidArgumentException($message);
        }

        $cookieLifetime = $this->cookieLifetime;
        if (\array_key_exists('exp', $additionalClaims)) {
            if (null !== $additionalClaims['exp']) {
                $cookieLifetime = $additionalClaims['exp'];
            }
        } else {
            $additionalClaims['exp'] = new \DateTimeImmutable(0 === $cookieLifetime ? '+1 hour' : "+{$cookieLifetime} seconds");
        }

        if (null !== $subscribe) {
            $subscribe = (array) $subscribe;
        }
        if (null !== $publish) {
            $publish = (array) $publish;
        }

        $token = $tokenFactory->create($subscribe, $publish, $additionalClaims);
        $url = $hubInstance->getPublicUrl();
        /** @var array $urlComponents */
        $urlComponents = parse_url($url);

        if (!$cookieLifetime instanceof \DateTimeInterface && 0 !== $cookieLifetime) {
            $cookieLifetime = new \DateTimeImmutable("+{$cookieLifetime} seconds");
        }

        return Cookie::create(
            self::MERCURE_AUTHORIZATION_COOKIE_NAME,
            $token,
            $cookieLifetime,
            $urlComponents['path'] ?? '/',
            $this->getCookieDomain($request, $urlComponents),
            'http' !== strtolower($urlComponents['scheme'] ?? 'https'),
            true,
            false,
            $this->cookieSameSite
        );
    }

    /**
     * Clears the mercureAuthorization cookie for the given hub.
     *
     * @param string|null $hub the hub to clear the cookie for
     */
    public function createClearCookie(Request $request, ?string $hub = null): Cookie
    {
        $hubInstance = $this->registry->getHub($hub);
        /** @var array $urlComponents */
        $urlComponents = parse_url($hubInstance->getPublicUrl());

        return Cookie::create(
            self::MERCURE_AUTHORIZATION_COOKIE_NAME,
            null,
            1,
            $urlComponents['path'] ?? '/',
            $this->getCookieDomain($request, $urlComponents),
            'http' !== strtolower($urlComponents['scheme'] ?? 'https'),
            true,
            false,
            $this->cookieSameSite
        );
    }

    private function getCookieDomain(Request $request, array $urlComponents): ?string
    {
        if (!isset($urlComponents['host'])) {
            return null;
        }

        $cookieDomain = strtolower($urlComponents['host']);
        $host = strtolower($request->getHost());
        if ($cookieDomain === $host) {
            return null;
        }

        if (str_ends_with($cookieDomain, '.'.$host)) {
            return $host;
        }

        $hostSegments = explode('.', $host);
        for ($i = 0, $length = \count($hostSegments) - 1; $i < $length; ++$i) {
            $currentDomain = implode('.', \array_slice($hostSegments, $i));
            $target = '.'.$currentDomain;
            if ($currentDomain === $cookieDomain || str_ends_with($cookieDomain, $target)) {
                return $target;
            }
        }

        throw new RuntimeException(sprintf('Unable to create authorization cookie for a hub on the different second-level domain "%s".', $cookieDomain));
    }

    private function updateCookies(Request $request, ?string $hub, Cookie $cookie): void
    {
        $cookies = $request->attributes->get('_mercure_authorization_cookies', []);
        if (\array_key_exists($hub, $cookies)) {
            $message = sprintf('The "mercureAuthorization" cookie for the "%s" has already been set. You cannot set it two times during the same request.', $hub ? "\"$hub\" hub" : 'default hub');
            throw new RuntimeException($message);
        }

        $cookies[$hub] = $cookie;
        $request->attributes->set('_mercure_authorization_cookies', $cookies);
    }
}
