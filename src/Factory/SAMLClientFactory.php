<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Factory;

use MediaWiki\Extension\SimpleSAMLphp\SAMLClient;
use MediaWiki\Extension\SimpleSAMLphp\SimpleSAMLphp;
use MediaWiki\Extension\SimpleSAMLphp\SimpleSAMLphpSAMLClient;
use MWException;

class SAMLClientFactory {

	/**
	 * @param SimpleSAMLphp $plugin
	 * @return SAMLClient
	 * @throws MWException
	 */
	public function getInstance( SimpleSAMLphp $plugin ): SAMLClient {
		// Make MW core `SpecialPageFatalTest` pass
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return new \MediaWiki\Extension\SimpleSAMLphp\Tests\Dummy\SimpleSAML\Auth\Simple();
		}
		$config = $plugin->getData();
		$authSourceId = $config->get( 'authSourceId' );
		return new SimpleSAMLphpSAMLClient( $authSourceId );
	}

	/**
	 * @inheritDoc
	 */
	protected function makeAssertClass(): string {
		return SAMLClient::class;
	}
}
