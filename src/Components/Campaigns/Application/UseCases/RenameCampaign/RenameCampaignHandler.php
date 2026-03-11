<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignRenamedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles renaming an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class RenameCampaignHandler extends AbstractCampaignMutationHandler implements RenameCampaignUseCase {

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
