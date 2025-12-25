<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;

/**
 * Carries the outcome of saving a campaign in the repository.
 *
 * @since 0.1.0
 */
final readonly class CampaignRepositorySaveOutcome {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositorySaveResult $result Indicates whether the campaign was inserted or updated.
	 * @param Campaign $campaign The persisted campaign snapshot.
	 */
	public function __construct(
		public CampaignRepositorySaveResult $result,
		public Campaign $campaign,
	) {}
}
