<?php

class PCOAccess {
	const PCO_CONSUMER_OPTION = 'pco_consumer_cred';
	const PCO_REQUEST_TOKEN_OPTION = 'pco_request_token';
	const PCO_ACCESS_TOKEN_OPTION = 'pco_access_token';
	
	private $hasCredentials;
	private $connected;
	public $api;
	
	function __construct() {
		$this->hasCredentials = false;
		$this->connected = false;
		$this->api = false;
		
		// check that client credentials exist
		$clientCredentials = maybe_unserialize(get_option(self::PCO_CONSUMER_OPTION));
		if (empty($clientCredentials->key) || empty($clientCredentials->secret)) return;
		$this->hasCredentials = true;
		
		// build connection object
		$this->api = new PlanningCenterOnline(array(
			'key' => $clientCredentials->key,
			'secret' => $clientCredentials->secret,
			'debug' => false
		));
		
		// connect object
		$init = $this->api->connect(PlanningCenterOnline::TOKEN_CACHE_CUSTOM, array(
			'getAccessToken' => array(__CLASS__, 'getAccessToken'),
			'saveAccessToken' => array(__CLASS__, 'saveAccessToken'),
			'getRequestToken' => array(__CLASS__, 'getRequestToken'),
			'saveRequestToken' => array(__CLASS__, 'saveRequestToken')
		));
		if (!$init) return;
		$this->connected = true;
	}
	
	function authenticate($callbackURL) {
		if (!$this->hasCredentials()) return false;
		$success = $this->api->login($callbackURL, PlanningCenterOnline::TOKEN_CACHE_CUSTOM, array(
			'getAccessToken' => array(__CLASS__, 'getAccessToken'),
			'saveAccessToken' => array(__CLASS__, 'saveAccessToken'),
			'getRequestToken' => array(__CLASS__, 'getRequestToken'),
			'saveRequestToken' => array(__CLASS__, 'saveRequestToken')
		));
		if ($success) $this->connected = true;
		return $success;
	}
	
	public function okay() {
		return $this->connected;
	}
	
	public function hasCredentials() {
		return $this->hasCredentials;
	}
	
	public static function saveAccessToken($token) {
		$tokenS = serialize($token);
		update_option(self::PCO_ACCESS_TOKEN_OPTION, $tokenS);
	}
	
	public static function getAccessToken() {
		$token = maybe_unserialize(get_option(self::PCO_ACCESS_TOKEN_OPTION));
		if ($token) return $token;
		return false;
	}
	
	public static function saveRequestToken($token) {
		$tokenS = serialize($token);
		update_option(self::PCO_REQUEST_TOKEN_OPTION, $tokenS);
	}
	
	public static function getRequestToken() {
		$token = maybe_unserialize(get_option(self::PCO_REQUEST_TOKEN_OPTION));
		if ($token) return $token;
		return false;
	}
	
	public static function deleteAccessToken() {
		delete_option(self::PCO_ACCESS_TOKEN_OPTION);
	}
}