<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation;

use Fundrik\Core\Components\Donations\Application\UseCases\DonationMutationException;

/**
 * Thrown when succeeding a donation fails.
 *
 * @since 0.1.0
 */
class SucceedDonationException extends DonationMutationException {}
