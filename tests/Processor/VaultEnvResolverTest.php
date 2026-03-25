<?php

namespace ItkDev\VaultBundle\Tests\Processor;

use ItkDev\Vault\Model\Secret;
use ItkDev\Vault\Model\Token;
use ItkDev\VaultBundle\Processor\VaultEnvResolver;
use ItkDev\VaultBundle\Service\Vault;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class VaultEnvResolverTest extends TestCase
{
    private Vault&MockObject $vaultService;
    private VaultEnvResolver $resolver;

    protected function setUp(): void
    {
        $this->vaultService = $this->createMock(Vault::class);
        $this->resolver = new VaultEnvResolver(
            $this->vaultService,
            'test-role-id',
            'test-secret-id',
        );
    }

    #[Test]
    public function getEnvBasic(): void
    {
        $token = $this->createToken();
        $secret = $this->createSecret();

        $this->vaultService->expects($this->once())
            ->method('login')
            ->with('test-role-id', 'test-secret-id')
            ->willReturn($token);

        $this->vaultService->expects($this->once())
            ->method('getSecret')
            ->with(
                $token,
                'prod',
                'my-secret',
                'api-key',
                null,    // version
                false,   // useCache
                false,   // refreshCache
                0,       // expire
            )
            ->willReturn($secret);

        $getEnv = fn (string $name) => 'prod:my-secret:api-key';

        $result = $this->resolver->getEnv('vault', 'MY_VAR', $getEnv);

        $this->assertSame('secret-value', $result);
    }

    #[Test]
    public function getEnvWithVersion(): void
    {
        $token = $this->createToken();
        $secret = $this->createSecret();

        $this->vaultService->method('login')->willReturn($token);

        $this->vaultService->expects($this->once())
            ->method('getSecret')
            ->with(
                $token,
                'prod',
                'my-secret',
                'api-key',
                3,       // version (string "3" coerced to int)
                false,   // useCache
                false,   // refreshCache
                0,       // expire
            )
            ->willReturn($secret);

        $getEnv = fn (string $name) => 'prod:my-secret:api-key:3';

        $result = $this->resolver->getEnv('vault', 'MY_VAR', $getEnv);

        $this->assertSame('secret-value', $result);
    }

    #[Test]
    public function getEnvWithVersionAndExpire(): void
    {
        $token = $this->createToken();
        $secret = $this->createSecret();

        $this->vaultService->method('login')->willReturn($token);

        $this->vaultService->expects($this->once())
            ->method('getSecret')
            ->with(
                $token,
                'prod',
                'my-secret',
                'api-key',
                5,       // version
                true,    // useCache (expire is not null)
                false,   // refreshCache
                600,     // expire
            )
            ->willReturn($secret);

        $getEnv = fn (string $name) => 'prod:my-secret:api-key:5:600';

        $result = $this->resolver->getEnv('vault', 'MY_VAR', $getEnv);

        $this->assertSame('secret-value', $result);
    }

    #[Test]
    #[AllowMockObjectsWithoutExpectations]
    public function getProvidedTypes(): void
    {
        $this->assertSame(['vault' => 'string'], VaultEnvResolver::getProvidedTypes());
    }

    private function createToken(): Token
    {
        return new Token(
            token: 'hvs.test-token',
            expiresAt: new \DateTimeImmutable('+1 hour', new \DateTimeZone('UTC')),
            renewable: true,
            roleName: 'test-role',
            numUsesLeft: 10,
        );
    }

    private function createSecret(string $value = 'secret-value'): Secret
    {
        return new Secret(
            key: 'api-key',
            value: $value,
            version: '1',
            createdAt: new \DateTimeImmutable('2025-01-01', new \DateTimeZone('UTC')),
        );
    }
}
