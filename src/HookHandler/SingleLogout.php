<?php

namespace MediaWiki\Extension\SimpleSAMLphp\HookHandler;

use MediaWiki\Extension\PluggableAuth\PluggableAuthFactory;
use MediaWiki\Extension\SimpleSAMLphp\Factory\SAMLClientFactory;
use MediaWiki\User\Hook\UserLoadAfterLoadFromSessionHook;

class SingleLogout implements UserLoadAfterLoadFromSessionHook {

	/** @var PluggableAuthFactory */
	private $pluggableAuthFactory = null;

	/** @var SAMLClientFactory */
	private $samlClientFactory = null;

	/**
	 * @param PluggableAuthFactory $pluggableAuthFactory
	 * @param SAMLClientFactory $samlClientFactory
	 */
	public function __construct(
		PluggableAuthFactory $pluggableAuthFactory,
		SAMLClientFactory $samlClientFactory
	) {
		$this->pluggableAuthFactory = $pluggableAuthFactory;
		$this->samlClientFactory = $samlClientFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function onUserLoadAfterLoadFromSession( $user ) {
		$currentPlugin = $this->pluggableAuthFactory->getInstance();
		if ( $currentPlugin === false ) {
			return;
		}
		$samlClient = $this->samlClientFactory->getInstance( $currentPlugin );
		if ( !$samlClient->isAuthenticated() ) {
			$user->logout();
		}

		return true;
	}
}
