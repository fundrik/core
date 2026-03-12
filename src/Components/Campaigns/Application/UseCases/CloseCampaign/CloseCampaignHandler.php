<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\UseCases\CloseCampaign;

use Fundrik\Core\Components\Campaigns\Application\Events\CampaignClosedEvent;
use Fundrik\Core\Components\Campaigns\Application\UseCases\AbstractCampaignMutationHandler;
use Fundrik\Core\Components\Campaigns\Application\UseCases\CampaignMutation;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignDomainException;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Handles closing an existing campaign for donations.
 *
 * @since 0.1.0
 */
final readonly class CloseCampaignHandler extends AbstractCampaignMutationHandler implements CloseCampaignUseCase {

	/**
	 * Returns the exception class exposed by this mutation use case.
	 *
	 * @since 0.1.0
	 *
	 * @return string Mutation exception class.
	 *
	 * @phpstan-return class-string<CloseCampaignException>
	 */
	protected function mutation_exception_class(): string {

		return CloseCampaignException::class;
	}

	/**
	 * Closes an existing campaign for donations.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign Persisted campaign snapshot.
	 */
	public function handle( EntityId $campaign_id ): Campaign {

		$mutation = CampaignMutation::Close;
		$campaign = $this->require_campaign( $campaign_id, $mutation );

		try {
			$closed_campaign = $campaign->close();
		} catch ( CampaignDomainException $e ) {
			$this->reject_mutation( $campaign_id, $mutation, $e );
		}

		return $this->persist_campaign(
			$closed_campaign,
			$mutation,
			new CampaignClosedEvent( $closed_campaign->get_id() ),
		);
	}
}
