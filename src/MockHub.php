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
    private $url;
    private $jwtProvider;
    private $publisher;
    private $jwtFactory;
    private $publicUrl;

    /**
     * @param (callable(Update): string) $publisher
     */
    public function __construct(
        string $url,
        TokenProviderInterface $jwtProvider,
        callable $publisher,
        TokenFactoryInterface $jwtFactory = null,
        string $publicUrl = null
    ) {
        $this->url = $url;
        $this->jwtProvider = $jwtProvider;
        $this->publisher = $publisher;
        $this->jwtFactory = $jwtFactory;
        $this->publicUrl = $publicUrl;
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

    public function publish(Update $update): string
    {
        return ($this->publisher)($update);
    }
}
