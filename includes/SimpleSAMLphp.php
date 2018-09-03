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
class SimpleSAMLphp extends PluggableAuth {

	protected $attributes;

	/**
	 * Get the user's username.  Override this if you need to change
	 * the appearance from what SAML gives.
	 *
	 * @param string &$username going into this
	 * @param int &$userId the user's id
	 * @param string|null &$errorMessage if you want to return an error message.
	 * @return bool|string false if there was a problem getting the username.
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	protected function getUsername( &$username = '', &$userId = 0, &$errorMessage = null ) {
		if ( isset( $GLOBALS['wgSimpleSAMLphp_UsernameAttribute'] ) ) {
			$usernameAttr = $GLOBALS['wgSimpleSAMLphp_UsernameAttribute'];
			if ( isset( $this->attributes[$usernameAttr] ) ) {
				$username = strtolower( $this->attributes[$usernameAttr][0] );
				$newTitle = Title::makeTitleSafe( NS_USER, $username );
				if ( is_null( $newTitle ) ) {
					$errorMessage = 'Invalid username: ' . $username;
					return false;
				}
				$username = $newTitle->getText();
				$userId = User::idFromName( $username );
			} else {
				$errorMessage = 'Could not find username attribute: ' .
					$usernameAttr;
				return false;
			}
		} else {
			$errorMessage = '$wgSimpleSAMLphp_UsernameAttribute is not set';
			return false;
		}
		return $username;
	}

	/**
	 * Get the user's real name.  Override this if you need to change
	 * the appearance from what SAML gives.
	 *
	 * @param string &$realname going into this
	 * @param string|null &$errorMessage if you want to return an error message.
	 * @return bool|string false if no realname could be found
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	protected function getRealname( &$realname = '', &$errorMessage = null ) {
		if ( isset( $GLOBALS['wgSimpleSAMLphp_RealNameAttribute'] ) ) {
			$realNameAttribute = $GLOBALS['wgSimpleSAMLphp_RealNameAttribute'];
			if ( is_array( $realNameAttribute ) ) {
				foreach ( $realNameAttribute as $attribute ) {
					if ( array_key_exists( $attribute, $this->attributes ) ) {
						if ( $realname != '' ) {
							$realname .= ' ';
						}
						$realname .= $this->attributes[$attribute][0];
					} else {
						$errorMessage = 'Could not find real name attribute ' .
							$attribute;
						return false;
					}
				}
			} elseif ( array_key_exists( $realNameAttribute, $this->attributes ) ) {
				$realname = $this->attributes[$realNameAttribute][0];
			} else {
				$errorMessage = 'Could not find real name attribute: ' .
					$realNameAttribute;
				return false;
			}
		} else {
			$errorMessage = '$wgSimpleSAMLphp_RealNameAttribute is not set';
			return false;
		}
		return $realname;
	}

	/**
	 * Get the user's email address.  Override this if you need to change
	 * the appearance from what SAML gives.
	 *
	 * @param string &$email going into this
	 * @param string|null &$errorMessage if you want to return an error message.
	 * @return bool|string false if no realname could be found
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	protected function getEmail( &$email = '', &$errorMessage = null ) {
		if ( isset( $GLOBALS['wgSimpleSAMLphp_EmailAttribute'] ) ) {
			$emailAttr = $GLOBALS['wgSimpleSAMLphp_EmailAttribute'];
			if ( isset( $this->attributes[$emailAttr] ) ) {
				$email = $this->attributes[$emailAttr][0];
			} else {
				$errorMessage = 'Could not find email attribute: ' . $emailAttr;
				return false;
			}
		} else {
			$errorMessage = '$wgSimpleSAMLphp_EmailAttribute is not set';
			return false;
		}
		return $email;
	}

	/**
	 * @since 1.0
	 *
	 * @param int &$userId id of user
	 * @param string &$username username
	 * @param string &$realname real name of user
	 * @param string &$email email
	 * @param string|null &$errorMessage any error encountered
	 * @return bool true if the user is authenticated
	 * @see https://www.mediawiki.org/wiki/Extension:PluggableAuth
	 */
	public function authenticate(
		&$userId, &$username, &$realname, &$email, &$errorMessage = null
	 ) {
		$saml = self::getSAMLClient();
		try {
			$saml->requireAuth();
		} catch ( Exception $e ) {
			$errorMessage = $e->getMessage();
			wfDebugLog( 'SimpleSAMLphp', $errorMessage );
			return false;
		}
		$this->attributes = $saml->getAttributes();

		if (
			( $this->getUsername( $username, $userId, $errorMessage ) !== false ) &&
			( $this->getRealName( $realname, $errorMessage ) !== false ) &&
			( $this->getEmail( $email, $errorMessage ) !== false )
		) {
			return true;
		}
		wfDebugLog( 'SimpleSAMLphp', $errorMessage );
		return false;
	}

	/**
	 * @since 1.0
	 *
	 * @param User &$user to deauthenticate
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public function deauthenticate( User &$user ) {
		$saml = self::getSAMLClient();
		$returnto = null;
		if ( array_key_exists( 'returnto', $_REQUEST ) ) {
			$title = Title::newFromText( $_REQUEST['returnto'] );
			if ( ! is_null( $title ) ) {
				$returnto = $title->getFullURL();
			}
		}
		if ( is_null( $returnto ) ) {
			$returnto = Title::newMainPage()->getFullURL();
		}
		$saml->logout( $returnto );
	}

	/**
	 * @since 1.0
	 *
	 * @param int $userId id of user
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function saveExtraAttributes( $userId ) {
		// intentionally left blank
	}

	/**
	 * @since 4.1
	 * Update MediaWiki group membership of the authenticated user (given as object).
	 * Override function of parent class to use groups from SAML attributes.
	 * Credits to Extension:SimpleSamlAuth by Jorn de Jong
	 *
	 * @param User &$user to get groups from SAML
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function populateGroups( User &$user ) {
		$saml = self::getSAMLClient();
		$attributes = $saml->getAttributes();

		# group map: [mediawiki group][saml attribute][saml attribute value]
		$groupMap = isset( $GLOBALS['wgSimpleSAMLphp_GroupMap'] )
				  ? $GLOBALS['wgSimpleSAMLphp_GroupMap']
				  : null;

		if ( is_array( $groupMap ) ) {
			foreach ( $groupMap as $group => $rules ) {
				foreach ( $rules as $attrName => $needles ) {
					if ( !isset( $attributes[$attrName] ) ) {
						continue;
					}
					foreach ( $needles as $needle ) {
						if ( in_array( $needle, $attributes[$attrName] ) ) {
							$user->addGroup( $group );
						} else {
							$user->removeGroup( $group );
						}
					}
				}
			}
		} else {
			wfDebugLog( 'SimpleSAMLphp',
				'$wgSimpleSAMLphp_GroupMap is not an array' );
		}
	}

	/**
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	private static function getSAMLClient() {
		require_once rtrim( $GLOBALS['wgSimpleSAMLphp_InstallDir'], '/' )
			. '/lib/_autoload.php';
		$class = 'SimpleSAML_Auth_Simple';
		if ( class_exists( 'SimpleSAML\Auth\Simple' ) ) {
			$class = 'SimpleSAML\\Auth\\Simple';
		}
		return new $class( $GLOBALS['wgSimpleSAMLphp_AuthSourceId'] );
	}
}
