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
use Symfony\Component\Mercure\Matcher;
use Symfony\Component\Mercure\MatcherType;
use Symfony\Component\Mercure\MercureVersion;

final class MatcherTest extends TestCase
{
    public function testStaticFactories(): void
    {
        $this->assertSame(MatcherType::Topic, Matcher::topic('/books/{id}')->type);
        $this->assertSame(MatcherType::Exact, Matcher::exact('/books/1')->type);
        $this->assertSame(MatcherType::URLPattern, Matcher::urlPattern('https://example.com/books/:id')->type);
        $this->assertSame(MatcherType::Regexp, Matcher::regexp('^chat-room-[0-9]+$')->type);
        $this->assertSame(['x' => 1], Matcher::exact('foo', ['x' => 1])->payload);
    }

    public function testFromAnyPassesThroughMatcher(): void
    {
        $m = Matcher::regexp('^foo$');
        $this->assertSame($m, Matcher::fromAny($m, MercureVersion::V1));
    }

    public function testFromAnyStringUsesVersionDefault(): void
    {
        $this->assertSame(MatcherType::Topic, Matcher::fromAny('/books/{id}', MercureVersion::V0)->type);
        $this->assertSame(MatcherType::Exact, Matcher::fromAny('/books/1', MercureVersion::V1)->type);
    }

    public function testFromAnySingleKeyArray(): void
    {
        $m = Matcher::fromAny(['matchURLPattern' => 'https://example.com/books/:id'], MercureVersion::V1);
        $this->assertSame(MatcherType::URLPattern, $m->type);
        $this->assertSame('https://example.com/books/:id', $m->value);
    }

    public function testFromAnyV1ClaimShape(): void
    {
        $m = Matcher::fromAny(['match' => 'foo', 'matchType' => 'Regexp', 'payload' => ['a' => 1]], MercureVersion::V1);
        $this->assertSame(MatcherType::Regexp, $m->type);
        $this->assertSame('foo', $m->value);
        $this->assertSame(['a' => 1], $m->payload);
    }

    public function testFromAnyInvalidMatchType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Matcher::fromAny(['match' => 'foo', 'matchType' => 'Bogus'], MercureVersion::V1);
    }

    public function testFromAnyInvalidInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Matcher::fromAny(42, MercureVersion::V1);
    }
}
