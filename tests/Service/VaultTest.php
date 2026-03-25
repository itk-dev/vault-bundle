<?php

namespace ItkDev\VaultBundle\Tests\Service;

use ItkDev\Vault\Exception\VaultException;
use ItkDev\VaultBundle\Service\Vault;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\SimpleCache\CacheInterface;

#[RunTestsInSeparateProcesses]
final class VaultTest extends TestCase
{
    #[Test]
    public function loginSuccess(): void
    {
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode($this->loginResponseData())));

        $vault = $this->createVaultService($httpClient);
        $token = $vault->login('test-role', 'test-secret');

        $this->assertSame('hvs.test-token-abc', $token->token);
        $this->assertTrue($token->renewable);
        $this->assertSame('test-role', $token->roleName);
        $this->assertSame(10, $token->usesLeft());
        $this->assertFalse($token->isExpired());
    }

    #[Test]
    public function loginPassesEnginePath(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request): bool {
                return str_contains((string) $request->getUri(), '/v1/auth/custom-engine/login');
            }))
            ->willReturn(new Response(200, [], json_encode($this->loginResponseData())));

        $vault = $this->createVaultService($httpClient);
        $vault->login('test-role', 'test-secret', 'custom-engine');
    }

    #[Test]
    public function getSecretSuccess(): void
    {
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], json_encode($this->loginResponseData())),
                new Response(200, [], json_encode($this->secretResponseData(['api-key' => 'my-secret-value']))),
            );

        $vault = $this->createVaultService($httpClient);
        $token = $vault->login('test-role', 'test-secret');
        $secret = $vault->getSecret($token, 'secret', 'my-app', 'api-key');

        $this->assertSame('api-key', $secret->key);
        $this->assertSame('my-secret-value', $secret->value);
        $this->assertSame(1, (int) $secret->version);
    }

    #[Test]
    public function getSecretsMultipleKeys(): void
    {
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], json_encode($this->loginResponseData())),
                new Response(200, [], json_encode($this->secretResponseData([
                    'user' => 'admin',
                    'pass' => 'p@ssw0rd',
                ]))),
            );

        $vault = $this->createVaultService($httpClient);
        $token = $vault->login('test-role', 'test-secret');
        $secrets = $vault->getSecrets($token, 'secret', 'db', ['user', 'pass']);

        $this->assertCount(2, $secrets);
        $this->assertSame('admin', $secrets['user']->value);
        $this->assertSame('p@ssw0rd', $secrets['pass']->value);
    }

    #[Test]
    public function loginVaultError(): void
    {
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')
            ->willReturn(new Response(200, [], json_encode([
                'errors' => ['permission denied'],
            ])));

        $vault = $this->createVaultService($httpClient);

        $this->expectException(VaultException::class);
        $vault->login('bad-role', 'bad-secret');
    }

    private function createVaultService(ClientInterface $httpClient): Vault
    {
        $factory = new Psr17Factory();
        $cache = $this->createStub(CacheInterface::class);
        $cache->method('get')->willReturn(null);

        return new Vault($httpClient, $factory, $factory, $cache, 'http://vault.test');
    }

    private function loginResponseData(): array
    {
        return [
            'auth' => [
                'client_token' => 'hvs.test-token-abc',
                'lease_duration' => 3600,
                'renewable' => true,
                'metadata' => ['role_name' => 'test-role'],
                'num_uses' => 10,
            ],
        ];
    }

    /**
     * @param array<string, string> $data
     */
    private function secretResponseData(array $data): array
    {
        return [
            'data' => [
                'metadata' => [
                    'created_time' => '2025-01-01T00:00:00.000000Z',
                    'version' => 1,
                ],
                'data' => $data,
            ],
        ];
    }
}
