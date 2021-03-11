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
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;

final class LcobucciFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(Key\InMemory::class)) {
            $this->markTestSkipped('requires lcobucci/jwt:^4.0.');
        }
    }

    public function testCreate(): void
    {
        $factory = new LcobucciFactory('!ChangeMe!');

        $this->assertSame(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.TywAqS7IPhvLdP7cXq_U-kXWUVPKFUyYz8NyfRe0vAU',
            $factory->create(['*'], [])
        );
    }

    public function testInvalidAlgorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported algorithm "md5", expected one of "hmac.sha256", "hmac.sha384", "hmac.sha512", "ecdsa.sha256", "ecdsa.sha384", "ecdsa.sha512", "rsa.sha256", "rsa.sha384", "rsa.sha512".');

        new LcobucciFactory('!ChangeMe!', 'md5');
    }
}
