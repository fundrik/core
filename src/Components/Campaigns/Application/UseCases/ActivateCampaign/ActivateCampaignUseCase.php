<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for activating a campaign.
 *
 * @since 0.1.0
 */
interface ActivateCampaignUseCase {

	/**
	 * Activates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws ActivateCampaignException When activation fails.
	 */
	public function handle( EntityId $campaign_id ): Campaign;
}
