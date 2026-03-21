<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\EnableCampaignDonations;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when enable-campaign-donations targets a campaign that no longer exists.
 *
 * @since 0.1.0
 */
final class EnableCampaignDonationsNotFoundException extends EnableCampaignDonationsException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $campaign_id Missing campaign identifier.
	 * @param Throwable|null $previous Underlying repository exception.
	 */
	public function __construct( string $campaign_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot enable donations for campaign "%s": campaign does not exist.',
				$campaign_id,
			),
			previous: $previous,
		);
	}
}
