<?php

namespace Home\Service;

/**
 * 信息发送接口服务
 *
 * @author dubin
 */
class SmsService extends ERPBaseService {

	private $query_url = "http://www.4001185185.com/sdk/smssdk!query.action";
	private $send_url  = "http://www.4001185185.com/sdk/smssdk!mt.action";
	private $rev_url  = "http://www.4001185185.com/sdk/smssdk!mo.action";
	private $sdk = "68888";
	private $code = "ceshi";
	private $pwdtype = "md5";
	private $initRequest = array();

	public function __construct(){
		$sms_config      = C("SMS");
		$this->sdk       = $sms_config["sdk"];
		$this->code      = $sms_config["code"];
		$this->query_url = $sms_config["query_url"];
		$this->send_url  = $sms_config["send_url"];
		$this->rev_url   = $sms_config["rev_url"];
		$data = array(
			"sdk"  => $this->sdk,
			"code" => md5($this->code),
			"pwdtype" => "md5"
		);
		$this->initRequest = $data;
	}

	public function queryBalance(){
		$data = $this->initRequest;
		$url = $this->query_url;
		$query_string = http_build_query($data);
		$url = $url."?".$query_string;
		try{
			$result = file_get_contents($url);
			$ret = array(
				"success" => true,
				"data" => $result
			);
		} catch( Exception $exc ){
			$result = 0;
			$ret = array(
				"success" => false,
				"data" => $result
			);
		}
		
		return $ret;
	}

	
	public function send($params){
		$data = $this->initRequest;
		$data["phones"] = $params["phones"];
		$data["userid"] = $params["userid"];
		$data["msg"]    = $params["msg"];
		$data["rpt"]    = "1";
		if($data["userid"] && !$data["phones"]){
			$phone_arr = array();
			$map = array(
				"code" => array("in", explode(",", $data["userid"]))
			);
			$user_list = M("customer")->where($map)->select();
			foreach ($user_list as $key => $value) {
				$phone_arr[] = $value["mobile01"];
			}
			$data["phones"] = implode(",", $phone_arr);
		}
		if(!$data["phones"]){
			return array("success"=>false,"msg"=> "parameter miss : phone");
		}
		$url = $this->send_url;
		$query_string = http_build_query($data);
		$url = $url."?".$query_string;
		$db = M("sms", "t_");
		try{
			$result = $this->httpGet($url);
			$ret = array(
				"success" => true,
				"data" => $result
			);
			$data = array(
				"phone" => $data["phones"],
				"msg" => $data["msg"],
				"msgid" => $result,
				"send_time" => date("Y-m-d H:i:s", time()),
				"status" => 1
			);
			$db->add($data);
			return $ret;
		} catch( Exception $exc ){
			$result = 0;
			$ret = array(
				"success" => false,
				"data" => $result
			);
			return $ret;
		}
	}

	//微信发送消息
	public function wx_send($params){
		//$openid  = $params["openid"];
        $content = $params["msg"];
        $userid  = $params["userid"];
        $sms     = $params["sms"];
        $userid_arr =explode(",", $userid);
        $access_token = $this->get_access_token("", true);
        $success_count = 0;
        $fail_count = 0;
        $sms_count = 0;
        $db = M("sms", "t_");
        //dump(time());
        foreach ($userid_arr as $key => $userid) {
        	$map = array(
        		"code" => $userid
        	);
        	$user = M("customer")->where($map)->find();
        	$openid = $user["wecha_id"];
        	$diff_time = 48 * 3600 - 300;

        	//if($user["lasttime"] && time() - $user["lasttime"] < $diff_time){
        	if($openid){
        		$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
		        $data='{"touser":"'.$openid.'","msgtype":"text","text":{"content":"'.$content.'"}}';
		        $ret = $this->curlPost($url, $data);
		        $data2 = array(
					"phones" => $openid,
					"msg" => $content,
					"msgid" => 0,
					"send_time" => date("Y-m-d H:i:s", time()),
					"status" => 1,
					"data" => json_encode($ret)
				);
				$db->add($data2);
		        $data = array(
		            "wecha_id" => $openid,
		            "msg" => $content,
		            "send_time" => date("Y-m-d", time()),
		        );
		        if($ret["success"] == false){
		            $data["status"] = 0;
		            $fail_count++;
		            //补发短信
		            if($sms == 1){
		            	$params["phones"] = $user["mobile01"];
        				$this->send($params);
        				$sms_count++;
		            }
		        } else {
		            $data["status"] = 1;
		            $success_count++;
		        }
		        M("weixin_message")->add($data);
        	} else {
        		if($sms == 1){
        			$params["phones"] = $user["mobile01"];
        			//$this->send($params);
        			$sms_count++;
        		}
        	}
        }
        $ret = array(
        	"success" => true,
        	"totalCount" => count($userid_arr),
        	"successCount" => $success_count,
        	"failCount"  => $fail_count,
        	"smsCount" => $sms_count
        );
        return $ret;
        
	}

	//根据用户的情况自动调用发送请求，如果微信有互动则发送微信，如果没有微信互动则发送短信
	public function auto_send($params){
		$userid = $params["userid"];
		$phone  = $params["phone"];
		if($userid){
			$map = array(
        		"code" => $userid
        	);
        	$user = M("customer")->where($map)->find();
        	if(!$phone){
        		$params["phone"] = $user["mobile01"];
        	}
		} else {
			$map = array(
        		"mobile01" => $phone
        	);
        	$user = M("customer")->where($map)->find();
        	$params["userid"] = $user["code"];
		}
		if(!$user){
			return array("success" => false, "msg" => "用户不存在");
		}
		$now = time();
		$diff_time = 48 * 3600 - 300;//48小时内互动过的用户,发送微信
		if($user["lasttime"]){
			if($now - $user["lasttime"] < $diff_time){
				return $this->wx_send($params);
			} else {
				return $this->send($params);
			}
		} else {
			//排除掉未绑定先互动的情况
			if($user["wecha_id"]){
				$map = array(
					"openid" => $user["wecha_id"]
				);
				$lasttime = M('wechat_member_enddate')->where($map)->getField("enddate");
				if($lasttime && $now - $lasttime < $diff_time){
					return $this->wx_send($params);
				}
			}
			return $this->send($params);
		}
	}

	//构造post请求
    function curlPost($url, $data){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8;";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        //curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_UPLOAD, true);
        
        $tmpInfo = curl_exec($ch);
        $errorno=curl_errno($ch);
        if ($errorno) {
            return array('success'=>false,'errorno'=>$errorno, 'msg' => $errorno);
        }else{
            $js=json_decode($tmpInfo,1);
            if (intval($js['errcode']==0)){
                return array('success'=> true ,  'errorno'=>0, 'media_id'=>$js['media_id'], 'msg_id'=>$js['msg_id']);
            }else {
                return array("success"=> false , "msg" => '发生了Post错误：错误代码'.$js['errcode'].',微信返回错误信息：'.$js['errmsg']);
            }
        }
    }




}