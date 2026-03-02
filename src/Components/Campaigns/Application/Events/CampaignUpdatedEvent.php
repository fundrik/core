<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Signals that a campaign has been updated.
 *
 * @since 0.1.0
 */
final readonly class CampaignUpdatedEvent implements CampaignApplicationEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID.
	 */
	public function __construct(
		private EntityId $campaign_id,
	) {}

	/**
	 * Returns the campaign ID associated with this event.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId The campaign ID.
	 */
	public function get_campaign_id(): EntityId {

		return $this->campaign_id;
	}
}
