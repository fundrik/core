<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Events;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Marks donation-related application-level events.
 *
 * @since 0.1.0
 */
interface DonationApplicationEventInterface extends ApplicationEventInterface {

	/**
	 * Returns the donation ID associated with the event.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Donation ID.
	 */
	public function get_donation_id(): EntityId;
}
