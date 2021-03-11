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

namespace Symfony\Component\Mercure\Exception;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
    /**
     * @param string[] $supportedAlgorithms
     */
    public static function forInvalidAlgorithm(string $algorithm, array $supportedAlgorithms): self
    {
        return new self(sprintf('Unsupported algorithm "%s", expected one of "%s".', $algorithm, implode('", "', $supportedAlgorithms)));
    }
}
