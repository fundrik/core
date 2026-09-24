<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Domain\Exceptions;

/**
 * Thrown when the entity version is not a positive integer.
 *
 * @since 1.0.0
 */
final class InvalidEntityVersionException extends FundrikDomainException {}
