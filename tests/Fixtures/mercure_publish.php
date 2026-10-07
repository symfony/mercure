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

/*
 * Stands in for the function FrankenPHP defines when its "mercure" directive is enabled.
 * Only ever loaded by a test running in an isolated process: once defined, it can't be
 * undefined for the tests covering its absence.
 */
if (!function_exists('mercure_publish')) {
    function mercure_publish(array $topics, string $data, bool $private = false, ?string $id = null, ?string $type = null, ?int $retry = null): string
    {
        return 'urn:uuid:published';
    }
}
