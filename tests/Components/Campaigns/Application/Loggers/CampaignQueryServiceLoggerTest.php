<?php

declare(strict_types=1);

namespace Fundrik\Core\Tests\Components\Campaigns\Application\Loggers;

use Fundrik\Core\Components\Campaigns\Application\Loggers\CampaignQueryServiceLogger;
use Fundrik\Core\Components\Campaigns\Application\Services\CampaignQueryService;
use Fundrik\Core\Tests\Fixtures\FakeCampaignRepositoryException;
use Fundrik\Core\Tests\MockeryTestCase;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

#[CoversClass( CampaignQueryServiceLogger::class )]
final class CampaignQueryServiceLoggerTest extends MockeryTestCase {

	private LoggerInterface&MockInterface $psr_logger;
	private CampaignQueryServiceLogger $logger;

	protected function setUp(): void {

		parent::setUp();

		$this->psr_logger = Mockery::mock( LoggerInterface::class );
		$this->logger = new CampaignQueryServiceLogger( $this->psr_logger );
	}

	#[Test]
	public function log_find_by_id_failed_repository_writes_error_with_exception_and_id(): void {

		$e = new FakeCampaignRepositoryException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaign by ID failed (repository error).',
				$this->log_context(
					[
						'operation' => 'find_campaign_by_id',
						'id' => 7,
						'exception' => $e,
						'exception_class' => $e::class,
					],
				),
			);

		$this->logger->log_find_by_id_failed_repository( id: 7, e: $e );
	}

	#[Test]
	public function log_find_all_failed_repository_writes_error_with_exception(): void {

		$e = new FakeCampaignRepositoryException();

		$this->psr_logger
			->shouldReceive( 'error' )
			->once()
			->with(
				'Finding campaigns failed (repository error).',
				$this->log_context(
					[
						'operation' => 'find_all_campaigns',
						'exception' => $e,
						'exception_class' => $e::class,
					],
				),
			);

		$this->logger->log_find_all_failed_repository( $e );
	}

	private function log_context( array $expected ): \Mockery\Matcher\Closure {

		return $this->array_has(
			$expected + [
				'service_class' => CampaignQueryService::class,
				'logger_class' => CampaignQueryServiceLogger::class,
				'component' => 'campaigns',
				'layer' => 'application',
				'system' => 'core',
			],
		);
	}
}
