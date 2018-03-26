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
 */


class SimpleSAMLphp extends PluggableAuth {

	/**
	 * @since 1.0
	 *
	 * @param &$id
	 * @param &$username
	 * @param &$realname
	 * @param &$email
	 * @param &$errorMessage
	 */
	public function authenticate( &$id, &$username, &$realname, &$email,
		&$errorMessage ) {

		$saml = self::getSAMLClient();
		try {
			$saml->requireAuth();
		} catch ( Exception $e ) {
			$errorMessage = $e->getMessage();
			return false;
		}
		$attributes = $saml->getAttributes();

		if ( isset( $GLOBALS['wgSimpleSAMLphp_RealNameAttribute'] ) ) {
			$realNameAttribute = $GLOBALS['wgSimpleSAMLphp_RealNameAttribute'];
			if ( is_array( $realNameAttribute ) ) {
				$realname = "";
				foreach ( $realNameAttribute as $attribute ) {
					if ( array_key_exists( $attribute, $attributes ) ) {
						if ( $realname != "" ) {
							$realname .= " ";
						}
						$realname .= $attributes[$attribute][0];
					} else {
						wfDebug( 'SimpleSAMLphp: Could not find real name attribute ' .
							$attribute );
						return false;
					}
				}
			} else {
				if ( array_key_exists( $realNameAttribute, $attributes ) ) {
					$realname = $attributes[$realNameAttribute][0];
				} else {
					wfDebug( 'SimpleSAMLphp: Could not find real name attribute ' .
						$attributes );
					return false;
				}
			}
		} else {
			wfDebug( 'SimpleSAMLphp: $wgSimpleSAMLphp_RealNameAttribute is not set' );
			return false;
		}

		if ( isset( $GLOBALS['wgSimpleSAMLphp_EmailAttribute'] ) ) {
			if ( array_key_exists( $GLOBALS['wgSimpleSAMLphp_EmailAttribute'],
				$attributes ) ) {
				$email = $attributes[$GLOBALS['wgSimpleSAMLphp_EmailAttribute']][0];
			} else {
				wfDebug( 'SimpleSAMLphp: Could not find email attribute ' .
					$attributes );
				return false;
			}
		} else {
			wfDebug( 'SimpleSAMLphp: $wgSimpleSAMLphp_EmailAttribute is not set' );
			return false;
		}

		if ( isset( $GLOBALS['wgSimpleSAMLphp_UsernameAttribute'] ) ) {
			if ( array_key_exists( $GLOBALS['wgSimpleSAMLphp_UsernameAttribute'],
				$attributes ) ) {
				$username = strtolower(
					$attributes[$GLOBALS['wgSimpleSAMLphp_UsernameAttribute']][0] );
				$nt = Title::makeTitleSafe( NS_USER, $username );
				if ( is_null( $nt ) ) {
					return false;
				}
				$username = $nt->getText();
				$id = User::idFromName( $username );
			} else {
				wfDebug( 'SimpleSAMLphp: Could not find username attribute ' .
					$attributes );
				return false;
			}
		} else {
			wfDebug( 'SimpleSAMLphp: $wgSimpleSAMLphp_UsernameAttribute is not set' );
			return false;
		}

		return true;
	}

	/**
	 * @since 1.0
	 *
	 * @param User &$user
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
		return true;
	}

	/**
	 * @since 1.0
	 *
	 * @param $id
	 */
	public function saveExtraAttributes( $id ) {
		// intentionally left blank
	}

	/**
	 * @since 4.1
	 * Update MediaWiki group membership of the authenticated user (given as object).
	 * Override function of parent class to use groups from SAML attributes.
	 * Credits to Extension:SimpleSamlAuth by Jorn de Jong
	 * @param User &$user
	 */
	public static function populateGroups( User $user ) {
		$saml = self::getSAMLClient();
		$attributes = $saml->getAttributes();

		if ( is_array( $GLOBALS['wgSimpleSAMLphp_GroupMap'] ) ) {
			# group map: [mediawiki group][saml attribute][saml attribute value]
			foreach ( $GLOBALS['wgSimpleSAMLphp_GroupMap'] as $group => $rules ) {
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
			wfDebug( 'SimpleSAMLphp: $wgSimpleSAMLphp_GroupMap is not an array' );
		}
	}

	private static function getSAMLClient() {
		require_once rtrim( $GLOBALS['wgSimpleSAMLphp_InstallDir'], '/' )
			. '/lib/_autoload.php';
		$class = "SimpleSAML_Auth_Simple";
		if ( class_exists( 'SimpleSAML\Auth\Simple' ) ) {
			$class = "SimpleSAML\\Auth\\Simple";
		}
		return new $class( $GLOBALS['wgSimpleSAMLphp_AuthSourceId'] );
	}
}
