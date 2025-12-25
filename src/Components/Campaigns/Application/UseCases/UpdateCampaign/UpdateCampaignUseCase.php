<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Provides methods for updating an existing campaign.
 *
 * @since 0.1.0
 */
interface UpdateCampaignUseCase {

	/**
	 * Updates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository update fails.
	 */
	public function handle( Campaign $campaign ): Campaign;
}
