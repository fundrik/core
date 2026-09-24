<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain\Exceptions;

/**
 * Thrown when a campaign cannot be changed, for example if nothing would actually change.
 *
 * @since 1.0.0
 */
final class CampaignChangeException extends CampaignDomainException {}
