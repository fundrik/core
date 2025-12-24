<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Provides methods for saving a campaign.
 *
 * @since 0.1.0
 */
interface SaveCampaignUseCase {

	/**
	 * Saves the given campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository save fails.
	 */
	public function handle( Campaign $campaign ): void;
}
