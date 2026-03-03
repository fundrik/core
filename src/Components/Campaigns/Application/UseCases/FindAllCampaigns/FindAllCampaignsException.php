<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\FindAllCampaigns;

use Fundrik\Core\Components\Campaigns\Application\Exceptions\CampaignApplicationException;

/**
 * Thrown when find-all-campaigns operation fails.
 *
 * @since 0.1.0
 */
final class FindAllCampaignsException extends CampaignApplicationException {}
