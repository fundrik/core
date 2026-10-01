# Architecture

Fundrik Core follows domain-driven and ports-and-adapters principles. It contains business rules and application orchestration but no database, framework, queue, HTTP, or payment-provider implementation.

## Components

The source tree contains three components:

- `Campaigns` owns campaign state, campaign mutations, and campaign read contracts.
- `Donations` owns donation state, payment workflows, and donation read contracts.
- `Shared` contains foundational value objects and cross-component application contracts.

Campaign and donation components may collaborate through explicit application ports. Shared code does not depend on either business component.

## Layers

### Domain

The domain layer contains entities, value objects, factories, domain enums, and domain exceptions. It has no dependency on the application layer or infrastructure.

Entities are immutable snapshots. Mutation methods return new snapshots with the next logical state. Repository adapters persist them and return snapshots carrying the persisted version.

### Application

The application layer coordinates domain behavior and external dependencies. It contains:

- commands and workflow data objects;
- application services;
- use-case handlers;
- read models;
- application events;
- outbound ports;
- application exceptions.

Use-case handlers are supported low-level entry points. They accept domain types where that makes the contract precise and return domain snapshots or workflow results.

Application services form the preferred high-level API. They accept primitives, commands, and shared foundational types, validate public input, and delegate to handlers.

### Infrastructure

Infrastructure lives outside this package. A consuming application implements the outbound ports for its database, read store, message bus, and payment provider.

Typical adapters include:

- an SQL or document repository for campaign entities;
- an SQL or search-backed campaign read adapter;
- an SQL repository and read adapter for donations;
- an event bus backed by a queue, dispatcher, or outbox;
- a payment-gateway adapter.

## Write and read paths

Write operations use repositories and domain entities. Read operations use separate read ports and return application read models. The two sides may use the same storage, but the library does not require them to do so.

Because read models may be projected asynchronously, a successful command does not guarantee that a following query already observes the change. An adapter with synchronous projections may provide immediate visibility as an infrastructure-specific guarantee.

## Application events

Successful writes publish a small application event containing the affected entity ID. The event is published after persistence. The library reports publication failure with the `EventPublish` stage; it does not roll back the already persisted state and does not claim delivery guarantees.

Consumers that require atomic persistence and event enqueueing should implement the event bus with a transactional outbox or an equivalent mechanism coordinated by their infrastructure.

## Dependency direction

Dependencies point inward:

1. Domain code depends only on domain code and approved third-party primitives.
2. Application contracts may depend on domain types and shared application types.
3. Application handlers depend on ports, never concrete infrastructure.
4. Infrastructure depends on Fundrik Core by implementing its ports.

The project enforces layer and component boundaries with Deptrac.

## Public and internal types

Application services, use-case handlers, ports, commands, read models, events, workflow DTOs, exceptions, component domain types, and shared foundational types are supported contracts unless documented otherwise.

Abstract orchestration helpers and types marked with `@internal` are implementation details. Consumers should not extend, instantiate, or type against them.

See [Public API](public-api.md) for entry points and [Implementing adapters](implementing-adapters.md) for infrastructure contracts.
