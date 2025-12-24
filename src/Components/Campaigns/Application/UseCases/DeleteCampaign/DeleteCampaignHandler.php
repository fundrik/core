<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Ports\EventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

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
	 * @param CampaignRepositoryPort $repository Removes campaign entities from storage.
	 * @param DeleteCampaignLogger $logger Logs the delete operation and outcomes.
	 * @param EventBusPort $event_bus Publishes application events to subscribed listeners.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private DeleteCampaignLogger $logger,
		private EventBusPort $event_bus,
	) {}

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository delete fails.
	 */
	public function handle( EntityId $id ): void {

		$id_value = $id->get_value();

		// @infection-ignore-all
		$this->logger->log_delete_started( $id_value );

		$this->delete_or_fail( $id );
		$this->publish_deleted_event_or_log( $id );

		// @infection-ignore-all
		$this->logger->log_delete_succeeded( $id_value );
	}

	/**
	 * Executes repository delete and rethrows repository errors.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID to delete.
	 */
	private function delete_or_fail( EntityId $id ): void {

		try {
			$this->repository->delete( $id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_delete_failed_repository( $id->get_value(), $e );
			throw $e;
		}
	}

	/**
	 * Publishes deleted event and logs publication errors without throwing.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID included in the deleted event.
	 */
	private function publish_deleted_event_or_log( EntityId $id ): void {

		try {
			$this->event_bus->publish( new CampaignDeletedEvent( $id ) );
		} catch ( Throwable $e ) {

			$this->logger->log_publish_deleted_event_failed( $id->get_value(), $e );
		}
	}
}
