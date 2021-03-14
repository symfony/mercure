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
final class CallableTokenProvider implements TokenProviderInterface
{
    private $provider;

    /**
     * @param (callable(): string) $provider
     */
    public function __construct(callable $provider)
    {
        $this->provider = $provider;
    }

    public function getJwt(): string
    {
        return ($this->provider)();
    }
}
