<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;

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
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CampaignRepositoryExceptionInterface When updating the campaign fails.
	 * @throws ApplicationEventBusExceptionInterface When publishing the campaign updated event fails
	 *                                    (if the implementation publishes events).
	 */
	public function handle( Campaign $campaign ): Campaign;
}
