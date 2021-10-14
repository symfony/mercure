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
            $this->markTestSkipped('requires lcobucci/jwt.');
        }
    }

    /**
     * @dataProvider provideCreateCases
     */
    public function testCreate(string $algorithm, array $subscribe, array $publish, array $additionalClaims, string $expectedJwt): void
    {
        $factory = new LcobucciFactory('!ChangeMe!', $algorithm, null);

        $this->assertSame(
            $expectedJwt,
            $factory->create($subscribe, $publish, $additionalClaims)
        );
    }

    public function testInvalidAlgorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported algorithm "md5", expected one of "hmac.sha256", "hmac.sha384", "hmac.sha512", "ecdsa.sha256", "ecdsa.sha384", "ecdsa.sha512", "rsa.sha256", "rsa.sha384", "rsa.sha512".');

        new LcobucciFactory('!ChangeMe!', 'md5');
    }

    public function provideCreateCases(): iterable
    {
        yield [
            'algorithm' => 'hmac.sha256',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.TywAqS7IPhvLdP7cXq_U-kXWUVPKFUyYz8NyfRe0vAU',
        ];

        yield [
            'algorithm' => 'hmac.sha384',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.ABjz1sGkZ_aOZupf4oq3E4GjfhX__GioTFsrzd7KnbgtwDx0pTOohqjgOjN6vSOe',
        ];

        yield [
            'algorithm' => 'hmac.sha512',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.eK12cNN2fGAaSnFjSYaqTrlKWFtOfKh5ILek_LN-qjG6tGpPKBXGknkQl7a_WrN1PYdgUhw3jPMtpk0HLO6VFA',
        ];

        yield [
            'algorithm' => 'hmac.sha256',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [
                'mercure' => [
                    'publish' => ['overridden'],
                    'subscribe' => ['overridden'],
                    'payload' => ['foo' => 'bar'],
                ],
            ],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsib3ZlcnJpZGRlbiJdLCJzdWJzY3JpYmUiOlsib3ZlcnJpZGRlbiJdLCJwYXlsb2FkIjp7ImZvbyI6ImJhciJ9fX0.EBddBO8x1UNIiyZLknllC8nvJV7XktOwCKbZbOuerh0',
        ];
    }
}
