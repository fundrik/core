<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when rename-campaign operation fails.
 *
 * @since 0.1.0
 */
final class RenameCampaignException extends CampaignMutationException {}
