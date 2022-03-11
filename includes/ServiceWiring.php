<?php

use MediaWiki\Extension\SimpleSAMLphp\Factory\AttributeProcessorFactory;
use MediaWiki\Extension\SimpleSAMLphp\Factory\MandatoryUserInfoProviderFactory;
use MediaWiki\Extension\SimpleSAMLphp\Factory\SAMLClientFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'SimpleSAMLphpSAMLClientFactory' => static function ( MediaWikiServices $services ): SAMLClientFactory {
		return new SAMLClientFactory();
	},
	'SimpleSAMLphpAttributeProcessorFactory' =>
		static function ( MediaWikiServices $services ): AttributeProcessorFactory {
			$config = $services->getMainConfig();
			$objectFactory = $services->getObjectFactory();
			$factory = new AttributeProcessorFactory(
				$config->get( 'SimpleSAMLphp_AttributeProcessors' ),
				$objectFactory
			);

			$factory->setLogger( LoggerFactory::getInstance( 'SimpleSAMLphp' ) );
			return $factory;
		},
	'SimpleSAMLphpMandatoryUserInfoProviderFactory' =>
		static function ( MediaWikiServices $services ): MandatoryUserInfoProviderFactory {
		$config = $services->getMainConfig();
		$objectFactory = $services->getObjectFactory();
		$factory = new MandatoryUserInfoProviderFactory(
			$config->get( 'SimpleSAMLphp_MandatoryUserInfoProviders' ),
			$objectFactory
		);
		$factory->setLogger( LoggerFactory::getInstance( 'SimpleSAMLphp' ) );
			return $factory;
		}
];
