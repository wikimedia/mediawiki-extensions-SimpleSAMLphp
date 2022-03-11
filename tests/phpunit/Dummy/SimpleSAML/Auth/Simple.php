<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Tests\Dummy\SimpleSAML\Auth;

use MediaWiki\Extension\SimpleSAMLphp\SAMLClient;

class Simple implements SAMLClient {

	/**
	 * @inheritDoc
	 */
	public function requireAuth(): void {
		// Do nothing
	}

	/**
	 * @inheritDoc
	 */
	public function getAttributes(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function logout( string $returnTo = '' ): void {
		// Do nothing
	}
}
