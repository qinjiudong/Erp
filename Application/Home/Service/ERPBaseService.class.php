<?php

namespace Home\Service;

/**
 * Service 基类
 *
 * @author 李静波
 */
class ERPBaseService {

	public function genUUID(){
		$data = M()->query("select UUID() as uuid");
		if (!$data) {
			return strtoupper(uniqid());
		} else {
			return strtoupper($data[0]["uuid"]);
		}
	}

	protected function isDemo() {
		return getenv("IS_DEMO") == "1";
	}
	
	protected function isMOPAAS() {
		// 是否部署在 http://jyerp.jyshop.mopaas.com
		return getenv("IS_MOPAAS") == "1";
	}

	protected function ok($id = null) {
		if ($id) {
			return array("success" => true, "id" => $id);
		} else {
			return array("success" => true);
		}
	}

	protected function suc($data = null) {
		if ($data) {
			return array("success" => true, "data" => $data);
		} else {
			return array("success" => true);
		}
	}

	protected function bad($msg) {
		return array("success" => false, "msg" => $msg);
	}

	protected function todo($info = null) {
		if ($info) {
			return array("success" => false, "msg" => "TODO: 功能还没开发, 附加信息：$info");
		} else {
			return array("success" => false, "msg" => "TODO: 功能还没开发");
		}
	}
	
	protected function sqlError($no) {
		return $this->bad("数据库错误". $no ."，请联系管理员");
	}
	
	protected function toYMD($d) {
		return date("Y-m-d", strtotime($d));
	}

	protected function getGoodsInPrice($goods_id){
		$db = M("goods", "t_");
		$map = array("id" => $goods_id);
		$price = $db->where($map)->getField("lastbuyprice");
		return $price;
	}

	//获取商品信息
	public function base_get_goods_info($goods_id){
		$db = M("goods", "t_");
		$map = array("id" => $goods_id);
		$info = $db->where($map)->find();
		if(!$info){
			$map = array("code" => $goods_id);
			$info = $db->where($map)->find();
		}
		return $info; 
	}

	//计算联营商品的进货价
	public function calc_inv_price($price, $rabat){
		if(strpos($rebat, "%") > -1){
			$rebat = str_replace("%", "", $rabat);
			$rabat = round($rabat / 100, 2);
		}
		return round($price * (1 - $rabat), 2);
	}

	//根据siteid获取area
	public function get_area_by_siteid($siteid){
		$map =array(
			"id" => $siteid
		);
		$site = M("site")->where($map)->find();
		$map = array(
			"id" => $site["category_id"]
		);
		$area = M("site_category")->where($map)->find();
		return $area;
	}
	//获取库存@convert 如果为true，则获取库存之后需要根据转化系数转化为件数
	public function base_get_goods_stock($goods_id, $convert = false ,$wid = ""){
		$db = M();
		$sql = "SELECT (balance_count) as b_count,warehouse_id FROM `t_inventory` WHERE `goods_id` = '$goods_id'";
		if($wid){
			$sql .= " and `warehouse_id` = '$wid'";
		}
		$data  = $db->query($sql);
		$count = floatval($data[0]['b_count']);
		if($convert == true){
			$goods_info = $this->base_get_goods_info($goods_id);
			if($goods_info["bulk"] == 0){//计重的自动转换
				$spec = $goods_info["spec"];
				if(strpos($spec, "g")){
					$kg = str_replace("g", "", $spec);
					if(is_numeric($kg)){
						$count = round(($count*1000) / $kg);
					}
				} else {

				}
			}
		}
		return $count;
	}

	public function base_convert_stock($goods_id, $goods_number){

		$goods_info = $this->base_get_goods_info($goods_id);
		if($goods_info && $goods_info["bulk"] == 0){//计重的自动转换
				$spec = $goods_info["spec"];
				if(strpos($spec, "g")){
					$kg = str_replace("g", "", $spec);
					if(is_numeric($kg)){
						$goods_number = round(($goods_number*1000) / $kg);
					}
				} else {

				}
		}
		return $goods_number;
	}
	//根据获取仓库库位信息
	public function get_position_name_by_id($id){
		$position = M("position_category", "t_")->where(array("id"=>$id))->find();
	}
	public function get_full_position($goods_code){
		$db1 = M("position", "t_");
		$db2 = M("position_category", "t_");
		$map = array(
			"code" => $goods_code
		);
		$position_id = $db1->where($map)->getField("position_id");
		$map = array(
			"id" => $position_id
		);
		$position = $db2->where($map)->find();
		if($position){
			$warehouseId = $position["wherehouse_id"];
			$fullname = $position["name"];
		} else {
			//$goods_info = $this->base_get_goods_info($goods_code);
			$warehouse = $this->base_get_default_warehouse();
			$warehouseId = $warehouse["warehouseId"];
			$fullname = "";
		}
		
		while ($position["pid"] > 0) {
			$map = array(
				"id" => $position["pid"]
			);
			$position = $db2->where($map)->find();
			$fullname = $position["name"]."|".$fullname;
		}
		$fullname = $fullname ? $this->get_warehouse_name_by_id($warehouseId)."|".$fullname : $this->get_warehouse_name_by_id($warehouseId);
		return $fullname;
	}
	//获取默认的仓库信息
	public function base_get_default_warehouse(){

		$sql = "select value from t_config where id = '2001-01' ";
		$data = M()->query($sql);
		if ($data) {
			$warehouseId = $data[0]["value"];
			$sql = "select id, name from t_warehouse where id = '%s' ";
			$data = M()->query($sql, $warehouseId);
			if ($data) {
				$result["warehouseId"] = $data[0]["id"];
				$result["warehouseName"] = $data[0]["name"];
			}
		} else {
			$ware = M("warehouse", "t_")->find();
			if($ware){
				$result["warehouseId"] = $ware["id"];
				$result["warehouseName"] = $ware["name"];
			}
		}
		return $result;
	}

	//根据id获取仓库名称
	public function get_warehouse_name_by_id($id){
		$sql = "select id, name from t_warehouse where id = '%s' ";
		$data = M()->query($sql, $id);
		return $data[0]["name"];
	}
	//根据id获取用户名称
	public function get_user_name_by_id($id){
		$map = array(
			"id" => $id
		);
		$name = M("user", "t_")->where($map)->getField("name");
		return $name;
	}
	//根据id获取单位
	public function get_unit_name_by_id($id){
		$map = array(
			"id" => $id
		);
		$name = M("goods_unit", "t_")->where($map)->getField("name");
		return $name;
	}
	//根据客户id获取客户名称
	public function get_customer_name_by_id($id){
		$map = array(
			"id" => $id
		);
		$name = M("customer", "t_")->where($map)->getField("name");
		return $name;
	}
	//获取电商的userid
	public function get_mall_user_id_by_customerid($customer_id){
		$map = array(
			"id" => $customer_id
		);
		$code = M("customer", "t_")->where($map)->getField("code");
		return $code;
	}
	//根据openid获取用户信息
	public function get_customer_by_openid($openid){
		if(!$openid){
			return false;
		}
		$map = array(
			"wecha_id" => $openid
		);
		$cus = M("customer", "t_")->where($map)->find();
		return $cus;
	}
	//根据id获取订单号
	public function get_order_ref_by_id($id){
		$map = array(
			"id" => $id
		);
		$ref = M("ws_bill", "t_")->where($map)->getField("ref");
		return $ref;
	}
	//获取用户信息
	

	//获取自动单据操作者
	public function base_get_auto_op_user(){
		$map = array(
			"name" => "默认自动操作用户"
		);
		$userid = M("config", "t_")->where($map)->getField("value");
		if(!$userid){
			$map = array(
				"login_name" => "admin"
			);
			$userid = M("user", "t_")->where($map)->getField("id");
		}
		return $userid;
	}

	//根据柜号获取柜子信息
	public function get_box_info_by_no($cno, $bno){
		$cno = intval($cno);
		$map = array(
			"no" => $cno
		);
		$info = M("cabinet")->where($map)->find();
		if($info){
			$boxinfo = $info["name"].$bno."号箱";
		} else {
			$boxinfo = "";
		}
		return $boxinfo;
	}	

	//根据配置id获取到配置的值
	public function getConfig($config_id){
		$map = array(
			"id" => $config_id
		);
		$value = M("config", "t_")->where($map)->getField("value");
		return $value;
	}

	/**
	 * 盘点当前用户的session是否已经失效
	 * true: 已经不在线
	 */
	protected function isNotOnline() {
		return session("loginUserId") == null;
	}

	/**
	 * 当用户不在线的时候，返回的提示信息
	 */
	protected function notOnlineError() {
		return $this->bad("当前用户已经退出系统，请重新登录");
	}

	/**
	 * 返回空列表
	 */
	protected function emptyResult() {
		return array();
	}

	/**
	 * 盘点日期是否是正确的Y-m-d格式
	 * @param string $date
	 * @return boolean true: 是正确的格式
	 */
	protected function dateIsValid($date) {
		$dt = strtotime($date);
		if (! $dt) {
			return false;
		}
		
		return date("Y-m-d", $dt) == $date;
	}

	/**
	 * 格式化价格，保留2位小数
	 * @param float $money
	 * @return float 11.11
	 */
	public function format_money($money, $suc = 2){
		return round($money, $suc);
	}

	public function httpGet($url) {


		
	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($curl, CURLOPT_URL, $url);

	    $res = curl_exec($curl);
	    curl_close($curl);

	    return $res;
  	}

	    //全局统一获取accesstoken的方法，
    public function get_access_token($token = '', $force = false){
    	$weixin_config = C("WEIXIN");
    	$url = C("WEIXIN_ACCESS_TOKEN_URL");
    	$result = $this->httpGet($url);
    	$result = json_decode($result, true);
    	return $result["access_token"];
        $token = $token || $weixin_config["TOKEN"];
        $appid = $weixin_config["APPID"];
        $appsecret = $weixin_config["APPSECRET"];
        $data = S("access_token_".$token);
        if($data["expire_time"] < time()){
            $data = null;
        }
        if($force){
            $data = null;
        }
        if ( !$data || is_null($data) || empty($data) ) {
          $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
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

    public function time_log($content){
    	$data = array(
    		"content" => $content,
    		"addtime" => time()
    	);
    	M("exe_time")->add($data);
    }

}
