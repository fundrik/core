<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Shared\Application\Ports\EventBus;

use Throwable;

/**
 * Marks all exceptions that occur during publishing application events.
 *
 * @since 0.1.0
 */
interface ApplicationEventBusExceptionInterface extends Throwable {}
