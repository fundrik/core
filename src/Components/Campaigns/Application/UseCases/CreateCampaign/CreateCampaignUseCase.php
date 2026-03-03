<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Provides methods for creating a new campaign.
 *
 * @since 0.1.0
 */
interface CreateCampaignUseCase {

	/**
	 * Creates a new campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to create.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CreateCampaignException When campaign creation fails.
	 */
	public function handle( Campaign $campaign ): Campaign;
}
