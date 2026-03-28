<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving a campaign entity by its ID.
 *
 * @since 0.1.0
 */
final readonly class FindCampaignByIdHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $campaigns Retrieves campaign entities from storage.
	 */
	public function __construct(
		private CampaignRepositoryPort $campaigns,
	) {}

	/**
	 * Retrieves a campaign entity by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID to retrieve.
	 *
	 * @return Campaign|null Campaign entity if found, null otherwise.
	 *
	 * @throws FindCampaignByIdException When retrieving the campaign fails.
	 */
	public function handle( EntityId $campaign_id ): ?Campaign {

		try {
			return $this->campaigns->find_by_id( $campaign_id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new FindCampaignByIdException(
				sprintf( 'Failed to retrieve campaign "%s".', (string) $campaign_id->get_value() ),
				previous: $e,
			);
		}
	}
}
