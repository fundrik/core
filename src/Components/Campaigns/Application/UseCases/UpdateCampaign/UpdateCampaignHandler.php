<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles updating an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class UpdateCampaignHandler implements UpdateCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Persists campaign entities in storage.
	 * @param UpdateCampaignLogger $logger Logs the update operation and outcomes.
	 * @param EventBusPort $event_bus Publishes application events to subscribed listeners.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private UpdateCampaignLogger $logger,
		private EventBusPort $event_bus,
	) {}

	/**
	 * Updates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository update fails.
	 */
	public function handle( Campaign $campaign ): void {

		$campaign_id = $campaign->get_id();
		$campaign_entity_id = $campaign->get_entity_id();

		// @infection-ignore-all
		$this->logger->log_update_started( $campaign_id );

		$this->update_or_fail( $campaign );
		$this->publish_updated_event_or_log( $campaign_entity_id );

		// @infection-ignore-all
		$this->logger->log_update_succeeded( $campaign_id );
	}

	/**
	 * Executes repository update and rethrows repository errors.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 */
	private function update_or_fail( Campaign $campaign ): void {

		try {
			$this->repository->update( $campaign );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_update_failed_repository( $campaign->get_id(), $e );
			throw $e;
		}
	}

	/**
	 * Publishes updated event and logs publication errors without throwing.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $entity_id The campaign entity ID to include in the event.
	 */
	private function publish_updated_event_or_log( EntityId $entity_id ): void {

		try {
			$this->event_bus->publish( new CampaignUpdatedEvent( $entity_id ) );
		} catch ( Throwable $e ) {
			$this->logger->log_publish_updated_event_failed( $entity_id->get_value(), $e );
		}
	}
}
