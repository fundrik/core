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
	 * @param EntityId $id The donation ID to retrieve.
	 *
	 * @return Donation|null The donation if found, null otherwise.
	 *
	 * @throws DonationRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Donation;

	/**
	 * Retrieves all donations.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, Donation> The list of donations.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws DonationRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_all(): array;

	/**
	 * Retrieves all donations for the specified campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID to filter by.
	 *
	 * @return array<int, Donation> The list of campaign donations.
	 *
	 * @phpstan-return list<Donation>
	 *
	 * @throws DonationRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_all_by_campaign_id( EntityId $campaign_id ): array;

	/**
	 * Returns whether any donations exist for the specified campaign.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $campaign_id The campaign ID to check.
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
	 * @param EntityId $id The donation ID to check.
	 *
	 * @return bool True if the donation exists.
	 *
	 * @throws DonationRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists_by_id( EntityId $id ): bool;

	/**
	 * Inserts a new donation into storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to insert.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws DonationAlreadyExistsExceptionInterface When a donation with the same ID already exists.
	 * @throws DonationRepositoryExceptionInterface When the insert fails for another reason.
	 */
	public function insert( Donation $donation ): Donation;

	/**
	 * Updates an existing donation in storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Donation $donation The donation to update.
	 *
	 * @return Donation The persisted donation snapshot.
	 *
	 * @throws DonationNotFoundExceptionInterface When the donation does not exist.
	 * @throws DonationRepositoryExceptionInterface When the update fails for another reason.
	 */
	public function update( Donation $donation ): Donation;
}
