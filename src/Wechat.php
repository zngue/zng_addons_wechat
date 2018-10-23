<?php
namespace zng\addons\wechat;

class Wechat {
	var $wxconfig = array ();
	private $batchget_material = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=";
	private $authorize_oauth2="https://open.weixin.qq.com/connect/oauth2/authorize?appid=";
	private $appid = "wx2c06beb35b62cf16";
	private $secret= "46df39caabb96fd75086ec96e35cb07c";
	private $scope = 'snsapi_userinfo';
	public function __construct($wxconfig =[]) {

			$this->appid = $wxconfig ['appid'];
			$this->secret = $wxconfig ['secret'];
			$this->redirect_uri = $wxconfig ['redirect_uri'];
			if(isset( $wxconfig['scope'] ) && $wxconfig['scope']){
                $this->scope = $wxconfig ['scope'];
            }
	}
	/**
	 * ***************************************************GET方式提交数据*************************************************************
	 */
	private function get($geturl) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $geturl );
		curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		$tmpInfo = curl_exec ( $ch );
		curl_close ( $ch );
		
		return $tmpInfo;
	}
	/**
	 * ***************************************************POST方式提交数据*************************************************************
	 */
	private function post($url, $data) {
		$curl = curl_init ();
		curl_setopt ( $curl, CURLOPT_URL, $url );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $curl, CURLOPT_POST, 1 );
		curl_setopt ( $curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		$result = curl_exec ( $curl );
		
		curl_close ( $curl );
		
		return $result;
	}
	
	// 获取code
	private function getCode() {
		if (isset ( $_GET ['code'] )) {
			
			return $_GET ['code'];
		} else {
			
			$redirect_uri = $this->redirect_uri;
			
			$redirect_uri = urlencode ( $redirect_uri );
			
			$url = $this->authorize_oauth2 . $this->appid . "&redirect_uri=" . $redirect_uri . "&response_type=code&scope=" . $this->scope . "&state=STATE#wechat_redirect";
			
			header ( 'Location:' . $url );
		}
	}
	
	// 获取用户的openid
	public function getOpnenId() {
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . $this->appid . "&secret=" . $this->secret . "&code=" . $this->getCode () . "&grant_type=authorization_code";
		
		$res = $this->get ( $url );
		
		$result = json_decode ( $res, true );

		return $result ['openid'];
	}
	// 获取用户全局Access_token
	private function getAccess_token() {
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->secret;
		
		$res = $this->get ( $url );
		
		$result = json_decode ( $res, 1 );
		
		return $result ['access_token'];
	}
	
	// 判断Access_token是否纯在
	public function getAccesstime() {
		$access_token = cookie ( 'access' );
		
		if (empty ( $access_token )) {
			
			$access_token = $this->getAccess_token ();
			
			cookie ( 'access', $access_token, 7100 ); // 指定cookie保存时间
		}
		
		return $access_token;
	}
	
	// 发送消息
	public function sendMessage($content, $openid) {
		$url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $this->getAccesstime ();
		
		$data = '{
                "touser":"' . $openid . '",
                "msgtype":"text",
                "text":
                {
                     "content":"' . $content . '"
                }
             }';
		
		$result = $this->post ( $url, $data );
		
		return $result;
	}
	public function getMenu($data) {
		$delUrl = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=" . $this->getAccess_token ();
		
		$result = $this->get ( $delUrl );
		
		$url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $this->getAccess_token ();
		$result = $this->post ( $url, $data );
		
		echo $result;
	}
	public function getMediaList($type='news',$offset=0,$count=20){

		$url = $this->batchget_material.$this->getAccess_token;
		
		$data = '{
			"type":$type,
			"offset":$offset,
			"count":$count
		}';
		$result = json_decode ( $this->post($url,$data), 1 );
		return $result;
	}
}
?>