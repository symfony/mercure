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

final class Authorization
{
    private const MERCURE_AUTHORIZATION_COOKIE_NAME = 'mercureAuthorization';

    private $registry;
    private $cookieLifetime;

    /**
     * @param int|null $cookieLifetime in seconds, 0 for the current session, null to default to the vlaue of "session.cookie_lifetime". The "exp" field of the JWT will be set accordingly if not set explicitly, defaults to 1h in case of session cookies.
     */
    public function __construct(HubRegistry $registry, ?int $cookieLifetime = null)
    {
        $this->registry = $registry;
        $this->cookieLifetime = $cookieLifetime ?? (int) ini_get('session.cookie_lifetime');
    }

    /**
     * Create Authorization cookie for the given hub.
     *
     * @param string[]    $subscribe        a list of topics that the authorization cookie will allow subscribing to
     * @param string[]    $publish          a list of topics that the authorization cookie will allow publishing to
     * @param mixed[]     $additionalClaims an array of additional claims for the JWT
     * @param string|null $hub              the hub to generate the cookie for
     * @param int|null    $cookieLifetime   the lifetime of the cookie, the "exp" claim of the JWT will be set accordingly, set to null to use the default value and to 0 to set a session cookie (the default expiration time of the JWT will be 1 hour)
     */
    public function createCookie(Request $request, array $subscribe = [], array $publish = [], array $additionalClaims = [], ?string $hub = null, ?int $cookieLifetime = null): Cookie
    {
        $hubInstance = $this->registry->getHub($hub);
        $tokenFactory = $hubInstance->getFactory();
        if (null === $tokenFactory) {
            throw new InvalidArgumentException(sprintf('The %s hub does not contain a token factory.', $hub ? '"'.$hub.'"' : 'default'));
        }

        if (array_key_exists('exp', $additionalClaims)) {
            if (null !== $additionalClaims['exp'] && null === $cookieLifetime) {
                $cookieLifetime = $additionalClaims['exp'];
            }
        } else {
            $cookieLifetime = $cookieLifetime ?? $this->cookieLifetime;
            $additionalClaims['exp'] = new \DateTimeImmutable(0 === $cookieLifetime ? '+1 hour' : "+{$cookieLifetime} seconds");
        }

        $token = $tokenFactory->create($subscribe, $publish, $additionalClaims);
        $url = $hubInstance->getPublicUrl();
        /** @var array $urlComponents */
        $urlComponents = parse_url($url);



        return Cookie::create(
            self::MERCURE_AUTHORIZATION_COOKIE_NAME,
            $token,
            $cookieLifetime ?? $this->cookieLifetime,
            $urlComponents['path'] ?? '/',
            $this->getCookieDomain($request, $urlComponents),
            'http' !== strtolower($urlComponents['scheme'] ?? 'https'),
            true,
            false,
            Cookie::SAMESITE_STRICT
        );
    }

    private function getCookieDomain(Request $request, array $urlComponents): ?string
    {
        if (!isset($urlComponents['host'])) {
            return null;
        }

        $cookieDomain = strtolower($urlComponents['host']);
        $currentDomain = strtolower($request->getHost());

        if ($cookieDomain === $currentDomain) {
            return null;
        }

        if (!str_ends_with($cookieDomain, ".${currentDomain}")) {
            throw new RuntimeException(sprintf('Unable to create authorization cookie for a hub on the different second-level domain "%s".', $cookieDomain));
        }

        return $cookieDomain;
    }
}
