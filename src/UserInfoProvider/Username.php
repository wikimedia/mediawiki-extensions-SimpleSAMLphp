<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use Exception;
use MediaWiki\Title\Title;

class Username extends Base {

	/**
	 * @inheritDoc
	 */
	public function getValue( $samlattributes, $config ): string {
		$usernameAttr = $config->get( 'usernameAttribute' );
		$username = '';

		if ( $usernameAttr === null ) {
			throw new Exception( 'SimpleSAMLphp data "usernameAttribute" key is not set' );
		}

		if ( !isset( $samlattributes[$usernameAttr] ) ) {
			throw new Exception( 'Could not find username attribute: ' . $usernameAttr );
		}

		$username = $this->normalizeUsername( $samlattributes[$usernameAttr][0] );
		$newTitle = Title::makeTitleSafe( NS_USER, $username );
		if ( $newTitle === null ) {
			throw new Exception( 'Invalid username: ' . $username );
		}

		$username = $newTitle->getText();

		return $username;
	}

	/**
	 * @param string $samlProvidedUsername
	 * @return string
	 */
	protected function normalizeUsername( $samlProvidedUsername ) {
		return $samlProvidedUsername;
	}
}
