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
use Symfony\Component\Mercure\Update;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class UpdateTest extends TestCase
{
    /**
     * @dataProvider updateProvider
     *
     * @param mixed $topics
     * @param mixed $data
     */
    public function testCreateUpdate($topics, $data, array $targets = [], string $id = null, string $type = null, int $retry = null)
    {
        $update = new Update($topics, $data, $targets, $id, $type, $retry);
        $this->assertSame((array) $topics, $update->getTopics());
        $this->assertSame($data, $update->getData());
        $this->assertSame($targets, $update->getTargets());
        $this->assertSame($id, $update->getId());
        $this->assertSame($type, $update->getType());
        $this->assertSame($retry, $update->getRetry());
    }

    public function updateProvider(): array
    {
        return [
            ['http://example.com/foo', 'payload', ['user-1', 'group-a'], 'id', 'type', 1936],
            [['https://mercure.rocks', 'https://github.com/dunglas/mercure'], 'payload'],
        ];
    }

    public function testInvalidTopic()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Update(1, 'data');
    }
}
