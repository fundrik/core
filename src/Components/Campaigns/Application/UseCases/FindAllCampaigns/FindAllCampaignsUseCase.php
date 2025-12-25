<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Provides methods for retrieving all campaigns.
 *
 * @since 0.1.0
 */
interface FindAllCampaignsUseCase {

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> The list of campaign entities.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws CampaignRepositoryExceptionInterface Thrown when the repository lookup fails.
	 */
	public function handle(): array;
}
