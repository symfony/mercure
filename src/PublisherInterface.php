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

trigger_deprecation('symfony/mercure', '0.5', 'Interface "%s" is deprecated, use "%s" instead.', PublisherInterface::class, HubInterface::class);

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 *
 * @deprecated
 */
interface PublisherInterface
{
    public function __invoke(Update $update): string;
}
