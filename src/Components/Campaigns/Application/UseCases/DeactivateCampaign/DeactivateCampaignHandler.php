<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeactivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;

// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
/**
 * Handles deactivating an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class DeactivateCampaignHandler extends AbstractCampaignMutationHandler {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<DeactivateCampaignException>
	 */
	protected function mutation_exception_class(): string {

		return DeactivateCampaignException::class;
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
// phpcs:enable
