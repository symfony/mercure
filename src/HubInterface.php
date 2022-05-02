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
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
interface HubInterface
{
    /**
     * Returns the Hub internal URL.
     */
    public function getUrl(): string;

    /**
     * Returns the Hub public URL.
     *
     * If the public URL is not configured, this method MUST return
     * the internal URL {@see HubInterface::getUrl()}.
     */
    public function getPublicUrl(): string;

    /**
     * Return the token provider used by this Hub.
     */
    public function getProvider(): TokenProviderInterface;

    /**
     * Return the token factory associated with this Hub.
     */
    public function getFactory(): ?TokenFactoryInterface;

    /**
     * Publish an update to this Hub.
     */
    public function publish(Update $update): string;

    /**
     * Publish an update to this Hub.
     *
     */
    public function publishFast(Update $update, ?string $token = null): ResponseInterface;
    /**
     * Publish updates to this Hub.
     * @param Iterable<Update> $updates
     */
    public function publishBatch($updates, bool $fireAndForget = false): array;
}
