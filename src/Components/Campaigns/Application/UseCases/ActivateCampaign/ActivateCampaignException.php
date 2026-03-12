<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ActivateCampaign;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when activate-campaign operation fails.
 *
 * @since 0.1.0
 */
final class ActivateCampaignException extends CampaignMutationException {}
