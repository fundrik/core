<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;

final class FakeApplicationEventBusException extends Exception implements ApplicationEventBusExceptionInterface {}
