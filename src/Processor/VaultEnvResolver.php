<?php

namespace ItkDev\VaultBundle\Processor;

use ItkDev\Vault\Exception\NotFoundException;
use ItkDev\Vault\Exception\VaultException;
use ItkDev\VaultBundle\Service\Vault;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

readonly class VaultEnvResolver implements EnvVarProcessorInterface
{
    public function __construct(
        private Vault $vaultService,
        private string $roleId,
        private string $secretId,
    ) {
    }

    /**
     * Get the value of provided env variable.
     *
     * @throws \DateMalformedStringException
     * @throws VaultException
     * @throws NotFoundException
     * @throws InvalidArgumentException
     */
    public function getEnv(string $prefix, string $name, \Closure $getEnv): mixed
    {
        $nameValue = $getEnv($name);
        $params = explode(':', $nameValue);

        return $this->getSecret(...$params);
    }

    public static function getProvidedTypes(): array
    {
        return ['vault' => 'string'];
    }

    /**
     * Get secret from the vault.
     *
     * @param string $path
     *   The vault path to use (e.g. "prod", "stg")
     * @param string $secret
     *   The name of the secret to fetch
     * @param string $key
     *   The id of the secret
     * @param int|null $version
     *   The version to fetch
     * @param int|null $expire
     *   Cache this secret in seconds
     *
     * @return string
     *   The secret found
     *
     * @throws \DateMalformedStringException
     * @throws NotFoundException
     * @throws VaultException
     * @throws InvalidArgumentException
     */
    private function getSecret(string $path, string $secret, string $key, ?int $version = null, ?int $expire = null): string
    {
        $token = $this->vaultService->login($this->roleId, $this->secretId);
        $val = $this->vaultService->getSecret(
            token: $token,
            path: $path,
            secret: $secret,
            key: $key,
            version: $version,
            useCache: !is_null($expire),
            expire: $expire ?? 0,
        );

        return $val->value;
    }
}
