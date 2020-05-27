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
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Publishes an update to the hub.
 *
 * Can be used as a Symfony Messenger handler too.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class Publisher implements PublisherInterface
{
    private $hubUrl;
    private $jwtProvider;
    private $httpClient;

    /**
     * @param callable(Update $update): string $jwtProvider
     */
    public function __construct(string $hubUrl, callable $jwtProvider, HttpClientInterface $httpClient = null)
    {
        $this->hubUrl = $hubUrl;
        $this->jwtProvider = $jwtProvider;
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function __invoke(Update $update): string
    {
        $postData = [
            'topic' => $update->getTopics(),
            'data' => $update->getData(),
            'private' => $update->isPrivate() ? 'on' : null,
            'id' => $update->getId(),
            'type' => $update->getType(),
            'retry' => $update->getRetry(),
        ];

        $jwt = ($this->jwtProvider)($update);
        $this->validateJwt($jwt);

        return $this->httpClient->request('POST', $this->hubUrl, [
            'auth_bearer' => $jwt,
            'body' => $this->buildQuery($postData),
        ])->getContent();
    }

    /**
     * Similar to http_build_query but doesn't add the brackets in keys for array values and skip null values.
     */
    private function buildQuery(array $data): string
    {
        $parts = [];
        foreach ($data as $key => $value) {
            if (null === $value) {
                continue;
            }

            if (\is_array($value)) {
                foreach ($value as $v) {
                    $parts[] = $this->encode($key, $v);
                }

                continue;
            }

            $parts[] = $this->encode($key, $value);
        }

        return implode('&', $parts);
    }

    private function encode($key, $value): string
    {
        // All Mercure's keys are safe, so don't need to be encoded, but it's not a generic solution
        return sprintf('%s=%s', $key, urlencode((string) $value));
    }

    /**
     * Regex ported from Windows Azure Active Directory IdentityModel Extensions for .Net.
     *
     * @throws \InvalidArgumentException
     *
     * @license MIT
     * @copyright Copyright (c) Microsoft Corporation
     *
     * @see https://github.com/AzureAD/azure-activedirectory-identitymodel-extensions-for-dotnet/blob/6e7a53e241e4566998d3bf365f03acd0da699a31/src/Microsoft.IdentityModel.JsonWebTokens/JwtConstants.cs#L58
     */
    private function validateJwt(string $jwt): void
    {
        if (!preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/', $jwt)) {
            throw new \InvalidArgumentException('The provided JWT is not valid');
        }
    }
}
