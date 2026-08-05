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
use Symfony\Component\Mercure\Jwt\DefaultClaimsTokenFactory;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

final class DefaultClaimsTokenFactoryTest extends TestCase
{
    public function testDefaultClaimsAreMergedIn()
    {
        $decorated = $this->createMock(TokenFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->with(['a'], ['b'], ['iss' => 'https://example.com', 'sub' => 'default-sub'])
        ;

        $factory = new DefaultClaimsTokenFactory($decorated, ['iss' => 'https://example.com', 'sub' => 'default-sub']);
        $factory->create(['a'], ['b']);
    }

    public function testCallTimeClaimsOverrideDefaults()
    {
        $decorated = $this->createMock(TokenFactoryInterface::class);
        $decorated
            ->expects($this->once())
            ->method('create')
            ->with(['a'], ['b'], ['sub' => 'per-request-sub', 'iss' => 'https://example.com'])
        ;

        $factory = new DefaultClaimsTokenFactory($decorated, ['iss' => 'https://example.com', 'sub' => 'default-sub']);
        $factory->create(['a'], ['b'], ['sub' => 'per-request-sub']);
    }
}
