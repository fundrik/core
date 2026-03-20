<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CreateCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignCreatedEvent;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignAlreadyExistsExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;

/**
 * Handles creating a new campaign.
 *
 * @since 0.1.0
 */
final readonly class CreateCampaignHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignRepositoryPort $repository Adds campaigns to storage.
	 * @param ApplicationEventBusPort $event_bus Publishes campaign events.
	 */
	public function __construct(
		private CampaignRepositoryPort $repository,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Creates a new campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign Campaign to create.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws CreateCampaignAlreadyExistsException When the campaign ID already exists.
	 * @throws CreateCampaignException When campaign creation fails for another reason.
	 */
	public function handle( Campaign $campaign ): Campaign {

		try {
			$created_campaign = $this->repository->insert( $campaign );
		} catch ( CampaignAlreadyExistsExceptionInterface $e ) {
			throw new CreateCampaignAlreadyExistsException(
				(string) $campaign->get_id()->get_value(),
				$e,
			);
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new CreateCampaignException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf( 'Failed to create campaign "%s".', (string) $campaign->get_id()->get_value() ),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish(
				new CampaignCreatedEvent( $created_campaign->get_id() ),
			);
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new CreateCampaignException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Campaign "%s" was created, but publishing the created event failed.',
					(string) $created_campaign->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		return $created_campaign;
	}
	// phpcs:enable
}
