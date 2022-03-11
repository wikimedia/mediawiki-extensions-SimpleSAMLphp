<?php

namespace MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor;

use Config;
use HashConfig;
use MultiConfig;

class SyncAllGroups extends GroupProcessorBase {

	/**
	 * @inheritDoc
	 */
	protected function getDefaultConfig(): Config {
		return new MultiConfig( [
			new HashConfig( [
				'syncAllGroups_GroupAttributeName' => 'groups',
				'syncAllGroups_LocallyManaged' => [ 'sysop' ],
				'syncAllGroups_GroupNameModificationCallback' => null
			] ),
			parent::getDefaultConfig()
		] );
	}

	/**
	 * Reads out the attribute that holds the user groups and applies them to the local user object
	 */
	public function doRun(): void {
		$groupsAttributeName = $this->config->get( 'syncAllGroups_GroupAttributeName' );
		$locallyManagedGroups = $this->config->get( 'syncAllGroups_LocallyManaged' );
		$groupModificationCallback = $this->config->get( 'syncAllGroups_GroupNameModificationCallback' );
		$delimiter = $this->config->get( 'groupAttributeDelimiter' );

		if ( !isset( $this->attributes[$groupsAttributeName] ) ) {
			$this->attributes[$groupsAttributeName] = [];
		}

		$samlGroups = $this->attributes[$groupsAttributeName];
		if ( $delimiter !== null ) {
			$samlGroups = explode( $delimiter, $samlGroups[0] );
		}

		$samlGroups = array_map( 'trim', $samlGroups );

		if ( is_callable( $groupModificationCallback ) ) {
			$samlGroups = array_map( $groupModificationCallback, $samlGroups );
		}

		$locallyManagedGroups = array_map( 'trim', $locallyManagedGroups );

		$currentGroups = $this->userGroupManager->getUserGroups( $this->user );
		$groupsToAdd = array_diff( $samlGroups, $currentGroups );
		foreach ( $groupsToAdd as $groupToAdd ) {
			if ( in_array( $groupToAdd, $locallyManagedGroups ) ) {
				continue;
			}
			$this->addUserToGroup( $groupToAdd );
		}

		$groupsToRemove = array_diff( $currentGroups, $samlGroups );
		foreach ( $groupsToRemove as $groupToRemove ) {
			if ( in_array( $groupToRemove, $locallyManagedGroups ) ) {
				continue;
			}
			$this->removeUserFromGroup( $groupToRemove );
		}
	}

}
