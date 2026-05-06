<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases;

use Fundrik\Core\Components\Donations\Application\Events\DonationApplicationEventInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationNotFoundExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Provides shared workflow for donation mutation use cases.
 *
 * @since 0.1.0
 */
abstract readonly class AbstractDonationMutationHandler {

	/**
	 * Creates the concrete mutation exception exposed by the use case.
	 *
	 * @since 0.1.0
	 *
	 * @param UseCaseFailureStage $stage Processing stage where failure happened.
	 * @param string $message Exception message.
	 * @param Throwable|null $previous Previous exception.
	 * @param DonationMutationPreconditionReason|null $reason Optional precondition failure reason.
	 *
	 * @return DonationMutationException Concrete mutation exception.
	 */
	abstract protected function new_mutation_exception(
		UseCaseFailureStage $stage,
		string $message,
		?Throwable $previous = null,
		?DonationMutationPreconditionReason $reason = null,
	): DonationMutationException;

	/**
	 * Creates the concrete mutation exception used when the donation disappears before persistence.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param Throwable $previous Previous repository exception.
	 *
	 * @return DonationMutationException Concrete mutation exception.
	 */
	abstract protected function new_not_found_mutation_exception(
		EntityId $donation_id,
		Throwable $previous,
	): DonationMutationException;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationRepositoryPort $donations Persists changed donations.
	 * @param ApplicationEventBusPort $event_bus Publishes donation events.
	 */
	public function __construct(
		private DonationRepositoryPort $donations,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Loads an existing donation for the requested mutation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param DonationMutation $mutation Donation mutation descriptor.
	 *
	 * @return Donation Loaded donation.
	 *
	 * @throws DonationMutationException When lookup fails or donation does not exist.
	 */
	protected function require_donation( EntityId $donation_id, DonationMutation $mutation ): Donation {

		try {
			$donation = $this->donations->find_by_id( $donation_id );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw $this->new_mutation_exception(
				stage: UseCaseFailureStage::Precondition,
				message: sprintf( 'Failed to retrieve donation "%s".', (string) $donation_id->get_value() ),
				previous: $e,
				reason: DonationMutationPreconditionReason::DonationLookupFailed,
			);
		}

		if ( $donation !== null ) {
			return $donation;
		}

		throw $this->new_mutation_exception(
			stage: UseCaseFailureStage::Precondition,
			message: sprintf(
				'Cannot %s donation "%s": donation does not exist.',
				$mutation->infinitive(),
				(string) $donation_id->get_value(),
			),
			reason: DonationMutationPreconditionReason::DonationNotFound,
		);
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Wraps domain-level donation mutation rejection.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 * @param DonationMutation $mutation Donation mutation descriptor.
	 * @param Throwable $previous Previous domain exception.
	 *
	 * @throws DonationMutationException Always.
	 */
	protected function reject_mutation(
		EntityId $donation_id,
		DonationMutation $mutation,
		Throwable $previous,
	): never {

		throw $this->new_mutation_exception(
			stage: UseCaseFailureStage::Precondition,
			message: sprintf(
				'Cannot %s donation "%s": change was rejected.',
				$mutation->infinitive(),
				(string) $donation_id->get_value(),
			),
			previous: $previous,
			reason: DonationMutationPreconditionReason::DonationMutationRejected,
		);
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Persists the changed donation and publishes the resulting mutation event.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation Changed donation.
	 * @param DonationMutation $mutation Donation mutation descriptor.
	 * @param DonationApplicationEventInterface $event Mutation event to publish.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws DonationMutationException When persistence or event publishing fails.
	 */
	protected function persist_donation(
		Donation $donation,
		DonationMutation $mutation,
		DonationApplicationEventInterface $event,
	): Donation {

		try {
			$updated_donation = $this->donations->update( $donation );
		} catch ( DonationNotFoundExceptionInterface $e ) {
			throw $this->new_not_found_mutation_exception( $donation->get_id(), $e );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw $this->new_mutation_exception(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to %s donation "%s".',
					$mutation->infinitive(),
					(string) $donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish( $event );
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw $this->new_mutation_exception(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Donation "%s" was %s, but publishing the %s event failed.',
					(string) $updated_donation->get_id()->get_value(),
					$mutation->past_participle(),
					$mutation->event_label(),
				),
				previous: $e,
			);
		}

		return $updated_donation;
	}
	// phpcs:enable
}
