<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignActivatedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;

// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
/**
 * Handles activating an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class ActivateCampaignHandler extends AbstractCampaignMutationHandler {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<ActivateCampaignException>
	 */
	protected function mutation_exception_class(): string {

		return ActivateCampaignException::class;
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
// phpcs:enable
