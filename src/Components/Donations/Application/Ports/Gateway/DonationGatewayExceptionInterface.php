<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\Gateway;

use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationExceptionInterface;

/**
 * Marks all exceptions that occur in donation gateway operations.
 *
 * @since 0.1.0
 */
interface DonationGatewayExceptionInterface extends FundrikApplicationExceptionInterface {}
