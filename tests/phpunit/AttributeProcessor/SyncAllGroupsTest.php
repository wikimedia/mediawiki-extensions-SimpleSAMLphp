<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\AttributeProcessor;

use HashConfig;
use MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\SyncAllGroups;
use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use MediaWikiIntegrationTestCase;
use TestUserRegistry;

class SyncAllGroupsTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\SyncAllGroups::factory
	 */
	public function testFactory() {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\SyncAllGroups::factory';

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
	 * @param array $expecedGroups
	 * @dataProvider provideRunData
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\SyncAllGroups::run
	 */
	public function testRun( $attributes, $configArray, $initialGroups, $expecedGroups ) {
		$testUser = TestUserRegistry::getMutableTestUser( 'MapGroupsTestUser', $initialGroups );
		$user = $testUser->getUser();
		$config = new HashConfig( $configArray );
		$groupManager = $this->getServiceContainer()->getUserGroupManager();

		$processor = new SyncAllGroups( $groupManager );
		$processor->run( $user, $attributes, $config );
		$actualGroups = $groupManager->getUserGroups( $user );

		$this->assertArrayEquals(
			$expecedGroups,
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
				[ 'groups' => [ 'administrator', 'alsosync' ] ],
				[
					'syncAllGroups_GroupAttributeName' => 'groups',
					'syncAllGroups_LocallyManaged' => [ 'abc' ],
					'groupAttributeDelimiter' => null,
					'syncAllGroups_GroupNameModificationCallback' => null
				],
				[ 'abc', 'def' ],
				[ 'abc', 'administrator', 'alsosync' ]
			],
			'delimiter-example' => [
				[ 'groups' => [ 'administrator, alsosync' ] ],
				[
					'syncAllGroups_GroupAttributeName' => 'groups',
					'syncAllGroups_LocallyManaged' => [ 'abc' ],
					'groupAttributeDelimiter' => ',',
					'syncAllGroups_GroupNameModificationCallback' => null
				],
				[ 'abc', 'def' ],
				[ 'abc', 'administrator', 'alsosync' ]
			],
			'delimiter-and-callback-example' => [
				[ 'groups' => [ 'CN=Group_1,OU=ABC,DC=someDomainController | '
					. 'CN=Group_2,OU=ABC,DC=someDomainController | '
					. 'CN=Group_3,OU=ABC,DC=someDomainController' ] ],
				[
					'syncAllGroups_GroupAttributeName' => 'groups',
					'syncAllGroups_LocallyManaged' => [],
					'groupAttributeDelimiter' => ' | ',
					'syncAllGroups_GroupNameModificationCallback' => static function ( $origGroupName ){
						return preg_replace( '#^CN=(.*?),OU=.*$#', '$1', $origGroupName );
					}
				],
				[ 'Group_1', 'Group_1000' ],
				[ 'Group_1', 'Group_2', 'Group_3' ]
			],
			'T297493' => [
				[ 'not_the_configured_attribute' ],
				[
					'syncAllGroups_GroupAttributeName' => 'groups'
				],
				[ 'Group_1', 'sysop' ],
				[ 'sysop' ]
			]
		];
	}
}
