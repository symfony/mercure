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

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class FrankenPhpHub implements HubInterface
{
    private $publicUrl;
    private $jwtFactory;

    public function __construct(
        string $publicUrl,
        ?TokenFactoryInterface $jwtFactory = null
    ) {
        $this->publicUrl = $publicUrl;
        $this->jwtFactory = $jwtFactory;
    }

    public function getPublicUrl(): string
    {
        return $this->publicUrl;
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->jwtFactory;
    }

    public function publish(Update $update): string
    {
        return mercure_publish(
            $update->getTopics(),
            $update->getData(),
            $update->isPrivate(),
            $update->getId(),
            $update->getType(),
            $update->getRetry()
        );
    }
}
