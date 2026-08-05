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

namespace Symfony\Component\Mercure\Tests\Jwt;

use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\JWSBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Jwt\WebTokenFactory;

final class WebTokenFactoryTest extends TestCase
{
    private const SECRET = 'looooooooooooongenoughtestsecret';
    private const REQUIRED_CLAIMS = [
        'iss' => 'https://example.com',
        'aud' => 'https://hub.example.com/.well-known/mercure',
        'sub' => 'urn:uuid:1',
        'client_id' => 'https://example.com',
    ];

    protected function setUp(): void
    {
        if (!class_exists(JWSBuilder::class)) {
            $this->markTestSkipped('requires web-token/jwt-library.');
        }
    }

    public function testInvalidAlgorithm()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported algorithm "md5", expected one of "hmac.sha256", "hmac.sha384", "hmac.sha512", "ecdsa.sha256", "ecdsa.sha384", "ecdsa.sha512", "rsa.sha256", "rsa.sha384", "rsa.sha512".');

        WebTokenFactory::fromSecret(self::SECRET, 'md5');
    }

    public function testRequiresRegisteredClaims()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "iss" additional claim is required');

        $factory->create(['a'], []);
    }

    public function testRejectsNullOrEmptyRegisteredClaims()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "aud" additional claim is required');

        $factory->create(['a'], [], ['iss' => 'https://example.com', 'aud' => '', 'sub' => 'urn:uuid:1', 'client_id' => 'https://example.com']);
    }

    public function testClaimShape()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET, 'hmac.sha256', 3600);

        [$header, $payload] = $this->decode($factory->create(
            ['exact' => ['https://example.com/books/1'], 'urlpattern' => ['https://example.com/reviews/:id']],
            ['*'],
            self::REQUIRED_CLAIMS
        ));

        $this->assertSame('HS256', $header['alg']);
        $this->assertSame('at+jwt', $header['typ']);
        $this->assertArrayNotHasKey('mercure', $payload);
        $this->assertCount(2, $payload['authorization_details']);

        $subscribeDetail = $payload['authorization_details'][0];
        $this->assertSame('https://mercure.rocks/authorization-detail', $subscribeDetail['type']);
        $this->assertSame(['subscribe'], $subscribeDetail['actions']);
        $this->assertSame(
            [
                ['match' => 'https://example.com/books/1'],
                ['match' => 'https://example.com/reviews/:id', 'match_type' => 'urlpattern'],
            ],
            $subscribeDetail['topics']
        );

        $publishDetail = $payload['authorization_details'][1];
        $this->assertSame(['publish'], $publishDetail['actions']);
        $this->assertSame([['match' => '*']], $publishDetail['topics']);

        $this->assertIsInt($payload['exp']);
        $this->assertIsInt($payload['iat']);
        $this->assertIsString($payload['jti']);
    }

    public function testPayloadIsAttachedToSubscribeDetail()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET);

        [, $payload] = $this->decode($factory->create(
            ['a'],
            null,
            self::REQUIRED_CLAIMS + ['mercure' => ['payload' => ['foo' => 'bar']]]
        ));

        $this->assertSame(['foo' => 'bar'], $payload['authorization_details'][0]['payload']);
    }

    public function testPayloadWithoutSubscribeTopicsThrows()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires at least one subscribe topic');

        $factory->create(
            [],
            null,
            self::REQUIRED_CLAIMS + ['mercure' => ['payload' => ['foo' => 'bar']]]
        );
    }

    public function testFromJwksUri()
    {
        $jwk = JWKFactory::createFromSecret(self::SECRET, ['kid' => 'key1']);
        $httpClient = new MockHttpClient(new MockResponse(json_encode(['keys' => [$jwk->jsonSerialize()]])));

        $factory = WebTokenFactory::fromJwksUri('https://example.com/jwks.json', $httpClient, 'hmac.sha256', 'key1');

        [$header, $payload] = $this->decode($factory->create(['a'], [], self::REQUIRED_CLAIMS));

        $this->assertSame('HS256', $header['alg']);
        $this->assertSame(['subscribe'], $payload['authorization_details'][0]['actions']);
    }

    public function testFromJwksUriThrowsWhenKeyIdNotFound()
    {
        $jwk = JWKFactory::createFromSecret(self::SECRET, ['kid' => 'key1']);
        $httpClient = new MockHttpClient(new MockResponse(json_encode(['keys' => [$jwk->jsonSerialize()]])));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No signing key matching algorithm "hmac.sha256" and key ID "unknown" was found');

        WebTokenFactory::fromJwksUri('https://example.com/jwks.json', $httpClient, 'hmac.sha256', 'unknown');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function decode(string $jwt): array
    {
        [$header, $payload] = explode('.', $jwt);

        return [
            json_decode($this->base64UrlDecode($header), true, flags: \JSON_THROW_ON_ERROR),
            json_decode($this->base64UrlDecode($payload), true, flags: \JSON_THROW_ON_ERROR),
        ];
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/').str_repeat('=', (4 - \strlen($data) % 4) % 4), true);
    }
}
