# Public API

Fundrik Core provides high-level services for common operations and low-level handlers for advanced integrations. Constructor injection is used throughout; the package does not depend on a service container.

## High-level services

### CampaignCommandService

`CampaignCommandService` exposes campaign write operations:

| Method | Purpose |
| --- | --- |
| `create(CreateCampaignCommand $command)` | Creates a campaign from public input. |
| `sync_from_snapshot(SyncCampaignFromSnapshotCommand $command)` | Synchronizes an existing campaign from an authoritative snapshot. |
| `rename($campaign_id, string $new_title)` | Renames a campaign. |
| `enable_donations($campaign_id)` | Enables donation acceptance. |
| `disable_donations($campaign_id)` | Disables donation acceptance. |
| `change_target_amount($campaign_id, ?int $target_amount)` | Changes or clears the target amount. |
| `delete($campaign_id)` | Deletes a campaign that has no donations. |

Campaign IDs accept an integer, a UUID string, or `EntityId`. Target amounts use minor units.

### CampaignQueryService

`CampaignQueryService::find_by_id()` returns a campaign read model or `null` when the campaign is not found.

### DonationCommandService

`DonationCommandService` exposes donation write operations:

| Method | Purpose |
| --- | --- |
| `create(CreateDonationCommand $command)` | Creates a donation without an initialized provider payment. |
| `succeed($donation_id)` | Changes a pending donation to succeeded. |
| `reject($donation_id)` | Changes a pending donation to rejected. |
| `refund($donation_id)` | Changes a succeeded donation to refunded. |

Donation IDs accept an integer, a UUID string, or `EntityId`. Donation amounts use minor units and inherit the campaign currency.

### DonationQueryService

`DonationQueryService::find_by_id()` returns a donation read model or `null`. `DonationQueryService::paginate()` returns `PaginatedDonations` with items, current page, page size, total item count, and total page count.

## Specialized workflows

Specialized workflows are intentionally exposed as handlers rather than service methods.

### Idempotent donation creation

`CreateDonationIdempotentlyHandler` attempts strict creation first. When the ID already exists, it returns the existing donation only if campaign ID and amount match the request. A different payload for the same ID raises `CreateDonationIdempotentlyConflictException`.

The result status is either `Created` or `Replayed`.

### Checkout creation

`CreateDonationCheckoutHandler` combines idempotent donation creation with `DonationGatewayPort`. A new donation starts in `created`. Persisting the provider payment association changes it to `pending`, meaning that the payment is ready and awaiting a result. Replaying checkout for an already pending donation is allowed when the gateway returns the same payment ID.

The payment-backed lifecycle is `created -> pending -> succeeded|rejected`, with `succeeded -> refunded`. A successful or rejected payment result cannot be applied directly to a created donation.

The gateway adapter must make repeated checkout calls for the same donation ID safe. See [Consistency and idempotency](consistency-and-idempotency.md).

### Payment result processing

`ProcessDonationPaymentResultHandler` validates the normalized donation and provider payment IDs, then converts the gateway result into a donation state transition. Its result status is:

- `Applied` when a state transition was performed;
- `Replayed` when the same result had already been applied;
- `Ignored` when the result is not applicable to the current state.

The policy accepts the normalized result types `Succeeded`, `Rejected`, and `Refunded`.

A missing payment association or mismatched payment ID fails with the `Precondition` stage and a typed `ProcessDonationPaymentResultPreconditionReason`.

## Low-level handlers

Handlers under `Application/UseCases` remain supported for integrations that need precise control or domain-level results. They generally accept `EntityId`, `Amount`, component domain entities, or use-case DTOs.

Use handlers when:

- wiring a specialized workflow;
- consuming the returned persisted entity snapshot;
- integrating with domain value objects directly;
- implementing behavior not surfaced by a high-level service.

Prefer services when primitives and a stable ergonomic facade are sufficient.

## Shared foundational types

Types in `Components/Shared/Domain` are stable public building blocks:

- `EntityId` supports positive integers and valid UUID strings and can create UUIDv4 and UUIDv7 values.
- `EntityVersion` represents positive optimistic-lock versions.
- `Amount` represents a positive amount in minor units.
- `Currency` normalizes and validates a three-letter Latin code without checking an ISO 4217 registry.
- `Money` combines `Amount` and `Currency`.
- `UtcDateTime` wraps a UTC `DateTimeImmutable`.

`Components/Shared/Application/Url` is the validated URL type used by checkout workflows.

## Read models and entities

Read models are transport-friendly query results and do not expose mutation behavior. Domain entities represent write-side state and return new snapshots from mutation methods.

Do not reconstruct persisted entities by guessing version semantics. Repository adapters should hydrate them through the provided factories and return the persisted snapshots required by their port contracts.

## Compatibility

Public services, handlers, ports, commands, read models, events, workflow DTOs, exceptions, component domain types, and shared foundational types follow semantic versioning.

Types carrying an `@internal` annotation are excluded from the compatibility promise. Exception message text is diagnostic and is not an API discriminator; use exception types, stages, and reasons.
