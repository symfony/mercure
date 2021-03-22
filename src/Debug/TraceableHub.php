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

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Traces updates for profiler.
 *
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class TraceableHub implements HubInterface, ResetInterface
{
    private $hub;
    private $stopwatch;
    private $messages = [];

    public function __construct(HubInterface $hub, Stopwatch $stopwatch)
    {
        $this->hub = $hub;
        $this->stopwatch = $stopwatch;
    }

    public function getUrl(): string
    {
        return $this->hub->getUrl();
    }

    public function getPublicUrl(): string
    {
        return $this->hub->getPublicUrl();
    }

    public function getProvider(): TokenProviderInterface
    {
        return $this->hub->getProvider();
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->hub->getFactory();
    }

    public function publish(Update $update): string
    {
        $this->stopwatch->start(__CLASS__);
        $content = $this->hub->publish($update);

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
