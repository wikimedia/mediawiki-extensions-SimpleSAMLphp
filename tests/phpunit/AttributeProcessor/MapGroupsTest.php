<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\AttributeProcessor;

use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use MediaWiki\Extension\SimpleSAMLphp\Tests\Dummy\SimpleSAML\Auth\Simple;
use MediaWikiTestCase;
use TestUserRegistry;

class MapGroupsTest extends MediaWikiTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\MapGroups::factory
	 */
	public function testFactory() {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\MapGroups::factory';

		$user = $this->createMock( \User::class );
		$attributes = [];
		$config = new HashConfig( [] );
		$saml = new Simple();

		$processor = $factoryMethod( $user, $attributes, $config, $saml );

		$this->assertInstanceOf(
			IAttributeProcessor::class,
			$processor,
			"Method $factoryMethod did not return an IAttributeProcessor!"
		);
	}

	/**
	 *
	 * @param array $attributes
	 * @param array $configArray
	 * @param array $initialGroups
	 * @param array $expectedGroups
	 * @dataProvider provideRunData
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\MapGroups::run
	 */
	public function testRun( $attributes, $configArray, $initialGroups, $expectedGroups ) {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\MapGroups::factory';

		$testUser = TestUserRegistry::getMutableTestUser( 'MapGroupsTestUser', $initialGroups );
		$user = $testUser->getUser();
		$config = new HashConfig( $configArray );
		$saml = new Simple();

		$processor = $factoryMethod( $user, $attributes, $config, $saml );
		$processor->run();
		$actualGroups = $user->getGroups();

		$this->assertArrayEquals(
			$expectedGroups,
			$actualGroups,
			"Groups have not been set properly!"
		);
	}

	/**
	 *
	 * @return array
	 */
	public function provideRunData() {
		return [
			'default-example' => [
				[ 'groups' => [ 'administrator', 'dontsync' ] ],
				[
					'GroupMap' => [ 'sysop' => [ 'groups' => [ 'administrator' ] ] ],
					'GroupAttributeDelimiter' => null
				],
				[ 'abc' ],
				[ 'abc', 'sysop' ]
			],
			'delimiter-example' => [
				[ 'groups' => [ 'administrator,dontsync' ] ],
				[
					'GroupMap' => [ 'sysop' => [ 'groups' => [ 'administrator' ] ] ],
					'GroupAttributeDelimiter' => ','
				],
				[ 'abc' ],
				[ 'abc', 'sysop' ]
			],
			'two-attributes' => [
				[
					'member' => [ 'saml-group-1', 'saml-group-2', 'saml-group-3' ],
					'NameId' => [ 'saml-firstname.lastname-1' ],
				],
				[
					'GroupMap' => [
						'editor' => [
							'member' => [ 'saml-group-1' ],
							'NameId' => [ 'saml-firstname.lastname-2', 'saml-firstname.lastname-3' ],
						],
						'sysop' => [
							'member' => [ 'saml-group-1' ],
							'NameId' => [ 'saml-firstname.lastname-2', 'saml-firstname.lastname-3' ],
						],
					],
					'GroupAttributeDelimiter' => null
				],
				[ 'abc' ],
				[ 'abc', 'editor', 'sysop' ]
			],
			'delete' => [
				[
					// Not in SAML attributes anymore
					// 'has-abc' => [ 'yes' ]
					'not-mapped' => [ 'dontsync' ]
				],
				[
					'GroupMap' => [ 'abc' => [ 'has-abc' => [ 'yes' ] ] ],
					'GroupAttributeDelimiter' => null
				],
				[ 'abc', 'sysop' ],
				[ 'sysop' ]
			],
			'Topic:V1k0yrv1f3ir7y6r-1' => [
				[ 'businessCategory' => [ 'B', 'N', 'Z' ] ],
				[
					'GroupMap' => [ 'staffer' => [ 'businessCategory' => [ 'B', 'N', 'Z' ] ] ],
					'GroupAttributeDelimiter' => null
				],
				[ 'abc' ],
				[ 'abc', 'staffer' ]
			],
			'Topic:V1k0yrv1f3ir7y6r-2' => [
				[ 'businessCategory' => [ 'B,N,Z' ] ],
				[
					'GroupMap' => [ 'staffer' => [ 'businessCategory' => [ 'B', 'N', 'Z' ] ] ],
					'GroupAttributeDelimiter' => ','
				],
				[ 'abc' ],
				[ 'abc', 'staffer' ]
			]
		];
	}
}
