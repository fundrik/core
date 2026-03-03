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
	 * @param CampaignRepositoryPort $repository Retrieves campaigns from storage.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
	) {}

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, Campaign> The list of campaigns.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws FindAllCampaignsException When retrieving campaigns fails.
	 */
	public function handle(): array {

		try {
			return $this->repository->find_all();
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new FindAllCampaignsException( 'Failed to retrieve campaigns.', previous: $e );
		}
	}
}
