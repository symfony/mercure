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

namespace Symfony\Component\Mercure\Tests;

use Lcobucci\JWT\Signer\Key\InMemory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class AuthorizationTest extends TestCase
{
    public function testJwtLifetime(): void
    {
        if (!class_exists(InMemory::class)) {
            $this->markTestSkipped('"lcobucci/jwt" is not installed');
        }

        $registry = new HubRegistry(new MockHub(
            'https://example.com/.well-known/mercure',
            new StaticTokenProvider('foo.bar.baz'),
            function (Update $u): string { return 'dummy'; },
            new LcobucciFactory('secret', 'hmac.sha256', 3600)
        ));

        $authorization = new Authorization($registry);
        $cookie = $authorization->createCookie(Request::create('https://example.com'));

        $payload = json_decode(base64_decode(explode('.', $cookie->getValue())[1], true), true);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertIsFloat($payload['exp']);
    }
}
