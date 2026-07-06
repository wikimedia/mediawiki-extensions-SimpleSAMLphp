<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests;

use Exception;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\HttpSAMLClient;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Request\FauxRequest;
use MediaWikiIntegrationTestCase;
use MWHttpRequest;
use Psr\Log\NullLogger;
use StatusValue;

/**
 * @covers \MediaWiki\Extension\SimpleSAMLphp\HttpSAMLClient
 */
class HttpSAMLClientTest extends MediaWikiIntegrationTestCase {

	private function getDefaultConfig(): HashConfig {
		return new HashConfig( [
			'baseUrl' => 'http://localhost/simplesaml',
			'sessionAPItoken' => 'test-token-123',
			'sessionIdCookieName' => 'SimpleSAMLSessionID',
			'authTokenCookieName' => 'SimpleSAMLAuthToken',
		] );
	}

	private function createClient(
		?HashConfig $config = null,
		?HttpRequestFactory $httpRequestFactory = null,
		?FauxRequest $webRequest = null
	): HttpSAMLClient {
		$config = $config ?? $this->getDefaultConfig();
		$httpRequestFactory = $httpRequestFactory ?? $this->createMock( HttpRequestFactory::class );
		$webRequest = $webRequest ?? new FauxRequest();

		$client = new HttpSAMLClient( 'default-sp', $config, $httpRequestFactory, $webRequest );
		$client->setLogger( new NullLogger() );
		return $client;
	}

	private function createMockHttpRequest( string $responseBody, int $statusCode = 200, bool $isOK = true ) {
		$mockRequest = $this->createMock( MWHttpRequest::class );
		$mockRequest->method( 'execute' )
			->willReturn( $isOK ? StatusValue::newGood() : StatusValue::newFatal( 'http-error' ) );
		$mockRequest->method( 'getStatus' )
			->willReturn( $statusCode );
		$mockRequest->method( 'getContent' )
			->willReturn( $responseBody );
		$mockRequest->method( 'setHeader' )
			->willReturn( null );
		return $mockRequest;
	}

	public function testConstructor() {
		$client = $this->createClient();
		$this->assertInstanceOf( HttpSAMLClient::class, $client );
	}

	public function testGetAttributesWhenAuthenticated() {
		$attributes = [ 'uid' => [ 'testuser' ], 'mail' => [ 'test@example.com' ] ];
		$responseBody = json_encode( [
			'authenticated' => true,
			'attributes' => $attributes,
		] );

		$mockRequest = $this->createMockHttpRequest( $responseBody );
		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )->willReturn( $mockRequest );

		$client = $this->createClient( null, $httpFactory );
		$result = $client->getAttributes();

		$this->assertEquals( $attributes, $result );
	}

	public function testGetAttributesWhenNotAuthenticated() {
		$responseBody = json_encode( [
			'authenticated' => false,
			'attributes' => [],
		] );

		$mockRequest = $this->createMockHttpRequest( $responseBody );
		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )->willReturn( $mockRequest );

		$client = $this->createClient( null, $httpFactory );
		$result = $client->getAttributes();

		$this->assertEquals( [], $result );
	}

	public function testGetAttributesThrowsOnHttpFailure() {
		$mockRequest = $this->createMockHttpRequest( 'error', 500, false );
		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )->willReturn( $mockRequest );

		$client = $this->createClient( null, $httpFactory );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Failed to communicate with SAML Service Provider' );
		$client->getAttributes();
	}

	public function testGetAttributesThrowsOnInvalidJson() {
		$mockRequest = $this->createMockHttpRequest( 'not json' );
		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )->willReturn( $mockRequest );

		$client = $this->createClient( null, $httpFactory );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid response from SAML Service Provider' );
		$client->getAttributes();
	}

	public function testGetAttributesThrowsOnApiError() {
		$responseBody = json_encode( [ 'error' => 'Unauthorized' ] );
		$mockRequest = $this->createMockHttpRequest( $responseBody );
		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )->willReturn( $mockRequest );

		$client = $this->createClient( null, $httpFactory );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Error from SAML Service Provider' );
		$client->getAttributes();
	}

	public function testApiCallForwardsCookies() {
		$responseBody = json_encode( [ 'authenticated' => false, 'attributes' => [] ] );

		$mockRequest = $this->createMock( MWHttpRequest::class );
		$mockRequest->method( 'execute' )->willReturn( StatusValue::newGood() );
		$mockRequest->method( 'getStatus' )->willReturn( 200 );
		$mockRequest->method( 'getContent' )->willReturn( $responseBody );

		$cookieHeader = null;
		$mockRequest->method( 'setHeader' )
			->willReturnCallback( static function ( $name, $value ) use ( &$cookieHeader ) {
				if ( $name === 'Cookie' ) {
					$cookieHeader = $value;
				}
			} );

		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )->willReturn( $mockRequest );

		$webRequest = new FauxRequest();
		$webRequest->setCookies( [
			'SimpleSAMLSessionID' => 'session-abc',
			'SimpleSAMLAuthToken' => 'auth-xyz',
		], '' );

		$client = $this->createClient( null, $httpFactory, $webRequest );
		$client->getAttributes();

		$this->assertNotNull( $cookieHeader );
		$this->assertStringContainsString( 'SimpleSAMLSessionID=session-abc', $cookieHeader );
		$this->assertStringContainsString( 'SimpleSAMLAuthToken=auth-xyz', $cookieHeader );
	}

	public function testApiCallSetsTokenHeader() {
		$responseBody = json_encode( [ 'authenticated' => false, 'attributes' => [] ] );

		$mockRequest = $this->createMock( MWHttpRequest::class );
		$mockRequest->method( 'execute' )->willReturn( StatusValue::newGood() );
		$mockRequest->method( 'getStatus' )->willReturn( 200 );
		$mockRequest->method( 'getContent' )->willReturn( $responseBody );

		$tokenHeader = null;
		$mockRequest->method( 'setHeader' )
			->willReturnCallback( static function ( $name, $value ) use ( &$tokenHeader ) {
				if ( $name === 'X-SIMPLESAMLPHP-SESSION-API-TOKEN' ) {
					$tokenHeader = $value;
				}
			} );

		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )->willReturn( $mockRequest );

		$client = $this->createClient( null, $httpFactory );
		$client->getAttributes();

		$this->assertEquals( 'test-token-123', $tokenHeader );
	}

	public function testApiCallBuildsCorrectUrl() {
		$responseBody = json_encode( [ 'authenticated' => true, 'attributes' => [] ] );

		$mockRequest = $this->createMockHttpRequest( $responseBody );
		$capturedUrl = null;

		$httpFactory = $this->createMock( HttpRequestFactory::class );
		$httpFactory->method( 'create' )
			->willReturnCallback( static function ( $url ) use ( $mockRequest, &$capturedUrl ) {
				$capturedUrl = $url;
				return $mockRequest;
			} );

		$client = $this->createClient( null, $httpFactory );
		$client->getAttributes();

		$this->assertNotNull( $capturedUrl );
		$this->assertStringStartsWith( 'http://localhost/simplesaml/api/session.php?', $capturedUrl );
		$this->assertStringContainsString( 'action=status', $capturedUrl );
		$this->assertStringContainsString( 'authSourceId=default-sp', $capturedUrl );
	}
}
