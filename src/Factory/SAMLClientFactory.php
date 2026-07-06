<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Factory;

use MediaWiki\Config\Config;
use MediaWiki\Extension\SimpleSAMLphp\SAMLClient;
use MediaWiki\Extension\SimpleSAMLphp\SimpleSAMLphp;

class SAMLClientFactory extends Base {

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param Config $config
	 * @param array $specs
	 * @param Wikimedia\ObjectFactory\ObjectFactory|Wikimedia\ObjectFactory $objectFactory
	 */
	public function __construct( Config $config, $specs, $objectFactory ) {
		$this->config = $config;
		parent::__construct( $specs, $objectFactory );
	}

	/**
	 * @param SimpleSAMLphp $plugin
	 * @return SAMLClient
	 * @throws MWException
	 */
	public function getInstance( $plugin ): SAMLClient {
		// Make MW core `SpecialPageFatalTest` pass
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return new \MediaWiki\Extension\SimpleSAMLphp\Tests\Dummy\SimpleSAML\Auth\Simple();
		}

		$samlClientKey = $this->config->get( 'SimpleSAMLphp_SAMLClient' );

		/** @var SAMLClient */
		$instance = $this->doGetInstance( $samlClientKey );
		return $instance;
	}

	/**
	 * @inheritDoc
	 */
	protected function makeAssertClass(): string {
		return SAMLClient::class;
	}
}
