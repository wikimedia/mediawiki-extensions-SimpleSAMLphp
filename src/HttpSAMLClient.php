<?php

namespace MediaWiki\Extension\SimpleSAMLphp;

use Exception;
use MediaWiki\Config\Config;
use MediaWiki\Config\HashConfig;
use MediaWiki\Extension\PluggableAuth\PluggableAuthFactory;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Request\WebRequest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use RequestContext;

/**
 * SAMLClient implementation that communicates with a standalone SimpleSAMLphp
 * installation over HTTP, avoiding autoloader/dependency collisions.
 */
class HttpSAMLClient implements SAMLClient, LoggerAwareInterface {

	/** @var Config */
	private Config $config;

	/** @var HttpRequestFactory */
	private HttpRequestFactory $httpRequestFactory;

	/** @var LoggerInterface */
	private LoggerInterface $logger;

	/** @var WebRequest */
	private WebRequest $currentWebRequest;

	/** @var string */
	private string $authSourceId;

	/** @var string Base URL of the SimpleSAMLphp API endpoint */
	private string $baseUrl;

	/** @var string Shared secret for authenticating API requests */
	private string $apiToken;

	/** @var string SimpleSAMLphp session cookie name */
	private string $sessionIdCookieName;

	/** @var string SimpleSAMLphp auth token cookie name */
	private string $authTokenCookieName;

	/**
	 * @param PluggableAuthFactory $pluggableAuthFactory
	 * @param Config $mainConfig
	 * @param HttpRequestFactory $httpRequestFactory
	 * @return HttpSAMLClient
	 */
	public static function factory(
			PluggableAuthFactory $pluggableAuthFactory,
			Config $mainConfig,
			HttpRequestFactory $httpRequestFactory ) {
		$currentConfig = $pluggableAuthFactory->getCurrentConfig();
		$authSourceId = $currentConfig['data']['authSourceId'];
		$clientConfig = new HashConfig( $mainConfig->get( 'SimpleSAMLphp_HTTPClientConfig' ) );
		$currentWebRequest = RequestContext::getMain()->getRequest();
		return new self( $authSourceId, $clientConfig, $httpRequestFactory, $currentWebRequest );
	}

	/**
	 * @param string $authSourceId
	 * @param Config $config
	 * @param HttpRequestFactory $httpRequestFactory
	 * @param WebRequest $currentWebRequest
	 */
	public function __construct(
			string $authSourceId,
			Config $config,
			HttpRequestFactory $httpRequestFactory,
			WebRequest $currentWebRequest ) {
		$this->authSourceId = $authSourceId;
		$this->config = $config;
		$this->httpRequestFactory = $httpRequestFactory;
		$this->currentWebRequest = $currentWebRequest;

		$this->baseUrl = rtrim( $this->config->get( 'baseUrl' ), '/' );
		$this->apiToken = $this->config->get( 'sessionAPItoken' );
		$this->sessionIdCookieName = $this->config->get( 'sessionIdCookieName' );
		$this->authTokenCookieName = $this->config->get( 'authTokenCookieName' );
	}

	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	public function requireAuth(): void {
		$status = $this->apiCall( 'status' );

		if ( !empty( $status['authenticated'] ) ) {
			return;
		}

		$returnTo = $this->currentWebRequest->getFullRequestURL();
		$response = $this->apiCall( 'login-url', [ 'returnTo' => $returnTo ] );

		if ( empty( $response['loginUrl'] ) ) {
			throw new Exception( 'Failed to get login URL from SimpleSAMLphp' );
		}

		header( 'Location: ' . $response['loginUrl'] );
		exit;
	}

	/**
	 * @inheritDoc
	 */
	public function getAttributes(): array {
		$status = $this->apiCall( 'status' );

		if ( empty( $status['authenticated'] ) ) {
			return [];
		}

		return $status['attributes'] ?? [];
	}

	/**
	 * @inheritDoc
	 */
	public function logout( string $returnTo = '' ): void {
		$response = $this->apiCall( 'logout-url', [ 'returnTo' => $returnTo ] );

		if ( !empty( $response['logoutUrl'] ) ) {
			header( 'Location: ' . $response['logoutUrl'] );
			exit;
		}
	}

	/**
	 * Make an API call to the SimpleSAMLphp API endpoint.
	 * Forwards the SSP session cookie so SSP can identify the user.
	 *
	 * @param string $action
	 * @param array $params
	 * @return array
	 * @throws Exception
	 */
	private function apiCall( string $action, array $params = [] ): array {
		$params['action'] = $action;
		$params['authSourceId'] = $this->authSourceId;

		$url = $this->baseUrl . '/api/session.php?' . http_build_query( $params );
		$this->logger->debug( "Making API call to SimpleSAMLphp: $url" );

		$request = $this->httpRequestFactory->create( $url, [ 'timeout' => 5 ], __METHOD__ );

		// Set cookies directly via header to bypass CookieJar domain validation,
		// which rejects single-label hosts (e.g. "localhost") and silently drops cookies.
		$cookieParts = [];
		$sessionId = $this->currentWebRequest->getCookie( $this->sessionIdCookieName, '' );
		$authToken = $this->currentWebRequest->getCookie( $this->authTokenCookieName, '' );

		if ( $sessionId !== '' ) {
			$cookieParts[] = $this->sessionIdCookieName . '=' . $sessionId;
		}
		if ( $authToken !== '' ) {
			$cookieParts[] = $this->authTokenCookieName . '=' . $authToken;
		}
		if ( $cookieParts ) {
			$request->setHeader( 'Cookie', implode( '; ', $cookieParts ) );
			$this->logger->debug( "Forwarding session cookies: " . implode( '; ', $cookieParts ) );
		}

		$request->setHeader( 'X-SIMPLESAMLPHP-SESSION-API-TOKEN', $this->apiToken );
		$this->logger->debug( "Setting API token {$this->apiToken}" );

		$responseStatus = $request->execute();
		$responseStatusCode = $request->getStatus();
		$responseBody = $request->getContent();

		if ( !$responseStatus->isOK() ) {
			$this->logger->error( "SAML Service Provider API call failed with $responseStatusCode: $responseBody" );
			throw new Exception( 'Failed to communicate with SAML Service Provider' );
		}

		$decoded = json_decode( $responseBody, true );
		if ( $decoded === null ) {
			$this->logger->error( "Invalid JSON response from SAML Service Provider: $responseBody" );
			throw new Exception( 'Invalid response from SAML Service Provider' );
		}

		if ( isset( $decoded['error'] ) ) {
			$this->logger->error( "SAML Service Provider API error: " . $decoded['error'] );
			throw new Exception( 'Error from SAML Service Provider' );
		}

		return $decoded;
	}
}
