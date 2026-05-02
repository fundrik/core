<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRead;

use Fundrik\Core\Components\Shared\Application\Exceptions\FundrikApplicationExceptionInterface;

/**
 * Marks all exceptions that occur while reading campaigns.
 *
 * @since 0.1.0
 */
interface CampaignReadExceptionInterface extends FundrikApplicationExceptionInterface {}
