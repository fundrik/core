<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving a campaign by its ID.
 *
 * @since 0.1.0
 */
final readonly class FindCampaignByIdHandler implements FindCampaignByIdUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Retrieves campaign entities from storage.
	 * @param FindCampaignByIdLogger $logger Logs the lookup operation and outcomes.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private FindCampaignByIdLogger $logger,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return Campaign|null The campaign if found, null otherwise.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository lookup fails.
	 */
	public function handle( EntityId $id ): ?Campaign {

		// @infection-ignore-all
		$this->logger->log_find_by_id_started( $id->get_value() );

		try {
			$campaign = $this->repository->find_by_id( $id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_find_by_id_failed_repository( $id->get_value(), $e );
			throw $e;
		}

		// @infection-ignore-all
		if ( $campaign === null ) {
			$this->logger->log_find_by_id_not_found( $id->get_value() );
		} else {
			$this->logger->log_find_by_id_succeeded( $id->get_value() );
		}

		return $campaign;
	}
}
