# Upgrading

## 1.1.0: provider payment IDs

The donation payment workflow now requires the payment provider's opaque payment ID. This is a breaking API and storage change.

### Storage

Add a nullable `payment_id` string column to donation storage and add `created` to the stored status values. Migrate existing `pending` rows without a payment ID to `created`. After migration, `created` rows must have a null payment ID and `pending` rows must have a non-null payment ID. Add a unique constraint or unique index for non-null payment IDs.

Repository adapters must hydrate the new field through `DonationFactory::create()` or `DonationFactory::create_from_primitives()` and preserve it on every update. Read adapters must pass it to the donation read model.

### Gateway adapters

`DonationGatewayCheckoutResult` now requires both values:

```php
return new DonationGatewayCheckoutResult(
	payment_id: PaymentId::create( $provider_response->id ),
	redirect_url: Url::create( $provider_response->confirmation_url ),
);
```

The existing requirement to use `DonationGatewayCheckoutRequest::get_donation_id()` as the provider idempotency key remains unchanged. A repeated checkout request must return the same provider payment ID.

### Checkout wiring

`CreateDonationCheckoutHandler` now requires the donation repository and application event bus after the existing dependencies:

```php
$handler = new CreateDonationCheckoutHandler(
	create_donation: $create_donation_idempotently,
	gateway: $gateway,
	donations: $donation_repository,
	event_bus: $event_bus,
);
```

The handler persists the provider payment ID before returning. Registering it changes the donation from `created` to `pending` in the same repository update. `CreateDonationCheckoutResult::get_payment_id()` exposes the registered `PaymentId`. A newly pending donation publishes `DonationPendingEvent`; idempotent replay of the same association does not publish it again.

If the donation disappears before the payment association is persisted, the handler throws `CreateDonationCheckoutNotFoundException`. Other repository failures remain `CreateDonationCheckoutException` instances with the `Persistence` stage.

### Payment-result processing

Normalize webhook input with both the trusted donation ID and the provider payment ID:

```php
$result = new DonationPaymentResult(
	donation_id: EntityId::create( $trusted_metadata['donation_id'] ),
	payment_id: PaymentId::create( $provider_event['payment_id'] ),
	type: DonationPaymentResultType::Succeeded,
);
```

`ProcessDonationPaymentResultHandler` now receives `FindDonationByIdHandler` as its first dependency instead of `ReadDonationByIdHandler`. It checks the payment ID against authoritative donation state before applying, replaying, or ignoring a status transition.

Precondition failures expose `ProcessDonationPaymentResultPreconditionReason` with `DonationNotFound`, `PaymentNotAttached`, or `PaymentIdMismatch`. Do not branch on exception messages.

### Domain and read contracts

- `Donation` and `DonationFactory::create()` require `?PaymentId $payment_id`.
- `DonationFactory::create_from_primitives()` requires `?string $payment_id`.
- `DonationFactory::create_pending()` and `create_pending_from_primitives()` were replaced by `create_created()` and `create_created_from_primitives()`.
- `Donation::await_payment()` registers the expected payment for a `created` donation and returns it in `pending`; awaiting the same payment again is safe.
- `DonationStatus::Created` is the initial state. `Pending` now guarantees that a provider payment is attached.
- The donation read-model constructor requires `?string $payment_id` and exposes `get_payment_id()`.
- `ProcessDonationPaymentResult` now exposes `get_payment_id()`.

Update stored status constraints and migrate pending rows without payment IDs before deploying the new core version. Then update adapters, factory calls, composition-root wiring, and webhook normalization before enabling checkout traffic.
