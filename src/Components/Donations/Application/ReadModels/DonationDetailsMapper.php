<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\ReadModels;

use Fundrik\Core\Components\Donations\Domain\Donation;

/**
 * Maps domain donation snapshots to public donation details.
 *
 * @since 0.1.0
 */
final readonly class DonationDetailsMapper {

	/**
	 * Maps a domain donation snapshot to public donation details.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation Domain donation snapshot.
	 *
	 * @return DonationDetails Public donation details.
	 */
	public function map( Donation $donation ): DonationDetails {

		return new DonationDetails(
			id: $donation->get_id()->get_value(),
			campaign_id: $donation->get_campaign_id()->get_value(),
			amount: $donation->get_money()->get_amount()->get_value(),
			currency_code: $donation->get_money()->get_currency()->get_code(),
			status: $donation->get_status()->value,
		);
	}
}
