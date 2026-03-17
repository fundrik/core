<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when close-campaign operation fails.
 *
 * @since 0.1.0
 */
class CloseCampaignException extends CampaignMutationException {}
