<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignOpenedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles opening an existing campaign for donations.
 *
 * @since 0.1.0
 */
final readonly class OpenCampaignHandler extends AbstractCampaignMutationHandler implements OpenCampaignUseCase {

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
