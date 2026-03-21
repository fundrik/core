<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead;

use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides the outbound port for reading donation details.
 *
 * @since 0.1.0
 */
interface DonationDetailsReadPort {

	/**
	 * Retrieves donation details by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID to retrieve.
	 *
	 * @return DonationDetails|null Donation details if found, null otherwise.
	 *
	 * @throws DonationDetailsReadExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?DonationDetails;
}
