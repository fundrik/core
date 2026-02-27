<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain\Exceptions;

use Fundrik\Core\Components\Shared\Domain\Exceptions\FundrikDomainException;

/**
 * Serves as the base exception for donation domain errors.
 *
 * @since 0.1.0
 */
abstract class DonationDomainException extends FundrikDomainException {}
