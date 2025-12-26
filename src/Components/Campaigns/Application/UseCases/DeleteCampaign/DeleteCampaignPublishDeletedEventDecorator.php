<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Publishes CampaignDeletedEvent after deleting the campaign.
 *
 * @since 0.1.0
 */
final readonly class DeleteCampaignPublishDeletedEventDecorator implements DeleteCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DeleteCampaignUseCase $inner Delegates the delete operation to the next use case in the chain.
	 * @param ApplicationEventBusPort $event_bus Publishes the application event.
	 */
	public function __construct(
		private DeleteCampaignUseCase $inner,
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

		$this->inner->handle( $campaign_id );

		$this->event_bus->publish(
			new CampaignDeletedEvent( $campaign_id ),
		);
	}
}
