<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ChangeCampaignTarget;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when change-campaign-target operation fails.
 *
 * @since 0.1.0
 */
class ChangeCampaignTargetException extends CampaignMutationException {}
