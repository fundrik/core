<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DisableCampaignDonations;

use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutationException;

/**
 * Thrown when disable-campaign-donations operation fails.
 *
 * @since 1.0.0
 */
class DisableCampaignDonationsException extends CampaignMutationException {}
