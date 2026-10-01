<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Domain\Exceptions;

/**
 * Thrown when a donation cannot be changed, for example due to an invalid status transition.
 *
 * @since 1.0.0
 */
final class DonationChangeException extends DonationDomainException {}
