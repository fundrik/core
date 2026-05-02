<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\DonationRead;

use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationExceptionInterface;

/**
 * Marks all exceptions that occur while reading donations.
 *
 * @since 0.1.0
 */
interface DonationReadExceptionInterface extends FundrikApplicationExceptionInterface {}
