<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when create-donation is retried with an already persisted donation ID.
 *
 * @since 0.1.0
 */
final class CreateDonationAlreadyExistsException extends CreateDonationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $donation_id Existing donation identifier.
	 * @param Throwable|null $previous Underlying repository exception.
	 */
	public function __construct( string $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot create donation "%s": donation already exists.',
				$donation_id,
			),
			previous: $previous,
		);
	}
}
