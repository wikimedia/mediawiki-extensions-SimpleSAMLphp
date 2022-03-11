<?php

namespace MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor;

use Config;
use MediaWiki\Extension\SimpleSAMLphp\IAttributeProcessor;
use MediaWiki\User\UserIdentity;
use MultiConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Base implements IAttributeProcessor, LoggerAwareInterface {

	/**
	 *
	 * @var \User
	 */
	protected $user = null;

	/**
	 *
	 * @var array
	 */
	protected $attributes = [];

	/**
	 *
	 * @var \Config
	 */
	protected $config = null;

	/**
	 * @var LoggerInterface
	 */
	protected $logger = null;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * @inheritDoc
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @return IAttributeProcessor
	 */
	public static function factory() {
		return new static();
	}

	/**
	 * @inheritDoc
	 */
	public function run( UserIdentity $user, array $attributes, Config $config ): void {
		$this->user = $user;
		$this->attributes = $attributes;
		$this->config = new MultiConfig( [
			$config,
			$this->getDefaultConfig()
		] );
		$this->doRun();
	}

	abstract protected function doRun(): void;

	/**
	 * @return Config
	 */
	abstract protected function getDefaultConfig(): Config;
}
