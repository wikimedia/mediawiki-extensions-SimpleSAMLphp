<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\AttributeProcessor;

use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\MapGroups;
use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use MediaWikiIntegrationTestCase;
use TestUserRegistry;

class MapGroupsTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\MapGroups::factory
	 */
	public function testFactory() {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\MapGroups::factory';

		$processor = $factoryMethod();

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
		$testUser = TestUserRegistry::getMutableTestUser( 'MapGroupsTestUser', $initialGroups );
		$user = $testUser->getUser();
		$config = new HashConfig( $configArray );
		$groupManager = $this->getServiceContainer()->getUserGroupManager();

		$processor = new MapGroups( $groupManager );
		$processor->run( $user, $attributes, $config );
		$actualGroups = $groupManager->getUserGroups( $user );

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
	public static function provideRunData() {
		return [
			'default-example' => [
				[ 'groups' => [ 'administrator', 'dontsync' ] ],
				[
					'mapGroups_Map' => [ 'sysop' => [ 'groups' => [ 'administrator' ] ] ],
					'groupAttributeDelimiter' => null
				],
				[ 'abc' ],
				[ 'abc', 'sysop' ]
			],
			'delimiter-example' => [
				[ 'groups' => [ 'administrator,dontsync' ] ],
				[
					'mapGroups_Map' => [ 'sysop' => [ 'groups' => [ 'administrator' ] ] ],
					'groupAttributeDelimiter' => ','
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
					'mapGroups_Map' => [
						'editor' => [
							'member' => [ 'saml-group-1' ],
							'NameId' => [ 'saml-firstname.lastname-2', 'saml-firstname.lastname-3' ],
						],
						'sysop' => [
							'member' => [ 'saml-group-1' ],
							'NameId' => [ 'saml-firstname.lastname-2', 'saml-firstname.lastname-3' ],
						],
					],
					'groupAttributeDelimiter' => null
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
					'mapGroups_Map' => [ 'abc' => [ 'has-abc' => [ 'yes' ] ] ],
					'groupAttributeDelimiter' => null
				],
				[ 'abc', 'sysop' ],
				[ 'sysop' ]
			],
			'Topic:V1k0yrv1f3ir7y6r-1' => [
				[ 'businessCategory' => [ 'B', 'N', 'Z' ] ],
				[
					'mapGroups_Map' => [ 'staffer' => [ 'businessCategory' => [ 'B', 'N', 'Z' ] ] ],
					'groupAttributeDelimiter' => null
				],
				[ 'abc' ],
				[ 'abc', 'staffer' ]
			],
			'Topic:V1k0yrv1f3ir7y6r-2' => [
				[ 'businessCategory' => [ 'B,N,Z' ] ],
				[
					'mapGroups_Map' => [ 'staffer' => [ 'businessCategory' => [ 'B', 'N', 'Z' ] ] ],
					'groupAttributeDelimiter' => ','
				],
				[ 'abc' ],
				[ 'abc', 'staffer' ]
			],
			'T304951-regex-positive' => [
				[
					'saml-groups' => [ 'saml-group-1', 'saml-group-2' ]
				],
				[
					'mapGroups_Map' => [ 'wiki_group_from_regex' => [
						'saml-groups' => [ static function ( $samlProvidedGroups ) {
							return count( preg_grep( '/^saml-group-\d+$/', $samlProvidedGroups ) );
						} ]
					] ]
				],
				[ 'sysop' ],
				[ 'sysop', 'wiki_group_from_regex' ]
			],
			'T304951-regex-negative' => [
				[
					'saml-groups' => [ 'saml-group-a', 'saml-group-b' ]
				],
				[
					'mapGroups_Map' => [ 'wiki_group_from_regex' => [
						'saml-groups' => [ static function ( $samlProvidedGroups ) {
							return count( preg_grep( '/^saml-group-\d+$/', $samlProvidedGroups ) );
						} ]
					] ]
				],
				[ 'sysop' ],
				[ 'sysop' ]
			],
			'T304951-regex-remove' => [
				[
					'saml-groups' => [ 'saml-group-a', 'saml-group-b' ]
				],
				[
					'mapGroups_Map' => [ 'wiki_group_from_regex' => [
						'saml-groups' => [ static function ( $samlProvidedGroups ) {
							return count( preg_grep( '/^saml-group-\d+$/', $samlProvidedGroups ) );
						} ]
					] ]
				],
				[ 'sysop', 'wiki_group_from_regex' ],
				[ 'sysop' ]
			],
			'T304950-will-add-with-addonly' => [
				[
					'saml-groups' => [ 'saml-group-1' ]
				],
				[
					'mapGroups_Map' => [
						'addonly_wikigroup' => [
							'saml-groups' => [ 'saml-group-1' ]
						]
					],
					'mapGroups_AddOnlyGroups' => [ 'addonly_wikigroup' ]
				],
				[ 'initial_wiki_group_1' ],
				[ 'initial_wiki_group_1', 'addonly_wikigroup' ]
			],
			'T304950-wont-remove-with-addonly' => [
				[
					'saml-groups' => [ 'saml-group-999' ]
				],
				[
					'mapGroups_Map' => [
						'addonly_wikigroup' => [
							'saml-groups' => [ 'saml-group-1' ]
						]
					],
					'mapGroups_AddOnlyGroups' => [ 'addonly_wikigroup' ]
				],
				[ 'addonly_wikigroup', 'initial_wiki_group_1' ],
				[ 'addonly_wikigroup', 'initial_wiki_group_1' ]
			]

		];
	}
}
