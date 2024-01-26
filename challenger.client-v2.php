<?php
// Copyright: Enga systems, UAB

class Challenger{
  var $url = null;
	var $host = null;
	var $params = [];
	var $key = null;
	var $ownerId = 0;
	var $clientId = '';

  var $eventsList = [];
  var $lastResponse = false;

	public function __construct($url, $key){
    $this -> url = $url;
    $this -> key = $key;
    $this -> host = parse_url($url)['host'];
	}

	private function generateVector(){
		return openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
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

	private function encryptData($data){

		// Generate an initialization vector
		// This *MUST* be available for decryption as well
		$iv = $this -> generateVector();

		// Encrypt $data using aes-256-cbc cipher with the given encryption key and
		// our initialization vector. The 0 gives us the default options, but can
		// be changed to OPENSSL_RAW_DATA or OPENSSL_ZERO_PADDING
		return openssl_encrypt($data, 'aes-256-cbc', $this -> key, 0, $iv) . ':' . base64_encode($iv);
	}

  // Adds an event to the batch by client id
	function addEvent($clientId, $event, $params = []){
		// Owner call's should always be hashed by default. Owner Id is a salt itself
		// However if clientKey (already hashed string) is provided. We don't hash it again
		// Also key is not hashed if it is not a call by the owner
		if($this -> ownerId){
			$clientKey = md5($this -> ownerId . ':' . $clientId);
		}else{
			$clientKey = $clientId;
		}

    // Add events to the list for the following encryption
    return $this -> eventsList[] = [
			'client_id' => $clientKey,
			'params' => $params,
			'event' => $event,
		];
	}

  // Adds an event to the batch by client key
	function addEventHashed($clientKey, $event, $params = []){
		// Owner call's should always be hashed by default. Owner Id is a salt itself
		// However if clientKey (already hashed string) is provided. We don't hash it again
		// Also key is not hashed if it is not a call by the owner

    // Add events to the list for the following encryption
    return $this -> eventsList[] = [
			'client_id' => $clientKey,
			'params' => $params,
			'event' => $event,
		];
	}

  // Send the list of events to the server
	public function send(){
		$res = $this -> httpsRequestPost("{$this->url}/api/v2/trackEvent", [
			'owner_id' => $this -> ownerId,
			'data' => $this -> encryptData(json_encode($this -> eventsList))
		]);

    // Flush existing event list
    $this -> eventsList = [];

    return $res;
	}

	public function deleteClient($clientId){
		return $this -> httpsRequestPost($this->url . "/api/v2/deleteClient", [
      'data' => $this -> encryptData(json_encode([
  			'client_id' => $clientId,
  		]))
    ]);
	}

	public function getEncryptedData(){
		return $this -> encryptData(json_encode([
			'client_id' => $this -> clientId,
			'params' => $this -> params,
		]));
	}

	public function getWidgetScript(){
		return "
			_chw = typeof _chw == 'undefined' ? {} : _chw;
			_chw.type = 'iframe';
			_chw.domain = '{$this->host}';
			_chw.data = '" . $this->getEncryptedData() . "';
			(function() {
				var ch = document.createElement('script'); ch.type = 'text/javascript'; ch.async = true;
				ch.src = '{$this->url}/v2/widget/script.js';
				var s = document.getElementsByTagName('script')[0];
				s.parentNode.insertBefore(ch, s);
			})();
		";
	}

	public function getWidgetHtml(){
		return '
		<div id="_chWidget"></div>
		<script type="text/javascript">
			'.$this -> getWidgetScript().'
		</script>';
	}

	public function getWidgetUrl(){
		return "{$this->url}/widget?data=" . urlencode($this -> getEncryptedData());
	}

	private function httpsRequest($url)
	{
		$ch = curl_init();

		// Set cURL settings
		curl_setopt_array($ch, [
			CURLOPT_NOPROXY => getenv('NO_PROXY'),
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 3, // 3 sec.
			CURLOPT_TIMEOUT => 10 // 10 sec.
		]);

		$this -> lastResponse = curl_exec($ch);

    // Get HTTP response code
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

    // Throw exception if server fails
    if($http_status < 200 or $http_status >= 300){
      throw new Exception("ApiResponseError");
    }

		return $this -> lastResponse;
	}

	private function httpsRequestPost($url, $postArray)
	{
		$ch = curl_init();

		// Set cURL settings
		curl_setopt_array($ch, [
			CURLOPT_NOPROXY => getenv('NO_PROXY'),
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => http_build_query($postArray),
			CURLOPT_CONNECTTIMEOUT => 3, // 3 sec.
			CURLOPT_TIMEOUT => 10 // 10 sec.
		]);

		$this -> lastResponse = curl_exec($ch);

    // Get HTTP response code
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

    // Throw exception if server fails
    if($http_status < 200 or $http_status >= 300){
      throw new Exception("ApiResponseError");
    }

		return $this -> lastResponse;
	}

  public function getLastResponse(){
    return $this -> lastResponse;
  }
}
