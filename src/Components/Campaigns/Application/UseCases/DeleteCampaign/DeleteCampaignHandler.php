<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles deleting a campaign by its ID.
 *
 * @since 0.1.0
 */
final readonly class DeleteCampaignHandler implements DeleteCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Removes campaigns from storage.
	 * @param ApplicationEventBusPort $event_bus Publishes campaign events.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When deleting the campaign fails.
	 * @throws ApplicationEventBusExceptionInterface When publishing the campaign deleted event fails.
	 */
	public function handle( EntityId $campaign_id ): void {

		$this->repository->delete( $campaign_id );

		$this->event_bus->publish(
			new CampaignDeletedEvent( $campaign_id ),
		);
	}
}
