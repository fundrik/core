<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead;

use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides the outbound port for reading campaigns.
 *
 * @since 0.1.0
 */
interface CampaignReadPort {

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Campaign ID to retrieve.
	 *
	 * @return Campaign|null Campaign read model if found, null otherwise.
	 *
	 * @throws CampaignReadExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Campaign;

	/**
	 * Retrieves campaigns by their IDs.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int, EntityId> $ids Campaign IDs to retrieve.
	 *
	 * @phpstan-param list<EntityId> $ids
	 *
	 * @return list<Campaign> Campaign read models.
	 *
	 * @throws CampaignReadExceptionInterface When the lookup fails.
	 */
	public function find_by_ids( array $ids ): array;
}
