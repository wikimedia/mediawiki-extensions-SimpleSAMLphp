<?php

namespace MediaWiki\Extension\SimpleSAMLphp\UserInfoProvider;

use MediaWiki\Extension\SimpleSAMLphp\IUserInfoProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class Base implements IUserInfoProvider, LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	protected $logger = null;

	public function __construct() {
		$this->logger = new NullLogger();
	}

	/**
	 * @return IUserInfoProvider
	 */
	public static function factory() {
		return new static();
	}

	/**
	 * @inheritDoc
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}
}
