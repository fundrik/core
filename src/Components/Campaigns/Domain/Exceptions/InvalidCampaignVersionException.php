<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain\Exceptions;

/**
 * Thrown when the campaign version is not a positive integer.
 *
 * @since 0.1.0
 */
final class InvalidCampaignVersionException extends CampaignDomainException {}
