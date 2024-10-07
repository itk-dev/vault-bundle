<?php

namespace ItkDev\VaultBundle\Command;

use ItkDev\Vault\Exception\VaultException;
use ItkDev\VaultBundle\Service\Vault;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'itkdev:vault:login',
    description: 'Log into the vault and show token information',
)]
class VaultTokenCommand extends Command
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
            ->addOption('engine-path', null, InputOption::VALUE_REQUIRED, 'Authentication engine path', 'approle')
            ->addOption('refresh', null, InputOption::VALUE_NONE, 'Refresh token from the vault (by-passing the cache)')
        ;
    }

    /**
     * @throws VaultException
     * @throws \DateMalformedStringException
     * @throws \DateInvalidOperationException
     * @throws \DateMalformedIntervalStringException
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $enginePath = $input->getOption('engine-path');
        $refresh = $input->getOption('refresh');

        $token = $this->vaultService->login($this->roleId, $this->secretId, $enginePath, $refresh);

        $tableHeaders = ['Field', 'Value'];
        $tableRows = [
            ['Token', $token->token],
            ['Expires At', $token->expiresAt->format('Y-m-d H:i:s').' '.$token->expiresAt->getTimezone()->getName()],
            ['Renewable', $token->renewable ? 'Yes' : 'No'],
            ['Role Name', $token->roleName],
            ['Number of Uses Left', $token->usesLeft()],
            ['Is Expired', $token->isExpired() ? 'Yes' : 'No'],
        ];
        $io->table($tableHeaders, $tableRows);

        return Command::SUCCESS;
    }
}
