<?php

namespace MediaWiki\Extension\SimpleSAMLphp\HookHandler;

use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;

class LogoutLink implements SkinTemplateNavigation__UniversalHook {

	// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

	/**
	 * MediaWiki 1.39+ uses XHR for logout, which is not supported by SAML.
	 * Therefore we need to remove the logout link and add a custom one.
	 *
	 * @inheritDoc
	 */
	public function onSkinTemplateNavigation__Universal( $sktemplate, &$links ): void {
		if ( !isset( $links['user-menu']['logout'] ) ) {
			return;
		}
		$links['user-menu']['custom-logout'] = $links['user-menu']['logout'];
		unset( $links['user-menu']['logout'] );
	}
}
