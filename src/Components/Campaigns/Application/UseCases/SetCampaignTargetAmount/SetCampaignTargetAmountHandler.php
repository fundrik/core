<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SetCampaignTargetAmount;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignTargetChangedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles setting a campaign target amount.
 *
 * @since 0.1.0
 */
final readonly class SetCampaignTargetAmountHandler extends AbstractCampaignMutationHandler {

	/**
	 * Creates the set-target-amount exception used when the campaign disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return SetCampaignTargetAmountException Concrete target-change exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $campaign_id,
		Throwable $previous,
	): SetCampaignTargetAmountException {

		return new SetCampaignTargetAmountNotFoundException(
			(string) $campaign_id->get_value(),
			$previous,
		);
	}

	/**
	 * Creates the set-target-amount exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param CampaignMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return SetCampaignTargetAmountException Concrete target-change exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?CampaignMutationPreconditionReason $reason = null,
	): SetCampaignTargetAmountException {

		return new SetCampaignTargetAmountException( $stage, $message, $previous, $reason );
	}

	/**
	 * Sets the target amount for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param int|CampaignTarget $amount Desired target amount or target value object.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id, int|CampaignTarget $amount ): Campaign {

		$mutation = CampaignMutation::SetTargetAmount;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$updated_campaign = $campaign->set_target_amount( $amount );
		} catch ( CampaignDomainException $e ) {
			$this->reject_mutation( $campaign_id, $mutation, $e );
		}

		return $this->persist_campaign(
			$updated_campaign,
			$mutation,
			new CampaignTargetChangedEvent( $updated_campaign->get_id() ),
		);
	}
}
