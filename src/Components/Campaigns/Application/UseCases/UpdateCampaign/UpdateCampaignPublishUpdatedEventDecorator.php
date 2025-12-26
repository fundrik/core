<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Publishes CampaignUpdatedEvent after updating the campaign.
 *
 * @since 0.1.0
 */
final readonly class UpdateCampaignPublishUpdatedEventDecorator implements UpdateCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param UpdateCampaignUseCase $inner Delegates the update operation to the next use case in the chain.
	 * @param ApplicationEventBusPort $event_bus Publishes the application event.
	 */
	public function __construct(
		private UpdateCampaignUseCase $inner,
		private ApplicationEventBusPort $event_bus,
	) {}

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
	 * @throws ApplicationEventBusExceptionInterface When publishing the campaign updated event fails.
	 */
	public function handle( Campaign $campaign ): Campaign {

		$updated_campaign = $this->inner->handle( $campaign );

		$this->event_bus->publish(
			new CampaignUpdatedEvent( $updated_campaign->get_entity_id() ),
		);

		return $updated_campaign;
	}
}
