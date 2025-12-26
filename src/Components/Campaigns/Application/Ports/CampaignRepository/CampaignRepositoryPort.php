<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\CampaignRepository;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Defines the outbound port for persisting and retrieving campaigns.
 *
 * @since 0.1.0
 */
interface CampaignRepositoryPort {

	/**
	 * Retrieves a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID to retrieve.
	 *
	 * @return Campaign|null The campaign if found, null otherwise.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Campaign;

	/**
	 * Retrieves all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> The list of campaigns.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_all(): array;

	/**
	 * Returns whether a campaign exists in storage by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID to check.
	 *
	 * @return bool True if the campaign exists.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists_by_id( EntityId $id ): bool;

	/**
	 * Inserts a new campaign into storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to insert.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the insert fails.
	 */
	public function insert( Campaign $campaign ): Campaign;

	/**
	 * Updates an existing campaign in storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @return Campaign The persisted campaign snapshot.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the update fails.
	 */
	public function update( Campaign $campaign ): Campaign;

	/**
	 * Saves the given campaign by inserting or updating it.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @return CampaignRepositorySaveOutcome Contains the result and the persisted campaign snapshot.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the save fails.
	 */
	public function save( Campaign $campaign ): CampaignRepositorySaveOutcome;

	/**
	 * Removes a campaign from storage by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the delete fails.
	 */
	public function delete( EntityId $id ): void;
}
