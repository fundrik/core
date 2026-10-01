<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\ReadDonationById;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;

/**
 * Thrown when read-donation-by-id operation fails.
 *
 * @since 1.0.0
 */
final class ReadDonationByIdException extends DonationApplicationException {}
