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

use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;

/**
 * @author Kévin Dunglas <kevin@dunglas.dev>
 *
 * @experimental
 */
final class FrankenPhpHub implements HubInterface
{
    private readonly string $cookieName;

    public function __construct(
        private readonly string $publicUrl,
        private readonly ?TokenFactoryInterface $jwtFactory = null,
        ?string $cookieName = null,
        private readonly ProtocolVersion $protocolVersion = ProtocolVersion::V1,
    ) {
        $this->cookieName = $cookieName ?? $protocolVersion->getDefaultCookieName();
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->jwtFactory;
    }

    public function getProtocolVersion(): ProtocolVersion
    {
        return $this->protocolVersion;
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    public function publish(Update $update): string
    {
        // checked here rather than in the constructor: subscribing (public URL, cookie, token
        // factory) works anywhere, e.g. under the CLI, where FrankenPHP doesn't define the function
        if (!\function_exists('mercure_publish')) {
            throw new RuntimeException('The mercure_publish() function is not available: publish from a request served by FrankenPHP with its "mercure" directive enabled, or use Hub to publish to a hub URL.');
        }

        return mercure_publish(
            $update->getTopics(),
            $update->getData(),
            $update->isPrivate(),
            $update->getId(),
            $update->getType(),
            $update->getRetry(),
        );
    }
}
