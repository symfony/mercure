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
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Saif Eddin Gmati <azjezz@protonmail.com>
 *
 * @experimental
 */
final class Hub implements HubInterface
{
    private $url;
    private $jwtProvider;
    private $jwtFactory;
    private $publicUrl;
    private $httpClient;

    public function __construct(
        string $url,
        TokenProviderInterface $jwtProvider,
        TokenFactoryInterface $jwtFactory = null,
        string $publicUrl = null,
        HttpClientInterface $httpClient = null
    ) {
        $this->url = $url;
        $this->jwtProvider = $jwtProvider;
        $this->publicUrl = $publicUrl;
        $this->jwtFactory = $jwtFactory;
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicUrl(): string
    {
        return $this->publicUrl ?? $this->getUrl();
    }

    /**
     * {@inheritDoc}
     */
    public function getProvider(): TokenProviderInterface
    {
        return $this->jwtProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getFactory(): ?TokenFactoryInterface
    {
        return $this->jwtFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function publish(Update $update): string
    {
        $jwt = $this->getProvider()->getJwt();
        $this->validateJwt($jwt);

        try {
            return $this->publishFast($update, $jwt)->getContent();
        } catch (ExceptionInterface $exception) {
            throw new Exception\RuntimeException('Failed to send an update.', 0, $exception);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function publishFast(Update $update, ?string $token = null): ResponseInterface
    {
        $postData = [
            'topic' => $update->getTopics(),
            'data' => $update->getData(),
            'private' => $update->isPrivate() ? 'on' : null,
            'id' => $update->getId(),
            'type' => $update->getType(),
            'retry' => $update->getRetry(),
        ];

        if (!$token) {
            $token = $this->getProvider()->getJwt();
            $this->validateJwt($token);
        }
        return $this->httpClient->request('POST', $this->getUrl(), [
            'auth_bearer' => $token,
            'body' => Internal\QueryBuilder::build($postData),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function publishBatch($updates, bool $fireAndForget = false): array
    {

        $jwt = $this->getProvider()->getJwt();
        $this->validateJwt($jwt);

        try {
            $requests = [];
            foreach ($updates as $update) {
                $requests[] = $this->publishFast($update, $jwt);
            }
            if ($fireAndForget) {
                return [];
            } else {
                return array_map(function ($val) {
                    return $val->getContent();
                }, $requests);
            }
        } catch (ExceptionInterface $exception) {
            throw new Exception\RuntimeException('Failed to send an update.', 0, $exception);
        }
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
            throw new Exception\InvalidArgumentException('The provided JWT is not valid');
        }
    }
}
