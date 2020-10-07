<?php

namespace Stringee\Http;

class HttpResponse {

	protected $_headers;
	protected $_content;
	protected $_statusCode;

	public function __construct($statusCode, $content, $headers = array()) {
		$this->_statusCode = $statusCode;
		$this->_content = $content;
		$this->_headers = $headers;
	}

	public function getContent() {
		return $this->_content;
	}

	public function getJsonContent() {
		return json_decode($this->_content);
	}

	public function getStatusCode() {
		return $this->_statusCode;
	}

	public function getHeaders() {
		return $this->_headers;
	}

	public function ok() {
		return $this->getStatusCode() < 400;
	}

}
