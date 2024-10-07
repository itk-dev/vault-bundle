<?php

namespace ItkDev\VaultBundle\Service;

use ItkDev\Vault\Model\Token;
use ItkDev\Vault\Vault as VaultClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;

readonly class Vault
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private CacheInterface $cache,
        private string $vaultUrl,
    ) {
    }

    public function login(string $roleId, string $secretId, string $enginePath = 'approle', bool $refreshCache = false): Token
    {
        return $this->getVault()->login($roleId, $secretId, $enginePath, $refreshCache);
    }

    public function getSecrets(Token $token, string $path, string $secret, array $ids, bool $useCache = false, bool $refreshCache = false, int $expire = 0): array
    {
        return $this->getVault()->getSecrets(
            token: $token,
            path: $path,
            secret: $secret,
            ids: $ids,
            useCache: $useCache,
            refreshCache: $refreshCache,
            expire: $expire
        );
    }

    private function getVault()
    {
        return new VaultClient(
            httpClient: $this->client,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            cache: $this->cache,
            vaultUrl: $this->vaultUrl
        );
    }
}
