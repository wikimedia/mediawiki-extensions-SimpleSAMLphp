<?php

namespace MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor;

use Config;
use HashConfig;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserGroupManager;

abstract class GroupProcessorBase extends Base {

	/**
	 * @var UserGroupManager
	 */
	protected $userGroupManager = null;

	/**
	 * @param UserGroupManager|null $userGroupManager
	 */
	public function __construct( $userGroupManager = null ) {
		parent::__construct();
		$this->userGroupManager = $userGroupManager;
		if ( $userGroupManager === null ) {
			$this->userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function getDefaultConfig(): Config {
		return new HashConfig( [
			// If the SAML attribute for groups is not an array but a CSV string,
			// this can be set to the appropriate delimiter (e.g. ',')
			'groupAttributeDelimiter' => null
		] );
	}

	/**
	 * @param string $groupToAdd
	 * @return void
	 */
	protected function addUserToGroup( $groupToAdd ) {
		$this->logger->debug( "Adding '{$this->user->getName()}' from group '$groupToAdd'" );
		$this->userGroupManager->addUserToGroup( $this->user, $groupToAdd );
	}

	/**
	 * @param string $groupToRemove
	 * @return void
	 */
	protected function removeUserFromGroup( $groupToRemove ) {
		$this->logger->debug( "Removing '{$this->user->getName()}' from group '$groupToRemove'" );
		$this->userGroupManager->removeUserFromGroup( $this->user, $groupToRemove );
	}

}
