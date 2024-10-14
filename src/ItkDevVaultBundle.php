<?php

namespace ItkDev\VaultBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ItkDevVaultBundle extends AbstractBundle
{
    protected string $extensionAlias = 'itkdev_vault';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('role_id')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('secret_id')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
                ->scalarNode('url')
                    ->cannotBeEmpty()
                    ->isRequired()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Load an XML, PHP or YAML file
        $container->import('../config/services.yaml');

        // This hack is need as all $config variable are placeholders, but we
        // need the to be resolved to connect to the vault in our envProcessor.
        $url = $builder->resolveEnvPlaceholders($config['url'], true);
        $roleId = $builder->resolveEnvPlaceholders($config['role_id'], true);
        $secretId = $builder->resolveEnvPlaceholders($config['secret_id'], true);

        $container->services()
            ->get('ItkDev\VaultBundle\Service\Vault')
                ->arg('$vaultUrl', $url)
            ->get('ItkDev\VaultBundle\Command\VaultLoginCommand')
                ->arg('$roleId', $roleId)
                ->arg('$secretId', $secretId)
            ->get('ItkDev\VaultBundle\Command\VaultSecretCommand')
                ->arg('$roleId', $roleId)
                ->arg('$secretId', $secretId)
            ->get('ItkDev\VaultBundle\Processor\VaultEnvResolver')
                ->arg('$roleId', $roleId)
                ->arg('$secretId', $secretId)
        ;
    }
}
