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
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\MatcherInput;

final class MatcherInputTest extends TestCase
{
    public function testNormalizeNull()
    {
        $this->assertSame([], MatcherInput::normalize(null));
    }

    public function testNormalizeEmptyArray()
    {
        $this->assertSame([], MatcherInput::normalize([]));
    }

    public function testNormalizeFlatListIsWrappedAsExact()
    {
        $this->assertSame(['exact' => ['a', 'b']], MatcherInput::normalize(['a', 'b']));
    }

    public function testNormalizeMatcherTypedArrayIsReturnedAsIs()
    {
        $input = ['exact' => ['a'], 'urlpattern' => ['https://example.com/books/:id']];

        $this->assertSame($input, MatcherInput::normalize($input));
    }

    public function testFlattenToExactOrFailWithNull()
    {
        $this->assertSame([], MatcherInput::flattenToExactOrFail(null));
    }

    public function testFlattenToExactOrFailWithFlatList()
    {
        $this->assertSame(['a', 'b'], MatcherInput::flattenToExactOrFail(['a', 'b']));
    }

    public function testFlattenToExactOrFailWithPureExactMap()
    {
        $this->assertSame(['a', 'b'], MatcherInput::flattenToExactOrFail(['exact' => ['a', 'b']]));
    }

    public function testFlattenToExactOrFailThrowsOnNonExactMatcherType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Topic matcher type(s) "urlpattern" require the Mercure protocol 1.0');

        MatcherInput::flattenToExactOrFail(['exact' => ['a'], 'urlpattern' => ['https://example.com/books/:id']]);
    }

    public function testNormalizeGrantsNull()
    {
        $this->assertSame([], MatcherInput::normalizeGrants(null));
    }

    public function testNormalizeGrantsEmptyArray()
    {
        $this->assertSame([], MatcherInput::normalizeGrants([]));
    }

    public function testNormalizeGrantsString()
    {
        $this->assertEquals([new Grant([Grant::ACTION_SUBSCRIBE], ['foo'])], MatcherInput::normalizeGrants('foo'));
    }

    public function testNormalizeGrantsFlatTopicList()
    {
        $this->assertEquals([new Grant([Grant::ACTION_SUBSCRIBE], ['foo', 'bar'])], MatcherInput::normalizeGrants(['foo', 'bar']));
    }

    public function testNormalizeGrantsMatcherTypeMap()
    {
        $topics = ['urlpattern' => ['https://example.com/books/:id']];

        $this->assertEquals([new Grant([Grant::ACTION_SUBSCRIBE], $topics)], MatcherInput::normalizeGrants($topics));
    }

    public function testNormalizeGrantsGrantListIsReturnedAsIs()
    {
        $grants = [new Grant([Grant::ACTION_SUBSCRIBE], ['foo']), new Grant([Grant::ACTION_PUBLISH], ['bar'])];

        $this->assertSame($grants, MatcherInput::normalizeGrants($grants));
    }

    public function testNormalizeGrantsGrantShapedArrayList()
    {
        $grants = [
            ['actions' => [Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], 'topics' => ['foo'], 'payload' => 'x'],
            ['topics' => ['bar']],
        ];

        $this->assertEquals(
            [
                new Grant([Grant::ACTION_SUBSCRIBE, Grant::ACTION_PUBLISH], ['foo'], 'x'),
                new Grant([Grant::ACTION_SUBSCRIBE], ['bar']),
            ],
            MatcherInput::normalizeGrants($grants)
        );
    }
}
