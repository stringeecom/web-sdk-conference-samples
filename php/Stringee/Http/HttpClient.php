<?php

namespace Stringee\Http;

include_once 'FirebaseJWT/JWT.php';
include_once 'HttpResponse.php';

use \Firebase\JWT\JWT;

class HttpClient {

	const DEFAULT_TIMEOUT = 60;

	protected $_curlOptions = array();
	protected $_keySid;
	protected $_keySecret;
	protected $_expiredAfter;
	protected $_accessToken;
	protected $_basicAuthUserPass;

	public function setBasicAuth($user, $pass) {
		$this->_basicAuthUserPass = $user . ':' . $pass;
	}

	public function setAccessToken($accessToken) {
		$this->_accessToken = $accessToken;
	}

	public function getAccessToken() {
		return $this->_accessToken;
	}

	public function __construct($keySid, $keySecret, $expiredAfter = 3600) {
		$this->_keySid = $keySid;
		$this->_keySecret = $keySecret;
		$this->_expiredAfter = $expiredAfter;
	}

	public function setOption(array $options) {
		$this->_curlOptions = $options;
	}

	public function post($url, $data, $timeout = null) {
		return $this->request($url, 'POST', $data, array(), $timeout);
	}

	public function get($url, $timeout = null) {
		return $this->request($url, 'GET', array(), array(), $timeout);
	}

	public function _genXStringeeAuthHeader() {
		$now = time();
		$exp = $now + $this->_expiredAfter;

		$header = array('cty' => 'stringee-api;v=1');
		$payload = array(
			'jti' => $this->_keySid . '-' . $now,
			'iss' => $this->_keySid,
			'exp' => $exp,
			'rest_api' => true
		);

		$token = JWT::encode($payload, $this->_keySecret, 'HS256', null, $header);

		return $token;
	}

	public function request($url, $method, $data = null, $headers = array(), $timeout = null) {
		$curl = curl_init($url);

		$timeout = is_null($timeout) ? self::DEFAULT_TIMEOUT : $timeout;

		$options = array();

		//default
		$options = $this->_curlOptions + array(
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_INFILESIZE => null,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Accept: application/json'
			),
			CURLOPT_TIMEOUT => $timeout
		);

		if ($this->_basicAuthUserPass) {
			curl_setopt($curl, CURLOPT_USERPWD, $this->_basicAuthUserPass);
		}

		//headers
		foreach ($headers as $key => $value) {
			$options[CURLOPT_HTTPHEADER][] = "$key: $value";
		}

		//X-STRINGEE-AUTH header
		if ($this->_accessToken) {
			$xStringeeAuthHeader = $this->_accessToken;
		} else {
			$xStringeeAuthHeader = $this->_genXStringeeAuthHeader();
		}

		$options[CURLOPT_HTTPHEADER][] = 'X-STRINGEE-AUTH: ' . $xStringeeAuthHeader;

		//method
		switch (strtolower(trim($method))) {
			case 'get':
				$options[CURLOPT_HTTPGET] = true;
				break;

			case 'post':
				$options[CURLOPT_POST] = true;
				$options[CURLOPT_POSTFIELDS] = $data;
				break;

			case 'put':
				$options[CURLOPT_PUT] = true;
				if ($data) {
					if ($buffer = fopen('php://memory', 'w+')) {
						fwrite($buffer, $data);
						fseek($buffer, 0);
						$options[CURLOPT_INFILE] = $buffer;
						$options[CURLOPT_INFILESIZE] = strlen($data);
					} else {
						throw new Exception('Unable to open a temporary file');
					}
				}
				break;

			case 'head':
				$options[CURLOPT_NOBODY] = true;
				break;

			default:
				$options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
		}

		curl_setopt_array($curl, $options);


		$response = curl_exec($curl);

		$error = curl_errno($curl);

		if (!$error) {
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			//============parse data =====>
			$parts = explode("\r\n\r\n", $response, 3);

			list($head, $responseBody) = ($parts[0] == 'HTTP/1.1 100 Continue') ? array($parts[1], $parts[2]) : array($parts[0], $parts[1]);

			//response headers
			$responseHeaders = array();
			$headerLines = explode("\r\n", $head);
			array_shift($headerLines);
			foreach ($headerLines as $line) {
				list($key, $value) = explode(':', $line, 2);
				$responseHeaders[$key] = $value;
			}
			//<============parse data =====
		} else {
			$statusCode = $error;
		}

		//close
		curl_close($curl);
		//		
		if (isset($buffer) && is_resource($buffer)) {
			fclose($buffer);
		}

		return new HttpResponse($statusCode, $responseBody, $responseHeaders);
	}

}
