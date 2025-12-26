<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Publishes CampaignCreatedEvent/CampaignUpdatedEvent after saving the campaign.
 *
 * @since 0.1.0
 */
final readonly class SaveCampaignPublishCreatedOrUpdatedEventDecorator implements SaveCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param SaveCampaignUseCase $inner Delegates the save operation to the next use case in the chain.
	 * @param ApplicationEventBusPort $event_bus Publishes the application event.
	 */
	public function __construct(
		private SaveCampaignUseCase $inner,
		private ApplicationEventBusPort $event_bus,
	) {}

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
	 * @throws ApplicationEventBusExceptionInterface When publishing the campaign created/updated event fails.
	 */
	public function handle( Campaign $campaign ): CampaignRepositorySaveOutcome {

		$outcome = $this->inner->handle( $campaign );
		$entity_id = $outcome->campaign->get_entity_id();

		$event = match ( $outcome->result ) {
			CampaignRepositorySaveResult::Inserted => new CampaignCreatedEvent( $entity_id ),
			CampaignRepositorySaveResult::Updated => new CampaignUpdatedEvent( $entity_id ),
		};

		$this->event_bus->publish( $event );

		return $outcome;
	}
}
