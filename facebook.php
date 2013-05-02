<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class CI_Facebook extends Facebook {

	private static $ogTags = array();

	var $myApiConfig = array(
		'cookie' => true,
		'language_code' => 'en_GB',
		'debug' => false
	);

	function __construct($config = null){
		if(is_null($config)){
			error_log('No Facebook Config file found');
			return;
		}

		$openGraph = array(
			'og:title' => '',
			'og:image' => '',
			'og:type' => 'website',
			'og:url' => base_url(),
			'fb:app_id' => $config['appId'],
			'og:description' => ''
		);

		if(array_key_exists('graph',$config) && is_array($config['graph'])){
			$openGraph = array_merge($openGraph,$config['graph']);
		}

		$this->setOpenGraphTags($openGraph);

		$this->myApiConfig = array_merge($this->myApiConfig, $config);
		parent::__construct($this->myApiConfig);


		$CI = get_instance();
		//Is there a myapi redirect we need to act on
		if(property_exists($CI, 'session')){
			$redirect = $CI->session->userdata('myapi_redirect');

			if($redirect){
				$CI->session->unset_userdata('myapi_redirect');
				redirect($redirect, 'refresh');
			}
		}
	}

	public function jsRedirect($location){
		echo '<script type="text/javascript">';
		echo "window.parent.location = '{$location}'";
		echo '</script>';
		die;
	}

	public function getNamespace(){
		return (array_key_exists('namespace', $this->myApiConfig)) ? $this->myApiConfig['namespace'] : null;
	}

	public function getPageId(){
		return (array_key_exists('pageId', $this->myApiConfig)) ? $this->myApiConfig['pageId'] : null;
	}

	public function getTabAppUrl(){
		$pageId = $this->getPageId();
		if($pageId){
			$appId = $this->getAppId();
			return "http://www.facebook.com/pages/null/{$pageId}?sk=app_{$appId}";
		}
		return null;
	}

	public function getCanvasUrl($path = ''){
		return (array_key_exists('namespace', $this->myApiConfig)) ? 'http://apps.facebook.com/'.$this->myApiConfig['namespace'].'/'.$path : null;
	}
	
	public function setOpenGraphTags($tags){
		self::$ogTags = array_merge(self::$ogTags,$tags);
	}

	public function openGraphMeta(){
		$html = '';
		foreach(self::$ogTags as $key => $value){
			if(trim($value) != ''){
				$html .= '<meta property="'.$key.'" content="'.addslashes($value).'">'."\n\t";
			}
		}
		return $html;
	}

	public function base64_url_decode($input) {
	  return base64_decode(strtr($input, '-_', '+/'));
	}

	//Parse the signed request if found to find the correct url to show
	public function parse_signed_request($signed_request) {
		list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

		// decode the data
		$sig = $this->base64_url_decode($encoded_sig);
		$data = json_decode($this->base64_url_decode($payload), true);

		if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
			error_log('Unknown algorithm. Expected HMAC-SHA256');
			return null;
		}

		// check sig
		$expected_sig = hash_hmac('sha256', $payload, $this->myApiConfig['secret'], $raw = true);
		if ($sig !== $expected_sig) {
			error_log('Bad Signed JSON signature! when decoding signed request');
			return NULL;
		}
		return $data;
	}

	public function get_signed_request() {
		if (isset($_REQUEST['signed_request'])) {
			return $this->parse_signed_request($_REQUEST['signed_request']);
		}
		return false;
	}
}
