<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository;

/**
 * Defines the repository save result.
 *
 * @since 0.1.0
 */
enum CampaignRepositorySaveResult: string {

	case Inserted = 'inserted';
	case Updated = 'updated';
}
