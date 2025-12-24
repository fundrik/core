<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns;

// phpcs:disable SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
// phpcs:enable

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
