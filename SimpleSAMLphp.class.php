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
	 */
	public function authenticate( &$id, &$username, &$realname, &$email ) {

		$saml = $this->getSAMLClient();
		$saml->requireAuth();
		$attributes = $saml->getAttributes();

		if ( isset( $GLOBALS['SimpleSAMLphp_RealNameAttribute'] ) ) {
			$realNameAttribute = $GLOBALS['SimpleSAMLphp_RealNameAttribute'];
			if ( is_array( $realNameAttribute ) ) {
				$realname = "";
				foreach ( $realNameAttribute as $attribute ) {
					if ( array_key_exists( $attribute, $attributes ) ) {
						if ( $realname != "" ) {
							$realname .= " ";
						}
						$realname .= $attributes[$attribute][0];
					} else {
						return false;
					}
				}
			} else {
				if ( array_key_exists( $realNameAttribute, $attributes ) ) {
					$realname = $attributes[$realNameAttribute][0];
				} else {
					return false;
				}
			}
		} else {
			return false;
		}

		if ( isset( $GLOBALS['SimpleSAMLphp_EmailAttribute'] ) &&
			array_key_exists( $GLOBALS['SimpleSAMLphp_EmailAttribute'],
				$attributes ) ) {
			$email = $attributes[$GLOBALS['SimpleSAMLphp_EmailAttribute']][0];
		} else {
			return false;
		}

		if ( isset( $GLOBALS['SimpleSAMLphp_UsernameAttribute'] ) &&
			array_key_exists( $GLOBALS['SimpleSAMLphp_UsernameAttribute'],
			$attributes ) ) {
			$username = strtolower(
				$attributes[$GLOBALS['SimpleSAMLphp_UsernameAttribute']][0] );
			$nt = Title::makeTitleSafe( NS_USER, $username );
			if ( is_null( $nt ) ) {
				return false;
			}
			$username = $nt->getText();
			$id = User::idFromName( $username );
			return true;
		}

		return false;
	}

	/**
	 * @since 1.0
	 *
	 * @param User &$user
	 */
	public function deauthenticate( User &$user ) {
		$saml = $this->getSAMLClient();
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

	private function getSAMLClient() {
		require_once rtrim( $GLOBALS['SimpleSAMLphp_InstallDir'],
			DIRECTORY_SEPARATOR ) .  DIRECTORY_SEPARATOR . 'lib' .
			DIRECTORY_SEPARATOR . '_autoload.php';
		return new SimpleSAML_Auth_Simple(
			$GLOBALS['SimpleSAMLphp_AuthSourceId'] );
	}
}

