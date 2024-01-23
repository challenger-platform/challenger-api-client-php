<?php
// Copyright: Enga systems, UAB

class Challenger{
	var $host = null;
	var $port = 443;
	var $params = [];
	var $key = null;
	var $ownerId = 0;
	var $clientId = '';
	var $clientKey = '';

	public function __construct($host, $port = false){
		$url = parse_url($host); // Check if URL is provided instead of hostname

		$this -> host = $url['host'] ?? $host;
		$this -> port = $url['port'] ??
			$port ?:
			((!empty($url['scheme']) and $url['scheme'] == 'http') ? 80 : $this -> port);
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

	public function setClientKey($key){
		$this -> clientKey = $key;
	}

	public function addParam($name, $value){
		$this -> params[$name] = $value;
	}

	private function encryptData($data){

		// Generate an initialization vector
		// This *MUST* be available for decryption as well
		$iv = $this -> generateVector();

		// Encrypt $data using aes-256-cbc cipher with the given encryption key and
		// our initialization vector. The 0 gives us the default options, but can
		// be changed to OPENSSL_RAW_DATA or OPENSSL_ZERO_PADDING
		return openssl_encrypt($data, 'aes-256-cbc', $this -> key, 0, $iv) . ':' . base64_encode($iv);
	}

	private function getHostUrl(){
		if($this->port == 443){
			return 'https://' . $this->host;
		}else{
			return 'http://' . $this->host
				. (($this->port != 443 and $this->port != 80) ? ':' . $this->port : '');
		}
	}

	private function getEventTrackingUrl($event){
		// Owner call's should always be hashed by default. Owner Id is a salt itself
		// However if clientKey (already hashed string) is provided. We don't hash it again
		// Also key is not hashed if it is not a call by the owner
		if($this -> ownerId and !$this -> clientKey){
			$this -> clientKey = md5($this -> ownerId . ':' . $this -> clientId);
		}else{
			$this -> clientKey = $this -> clientId ?: $this -> clientKey;
		}

		$encryptedData = urlencode($this -> encryptData(json_encode([
			'client_id' => $this -> clientKey,
			'params' => $this -> params,
			'event' => $event,
		])));

		return $this->getHostUrl() . "/api/v1/trackEvent?owner_id={$this->ownerId}&data=$encryptedData";
	}

	public function trackEvent($event){
		return $this -> httpsRequest($this -> getEventTrackingUrl($event));
	}

	private function getClientDeletionUrl(){
		$encryptedData = $this -> encryptData(json_encode([
			'client_id' => $this -> clientId,
		]));

		return $this->getHostUrl() . '/api/v1/deleteClient?data=' . urlencode($encryptedData);
	}

	public function deleteClient(){
		return $this -> httpsRequest($this -> getClientDeletionUrl());
	}

	public function getEncryptedData(){
		return $this -> encryptData(json_encode([
			'client_id' => $this -> clientId,
			'params' => $this -> params,
		]));
	}

	public function getWidgetScript(){
		return '
			_chw = typeof _chw == "undefined" ? {} : _chw;
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
		return '
		<div id="_chWidget"></div>
		<script type="text/javascript">
			<!--
			'.$this -> getWidgetScript().'
			//-->
		</script>';
	}

	public function getWidgetUrl(){
		return "//{$this->host}/widget?data=" . urlencode($this -> getEncryptedData());
	}

	private function httpsRequest($url)
	{
		$ch = curl_init();

		// Set cURL settings
		curl_setopt_array($ch, [
			CURLOPT_NOPROXY => getenv('NO_PROXY'),
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 3, // 3 sec.
			CURLOPT_TIMEOUT => 10 // 10 sec.
		]);
		
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}
}
