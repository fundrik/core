<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead\CampaignDetailsReadPort;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving a campaign by its ID.
 *
 * @since 0.1.0
 */
final readonly class FindCampaignByIdHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignDetailsReadPort $campaign_details_read Campaign details read port.
	 */
	public function __construct(
		private CampaignDetailsReadPort $campaign_details_read,
	) {}

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID to retrieve.
	 *
	 * @return CampaignDetails|null Campaign details if found, null otherwise.
	 *
	 * @throws FindCampaignByIdException When retrieving the campaign fails.
	 */
	public function handle( EntityId $campaign_id ): ?CampaignDetails {

		try {
			return $this->campaign_details_read->find_by_id( $campaign_id );
		} catch ( CampaignDetailsReadExceptionInterface $e ) {
			throw new FindCampaignByIdException(
				sprintf( 'Failed to retrieve campaign "%s".', (string) $campaign_id->get_value() ),
				previous: $e,
			);
		}
	}
}
