# Contributing

This document describes various tools used during development of this library.

## Prerequisites

- [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/)
- [Taskfile](https://taskfile.dev/) (`task`)

## Install

Set up the project (starts Docker containers and installs dependencies):

```shell
task setup
```

Or step by step:

```shell
task up
task composer:install
```

## Tests

We use the [PHPUnit](https://phpunit.de/) testing framework.

Run tests:

```shell
task test
```

Run tests with coverage:

```shell
task test:coverage
```

Run the full test matrix across PHP versions and dependency sets (mirrors CI):

```shell
task test:matrix
```

## Static analysis

Run [PHPStan](https://phpstan.org/) at max level:

```shell
task analyze:php
```

## Check coding standards

Run all linters (PHP, Composer, Markdown, YAML):

```shell
task lint
```

Or individually:

```shell
task lint:php         # PHP CS Fixer (dry-run)
task lint:composer    # Validate, normalize, audit
task lint:markdown    # markdownlint
task lint:yaml        # Prettier
```

## Apply coding standards

Fix code style issues automatically:

```shell
task lint:php:fix
task lint:markdown:fix
task lint:yaml:fix
task composer:normalize
```

## Run all CI checks locally

```shell
task pr:actions
```
