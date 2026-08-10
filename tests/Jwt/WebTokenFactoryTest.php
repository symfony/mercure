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

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\HS384;
use Jose\Component\Signature\JWSBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Jwt\Grant;
use Symfony\Component\Mercure\Jwt\WebTokenFactory;

final class WebTokenFactoryTest extends TestCase
{
    private const SECRET = 'looooooooooooongenoughtestsecret';
    private const PRIVATE_RSA_KEY = '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDYNJemisJlVVQ9
CdbCk8l3T017jgcQHd+V7AIouMkEGtnssCK06nCn7paoZSVUNnRlSXI+AYU7cJ85
r3sxyDVXbwL8TeUksCHa/ZNSI3kI5w3oPM5oubzWI920ltgNRT4zoQ7VCNTF7ZpF
EBF3EuugTOUumjM90U8YaasGtEvFrgJMRI6ZgrdEzVhWfpAUPHPXLAjDSZNZSVK0
YMHqeKrpQXcrRrGet+Lc63kLbxQ4w7s4Lei7BM9i6Cr5cuj71oP5I4lKsLIj/w0+
8Y9sBKwQjafEusC8Ndc9ts1F8w5V59CTM/WbAWzcSsjQ6GWRKM4RSucebL+epLfO
5CgE1aBXAgMBAAECggEAA/UAxwVV3X7SNDBrfUzSufoVEKR5xGgq7nuf2QSi41GA
zrpkOsBIi/shmKmDxveImTM9I4KmWbUZc0H9XupNJl1AneTow1xLXvcDQQpWo5r3
1DF3BuqbIunjqTcBjlhpWhVlR/6Qb6K8DyPznrjs+xZp1Rcm4SXQ7Ri4vfr8eHYH
ZKPfgRpyREl7tvsa0mkAbNy6b7XyCkhQY8ndurLnAL311AHXoPbNDVommRCA1xXn
B2w63IXHw6x6HtJhlqKcvGyE5I/PALWWjqM3SNEJe2Rw3l7R0tVGajLGsEsLgEWU
mfHy1GNT1J9QaxJC+ZHc9sivoK21dX+nUomDmuv4kQKBgQDvS4U8mO/KJx/35qoM
wTX3vWljLhvjAlW1azG+uD2bKZKpuVyU0+VgfcxrMq5UHFo2stl3Smch81HpDFVl
sU/gfvD+OnWEayaEvygswvfwEZBAe9Fy0bU2YjFQcF3hky+AM9VODE5nWtbNtQqF
AWj6MXB3szJqERK3y43djh5NdQKBgQDnTG9D/+7VkoJDmpC1MKUkG6A+akEbkO2b
d3nzGjEsOL+qRSpJIBQyRSQY3YFfKwlLOCM6FJIRl+3leJbmIHMAsTfmsjfyUuGh
lyef2cy7gAGMhH2EHFg83IZ9MLG6ZbRgMzJSLqVnyFvJn7WV9ylCSp5z+vMcL86S
px2Iqz4BGwKBgCfcd6xZeZA+JjggZ3FKehfzqGuKEcEl8WsQGTBA9pozOagmJYLx
LUN+kR/GSH3gXzK/ZjRV44onQxzEHjGbcMffvXtL96mAQ+FXCnUyYSTPeC0VsxUi
U8JkZvyUSz85Sm9bswuWRzU2T5PitYbkbj1HIQn/gsViZLDTKqWed/PFAoGARkoI
FhYUsnqPc5PpHebmiI6Mp+sKViI40uIAAUyYXOPx+wCB1S8WdCBm09eclRsy+9TX
f7L4oLgW54E5+j0qNa+lWUoXjmB5iO2ycPVzLhe8YMNykU870WMCy9CcchSuN/3W
8PqT8XIF0sPiHuy5cRfUB1LfxlUQ5ag7ZWkEsrkCgYAsuaBCVIrH2cKOOD9SZwyu
NjCt6sMesmqmyQu8+Tmii35Dr70xKonHAT+O/3uZ0imeIDYhHgn6Cljk1zzf2nG2
T8tgrer/0mSgQvTtD12wOFKyiVD1k5zQfxpfHO5ZvSQnfbXCGEeUZTnLUihbnLYJ
EJ2MUSeVKtAPyVYYqYsEUQ==
-----END PRIVATE KEY-----';
    private const PRIVATE_ED25519_KEY = '-----BEGIN PRIVATE KEY-----
MC4CAQAwBQYDK2VwBCIEIC2sHlY290BGA/Cr3ASUox+INF9KzT10bd96xOo5UPir
-----END PRIVATE KEY-----';
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
        $this->expectExceptionMessage('Unsupported algorithm "md5", expected one of "HS256", "HS384", "HS512", "ES256", "ES384", "ES512", "RS256", "RS384", "RS512", "PS256", "PS384", "PS512", "EdDSA".');

        WebTokenFactory::fromSecret(self::SECRET, 'md5');
    }

    public function testRequiresRegisteredClaims()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "iss" additional claim is required');

        $factory->create([new Grant([Grant::ACTION_SUBSCRIBE], ['a'])]);
    }

    public function testRejectsNullOrEmptyRegisteredClaims()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "aud" additional claim is required');

        $factory->create([new Grant([Grant::ACTION_SUBSCRIBE], ['a'])], ['iss' => 'https://example.com', 'aud' => '', 'sub' => 'urn:uuid:1', 'client_id' => 'https://example.com']);
    }

    public function testClaimShape()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET, 'HS256', 3600);

        [$header, $payload] = $this->decode($factory->create(
            [
                new Grant([Grant::ACTION_SUBSCRIBE], ['exact' => ['https://example.com/books/1'], 'urlpattern' => ['https://example.com/reviews/:id']]),
                new Grant([Grant::ACTION_PUBLISH], ['*']),
            ],
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
            [new Grant([Grant::ACTION_SUBSCRIBE], ['a'], ['foo' => 'bar'])],
            self::REQUIRED_CLAIMS
        ));

        $this->assertSame(['foo' => 'bar'], $payload['authorization_details'][0]['payload']);
    }

    public function testPayloadWithoutTopicsThrows()
    {
        $factory = WebTokenFactory::fromSecret(self::SECRET);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('requires at least one topic');

        $factory->create(
            [new Grant([Grant::ACTION_SUBSCRIBE], [], ['foo' => 'bar'])],
            self::REQUIRED_CLAIMS
        );
    }

    public function testSupportsRsaPssAlgorithm()
    {
        $factory = WebTokenFactory::fromSecret(self::PRIVATE_RSA_KEY, 'PS256');

        [$header, $payload] = $this->decode($factory->create([new Grant([Grant::ACTION_SUBSCRIBE], ['a'])], self::REQUIRED_CLAIMS));

        $this->assertSame('PS256', $header['alg']);
        $this->assertSame(['subscribe'], $payload['authorization_details'][0]['actions']);
    }

    public function testSupportsEdDsaAlgorithm()
    {
        $factory = WebTokenFactory::fromSecret(self::PRIVATE_ED25519_KEY, 'EdDSA');

        [$header, $payload] = $this->decode($factory->create([new Grant([Grant::ACTION_SUBSCRIBE], ['a'])], self::REQUIRED_CLAIMS));

        $this->assertSame('EdDSA', $header['alg']);
        $this->assertSame(['subscribe'], $payload['authorization_details'][0]['actions']);
    }

    public function testAcceptsAPreconfiguredJwsBuilder()
    {
        $jwsBuilder = new JWSBuilder(new AlgorithmManager([new HS384(), new HS256()]));
        $factory = new WebTokenFactory($jwsBuilder, JWKFactory::createFromSecret(self::SECRET), 'HS256');

        [$header, $payload] = $this->decode($factory->create([new Grant([Grant::ACTION_SUBSCRIBE], ['a'])], self::REQUIRED_CLAIMS));

        $this->assertSame('HS256', $header['alg']);
        $this->assertSame(['subscribe'], $payload['authorization_details'][0]['actions']);
    }

    public function testRejectsAnAlgorithmTheJwsBuilderDoesNotSupport()
    {
        $jwsBuilder = new JWSBuilder(new AlgorithmManager([new HS384()]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported algorithm "HS256", expected one of "HS384".');

        new WebTokenFactory($jwsBuilder, JWKFactory::createFromSecret(self::SECRET), 'HS256');
    }

    public function testFromJwksUri()
    {
        $jwk = JWKFactory::createFromSecret(self::SECRET, ['kid' => 'key1']);
        $httpClient = new MockHttpClient(new MockResponse(json_encode(['keys' => [$jwk->jsonSerialize()]])));

        $factory = WebTokenFactory::fromJwksUri('https://example.com/jwks.json', $httpClient, 'HS256', 'key1');

        [$header, $payload] = $this->decode($factory->create([new Grant([Grant::ACTION_SUBSCRIBE], ['a'])], self::REQUIRED_CLAIMS));

        $this->assertSame('HS256', $header['alg']);
        $this->assertSame(['subscribe'], $payload['authorization_details'][0]['actions']);
    }

    public function testFromJwksUriThrowsWhenKeyIdNotFound()
    {
        $jwk = JWKFactory::createFromSecret(self::SECRET, ['kid' => 'key1']);
        $httpClient = new MockHttpClient(new MockResponse(json_encode(['keys' => [$jwk->jsonSerialize()]])));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No signing key matching algorithm "HS256" and key ID "unknown" was found');

        WebTokenFactory::fromJwksUri('https://example.com/jwks.json', $httpClient, 'HS256', 'unknown');
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
