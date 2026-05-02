<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Services;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById\ReadCampaignByIdHandler;
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
	 * @param ReadCampaignByIdHandler $read_campaign_by_id Retrieves campaigns by ID.
	 */
	public function __construct(
		private ReadCampaignByIdHandler $read_campaign_by_id,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign|null Campaign read model if found, null otherwise.
	 *
	 * @throws ReadCampaignByIdException When campaign retrieval fails.
	 */
	public function find_by_id( int|string|EntityId $campaign_id ): ?Campaign {

		try {
			return $this->read_campaign_by_id->handle( EntityId::create( $campaign_id ) );
		} catch ( InvalidEntityIdException $e ) {
			throw new ReadCampaignByIdException( $e->getMessage(), previous: $e );
		}
	}
}
