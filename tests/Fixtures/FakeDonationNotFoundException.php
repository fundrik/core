<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationNotFoundExceptionInterface;

final class FakeDonationNotFoundException extends Exception implements DonationNotFoundExceptionInterface {}
