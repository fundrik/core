<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Fixtures;

use Exception;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;

final class FakeDonationRepositoryException extends Exception implements DonationRepositoryExceptionInterface {}
