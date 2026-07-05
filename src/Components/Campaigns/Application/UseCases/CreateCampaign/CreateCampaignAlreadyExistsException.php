<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
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
	 * @param EntityId $campaign_id Existing campaign identifier.
	 * @param Throwable|null $previous Underlying repository exception.
	 */
	public function __construct( EntityId $campaign_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot create campaign "%s": campaign already exists.',
				(string) $campaign_id->get_value(),
			),
			previous: $previous,
		);
	}
}
