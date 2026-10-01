<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;

/**
 * Thrown when find-donation-by-id operation fails.
 *
 * @since 1.0.0
 */
final class FindDonationByIdException extends DonationApplicationException {}
