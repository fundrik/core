<?php

declare(strict_types=1);

namespace Fundrik\Core;

use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;

/**
 * Provides the public entry point for Fundrik services.
 *
 * @since 0.1.0
 */
final readonly class Fundrik {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignQueryService $campaign_query Campaign read service.
	 * @param CampaignCommandService $campaign_command Campaign write service.
	 * @param DonationQueryService $donation_query Donation read service.
	 * @param DonationCommandService $donation_command Donation write service.
	 */
	public function __construct(
		private CampaignQueryService $campaign_query,
		private CampaignCommandService $campaign_command,
		private DonationQueryService $donation_query,
		private DonationCommandService $donation_command,
	) {}

	/**
	 * Returns the public campaign read service.
	 *
	 * @since 0.1.0
	 *
	 * @return CampaignQueryService Campaign read service.
	 */
	public function campaign_query(): CampaignQueryService {

		return $this->campaign_query;
	}

	/**
	 * Returns the public campaign write service.
	 *
	 * @since 0.1.0
	 *
	 * @return CampaignCommandService Campaign write service.
	 */
	public function campaign_command(): CampaignCommandService {

		return $this->campaign_command;
	}

	/**
	 * Returns the public donation read service.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationQueryService Donation read service.
	 */
	public function donation_query(): DonationQueryService {

		return $this->donation_query;
	}

	/**
	 * Returns the public donation write service.
	 *
	 * @since 0.1.0
	 *
	 * @return DonationCommandService Donation write service.
	 */
	public function donation_command(): DonationCommandService {

		return $this->donation_command;
	}
}
