<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SetCampaignTargetAmount;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when set-campaign-target-amount operation fails.
 *
 * @since 0.1.0
 */
final class SetCampaignTargetAmountException extends CampaignMutationException {}
