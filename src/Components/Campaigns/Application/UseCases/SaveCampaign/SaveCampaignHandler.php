<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles saving a campaign.
 *
 * Creates a new campaign or updates an existing one depending on repository outcome.
 *
 * @since 0.1.0
 */
final readonly class SaveCampaignHandler implements SaveCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Persists campaign entities in storage.
	 * @param SaveCampaignLogger $logger Logs the save operation and outcomes.
	 * @param EventBusPort $event_bus Publishes application events to subscribed listeners.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private SaveCampaignLogger $logger,
		private EventBusPort $event_bus,
	) {}

	/**
	 * Saves the given campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository save fails.
	 */
	public function handle( Campaign $campaign ): void {

		$campaign_id = $campaign->get_id();
		$campaign_entity_id = $campaign->get_entity_id();

		// @infection-ignore-all
		$this->logger->log_save_started( $campaign_id );

		$result = $this->save_or_fail( $campaign );
		$this->publish_saved_event_or_log( $campaign_entity_id, $result );

		// @infection-ignore-all
		$this->logger->log_save_succeeded( $campaign_id, $result );
	}

	/**
	 * Executes repository save and rethrows repository errors.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @return CampaignRepositorySaveResult The repository outcome (Inserted or Updated).
	 */
	private function save_or_fail( Campaign $campaign ): CampaignRepositorySaveResult {

		try {
			return $this->repository->save( $campaign );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_save_failed_repository( $campaign->get_id(), $e );
			throw $e;
		}
	}

	/**
	 * Publishes created/updated event and logs publication errors without throwing.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $entity_id The campaign entity ID to include in the event.
	 * @param CampaignRepositorySaveResult $result The repository outcome determining which event to publish.
	 */
	private function publish_saved_event_or_log( EntityId $entity_id, CampaignRepositorySaveResult $result ): void {

		$event = match ( $result ) {
			CampaignRepositorySaveResult::Inserted => new CampaignCreatedEvent( $entity_id ),
			CampaignRepositorySaveResult::Updated => new CampaignUpdatedEvent( $entity_id ),
		};

		try {
			$this->event_bus->publish( $event );
		} catch ( Throwable $e ) {

			$this->logger->log_publish_saved_event_failed( $entity_id->get_value(), $e, $result );
		}
	}
}
