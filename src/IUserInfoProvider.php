<?php

namespace MediaWiki\Extension\SimpleSAMLphp;

use MediaWiki\Config\Config;

interface IUserInfoProvider {

	/**
	 * @param array $samlattributes
	 * @param Config $config
	 * @return string
	 */
	public function getValue( $samlattributes, $config ): string;
}
