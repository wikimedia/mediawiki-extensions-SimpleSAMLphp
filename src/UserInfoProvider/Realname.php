<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use Exception;

class Realname extends Base {

	/**
	 * @inheritDoc
	 */
	public function getValue( $samlattributes, $config ): string {
		$realNameAttr = $config->get( 'realNameAttribute' );
		$realname = '';

		if ( $realNameAttr === null ) {
			throw new Exception( 'SimpleSAMLphp data "realNameAttribute" key is not set' );
		}

		if ( is_array( $realNameAttr ) ) {
			foreach ( $realNameAttr as $attribute ) {
				if ( isset( $samlattributes[$attribute] ) ) {
					if ( $realname != '' ) {
						$realname .= ' ';
					}
					$realname .= $samlattributes[$attribute][0];
				} else {
					throw new Exception( 'Could not find real name attribute ' . $attribute );
				}
			}
		} elseif ( isset( $samlattributes[$realNameAttr] ) ) {
			$realname = $samlattributes[$realNameAttr][0];
		} else {
			throw new Exception( 'Could not find real name attribute: ' . $realNameAttr );
		}

		return $realname;
	}

}
