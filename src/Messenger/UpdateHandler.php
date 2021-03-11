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

use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class UpdateHandler
{
    private $publisher;

    public function __construct(PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    public function __invoke(Update $update): void
    {
        if (method_exists($this->publisher, 'publish')) {
            $this->publisher->publish($update);
        } else {
            ($this->publisher)($update);
        }
    }
}
