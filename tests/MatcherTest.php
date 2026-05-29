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
use Symfony\Component\Mercure\Matcher;

final class MatcherTest extends TestCase
{
    public function testDefaults(): void
    {
        $m = new Matcher('https://example.com/books/1');

        $this->assertSame('https://example.com/books/1', $m->match);
        $this->assertNull($m->matchType);
        $this->assertNull($m->payload);
    }

    public function testJsonSerializeBare(): void
    {
        $this->assertSame(
            '{"match":"https:\/\/example.com\/books\/1"}',
            json_encode(new Matcher('https://example.com/books/1')),
        );
    }

    public function testJsonSerializeWithMatchType(): void
    {
        $this->assertSame(
            '{"match":"https:\/\/example.com\/books\/:id","matchType":"URLPattern"}',
            json_encode(new Matcher('https://example.com/books/:id', 'URLPattern')),
        );
    }

    public function testJsonSerializeWithPayload(): void
    {
        $this->assertSame(
            '{"match":"^chat-room-[0-9]+$","matchType":"Regexp","payload":{"role":"reader"}}',
            json_encode(new Matcher('^chat-room-[0-9]+$', 'Regexp', ['role' => 'reader'])),
        );
    }

    public function testCustomMatchTypeIsAccepted(): void
    {
        $m = new Matcher('topic == "/books/1"', 'CEL');

        $this->assertSame('CEL', $m->matchType);
        $this->assertSame(
            '{"match":"topic == \"\/books\/1\"","matchType":"CEL"}',
            json_encode($m),
        );
    }
}
