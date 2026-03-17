<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CaptureDonation;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when capture-donation targets a donation that no longer exists.
 *
 * @since 0.1.0
 */
final class CaptureDonationNotFoundException extends CaptureDonationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $donation_id The missing donation identifier.
	 * @param Throwable|null $previous The underlying repository exception.
	 */
	public function __construct( string $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot capture donation "%s": donation does not exist.',
				$donation_id,
			),
			previous: $previous,
		);
	}
}
