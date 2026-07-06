<?php

use MediaWiki\Extension\SimpleSAMLphp\Factory\MandatoryUserInfoProviderFactory;
use MediaWiki\Extension\SimpleSAMLphp\Factory\SAMLClientFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

return [
	'SimpleSAMLphpSAMLClientFactory' => static function ( MediaWikiServices $services ): SAMLClientFactory {
		$config = $services->getMainConfig();
		$objectFactory = $services->getObjectFactory();
		$factory = new SAMLClientFactory(
			$config,
			$config->get( 'SimpleSAMLphp_SAMLClientSpecs' ),
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
