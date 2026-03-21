<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Throwable;

/**
 * Thrown when a donation to succeed cannot be found.
 *
 * @since 0.1.0
 */
final class SucceedDonationNotFoundException extends SucceedDonationException {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $donation_id Missing donation identifier.
	 * @param Throwable|null $previous Underlying repository exception.
	 */
	public function __construct( string $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot succeed donation "%s": donation does not exist.',
				$donation_id,
			),
			previous: $previous,
		);
	}
}
