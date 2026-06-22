<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ReadPaginatedDonations;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;

/**
 * Thrown when reading paginated donations fails.
 *
 * @since 0.1.0
 */
final class ReadPaginatedDonationsException extends DonationApplicationException {}
