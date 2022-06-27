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

namespace Symfony\Component\Mercure\Event;

use Symfony\Component\Mercure\Update;

final class UpdatePublished
{
    private $topics;
    private $data;
    private $private;
    private $id;
    private $type;
    private $retry;

    /**
     * @param string|string[] $topics
     */
    private function __construct(
        $topics,
        string $data = '',
        bool $private = false,
        string $id = null,
        string $type = null,
        int $retry = null
    ) {
        $this->topics = (array) $topics;
        $this->data = $data;
        $this->private = $private;
        $this->id = $id;
        $this->type = $type;
        $this->retry = $retry;
    }

    public static function fromUpdate(Update $update)
    {
        return new self(
            $update->getTopics(),
            $update->getData(),
            $update->isPrivate(),
            $update->getId(),
            $update->getType(),
            $update->getRetry()
        );
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
