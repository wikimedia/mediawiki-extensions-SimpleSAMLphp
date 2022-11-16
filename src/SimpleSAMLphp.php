<?php

/*
 * Copyright (c) 2014 The MITRE Corporation
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @author Cindy Cicalese <cindom@gmail.com>
 * @author Mark A. Hershberger <mah@nichework.com>
 */

namespace MediaWiki\Extension\SimpleSAMLphp;

use Exception;
use HashConfig;
use MediaWiki\Extension\PluggableAuth\PluggableAuth;
use MediaWiki\Extension\SimpleSAMLphp\Factory\MandatoryUserInfoProviderFactory;
use MediaWiki\Extension\SimpleSAMLphp\Factory\SAMLClientFactory;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use TitleFactory;

class SimpleSAMLphp extends PluggableAuth {

	/**
	 *
	 * @var array
	 */
	private $attributes = [];

	/**
	 * @var TitleFactory
	 */
	private $titleFactory = null;

	/**
	 * @var UserFactory
	 */
	private $userFactory = null;

		/**
		 * @var SAMLClientFactory
		 */
	private $samlClientFactory = null;

	/**
	 * @var SAMLClient
	 */
	private $samlClient = null;

	/**
	 * @param TitleFactory $titleFactory
	 * @param UserFactory $userFactory
	 * @param SAMLClientFactory $samlClientFactory
	 * @param MandatoryUserInfoProviderFactory $userInfoProviderFactory
	 */
	public function __construct(
		TitleFactory $titleFactory,
		UserFactory $userFactory,
		SAMLClientFactory $samlClientFactory,
		MandatoryUserInfoProviderFactory $userInfoProviderFactory
	) {
		$this->titleFactory = $titleFactory;
		$this->userFactory = $userFactory;
		$this->samlClientFactory = $samlClientFactory;
		$this->userInfoProviderFactory = $userInfoProviderFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function init( string $configId, ?array $data ): void {
		parent::init( $configId, $data );

		$this->samlClient = $this->samlClientFactory->getInstance( $this );

		// Set some defaults
		$defaultUserinfoProviders = [
			// 'providerKey' => 'factoryKey'
			'username' => 'username',
			'realname' => 'realname',
			'email' => 'email'
		];
		$this->data['userinfoProviders'] = array_merge(
			$defaultUserinfoProviders,
			$this->data['userinfoProviders'] ?? []
		);

		$this->data['attributeProcessors'] =
			$this->data['attributeProcessors']
			?? [ 'groupsync-mapped' ];
	}

	/**
	 * @since 1.0
	 *
	 * @inheritDoc
	 */
	public function authenticate(
		&$userId, &$username, &$realname, &$email, &$errorMessage = null
	 ): bool {
		try {
			$this->samlClient->requireAuth();
		} catch ( Exception $e ) {
			$errorMessage = $e->getMessage();
			$this->logger->error( $errorMessage );
			return false;
		}
		$this->attributes = $this->samlClient->getAttributes();

		try {
			$username = $this->makeValueFromAttributes( 'username' );
			$realname = $this->makeValueFromAttributes( 'realname' );
			$email = $this->makeValueFromAttributes( 'email' );
			$id = $this->userFactory->newFromName( $username )->getId();
			if ( $id ) {
				$userId = $id;
			}
		} catch ( Exception $ex ) {
			$errorMessage = $ex->getMessage();
			$this->logger->error( $errorMessage );
			return false;
		}

		return true;
	}

	/**
	 * Get a value for a particular mandatory user info
	 * @param string $providerKey
	 * @return string
	 * @throws Exception
	 */
	private function makeValueFromAttributes( $providerKey ) {
		$factoryKey = $this->data['userinfoProviders'][$providerKey];
		$provider = $this->userInfoProviderFactory->getInstance( $factoryKey );
		$this->logger->debug(
			"Using '{factoryKey}' (class '{clazz}') for '{providerKey}' UserInfoProvider",
			[
				'clazz' => get_class( $provider ),
				'factoryKey' => $factoryKey,
				'providerKey' => $providerKey
			]
		);
		return $provider->getValue( $this->attributes, new HashConfig( $this->data ) );
	}

	/**
	 * @since 1.0
	 *
	 * @inheritDoc
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function deauthenticate( UserIdentity &$user ): void {
		$returnto = null;
		if ( isset( $_REQUEST['returnto'] ) ) {
			$title = $this->titleFactory->newFromText( $_REQUEST['returnto'] );
			if ( $title !== null ) {
				$returnto = $title->getFullURL();
			}
		}
		if ( $returnto === null ) {
			$returnto = $this->titleFactory->newMainPage()->getFullURL();
		}
		$this->logger->debug( "Deauthenticate {$user->getName()} with returnTo '$returnto'" );
		$this->samlClient->logout( $returnto );
	}

	/**
	 * @since 1.0
	 *
	 * @inheritDoc
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function saveExtraAttributes( $userId ): void {
		// intentionally left blank
	}

	/**
	 * Required for `PluggableAuthPopulateGroups` hookhandler
	 * Maybe worth promoting into "Extension:PluggableAuth"
	 * @return array
	 */
	public function getConfig(): array {
		return $this->data;
	}

}
