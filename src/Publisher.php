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
use Symfony\Component\Mercure\Internal\QueryBuilder;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

trigger_deprecation('symfony/mercure', '0.5', 'Class "%s" is deprecated, use "%s" instead.', Publisher::class, Hub::class);

/**
 * Publishes an update to the hub.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 *
 * @deprecated
 */
final class Publisher implements PublisherInterface
{
    private $hubUrl;
    private $jwtProvider;
    private $httpClient;

    /**
     * @param TokenProviderInterface|callable(Update $update):string $jwtProvider
     */
    public function __construct(string $hubUrl, $jwtProvider, HttpClientInterface $httpClient = null)
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

        if ($this->jwtProvider instanceof TokenProviderInterface) {
            $jwt = $this->jwtProvider->getJwt();
        } else {
            $jwt = ($this->jwtProvider)($update);
        }
        $this->validateJwt($jwt);

        return $this->httpClient->request('POST', $this->hubUrl, [
            'auth_bearer' => $jwt,
            'body' => QueryBuilder::build($postData),
        ])->getContent();
    }

    /**
     * Regex ported from Windows Azure Active Directory IdentityModel Extensions for .Net.
     *
     * @throws Exception\InvalidArgumentException
     *
     * @license MIT
     * @copyright Copyright (c) Microsoft Corporation
     *
     * @see https://github.com/AzureAD/azure-activedirectory-identitymodel-extensions-for-dotnet/blob/6e7a53e241e4566998d3bf365f03acd0da699a31/src/Microsoft.IdentityModel.JsonWebTokens/JwtConstants.cs#L58
     */
    private function validateJwt(string $jwt): void
    {
        if (!preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z0-9-_]+\.[A-Za-z0-9-_]*$/', $jwt)) {
            throw new Exception\InvalidArgumentException('The provided JWT is not valid.');
        }
    }
}
