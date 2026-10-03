# Implementing adapters

Fundrik Core declares outbound interfaces and exception contracts. A consuming application implements them for its infrastructure and injects the adapters into use-case handlers.

## Repository adapters

Implement `CampaignRepositoryPort` and `DonationRepositoryPort` for write-side entity storage.

Repository methods work with domain entities, not read models. Implementations should:

- preserve entity IDs and all domain state;
- persist and hydrate entity versions accurately;
- enforce unique entity IDs;
- apply updates only when the stored version matches the supplied entity version;
- increment the persisted version after a successful update;
- return the actual persisted snapshot from `insert()` and `update()`;
- translate expected infrastructure failures into the declared exception interfaces;
- retain the original infrastructure exception as `previous` where possible.

`DonationRepositoryPort` implementations must also preserve nullable provider payment IDs and enforce uniqueness for non-null values.

An optimistic-lock conflict is reported through the general repository exception interface. The core does not currently define a dedicated conflict exception.

Campaign deletion must report a missing campaign through `CampaignNotFoundExceptionInterface`. Donation and campaign inserts must report duplicate IDs through their respective `AlreadyExistsExceptionInterface` contracts.

## Read adapters

Implement `CampaignReadPort` and `DonationReadPort` for query-side access.

Read adapters return application read models rather than domain entities. They may query normalized write tables, denormalized projections, a search index, or a remote service.

Contracts to preserve include:

- `find_by_id()` returns `null` for an ordinary not-found result;
- `CampaignReadPort::find_by_ids()` returns a list of the campaigns it found;
- `DonationReadPort::paginate()` returns a valid `PaginatedDonations` value;
- operational failures throw the corresponding read exception interface;
- monetary values remain integers in minor units;
- timestamps are represented by `UtcDateTime`.

The read ports do not promise read-after-write consistency. Document stronger guarantees in the adapter package if it provides them.

## Event bus adapter

Implement `ApplicationEventBusPort::publish()` to dispatch application events. Campaign events expose a campaign ID and donation events expose a donation ID.

The use cases call the event bus after persistence. If publication fails, throw an exception implementing `ApplicationEventBusExceptionInterface`. The application exception then reports the `EventPublish` stage and retains the adapter exception as `previous`.

The port describes publication to the bus, not end-to-end delivery. At-most-once, at-least-once, ordering, retries, and deduplication are infrastructure concerns. Use an outbox or equivalent mechanism when durable delivery must be coordinated with persistence.

## Payment gateway adapter

Implement `DonationGatewayPort::create_checkout()` to translate a normalized checkout request into provider-specific input and return a `DonationGatewayCheckoutResult` containing the provider payment ID and redirect URL.

The adapter must:

- use the donation ID as a stable provider idempotency key or equivalent merchant payment key;
- avoid creating or charging a second payment when the same donation ID is replayed;
- return the same provider payment ID when a checkout is replayed;
- preserve the supplied amount and currency;
- preserve success and cancellation URLs;
- throw an exception implementing `DonationGatewayExceptionInterface` on provider or transport failure;
- avoid leaking provider-specific objects through the port.

Inbound webhooks are normalized with both the trusted donation ID and provider payment ID before calling `ProcessDonationPaymentResultHandler`. The handler rejects results whose payment ID does not match the persisted association.

## Wiring

The package is container-neutral. Register adapters against their port interfaces, construct handlers from those interfaces, and expose the service facades to the rest of the application.

For example, `CampaignCommandService` requires its campaign handlers plus `CampaignFactory`. The handlers in turn require repository and event-bus ports. `CampaignQueryService` requires `ReadCampaignByIdHandler`, which requires `CampaignReadPort`.

Keep this object graph in the application's composition root or dependency injection configuration. Do not place infrastructure lookups inside domain entities or handlers.

## Adapter testing checklist

An adapter test suite should cover:

- insertion and duplicate-ID handling;
- missing-entity handling;
- optimistic update success and conflict behavior;
- exact persistence of IDs, provider payment IDs, versions, money, statuses, and UTC timestamps;
- read-model mapping and pagination totals;
- low-level exception translation with `previous` preserved;
- gateway idempotency under repeated calls;
- event publication failure behavior;
- storage-specific concurrency guarantees.

See [Consistency and idempotency](consistency-and-idempotency.md) before choosing transaction boundaries.
