<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when sync-campaign-from-snapshot targets a campaign that no longer exists.
 *
 * @since 0.1.0
 */
final class SyncCampaignFromSnapshotNotFoundException extends SyncCampaignFromSnapshotException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $campaign_id Missing campaign identifier.
	 * @param UseCaseFailureStage $stage Failure stage.
	 * @param Throwable|null $previous Underlying repository exception.
	 * @param SyncCampaignFromSnapshotPreconditionReason|null $reason Failure reason, if available.
	 */
	public function __construct(
		string $campaign_id,
		UseCaseFailureStage $stage,
		?Throwable $previous = null,
		?SyncCampaignFromSnapshotPreconditionReason $reason = null,
	) {

		parent::__construct(
			stage: $stage,
			message: sprintf(
				'Cannot sync campaign "%s": campaign does not exist.',
				$campaign_id,
			),
			previous: $previous,
			reason: $reason,
		);
	}
}
