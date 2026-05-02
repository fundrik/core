<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRead\DonationReadExceptionInterface;

final class FakeDonationReadException extends Exception implements DonationReadExceptionInterface {}
