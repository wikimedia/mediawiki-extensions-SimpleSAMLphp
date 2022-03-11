<?php

namespace MediaWiki\Extension\SimpleSAMLphp;

use Config;
use MediaWiki\User\UserIdentity;

interface IAttributeProcessor {

	/**
	 * @since 5.0.0
	 *
	 * @param UserIdentity $user
	 * @param array $attributes
	 * @param Config $config
	 * @return void
	 */
	public function run( UserIdentity $user, array $attributes, Config $config ): void;
}
