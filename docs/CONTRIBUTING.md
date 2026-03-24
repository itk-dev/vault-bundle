# Contributing

This document describes various tools used during development of this library.

## Install

To install the dependencies required for the development and usage of this
library, run `composer install` through the supplied docker compose setup.

```shell
docker compose run --rm phpfpm composer install
```

## Tests

We use the [PHPUnit](https://phpunit.de/) testing framework.

To run tests execute the following command:

```shell
docker compose run --rm phpfpm vendor/bin/phpunit --coverage-clover=coverage/unit.xml
```

## Check coding standards

The following commands let you test that the code follows the coding
standards we decided to adhere to in this project.

```shell
docker compose run --rm phpfpm composer coding-standards-check
```

### Check Markdown file

```shell
docker compose run --rm node yarn install
docker compose run --rm node yarn run coding-standards-check
```

## Apply coding standards

You can automatically fix some coding styles issues by running:

```shell
docker compose run --rm phpfpm composer coding-standards-apply
```
