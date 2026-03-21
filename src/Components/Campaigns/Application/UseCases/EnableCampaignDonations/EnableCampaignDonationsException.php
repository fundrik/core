<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when enable-campaign-donations operation fails.
 *
 * @since 0.1.0
 */
class EnableCampaignDonationsException extends CampaignMutationException {}
