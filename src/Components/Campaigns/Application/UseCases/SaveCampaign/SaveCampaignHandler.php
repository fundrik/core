<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Handles saving a campaign.
 *
 * Creates a new campaign or updates an existing.
 *
 * @since 0.1.0
 */
final readonly class SaveCampaignHandler implements SaveCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Saves campaigns in storage.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
	) {}

	/**
	 * Saves the given campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @return CampaignRepositorySaveOutcome The repository save outcome.
	 *
	 * @throws CampaignRepositoryExceptionInterface When saving the campaign fails.
	 */
	public function handle( Campaign $campaign ): CampaignRepositorySaveOutcome {

		return $this->repository->save( $campaign );
	}
}
