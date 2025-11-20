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
     */
    public function testCreateUpdate($topics, $data, bool $private = false, ?string $id = null, ?string $type = null, ?int $retry = null)
    {
        $update = new Update($topics, $data, $private, $id, $type, $retry);
        $this->assertSame((array) $topics, $update->getTopics());
        $this->assertSame($data, $update->getData());
        $this->assertSame($private, $update->isPrivate());
        $this->assertSame($id, $update->getId());
        $this->assertSame($type, $update->getType());
        $this->assertSame($retry, $update->getRetry());
    }

    public function updateProvider(): array
    {
        return [
            ['http://example.com/foo', 'payload', true, 'id', 'type', 1936],
            [['https://mercure.rocks', 'https://github.com/dunglas/mercure'], 'payload'],
        ];
    }
}
