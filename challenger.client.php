<?php

class Challenger{
	var $host = null;
	var $port = 443;
	var $params = array();
	var $key = null;
	var $ownerId = 0;
	var $clientId = 0;

	public function __construct($host, $port = false){
		$this -> host = $host;
		if($port){
			$this -> port = $port;
		}
	}

	private function generateVector(){
		return openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
	}

	public function setKey($key){
		if(!$key){
			return false;
		}

		$this -> key = $key;

		return true;
	}

	public function setOwnerId($ownerId){
		$this -> ownerId = $ownerId;
	}

	public function setClientId($clientId){
		$this -> clientId = $clientId;
	}

	public function addParam($name, $value){
		$this -> params[$name] = $value;
	}

	private function encryptData($data = array()){
		// Generate an initialization vector
		// This *MUST* be available for decryption as well
		$iv = $this -> generateVector();

		// Encrypt $data using aes-256-cbc cipher with the given encryption key and
		// our initialization vector. The 0 gives us the default options, but can
		// be changed to OPENSSL_RAW_DATA or OPENSSL_ZERO_PADDING
		$encrypted = openssl_encrypt($data, 'aes-256-cbc', $this -> key, 0, $iv);

		return $encrypted.':'.base64_encode($iv);
	}

	private function getEventTrackingUrl($event){
		// Owner call's should always be hashed be default. Owner Id is a salt itself
		if($this -> ownerId){
			$clientId = md5($this -> ownerId . ":" . $this -> clientId);
		}else{
			$clientId = $this -> clientId;
		}

		$data = array(
			'client_id' => $clientId,
			'params' => $this -> params,
			'event' => $event,
		);

		$encryptedData = $this -> encryptData(json_encode($data));

		return ($this -> port == '443' ? 'https' : 'http') . '://' . $this -> host . '/api/v1/trackEvent?owner_id='.$this -> ownerId.'&data=' . urlencode($encryptedData);
	}

	public function trackEvent($event){
		$url = $this -> getEventTrackingUrl($event);

		return file_get_contents($url);
	}

	private function getClientDeletionUrl(){
		$data = json_encode(array(
			'client_id' => $this -> clientId,
		));

		$encryptedData = $this -> encryptData($data);

		return ($this -> port == '443' ? 'https' : 'http') . '://' . $this -> host . '/api/v1/deleteClient?data=' . urlencode($encryptedData);
	}

	public function deleteClient(){
		$url = $this -> getClientDeletionUrl();

		return file_get_contents($url);
	}

	private function getEncryptedWidgetData(){
		$data = json_encode(array(
			'client_id' => $this -> clientId,
			'params' => $this -> params,
		));

		return $this -> encryptData($data);
	}

	public function getWidgetScript(){
		return '
			_chw = _chw || {};
			_chw.type = "iframe";
			_chw.domain = "'.$this -> host.'";
			_chw.data = "'.$this -> getEncryptedData().'";
			(function() {
			var ch = document.createElement("script"); ch.type = "text/javascript"; ch.async = true;
			ch.src = "//'.($this -> host).'/v1/widget/script.js";
			var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ch, s);
			})();
		';
	}

	public function getWidgetHtml(){
		$out = '
		<div id="_chWidget"></div>
		<script type="text/javascript">
			<!--
			'.$this -> getWidgetScript().'
			//-->
		</script>';

		return $out;
	}

	public function getWidgetUrl(){
		return '//' . $this -> host . '/widget?data=' . urlencode($this -> getEncryptedWidgetData());
	}
}
