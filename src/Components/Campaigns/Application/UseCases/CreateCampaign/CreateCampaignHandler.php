<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;

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
	 * @param CampaignRepositoryPort $repository Adds campaigns to storage.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
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
	 */
	public function handle( Campaign $campaign ): Campaign {

		return $this->repository->insert( $campaign );
	}
}
