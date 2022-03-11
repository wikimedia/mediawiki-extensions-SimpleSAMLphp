<?php

namespace MediaWiki\Extension\SimpleSAMLphp\HookHandler;

use HashConfig;
use MediaWiki\Extension\PluggableAuth\Hook\PluggableAuthPopulateGroups as HookPluggableAuthPopulateGroups;
use MediaWiki\Extension\PluggableAuth\PluggableAuthFactory;
use MediaWiki\Extension\SimpleSAMLphp\AttributeProcessorFactory;
use MediaWiki\Extension\SimpleSAMLphp\Factory\AttributeProcessorFactory as FactoryAttributeProcessorFactory;
use MediaWiki\Extension\SimpleSAMLphp\Factory\SAMLClientFactory;
use MediaWiki\Extension\SimpleSAMLphp\SimpleSAMLphp;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\User\UserIdentity;
use Psr\Log\LoggerInterface;

class PluggableAuthPopulateGroups implements HookPluggableAuthPopulateGroups {

	/**
	 * @var PluggableAuthFactory
	 */
	private $pluggableAuthFactory = null;

	/**
	 * @var AttributeProcessorFactory
	 */
	private $attributeProcessorFactory = null;

	/**
	 * @var SAMLClientFactory
	 */
	private $samlClientFactory = null;

	/**
	 * @var LoggerInterface
	 */
	private $logger = null;

	/**
	 * @param PluggableAuthFactory $pluggableAuthFactory
	 * @param AttributeProcessorFactory $attributeProcessorFactory
	 * @param SAMLClientFactory $samlClientFactory
	 */
	public function __construct(
		PluggableAuthFactory $pluggableAuthFactory,
		FactoryAttributeProcessorFactory $attributeProcessorFactory,
		SAMLClientFactory $samlClientFactory
		) {
		$this->pluggableAuthFactory = $pluggableAuthFactory;
		$this->attributeProcessorFactory = $attributeProcessorFactory;
		$this->samlClientFactory = $samlClientFactory;
		$this->logger = LoggerFactory::getInstance( 'SimpleSAMLphp' );
	}

	/**
	 * @inheritDoc
	 */
	public function onPluggableAuthPopulateGroups( UserIdentity $user ): void {
		$currentPlugin = $this->pluggableAuthFactory->getInstance();
		if ( !( $currentPlugin instanceof SimpleSAMLphp ) ) {
			// We can only sync groups in the context of a SAML authentication flow,
			// not for arbitrary other plugins
			return;
		}
		$config = new HashConfig( $currentPlugin->getConfig() );
		$processorKeys = $config->get( 'attributeProcessors' );
		if ( empty( $processorKeys ) ) {
			$this->logger->debug( "No 'attributeProcessors' set." );
			return;
		}

		$samlClient = $this->samlClientFactory->getInstance( $currentPlugin );
		$attributes = $samlClient->getAttributes();
		foreach ( $processorKeys as $processorKey ) {
			$this->logger->debug( "Run '$processorKey' attribute processor." );
			$processor = $this->attributeProcessorFactory->getInstance( $processorKey );
			$processor->run( $user, $attributes, $config );
		}
	}

}
