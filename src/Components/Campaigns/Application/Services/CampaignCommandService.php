<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Commands\CreateCampaignCommand;
use Fundrik\Core\Components\Campaigns\Application\Commands\SyncCampaignFromSnapshotCommand;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations\DisableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations\EnableCampaignDonationsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SyncCampaignFromSnapshot\SyncCampaignFromSnapshotPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\CampaignFactory;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;

/**
 * Provides the public entry point for campaign write operations.
 *
 * @since 0.1.0
 */
final readonly class CampaignCommandService {

	// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateCampaignHandler $create_campaign Creates new campaigns.
	 * @param CampaignFactory $campaign_factory Creates campaigns from public input.
	 * @param SyncCampaignFromSnapshotHandler $sync_campaign_from_snapshot Synchronizes campaigns from snapshots.
	 * @param RenameCampaignHandler $rename_campaign Renames campaigns.
	 * @param EnableCampaignDonationsHandler $enable_donations Enables accepting donations for campaigns.
	 * @param DisableCampaignDonationsHandler $disable_donations Disables accepting donations for campaigns.
	 * @param ChangeCampaignTargetHandler $change_target Changes campaign targets.
	 * @param DeleteCampaignHandler $delete_campaign Deletes campaigns.
	 */
	public function __construct(
		private CreateCampaignHandler $create_campaign,
		private CampaignFactory $campaign_factory,
		private SyncCampaignFromSnapshotHandler $sync_campaign_from_snapshot,
		private RenameCampaignHandler $rename_campaign,
		private EnableCampaignDonationsHandler $enable_donations,
		private DisableCampaignDonationsHandler $disable_donations,
		private ChangeCampaignTargetHandler $change_target,
		private DeleteCampaignHandler $delete_campaign,
	) {}
	// phpcs:enable

	/**
	 * Creates a new campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateCampaignCommand $command Public campaign creation input.
	 *
	 * @throws CreateCampaignException When campaign creation fails.
	 */
	public function create( CreateCampaignCommand $command ): void {

		try {
			$campaign = $this->campaign_factory->create_new_from_primitives(
				id: $command->get_id(),
				title: $command->get_title(),
				accepts_donations: $command->accepts_donations(),
				currency_code: $command->get_currency_code(),
				target_amount: $command->get_target_amount(),
			);
		} catch ( CampaignFactoryException $e ) {
			throw new CreateCampaignException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->create_campaign->handle( $campaign );
	}

	/**
	 * Synchronizes a campaign from an authoritative snapshot.
	 *
	 * @since 0.1.0
	 *
	 * @param SyncCampaignFromSnapshotCommand $command Public campaign synchronization input.
	 *
	 * @throws SyncCampaignFromSnapshotException When campaign synchronization fails.
	 */
	public function sync_from_snapshot( SyncCampaignFromSnapshotCommand $command ): void {

		try {
			$snapshot = $this->campaign_factory->create_from_primitives(
				id: $command->get_id(),
				version: $command->get_expected_version(),
				title: $command->get_title(),
				accepts_donations: $command->accepts_donations(),
				currency_code: $command->get_currency_code(),
				target_amount: $command->get_target_amount(),
			);
		} catch ( CampaignFactoryException $e ) {
			throw new SyncCampaignFromSnapshotException(
				stage: UseCaseFailureStage::Precondition,
				reason: SyncCampaignFromSnapshotPreconditionReason::SnapshotInvalid,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->sync_campaign_from_snapshot->handle( $snapshot );
	}

	/**
	 * Renames an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 * @param string $new_title New campaign title.
	 *
	 * @throws RenameCampaignException When renaming fails.
	 */
	public function rename( int|string|EntityId $campaign_id, string $new_title ): void {

		$entity_id = $this->require_campaign_id( $campaign_id, RenameCampaignException::class );

		try {
			$title = CampaignTitle::create( $new_title );
		} catch ( InvalidCampaignTitleException $e ) {
			throw new RenameCampaignException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->rename_campaign->handle( $entity_id, $title );
	}

	/**
	 * Enables accepting donations for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 *
	 * @throws EnableCampaignDonationsException When enabling donation acceptance fails.
	 */
	public function enable_donations( int|string|EntityId $campaign_id ): void {

		$entity_id = $this->require_campaign_id( $campaign_id, EnableCampaignDonationsException::class );

		$this->enable_donations->handle( $entity_id );
	}

	/**
	 * Disables accepting donations for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 *
	 * @throws DisableCampaignDonationsException When disabling donation acceptance fails.
	 */
	public function disable_donations( int|string|EntityId $campaign_id ): void {

		$entity_id = $this->require_campaign_id( $campaign_id, DisableCampaignDonationsException::class );

		$this->disable_donations->handle( $entity_id );
	}

	/**
	 * Changes the target amount for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 * @param int|null $target_amount Desired target amount, or null to clear it.
	 *
	 * @throws ChangeCampaignTargetException When changing the target amount fails.
	 */
	public function change_target_amount( int|string|EntityId $campaign_id, ?int $target_amount ): void {

		$entity_id = $this->require_campaign_id( $campaign_id, ChangeCampaignTargetException::class );

		try {
			$amount = $target_amount === null ? null : Amount::create( $target_amount );
		} catch ( InvalidAmountException $e ) {
			throw new ChangeCampaignTargetException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		$this->change_target->handle( $entity_id, $amount );
	}

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID to delete.
	 *
	 * @throws DeleteCampaignException When deletion fails.
	 */
	public function delete( int|string|EntityId $campaign_id ): void {

		$this->delete_campaign->handle(
			$this->require_campaign_id( $campaign_id, DeleteCampaignException::class ),
		);
	}

	/**
	 * Creates a validated campaign ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID input.
	 * @param string $exception_class Exception class to throw on validation failure.
	 *
	 * @return EntityId Campaign ID.
	 *
	 * @throws CreateCampaignException When create-campaign ID validation fails.
	 * @throws RenameCampaignException When rename-campaign ID validation fails.
	 * @throws EnableCampaignDonationsException When enable-donations ID validation fails.
	 * @throws DisableCampaignDonationsException When disable-donations ID validation fails.
	 * @throws ChangeCampaignTargetException When target-change ID validation fails.
	 * @throws DeleteCampaignException When delete-campaign ID validation fails.
	 */
	private function require_campaign_id( int|string|EntityId $campaign_id, string $exception_class ): EntityId {

		try {
			return EntityId::create( $campaign_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new $exception_class(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}
	}
}
