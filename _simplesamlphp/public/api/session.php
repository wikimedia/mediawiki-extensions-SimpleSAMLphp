<?php

namespace SimpleSAMLphp\Integration\MediaWiki\Extension\SimpleSAMLphp;

/**
 * Internal API endpoint for MediaWiki to query SimpleSAMLphp session state.
 *
 * IMPORTANT: This must be protected so only MediaWiki can access it.
 * Use firewall rules, IP allowlisting, or a shared secret.
 */
// phpcs:disable MediaWiki.Usage.SuperGlobalsUsage.SuperGlobals
// phpcs:disable MediaWiki.Files.ClassMatchesFilename.NotMatch

use SimpleSAML\Auth\Simple;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;

class SessionHandler {

	/**
	 * @var Configuration
	 */
	private Configuration $config;

	/**
	 * @var array
	 */
	private array $serverVars;

	public function __construct( Configuration $config, array $serverVars ) {
		$this->config = $config;
		$this->serverVars = $serverVars;
	}

	public function getContentType(): string {
		return 'application/json';
	}

	/**
	 * @var int
	 */
	private $statusCode = 200;

	/**
	 * @return int
	 */
	public function getStatusCode(): int {
		return $this->statusCode;
	}

	/**
	 * @var array
	 */
	private $responseBody = [];

	/**
	 * @return string
	 */
	public function getResponseBody(): string {
		return json_encode( $this->responseBody );
	}

	public function execute( array $request ): void {
		Logger::debug( "SimpleSAMLphp/MediaWiki Session API called with request: " . json_encode( $request ) );

		if ( !$this->hasValidToken( $request ) || !$this->isCallerAllowed() ) {
			Logger::error( 'Unauthorized access' );
			$this->statusCode = 403;
			$this->responseBody = [ 'error' => 'Unauthorized' ];
			return;
		}

		$authSourceId = $request['authSourceId'] ?? 'default-sp';
		$auth = new Simple( $authSourceId );

		$action = $request['action'] ?? 'status';
		switch ( $action ) {
			case 'status':
				$isAuthenticated = $auth->isAuthenticated();
				$attributes = $auth->getAttributes();
				Logger::debug( "isAuthenticated: " . ( $isAuthenticated ? 'true' : 'false' ) );
				Logger::debug( "attributes: " . json_encode( $attributes ) );

				$this->responseBody = [
					'authenticated' => $isAuthenticated,
					'attributes' => $isAuthenticated ? $attributes : [],
				];
				break;

			case 'login-url':
				// Return the URL that MediaWiki should redirect the user to
				$returnTo = $request['returnTo'] ?? '';
				$loginUrl = $auth->getLoginURL( $returnTo );
				Logger::debug( "Login URL: $loginUrl" );

				$this->responseBody = [
					'loginUrl' => $loginUrl,
				];
				break;

			case 'logout-url':
				// Return the URL that triggers SSP logout
				$returnTo = $request['returnTo'] ?? '';
				$logoutUrl = $auth->getLogoutURL( $returnTo );
				Logger::debug( "Logout URL: $logoutUrl" );

				$this->responseBody = [
					'logoutUrl' => $logoutUrl,
				];
				break;

			default:
				$this->statusCode = 400;
				$this->responseBody = [ 'error' => 'Unknown action' ];
		}
	}

	private function hasValidToken( array $request ): bool {
		$expectedToken = $this->config->getString( 'mediawiki.sessionapi.token', '' );
		if ( empty( $expectedToken ) ) {
			Logger::error( 'Session API token is not configured' );
			return false;
		}
		$providedToken = $this->serverVars['HTTP_X_SIMPLESAMLPHP_SESSION_API_TOKEN'] ?? '';
		Logger::debug( "Incoming token: $providedToken, expected token: $expectedToken" );
		return hash_equals( $expectedToken, $providedToken );
	}

	private function isCallerAllowed(): bool {
		$allowedCallersCIDR = $this->config->getString( 'mediawiki.sessionapi.allowedcallers', '' );
		$callerIP = $this->serverVars['REMOTE_ADDR'] ?? '';
		$cidrList = array_map( 'trim', explode( ',', $allowedCallersCIDR ) );
		// Calls from localhost should always be allowed
		$cidrList[] = '127.0.0.1/32';

		foreach ( $cidrList as $cidr ) {
			if ( empty( $cidr ) ) {
				continue;
			}
			Logger::debug( "Checking if caller IP $callerIP is in allowed CIDR $cidr" );
			if ( $this->cidrMatch( $callerIP, $cidr ) ) {
				Logger::debug( "Match" );
				return true;
			}
			Logger::debug( "Not match" );
		}
		Logger::error( "Caller IP $callerIP is not in allowed CIDR list" );
		return false;
	}

	private function cidrMatch( string $ip, string $cidr ): bool {
		[ $subnet, $prefixLen ] = explode( '/', $cidr );

		$ip     = inet_pton( $ip );
		$subnet = inet_pton( $subnet );

		if ( $ip === false || $subnet === false ) {
			return false;
		}

		// Build a binary mask from the prefix length
		// 4 bytes for IPv4, 16 for IPv6
		$ipLen  = strlen( $ip );
		$mask   = str_repeat( "\xff", (int)( $prefixLen / 8 ) );
		$rem    = $prefixLen % 8;
		if ( $rem > 0 ) {
			$mask .= chr( 0xff << ( 8 - $rem ) & 0xff );
		}
		$mask = str_pad( $mask, $ipLen, "\x00" );

		return ( $ip & $mask ) === ( $subnet & $mask );
	}

}

if ( defined( 'MW_PHPUNIT_TEST' ) ) {
	return;
}

require_once dirname( __DIR__, 2 ) . '/lib/_autoload.php';

$config = Configuration::getInstance();
$handler = new SessionHandler( $config, $_SERVER );
$handler->execute( $_REQUEST );

$contentType = $handler->getContentType();
$statusCode = $handler->getStatusCode();
$responseBody = $handler->getResponseBody();

Logger::debug( "SimpleSAMLphp/MediaWiki Session API response: status $statusCode, body: $responseBody" );

header( "Content-Type: $contentType" );
http_response_code( $statusCode );
echo $responseBody;
