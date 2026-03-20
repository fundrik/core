<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\DeleteCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignDeletedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignNotFoundExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryExceptionInterface;
use Fundrik\Core\Components\Donations\Application\Ports\DonationRepository\DonationRepositoryPort;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles deleting a campaign by its ID.
 *
 * @since 0.1.0
 */
final readonly class DeleteCampaignHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Removes campaigns from storage.
	 * @param DonationRepositoryPort $donations Retrieves donations for deletion guard checks.
	 * @param ApplicationEventBusPort $event_bus Publishes campaign events.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private DonationRepositoryPort $donations,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Deletes a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID to delete.
	 *
	 * @throws DeleteCampaignNotFoundException When the campaign does not exist.
	 * @throws DeleteCampaignException When campaign deletion fails for another reason.
	 */
	public function handle( EntityId $campaign_id ): void {

		try {
			$campaign_has_donations = $this->donations->exists_by_campaign_id( $campaign_id );
		} catch ( DonationRepositoryExceptionInterface $e ) {
			throw new DeleteCampaignException(
				stage: UseCaseFailureStage::Precondition,
				reason: DeleteCampaignPreconditionReason::DonationsLookupFailed,
				message: sprintf( 'Failed to check donations for campaign "%s".', (string) $campaign_id->get_value() ),
				previous: $e,
			);
		}

		if ( $campaign_has_donations ) {
			throw new DeleteCampaignException(
				stage: UseCaseFailureStage::Precondition,
				reason: DeleteCampaignPreconditionReason::HasDonations,
				message: sprintf(
					'Cannot delete campaign "%s": campaign already has donations.',
					(string) $campaign_id->get_value(),
				),
			);
		}

		try {
			$this->repository->delete( $campaign_id );
		} catch ( CampaignNotFoundExceptionInterface $e ) {
			throw new DeleteCampaignNotFoundException(
				(string) $campaign_id->get_value(),
				$e,
			);
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new DeleteCampaignException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf( 'Failed to delete campaign "%s".', (string) $campaign_id->get_value() ),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish(
				new CampaignDeletedEvent( $campaign_id ),
			);
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new DeleteCampaignException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Campaign "%s" was deleted, but publishing the deleted event failed.',
					(string) $campaign_id->get_value(),
				),
				previous: $e,
			);
		}
	}
	// phpcs:enable
}
