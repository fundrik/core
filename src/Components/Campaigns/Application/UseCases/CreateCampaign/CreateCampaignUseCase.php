<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\EventBusExceptionInterface;

/**
 * Provides methods for creating a new campaign.
 *
 * @since 0.1.0
 */
interface CreateCampaignUseCase {

	/**
	 * Creates a new campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to create.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CampaignRepositoryExceptionInterface When creating the campaign fails.
	 * @throws EventBusExceptionInterface When publishing the campaign created event fails
	 *                                    (if the implementation publishes events).
	 */
	public function handle( Campaign $campaign ): Campaign;
}
