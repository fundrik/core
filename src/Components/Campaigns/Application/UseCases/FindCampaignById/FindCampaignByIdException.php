<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;

/**
 * Thrown when find-campaign-by-id operation fails.
 *
 * @since 0.1.0
 */
final class FindCampaignByIdException extends CampaignApplicationException {}
