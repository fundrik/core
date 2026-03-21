<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot;

/**
 * Specifies why sync-campaign-from-snapshot precondition validation failed.
 *
 * @since 0.1.0
 */
enum SyncCampaignFromSnapshotPreconditionReason: string {

	case SnapshotInvalid = 'snapshot_invalid';
	case CampaignLookupFailed = 'campaign_lookup_failed';
	case CampaignNotFound = 'campaign_not_found';
	case CurrencyChangeRejected = 'currency_change_rejected';
}
