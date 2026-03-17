<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;

/**
 * Thrown when capture-donation operation fails.
 *
 * @since 0.1.0
 */
class CaptureDonationException extends DonationMutationException {}
