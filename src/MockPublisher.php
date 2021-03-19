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

    /**
     * @deprecated since 0.5, use {@see PublisherInterface::publish} instead.
     */
    public function __invoke(Update $update): string
    {
        trigger_deprecation('symfony/mercure', '0.5', 'Method "%s()" is deprecated, use "%s::publish()" instead.', __METHOD__, __CLASS__);

        return ($this->callable)($update);
    }
}
