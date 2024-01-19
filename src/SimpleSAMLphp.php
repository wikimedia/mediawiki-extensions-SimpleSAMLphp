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
use MediaWiki\Auth\AuthManager;
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
	 * @var AuthManager
	 */
	private $authManager = null;

	/**
	 * @var SAMLClientFactory
	 */
	private $samlClientFactory = null;

	/**
	 * @var SAMLClient
	 */
	private $samlClient = null;

	/**
	 * @var MandatoryUserInfoProviderFactory
	 */
	private $userInfoProviderFactory;

	/**
	 * @var array
	 */
	private $userinfoProviders;

	/**
	 * @param TitleFactory $titleFactory
	 * @param UserFactory $userFactory
	 * @param AuthManager $authManager
	 * @param SAMLClientFactory $samlClientFactory
	 * @param MandatoryUserInfoProviderFactory $userInfoProviderFactory
	 */
	public function __construct(
		TitleFactory $titleFactory,
		UserFactory $userFactory,
		AuthManager $authManager,
		SAMLClientFactory $samlClientFactory,
		MandatoryUserInfoProviderFactory $userInfoProviderFactory
	) {
		$this->titleFactory = $titleFactory;
		$this->userFactory = $userFactory;
		$this->authManager = $authManager;
		$this->samlClientFactory = $samlClientFactory;
		$this->userInfoProviderFactory = $userInfoProviderFactory;
	}

	/**
	 * @var array - has been removed from the base class, but is requried in this context
	 */
	private $data = [];

	/**
	 * @inheritDoc
	 */
	public function init( string $configId, array $config ): void {
		parent::init( $configId, $config );

		$this->samlClient = $this->samlClientFactory->getInstance( $this );

		// Set some defaults
		$defaultUserinfoProviders = [
			// 'providerKey' => 'factoryKey'
			'username' => 'username',
			'realname' => 'realname',
			'email' => 'email'
		];
		$this->userinfoProviders = array_merge(
			$defaultUserinfoProviders,
			$config['data']['userinfoProviders'] ?? []
		);
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
			$this->getLogger()->error( $errorMessage );
			return false;
		}
		$this->attributes = $this->samlClient->getAttributes();
		$this->authManager->getRequest()->getSession()->setSecret( 'samlAttributes', $this->attributes );
		$this->getLogger()->debug( 'Received attributes: ' . json_encode( $this->attributes, true ) );

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
			$this->getLogger()->error( $errorMessage );
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
		$factoryKey = $this->userinfoProviders[$providerKey] ?? '';
		$provider = $this->userInfoProviderFactory->getInstance( $factoryKey );
		$providedValue = $provider->getValue( $this->attributes, $this->getData() );
		$this->getLogger()->debug(
			"UserInfoProvider '{factoryKey}' (class '{clazz}') for '{providerKey}' provided value '{providedValue}'",
			[
				'clazz' => get_class( $provider ),
				'factoryKey' => $factoryKey,
				'providerKey' => $providerKey,
				'providedValue' => $providedValue
			]
		);
		return $providedValue;
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
		$this->getLogger()->debug( "Deauthenticate {$user->getName()} with returnTo '$returnto'" );
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
	 * @inheritDoc
	 */
	public function getAttributes( $user ): array {
		$sessionAttributes = $this->authManager->getRequest()->getSession()->getSecret( 'samlAttributes' );
		if ( empty( $this->attributes ) && $sessionAttributes !== null ) {
			$this->getLogger()->debug( 'Attributes from session: ' . json_encode( $sessionAttributes, true ) );
			$this->attributes = $sessionAttributes;
		}
		return $this->attributes;
	}

	/**
	 * @return bool
	 * @since 7.0
	 */
	public function shouldOverrideDefaultLogout(): bool {
		return true;
	}
}
