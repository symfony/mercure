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

final class MockHub implements HubInterface
{
    /**
     * @var callable
     */
    private $publisher;

    private readonly string $cookieName;

    /**
     * @param (callable(Update): string) $publisher
     */
    public function __construct(
        private readonly string $url,
        private readonly TokenProviderInterface $jwtProvider,
        callable $publisher,
        private readonly ?TokenFactoryInterface $jwtFactory = null,
        private readonly ?string $publicUrl = null,
        private readonly ProtocolVersion $protocolVersion = ProtocolVersion::Legacy,
        ?string $cookieName = null,
    ) {
        $this->publisher = $publisher;
        $this->cookieName = $cookieName ?? $protocolVersion->getDefaultCookieName();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl ?? $this->url;
    }

    public function getProvider(): TokenProviderInterface
    {
        return $this->jwtProvider;
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
        return ($this->publisher)($update);
    }
}
