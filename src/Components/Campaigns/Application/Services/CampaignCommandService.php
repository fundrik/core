<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Commands\CreateCampaignCommand;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetailsMapper;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget\ChangeCampaignTargetHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
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
	 * @param CampaignDetailsMapper $campaign_details_mapper Maps domain campaigns to public details.
	 * @param RenameCampaignHandler $rename_campaign Renames campaigns.
	 * @param OpenCampaignHandler $open_campaign Opens campaigns for donations.
	 * @param CloseCampaignHandler $close_campaign Closes campaigns for donations.
	 * @param ChangeCampaignTargetHandler $change_target Changes campaign targets.
	 * @param DeleteCampaignHandler $delete_campaign Deletes campaigns.
	 */
	public function __construct(
		private CreateCampaignHandler $create_campaign,
		private CampaignFactory $campaign_factory,
		private CampaignDetailsMapper $campaign_details_mapper,
		private RenameCampaignHandler $rename_campaign,
		private OpenCampaignHandler $open_campaign,
		private CloseCampaignHandler $close_campaign,
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
	 * @return CampaignDetails The persisted campaign details.
	 *
	 * @throws CreateCampaignException When campaign creation fails.
	 */
	public function create( CreateCampaignCommand $command ): CampaignDetails {

		try {
			$campaign = $this->campaign_factory->create_new_from_primitives(
				id: $command->get_id(),
				title: $command->get_title(),
				is_open: $command->can_receive_donations(),
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

		return $this->campaign_details_mapper->map( $this->create_campaign->handle( $campaign ) );
	}

	/**
	 * Renames an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 * @param string $new_title New campaign title.
	 *
	 * @return CampaignDetails The persisted campaign details.
	 *
	 * @throws RenameCampaignException When renaming fails.
	 */
	public function rename( int|string|EntityId $campaign_id, string $new_title ): CampaignDetails {

		try {
			$entity_id = EntityId::create( $campaign_id );
			$title = CampaignTitle::create( $new_title );
		} catch ( InvalidEntityIdException $e ) {
			throw new RenameCampaignException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		} catch ( InvalidCampaignTitleException $e ) {
			throw new RenameCampaignException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->campaign_details_mapper->map(
			$this->rename_campaign->handle( $entity_id, $title ),
		);
	}

	/**
	 * Opens an existing campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 *
	 * @return CampaignDetails The persisted campaign details.
	 *
	 * @throws OpenCampaignException When opening fails.
	 */
	public function open( int|string|EntityId $campaign_id ): CampaignDetails {

		try {
			$entity_id = EntityId::create( $campaign_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new OpenCampaignException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->campaign_details_mapper->map(
			$this->open_campaign->handle( $entity_id ),
		);
	}

	/**
	 * Closes an existing campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 *
	 * @return CampaignDetails The persisted campaign details.
	 *
	 * @throws CloseCampaignException When closing fails.
	 */
	public function close( int|string|EntityId $campaign_id ): CampaignDetails {

		try {
			$entity_id = EntityId::create( $campaign_id );
		} catch ( InvalidEntityIdException $e ) {
			throw new CloseCampaignException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->campaign_details_mapper->map(
			$this->close_campaign->handle( $entity_id ),
		);
	}

	/**
	 * Changes the target amount for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 * @param int|null $target_amount Desired target amount, or null to clear it.
	 *
	 * @return CampaignDetails The persisted campaign details.
	 *
	 * @throws ChangeCampaignTargetException When changing the target amount fails.
	 */
	public function change_target_amount( int|string|EntityId $campaign_id, ?int $target_amount ): CampaignDetails {

		try {
			$entity_id = EntityId::create( $campaign_id );
			$amount = $target_amount === null ? null : Amount::create( $target_amount );
		} catch ( InvalidEntityIdException $e ) {
			throw new ChangeCampaignTargetException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		} catch ( InvalidAmountException $e ) {
			throw new ChangeCampaignTargetException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}

		return $this->campaign_details_mapper->map(
			$this->change_target->handle( $entity_id, $amount ),
		);
	}

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id The campaign ID to delete.
	 *
	 * @throws DeleteCampaignException When deletion fails.
	 */
	public function delete( int|string|EntityId $campaign_id ): void {

		try {
			$this->delete_campaign->handle( EntityId::create( $campaign_id ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new DeleteCampaignException(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
			);
		}
	}
}
