<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead\CampaignReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving a campaign read model by its ID.
 *
 * @since 0.1.0
 */
final readonly class ReadCampaignByIdHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignReadPort $campaign_read Retrieves campaigns from storage.
	 */
	public function __construct(
		private CampaignReadPort $campaign_read,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID to retrieve.
	 *
	 * @return Campaign|null Campaign read model if found, null otherwise.
	 *
	 * @throws ReadCampaignByIdException When campaign retrieval fails.
	 */
	public function handle( EntityId $campaign_id ): ?Campaign {

		try {
			return $this->campaign_read->find_by_id( $campaign_id );
		} catch ( CampaignReadExceptionInterface $e ) {
			throw new ReadCampaignByIdException(
				sprintf( 'Failed to retrieve campaign "%s".', (string) $campaign_id->get_value() ),
				previous: $e,
			);
		}
	}
}
