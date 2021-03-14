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
final class FactoryTokenProvider implements TokenProviderInterface
{
    private $factory;
    private $publish;
    private $subscribe;

    public function __construct(TokenFactoryInterface $factory, array $publish, array $subscribe)
    {
        $this->factory = $factory;
        $this->publish = $publish;
        $this->subscribe = $subscribe;
    }

    public function getJwt(): string
    {
        return $this->factory->create($this->publish, $this->subscribe);
    }
}
