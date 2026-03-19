<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetailsMapper;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdHandler;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;

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
	 * @param CampaignDetailsMapper $campaign_details_mapper Maps domain campaigns to public details.
	 */
	public function __construct(
		private FindCampaignByIdHandler $find_campaign_by_id,
		private CampaignDetailsMapper $campaign_details_mapper,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id The campaign ID to retrieve.
	 *
	 * @return CampaignDetails|null The campaign details if found, null otherwise.
	 *
	 * @throws FindCampaignByIdException When campaign retrieval fails.
	 */
	public function find_by_id( int|string|EntityId $campaign_id ): ?CampaignDetails {

		try {
			$campaign = $this->find_campaign_by_id->handle( EntityId::create( $campaign_id ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new FindCampaignByIdException( $e->getMessage(), previous: $e );
		}

		if ( $campaign === null ) {
			return null;
		}

		return $this->campaign_details_mapper->map( $campaign );
	}
}
