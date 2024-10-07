<?php

namespace ItkDev\VaultBundle\Service;

use ItkDev\Vault\Exception\NotFoundException;
use ItkDev\Vault\Exception\VaultException;
use ItkDev\Vault\Model\Secret;
use ItkDev\Vault\Model\Token;
use ItkDev\Vault\Vault as VaultClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

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

    /**
     * @throws \DateMalformedStringException
     * @throws VaultException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function getSecret(Token $token, string $path, string $secret, string $id, ?int $version = null, bool $useCache = false, bool $refreshCache = false, int $expire = 0): Secret
    {
        return $this->getVault()->getSecret(
            token: $token,
            path: $path,
            secret: $secret,
            id: $id,
            version: $version,
            useCache: $useCache,
            refreshCache: $refreshCache,
            expire: $expire
        );
    }

    /**
     * @throws VaultException
     * @throws \DateMalformedStringException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function getSecrets(Token $token, string $path, string $secret, array $ids, ?int $version = null, bool $useCache = false, bool $refreshCache = false, int $expire = 0): array
    {
        return $this->getVault()->getSecrets(
            token: $token,
            path: $path,
            secret: $secret,
            ids: $ids,
            version: $version,
            useCache: $useCache,
            refreshCache: $refreshCache,
            expire: $expire
        );
    }

    /**
     * Helper function to create vault client.
     *
     * @return VaultClient
     *   The client
     */
    private function getVault(): VaultClient
    {
        static $vaultClient = null;

        if (is_null($vaultClient)) {
            $vaultClient = new VaultClient(
                httpClient: $this->client,
                requestFactory: $this->requestFactory,
                streamFactory: $this->streamFactory,
                cache: $this->cache,
                vaultUrl: $this->vaultUrl
            );
        }

        return $vaultClient;
    }
}
