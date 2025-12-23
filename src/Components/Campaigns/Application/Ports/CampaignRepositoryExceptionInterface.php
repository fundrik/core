<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports;

use Throwable;

/**
 * Marks all exceptions that occur in campaign repository operations.
 *
 * @since 0.1.0
 */
interface CampaignRepositoryExceptionInterface extends Throwable {}
