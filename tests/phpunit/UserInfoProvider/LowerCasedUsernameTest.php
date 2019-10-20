<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use Exception;
use MediaWikiTestCase;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername;
use HashConfig;

class LowerCasedUsernameTest extends MediaWikiTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::__construct
	 */
	public function testConstructor() {
		$provider = new LowerCasedUsername( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::factory
	 */
	public function testFactory() {
		$provider = LowerCasedUsername::factory( new HashConfig( [] ) );

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testBadConfigException() {
		$provider = LowerCasedUsername::factory( new HashConfig( [] ) );
		$this->expectException( Exception::class );
		$provider->getValue( [
			'username' => [
				'John Doe'
			]
		] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testMissingAttributeException() {
		$provider = LowerCasedUsername::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$this->expectException( Exception::class );
		$provider->getValue( [] );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testInvalidLowerCasedUsernameException() {
		$provider = LowerCasedUsername::factory( new HashConfig( [
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
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\LowerCasedUsername::getValue
	 */
	public function testGetValue() {
		$provider = LowerCasedUsername::factory( new HashConfig( [
			'UsernameAttribute' => 'username'
		] ) );
		$username = $provider->getValue( [
			'username' => [
				'John Doe'
			]
		] );

		$this->assertEquals( 'John doe', $username );
	}
}
