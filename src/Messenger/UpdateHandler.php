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

namespace Symfony\Component\Mercure\Messenger;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class UpdateHandler
{
    private $hub;

    /**
     * @param HubInterface|PublisherInterface $hub
     */
    public function __construct($hub)
    {
        $this->hub = $hub;
    }

    public function __invoke(Update $update): void
    {
        if ($this->hub instanceof HubInterface) {
            $this->hub->publish($update);
        } else {
            ($this->hub)($update);
        }
    }
}
