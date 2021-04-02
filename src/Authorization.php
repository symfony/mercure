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
    private $jwtLifetime;

    /**
     * @param int|null $jwtLifetime If not null, an "exp" claim is always set to now + $jwtLifetime (in seconds)
     */
    public function __construct(HubRegistry $registry, int $jwtLifetime = null)
    {
        $this->registry = $registry;
        $this->jwtLifetime = $jwtLifetime;
    }

    /**
     * Create Authorization cookie for the given hub.
     *
     * @param string[]    $subscribe        a list of topics that the authorization cookie will allow subscribing to
     * @param string[]    $publish          a list of topics that the authorization cookie will allow publishing to
     * @param mixed[]     $additionalClaims an array of additional claims for the JWT
     * @param string|null $hub              the hub to generate the cookie for
     */
    public function createCookie(Request $request, array $subscribe = [], array $publish = [], array $additionalClaims = [], ?string $hub = null): Cookie
    {
        $hubInstance = $this->registry->getHub($hub);
        $tokenFactory = $hubInstance->getFactory();
        if (null === $tokenFactory) {
            throw new InvalidArgumentException(sprintf('The %s hub does not contain a token factory.', $hub ? '"'.$hub.'"' : 'default'));
        }

        if (null !== $this->jwtLifetime && !isset($additionalClaims['exp'])) {
            $additionalClaims['exp'] = new \DateTimeImmutable("+{$this->jwtLifetime} seconds");
        }

        $token = $tokenFactory->create($subscribe, $publish, $additionalClaims);
        $url = $hubInstance->getPublicUrl();
        /** @var array $urlComponents */
        $urlComponents = parse_url($url);

        $cookie = Cookie::create(self::MERCURE_AUTHORIZATION_COOKIE_NAME)
            ->withValue($token)
            ->withPath(($urlComponents['path'] ?? '/'))
            ->withSecure('http' !== strtolower($urlComponents['scheme'] ?? 'https'))
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_STRICT);

        if (isset($urlComponents['host'])) {
            $cookieDomain = strtolower($urlComponents['host']);
            $currentDomain = strtolower($request->getHost());

            if ($cookieDomain === $currentDomain) {
                return $cookie;
            }

            if (!str_ends_with($cookieDomain, ".${currentDomain}")) {
                throw new RuntimeException(sprintf('Unable to create authorization cookie for external domain "%s".', $cookieDomain));
            }

            $cookie = $cookie->withDomain($cookieDomain);
        }

        return $cookie;
    }
}
