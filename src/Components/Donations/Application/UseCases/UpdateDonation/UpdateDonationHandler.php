<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\UpdateDonation;

use Fundrik\Core\Components\Donations\Application\Events\DonationUpdatedEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Handles updating an existing donation.
 *
 * @since 0.1.0
 */
final readonly class UpdateDonationHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param DonationRepositoryPort $repository Updates donations in storage.
	 * @param ApplicationEventBusPort $event_bus Publishes donation events.
	 */
	public function __construct(
		private DonationRepositoryPort $repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Updates an existing donation.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to update.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws UpdateDonationException When donation update fails.
	 */
	public function handle( Donation $donation ): Donation {

		try {
			$updated_donation = $this->repository->update( $donation );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new UpdateDonationException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to update donation "%s".',
					(string) $donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish(
				new DonationUpdatedEvent( $updated_donation->get_id() ),
			);
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new UpdateDonationException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Donation "%s" was updated, but publishing the updated event failed.',
					(string) $updated_donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		return $updated_donation;
	}
	// phpcs:enable
}
