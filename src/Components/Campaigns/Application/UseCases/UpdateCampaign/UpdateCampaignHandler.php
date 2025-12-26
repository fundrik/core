<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;

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
	 * @param CampaignRepositoryPort $repository Updates campaigns in storage.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
	) {}

	/**
	 * Updates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CampaignRepositoryExceptionInterface When updating the campaign fails.
	 */
	public function handle( Campaign $campaign ): Campaign {

		return $this->repository->update( $campaign );
	}
}
