<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles retrieving a campaign by its ID.
 *
 * @since 0.1.0
 */
final readonly class FindCampaignByIdHandler implements FindCampaignByIdUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Retrieves campaigns from storage.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
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
	 * @throws CampaignRepositoryExceptionInterface When retrieving the campaign fails.
	 */
	public function handle( EntityId $campaign_id ): ?Campaign {

		return $this->repository->find_by_id( $campaign_id );
	}
}
