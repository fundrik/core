<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\SaveCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Events\CampaignUpdatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveOutcome;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositorySaveResult;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Handles saving a campaign.
 *
 * Creates a new campaign or updates an existing.
 *
 * @since 0.1.0
 */
final readonly class SaveCampaignHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Saves campaigns in storage.
	 * @param ApplicationEventBusPort $event_bus Publishes campaign events.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Saves the given campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @return CampaignRepositorySaveOutcome The repository save outcome.
	 *
	 * @throws SaveCampaignException When campaign save fails.
	 */
	public function handle( Campaign $campaign ): CampaignRepositorySaveOutcome {

		try {
			$outcome = $this->repository->save( $campaign );
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new SaveCampaignException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf( 'Failed to save campaign "%s".', (string) $campaign->get_id()->get_value() ),
				previous: $e,
			);
		}

		$entity_id = $outcome->campaign->get_id();

		$event = match ( $outcome->result ) {
			CampaignRepositorySaveResult::Inserted => new CampaignCreatedEvent( $entity_id ),
			CampaignRepositorySaveResult::Updated => new CampaignUpdatedEvent( $entity_id ),
		};

		try {
			$this->event_bus->publish( $event );
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new SaveCampaignException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Campaign "%s" was saved, but publishing the lifecycle event failed.',
					(string) $entity_id->get_value(),
				),
				previous: $e,
			);
		}

		return $outcome;
	}
	// phpcs:enable
}
