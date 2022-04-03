<?php

namespace MediaWiki\Extension\SimpleSAMLphp\Factory;

use Monolog\Logger;
use MWException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Base implements LoggerAwareInterface {

	/**
	 * @var array
	 */
	protected $registry = [];

	/**
	 * @var Wikimedia\ObjectFactory\ObjectFactory|Wikimedia\ObjectFactory
	 */
	protected $objetFactory = null;

	/**
	 * @var Logger
	 */
	protected $logger = null;

	/**
	 * @param array $registry
	 * @param Wikimedia\ObjectFactory\ObjectFactory|Wikimedia\ObjectFactory $objectFactory
	 */
	public function __construct( array $registry, $objectFactory ) {
		$this->registry = $registry;
		$this->objetFactory = $objectFactory;
		$this->logger = new NullLogger();
	}

	/**
	 * @inheritDoc
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @param string $registryKey
	 * @return stdClass
	 * @throws MWException
	 */
	protected function doGetInstance( $registryKey ) {
		if ( !isset( $this->registry[$registryKey] ) ) {
			throw new MWException( "No spec found for '$registryKey'!" );
		}
		$spec = $this->makeObjectFactorySpec( $this->registry[$registryKey] );
		$object = $this->objetFactory->createObject( $spec, [
			'assertClass' => $this->makeAssertClass()
		] );

		if ( $object instanceof LoggerAwareInterface ) {
			$object->setLogger( $this->logger );
		}

		return $object;
	}

	/**
	 * Class or interface to check against
	 * @return string
	 */
	abstract protected function makeAssertClass(): string;

	/**
	 * @param string|callable|array $registryEntry
	 * @return array
	 */
	protected function makeObjectFactorySpec( $registryEntry ) {
		// b/c with the old config
		if ( !is_array( $registryEntry ) ) {
			return [
				'factory' => $registryEntry
			];
		}
		return $registryEntry;
	}
}
