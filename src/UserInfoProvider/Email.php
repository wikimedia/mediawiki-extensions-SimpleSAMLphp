<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use Exception;

class Email extends Base {

	/**
	 * @inheritDoc
	 */
	public function getValue( $samlattributes, $config ): string {
		$emailAttr = $config->get( 'emailAttribute' );
		$email = '';

		if ( $emailAttr === null ) {
			throw new Exception( 'SimpleSAMLphp data "emailAtribute" key is not set' );
		}
		if ( !isset( $samlattributes[$emailAttr] ) ) {
			throw new Exception( 'Could not find email attribute: ' . $emailAttr );
		}
		$email = $samlattributes[$emailAttr][0];

		return $email;
	}

}
