<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindAllDonations;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;

/**
 * Thrown when find-all-donations operation fails.
 *
 * @since 0.1.0
 */
final class FindAllDonationsException extends DonationApplicationException {}
