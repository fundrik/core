<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\SucceedDonation;

use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Thrown when a donation to succeed cannot be found.
 *
 * @since 1.0.0
 */
final class SucceedDonationNotFoundException extends SucceedDonationException {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Missing donation identifier.
	 * @param Throwable|null $previous Underlying repository exception.
	 */
	public function __construct( EntityId $donation_id, ?Throwable $previous = null ) {

		parent::__construct(
			stage: UseCaseFailureStage::Persistence,
			message: sprintf(
				'Cannot succeed donation "%s": donation does not exist.',
				(string) $donation_id->get_value(),
			),
			previous: $previous,
		);
	}
}
