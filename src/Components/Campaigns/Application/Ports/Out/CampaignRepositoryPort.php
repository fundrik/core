<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Application\Ports\Out;

use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Shared\Domain\EntityId;

/**
 * Defines the outbound port for accessing campaign persistence.
 *
 * This interface represents the storage contract required by the application layer.
 * It allows the service layer to remain decoupled from specific infrastructure details.
 *
 * @since 0.1.0
 */
interface CampaignRepositoryPort {

	/**
	 * Fetches the DTO of a campaign by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to retrieve.
	 *
	 * @return Campaign|null The campaign if found, null otherwise.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_by_id( EntityId $id ): ?Campaign;

	/**
	 * Fetches all campaigns.
	 *
	 * @since 0.1.0
	 *
	 * @return array<Campaign> The list of campaign entities.
	 *
	 * @phpstan-return list<Campaign>
	 *
	 * @throws CampaignRepositoryExceptionInterface When the lookup fails.
	 */
	public function find_all(): array;

	/**
	 * Returns whether the campaign exists in storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign entity to check.
	 *
	 * @return bool True if the campaign exists.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the existence check fails.
	 */
	public function exists( Campaign $campaign ): bool;

	/**
	 * Inserts a new campaign into storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to insert.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the insert fails.
	 */
	public function insert( Campaign $campaign ): void;

	/**
	 * Inserts a new campaign into storage without a predefined ID.
	 *
	 * This method should be used when the underlying persistence mechanism
	 * generates the campaign ID automatically (for example, auto-increment column).
	 *
	 * @since 0.1.0
	 *
	 * @param CampaignTitle $title The campaign title.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open for donations.
	 * @param CampaignTarget $target The campaign target.
	 *
	 * @return EntityId The newly assigned campaign ID.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the insert fails.
	 */
	public function insert_without_id(
		CampaignTitle $title,
		bool $is_active,
		bool $is_open,
		CampaignTarget $target,
	): EntityId;

	/**
	 * Updates an existing campaign in storage.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to update.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the update fails.
	 */
	public function update( Campaign $campaign ): void;

	/**
	 * Saves the given campaign by inserting or updating it.
	 *
	 * @since 0.1.0
	 *
	 * @param Campaign $campaign The campaign to save.
	 *
	 * @return CampaignRepositorySaveResult Indicates whether the campaign was inserted or updated.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the save fails.
	 */
	public function save( Campaign $campaign ): CampaignRepositorySaveResult;

	/**
	 * Removes a campaign from storage by its ID.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The ID of the campaign to delete.
	 *
	 * @throws CampaignRepositoryExceptionInterface When the delete fails.
	 */
	public function delete( EntityId $id ): void;
}
