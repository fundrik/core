<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\Amount;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles changing a campaign target.
 *
 * @since 0.1.0
 */
final readonly class ChangeCampaignTargetHandler extends AbstractCampaignMutationHandler {

	/**
	 * Creates the change-target exception used when the campaign disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return ChangeCampaignTargetException Concrete target-change exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $campaign_id,
		Throwable $previous,
	): ChangeCampaignTargetException {

		return new ChangeCampaignTargetNotFoundException(
			$campaign_id,
			$previous,
		);
	}

	/**
	 * Creates the change-target exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param CampaignMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return ChangeCampaignTargetException Concrete target-change exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?CampaignMutationPreconditionReason $reason = null,
	): ChangeCampaignTargetException {

		return new ChangeCampaignTargetException( $stage, $message, $previous, $reason );
	}

	/**
	 * Changes the target for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Amount|null $target_amount Desired campaign target amount.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id, ?Amount $target_amount ): Campaign {

		$mutation = CampaignMutation::ChangeTarget;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$updated_campaign = $campaign->change_target_amount( $target_amount );
		} catch ( CampaignDomainException $e ) {
			throw $this->new_mutation_exception(
				stage: UseCaseFailureStage::Precondition,
				message: $e->getMessage(),
				previous: $e,
				reason: CampaignMutationPreconditionReason::CampaignMutationRejected,
			);
		}

		return $this->persist_campaign(
			$updated_campaign,
			$mutation,
			new CampaignTargetChangedEvent( $updated_campaign->get_id() ),
		);
	}
}
