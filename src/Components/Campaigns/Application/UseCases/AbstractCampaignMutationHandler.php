<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignApplicationEventInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryExceptionInterface;
use Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository\CampaignRepositoryPort;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdException;
use Fundrik\Core\Components\Campaigns\Application\UseCases\FindCampaignById\FindCampaignByIdUseCase;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Application\Exceptions\UseCaseFailureStage;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusExceptionInterface;
use Fundrik\Core\Components\Shared\Application\Ports\EventBus\ApplicationEventBusPort;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Throwable;

/**
 * Provides shared workflow for campaign mutation use cases.
 *
 * @since 0.1.0
 */
abstract readonly class AbstractCampaignMutationHandler {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param FindCampaignByIdUseCase $find_campaign_by_id Retrieves campaigns for mutation use cases.
	 * @param CampaignRepositoryPort $campaigns Persists changed campaigns.
	 * @param ApplicationEventBusPort $event_bus Publishes campaign events.
	 */
	public function __construct(
		private FindCampaignByIdUseCase $find_campaign_by_id,
		private CampaignRepositoryPort $campaigns,
		private ApplicationEventBusPort $event_bus,
	) {}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Loads an existing campaign for the requested mutation.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param CampaignMutation $mutation Campaign mutation descriptor.
	 *
	 * @return Campaign Loaded campaign.
	 *
	 * @throws CampaignMutationException When lookup fails or campaign does not exist.
	 */
	protected function require_campaign( EntityId $campaign_id, CampaignMutation $mutation ): Campaign {

		try {
			$campaign = $this->find_campaign_by_id->handle( $campaign_id );
		} catch ( FindCampaignByIdException $e ) {
			throw new CampaignMutationException(
				stage: UseCaseFailureStage::Precondition,
				reason: CampaignMutationPreconditionReason::CampaignLookupFailed,
				message: sprintf( 'Failed to retrieve campaign "%s".', (string) $campaign_id->get_value() ),
				previous: $e,
			);
		}

		if ( $campaign !== null ) {
			return $campaign;
		}

		throw new CampaignMutationException(
			stage: UseCaseFailureStage::Precondition,
			reason: CampaignMutationPreconditionReason::CampaignNotFound,
			message: sprintf(
				'Cannot %s campaign "%s": campaign does not exist.',
				$mutation->infinitive(),
				(string) $campaign_id->get_value(),
			),
		);
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
	/**
	 * Wraps domain-level campaign mutation rejection.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 * @param CampaignMutation $mutation Campaign mutation descriptor.
	 * @param Throwable $previous Previous domain exception.
	 *
	 * @throws CampaignMutationException Always.
	 */
	protected function reject_mutation( EntityId $campaign_id, CampaignMutation $mutation, Throwable $previous ): never {

		throw new CampaignMutationException(
			stage: UseCaseFailureStage::Precondition,
			reason: CampaignMutationPreconditionReason::CampaignMutationRejected,
			message: sprintf(
				'Cannot %s campaign "%s": change was rejected.',
				$mutation->infinitive(),
				(string) $campaign_id->get_value(),
			),
			previous: $previous,
		);
	}
	// phpcs:enable

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Persists the changed campaign and publishes the resulting mutation event.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign Changed campaign.
	 * @param CampaignMutation $mutation Campaign mutation descriptor.
	 * @param CampaignApplicationEventInterface $event Mutation event to publish.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 *
	 * @throws CampaignMutationException When persistence or event publishing fails.
	 */
	protected function persist_campaign(
		Campaign $campaign,
		CampaignMutation $mutation,
		CampaignApplicationEventInterface $event,
	): Campaign {

		try {
			$updated_campaign = $this->campaigns->update( $campaign );
		} catch ( CampaignRepositoryExceptionInterface $e ) {
			throw new CampaignMutationException(
				stage: UseCaseFailureStage::Persistence,
				message: sprintf(
					'Failed to %s campaign "%s".',
					$mutation->infinitive(),
					(string) $campaign->get_id()->get_value(),
				),
				previous: $e,
			);
		}

		try {
			$this->event_bus->publish( $event );
		} catch ( ApplicationEventBusExceptionInterface $e ) {
			throw new CampaignMutationException(
				stage: UseCaseFailureStage::EventPublish,
				message: sprintf(
					'Campaign "%s" was %s, but publishing the %s event failed.',
					(string) $updated_campaign->get_id()->get_value(),
					$mutation->past_participle(),
					$mutation->event_label(),
				),
				previous: $e,
			);
		}

		return $updated_campaign;
	}
	// phpcs:enable
}
