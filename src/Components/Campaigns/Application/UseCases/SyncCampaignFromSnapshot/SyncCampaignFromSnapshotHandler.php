<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignSynchronizedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignNotFoundExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
	use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles synchronizing a campaign from an authoritative snapshot.
 *
 * @since 0.1.0
 */
final readonly class SyncCampaignFromSnapshotHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $campaigns Persists synchronized campaigns.
	 * @param ApplicationEventBusPort $event_bus Publishes campaign events.
	 */
	public function __construct(
		private CampaignRepositoryPort $campaigns,
		private ApplicationEventBusPort $event_bus,
	) {}

	/**
	 * Synchronizes a campaign from a validated snapshot.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $snapshot Authoritative campaign snapshot.
	 *
	 * @throws SyncCampaignFromSnapshotException When synchronization fails.
	 */
	public function handle( Campaign $snapshot ): void {

		$persisted = $this->require_campaign( $snapshot->get_id() );

		if ( ! $this->needs_sync( $persisted, $snapshot ) ) {
			return;
		}

		$this->assert_supported_currency( $persisted, $snapshot );

		$synchronized_campaign = $this->update_campaign( $snapshot );

		$this->publish_synchronized_event( $synchronized_campaign->get_id() );
	}

	/**
	 * Returns the existing campaign required for synchronization.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws SyncCampaignFromSnapshotException When campaign lookup fails.
	 */
	private function require_campaign( EntityId $campaign_id ): Campaign {

		try {
			$campaign = $this->campaigns->find_by_id( $campaign_id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new SyncCampaignFromSnapshotException(
				stage: UseCaseFailureStage::Precondition,
				reason: SyncCampaignFromSnapshotPreconditionReason::CampaignLookupFailed,
				message: sprintf( 'Failed to retrieve campaign "%s".', (string) $campaign_id->get_value() ),
				previous: $e,
			);
		}

		if ( $campaign === null ) {
			throw $this->new_campaign_not_found_exception(
				$campaign_id,
				UseCaseFailureStage::Precondition,
				SyncCampaignFromSnapshotPreconditionReason::CampaignNotFound,
			);
		}

		return $campaign;
	}

	/**
	 * Checks whether the persisted campaign already matches the snapshot state.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $persisted Persisted campaign snapshot.
	 * @param Campaign $snapshot Authoritative campaign snapshot.
	 *
	 * @return bool True when the persisted campaign already matches the snapshot state.
	 */
	private function needs_sync( Campaign $persisted, Campaign $snapshot ): bool {

		return $persisted->get_title() !== $snapshot->get_title()
			|| $persisted->accepts_donations() !== $snapshot->accepts_donations()
			|| ! $persisted->get_target()->equals( $snapshot->get_target() );
	}

	/**
	 * Ensures that synchronization does not attempt to change campaign currency.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $persisted Persisted campaign snapshot.
	 * @param Campaign $snapshot Authoritative campaign snapshot.
	 *
	 * @throws SyncCampaignFromSnapshotException When currency differs.
	 */
	private function assert_supported_currency( Campaign $persisted, Campaign $snapshot ): void {

		if ( $persisted->get_target()->get_currency()->equals( $snapshot->get_target()->get_currency() ) ) {
			return;
		}

		throw new SyncCampaignFromSnapshotException(
			stage: UseCaseFailureStage::Precondition,
			reason: SyncCampaignFromSnapshotPreconditionReason::CurrencyChangeRejected,
			message: sprintf(
				'Cannot sync campaign "%s": currency change is not supported.',
				(string) $persisted->get_id()->get_value(),
			),
		);
	}

	/**
	 * Persists the synchronized campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $snapshot Authoritative campaign snapshot.
	 *
	 * @return Campaign Persisted synchronized campaign.
	 *
	 * @throws SyncCampaignFromSnapshotException When persistence fails.
	 */
	private function update_campaign( Campaign $snapshot ): Campaign {

		try {
			return $this->campaigns->update( $snapshot );
		} catch ( CampaignNotFoundExceptionInterface $e ) {
			throw $this->new_campaign_not_found_exception(
				$snapshot->get_id(),
				UseCaseFailureStage::Persistence,
				null,
				$e,
			);
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new SyncCampaignFromSnapshotException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to sync campaign "%s".',
					(string) $snapshot->get_id()->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Publishes the campaign synchronized event.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @throws SyncCampaignFromSnapshotException When event publishing fails.
	 */
	private function publish_synchronized_event( EntityId $campaign_id ): void {

		try {
			$this->event_bus->publish( new CampaignSynchronizedEvent( $campaign_id ) );
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new SyncCampaignFromSnapshotException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Campaign "%s" was synchronized, but publishing the synchronized event failed.',
					(string) $campaign_id->get_value(),
				),
				previous: $e,
			);
		}
	}

	/**
	 * Creates the campaign-not-found synchronization exception.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param UseCaseFailureStage $stage Failure stage.
	 * @param SyncCampaignFromSnapshotPreconditionReason|null $reason Failure reason, if available.
	 * @param CampaignNotFoundExceptionInterface|null $previous Previous exception.
	 *
	 * @return SyncCampaignFromSnapshotNotFoundException Campaign-not-found exception.
	 */
	private function new_campaign_not_found_exception(
		EntityId $campaign_id,
		UseCaseFailureStage $stage,
		?SyncCampaignFromSnapshotPreconditionReason $reason,
		?CampaignNotFoundExceptionInterface $previous = null,
	): SyncCampaignFromSnapshotNotFoundException {

		return new SyncCampaignFromSnapshotNotFoundException(
			(string) $campaign_id->get_value(),
			$stage,
			$previous,
			$reason,
		);
	}
}
