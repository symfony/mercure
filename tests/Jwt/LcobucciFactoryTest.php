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

use Lcobucci\JWT\Signer\Key;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Exception\InvalidArgumentException;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;

final class LcobucciFactoryTest extends TestCase
{
    private const PRIVATE_ECDSA_KEY = '-----BEGIN EC PRIVATE KEY-----
MHcCAQEEIBGpMoZJ64MMSzuo5JbmXpf9V4qSWdLIl/8RmJLcfn/qoAoGCCqGSM49
AwEHoUQDQgAE7it/EKmcv9bfpcV1fBreLMRXxWpnd0wxa2iFruiI2tsEdGFTLTsy
U+GeRqC7zN0aTnTQajarUylKJ3UWr/r1kg==
-----END EC PRIVATE KEY-----';

    private const PRIVATE_RSA_ENCRYPTED_KEY = '-----BEGIN RSA PRIVATE KEY-----
Proc-Type: 4,ENCRYPTED
DEK-Info: AES-128-CBC,0D71668CE71033CB9150ED82FC87F4A1

uLzPNDdlHnZ77tAGMHyPYERDMBcdV4SsQJYcSjiHhR2o0dLGTdgOpQrXTHPX4GJF
LlEWLhAAV9wx2mM/2kHDWB4uZwThtT9/v+RFoW1WbVO/d3lhI9fg4/73/DWAH/7/
afMRc7ZOVoAmVCESotPx4khCHoE97RdY/JtkLTzc3+peqmL53AbYXrg9rTN1B+ZV
U3w4ciQS8Uki87zDYIBjYtaOCyMUTvug25CvdssvUMBoc/Jc0xps6/vAyXrnzlGT
pZD0Tst8idswfDi613BhAaxJspeY0AErWA59qJ3eGzbiQq5RDWcbJe/Tz5r/6+NN
DkvNQ7DaEZ6LpeWX0MUq6/QWfrM8yE95XhjyC1d3LYn32lXHUygbgTFWIgLDoOE6
nBhu34SWtbLAnqYGewaJFxhlYVS9rb/uvYQg70r5X9Sx6alCQPiPyIv39IItezn2
HF2GRfE91MPZUeDhdqdvvOlSZVM5KnYc1fhamGAwM48gdDDXe8Czu/JEGoANNvC3
l/Z1p5RtGF4hrel9WpeX9zQq3pvtfVcVIiWuRUwCOSQytXlieRK37sMuYeggvmjV
VvaCods3mS/panWg9T/D/deIXjhzNJLvyiJg8+3sY5H4yNe0XpbaAc/ySwt9Rcxy
FzFQ+5pghLSZgR1uV3AhdcnzXBU2GkYhdGKt2tUsH0UeVQ2BXxTlBFsCOh2dWqcj
y3suIG65bukDAAWidQ4q3S6ZIMpXBhhCj7nwB5jQ7wSlU3U9So0ndr7zxdUILiMm
chHi3q5apVZnMGcwv2B33rt4nD7HgGEmRKkCelrSrBATY1ut+T4rCDzKDqDs3jpv
hYIWrlNPTkJyQz3eWly6Db+FJEfdYGadYJusc7/nOxCh/QmUu8Sh3NhKT6TH0bS7
1AAqd8H+2hJ9I32Dhd2qwAF7PkNe2LGi+P8tbAtepKGim5w65wnsPePMnrfxumsG
PeDnMrqeCKy+fME7a/MS5kmEBpmD4BMhVC6/OhFVz8gBty1f8yIEZggHNQN2QK7m
NIrG+PwqW2w8HoxOlAi2Ix4LTPifrdfsH02U7aM1pgo1rZzD4AOzqvzCaK43H2VB
BHLeTBGoLEUxXA9C+iGbeQlKXkMC00QKkjK5+nvkvnvePFfsrTQIpuyGufD/MoPb
6fpwsyHZDxhxMN1PJk1b1lPq2Ui4hXpVNOYd4Q6OQz7bwxTMRX9XQromUlKMMgAT
edX8v2NdM7Ssy1IwHuGVbDEpZdjoeaWZ1iNRV17i/EaJAqwYDQLfsuHBlzZL1ov1
xkKVJdL8Y3q80oRAzTQDVdzL/rI44LLAfv609YByCnw29feYJY2W6gV0O7ZSw413
XUkc5CaEbR1LuG8NtnOOPJV4Tb/hNsIDtvVm7Hl5npBKBe4iVgQ2LNuC2eT69d/z
uvzgjISlumPiO5ivuYe0QtLPuJSc+/Bl8bPL8gcNQEtqkzj7IftHPPZNs+bJC2uY
bPjq5KoDNAMF6VHuKHwu48MBYpnXDIg3ZenmJwGRULRBhK6324hDS6NJ7ULTBU2M
TZCHmg89ySLBfCAspVeo63o/R7bs9a7BP9x2h5uwCBogSvkEwhhPKnboVN45bp9c
-----END RSA PRIVATE KEY-----';

    protected function setUp(): void
    {
        if (!class_exists(Key\InMemory::class)) {
            $this->markTestSkipped('requires lcobucci/jwt.');
        }
    }

    /**
     * @dataProvider provideCreateCases
     */
    public function testCreate(string $secret, string $algorithm, ?array $subscribe, ?array $publish, array $additionalClaims, string $expectedJwt)
    {
        \assert('' !== $secret);
        $factory = new LcobucciFactory($secret, $algorithm, null);

        $this->assertSame(
            $expectedJwt,
            $factory->create($subscribe, $publish, $additionalClaims)
        );
    }

    public function testCreateWithEcdsaAlgorithm()
    {
        $factory = new LcobucciFactory(self::PRIVATE_ECDSA_KEY, 'ecdsa.sha256', null);

        $this->assertStringStartsWith('eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9', $factory->create([], ['*']));
    }

    public function testCreateWithEncryptedRSAAlgorithm()
    {
        $factory = new LcobucciFactory(self::PRIVATE_RSA_ENCRYPTED_KEY, 'rsa.sha512', null, 'testing');

        $this->assertSame(
            'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzUxMiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.AHKMv2PQOGq5M8VhEM1Snf7QMHoTEyeuY0-L7GjRGkaygb3TyRWFO__uvIkStj1shOykO293tqGd_pijtRrbvul4ZdOQKYBjOxk7tNsQ_gQgepptneYr4eL8F9r2_KgUVrb-xcl0YzobH389OKBhuJ8HRQ-gADniBqbSuURwFyKXcEXz-GiZ_y9hTJ4tQ4bY28SlER_-LpjRCadUik4SqXLt--8VIoJ7zHvxCSOMIHFbLZ1CFaycMuXly1w7W8XKCfpshCobbi5Xt2QndAhTgpfvmnx1mn7e1ng9QDYzNHqNb6iZzxSbZ8bnttCwVv7uuBU2tEDxBXQB-TeVSD71pw',
            $factory->create([], ['*'])
        );
    }

    public function testInvalidAlgorithm()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported algorithm "md5", expected one of "hmac.sha256", "hmac.sha384", "hmac.sha512", "ecdsa.sha256", "ecdsa.sha384", "ecdsa.sha512", "rsa.sha256", "rsa.sha384", "rsa.sha512".');

        new LcobucciFactory('!ChangeMe!', 'md5');
    }

    public function provideCreateCases(): iterable
    {
        yield [
            'secret' => 'looooooooooooongenoughtestsecret',
            'algorithm' => 'hmac.sha256',
            'subscribe' => null,
            'publish' => null,
            'additionalClaims' => [],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjpbXX0.V7YsSEFCPfzyvt38oIID7b9iE4NYjfcV07CxPUyBeLk',
        ];

        yield [
            'secret' => 'looooooooooooongenoughtestsecret',
            'algorithm' => 'hmac.sha256',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.ZTK3JhEKO1338LAgRMw6j0lkGRMoaZtU4EtGiAylAns',
        ];

        yield [
            'secret' => 'looooooooooooooooooooooooooooongenoughtestsecret',
            'algorithm' => 'hmac.sha384',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzM4NCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.ERwjuquA1VXjCx_Q05zHHIVWU40maCOLsu493IKD4osTk0l0bTs9t9S8_tgM32Ih',
        ];

        yield [
            'secret' => 'loooooooooooooooooooooooooooooooooooooooooooooongenoughtestsecret',
            'algorithm' => 'hmac.sha512',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzUxMiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdLCJzdWJzY3JpYmUiOltdfX0.eMSnFpi3G0i0lvM_f55E5vUcxkT1GqyVY7qu7c_mZTjKAh4wX3mIJOGoftX7WQRlE1qTVs0OsJ0qyeyet3Yb-g',
        ];

        yield [
            'secret' => 'looooooooooooongenoughtestsecret',
            'algorithm' => 'hmac.sha256',
            'subscribe' => [],
            'publish' => ['*'],
            'additionalClaims' => [
                'mercure' => [
                    'publish' => ['overridden'],
                    'subscribe' => ['overridden'],
                    'payload' => ['foo' => 'bar'],
                ],
            ],
            'expectedJwt' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsib3ZlcnJpZGRlbiJdLCJzdWJzY3JpYmUiOlsib3ZlcnJpZGRlbiJdLCJwYXlsb2FkIjp7ImZvbyI6ImJhciJ9fX0.owz54sSlMuVq2PqtBGFPdrYSXvMKTQc6UQdLEMOlP5s',
        ];
    }
}
