<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for renaming a campaign.
 *
 * @since 0.1.0
 */
interface RenameCampaignUseCase {

	/**
	 * Renames an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param string|CampaignTitle $new_title New campaign title.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws RenameCampaignException When renaming fails.
	 */
	public function handle( EntityId $campaign_id, string|CampaignTitle $new_title ): Campaign;
}
