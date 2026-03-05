# Testing Conventions

- If `phpunit` fails because `mbstring` is missing, run PHPUnit with `php -c .tmp/php.ini vendor/bin/phpunit`.
- Do not call private/protected methods via reflection in tests to force coverage.
- If target lines cannot be reached through the public API, report this explicitly and align on refactoring or coverage exclusions instead of testing internals directly.
- When tests need fake/dummy classes, create them in `tests/unit/Fixtures/` instead of declaring ad-hoc anonymous/helper classes inside test files.

# Exception Message Policy

- Keep exception message text developer-focused, concise, and in English.
- Use stable templates:
  - Validation: `<Field> must <constraint>. Given: <value>.`
  - Business rule: `Cannot <action> <entity> "<id>": <reason>.`
  - Infrastructure/read failures: `Failed to <action> <entity> "<id>".`
  - Post-action side-effect failures: `<Entity> "<id>" was <past participle>, but <side effect> failed.`
- End every exception message with a period.
- Do not build logic on `message` text.
- Use exception class as the primary discriminator.
- Add `stage`/`reason` only when there are 2+ meaningful failure branches that require different handling.
- Preserve low-level details via `previous` exceptions, not by concatenating nested messages into the top-level message.

# Editing Conventions

- When editing files in this repository, preserve Windows line endings (`CRLF`).

# Architecture Conventions

- Port interface docblocks should use the standard wording `Provides the <inbound|outbound> port for ...` for consistency across the codebase.

# Public API Conventions

- Treat this package as a public library: when designing or changing behavior, evaluate not only current internal usage, but also API clarity, ergonomics, and logical consistency for external consumers.

# Use Case Failure Conventions

- For application use-case failures, use the shared `UseCaseFailureStage` enum (`Precondition`, `Persistence`, `EventPublish`) by default.
- Treat `stage` as "where the failure happened" and `reason` as "why it happened within that stage".
- Add operation-specific reason enums only when needed for branching logic, and scope them to a stage (for example, `DeleteCampaignPreconditionReason` for `Precondition`).
- Do not introduce per-use-case `*FailureStage` enums unless there is an explicit and documented need to diverge from `UseCaseFailureStage`.
- Keep `reason` nullable for non-precondition failures unless a use case explicitly defines another stage-specific reason contract.
