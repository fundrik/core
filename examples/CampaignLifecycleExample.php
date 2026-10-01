<?php

declare(strict_types=1);

namespace Fundrik\Core\Examples;

use Fundrik\Core\Components\Campaigns\Application\Commands\CreateCampaignCommand;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\Campaign;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignCommandService;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Demonstrates campaign operations through the high-level services.
 *
 * @since 1.0.0
 */
final readonly class CampaignLifecycleExample {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param CampaignCommandService $commands Campaign write service.
	 * @param CampaignQueryService $queries Campaign read service.
	 */
	public function __construct(
		private CampaignCommandService $commands,
		private CampaignQueryService $queries,
	) {}

	/**
	 * Creates a campaign that accepts donations.
	 *
	 * @since 1.0.0
	 *
	 * @return EntityId Created campaign ID.
	 */
	public function create_campaign(): EntityId {

		$campaign_id = EntityId::uuid7();

		$this->commands->create(
			new CreateCampaignCommand(
				id: $campaign_id,
				title: 'Community library',
				accepts_donations: true,
				currency_code: 'USD',
				target_amount: 500_000,
			),
		);

		return $campaign_id;
	}

	/**
	 * Renames a campaign and disables new donations.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 */
	public function rename_and_disable( EntityId $campaign_id ): void {

		$this->commands->rename( $campaign_id, 'Community library and learning space' );
		$this->commands->disable_donations( $campaign_id );
	}

	/**
	 * Retrieves the campaign read model.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return Campaign|null Campaign read model, null otherwise.
	 */
	public function find_campaign( EntityId $campaign_id ): ?Campaign {

		return $this->queries->find_by_id( $campaign_id );
	}
}
