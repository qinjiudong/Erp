<?php
namespace Home\Controller;
use Think\Controller;
use Org\Wechat;
class WeixinController extends Controller{
	private $token;
	private $fun;
	private $data=array();
	public $fans;
	public $mykey;
	public $chatkey;
	private $my='tjy';
	public $wxuser;
	public $apiServer;
	public $siteUrl;
	public $user;
	public $ali;
    public function __constuct(){
        
    }
	public function index(){
        //加入日志监控回复所需时间
        
		$this->ali=0;
		$this->siteUrl=C('site_url');
		if (!class_exists('SimpleXMLElement')){
			exit('SimpleXMLElement class not exist');
		}
		if (!function_exists('dom_import_simplexml')){
			exit('dom_import_simplexml function not exist');
		}
		$this->token=I('get.token');
		if(!preg_match("/^[0-9a-zA-Z]{3,42}$/",$this->token)){
			exit('error token');
		}
		
        //$this->wxuser= M("wxuser")->where(array('token'=>$this->token))->find();
        $this->wxuser = $this->get_wxuser($this->token);
		if (true){
			$weixin = new \Org\Wx\Wechat($this->token,$this->wxuser);
		}
		
		//$this->user=M('Users')->where(array('id'=>$this->wxuser['uid']))->find();
		$data = $weixin->request();
		$this->data = $weixin->request();
        $data = $weixin->request();
        $this->data = $weixin->request();
		if ($this->data) {
            list($content, $type) = $this->reply($data);
            $weixin->response($content, $type);
        }
	}
	private function reply($data){
        //语音功能,则写入识别后的文本
		if (isset($data['MsgType'])) {
            if ('voice' == $data['MsgType']) {
                $data['Content'] = $data['Recognition'];
                $this->data['Content'] = $data['Recognition'];
            }
		}
        //判断关注
		if('CLICK' == $data['Event']){
			$data['Content']= $data['EventKey'];
			$this->data['Content'] = $data['EventKey'];
		}elseif($data['Event']=='SCAN'){
			if ($this->wxuser['openphotoprint']){
				$photoPrint->initUser();
			}
			$data['Content']= $this->getRecognition($data['EventKey']);
			$this->data['Content'] = $data['Content'];
		}elseif($data['Event']=='MASSSENDJOBFINISH'){
			M('Send_message')->where(array('msg_id'=>$data['msg_id']))->save(array('reachcount'=>$data['SentCount']));
		//subscribe(订阅)
		}elseif('subscribe' == $data['Event']){
			$this->behaviordata('follow','1');
			$this->requestdata('follownum');
			$follow_data=M('Areply')->field('home,keyword,content,check_subscribed,content2,keyword2,type')->where(array('token'=>$this->token))->find();
	                //用户未关注时，进行关注后的事件推送 事件KEY值，qrscene_为前缀，后面为二维码的参数值
			//首先获取是否存在关注送礼活动
            $focusmap = array(
                "token" => $this->token,
                "status" => 1
            );
            $first = M("first_subscribe")->where($focusmap)->order("id desc")->find();
            if($first){
                //如果需要去重，则需要记录关注者的关注信息
                $submap = array(
                    "openid" => $data["FromUserName"],
                    "token"  => $this->token,
                    "actid" => $first["id"]
                );
                //如果不允许已经关注过的粉丝参与活动，则条件需变化
                if($first["allow_old"] == 1){
                    $if = M("subscribe_time")->where($submap)->find();
                } else {
                    $fansmap = array(
                        "token" => $this->token,
                        "openid" => $data["FromUserName"]
                    );
                    $if = M("subscribe_time")->where($submap)->find() || M("wechat_group_list")->where($fansmap)->find();
                }
                if($if){
                    //如果已经关注过了
                    if($first['reply2_keyword']!=""){
                        return $this->keyword($first['reply2_keyword']);
                    } else {
                        return array(html_entity_decode($first['reply2']),'text');
                    }
                } else {
                    $subdata = array(
                        "openid" => $data["FromUserName"],
                        "token" => $this->token,
                        "actid" => $first["id"],
                        "subscribetime" => time()
                    );
                    M("subscribe_time")->add($subdata);
                    $firstmap = array(
                        "id" => $first["id"]
                    );
                    M("first_subscribe")->where($firstmap)->setInc("vcount");
                    if($first['reply1_keyword']!=""){
                        return $this->keyword($first['reply1_keyword']);
                    } else {
                        return array(html_entity_decode($first['reply1']),'text');
                    }
                }
                
            }
            if(!(strpos($data['EventKey'],'qrscene_') === FALSE)){
				$follow_data['keyword']=$this->getRecognition(str_replace('qrscene_','',$data['EventKey']));
				$follow_data['home']=1;
			}
            //首页功能
			if($follow_data['home']==1){
				if(trim($follow_data['keyword'])=='首页'||$follow_data['keyword']=='home'){
					return $this->shouye();
				}elseif(trim($follow_data['keyword'])=='我要上网'){
					return $this->wysw();
				}
				return $this->keyword($follow_data['keyword']);
			}else{
				if($follow_data['keyword']!=""){
                    return $this->keyword($follow_data['keyword']);
			     }else{
				    return array(html_entity_decode($follow_data['content']),'text');
			     }
            }
		}elseif('unsubscribe'==$data['Event']){
			$this->requestdata('unfollownum');
			$node=D('Rippleos_node')->where(array('token'=>$this->token))->find();
			$this->rippleos_unauth($node['node']);
		}elseif($data['Event']=='LOCATION'){
			return $this->nokeywordApi();
		}
        //其他操作都算作互动
        if($data['MsgType'] == 'text'){
            $this->behaviordata("text");
        }
        //处理扫码事件
        if($data['MsgType'] == "event" && $data["Event"] == "scancode_waitmsg"){
            $scanResult = $data["ScanCodeInfo"]["ScanResult"];
            //条码处理
            if($data["ScanCodeInfo"]["ScanType"] == "barcode"){
                $scanResultArray = explode(",", $scanResult);
                $barcode = $scanResultArray[1];
                //首先根据条码长度判断是不是订单,15位并且1开头的表明是订单
                if(strlen($barcode) == 15 && $barcode{0} == 1){
                    //调用签收流程
                    $response = "订单：".$barcode."已签收";
                    return array($response,'text');
                }
            } else if ($data["ScanCodeInfo"]["ScanType"] == "qrcode"){

            }
            
            
        }
        return array("消息已收到",'text');
	}

	private function error_msg($data){
		return '没有找到'.$data.'相关的数据';
	}
        //记录请求信息统计
        //field 字段名
	private function requestdata($field){
		$data['year']=date('Y');
		$data['month']=date('m');
		$data['day']=date('d');
		$data['token']=$this->token;
		$mysql=M('Requestdata');
		$check=$mysql->field('id')->where($data)->find();
		if($check==false){
			$data['time']=time();
			$data[$field]=1;
			$mysql->add($data);
		}else{
			$mysql->where($data)->setInc($field);
		}
	}
	private function behaviordata($field,$id='',$type=''){
		$data['date']=date('Y-m-d',time());
		$data['token']=$this->token;
		$data['openid']=$this->data['FromUserName'];
		$data['keyword']=$this->data['Content'];
		if (!$data['keyword']){
			$data['keyword']='用户关注';
		}
		$data['model']=$field;
		if($id!=false){
			$data['fid']=$id;
		}
		if($type!=false){
			$data['type']=1;
		}
		$mysql=M('Behavior');
		$check=$mysql->field('id')->where($data)->find();
		$this->updateMemberEndTime($data['openid']);
		if($check==false){
			$data['num']=1;
			$data['enddate']=time();
			$mysql->add($data);
		}else{
			$mysql->where($data)->setInc('num');
		}
	}
	private function updateMemberEndTime($openid){
		$mysql=M('wechat_member_enddate');
		$id=$mysql->field('id')->where(array('openid'=>$openid))->find();
		$data['enddate']=time();
		$data['openid']=$openid;
		$data['token']=$this->token;
		if($id==false){
			$mysql->add($data);
		}else{
			$data['id']=$id['id'];
			$mysql->save($data);
		}
        $map = array(
            "wecha_id" => $openid
        );
        $data = array(
            "lasttime" => time()
        );
        
        if(M("customer")->where($map)->find()){
            M("customer")->where($map)->save($data);
        } else {

        }
	}

	private function curlGet($url,$method='get',$data=''){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$temp = curl_exec($ch);
		return $temp;
	}

	public function handleIntro($str){
    		$str = html_entity_decode(htmlspecialchars_decode($str));
    		$search = array('&amp;', '&quot;', '&nbsp;', '&gt;', '&lt;');
    		$replace = array('&', '"', ' ', '>', '<');
    		return strip_tags(str_replace($search, $replace, $str));
	}

    private function get_wxuser($token){
        $wxuser = C("WEIXIN");
        //$this->wxuser= M("wxuser")->where(array('token'=>$token))->find();
        $this->wxuser = $wxuser;
        return $this->wxuser;
    }

    //全局统一获取accesstoken的方法，
    private function get_access_token($token = '', $force = false){
        $token = $token || $this->token;
        $data = S("access_token_".$token);
        if($data["expire_time"] < time()){
            $data = null;
        }
        if($force){
            $data = null;
        }
        $wxuser = $this->get_wxuser($token);
        if ( !$data || is_null($data) || empty($data) ) {
          $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->wxuser['appid'] . "&secret=" . $this->wxuser['appsecret'];
          $res = json_decode($this->httpGet($url));
          $json = $ret;
          if($res->errmsg){
            return false;
          }
          $access_token = $res->access_token;
          if ($access_token) {
            $expires_in = intval($res->expires_in);
            $expires_in = $expires_in ? $expires_in : 7000;
            $data['expire_time'] = time() + $expires_in;
            $data['access_token'] = $access_token;
            //写入缓存
            S("access_token_".$token, $data, $expires_in);
          }
        } else {
          $access_token = $data['access_token'];
        }
        return $access_token;
    }

    //客服消息发送
    public function sendMessage(){
        $openid  = I("request.openid");
        $content = I("request.msg");
        $access_token = $this->get_access_token();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
        $data='{"touser":"'.$openid.'","msgtype":"text","text":{"content":"'.$content.'"}}';
        $ret = curlPost($url, $data);
        $data = array(
            "wecha_id" => $openid,
            "msg" => $content,
            "send_time" => date("Y-m-d", time()),
        );
        if($ret["success"] == false){
            $data["status"] = 0;
        } else {
            $data["status"] = 1;
        }
        M("weixin_message")->add($data);
        return $ret;
    }

    //账户绑定
    public function bindAccount($params){
        
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
