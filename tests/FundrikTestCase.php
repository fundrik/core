<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests;

use DateTimeImmutable;
use Fundrik\Core\Components\Campaigns\Application\ReadModels\CampaignDetails;
use Fundrik\Core\Components\Campaigns\Domain\Campaign;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTarget;
use Fundrik\Core\Components\Campaigns\Domain\CampaignTitle;
use Fundrik\Core\Components\Donations\Application\ReadModels\DonationDetails;
use Fundrik\Core\Components\Donations\Domain\Donation;
use Fundrik\Core\Components\Donations\Domain\DonationFactory;
use Fundrik\Core\Components\Shared\Domain\EntityId;
use Fundrik\Core\Components\Shared\Domain\EntityVersion;
use Fundrik\Core\Components\Shared\Domain\Money;
use Fundrik\Core\Components\Shared\Domain\UtcDateTime;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class FundrikTestCase extends PHPUnitTestCase {

	/**
	 * Returns valid campaign details for tests with optional field overrides.
	 */
	protected function make_campaign_details(
		int|string $id = 1,
		string $title = 'Test Campaign',
		bool $can_receive_donations = true,
		string $currency_code = 'RUB',
		?int $target_amount = 100,
		?UtcDateTime $created_at = null,
		?UtcDateTime $updated_at = null,
	): CampaignDetails {

		return new CampaignDetails(
			id: $id,
			title: $title,
			can_receive_donations: $can_receive_donations,
			currency_code: $currency_code,
			target_amount: $target_amount,
			created_at: $created_at ?? $this->make_utc_date_time( '2026-03-01T10:00:00+00:00' ),
			updated_at: $updated_at,
		);
	}

	/**
	 * Returns valid donation details for tests with optional field overrides.
	 */
	protected function make_donation_details(
		int|string $id = 5_001,
		int|string $campaign_id = 901,
		int $amount = 1_000,
		string $currency_code = 'RUB',
		string $status = 'pending',
		?UtcDateTime $created_at = null,
		?UtcDateTime $updated_at = null,
	): DonationDetails {

		return new DonationDetails(
			id: $id,
			campaign_id: $campaign_id,
			amount: $amount,
			currency_code: $currency_code,
			status: $status,
			created_at: $created_at ?? $this->make_utc_date_time( '2026-03-01T10:00:00+00:00' ),
			updated_at: $updated_at,
		);
	}

	/**
	 * Returns a UTC timestamp for tests.
	 */
	protected function make_utc_date_time( string $value ): UtcDateTime {

		return UtcDateTime::create( new DateTimeImmutable( $value ) );
	}

	/**
	 * Returns a valid campaign for tests with optional field overrides.
	 */
	protected function make_campaign(
		int|string $id = 1,
		string $title = 'Test Campaign',
		bool $is_open = true,
		string $currency_code = 'RUB',
		?int $target_amount = 100,
	): Campaign {

		return new Campaign(
			id: EntityId::create( $id ),
			version: EntityVersion::initial(),
			title: CampaignTitle::create( $title ),
			is_open: $is_open,
			target: CampaignTarget::create( $currency_code, $target_amount ),
		);
	}

	/**
	 * Returns a valid pending donation for tests with optional field overrides.
	 */
	protected function make_pending_donation(
		int|string $id = 5_001,
		int|string|EntityId $campaign_id = 901,
		int $amount = 1_000,
		string $currency = 'RUB',
	): Donation {

		$factory = new DonationFactory();

		return $factory->create_pending(
			id: EntityId::create( $id ),
			campaign_id: $campaign_id instanceof EntityId ? $campaign_id : EntityId::create( $campaign_id ),
			money: Money::create( $amount, $currency ),
		);
	}

	/**
	 * Returns a valid captured donation for tests with optional field overrides.
	 */
	protected function make_captured_donation(
		int|string $id = 5_001,
		int|string|EntityId $campaign_id = 901,
		int $amount = 1_000,
		string $currency = 'RUB',
	): Donation {

		return $this->make_pending_donation(
			id: $id,
			campaign_id: $campaign_id,
			amount: $amount,
			currency: $currency,
		)->capture();
	}
}
