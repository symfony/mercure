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

namespace Symfony\Component\Mercure\Debug;

use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\ResetInterface;

trigger_deprecation('symfony/mercure', '0.5', 'Class "%s" is deprecated, use "%s" instead.', TraceablePublisher::class, TraceableHub::class);

/**
 * Traces updates for profiler.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @experimental
 *
 * @deprecated since Mercure 0.5
 */
final class TraceablePublisher implements PublisherInterface, ResetInterface
{
    private array $messages = [];

    public function __construct(
        private PublisherInterface $publisher,
        private Stopwatch $stopwatch,
    ) {
    }

    public function __invoke(Update $update): string
    {
        $this->stopwatch->start(__CLASS__);
        $content = ($this->publisher)($update);

        $e = $this->stopwatch->stop(__CLASS__);
        $this->messages[] = [
            'object' => $update,
            'duration' => $e->getDuration(),
            'memory' => $e->getMemory(),
        ];

        return $content;
    }

    public function reset(): void
    {
        $this->messages = [];
    }

    public function count(): int
    {
        return \count($this->messages);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getDuration(): float
    {
        return array_sum(array_map(function ($a) {
            return $a['duration'];
        }, $this->messages));
    }

    public function getMemory(): int
    {
        return (int) array_sum(array_map(static fn ($a) => $a['memory'], $this->messages));
    }
}
