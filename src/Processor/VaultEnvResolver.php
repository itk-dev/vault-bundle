<?php

namespace ItkDev\VaultBundle\Processor;

use ItkDev\VaultBundle\Service\Vault;
use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

readonly class VaultEnvResolver implements EnvVarProcessorInterface
{
    public function __construct(
        private Vault $vaultService,
        private readonly string $roleId,
        private readonly string $secretId,
    ) {
    }

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
     * @param string $id
     *   The id of the secret
     * @param int|null $version
     *   The version to fetch
     * @param int|null $expire
     *   Cache this secret in seconds
     *
     * @return string
     */
    private function getSecret(string $path, string $secret, string $id, ?int $version = null, ?int $expire = null): string
    {
        $token = $this->vaultService->login($this->roleId, $this->secretId);
        $val = $this->vaultService->getSecret(
            token: $token,
            path: $path,
            secret: $secret,
            id: $id,
            version: $version,
            useCache: !is_null($expire),
            expire: $expire,
        );

        return $val->value;
    }
}
