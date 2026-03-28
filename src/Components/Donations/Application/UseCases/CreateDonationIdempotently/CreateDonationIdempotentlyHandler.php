<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonationIdempotently;

use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationAlreadyExistsException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationException;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\CreateDonationHandler;
use Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation\DonationCreationData;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdException;
use Fundrik\Core\Components\Donations\Application\UseCases\FindDonationById\FindDonationByIdHandler;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;

/**
 * Handles idempotent donation creation.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationIdempotentlyHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CreateDonationHandler $create_donation Creates donations from validated input.
	 * @param FindDonationByIdHandler $find_donation_by_id Retrieves donation entities for replay resolution.
	 */
	public function __construct(
		private CreateDonationHandler $create_donation,
		private FindDonationByIdHandler $find_donation_by_id,
	) {}

	/**
	 * Creates a donation with idempotent replay semantics.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationCreationData $data Validated donation creation data.
	 *
	 * @return CreateDonationIdempotentlyResult Created or replayed donation result.
	 *
	 * @throws CreateDonationIdempotentlyConflictException When an existing donation conflicts with the request payload.
	 * @throws CreateDonationException When idempotent donation creation fails.
	 */
	public function handle( DonationCreationData $data ): CreateDonationIdempotentlyResult {

		try {
			$created_donation = $this->create_donation->handle( $data );
		} catch ( CreateDonationAlreadyExistsException ) {
			$replayed_donation = $this->resolve_duplicate_request( $data );

			return new CreateDonationIdempotentlyResult(
				$replayed_donation,
				CreateDonationIdempotentlyStatus::Replayed,
			);
		}

		// phpcs:ignore SlevomatCodingStandard.Functions.RequireSingleLineCall.RequiredSingleLineCall
		return new CreateDonationIdempotentlyResult(
			$created_donation,
			CreateDonationIdempotentlyStatus::Created,
		);
	}

	/**
	 * Resolves an idempotent duplicate-create request.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationCreationData $data Validated donation creation data.
	 *
	 * @return Donation Replayed donation.
	 *
	 * @throws CreateDonationIdempotentlyConflictException When an existing donation conflicts with the request payload.
	 * @throws CreateDonationException When existing donation lookup fails.
	 */
	private function resolve_duplicate_request( DonationCreationData $data ): Donation {

		$existing_donation = $this->find_existing_donation( $data );

		if ( ! $this->existing_donation_matches_request( $existing_donation, $data ) ) {
			throw new CreateDonationIdempotentlyConflictException(
				$data->get_donation_id(),
			);
		}

		return $existing_donation;
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Returns the existing donation for duplicate-request resolution.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationCreationData $data Validated donation creation data.
	 *
	 * @return Donation Existing donation.
	 *
	 * @throws CreateDonationException When existing donation lookup fails.
	 */
	private function find_existing_donation( DonationCreationData $data ): Donation {

		$donation_id = (string) $data->get_donation_id()->get_value();

		try {
			$existing_donation = $this->find_donation_by_id->handle( $data->get_donation_id() );
		} catch ( FindDonationByIdException $e ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to retrieve existing donation "%s".',
					$donation_id,
				),
				previous: $e,
			);
		}

		if ( $existing_donation === null ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to retrieve existing donation "%s".',
					$donation_id,
				),
			);
		}

		return $existing_donation;
	}
	// phpcs:enable

	/**
	 * Returns whether the existing donation matches the idempotent request.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $existing_donation Existing donation.
	 * @param DonationCreationData $data Validated donation creation data.
	 *
	 * @return bool True when the existing donation matches the request.
	 */
	private function existing_donation_matches_request(
		Donation $existing_donation,
		DonationCreationData $data,
	): bool {

		return $existing_donation->get_campaign_id()->equals( $data->get_campaign_id() )
			&& $existing_donation->get_money()->get_amount()->equals( $data->get_amount() );
	}
}
