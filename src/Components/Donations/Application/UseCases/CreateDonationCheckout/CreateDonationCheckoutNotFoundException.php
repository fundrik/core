<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationCheckout;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Thrown when a donation disappears before its payment association is persisted.
 *
 * @since 1.1.0
 */
final class CreateDonationCheckoutNotFoundException extends CreateDonationCheckoutException {

	/**
	 * Constructor.
	 *
	 * @since 1.1.0
	 *
	 * @param EntityId $donation_id Missing donation ID.
	 * @param Throwable|null $previous Underlying repository exception.
	 */
	public function __construct( EntityId $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot await payment for donation "%s": donation does not exist.',
				$donation_id->get_value(),
			),
			previous: $previous,
		);
	}
}
