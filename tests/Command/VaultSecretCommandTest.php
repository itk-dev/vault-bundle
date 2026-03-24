<?php

namespace ItkDev\VaultBundle\Tests\Command;

use ItkDev\Vault\Model\Secret;
use ItkDev\Vault\Model\Token;
use ItkDev\VaultBundle\Command\VaultSecretCommand;
use ItkDev\VaultBundle\Service\Vault;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Tester\CommandTester;

final class VaultSecretCommandTest extends TestCase
{
    #[Test]
    public function executeMissingPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('path');

        $tester = $this->createCommandTester();
        $tester->execute([
            '--secret' => 'db',
            '--key' => ['password'],
        ]);
    }

    #[Test]
    public function executeMissingSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('secret');

        $tester = $this->createCommandTester();
        $tester->execute([
            '--path' => 'prod',
            '--key' => ['password'],
        ]);
    }

    #[Test]
    public function executeMissingKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('key');

        $tester = $this->createCommandTester();
        $tester->execute([
            '--path' => 'prod',
            '--secret' => 'db',
        ]);
    }

    #[Test]
    public function executeSuccess(): void
    {
        $token = $this->createToken();
        $secrets = [
            $this->createSecret('password', 's3cret'),
        ];

        $vault = $this->createMock(Vault::class);
        $vault->expects($this->once())
            ->method('login')
            ->with('role-id', 'secret-id')
            ->willReturn($token);

        $vault->expects($this->once())
            ->method('getSecrets')
            ->with(
                $token,
                'prod',
                'db',
                ['password'],
                null,    // version
                false,   // useCache
                false,   // refreshCache
                0,       // expire
            )
            ->willReturn($secrets);

        $tester = $this->createCommandTester($vault);
        $exitCode = $tester->execute([
            '--path' => 'prod',
            '--secret' => 'db',
            '--key' => ['password'],
        ]);
        $output = $tester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('password', $output);
        $this->assertStringContainsString('s3cret', $output);
    }

    #[Test]
    public function executeWithAllOptions(): void
    {
        $token = $this->createToken();
        $secrets = [
            $this->createSecret('user', 'admin'),
            $this->createSecret('pass', 'p@ss'),
        ];

        $vault = $this->createMock(Vault::class);
        $vault->method('login')->willReturn($token);

        $vault->expects($this->once())
            ->method('getSecrets')
            ->with(
                $token,
                'prod',
                'db',
                ['user', 'pass'],
                3,       // version (cast from string)
                true,    // useCache
                true,    // refreshCache
                600,     // expire
            )
            ->willReturn($secrets);

        $tester = $this->createCommandTester($vault);
        $exitCode = $tester->execute([
            '--path' => 'prod',
            '--secret' => 'db',
            '--key' => ['user', 'pass'],
            '--version-id' => '3',
            '--useCache' => true,
            '--expire' => '600',
            '--refresh' => true,
        ]);
        $output = $tester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('admin', $output);
        $this->assertStringContainsString('p@ss', $output);
    }

    private function createCommandTester(?Vault $vault = null): CommandTester
    {
        $vault ??= $this->createStub(Vault::class);
        $command = new VaultSecretCommand($vault, 'role-id', 'secret-id');
        $application = new Application();
        $application->add($command);

        return new CommandTester($application->find('itkdev:vault:secret'));
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

    private function createSecret(string $key, string $value): Secret
    {
        return new Secret(
            key: $key,
            value: $value,
            version: '1',
            createdAt: new \DateTimeImmutable('2025-01-01', new \DateTimeZone('UTC')),
        );
    }
}
