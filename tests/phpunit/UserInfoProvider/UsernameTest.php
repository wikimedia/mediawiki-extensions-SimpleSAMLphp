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
		$provider = new Username( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::factory
	 */
	public function testFactory() {
		$provider = Username::factory( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testBadConfigException() {
		$provider = Username::factory( new HashConfig( [] ) );
		$this->expectException( Exception::class );
		$provider->getValue( [
			'username' => [
				'John Doe'
			]
		] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testMissingAttributeException() {
		$provider = Username::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$this->expectException( Exception::class );
		$provider->getValue( [] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testInvalidUsernameException() {
		$provider = Username::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$this->expectException( Exception::class );
		$provider->getValue( [
			'username' => [
				'John Doe|invalid'
			]
		] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Username::getValue
	 */
	public function testGetValue() {
		$provider = Username::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$username = $provider->getValue( [
			'username' => [
				'John Doe'
			]
		] );

		$this->assertEquals( 'John Doe', $username );
	}
}
