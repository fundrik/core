<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\RenameCampaign;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when rename-campaign targets a campaign that no longer exists.
 *
 * @since 0.1.0
 */
final class RenameCampaignNotFoundException extends RenameCampaignException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $campaign_id The missing campaign identifier.
	 * @param Throwable|null $previous The underlying repository exception.
	 */
	public function __construct( string $campaign_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot rename campaign "%s": campaign does not exist.',
				$campaign_id,
			),
			previous: $previous,
		);
	}
}
