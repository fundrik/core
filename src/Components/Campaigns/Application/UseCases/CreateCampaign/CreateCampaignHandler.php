<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Ports\EventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Handles creating a new campaign.
 *
 * @since 0.1.0
 */
final readonly class CreateCampaignHandler implements CreateCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Persists campaign entities in storage.
	 * @param CreateCampaignLogger $logger Logs the create operation and outcomes.
	 * @param EventBusPort $event_bus Publishes application events to subscribed listeners.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private CreateCampaignLogger $logger,
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
	 * @throws CampaignRepositoryExceptionInterface When the repository insert fails.
	 */
	public function handle( Campaign $campaign ): Campaign {

		// @infection-ignore-all
		$this->logger->log_create_started( $campaign->get_id() );

		$inserted = $this->insert_or_fail( $campaign );
		$this->publish_created_event_or_log( $inserted->get_entity_id() );

		// @infection-ignore-all
		$this->logger->log_create_succeeded( $inserted->get_id() );

		return $inserted;
	}

	/**
	 * Executes repository insert and rethrows repository errors.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to insert.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 */
	private function insert_or_fail( Campaign $campaign ): Campaign {

		try {
			return $this->repository->insert( $campaign );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_create_failed_repository( $campaign->get_id(), $e );
			throw $e;
		}
	}

	/**
	 * Publishes created event and logs publication errors without throwing.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $entity_id The campaign entity ID to include in the event.
	 */
	private function publish_created_event_or_log( EntityId $entity_id ): void {

		try {
			$this->event_bus->publish( new CampaignCreatedEvent( $entity_id ) );
		} catch ( Throwable $e ) {
			$this->logger->log_publish_created_event_failed( $entity_id->get_value(), $e );
		}
	}
}
