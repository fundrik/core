<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\EventBusExceptionInterface;

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
	 * @return CampaignRepositorySaveOutcome The repository save outcome.
	 *
	 * @throws CampaignRepositoryExceptionInterface When saving the campaign fails.
	 * @throws EventBusExceptionInterface When publishing the campaign created/updated event fails
	 *                                    (if the implementation publishes events).
	 */
	public function handle( Campaign $campaign ): CampaignRepositorySaveOutcome;
}
