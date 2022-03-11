<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use Exception;
use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername;
use MediaWikiIntegrationTestCase;

class LowerCasedUsernameTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::__construct
	 */
	public function testConstructor() {
		$provider = new LowerCasedUsername();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::factory
	 */
	public function testFactory() {
		$provider = LowerCasedUsername::factory();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testBadConfigException() {
		$provider = LowerCasedUsername::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [
			'username' => [
				'John Doe'
			]
		],
		new HashConfig( [] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testMissingAttributeException() {
		$provider = LowerCasedUsername::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [], new HashConfig( [
			'usernameAttribute' => 'username'
		] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testInvalidLowerCasedUsernameException() {
		$provider = LowerCasedUsername::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [
			'username' => [
				'John Doe|invalid'
			]
		], new HashConfig( [
			'usernameAttribute' => 'username'
		] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testGetValue() {
		$provider = LowerCasedUsername::factory();
		$username = $provider->getValue( [
			'username' => [
				'John Doe'
			]
		],
		new HashConfig( [
			'usernameAttribute' => 'username'
		] ) );

		$this->assertEquals( 'John doe', $username );
	}
}
