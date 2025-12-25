<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Handles retrieving all campaigns.
 *
 * @since 0.1.0
 */
final readonly class FindAllCampaignsHandler implements FindAllCampaignsUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Retrieves campaign entities from storage.
	 * @param FindAllCampaignsLogger $logger Logs the lookup operation and outcomes.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private FindAllCampaignsLogger $logger,
	) {}

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> The list of campaign entities.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws CampaignRepositoryExceptionInterface Thrown when the repository lookup fails.
	 */
	public function handle(): array {

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
