# Changelog

## [1.1.0]

### Added

- Validation that incoming payment results belong to the payment associated with the donation.
- A `DonationPendingEvent` when a donation first enters the payment-waiting state.

### Changed

- Checkout now records the provider payment ID on the donation before returning the redirect result.
- New donations start in `created` and move to `pending` when a provider payment is registered and awaits a result.
- Payment-result processing checks the registered payment before applying status changes.

See [Upgrading](docs/upgrading.md#110-provider-payment-ids) for the required adapter, storage, and wiring changes.

[1.1.0]: https://github.com/fundrik/core/compare/v1.0.0...HEAD

## [1.0.0] - 2026-10-02

### Added

- Initial public release.
- Campaign and donation domain models and application services.
- Payment checkout and idempotent payment-result workflows.
- Ports for persistence, read models, events, and payment gateways.

[1.0.0]: https://github.com/fundrik/core/releases/tag/v1.0.0
