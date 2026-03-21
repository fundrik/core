<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Signals that campaign donations have been enabled.
 *
 * @since 0.1.0
 */
final readonly class CampaignDonationsEnabledEvent implements CampaignChangedEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 */
	public function __construct(
		private EntityId $campaign_id,
	) {}

	/**
	 * Returns the campaign ID associated with this event.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Campaign ID.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}
}
