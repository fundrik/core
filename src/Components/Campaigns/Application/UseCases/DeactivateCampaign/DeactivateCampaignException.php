<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeactivateCampaign;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when deactivate-campaign operation fails.
 *
 * @since 0.1.0
 */
final class DeactivateCampaignException extends CampaignMutationException {}
