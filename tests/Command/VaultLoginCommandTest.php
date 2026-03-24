<?php

namespace ItkDev\VaultBundle\Tests\Command;

use ItkDev\Vault\Model\Token;
use ItkDev\VaultBundle\Command\VaultLoginCommand;
use ItkDev\VaultBundle\Service\Vault;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class VaultLoginCommandTest extends TestCase
{
    #[Test]
    public function executeDefault(): void
    {
        $token = $this->createToken();

        $vault = $this->createMock(Vault::class);
        $vault->expects($this->once())
            ->method('login')
            ->with('role-id', 'secret-id', 'approle', false)
            ->willReturn($token);

        $tester = $this->createCommandTester($vault);
        $exitCode = $tester->execute([]);
        $output = $tester->getDisplay();

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('hvs.test-token', $output);
        $this->assertStringContainsString('test-role', $output);
        $this->assertStringContainsString('Yes', $output); // renewable
        $this->assertStringContainsString('10', $output); // uses left
    }

    #[Test]
    public function executeWithEnginePath(): void
    {
        $vault = $this->createMock(Vault::class);
        $vault->expects($this->once())
            ->method('login')
            ->with('role-id', 'secret-id', 'custom-engine', false)
            ->willReturn($this->createToken());

        $tester = $this->createCommandTester($vault);
        $tester->execute(['--engine-path' => 'custom-engine']);
    }

    #[Test]
    public function executeWithRefresh(): void
    {
        $vault = $this->createMock(Vault::class);
        $vault->expects($this->once())
            ->method('login')
            ->with('role-id', 'secret-id', 'approle', true)
            ->willReturn($this->createToken());

        $tester = $this->createCommandTester($vault);
        $tester->execute(['--refresh' => true]);
    }

    #[Test]
    public function executeDisplaysExpiredToken(): void
    {
        $token = $this->createExpiredToken();

        $vault = $this->createStub(Vault::class);
        $vault->method('login')->willReturn($token);

        $tester = $this->createCommandTester($vault);
        $tester->execute([]);
        $output = $tester->getDisplay();

        // Renewable should show "No" for non-renewable token
        $this->assertMatchesRegularExpression('/Renewable\s+No/', $output);
        // Is Expired should show "Yes" for expired token
        $this->assertMatchesRegularExpression('/Is Expired\s+Yes/', $output);
    }

    private function createCommandTester(Vault $vault): CommandTester
    {
        $command = new VaultLoginCommand($vault, 'role-id', 'secret-id');
        $application = new Application();
        $application->add($command);

        return new CommandTester($application->find('itkdev:vault:login'));
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

    private function createExpiredToken(): Token
    {
        return new Token(
            token: 'hvs.expired',
            expiresAt: new \DateTimeImmutable('-1 hour', new \DateTimeZone('UTC')),
            renewable: false,
            roleName: 'expired-role',
            numUsesLeft: 0,
        );
    }
}
