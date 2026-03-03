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
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyAmountException;
use Fundrik\Core\Components\Shared\Domain\Exceptions\InvalidMoneyCurrencyException;
use Fundrik\Core\Components\Shared\Domain\Money;

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
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param CampaignTarget $target The campaign target.
	 *
	 * @return Campaign The built campaign entity.
	 */
	public function create(
		EntityId $id,
		EntityVersion $version,
		CampaignTitle $title,
		bool $is_active,
		bool $is_open,
		CampaignTarget $target,
	): Campaign {

		return new Campaign(
			id: $id,
			version: $version,
			title: $title,
			is_active: $is_active,
			is_open: $is_open,
			target: $target,
		);
	}

	// phpcs:disable SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
	/**
	 * Creates a campaign from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID.
	 * @param int $version The campaign version.
	 * @param string $title The campaign title.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param bool $has_target Whether targeting is enabled.
	 * @param int $target_amount The target amount in minor units.
	 * @param string $target_currency The target currency (ISO 4217).
	 *
	 * @return Campaign The built campaign entity.
	 *
	 * @throws CampaignFactoryException When creating campaign from primitives fails.
	 */
	public function create_from_primitives(
		int|string $id,
		int $version,
		string $title,
		bool $is_active,
		bool $is_open,
		bool $has_target,
		int $target_amount,
		string $target_currency,
	): Campaign {

		try {

			return $this->create(
				id: EntityId::create( $id ),
				version: EntityVersion::create( $version ),
				title: CampaignTitle::create( $title ),
				is_active: $is_active,
				is_open: $is_open,
				target: CampaignTarget::create( $has_target, Money::create( $target_amount, $target_currency ) ),
			);

		} catch (
			InvalidEntityIdException
			| InvalidEntityVersionException
			| InvalidCampaignTitleException
			| InvalidCampaignTargetException
			| InvalidMoneyAmountException
			| InvalidMoneyCurrencyException $e
		) {

			throw new CampaignFactoryException( 'Failed to create campaign from primitives.', previous: $e );
		}
	}
	// phpcs:enable

	/**
	 * Creates a new campaign with the initial version.
	 *
	 * @since 0.1.0
	 *
	 * @param EntityId $id The campaign ID.
	 * @param CampaignTitle $title The campaign title.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param CampaignTarget $target The campaign target.
	 *
	 * @return Campaign The built campaign entity.
	 */
	public function create_new(
		EntityId $id,
		CampaignTitle $title,
		bool $is_active,
		bool $is_open,
		CampaignTarget $target,
	): Campaign {

		return new Campaign(
			id: $id,
			version: EntityVersion::initial(),
			title: $title,
			is_active: $is_active,
			is_open: $is_open,
			target: $target,
		);
	}

	/**
	 * Creates a new campaign with initial version from primitive values.
	 *
	 * @since 0.1.0
	 *
	 * @param int|string $id The campaign ID.
	 * @param string $title The campaign title.
	 * @param bool $is_active Whether the campaign is active.
	 * @param bool $is_open Whether the campaign is open.
	 * @param bool $has_target Whether targeting is enabled.
	 * @param int $target_amount The target amount in minor units.
	 * @param string $target_currency The target currency (ISO 4217).
	 *
	 * @return Campaign The built campaign entity.
	 *
	 * @throws CampaignFactoryException When creating campaign from primitives fails.
	 */
	public function create_new_from_primitives(
		int|string $id,
		string $title,
		bool $is_active,
		bool $is_open,
		bool $has_target,
		int $target_amount,
		string $target_currency,
	): Campaign {

		return $this->create_from_primitives(
			id: $id,
			version: EntityVersion::initial()->get_value(),
			title: $title,
			is_active: $is_active,
			is_open: $is_open,
			has_target: $has_target,
			target_amount: $target_amount,
			target_currency: $target_currency,
		);
	}
}
