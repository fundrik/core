# Fundrik Core

*Domain-driven PHP library for building transparent fundraising systems.*

[![Checks](https://github.com/fundrik/core/actions/workflows/checks.yml/badge.svg?branch=main)](https://github.com/fundrik/core/actions/workflows/checks.yml?query=branch%3Amain)
![License](https://img.shields.io/github/license/fundrik/core)
![Packagist](https://img.shields.io/packagist/v/fundrik/core)
![PHP Version](https://img.shields.io/badge/PHP-8.3+-blue)
![CodeStyle](https://img.shields.io/badge/Code%20Style-FundrikStandard-blueviolet)

![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-brightgreen)
![PHPUnit](https://img.shields.io/badge/PHPUnit-100%25%20coverage-brightgreen)
![Infection](https://img.shields.io/badge/Infection-100%25%20killed-brightgreen)
![Deptrac](https://img.shields.io/badge/Deptrac-100%25%20allowed-brightgreen)

Fundrik Core contains the domain model and application use cases for fundraising campaigns and donations. It is framework-agnostic and provides ports for persistence, read models, application events, and payment gateways. Applications supply adapters for those ports and wire the services or handlers through their preferred dependency injection container.

## Requirements

- PHP 8.3 or later.
- Composer 2.

## Installation

```bash
composer require fundrik/core
```

## Capabilities

- Create, synchronize, rename, enable, disable, retarget, and delete campaigns.
- Create donations and manage their `created`, `pending`, `succeeded`, `rejected`, and `refunded` lifecycle.
- Create idempotent donation checkouts through a payment gateway port.
- Process normalized payment results idempotently.
- Publish application events after successful state changes.
- Read campaigns and donations through dedicated read-model ports.
- Use integer or UUID entity identifiers and optimistic entity versions.

Amounts are integers in the currency's minor unit. For example, `1500` represents `15.00` for a currency with two decimal places. Currency values are normalized three-letter codes; the library validates their format but does not check membership in ISO 4217.

## Quick start

The high-level services are the preferred API for common campaign and donation operations. They are assembled from use-case handlers and adapters in your application's composition root.

```php
<?php

use Fundrik\Core\Components\Campaigns\Application\Commands\CreateCampaignCommand;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCommand;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;

/** @var CampaignCommandService $campaigns */
/** @var DonationCommandService $donations */

$campaigns->create(
	new CreateCampaignCommand(
		id: '0199a4de-0c2f-7a35-8c41-f946bdc1d62a',
		title: 'Community library',
		accepts_donations: true,
		currency_code: 'USD',
		target_amount: 500_000,
	),
);

$donations->create(
	new CreateDonationCommand(
		id: '0199a4df-5d1e-75c4-b067-9364f27a3871',
		campaign_id: '0199a4de-0c2f-7a35-8c41-f946bdc1d62a',
		amount: 2_500,
	),
);
```

The services do not build infrastructure themselves. Implement the required repository, read, event bus, and gateway ports, then inject them into the handlers. See [Implementing adapters](docs/implementing-adapters.md) and the [usage examples](examples/README.md).

## Public API

Fundrik Core exposes two supported application entry levels:

- `Application/Services/*Service` is the ergonomic high-level API for external consumers.
- `Application/UseCases/*Handler` is the lower-level API for integrations that need domain entities, value objects, or specialized workflows.

Types in `Components/Shared/Domain` are stable foundational types. Port interfaces define the contracts implemented by infrastructure adapters. Types marked `@internal` are not part of the supported public API.

Read [Public API](docs/public-api.md) for the available entry points and [Architecture](docs/architecture.md) for dependency boundaries.

## Failures

Application failures implement `FundrikApplicationExceptionInterface`. Mutation and workflow exceptions expose a `UseCaseFailureStage` that identifies where processing failed:

- `Precondition` — validation or a business precondition failed before persistence.
- `Persistence` — a repository or state write failed.
- `External` — an external dependency such as a payment gateway failed.
- `EventPublish` — state was persisted, but follow-up event publication failed.

Some precondition failures also expose a typed `reason`. Branch on exception classes, stages, and reasons rather than exception messages. See [Failure handling](docs/failure-handling.md).

## Consistency and idempotency

Repository adapters are responsible for uniqueness, optimistic locking, and translating infrastructure failures into the port exception contracts. Checkout adapters must use the donation ID as a stable provider idempotency key or equivalent merchant payment key.

The core intentionally does not provide a transaction manager or prescribe a storage engine. Cross-repository race windows and event-delivery semantics are documented in [Consistency and idempotency](docs/consistency-and-idempotency.md).

## Documentation

- [Architecture](docs/architecture.md)
- [Public API](docs/public-api.md)
- [Implementing adapters](docs/implementing-adapters.md)
- [Failure handling](docs/failure-handling.md)
- [Consistency and idempotency](docs/consistency-and-idempotency.md)
- [Upgrading](docs/upgrading.md)
- [Examples](examples/README.md)
- [Changelog](CHANGELOG.md)

## Development

The Composer scripts are the source of truth for project checks:

Run `checks` for the regular local or pull request verification. It covers the checks required by the checks workflow:

```bash
composer run checks
```

Run `release-checks` before creating a release. It covers the checks required by the release-checks workflow, including random test ordering, coverage, and mutation testing:

```bash
composer run release-checks
```

For a focused subset, run `composer-checks` after changing `composer.json`, `composer.lock`, or Composer autoload configuration. It validates the Composer configuration, dependencies, and autoloading, and is already included in both aggregate workflows:

```bash
composer run composer-checks
```

Run individual tools when troubleshooting a specific failure:

```bash
composer run lint
composer run rector -- --dry-run
composer run phpstan
composer run deptrac
composer run test
composer run infection
```

## License

Fundrik Core is released under the [MIT License](LICENSE).
