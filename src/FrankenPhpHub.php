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
        private readonly ProtocolVersion $protocolVersion = ProtocolVersion::Legacy,
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
