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
 * Creates Campaign entities.
 *
 * @since 0.1.0
 */
final readonly class CampaignFactory {

	/**
	 * Creates a Campaign from value objects.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID.
	 * @param EntityVersion $version The campaign version.
	 * @param CampaignTitle $title The campaign title.
	 * @param bool $is_open Whether the campaign is open.
	 * @param CampaignTarget $target Campaign target.
	 *
	 * @return Campaign The built campaign entity.
	 */
	public function create(
		EntityId $id,
		EntityVersion $version,
		CampaignTitle $title,
		bool $is_open,
		CampaignTarget $target,
	): Campaign {

		return new Campaign( $id, $version, $title, $is_open, $target );
	}

	/**
	 * Creates a campaign from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $id The campaign ID.
	 * @param int $version The campaign version.
	 * @param string $title The campaign title.
	 * @param bool $is_open Whether the campaign is open.
	 * @param string $currency_code The campaign currency code (ISO 4217).
	 * @param int|null $target_amount The target amount, if configured.
	 *
	 * @return Campaign The built campaign entity.
	 *
	 * @throws CampaignFactoryException When creating campaign from primitives fails.
	 */
	public function create_from_primitives(
		int|string|EntityId $id,
		int $version,
		string $title,
		bool $is_open,
		string $currency_code,
		?int $target_amount,
	): Campaign {

		try {

			return $this->create(
				id: EntityId::create( $id ),
				version: EntityVersion::create( $version ),
				title: CampaignTitle::create( $title ),
				is_open: $is_open,
				target: CampaignTarget::create( $currency_code, $target_amount ),
			);

		} catch (
			InvalidEntityIdException
			| InvalidEntityVersionException
			| InvalidCampaignTitleException
			| InvalidCampaignTargetException $e
		) {

			throw new CampaignFactoryException( $e->getMessage(), previous: $e );
		}
	}

	/**
	 * Creates a new campaign with the initial version.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID.
	 * @param CampaignTitle $title The campaign title.
	 * @param bool $is_open Whether the campaign is open.
	 * @param CampaignTarget $target Campaign target.
	 *
	 * @return Campaign The built campaign entity.
	 */
	public function create_new( EntityId $id, CampaignTitle $title, bool $is_open, CampaignTarget $target ): Campaign {

		return new Campaign( $id, EntityVersion::initial(), $title, $is_open, $target );
	}

	/**
	 * Creates a new campaign with initial version from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string|EntityId $id The campaign ID.
	 * @param string $title The campaign title.
	 * @param bool $is_open Whether the campaign is open.
	 * @param string $currency_code The campaign currency code (ISO 4217).
	 * @param int|null $target_amount The target amount, if configured.
	 *
	 * @return Campaign The built campaign entity.
	 *
	 * @throws CampaignFactoryException When creating campaign from primitives fails.
	 */
	public function create_new_from_primitives(
		int|string|EntityId $id,
		string $title,
		bool $is_open,
		string $currency_code,
		?int $target_amount,
	): Campaign {

		return $this->create_from_primitives(
			$id,
			EntityVersion::initial()->get_value(),
			$title,
			$is_open,
			$currency_code,
			$target_amount,
		);
	}
}
