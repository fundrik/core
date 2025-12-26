<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\EventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\EventBusPort;

/**
 * Publishes CampaignCreatedEvent after creating the campaign.
 *
 * @since 0.1.0
 */
final readonly class CreateCampaignPublishCreatedEventDecorator implements CreateCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateCampaignUseCase $inner Delegates the create operation to the next use case in the chain.
	 * @param EventBusPort $event_bus Publishes the application event.
	 */
	public function __construct(
		private CreateCampaignUseCase $inner,
		private EventBusPort $event_bus,
	) {}

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
	 * @throws EventBusExceptionInterface When publishing the campaign created event fails.
	 */
	public function handle( Campaign $campaign ): Campaign {

		$created_campaign = $this->inner->handle( $campaign );

		$this->event_bus->publish(
			new CampaignCreatedEvent( $created_campaign->get_entity_id() ),
		);

		return $created_campaign;
	}
}
