<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use Exception;
use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback;
use PHPUnit\Framework\TestCase;

class GenericCallbackTest extends TestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback::__construct
	 */
	public function testConstructor() {
		$provider = new GenericCallback(
			static function () {
			}
		);

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\GenericCallback::getValue
	 */
	public function testGetValue() {
		$provider = new GenericCallback( static function ( $attributes ) {
			if ( !isset( $attributes['mail'] ) ) {
				throw new Exception( 'missing email address' );
			}
			$parts = explode( '@', $attributes['mail'][0] );
			return strtolower( $parts[0] );
		} );
		$value = $provider->getValue( [
			'mail' => [
				'John.Doe@example.com'
			]
		],
		new HashConfig( [] ) );

		$this->assertEquals( 'john.doe', $value );
	}
}
