<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\AuthorizeDonation;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;

/**
 * Thrown when authorize-donation operation fails.
 *
 * @since 0.1.0
 */
final class AuthorizeDonationException extends DonationMutationException {}
