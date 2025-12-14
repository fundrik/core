<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Loggers\CampaignQueryServiceLogger;
use Fundrik\Core\Components\Campaigns\Application\Ports\In\CampaignQueryServicePort;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\Out\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides application-level queries for retrieving campaigns.
 *
 * @since 0.1.0
 */
final readonly class CampaignQueryService implements CampaignQueryServicePort {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Provides access to campaign storage.
	 * @param CampaignQueryServiceLogger $logger Logs application-level operations and outcomes.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private CampaignQueryServiceLogger $logger,
	) {}

	/**
	 * Finds a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return Campaign|null The campaign if found, otherwise null.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository lookup fails.
	 */
	public function find_campaign_by_id( EntityId $id ): ?Campaign {

		// @infection-ignore-all
		$this->logger->log_find_by_id_started( $id->get_value() );

		try {
			$campaign = $this->repository->find_by_id( $id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_find_by_id_failed_repository( $id->get_value(), $e );
			throw $e;
		}

		if ( $campaign === null ) {
			// @infection-ignore-all
			$this->logger->log_find_by_id_not_found( $id->get_value() );
			return null;
		}

		// @infection-ignore-all
		$this->logger->log_find_by_id_succeeded( $id->get_value() );

		return $campaign;
	}

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> The list of campaign entities.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository lookup fails.
	 */
	public function find_all_campaigns(): array {

		// @infection-ignore-all
		$this->logger->log_find_all_started();

		try {
			$campaigns = $this->repository->find_all();
		} catch ( CampaignRepositoryExceptionInterface $e ) {

			$this->logger->log_find_all_failed_repository( $e );
			throw $e;
		}

		// @infection-ignore-all
		$this->logger->log_find_all_succeeded( count( $campaigns ) );

		return $campaigns;
	}
}
