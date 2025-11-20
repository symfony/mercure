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

use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
interface HubInterface
{
    /**
     * Returns the Hub public URL.
     *
     * If the public URL is not configured and the hub also implements the {@see RemoteHubInterface}, this method MUST return
     * the internal URL {@see RemoteHubInterface::getUrl()}.
     */
    public function getPublicUrl(): string;

    /**
     * Return the token factory associated with this Hub.
     */
    public function getFactory(): ?TokenFactoryInterface;

    /**
     * Publish an update to this Hub.
     */
    public function publish(Update $update): string;
}
