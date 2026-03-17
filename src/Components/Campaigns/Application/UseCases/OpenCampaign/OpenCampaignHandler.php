<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignOpenedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles opening an existing campaign for donations.
 *
 * @since 0.1.0
 */
final readonly class OpenCampaignHandler extends AbstractCampaignMutationHandler {

	/**
	 * Creates the open-campaign exception used when the campaign disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return OpenCampaignException Concrete open-campaign exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $campaign_id,
		Throwable $previous,
	): OpenCampaignException {

		return new OpenCampaignNotFoundException(
			(string) $campaign_id->get_value(),
			$previous,
		);
	}

	/**
	 * Creates the open-campaign exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param CampaignMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return OpenCampaignException Concrete open-campaign exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?CampaignMutationPreconditionReason $reason = null,
	): OpenCampaignException {

		return new OpenCampaignException( stage: $stage, message: $message, previous: $previous, reason: $reason );
	}

	/**
	 * Opens an existing campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id ): Campaign {

		$mutation = CampaignMutation::Open;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$opened_campaign = $campaign->open();
		} catch ( CampaignDomainException $e ) {
			$this->reject_mutation( $campaign_id, $mutation, $e );
		}

		return $this->persist_campaign(
			$opened_campaign,
			$mutation,
			new CampaignOpenedEvent( $opened_campaign->get_id() ),
		);
	}
}
