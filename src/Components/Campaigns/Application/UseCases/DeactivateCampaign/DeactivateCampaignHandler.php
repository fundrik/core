<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeactivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles deactivating an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class DeactivateCampaignHandler extends AbstractCampaignMutationHandler {

	/**
	 * Creates the deactivate-campaign exception used when the campaign disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return DeactivateCampaignException Concrete deactivate-campaign exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $campaign_id,
		Throwable $previous,
	): DeactivateCampaignException {

		return new DeactivateCampaignNotFoundException(
			(string) $campaign_id->get_value(),
			$previous,
		);
	}

	/**
	 * Creates the deactivate-campaign exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param CampaignMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return DeactivateCampaignException Concrete deactivate-campaign exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?CampaignMutationPreconditionReason $reason = null,
	): DeactivateCampaignException {

		return new DeactivateCampaignException(
			stage: $stage,
			message: $message,
			previous: $previous,
			reason: $reason,
		);
	}

	/**
	 * Deactivates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id ): Campaign {

		$mutation = CampaignMutation::Deactivate;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$deactivated_campaign = $campaign->deactivate();
		} catch ( CampaignDomainException $e ) {
			$this->reject_mutation( $campaign_id, $mutation, $e );
		}

		return $this->persist_campaign(
			$deactivated_campaign,
			$mutation,
			new CampaignDeactivatedEvent( $deactivated_campaign->get_id() ),
		);
	}
}
