<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignActivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles activating an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class ActivateCampaignHandler extends AbstractCampaignMutationHandler {

	/**
	 * Creates the activate-campaign exception used when the campaign disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return ActivateCampaignException Concrete activate-campaign exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $campaign_id,
		Throwable $previous,
	): ActivateCampaignException {

		return new ActivateCampaignNotFoundException(
			(string) $campaign_id->get_value(),
			$previous,
		);
	}

	/**
	 * Creates the activate-campaign exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param CampaignMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return ActivateCampaignException Concrete activate-campaign exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?CampaignMutationPreconditionReason $reason = null,
	): ActivateCampaignException {

		return new ActivateCampaignException( stage: $stage, message: $message, previous: $previous, reason: $reason );
	}

	/**
	 * Activates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id ): Campaign {

		$mutation = CampaignMutation::Activate;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$activated_campaign = $campaign->activate();
		} catch ( CampaignDomainException $e ) {
			$this->reject_mutation( $campaign_id, $mutation, $e );
		}

		return $this->persist_campaign(
			$activated_campaign,
			$mutation,
			new CampaignActivatedEvent( $activated_campaign->get_id() ),
		);
	}
}
