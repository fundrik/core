<?php

declare(strict_types=1);

namespace Fundrik\Core\Components\Campaigns\Domain;

use Fundrik\Core\Components\Campaigns\Domain\Exceptions\CampaignFactoryException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTargetException;
use Fundrik\Core\Components\Campaigns\Domain\Exceptions\InvalidCampaignTitleException;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityIdException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidEntityVersionException;

/**
 * Creates Campaign entities from primitives and value objects.
 *
 * @since 0.1.0
 */
final readonly class CampaignFactory {

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Creates a Campaign from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $id The campaign ID.
	 * @param int|EntityVersion $version The campaign version.
	 * @param string|CampaignTitle $title The campaign title.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param bool $has_target Whether targeting is enabled.
	 * @param int $target_amount The target amount in minor units (0 if disabled).
	 *
	 * @return Campaign The built campaign entity.
	 *
	 * @throws CampaignFactoryException When the campaign cannot be created from the given input values.
	 */
	public function create(
		int|string|EntityId $id,
		int|EntityVersion $version,
		string|CampaignTitle $title,
		bool $is_active,
		bool $is_open,
		bool $has_target,
		int $target_amount,
	): Campaign {

		try {

			$id = $id instanceof EntityId ? $id : EntityId::create( $id );
			$version = is_int( $version ) ? EntityVersion::create( $version ) : $version;
			$title = is_string( $title ) ? CampaignTitle::create( $title ) : $title;

			$target = CampaignTarget::create( $has_target, $target_amount );

			return new Campaign(
				id: $id,
				version: $version,
				title: $title,
				is_active: $is_active,
				is_open: $is_open,
				target: $target,
			);

		} catch (
			InvalidEntityIdException
			| InvalidEntityVersionException
			| InvalidCampaignTitleException
			| InvalidCampaignTargetException $e
		) {

			throw new CampaignFactoryException(
				sprintf( 'Cannot create Campaign: %s', $e->getMessage() ),
				previous: $e,
			);
		}
	}
	// phpcs:enable

	/**
	 * Creates a new campaign with the initial version.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $id The campaign ID.
	 * @param string|CampaignTitle $title The campaign title.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param bool $has_target Whether targeting is enabled.
	 * @param int $target_amount The target amount in minor units (0 if disabled).
	 *
	 * @return Campaign The built campaign entity.
	 *
	 * @throws CampaignFactoryException When the campaign cannot be created from the given input values.
	 */
	public function create_new(
		int|string|EntityId $id,
		string|CampaignTitle $title,
		bool $is_active,
		bool $is_open,
		bool $has_target,
		int $target_amount,
	): Campaign {

			return $this->create(
				$id,
				EntityVersion::initial(),
				$title,
				$is_active,
				$is_open,
				$has_target,
				$target_amount,
			);
	}
}
