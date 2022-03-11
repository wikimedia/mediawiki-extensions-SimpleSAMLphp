<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Factory;

use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use MWException;

class AttributeProcessorFactory extends Base {

	/**
	 * @param string $processorKey
	 * @return IAttributeProcessor
	 * @throws MWException
	 */
	public function getInstance( $processorKey ): IAttributeProcessor {
		/** @var IAttributeProcessor */
		$instance = $this->doGetInstance( $processorKey );
		return $instance;
	}

	/**
	 * @inheritDoc
	 */
	protected function makeAssertClass(): string {
		return IAttributeProcessor::class;
	}
}
