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
use Symfony\Component\Mercure\MatcherInput;

final class MatcherInputTest extends TestCase
{
    public function testNormalizeNull(): void
    {
        $this->assertSame([], MatcherInput::normalize(null));
    }

    public function testNormalizeEmptyArray(): void
    {
        $this->assertSame([], MatcherInput::normalize([]));
    }

    public function testNormalizeFlatListIsWrappedAsExact(): void
    {
        $this->assertSame(['exact' => ['a', 'b']], MatcherInput::normalize(['a', 'b']));
    }

    public function testNormalizeMatcherTypedArrayIsReturnedAsIs(): void
    {
        $input = ['exact' => ['a'], 'urlpattern' => ['https://example.com/books/:id']];

        $this->assertSame($input, MatcherInput::normalize($input));
    }

    public function testFlattenToExactOrFailWithNull(): void
    {
        $this->assertSame([], MatcherInput::flattenToExactOrFail(null));
    }

    public function testFlattenToExactOrFailWithFlatList(): void
    {
        $this->assertSame(['a', 'b'], MatcherInput::flattenToExactOrFail(['a', 'b']));
    }

    public function testFlattenToExactOrFailWithPureExactMap(): void
    {
        $this->assertSame(['a', 'b'], MatcherInput::flattenToExactOrFail(['exact' => ['a', 'b']]));
    }

    public function testFlattenToExactOrFailThrowsOnNonExactMatcherType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Topic matcher type(s) "urlpattern" require the Mercure protocol 1.0');

        MatcherInput::flattenToExactOrFail(['exact' => ['a'], 'urlpattern' => ['https://example.com/books/:id']]);
    }
}
