<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\UseCases\CreateDonation;

use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Events\DonationCreatedEvent;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationAlreadyExistsExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationStatus;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Handles creating a new donation.
 *
 * @since 0.1.0
 */
final readonly class CreateDonationHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $campaigns Retrieves campaigns for donation precondition checks.
	 * @param DonationRepositoryPort $repository Adds donations to storage.
	 * @param ApplicationEventBusPort $event_bus Publishes donation events.
	 */
	public function __construct(
		private CampaignRepositoryPort $campaigns,
		private DonationRepositoryPort $repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength, SlevomatCodingStandard.Complexity.Cognitive.ComplexityTooHigh
	/**
	 * Creates a new donation.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to create.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws CreateDonationAlreadyExistsException When the donation ID already exists.
	 * @throws CreateDonationException When donation creation fails for another reason.
	 */
	public function handle( Donation $donation ): Donation {

		$campaign_id = $donation->get_campaign_id();
		$donation_id = $donation->get_id();

		if ( $donation->get_status() !== DonationStatus::Pending ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Precondition,
				reason: CreateDonationPreconditionReason::DonationStatusMustBePending,
				message: sprintf(
					'Cannot create donation "%s": donation status must be pending. Given: "%s".',
					(string) $donation_id->get_value(),
					$donation->get_status()->value,
				),
			);
		}

		try {
			$campaign = $this->campaigns->find_by_id( $campaign_id );
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Precondition,
				reason: CreateDonationPreconditionReason::CampaignLookupFailed,
				message: sprintf(
					'Failed to retrieve campaign "%s".',
					(string) $campaign_id->get_value(),
				),
				previous: $e,
			);
		}

		if ( $campaign === null ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Precondition,
				reason: CreateDonationPreconditionReason::CampaignNotFound,
				message: sprintf(
					'Cannot create donation "%s": campaign "%s" does not exist.',
					(string) $donation_id->get_value(),
					(string) $campaign_id->get_value(),
				),
			);
		}

		if ( ! $campaign->can_receive_donations() ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Precondition,
				reason: CreateDonationPreconditionReason::CampaignCannotReceiveDonations,
				message: sprintf(
					'Cannot create donation "%s": campaign "%s" cannot receive donations.',
					(string) $donation_id->get_value(),
					(string) $campaign_id->get_value(),
				),
			);
		}

		$campaign_currency_code = $campaign->get_target()->get_currency()->get_code();
		$donation_currency_code = $donation->get_money()->get_currency()->get_code();

		if ( $donation_currency_code !== $campaign_currency_code ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Precondition,
				reason: CreateDonationPreconditionReason::CampaignCurrencyMismatch,
				message: sprintf(
					'Cannot create donation "%s": campaign "%s" uses currency "%s". Given: "%s".',
					(string) $donation_id->get_value(),
					(string) $campaign_id->get_value(),
					$campaign_currency_code,
					$donation_currency_code,
				),
			);
		}

		try {
			$created_donation = $this->repository->insert( $donation );
		} catch ( DonationAlreadyExistsExceptionInterface $e ) {
			throw new CreateDonationAlreadyExistsException(
				(string) $donation_id->get_value(),
				$e,
			);
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to create donation "%s".',
					(string) $donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish(
				new DonationCreatedEvent( $created_donation->get_id() ),
			);
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new CreateDonationException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Donation "%s" was created, but publishing the created event failed.',
					(string) $created_donation->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		return $created_donation;
	}
	// phpcs:enable
}
