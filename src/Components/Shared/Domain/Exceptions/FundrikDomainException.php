<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain\Exceptions;

use DomainException;

/**
 * Serves as the base exception for domain errors.
 *
 * @since 1.0.0
 */
abstract class FundrikDomainException extends DomainException {}
