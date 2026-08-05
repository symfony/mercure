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

namespace Symfony\Component\Mercure\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Internal\JwtLifetime;

final class JwtLifetimeTest extends TestCase
{
    public function testNullMeansNoAutoExpiration()
    {
        $this->assertNull(JwtLifetime::resolve(null));
    }

    public function testExplicitValueIsReturnedAsIs()
    {
        $this->assertSame(3600, JwtLifetime::resolve(3600));
    }

    public function testZeroFallsBackToSessionCookieLifetimeOrDefault()
    {
        $this->assertSame((int) \ini_get('session.cookie_lifetime') ?: 3600, JwtLifetime::resolve(0));
    }
}
