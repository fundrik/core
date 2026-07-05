<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\RejectDonation;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Thrown when a donation to reject cannot be found.
 *
 * @since 0.1.0
 */
final class RejectDonationNotFoundException extends RejectDonationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Missing donation identifier.
	 * @param Throwable|null $previous Underlying repository exception.
	 */
	public function __construct( EntityId $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot reject donation "%s": donation does not exist.',
				(string) $donation_id->get_value(),
			),
			previous: $previous,
		);
	}
}
