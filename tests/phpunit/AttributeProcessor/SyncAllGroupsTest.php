<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\AttributeProcessor;

use MediaWikiTestCase;
use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use HashConfig;
use TestUserRegistry;

class SyncAllGroupsTest extends MediaWikiTestCase {

	public function setUp() : void {
		parent::setUp();
		if ( !class_exists( \SimpleSAML\Auth\Simple::class ) ) {
			$this->markTestSkipped( 'SimpleSAMLphp must be installed' );
		}
	}

	/**
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\SyncAllGroups::factory
	 */
	public function testFactory() {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\SyncAllGroups::factory';

		$user = $this->createMock( \User::class );
		$attributes = [];
		$config = new HashConfig( [] );
		$saml = $this->createMock( \SimpleSAML\Auth\Simple::class );

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
	 * @param array $expecedGroups
	 * @dataProvider provideRunData
	 * @covers MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor\SyncAllGroups::run
	 */
	public function testRun( $attributes, $configArray, $initialGroups, $expecedGroups ) {
		$factoryMethod =
			'MediaWiki\\Extension\\SimpleSAMLphp\\AttributeProcessor\\SyncAllGroups::factory';

		$testUser = TestUserRegistry::getMutableTestUser( 'MapGroupsTestUser', $initialGroups );
		$user = $testUser->getUser();
		$config = new HashConfig( $configArray );
		$saml = $this->createMock( \SimpleSAML\Auth\Simple::class );

		$processor = $factoryMethod( $user, $attributes, $config, $saml );
		$processor->run();
		$actualGroups = $user->getGroups();

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
	public function provideRunData() {
		return [
			'default-example' => [
				[ 'groups' => [ 'administrator', 'alsosync' ] ],
				[
					'SyncAllGroups_GroupAttributeName' => 'groups',
					'SyncAllGroups_LocallyManaged' => [ 'abc' ],
					'GroupAttributeDelimiter' => null,
					'SyncAllGroups_GroupNameModificationCallback' => null
				],
				[ 'abc', 'def' ],
				[ 'abc', 'administrator', 'alsosync' ]
			],
			'delimiter-example' => [
				[ 'groups' => [ 'administrator, alsosync' ] ],
				[
					'SyncAllGroups_GroupAttributeName' => 'groups',
					'SyncAllGroups_LocallyManaged' => [ 'abc' ],
					'GroupAttributeDelimiter' => ',',
					'SyncAllGroups_GroupNameModificationCallback' => null
				],
				[ 'abc', 'def' ],
				[ 'abc', 'administrator', 'alsosync' ]
			],
			'delimiter-and-callback-example' => [
				[ 'groups' => [ 'CN=Group_1,OU=ABC,DC=someDomainController | '
					. 'CN=Group_2,OU=ABC,DC=someDomainController | '
					. 'CN=Group_3,OU=ABC,DC=someDomainController' ] ],
				[
					'SyncAllGroups_GroupAttributeName' => 'groups',
					'SyncAllGroups_LocallyManaged' => [],
					'GroupAttributeDelimiter' => ' | ',
					'SyncAllGroups_GroupNameModificationCallback' => function ( $origGroupName ){
						return preg_replace( '#^CN=(.*?),OU=.*$#', '$1', $origGroupName );
					}
				],
				[ 'Group_1', 'Group_1000' ],
				[ 'Group_1', 'Group_2', 'Group_3' ]
			]
		];
	}
}
