# Failure handling

Fundrik Core uses exception types as the primary failure discriminator. Exception messages are concise diagnostics for developers and are not stable machine-readable contracts.

## Exception families

Application-level exceptions implement `FundrikApplicationExceptionInterface`. They include service and use-case failures caused by invalid public input, failed preconditions, persistence, event publication, and external dependencies.

Domain exceptions extend `FundrikDomainException`. They report invalid value objects, failed entity construction, and rejected domain transitions. High-level services normally translate domain input failures into the corresponding application exception.

Port interfaces declare marker exception interfaces. Adapter exceptions should extend an appropriate PHP exception class and implement the declared marker interface.

## Failure stages

Mutation and workflow exceptions expose `get_stage()` with one of these values:

| Stage | Meaning | State-change implication |
| --- | --- | --- |
| `Precondition` | Input or a business precondition failed. | The requested write was not performed. |
| `Persistence` | A repository or state write failed. | Persistence outcome depends on the adapter failure and transaction contract. |
| `External` | An external dependency failed. | Earlier workflow steps may already have persisted state. |
| `EventPublish` | State was persisted, but event publication failed. | Do not blindly repeat a non-idempotent write. |

Always inspect the exception type and its stage before deciding whether to retry.

## Failure reasons

Some exceptions expose a nullable typed `reason` when their precondition stage has multiple outcomes that consumers may need to distinguish. Reasons are scoped to their use case, for example `DeleteCampaignPreconditionReason` and `CreateDonationPreconditionReason`.

A `null` reason is expected for stages or operations without a more specific reason contract.

## Catching failures

Catch the narrow operation exception when the caller owns a specific workflow:

```php
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignPreconditionReason;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;

try {
	$campaigns->delete($campaign_id);
} catch (DeleteCampaignException $exception) {
	if (
		$exception->get_stage() === UseCaseFailureStage::Precondition
		&& $exception->get_reason() === DeleteCampaignPreconditionReason::HasDonations
	) {
		// Return the application's campaign-not-empty response.
	}

	throw $exception;
}
```

Catch `FundrikApplicationExceptionInterface` only at a broad application boundary such as centralized logging or HTTP exception mapping.

## Previous exceptions

Application and adapter layers preserve low-level details through `Throwable::getPrevious()`. Log the exception chain for diagnostics, but do not expose database, provider, or transport details directly to end users.

## Retry guidance

- Precondition failures are not transient unless the relevant business state changes.
- Persistence failures may be retried only when the adapter and operation make the retry safe.
- External checkout failures may be retried because the gateway port requires idempotency by donation ID.
- Event publication failures require infrastructure-specific recovery. The state change has already been persisted.

Idempotent workflow result statuses such as `Replayed` and `Ignored` are normal results, not exceptions.
