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

final class MockPublisher implements PublisherInterface
{
    private $callable;

    /**
     * @param (callable(Update): string) $callable
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    public function publish(Update $update): string
    {
        return ($this->callable)($update);
    }
}
