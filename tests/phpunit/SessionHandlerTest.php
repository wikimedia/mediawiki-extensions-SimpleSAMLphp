<?php

// phpcs:disable MediaWiki.Files.ClassMatchesFilename.NotMatch
// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound


namespace SimpleSAML;

class Logger {
	public static function __callStatic( $name, $args ) {
	}
}
class Configuration {
	public function getString( string $key, string $default = '' ): string {
		return $default;
	}
}

namespace SimpleSAML\Auth;

class Simple {
	public function __construct( string $authSourceId ) {
	}

	public function isAuthenticated(): bool {
		return true;
	}

	public function getAuthData( string $key ): ?string {
		return null;
	}

	public function logout( array $params = [] ): void {
	}

	public function getAttributes(): array {
		return [];
	}
}

// The SessionHandler class is defined in this file
require_once __DIR__ . '/../../_simplesamlphp/public/api/session.php';

namespace MediaWiki\Extension\SimpleSAMLphp\Tests;

use MediaWikiIntegrationTestCase;
use SimpleSAMLphp\Integration\MediaWiki\Extension\SimpleSAMLphp\SessionHandler;

/**
 * @covers \SimpleSAMLphp\Integration\MediaWiki\Extension\SimpleSAMLphp\SessionHandler
 */
class SessionHandlerTest extends MediaWikiIntegrationTestCase {

	private function createHandler(
		array $configOverrides = [],
		array $serverVarOverrides = []
	): SessionHandler {
		$configData = $configOverrides + [
			'mediawiki.sessionapi.token' => 'valid-token',
			'mediawiki.sessionapi.allowedcallers' => '127.0.0.1/32',
		];

		$config = $this->createMock( \SimpleSAML\Configuration::class );
		$config->method( 'getString' )
			->willReturnCallback( static function ( $key, $default = null ) use ( $configData ) {
				return $configData[$key] ?? $default;
			} );

		$serverVars = $serverVarOverrides + [
			'HTTP_X_SIMPLESAMLPHP_SESSION_API_TOKEN' => 'valid-token',
			'REMOTE_ADDR' => '127.0.0.1',
		];

		return new SessionHandler( $config, $serverVars );
	}

	public function testConstructor() {
		$handler = $this->createHandler();
		$this->assertInstanceOf( SessionHandler::class, $handler );
	}

	public function testGetContentType() {
		$handler = $this->createHandler();
		$this->assertEquals( 'application/json', $handler->getContentType() );
	}

	public function testDefaultStatusCode() {
		$handler = $this->createHandler();
		$this->assertEquals( 200, $handler->getStatusCode() );
	}

	public function testUnauthorizedWhenTokenMissing() {
		$handler = $this->createHandler( [], [
			'HTTP_X_SIMPLESAMLPHP_SESSION_API_TOKEN' => '',
		] );

		$handler->execute( [ 'action' => 'status' ] );

		$this->assertEquals( 403, $handler->getStatusCode() );
		$body = json_decode( $handler->getResponseBody(), true );
		$this->assertEquals( 'Unauthorized', $body['error'] );
	}

	public function testUnauthorizedWhenTokenInvalid() {
		$handler = $this->createHandler( [], [
			'HTTP_X_SIMPLESAMLPHP_SESSION_API_TOKEN' => 'wrong-token',
		] );

		$handler->execute( [ 'action' => 'status' ] );

		$this->assertEquals( 403, $handler->getStatusCode() );
		$body = json_decode( $handler->getResponseBody(), true );
		$this->assertEquals( 'Unauthorized', $body['error'] );
	}

	public function testUnauthorizedWhenTokenNotConfigured() {
		$handler = $this->createHandler( [
			'mediawiki.sessionapi.token' => '',
		] );

		$handler->execute( [ 'action' => 'status' ] );

		$this->assertEquals( 403, $handler->getStatusCode() );
	}

	public function testUnauthorizedWhenCallerNotAllowed() {
		$handler = $this->createHandler(
			[ 'mediawiki.sessionapi.allowedcallers' => '10.0.0.0/8' ],
			[ 'REMOTE_ADDR' => '192.168.1.1' ]
		);

		$handler->execute( [ 'action' => 'status' ] );

		$this->assertEquals( 403, $handler->getStatusCode() );
	}

	public function testLocalhostAlwaysAllowed() {
		$handler = $this->createHandler(
			[ 'mediawiki.sessionapi.allowedcallers' => '10.0.0.0/8' ],
			[ 'REMOTE_ADDR' => '127.0.0.1' ]
		);

		$handler->execute( [ 'action' => 'status' ] );

		// Should not be 403 - localhost is always allowed
		$this->assertNotEquals( 403, $handler->getStatusCode() );
	}

	public function testUnknownActionReturns400() {
		$handler = $this->createHandler();

		$handler->execute( [ 'action' => 'unknown-action' ] );

		$this->assertEquals( 400, $handler->getStatusCode() );
		$body = json_decode( $handler->getResponseBody(), true );
		$this->assertEquals( 'Unknown action', $body['error'] );
	}

	public function testGetResponseBodyReturnsJson() {
		$handler = $this->createHandler( [], [
			'HTTP_X_SIMPLESAMLPHP_SESSION_API_TOKEN' => 'wrong',
		] );

		$handler->execute( [ 'action' => 'status' ] );

		$body = $handler->getResponseBody();
		$decoded = json_decode( $body, true );
		$this->assertNotNull( $decoded, 'Response body should be valid JSON' );
	}

	public function testCallerAllowedByCIDR() {
		$handler = $this->createHandler(
			[ 'mediawiki.sessionapi.allowedcallers' => '192.168.0.0/16' ],
			[ 'REMOTE_ADDR' => '192.168.1.50' ]
		);

		$handler->execute( [ 'action' => 'unknown-action' ] );

		// If caller is allowed, we should get 400 (unknown action) not 403
		$this->assertEquals( 400, $handler->getStatusCode() );
	}

	public function testMultipleCIDRRanges() {
		$handler = $this->createHandler(
			[ 'mediawiki.sessionapi.allowedcallers' => '10.0.0.0/8, 172.16.0.0/12' ],
			[ 'REMOTE_ADDR' => '172.16.5.10' ]
		);

		$handler->execute( [ 'action' => 'unknown-action' ] );

		$this->assertEquals( 400, $handler->getStatusCode() );
	}
}
