<?php

namespace MediaWiki\Extension\SimpleSAMLphp;

require_once rtrim( $GLOBALS['wgSimpleSAMLphp_InstallDir'], '/' ) . '/lib/_autoload.php';

use MediaWiki\Extension\PluggableAuth\PluggableAuthFactory;
use SimpleSAML\Auth\Simple;

class SimpleSAMLphpSAMLClient implements SAMLClient {

	/**
	 * @param PluggableAuthFactory $pluggableAuthFactory
	 * @return SimpleSAMLphpSAMLClient
	 */
	public static function factory( PluggableAuthFactory $pluggableAuthFactory ) {
		$currentConfig = $pluggableAuthFactory->getCurrentConfig();
		$authSourceId = $currentConfig['data']['authSourceId'];
		return new self( $authSourceId );
	}

	/**
	 * @var SimpleSAML\Auth\Simple
	 */
	private $externalLibClient = null;

	/**
	 * @param string $authSourceId
	 */
	public function __construct( $authSourceId ) {
		$this->externalLibClient = new Simple( $authSourceId );
	}

	/**
	 * @inheritDoc
	 */
	public function requireAuth(): void {
		$this->externalLibClient->requireAuth();
	}

	/**
	 * @inheritDoc
	 */
	public function getAttributes(): array {
		return $this->externalLibClient->getAttributes();
	}

	/**
	 * @inheritDoc
	 */
	public function logout( string $returnTo = '' ): void {
		$this->externalLibClient->logout( $returnTo );
	}
}
