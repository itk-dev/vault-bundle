<?php

namespace ItkDev\VaultBundle\Tests;

use ItkDev\VaultBundle\ItkDevVaultBundle;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ItkDevVaultBundleTest extends TestCase
{
    #[Test]
    public function configureDefinesRequiredNodes(): void
    {
        $bundle = new ItkDevVaultBundle();

        // Use the bundle's getConfiguration() to get the processed tree
        $configuration = $bundle->getContainerExtension()->getConfiguration([], $this->createStub(ContainerBuilder::class));

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [
            'itkdev_vault' => [
                'role_id' => 'test-role',
                'secret_id' => 'test-secret',
                'url' => 'http://vault.test',
            ],
        ]);

        $this->assertSame('test-role', $config['role_id']);
        $this->assertSame('test-secret', $config['secret_id']);
        $this->assertSame('http://vault.test', $config['url']);
    }

    #[Test]
    public function configureRequiresRoleId(): void
    {
        $bundle = new ItkDevVaultBundle();
        $configuration = $bundle->getContainerExtension()->getConfiguration([], $this->createStub(ContainerBuilder::class));

        $this->expectException(InvalidConfigurationException::class);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [
            'itkdev_vault' => [
                'secret_id' => 'test-secret',
                'url' => 'http://vault.test',
            ],
        ]);
    }
}
