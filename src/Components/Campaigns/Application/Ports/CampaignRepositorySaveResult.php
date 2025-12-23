<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports;

/**
 * Carries the result of saving a campaign in the repository.
 *
 * Distinguishes whether the repository inserted a new record
 * or updated an existing one.
 *
 * @since 0.1.0
 */
enum CampaignRepositorySaveResult: string {

	case Inserted = 'inserted';
	case Updated = 'updated';
}
