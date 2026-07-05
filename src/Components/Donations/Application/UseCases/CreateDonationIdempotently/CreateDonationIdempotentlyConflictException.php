<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently;

use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Thrown when idempotent donation creation conflicts with an existing donation.
 *
 * @since 0.1.0
 */
final class CreateDonationIdempotentlyConflictException extends CreateDonationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Existing donation identifier.
	 * @param Throwable|null $previous Previous exception.
	 */
	public function __construct( EntityId $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Precondition,
			message: sprintf(
				'Cannot create donation "%s": request payload does not match existing donation.',
				(string) $donation_id->get_value(),
			),
			previous: $previous,
		);
	}
}
