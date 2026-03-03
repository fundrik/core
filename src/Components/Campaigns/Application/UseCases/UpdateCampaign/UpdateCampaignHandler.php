<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\UpdateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Handles updating an existing campaign.
 *
 * @since 0.1.0
 */
final readonly class UpdateCampaignHandler implements UpdateCampaignUseCase {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Updates campaigns in storage.
	 * @param ApplicationEventBusPort $event_bus Publishes campaign events.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Updates an existing campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws UpdateCampaignException When campaign update fails.
	 */
	public function handle( Campaign $campaign ): Campaign {

		try {
			$updated_campaign = $this->repository->update( $campaign );
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new UpdateCampaignException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf( 'Failed to update campaign "%s".', (string) $campaign->get_id()->get_value() ),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish(
				new CampaignUpdatedEvent( $updated_campaign->get_id() ),
			);
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new UpdateCampaignException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Campaign "%s" was updated, but publishing the updated event failed.',
					(string) $updated_campaign->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		return $updated_campaign;
	}
	// phpcs:enable
}
