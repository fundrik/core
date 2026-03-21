<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when sync-campaign-from-snapshot operation fails.
 *
 * @since 0.1.0
 */
class SyncCampaignFromSnapshotException extends CampaignApplicationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param SyncCampaignFromSnapshotPreconditionReason|null $reason Optional precondition failure reason.
	 */
	public function __construct(
		private readonly UseCaseFailureStage $stage,
		string $message = '',
		?Throwable $previous = null,
		private readonly ?SyncCampaignFromSnapshotPreconditionReason $reason = null,
	) {

		parent::__construct( $message, 0, $previous );
	}

	/**
	 * Returns processing stage where failure happened.
	 *
	 * @since 0.1.0
	 *
	 * @return UseCaseFailureStage Failure stage.
	 */
	public function get_stage(): UseCaseFailureStage {

		return $this->stage;
	}

	/**
	 * Returns precondition failure reason, when available.
	 *
	 * @since 0.1.0
	 *
	 * @return SyncCampaignFromSnapshotPreconditionReason|null Precondition reason.
	 */
	public function get_reason(): ?SyncCampaignFromSnapshotPreconditionReason {

		return $this->reason;
	}
}
