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
use Symfony\Component\Mercure\Jwt\CallableTokenProvider;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Publishes an update to the hub.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 *
 * @experimental
 */
final class Publisher implements PublisherInterface
{
    private $hub;
    private $httpClient;

    /**
     * @param Hub                      $hub
     * @param HttpClientInterface|null $httpClient
     */
    public function __construct($hub, $httpClient = null)
    {
        if (!$hub instanceof Hub) {
            trigger_deprecation('symfony/mercure', '0.5', 'Passing a hub url, and a callable to "%s::__construct()" is deprecated, pass a "%s" instance instead.', __CLASS__, Hub::class);

            $hub = new Hub((string) $hub, new CallableTokenProvider($httpClient));
            $httpClient = \func_get_args()[2] ?? null;
        }

        $this->hub = $hub;
        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    public function __invoke(Update $update): string
    {
        trigger_deprecation('symfony/mercure', '0.5', 'Method "%s()" is deprecated, use "%s::publish()" instead.', __METHOD__, __CLASS__);

        return $this->publish($update);
    }

    /**
     * Sends $update to the mercure hub.
     */
    public function publish(Update $update): string
    {
        $postData = [
            'topic' => $update->getTopics(),
            'data' => $update->getData(),
            'private' => $update->isPrivate() ? 'on' : null,
            'id' => $update->getId(),
            'type' => $update->getType(),
            'retry' => $update->getRetry(),
        ];

        $jwt = $this->hub->getProvider()->getJwt();
        $this->validateJwt($jwt);

        try {
            return $this->httpClient->request('POST', $this->hub->getUrl(), [
                'auth_bearer' => $jwt,
                'body' => Internal\QueryBuilder::build($postData),
            ])->getContent();
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
