<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for opening a campaign for donations.
 *
 * @since 0.1.0
 */
interface OpenCampaignUseCase {

	/**
	 * Opens an existing campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws OpenCampaignException When opening fails.
	 */
	public function handle( EntityId $campaign_id ): Campaign;
}
