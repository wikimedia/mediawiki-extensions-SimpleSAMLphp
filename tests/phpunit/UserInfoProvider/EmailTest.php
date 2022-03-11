<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\UserInfoProvider;

use Exception;
use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::__construct
	 */
	public function testConstructor() {
		$provider = new Email();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::factory
	 */
	public function testFactory() {
		$provider = Email::factory();

		$this->assertInstanceOf(
			'MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email',
			$provider
		);
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::getValue
	 */
	public function testBadConfigException() {
		$provider = Email::factory( new HashConfig( [] ) );
		$this->expectException( Exception::class );
		$provider->getValue( [
			'mail' => [
				'someone@somewhere.com'
			]
		],
		new HashConfig( [] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::getValue
	 */
	public function testMissingAttributeException() {
		$provider = Email::factory();
		$this->expectException( Exception::class );
		$provider->getValue( [], new HashConfig( [
			'emailAttribute' => 'mail'
		] ) );
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider\Email::getValue
	 */
	public function testGetValue() {
		$provider = Email::factory();
		$mail = $provider->getValue( [
			'mail' => [
				'someone@somewhere.com'
			]
		],
		new HashConfig( [
			'emailAttribute' => 'mail'
		] ) );

		$this->assertEquals( 'someone@somewhere.com', $mail );
	}
}
