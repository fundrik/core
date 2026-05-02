<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\ReadCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;

/**
 * Thrown when read-campaign-by-id operation fails.
 *
 * @since 0.1.0
 */
final class ReadCampaignByIdException extends CampaignApplicationException {}
