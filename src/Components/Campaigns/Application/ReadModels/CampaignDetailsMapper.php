<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\ReadModels;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Maps domain campaign snapshots to public campaign details.
 *
 * @since 0.1.0
 */
final readonly class CampaignDetailsMapper {

	/**
	 * Maps a domain campaign snapshot to public campaign details.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign Domain campaign snapshot.
	 *
	 * @return CampaignDetails Public campaign details.
	 */
	public function map( Campaign $campaign ): CampaignDetails {

		return new CampaignDetails(
			id: $campaign->get_id()->get_value(),
			title: $campaign->get_title(),
			can_receive_donations: $campaign->can_receive_donations(),
			currency_code: $campaign->get_target()->get_currency()->get_code(),
			target_amount: $campaign->get_target()->get_amount()?->get_value(),
		);
	}
}
