<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationDetailsById;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;

/**
 * Thrown when find-donation-details-by-id operation fails.
 *
 * @since 0.1.0
 */
final class FindDonationDetailsByIdException extends DonationApplicationException {}
