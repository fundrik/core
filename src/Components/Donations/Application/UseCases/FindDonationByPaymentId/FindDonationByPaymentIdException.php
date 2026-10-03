<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FindDonationByPaymentId;

use Fundrik\Core\Components\Donations\Application\Exceptions\DonationApplicationException;

/**
 * Indicates that donation lookup by payment ID failed.
 *
 * @since 1.1.0
 */
final class FindDonationByPaymentIdException extends DonationApplicationException {}
