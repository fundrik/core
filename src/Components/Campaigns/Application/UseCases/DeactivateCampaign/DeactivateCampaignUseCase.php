<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for deactivating a campaign.
 *
 * @since 0.1.0
 */
interface DeactivateCampaignUseCase {

	/**
	 * Deactivates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws CampaignMutationException When deactivation fails.
	 */
	public function handle( EntityId $campaign_id ): Campaign;
}
