<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns;

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
	 * @return array<int, Campaign> The list of campaigns.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws FindAllCampaignsException When retrieving campaigns fails.
	 */
	public function handle(): array;
}
