<?php

namespace ItkDev\VaultBundle\Command;

use ItkDev\Vault\Exception\NotFoundException;
use ItkDev\Vault\Exception\VaultException;
use ItkDev\Vault\Model\Secret;
use ItkDev\VaultBundle\Service\Vault;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'itkdev:vault:secret',
    description: 'Fetch secret from the vault',
)]
class VaultSecretCommand extends Command
{
    public function __construct(
        private readonly Vault $vaultService,
        private readonly string $roleId,
        private readonly string $secretId,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Vault secret engine path)')
            ->addOption('secret', null, InputOption::VALUE_REQUIRED, 'Name of the secret to fetch')
            ->addOption('keys', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'List of secret keys to fetch')
            ->addOption('version-id', null, InputOption::VALUE_REQUIRED, 'Version of the secret to fetch')
            ->addOption('useCache', null, InputOption::VALUE_NONE, 'Cache the token and secrets fetched')
            ->addOption('expire', null, InputOption::VALUE_REQUIRED, 'For how long the secrets should be cached (in seconds). The token will be cached based on its expiration time.')
            ->addOption('refresh', null, InputOption::VALUE_NONE, 'Should both token and secrets be refreshed from the vault (by-passing the cache)')
        ;
    }

    /**
     * @throws InvalidArgumentException
     * @throws NotFoundException
     * @throws VaultException
     * @throws \DateMalformedIntervalStringException
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = $input->getOption('path');
        $secret = $input->getOption('secret');
        $keys = $input->getOption('keys');
        $version = $input->getOption('version-id');

        $useCache = $input->getOption('useCache');
        $expire = (int) $input->getOption('expire');
        $refresh = $input->getOption('refresh');

        $token = $this->vaultService->login($this->roleId, $this->secretId);
        $secrets = $this->vaultService->getSecrets(
            token: $token,
            path: $path,
            secret: $secret,
            keys: $keys,
            version: $version,
            useCache: $useCache,
            refreshCache: $refresh,
            expire: $expire,
        );

        // Prepare the data for the table
        $tableHeaders = ['Key', 'Secret', 'Version'];
        $tableRows = [];
        /** @var Secret $secret */
        foreach ($secrets as $secret) {
            $tableRows[] = [$secret->key, $secret->value, $secret->version];
        }
        $io->table($tableHeaders, $tableRows);

        return Command::SUCCESS;
    }
}
