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

use Symfony\Component\Mercure\Exception\InvalidArgumentException;

final class HubRegistry
{
    private $defaultHub;
    private $hubs;

    /**
     * @param array<string, HubInterface> $hubs An array of hub instances, where the keys are the names
     */
    public function __construct(HubInterface $defaultHub, array $hubs = [])
    {
        $this->defaultHub = $defaultHub;
        $this->hubs = $hubs;
    }

    public function getHub(?string $name = null): HubInterface
    {
        if (null === $name) {
            return $this->defaultHub;
        }

        if (!isset($this->hubs[$name])) {
            throw new InvalidArgumentException('Invalid hub name provided.');
        }

        return $this->hubs[$name];
    }

    /**
     * @return array<string, HubInterface>
     */
    public function all(): array
    {
        return $this->hubs;
    }
}
