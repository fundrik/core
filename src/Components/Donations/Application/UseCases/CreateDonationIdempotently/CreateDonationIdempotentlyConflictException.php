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
	 * @param int|string|EntityId $donation_id Existing donation identifier.
	 * @param Throwable|null $previous Previous exception.
	 */
	public function __construct( int|string|EntityId $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Precondition,
			message: sprintf(
				'Cannot create donation "%s": request payload does not match existing donation.',
				self::format_donation_id( $donation_id ),
			),
			previous: $previous,
		);
	}

	/**
	 * Formats the donation ID for exception messages.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $donation_id Donation identifier.
	 *
	 * @return string Donation identifier.
	 */
	private static function format_donation_id( int|string|EntityId $donation_id ): string {

		return (string) ( $donation_id instanceof EntityId ? $donation_id->get_value() : $donation_id );
	}
}
