<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Application\Exceptions;

use RuntimeException;

/**
 * Serves as the base exception for application errors.
 *
 * @since 0.1.0
 */
abstract class FundrikApplicationException extends RuntimeException implements FundrikApplicationExceptionInterface {}
