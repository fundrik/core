<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Donations\Application\Ports\DonationDetailsRead\DonationDetailsReadExceptionInterface;

final class FakeDonationDetailsReadException extends Exception implements DonationDetailsReadExceptionInterface {}
