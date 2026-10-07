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
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\FrankenPhpHub;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\Component\Mercure\Update;

class FrankenPhpHubTest extends TestCase
{
    public const PUBLIC_URL = 'https://example.com/.well-known/mercure';

    public function testSubscribingWorksWithoutFrankenPhp()
    {
        if (\function_exists('mercure_publish')) {
            $this->markTestSkipped('FrankenPHP\'s mercure_publish() function is available.');
        }

        $hub = new FrankenPhpHub(self::PUBLIC_URL, null, 'custom_cookie', ProtocolVersion::Legacy);

        $this->assertSame(self::PUBLIC_URL, $hub->getPublicUrl());
        $this->assertSame('custom_cookie', $hub->getCookieName());
        $this->assertSame(ProtocolVersion::Legacy, $hub->getProtocolVersion());
    }

    public function testPublishWithoutFrankenPhpThrows()
    {
        if (\function_exists('mercure_publish')) {
            $this->markTestSkipped('FrankenPHP\'s mercure_publish() function is available.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The mercure_publish() function is not available');

        (new FrankenPhpHub(self::PUBLIC_URL))->publish(new Update('https://example.com/books/1', 'data'));
    }

    /**
     * @runInSeparateProcess
     *
     * @preserveGlobalState disabled
     */
    public function testPublish()
    {
        require __DIR__.'/Fixtures/mercure_publish.php';

        $this->assertSame('urn:uuid:published', (new FrankenPhpHub(self::PUBLIC_URL))->publish(new Update('https://example.com/books/1', 'data')));
    }
}
