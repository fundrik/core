<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationPreconditionReason;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles renaming an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class RenameCampaignHandler extends AbstractCampaignMutationHandler {

	/**
	 * Creates the rename-campaign exception used when the campaign disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return RenameCampaignException Concrete rename-campaign exception.
	 */
	protected function new_not_found_mutation_exception(
		EntityId $campaign_id,
		Throwable $previous,
	): RenameCampaignException {

		return new RenameCampaignNotFoundException(
			(string) $campaign_id->get_value(),
			$previous,
		);
	}

	/**
	 * Creates the rename-campaign exception exposed by this use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param CampaignMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return RenameCampaignException Concrete rename-campaign exception.
	 */
	protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?CampaignMutationPreconditionReason $reason = null,
	): RenameCampaignException {

		return new RenameCampaignException( $stage, $message, $previous, $reason );
	}

	/**
	 * Renames an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param string|CampaignTitle $new_title New campaign title.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id, string|CampaignTitle $new_title ): Campaign {

		$mutation = CampaignMutation::Rename;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$renamed_campaign = $campaign->rename( $new_title );
		} catch ( CampaignDomainException $e ) {
			$this->reject_mutation( $campaign_id, $mutation, $e );
		}

		return $this->persist_campaign(
			$renamed_campaign,
			$mutation,
			new CampaignRenamedEvent( $renamed_campaign->get_id() ),
		);
	}
}
