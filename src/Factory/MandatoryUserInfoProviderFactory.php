<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Factory;

use MediaWiki\Extension\SimpleSAMLphp\IUserInfoProvider;
use MWException;

class MandatoryUserInfoProviderFactory extends Base {

	/**
	 * @param string $providerKey
	 * @return IUserInfoProvider
	 * @throws MWException
	 */
	public function getInstance( $providerKey ): IUserInfoProvider {
		/** @var IUserInfoProvider */
		$instance = $this->doGetInstance( $providerKey );
		return $instance;
	}

	/**
	 * @inheritDoc
	 */
	protected function makeAssertClass(): string {
		return IUserInfoProvider::class;
	}

}
