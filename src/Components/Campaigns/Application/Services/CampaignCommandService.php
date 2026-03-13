<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign\ActivateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign\ActivateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign\CloseCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign\CreateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign\DeactivateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign\DeactivateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign\DeleteCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign\OpenCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign\RenameCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign\SaveCampaignHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SetCampaignTargetAmount\SetCampaignTargetAmountException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\SetCampaignTargetAmount\SetCampaignTargetAmountHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign\UpdateCampaignException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign\UpdateCampaignHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;

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
	 * @param SaveCampaignHandler $save_campaign Saves campaigns using insert-or-update semantics.
	 * @param UpdateCampaignHandler $update_campaign Persists existing campaign snapshots.
	 * @param RenameCampaignHandler $rename_campaign Renames campaigns.
	 * @param ActivateCampaignHandler $activate_campaign Activates campaigns.
	 * @param DeactivateCampaignHandler $deactivate_campaign Deactivates campaigns.
	 * @param OpenCampaignHandler $open_campaign Opens campaigns for donations.
	 * @param CloseCampaignHandler $close_campaign Closes campaigns for donations.
	 * @param SetCampaignTargetAmountHandler $set_target_amount Changes campaign target amounts.
	 * @param DeleteCampaignHandler $delete_campaign Deletes campaigns.
	 */
	public function __construct(
		private CreateCampaignHandler $create_campaign,
		private SaveCampaignHandler $save_campaign,
		private UpdateCampaignHandler $update_campaign,
		private RenameCampaignHandler $rename_campaign,
		private ActivateCampaignHandler $activate_campaign,
		private DeactivateCampaignHandler $deactivate_campaign,
		private OpenCampaignHandler $open_campaign,
		private CloseCampaignHandler $close_campaign,
		private SetCampaignTargetAmountHandler $set_target_amount,
		private DeleteCampaignHandler $delete_campaign,
	) {}
	// phpcs:enable

	/**
	 * Creates a new campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to create.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CreateCampaignException When campaign creation fails.
	 */
	public function create( Campaign $campaign ): Campaign {

		return $this->create_campaign->handle( $campaign );
	}

	/**
	 * Saves the given campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @return CampaignRepositorySaveOutcome The repository save outcome.
	 *
	 * @throws SaveCampaignException When campaign save fails.
	 */
	public function save( Campaign $campaign ): CampaignRepositorySaveOutcome {

		return $this->save_campaign->handle( $campaign );
	}

	/**
	 * Updates an existing campaign snapshot.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws UpdateCampaignException When campaign update fails.
	 */
	public function update( Campaign $campaign ): Campaign {

		return $this->update_campaign->handle( $campaign );
	}

	/**
	 * Renames an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param string|CampaignTitle $new_title New campaign title.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws RenameCampaignException When renaming fails.
	 */
	public function rename( EntityId $campaign_id, string|CampaignTitle $new_title ): Campaign {

		return $this->rename_campaign->handle( $campaign_id, $new_title );
	}

	/**
	 * Activates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws ActivateCampaignException When activation fails.
	 */
	public function activate( EntityId $campaign_id ): Campaign {

		return $this->activate_campaign->handle( $campaign_id );
	}

	/**
	 * Deactivates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws DeactivateCampaignException When deactivation fails.
	 */
	public function deactivate( EntityId $campaign_id ): Campaign {

		return $this->deactivate_campaign->handle( $campaign_id );
	}

	/**
	 * Opens an existing campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws OpenCampaignException When opening fails.
	 */
	public function open( EntityId $campaign_id ): Campaign {

		return $this->open_campaign->handle( $campaign_id );
	}

	/**
	 * Closes an existing campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CloseCampaignException When closing fails.
	 */
	public function close( EntityId $campaign_id ): Campaign {

		return $this->close_campaign->handle( $campaign_id );
	}

	/**
	 * Sets the target amount for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param int|CampaignTarget $amount Desired target amount or target value object.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws SetCampaignTargetAmountException When changing the target amount fails.
	 */
	public function set_target_amount( EntityId $campaign_id, int|CampaignTarget $amount ): Campaign {

		return $this->set_target_amount->handle( $campaign_id, $amount );
	}

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID to delete.
	 *
	 * @throws DeleteCampaignException When deletion fails.
	 */
	public function delete( EntityId $campaign_id ): void {

		$this->delete_campaign->handle( $campaign_id );
	}
}
