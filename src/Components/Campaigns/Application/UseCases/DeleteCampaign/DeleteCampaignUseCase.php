<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for deleting a campaign by its ID.
 *
 * @since 0.1.0
 */
interface DeleteCampaignUseCase {

	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the repository delete fails.
	 */
	public function handle( EntityId $id ): void;
}
