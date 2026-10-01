# Consistency and idempotency

Fundrik Core defines domain behavior and port contracts without prescribing a database or transaction manager. Integrators choose the consistency model and must understand the boundaries below.

## Optimistic locking

Campaign and donation repository `update()` methods use the entity version as the expected persisted version. An adapter must update only when the expected version still matches, increment the persisted version on success, and return the resulting snapshot.

A version mismatch is a repository failure. Callers should reload state before deciding whether the business operation can be repeated.

## Unique identifiers

Repository adapters must enforce unique campaign and donation IDs at the storage boundary. Application-side existence checks alone are not sufficient under concurrency.

Strict donation creation raises `CreateDonationAlreadyExistsException` for a duplicate ID. Idempotent donation creation resolves that duplicate by loading the existing donation and comparing its campaign ID and amount with the request.

## Checkout idempotency

`CreateDonationCheckoutHandler` may call the gateway again when an earlier request created the donation but its response was lost. `DonationGatewayPort` therefore requires the donation ID to be used as a stable provider idempotency key or equivalent merchant payment key.

The adapter must return the existing checkout or another equivalent safe result rather than create or charge a second payment.

## Payment result idempotency

`ProcessDonationPaymentResultHandler` compares the normalized provider result with the current donation status:

- a transition from its required source state is `Applied`;
- a result whose target state already exists is `Replayed`;
- a result that is not valid from the current state is `Ignored`.

The allowed state transitions are `pending -> succeeded`, `pending -> rejected`, and `succeeded -> refunded`.

Concurrent webhook processing can still produce optimistic-lock conflicts. The consumer may reload and invoke the handler again to resolve the final result as replayed or ignored.

## Event publication

Application events are published after repository persistence. A publication failure produces the `EventPublish` stage and does not undo the state change.

The core does not guarantee atomicity between repository persistence and event publication. Applications that require durable, exactly-once-like processing should use a transactional outbox, consumer deduplication, and idempotent projections.

## Cross-repository race windows

Two cross-component checks are intentionally non-atomic in the core contract:

1. Campaign deletion checks whether donations exist and then deletes the campaign. A donation may be inserted between those operations.
2. Donation creation loads the campaign and checks that it accepts donations before inserting the donation. The campaign may be deleted or donation acceptance may be disabled between those operations.

These windows are explicit limitations, not guarantees of atomic enforcement. Applications requiring stronger integrity must provide it in infrastructure, for example with transaction coordination, row locking, conditional writes, foreign keys, or storage-specific constraints.

Adapters should document which guarantees they provide and how constraint failures are translated into port exceptions.

## Read-model visibility

Read ports may be backed by asynchronously updated projections. Command success therefore does not imply immediate query-side visibility. Consumers that need read-your-writes behavior should use returned low-level handler snapshots where available or provide a synchronously updated read adapter.
