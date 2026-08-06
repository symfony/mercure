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
use Symfony\Component\Mercure\Jwt\Grant;

/**
 * Manages the subscriber authorization cookie.
 */
final class Authorization
{
    private readonly int $cookieLifetime;

    /**
     * @param int|null                $cookieLifetime in seconds, 0 for the current session, null to default to the value of "session.cookie_lifetime" or 3600 if "session.cookie_lifetime" is set to 0. The "exp" field of the JWT will be set accordingly if not set explicitly, defaults to 1h in case of session cookies.
     * @param Cookie::SAMESITE_*|null $cookieSameSite
     */
    public function __construct(
        private readonly HubRegistry $registry,
        ?int $cookieLifetime = null,
        private readonly ?string $cookieSameSite = Cookie::SAMESITE_STRICT,
    ) {
        $this->cookieLifetime = $cookieLifetime ?? (int) \ini_get('session.cookie_lifetime');
    }

    /**
     * Sets the subscriber authorization cookie for the given hub.
     *
     * @param string[]|string|null $subscribe        a topic or a list of topics that the authorization cookie will allow subscribing to
     * @param string[]|string|null $publish          a list of topics that the authorization cookie will allow publishing to
     * @param array<string, mixed> $additionalClaims an array of additional claims for the JWT
     * @param string|null          $hub              the hub to generate the cookie for
     * @param mixed                $payload          data attached to the subscribe grant (Mercure protocol 1.0 hubs only); requires a non-null $subscribe
     */
    public function setCookie(Request $request, string|array|null $subscribe = [], string|array|null $publish = [], array $additionalClaims = [], ?string $hub = null, mixed $payload = null): void
    {
        $this->updateCookies($request, $hub, $this->createCookie($request, $subscribe, $publish, $additionalClaims, $hub, $payload));
    }

    /**
     * Clears the subscriber authorization cookie for the given hub.
     *
     * @param string|null $hub the hub to clear the cookie for
     */
    public function clearCookie(Request $request, ?string $hub = null): void
    {
        $this->updateCookies($request, $hub, $this->createClearCookie($request, $hub));
    }

    /**
     * Creates the subscriber authorization cookie for the given hub.
     *
     * @param string[]|string|null $subscribe        a list of topics that the authorization cookie will allow subscribing to
     * @param string[]|string|null $publish          a list of topics that the authorization cookie will allow publishing to
     * @param array<string, mixed> $additionalClaims an array of additional claims for the JWT
     * @param string|null          $hub              the hub to generate the cookie for
     * @param mixed                $payload          data attached to the subscribe grant (Mercure protocol 1.0 hubs only); requires a non-null $subscribe
     */
    public function createCookie(Request $request, string|array|null $subscribe = [], string|array|null $publish = [], array $additionalClaims = [], ?string $hub = null, mixed $payload = null): Cookie
    {
        $hubInstance = $this->registry->getHub($hub);
        $tokenFactory = $hubInstance->getFactory();
        if (null === $tokenFactory) {
            $message = \sprintf('The %s hub does not contain a token factory.', $hub ? "\"$hub\"" : 'default');
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

        $grants = [];
        if (null !== $subscribe) {
            $grants[] = new Grant([Grant::ACTION_SUBSCRIBE], (array) $subscribe, $payload);
        } elseif (null !== $payload) {
            throw new InvalidArgumentException('A "payload" requires a non-null "$subscribe".');
        }
        if (null !== $publish) {
            $grants[] = new Grant([Grant::ACTION_PUBLISH], (array) $publish);
        }

        $token = $tokenFactory->create($grants, $additionalClaims);
        $url = $hubInstance->getPublicUrl();
        /** @var array $urlComponents */
        $urlComponents = parse_url($url);

        if (!$cookieLifetime instanceof \DateTimeInterface && 0 !== $cookieLifetime) {
            $cookieLifetime = new \DateTimeImmutable("+{$cookieLifetime} seconds");
        }

        $path = $urlComponents['path'] ?? '/';
        $domain = $this->getCookieDomain($request, $urlComponents);
        $secure = 'http' !== strtolower($urlComponents['scheme'] ?? 'https');

        return Cookie::create(
            $hubInstance->getCookieName(),
            $token,
            $cookieLifetime,
            $path,
            $domain,
            $secure,
            true,
            false,
            $this->cookieSameSite
        );
    }

    /**
     * Clears the subscriber authorization cookie for the given hub.
     *
     * @param string|null $hub the hub to clear the cookie for
     */
    public function createClearCookie(Request $request, ?string $hub = null): Cookie
    {
        $hubInstance = $this->registry->getHub($hub);
        /** @var array $urlComponents */
        $urlComponents = parse_url($hubInstance->getPublicUrl());

        $path = $urlComponents['path'] ?? '/';
        $domain = $this->getCookieDomain($request, $urlComponents);
        $secure = 'http' !== strtolower($urlComponents['scheme'] ?? 'https');

        return Cookie::create(
            $hubInstance->getCookieName(),
            null,
            1,
            $path,
            $domain,
            $secure,
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

        throw new RuntimeException(\sprintf('Unable to create authorization cookie for a hub on the different second-level domain "%s".', $cookieDomain));
    }

    private function updateCookies(Request $request, ?string $hub, Cookie $cookie): void
    {
        $hub = (string) $hub;

        $cookies = $request->attributes->get('_mercure_authorization_cookies', []);
        if (\array_key_exists($hub, $cookies)) {
            $message = \sprintf('The subscriber authorization cookie for the "%s" has already been set. You cannot set it two times during the same request.', $hub ? "\"$hub\" hub" : 'default hub');
            throw new RuntimeException($message);
        }

        $cookies[$hub] = $cookie;
        $request->attributes->set('_mercure_authorization_cookies', $cookies);
    }
}
