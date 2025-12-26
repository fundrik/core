<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for deleting a campaign by its ID.
 *
 * @since 0.1.0
 */
interface DeleteCampaignUseCase {

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When deleting the campaign fails.
	 * @throws ApplicationEventBusExceptionInterface When publishing the campaign deleted event fails
	 *                                    (if the implementation publishes events).
	 */
	public function handle( EntityId $campaign_id ): void;
}
