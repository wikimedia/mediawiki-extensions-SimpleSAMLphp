<?php

namespace MediaWiki\Extension\SimpleSAMLphp;

interface SAMLClient {

	/**
	 * @return void
	 */
	public function requireAuth(): void;

	/**
	 * @return array
	 */
	public function getAttributes(): array;

	/**
	 * @param string $returnTo
	 * @return void
	 */
	public function logout( string $returnTo = '' ): void;

}
