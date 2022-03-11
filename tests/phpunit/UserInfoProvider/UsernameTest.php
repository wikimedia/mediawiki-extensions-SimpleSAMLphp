<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use Exception;
use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username;
use MediaWikiIntegrationTestCase;

class UsernameTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::__construct
	 */
	public function testConstructor() {
		$provider = new Username();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::factory
	 */
	public function testFactory() {
		$provider = Username::factory();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testBadConfigException() {
		$provider = Username::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [
			'username' => [
				'John Doe'
			]
		],
		new HashConfig( [] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testMissingAttributeException() {
		$provider = Username::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [], new HashConfig( [
			'usernameAttribute' => 'username'
		] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testInvalidUsernameException() {
		$provider = Username::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [
			'username' => [
				'John Doe|invalid'
			]
		],
		new HashConfig( [
			'usernameAttribute' => 'username'
		] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testGetValue() {
		$provider = Username::factory();
		$username = $provider->getValue( [
			'username' => [
				'John Doe'
			]
		],
		new HashConfig( [
			'usernameAttribute' => 'username'
		] ) );

		$this->assertEquals( 'John Doe', $username );
	}
}
