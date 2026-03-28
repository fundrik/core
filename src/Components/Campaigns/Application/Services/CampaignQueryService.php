<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignDetailsById\FindCampaignDetailsByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignDetailsById\FindCampaignDetailsByIdHandler;
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
	 * @param FindCampaignDetailsByIdHandler $find_campaign_details_by_id Retrieves campaign details by ID.
	 */
	public function __construct(
		private FindCampaignDetailsByIdHandler $find_campaign_details_by_id,
	) {}

	/**
	 * Retrieves campaign details by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 *
	 * @return CampaignDetails|null Campaign details if found, null otherwise.
	 *
	 * @throws FindCampaignDetailsByIdException When campaign details retrieval fails.
	 */
	public function find_by_id( int|string|EntityId $campaign_id ): ?CampaignDetails {

		try {
			return $this->find_campaign_details_by_id->handle( EntityId::create( $campaign_id ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new FindCampaignDetailsByIdException( $e->getMessage(), previous: $e );
		}
	}
}
