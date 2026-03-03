<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationsByCampaignId;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;

/**
 * Thrown when find-donations-by-campaign-id operation fails.
 *
 * @since 0.1.0
 */
final class FindDonationsByCampaignIdException extends DonationApplicationException {}
