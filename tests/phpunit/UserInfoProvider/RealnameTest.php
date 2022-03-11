<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use Exception;
use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname;
use PHPUnit\Framework\TestCase;

class RealnameTest extends TestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::__construct
	 */
	public function testConstructor() {
		$provider = new Realname();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::factory
	 */
	public function testFactory() {
		$provider = Realname::factory();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::getValue
	 */
	public function testBadConfigException() {
		$provider = Realname::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [
			'realname' => [
				'John Doe'
			]
		],
		new HashConfig( [] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::getValue
	 */
	public function testMissingAttributeException() {
		$provider = Realname::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [], new HashConfig( [
			'realNameAttribute' => 'realname'
		] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Realname::getValue
	 */
	public function testGetValue() {
		$provider = Realname::factory();
		$realname = $provider->getValue( [
			'realname' => [
				'John Doe'
			]
		],
		new HashConfig( [
			'realNameAttribute' => 'realname'
		] ) );

		$this->assertEquals( 'John Doe', $realname );
	}
}
