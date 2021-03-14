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

namespace Symfony\Component\Mercure\Jwt;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class StaticTokenProvider implements TokenProviderInterface
{
    private $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function getJwt(): string
    {
        return $this->token;
    }
}
