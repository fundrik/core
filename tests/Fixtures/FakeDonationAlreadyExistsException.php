<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationAlreadyExistsExceptionInterface;

final class FakeDonationAlreadyExistsException extends Exception implements DonationAlreadyExistsExceptionInterface {}
