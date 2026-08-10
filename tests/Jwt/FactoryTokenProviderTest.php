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
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

final class FactoryTokenProviderTest extends TestCase
{
    public function testGetToken()
    {
        if (!class_exists(Key\InMemory::class)) {
            $this->markTestSkipped('requires lcobucci/jwt.');
        }

        $factory = new LcobucciFactory('looooooooooooongenoughtestsecret', 'hmac.sha256', null);
        $provider = new FactoryTokenProvider($factory, [new Grant([Grant::ACTION_PUBLISH], ['*']), new Grant([Grant::ACTION_SUBSCRIBE], [])]);

        $this->assertSame(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.ZTK3JhEKO1338LAgRMw6j0lkGRMoaZtU4EtGiAylAns',
            $provider->getJwt()
        );
    }

    public function testAdditionalClaimsAreForwardedToTheFactory()
    {
        $grants = [new Grant([Grant::ACTION_SUBSCRIBE], ['a']), new Grant([Grant::ACTION_PUBLISH], ['b'])];

        $factory = $this->createMock(TokenFactoryInterface::class);
        $factory
            ->expects($this->once())
            ->method('create')
            ->with($this->equalTo($grants), ['iss' => 'https://example.com'])
        ;

        $provider = new FactoryTokenProvider($factory, $grants, ['iss' => 'https://example.com']);
        $provider->getJwt();
    }
}
