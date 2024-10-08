# Vault bundle

This bundle enables Symfony sites to interact with HashiCorp Vault using the
"approle" authentication method. It allows fetching secrets and provides the
capability for local caching of both access tokens and the secrets themselves.

The bundle offers a services wrapper for the PHP
library [itk-dev/vault-library](https://github.com/itk-dev/vault-library).
Additionally, it includes an environment variable processor, enabling the
retrieval of secrets directly in `.env` files at runtime.

## Install

```shell
composer require itk-dev/vault-bundle
```

### Setup

Firstly, enable the bundle by editing `config/bundles.php` adding the bundle class
to the return array in the file. Thereby loading the bundle when bootstrapping
Symfony,

```php
ItkDev\VaultBundle\ItkDevVaultBundle::class => ['all' => true],
```

## Usage

Use the service by simply injecting the service named `Vault` from the
namespace `ItkDev\VaultBundle\Service`. Use the login function to fetch a token
and then use the acquired token in the `getSecret` or `getSecrets` functions.

To use the environment variable processor, use the following format to specify
what to retrieve from the vault:

```dotenv
MY_SECRET=<PATH>:<SECRET>:<KEY>:<VERSION>:<EXPIRE>
```

* __Path__: The secret engine path (e.g. prod, stg, test)
* __Secret__: Name of the secret in the engine (eg. itksites, dokk1)
* __Key__: Secret identifier (eg. OIDC, pretix-api-key)
* __Version__: Optional, fetch a specific version of the secret.
* __Expire__: Optional, the number of seconds to cache the secret.

When the variable have been defined, the next step is to activate the processor
on the variable in `config/services.yaml` using the `vault` keyword.

```yaml
parameters:
  $myOtherSecret: '%env(vault:MY_OTHER_SECRET)%'

App\Command\TestCommand:
  arguments:
    $secret: '%env(vault:MY_SECRET)%'
```

## CLI support

This bundle also comes with two CLI commands to help debug configuration and to
check that you fetch the expected data from the vault. Use the `--help` option
to symfony console to see the options available for the commands.

* `itkdev:vault:login`
* `itkdev:vault:secret`

## Developing

See details on contributing in the [contributing docs](/docs/CONTRIBUTING.md).
