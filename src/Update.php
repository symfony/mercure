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

namespace Symfony\Component\Mercure;

/**
 * Represents an update to send to the hub.
 *
 * @see https://github.com/dunglas/mercure/blob/main/spec/mercure.md#hub
 * @see https://github.com/dunglas/mercure/blob/main/update.go
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class Update
{
    /**
     * @var string[]
     */
    private readonly array $topics;

    /**
     * @param string|string[] $topics
     * @param ?string $type SSE type: https://developer.mozilla.org/en-US/docs/Web/API/EventSource
     */
    public function __construct(
        array|string $topics,
        private readonly string $data = '',
        private readonly bool $private = false,
        private readonly ?string $id = null,
        private readonly ?string $type = null,
        private readonly ?int $retry = null,
    ) {
        $this->topics = (array) $topics;
    }

    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRetry(): ?int
    {
        return $this->retry;
    }
}
