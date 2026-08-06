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
 * One Mercure protocol 1.0 authorization_details entry: a set of actions granted over
 * a set of topics, with an optional payload. A payload is only meaningful when "subscribe"
 * is among $actions: the hub stores one per entry but never surfaces it for a publish-only
 * grant (it resolves payloads by walking only the entries whose actions include "subscribe").
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Grant
{
    /*
     * Not an enum: the hub explicitly ignores, rather than rejects, an action string it
     * doesn't recognize, so issuers can use action types registered by future specifications
     * without a library update — the same reason topic matcher types stay plain strings too.
     */
    public const ACTION_SUBSCRIBE = 'subscribe';
    public const ACTION_PUBLISH = 'publish';

    /**
     * @param string[]                                         $actions one or more of self::ACTION_*
     * @param array<int, string>|array<string, string[]>       $topics  a flat list of topics (matched as "exact"), or an
     *                                                                   associative array mapping a matcher type name
     *                                                                   ("exact", "urlpattern", or a registered extension
     *                                                                   type) to a list of patterns of that type
     */
    public function __construct(
        public readonly array $actions,
        public readonly array $topics,
        public readonly mixed $payload = null,
    ) {
    }
}
