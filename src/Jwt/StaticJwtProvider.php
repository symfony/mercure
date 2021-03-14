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

trigger_deprecation('symfony/mercure', '0.5', 'Class "%s" is deprecated, use "%s" instead.', StaticJwtProvider::class, StaticTokenProvider::class);

/**
 * Provides a JWT passed as a configuration parameter.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 *
 * @deprecated
 */
final class StaticJwtProvider
{
    private $jwt;

    public function __construct(string $jwt)
    {
        $this->jwt = $jwt;
    }

    public function __invoke(): string
    {
        return $this->jwt;
    }
}
