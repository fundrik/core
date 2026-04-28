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
- Do not add runtime no-op code like `unset( $unused )` only to silence linting for fixed callback signatures; prefer a targeted `phpcs:ignore` with a short reason instead.

# Docblock Conventions

- Treat docblocks as concise API reference text, not prose paragraphs.
- Keep class/interface/enum/trait summary lines as one sentence in present tense, ending with a period.
- Prefer stable summary verbs by artifact role:
  - `Represents ...` for value objects, DTOs, commands, and read models.
  - `Provides ...` for services, factories, and ports when they expose an entry point or capability.
  - `Creates ...`, `Returns ...`, `Checks ...`, `Formats ...`, `Converts ...` for methods, based on what they do.
- For port interfaces, use the standard summary wording `Provides the <inbound|outbound> port for ...`.
- In `@param`, `@return`, and `@throws` descriptions, use short noun phrases or outcome phrases, not full explanatory sentences.
- Do not start `@param`, `@return`, or `@throws` descriptions with `The`; prefer `Campaign ID.` over `The campaign ID.`.
- Keep tag descriptions in sentence case and end them with a period.
- For booleans in `@return`, prefer `True when ...`.
- For nullable values, prefer explicit endings such as `..., if configured.` or `..., null otherwise.`.

# Application Boundary Conventions

- Treat `Application/UseCases/*Handler` as the supported low-level public application API for domain operations.
- Treat `Application/Services/*Service` as the preferred high-level public API for external consumers.
- Public API discovery should prefer services by default; handlers remain supported for advanced integrations that want lower-level control.
- If a capability exists both as a handler and as a service method, the service contract is the ergonomic facade and the handler contract is the lower-level equivalent.
- Use cases may work with domain entities and value objects as part of their supported contracts; service contracts should be designed for external ergonomics, safety by default, and long-term backward compatibility.
- Treat abstract use-case helpers and other shared orchestration details as internal unless they are intentionally documented as supported public contracts.

# Public API Conventions

- Treat this package as a public library: when designing or changing behavior, evaluate not only current internal usage, but also API clarity, ergonomics, and logical consistency for external consumers.
- `EntityId` is the only domain/shared value object currently allowed by default in public service contracts; treat it as a stable public contract when it improves identity ergonomics.
- Low-level use-case handlers may expose additional domain entities and value objects when that is part of the supported contract.
- Do not require aggregate-specific domain value objects such as `CampaignTitle`, `CampaignTarget`, or `Money` in public service contracts unless there is an explicit decision to bless them as public shared types.
- Prefer scalar public inputs/outputs for domain-specific values in service contracts and read models; for monetary values, use explicit `amount`/`currency` pairs instead of exposing `Money`.

# Use Case Failure Conventions

- For application use-case failures, use the shared `UseCaseFailureStage` enum (`Precondition`, `Persistence`, `EventPublish`) by default.
- Treat `stage` as "where the failure happened" and `reason` as "why it happened within that stage".
- Add operation-specific reason enums only when needed for branching logic, and scope them to a stage (for example, `DeleteCampaignPreconditionReason` for `Precondition`).
- Do not introduce per-use-case `*FailureStage` enums unless there is an explicit and documented need to diverge from `UseCaseFailureStage`.
- Keep `reason` nullable for non-precondition failures unless a use case explicitly defines another stage-specific reason contract.

# Project Scripts

- Source of truth for runnable project commands is the `scripts` section in `composer.json`.
- Before running lint/tests commands, read scripts from those files and execute via `composer run <script>`.
- In this workspace, if `composer run <script>` fails because Composer uses a PHP build without `openssl`, rerun it as `$env:COMPOSER_ALLOW_XDEBUG='1'; php -c .tmp/php.ini -d extension=php_openssl.dll C:\ProgramData\ComposerSetup\bin\composer.phar run <script>`.
- If a Composer script invokes `php` directly and still fails because extensions such as `mbstring` are missing, copy the underlying command from `composer.json` and run it with `php -c .tmp/php.ini ...` instead of plain `php`.
