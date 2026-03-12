<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CancelDonation;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;

/**
 * Thrown when cancel-donation operation fails.
 *
 * @since 0.1.0
 */
final class CancelDonationException extends DonationMutationException {}
