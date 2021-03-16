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

use Lcobucci\JWT\Signer\Key;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Jwt\FactoryTokenProvider;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;

final class FactoryTokenProviderTest extends TestCase
{
    public function testGetToken(): void
    {
        if (!class_exists(Key\InMemory::class)) {
            $this->markTestSkipped('requires lcobucci/jwt:^4.0.');
        }

        $factory = new LcobucciFactory('!ChangeMe!');
        $provider = new FactoryTokenProvider($factory, [], ['*']);

        $this->assertSame(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.TywAqS7IPhvLdP7cXq_U-kXWUVPKFUyYz8NyfRe0vAU',
            $provider->getJwt()
        );
    }
}
