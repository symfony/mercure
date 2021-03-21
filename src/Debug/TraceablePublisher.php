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

/**
 * Traces updates for profiler.
 *
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @experimental
 */
final class TraceablePublisher implements PublisherInterface, ResetInterface
{
    private $publisher;
    private $stopwatch;
    private $messages = [];

    public function __construct(PublisherInterface $publisher, Stopwatch $stopwatch)
    {
        $this->publisher = $publisher;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @deprecated since symfony/mercure 0.5
     */
    public function __invoke(Update $update): string
    {
        trigger_deprecation('symfony/mercure', '0.5', 'Method "%s()" is deprecated, use "%s::publish()" instead.', __METHOD__, __CLASS__);

        return $this->publish($update);
    }

    public function publish(Update $update): string
    {
        $this->stopwatch->start(__CLASS__);

        if (method_exists($this->publisher, 'publish')) {
            $content = $this->publisher->publish($update);
        } else {
            trigger_deprecation('symfony/mercure', '0.5', 'Method "%1$s()" is deprecated, implement "%1$s::publish()" instead.', PublisherInterface::class);
            $content = ($this->publisher)($update);
        }

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
        return (int) array_sum(array_map(function ($a) {
            return $a['memory'];
        }, $this->messages));
    }
}
