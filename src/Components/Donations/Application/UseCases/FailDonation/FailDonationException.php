<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\FailDonation;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;

/**
 * Thrown when fail-donation operation fails.
 *
 * @since 0.1.0
 */
class FailDonationException extends DonationMutationException {}
