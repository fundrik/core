<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\EventBusExceptionInterface;

final class FakeEventBusException extends Exception implements EventBusExceptionInterface {}
