<?php

namespace MediaWiki\Extension\SimpleSAMLphp\AttributeProcessor;

use Config;
use HashConfig;
use MultiConfig;

class MapGroups extends GroupProcessorBase {

	/**
	 * @inheritDoc
	 */
	protected function getDefaultConfig(): Config {
		return new MultiConfig( [
			new HashConfig( [
				'mapGroups_Map' => [],
				'mapGroups_AddOnlyGroups' => []
			] ),
			parent::getDefaultConfig()
		] );
	}

	/**
	 *
	 * @var array
	 */
	protected $groupMap = [];

	/**
	 * Reads out the attribute that holds the user groups and applies them to the local user object
	 */
	public function doRun(): void {
		$this->initGroupMap();

		$groupListDelimiter = $this->config->get( 'groupAttributeDelimiter' );
		$addOnlyGroups = $this->config->get( 'mapGroups_AddOnlyGroups' );

		foreach ( $this->groupMap as $group => $rules ) {
			$group = trim( $group );
			$groupAdded = false;

			foreach ( $rules as $attrName => $needles ) {
				if ( $groupAdded == true ) {
					break;
				} elseif ( !isset( $this->attributes[$attrName] ) ) {
					$this->removeUserFromGroup( $group );
					continue;
				}
				$samlProvidedGroups = $this->attributes[$attrName];
				if ( $groupListDelimiter !== null ) {
					$samlProvidedGroups = explode( $groupListDelimiter, $samlProvidedGroups[0] );
				}
				foreach ( $needles as $needle ) {
					if ( is_callable( $needle ) ) {
						$foundMatch = $needle( $samlProvidedGroups );
					} else {
						$foundMatch = in_array( $needle, $samlProvidedGroups );
					}
					if ( $foundMatch ) {
						$this->addUserToGroup( $group );

						// This differs from the original implementation: Otherwise the _last_ group
						// in the list would always determine whether a group should be added or not
						$groupAdded = true;
						break;
					} else {
						if ( in_array( $group, $addOnlyGroups ) ) {
							continue;
						}
						$this->removeUserFromGroup( $group );
					}
				}
			}
		}
	}

	private function initGroupMap() {
		$this->groupMap = [];
		if ( $this->config->has( 'mapGroups_Map' ) ) {
			$this->groupMap = $this->config->get( 'mapGroups_Map' );
		}

		# group map: [mediawiki group][saml attribute][saml attribute value]
		if ( !is_array( $this->groupMap ) ) {
			$this->logger->debug( '`mapGroups_Map` is not an array' );
			$this->groupMap = [];
		}
	}

}
