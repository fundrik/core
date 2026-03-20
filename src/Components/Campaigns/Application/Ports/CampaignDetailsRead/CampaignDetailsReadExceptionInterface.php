<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignDetailsRead;

use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationExceptionInterface;

/**
 * Marks all exceptions that occur in campaign details read operations.
 *
 * @since 0.1.0
 */
interface CampaignDetailsReadExceptionInterface extends FundrikApplicationExceptionInterface {}
