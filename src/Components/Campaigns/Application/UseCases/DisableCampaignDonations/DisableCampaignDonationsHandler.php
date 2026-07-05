<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDonationsDisabledEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles disabling donation acceptance for an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class DisableCampaignDonationsHandler extends AbstractCampaignMutationHandler {

	/**
	 * Creates the disable-donations exception used when the campaign disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return DisableCampaignDonationsException Concrete disable-donations exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $campaign_id,
		Throwable $previous,
	): DisableCampaignDonationsException {

		return new DisableCampaignDonationsNotFoundException(
			$campaign_id,
			$previous,
		);
	}

	/**
	 * Creates the disable-donations exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param CampaignMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return DisableCampaignDonationsException Concrete disable-donations exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?CampaignMutationPreconditionReason $reason = null,
	): DisableCampaignDonationsException {

		return new DisableCampaignDonationsException( $stage, $message, $previous, $reason );
	}

	/**
	 * Disables accepting donations for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id ): Campaign {

		$mutation = CampaignMutation::DisableDonations;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$updated_campaign = $campaign->disable_donations();
		} catch ( CampaignDomainException $e ) {
			$this->reject_mutation( $campaign_id, $mutation, $e );
		}

		return $this->persist_campaign(
			$updated_campaign,
			$mutation,
			new CampaignDonationsDisabledEvent( $updated_campaign->get_id() ),
		);
	}
}
