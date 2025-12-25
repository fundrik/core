<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Events;

use Fundrik\Core\Components\Shared\Application\Events\ApplicationEventInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Signals that a campaign has been created.
 *
 * @since 0.1.0
 */
final readonly class CampaignCreatedEvent implements ApplicationEventInterface {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID.
	 */
	public function __construct(
		public EntityId $campaign_id,
	) {}
}
