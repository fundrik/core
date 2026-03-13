<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns\FindAllCampaignsHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides the public entry point for campaign read operations.
 *
 * @since 0.1.0
 */
final readonly class CampaignQueryService {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param FindCampaignByIdHandler $find_campaign_by_id Retrieves a campaign by its ID.
	 * @param FindAllCampaignsHandler $find_all_campaigns Retrieves all campaigns.
	 */
	public function __construct(
		private FindCampaignByIdHandler $find_campaign_by_id,
		private FindAllCampaignsHandler $find_all_campaigns,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID to retrieve.
	 *
	 * @return Campaign|null The campaign if found, null otherwise.
	 *
	 * @throws FindCampaignByIdException When campaign retrieval fails.
	 */
	public function find_by_id( EntityId $campaign_id ): ?Campaign {

		return $this->find_campaign_by_id->handle( $campaign_id );
	}

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, Campaign> The list of campaigns.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws FindAllCampaignsException When campaign retrieval fails.
	 */
	public function find_all(): array {

		return $this->find_all_campaigns->handle();
	}
}
