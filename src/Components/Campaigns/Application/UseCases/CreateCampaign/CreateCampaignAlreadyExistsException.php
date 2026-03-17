<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when create-campaign is retried with an already persisted campaign ID.
 *
 * @since 0.1.0
 */
final class CreateCampaignAlreadyExistsException extends CreateCampaignException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $campaign_id The existing campaign identifier.
	 * @param Throwable|null $previous The underlying repository exception.
	 */
	public function __construct( string $campaign_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot create campaign "%s": campaign already exists.',
				$campaign_id,
			),
			previous: $previous,
		);
	}
}
