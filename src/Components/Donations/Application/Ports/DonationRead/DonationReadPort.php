<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\DonationRead;

use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides the outbound port for reading donations.
 *
 * @since 0.1.0
 */
interface DonationReadPort {

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID to retrieve.
	 *
	 * @return Donation|null Donation read model if found, null otherwise.
	 *
	 * @throws DonationReadExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Donation;
}
