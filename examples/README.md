# Examples

The examples demonstrate the supported application APIs without selecting a framework or infrastructure stack. They are regular classes under the `Fundrik\Core\Examples` development namespace and receive already-wired services or handlers through constructor injection.

- [`CampaignLifecycleExample`](CampaignLifecycleExample.php) uses the high-level campaign services.
- [`DonationLifecycleExample`](DonationLifecycleExample.php) uses the high-level donation services.
- [`PaymentWorkflowExample`](PaymentWorkflowExample.php) uses the low-level checkout and payment-result workflows.

## Running an example

Register your adapters for the required ports, assemble the handlers and services in your dependency injection container, then invoke the example class from your application or a local script.

For example:

```php
use Fundrik\Core\Examples\CampaignLifecycleExample;

/** @var CampaignLifecycleExample $example */
$campaign_id = $example->create_campaign();
$example->rename_and_disable($campaign_id);

$campaign = $example->find_campaign($campaign_id);
```

The query may temporarily return `null` when the read adapter uses asynchronous projections. See [Architecture](../docs/architecture.md) and [Consistency and idempotency](../docs/consistency-and-idempotency.md).

The examples intentionally do not include in-memory repositories or gateway stubs: simplistic adapters can hide uniqueness, optimistic-locking, idempotency, and transaction requirements that production adapters must implement.
