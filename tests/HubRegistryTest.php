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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\HubRegistry;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;

class HubRegistryTest extends TestCase
{
    public function testGetHubByName(): void
    {
        $fooHub = new MockHub('fooUrl', new StaticTokenProvider('fooToken'), static function (): string { return 'foo'; });
        $barHub = new MockHub('barUrl', new StaticTokenProvider('barToken'), static function (): string { return 'bar'; });
        $registry = new HubRegistry($fooHub, ['foo' => $fooHub, 'bar' => $barHub]);

        $this->assertSame($fooHub, $registry->getHub('foo'));
    }

    public function testGetDefaultHub(): void
    {
        $fooHub = new MockHub('fooUrl', new StaticTokenProvider('fooToken'), static function (): string { return 'foo'; });
        $barHub = new MockHub('barUrl', new StaticTokenProvider('barToken'), static function (): string { return 'bar'; });
        $registry = new HubRegistry($fooHub, ['foo' => $fooHub, 'bar' => $barHub]);

        $this->assertSame($fooHub, $registry->getHub());
    }

    public function testGetMissingHubThrows(): void
    {
        $fooHub = new MockHub('fooUrl', new StaticTokenProvider('fooToken'), static function (): string { return 'foo'; });
        $registry = new HubRegistry($fooHub, ['foo' => $fooHub]);

        $this->expectException(InvalidArgumentException::class);
        $registry->getHub('bar');
    }

    public function testGetAllHubs(): void
    {
        $fooHub = new MockHub('fooUrl', new StaticTokenProvider('fooToken'), static function (): string { return 'foo'; });
        $barHub = new MockHub('barUrl', new StaticTokenProvider('barToken'), static function (): string { return 'bar'; });
        $registry = new HubRegistry($fooHub, ['foo' => $fooHub, 'bar' => $barHub]);

        $this->assertSame(['foo' => $fooHub, 'bar' => $barHub], $registry->all());
    }
}
