<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SetCampaignTargetAmount;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides methods for setting a campaign target amount.
 *
 * @since 0.1.0
 */
interface SetCampaignTargetAmountUseCase {

	/**
	 * Sets the target amount for an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param int|CampaignTarget $amount Desired target amount or target value object.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws CampaignMutationException When changing the target amount fails.
	 */
	public function handle( EntityId $campaign_id, int|CampaignTarget $amount ): Campaign;
}
