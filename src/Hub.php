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

use Symfony\Component\Mercure\Jwt\TokenProviderInterface;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class Hub
{
    private $url;
    private $jwtProvider;

    public function __construct(string $url, TokenProviderInterface $jwtProvider)
    {
        $this->url = $url;
        $this->jwtProvider = $jwtProvider;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getProvider(): TokenProviderInterface
    {
        return $this->jwtProvider;
    }
}
