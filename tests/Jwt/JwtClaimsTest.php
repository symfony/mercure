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

namespace Symfony\Component\Mercure\Tests\Jwt;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\JwtClaims;

final class JwtClaimsTest extends TestCase
{
    private const REQUIRED_CLAIMS = [
        'iss' => 'https://example.com',
        'aud' => 'https://hub.example.com/.well-known/mercure',
        'sub' => 'urn:uuid:1',
        'client_id' => 'https://example.com',
    ];

    public function testResolveLifetimeNullMeansNoAutoExpiration()
    {
        $this->assertNull(JwtClaims::resolveLifetime(null));
    }

    public function testResolveLifetimeExplicitValueIsReturnedAsIs()
    {
        $this->assertSame(3600, JwtClaims::resolveLifetime(3600));
    }

    public function testResolveLifetimeZeroFallsBackToSessionCookieLifetimeOrDefault()
    {
        $this->assertSame((int) \ini_get('session.cookie_lifetime') ?: 3600, JwtClaims::resolveLifetime(0));
    }

    public function testMultipleGrantsProduceMultipleEntries()
    {
        $claims = JwtClaims::buildAuthorizationDetails(
            [new Grant([Grant::ACTION_SUBSCRIBE], ['a']), new Grant([Grant::ACTION_PUBLISH], ['b'])],
            self::REQUIRED_CLAIMS,
            null
        );

        $this->assertCount(2, $claims['authorization_details']);
        $this->assertSame(['subscribe'], $claims['authorization_details'][0]['actions']);
        $this->assertSame(['publish'], $claims['authorization_details'][1]['actions']);
    }

    public function testSingleGrantWithMultipleActionsProducesOneEntry()
    {
        $claims = JwtClaims::buildAuthorizationDetails(
            [new Grant([Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], ['a'])],
            self::REQUIRED_CLAIMS,
            null
        );

        $this->assertCount(1, $claims['authorization_details']);
        $this->assertSame(['subscribe', 'publish'], $claims['authorization_details'][0]['actions']);
    }

    public function testGrantWithNoActionsThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A grant must declare at least one action.');

        JwtClaims::buildAuthorizationDetails([new Grant([], ['a'])], self::REQUIRED_CLAIMS, null);
    }

    public function testEmptyTopicsGrantContributesNothing()
    {
        $claims = JwtClaims::buildAuthorizationDetails(
            [new Grant([Grant::ACTION_SUBSCRIBE], [])],
            self::REQUIRED_CLAIMS,
            null
        );

        $this->assertArrayNotHasKey('authorization_details', $claims);
    }

    public function testEmptyTopicsGrantWithPayloadThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires at least one topic');

        JwtClaims::buildAuthorizationDetails([new Grant([Grant::ACTION_SUBSCRIBE], [], ['foo' => 'bar'])], self::REQUIRED_CLAIMS, null);
    }

    public function testPayloadOnNonSubscribeGrantThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only meaningful when its "actions" include "subscribe"');

        JwtClaims::buildAuthorizationDetails([new Grant([Grant::ACTION_PUBLISH], ['a'], ['foo' => 'bar'])], self::REQUIRED_CLAIMS, null);
    }
}
