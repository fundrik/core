<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\RefundDonation;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;

/**
 * Thrown when refund-donation operation fails.
 *
 * @since 0.1.0
 */
final class RefundDonationException extends DonationMutationException {}
