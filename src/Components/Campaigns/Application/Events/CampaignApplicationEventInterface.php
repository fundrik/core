<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Marks campaign-related application-level events.
 *
 * @since 0.1.0
 */
interface CampaignApplicationEventInterface extends ApplicationEventInterface {

	/**
	 * Returns the campaign ID associated with the event.
	 *
	 * @since 0.1.0
	 *
	 * @return EntityId Campaign ID.
	 */
	public function get_campaign_id(): EntityId;
}
