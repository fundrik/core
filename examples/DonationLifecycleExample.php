<?php

declare(strict_types=1);

namespace Fundrik\Core\Examples;

use Fundrik\Core\Components\Donations\Application\Commands\CreateDonationCommand;
use Fundrik\Core\Components\Donations\Application\ReadModels\Donation;
use Fundrik\Core\Components\Donations\Application\Services\DonationCommandService;
use Fundrik\Core\Components\Donations\Application\Services\DonationQueryService;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Demonstrates donation operations through the high-level services.
 *
 * @since 1.0.0
 */
final readonly class DonationLifecycleExample {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param DonationCommandService $commands Donation write service.
	 * @param DonationQueryService $queries Donation read service.
	 */
	public function __construct(
		private DonationCommandService $commands,
		private DonationQueryService $queries,
	) {}

	/**
	 * Creates a pending donation for an existing campaign.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $campaign_id Campaign ID.
	 *
	 * @return EntityId Created donation ID.
	 */
	public function create_donation( EntityId $campaign_id ): EntityId {

		$donation_id = EntityId::uuid7();

		$this->commands->create(
			new CreateDonationCommand(
				id: $donation_id,
				campaign_id: $campaign_id,
				amount: 2_500,
			),
		);

		return $donation_id;
	}

	/**
	 * Marks a pending donation as succeeded and then refunds it.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 */
	public function succeed_and_refund( EntityId $donation_id ): void {

		$this->commands->succeed( $donation_id );
		$this->commands->refund( $donation_id );
	}

	/**
	 * Retrieves the donation read model.
	 *
	 * @since 1.0.0
	 *
	 * @param EntityId $donation_id Donation ID.
	 *
	 * @return Donation|null Donation read model, null otherwise.
	 */
	public function find_donation( EntityId $donation_id ): ?Donation {

		return $this->queries->find_by_id( $donation_id );
	}
}
