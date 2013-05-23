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

		$this->myApiConfig = array_merge($this->myApiConfig, $config);
		parent::__construct($this->myApiConfig);


		//If enabled in the config force the aplication to redirect to the canvas
		if(array_key_exists('canvasRedirect', $config) && $config['canvasRedirect']){
			if(!array_key_exists('HTTP_REFERER', $_SERVER)){
				redirect($this->getCanvasUrl(), 'refresh');
			}
		}

		$openGraph = array(
			'og:title' => '',
			'og:image' => '',
			'og:type' => 'website',
			'og:url' => current_url(),
			'fb:app_id' => $config['appId'],
			'og:description' => ''
		);

		$this->setOpenGraphTags($openGraph);
	}

	/**
	 * Checks to see if the user has "liked" the page by checking a signed request
	 * @return boolean true if liked, flase if we are not sure if the user liked the page
	 */
	public function hasLiked(){
		$signedRequest = $this->getSignedRequest();
		
		return !is_null($signedRequest) && array_key_exists('page', $signedRequest) && $signedRequest['page']['liked'];
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
				$html .= '<meta property="'.$key.'" content="'.htmlentities($value).'">'."\n\t";
			}
		}
		return $html;
	}
}
