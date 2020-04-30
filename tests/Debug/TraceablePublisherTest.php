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

namespace Symfony\Component\Mercure\Tests\Debug;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Debug\TraceablePublisher;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class TraceablePublisherTest extends TestCase
{
    const URL = 'https://demo.mercure.rocks/.well-known/mercure';
    const JWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InN1YnNjcmliZSI6WyJmb28iLCJiYXIiXSwicHVibGlzaCI6WyJmb28iXX19.afLx2f2ut3YgNVFStCx95Zm_UND1mZJ69OenXaDuZL8';

    public function testPublish(): void
    {
        $publisher = new Publisher(self::URL, function (): string {
            return self::JWT;
        });
        $traceablePublisher = new TraceablePublisher($publisher, new Stopwatch());
        $update = new Update(
            'https://demo.mercure.rocks/demo/books/1.jsonld',
            'Hi from Symfony!',
            [],
            'id',
            null,
            3
        );
        $traceablePublisher($update);

        $this->assertEquals(1, $traceablePublisher->count());
        $this->assertSame($update, $traceablePublisher->getMessages()[0]['object']);
    }
}
