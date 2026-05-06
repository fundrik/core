<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Donations\Application\Ports\DonationRepository;

use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Provides the outbound port for persisting and retrieving donations.
 *
 * @since 0.1.0
 */
interface DonationRepositoryPort {

	/**
	 * Retrieves a donation by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID to retrieve.
	 *
	 * @return Donation|null Donation if found, null otherwise.
	 *
	 * @throws DonationRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Donation;

	/**
	 * Returns whether any donations exist for the specified campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id Campaign ID to check.
	 *
	 * @return bool True when at least one donation exists for the campaign.
	 *
	 * @throws DonationRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists_by_campaign_id( EntityId $campaign_id ): bool;

	/**
	 * Returns whether a donation exists in storage by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id Donation ID to check.
	 *
	 * @return bool True when the donation exists.
	 *
	 * @throws DonationRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists_by_id( EntityId $id ): bool;

	/**
	 * Inserts a new donation into storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation Donation to insert.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws DonationAlreadyExistsExceptionInterface When a donation with the same ID already exists.
	 * @throws DonationRepositoryExceptionInterface When the insert fails for another reason.
	 */
	public function insert( Donation $donation ): Donation;

	/**
	 * Updates an existing donation in storage.
	 *
	 * Uses the donation version from the provided entity as the expected persisted version for optimistic locking.
	 * Updates are applied only when storage still contains that version.
	 * On success, increments the persisted version and returns the persisted donation snapshot.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation Donation to update.
	 *
	 * @return Donation Persisted donation snapshot.
	 *
	 * @throws DonationNotFoundExceptionInterface When the donation does not exist.
	 * @throws DonationRepositoryExceptionInterface When the expected version does not match
	 *                                              or the update fails for another reason.
	 */
	public function update( Donation $donation ): Donation;
}
