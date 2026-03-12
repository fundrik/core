<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\OpenCampaign;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when open-campaign operation fails.
 *
 * @since 0.1.0
 */
final class OpenCampaignException extends CampaignMutationException {}
