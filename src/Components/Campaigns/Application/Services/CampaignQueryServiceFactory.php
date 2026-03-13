<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;

/**
 * Provides the default factory for creating the public campaign read service.
 *
 * @since 0.1.0
 */
final readonly class CampaignQueryServiceFactory {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $campaign_repository Provides campaign persistence.
	 */
	public function __construct(
		private CampaignRepositoryPort $campaign_repository,
	) {}

	/**
	 * Creates the public campaign read service.
	 *
	 * @since 0.1.0
	 *
	 * @return CampaignQueryService Campaign read service.
	 */
	public function create(): CampaignQueryService {

		return new CampaignQueryService(
			new FindCampaignByIdHandler( $this->campaign_repository ),
			new FindAllCampaignsHandler( $this->campaign_repository ),
		);
	}
}
