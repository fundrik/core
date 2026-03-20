<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides the outbound port for reading campaign details.
 *
 * @since 0.1.0
 */
interface CampaignDetailsReadPort {

	/**
	 * Retrieves campaign details by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Campaign ID to retrieve.
	 *
	 * @return CampaignDetails|null Campaign details if found, null otherwise.
	 *
	 * @throws CampaignDetailsReadExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?CampaignDetails;
}
