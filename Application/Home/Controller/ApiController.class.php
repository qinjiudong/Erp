<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Common\FIdConst;
use Home\Service\WSBillService;
use Home\Service\SRBillService;
use Home\Service\ApiService;
use Home\Service\ICBillService;
use Home\Service\GoodsService;
use Home\Service\PickService;
use Home\Service\MallService;
use Home\Service\IdGenService;
use Home\Service\SmsService;
use Home\Service\BoxService;
use Home\Service\AutoService;
use Home\Service\PWBillService;
use Home\Service\IABillService;
/**
 * 对外API接口
 * @author dubin
 *
 */
class ApiController extends Controller {

	//定义错误
	public function err($msg){
		$ret = array("success" => false, "msg" => $msg);
		$this->ajaxReturn($ret);
	}

	public function suc($data, $extend = array()){
		
		if($extend){
			$ret = array("success" => true);
			$ret = array_merge($ret, $extend);
			$ret["data"] = $data;
		} else {
			$ret = array("success" => true, "data" => $data);
		}
		$this->ajaxReturn($ret);
	}

	public function fix_pandian_goods(){
		$map = array(
			"status" => "0"
		);
		//联营的自动入库
		$goods_list = M("goods")->where($map)->select();
		foreach ($goods_list as $key => $v) {
			$map =array(
				"goods_id" => $v["id"]
			);
			$result = M("inventory")->where($map)->find();
			if(!$result){
				$data = array(
					"goods_id" => $v["id"],
					"in_count" => 0,
					"in_price" => 0,
					"in_money" => 0,
					"balance_count" => 0,
					"balance_price" => 0,
					"balance_money" => 0,
					"warehouse_id" => "17A72FFA-B3F3-11E4-9DEA-782BCBD7746B"
				);
				M("inventory")->add($data);
			}
		}

	}

	public function fix_inv_stock(){
		foreach ($list as $key => $value) {
			$map = array(
				"code" => $value["code"]
			);
			$goods_info = M("goods")->where($map)->find();
			dump($goods_info);
			if($goods_info){
				$map = array(
					"goods_id" => $goods_info["id"]
				);
				$data = array(
					"balance_count" => $value["stock"],
					"balance_money" => 0,
					"balance_price" => 0
				);
				dump($map);
				dump($data);
				M("inventory")->where($map)->save($data);
			}
			
		}
	}

	//测试接口
	public function test(){
		$ic = new ICBillService();
		//$fullname = $ic->get_full_position("000001");
		//dump($fullname);
		echo $ic->get_access_token();
	}

	//登陆接口
	public function login(){


		$username = I("request.username");
		$password = I("request.password");
		$us = new UserService();
		$ret = $us->doLogin($username,$password);
		if($ret["success"] == true){
			$ret["data"] = $ret["id"];
			unset($ret["id"]);
		}
		
		$this->ajaxReturn($ret);
	}

	//获取站点接口
	public function site(){
		$db = M("site", "t_");
		$areaid = I("request.areaid");
		$width_order = I("request.with_order");
		$delivery_date = I("request.delivery_date");
		$delivery_time = I("request.delivery_time");
		$map = array();
		
		if($width_order){
			$sql = "select * from t_site where 1 = 1 ";
			if($areaid){
				$sql .= "and line_id = '$areaid' ";
			}
			if($delivery_date){
				$order_sql = "select siteid from t_ws_bill where bill_status in (0,1) and delivery_date = '$delivery_date' ";
				if($delivery_time){
					$order_sql .= " and delivery_time = '$delivery_time' ";
				}
				$sql .=" and id in($order_sql)";
			} else {

			}
			$data = $db->query($sql);
		} else {
			if($areaid){
				$map["line_id"] = $areaid;
			}
			$data = $db->where($map)->select();
		}
		
		$site_list = array();
		foreach ($data as $key => $value) {
			$site = array(
				"id" => $value["id"],
				"site_id" => $value["id"],
				"name" => $value["name"],
				"attr" => $value["attr"],
				"freight" => $value["freight"],
				"des"  => $value["des"]
			);
			$site_list[] = $site;
		}
		$this->suc($site_list);
	}
	//获取所有站点接口2
	public function site2(){
		$db = M("site_category", "t_");
		$data = $db->order("code")->order("sort desc,code asc")->select();
		$area_list = array();
		foreach ($data as $key => $value) {
			//获取本区域下的所有站点
			$map["category_id"] = $value["id"];
			$data = M("site", "t_")->where($map)->order("code")->select();
			$site_list = array();
			foreach ($data as $key2 => $value2) {
				$site = array(
					"id" => $value2["id"],
					"site_id" => $value2["id"],
					"name" => $value2["name"],
					"attr" => $value2["attr"],
					"freight" => $value2["freight"],
					"des"  => $value2["des"]
				);
				$site_list[] = $site;
			}
			$site = array(
				"id"   => $value["id"],
				"name" => $value["name"],
				"des"  => $value["des"],
				"sitelist" => $site_list
			);
			if($value['id'] == 1000 || !$value['id']){
				continue;
			}
			$area_list[] = $site;
		}
		$this->suc($area_list);
	}

	public function get_site_by_ids(){
		$db = M("site", "t_");
		
		$siteid = I("request.str");
		$map['id']  = array('in',$siteid); 
		$ret = $db->field('id,name')->where($map)->select();
		$this->ajaxReturn($ret);
	}	
	
	public function get_site_by_id(){
		$db = M("site", "t_");
		$siteid = I("request.siteid");
		$site = $db->where(array("id"=>$siteid))->find();
		$this->suc($site);
	}
	//获取送货区域接口
	public function area(){
		$db = M("site_line", "t_");
		$data = $db->select();
		$site_list = array();
		foreach ($data as $key => $value) {
			$site = array(
				"id" => $value["id"],
				"name" => $value["name"]
			);
			$site_list[] = $site;
		}
		$this->suc($site_list);
	}


	/* 根据erp订单号操作订单 */
	public function finishOrder(){
		$action = I("request.action");
		$userid = I("request.userid");
		//用户id每个操作必须用到，首先做判断
		if(!$userid){
			$this->err("缺少参数：userid");
		}
		//判断用户是否存在
		$us = new UserService();
		$isExist = $us->userExists($userid);
		if(!$isExist){
			$this->err("用户不存在：$userid");
		}
		if($action == "order_info"){
			$ref = I("request.ref");
			$map = array(
				"ref" => $ref
			);
			$info = M("ws_bill")->where($map)->find();
			$this->suc($info);
		} else if ($action == "order_station"){
			$db = M();
			$ref = I("request.ref");
			$map = array(
				"ref" => $ref
			);
			$info = M("ws_bill")->where($map)->find();
			if(!$info){
				$this->err("订单不存在");
			}
			//更改状态，并且同步电商
			if ($info['bill_status'] < 2) {
				return $this->err("订单状态不正确，不能做入站操作");
			}
			$phone = $info["tel"];
			$mall_order_id = $info['order_id'];
			$ms = new MallService();
			$result = $ms->order_status($mall_order_id, 2, 5, $info['delivery_date'], $info['delivery_time']);
			$now = time();
			$sql = "update t_ws_bill set bill_status = 4 , stock_time = $now, pick_type='送货' where id = '".$info["id"]."' ";
			$db->execute($sql);
			//发送短信
			$sms_content = "【淘江阴】您好，您有订单已到站";
			$sms = new SmsService();
			$params = array(
				"phones" => $phone,
				"msg" => $sms_content
			);
			$sms->send($params);
			$this->suc("到站成功");
		} else if ($action == 'order_sign'){
			$db = M();
			$ref = I("request.ref");
			$map = array(
				"ref" => $ref
			);
			$info = M("ws_bill")->where($map)->find();
			if(!$info){
				$this->err("订单不存在");
			}
			//更改状态，并且同步电商
			if ($info['bill_status'] < 2) {
				return $this->err("订单状态不正确，不能做签收操作");
			}
			$phone = $info["tel"];
			$mall_order_id = $info['order_id'];
			$ms = new MallService();
			$result = $ms->order_status($mall_order_id, 3, 5, $info['delivery_date'], $info['delivery_time']);
			$now = time();
			$sql = "update t_ws_bill set bill_status = 5 , pick_time = $now where id = '".$info["id"]."' ";
			$db->execute($sql);
			//发送短信
			/*
			$sms_content = "【淘江阴】你好，你的订单$ref已经到站了哦.";
			$sms = new SmsService();
			$params = array(
				"phones" => $phone,
				"msg" => $sms_content
			);
			$sms->send($params);
			*/
			$this->suc("签收成功");
		}
	}

	//盘库接口
	//包含多个动作@action
	//1，ic_add = 新增盘点单 2，ic_list = 获取盘点单列表， 3，ic_info = 获取盘点单信息，包含盘点单里的所有商品信息
	//4，ic_del = 删除为完成的盘点单 ，5, ic_goods_add = 新增盘点商品,id相同则覆盖数量 , 6，ic_finish = 盘点完成
	public function invCheck(){
		$action = I("request.action");
		$userid = I("request.userid");
		//用户id每个操作必须用到，首先做判断
		if(!$userid){
			$this->err("缺少参数：userid");
		}
		//判断用户是否存在
		$us = new UserService();
		$isExist = $us->userExists($userid);
		if(!$isExist){
			$this->err("用户不存在：$userid");
		}
		$icRight = $us->userIcRight($userid);
		$ic = new ICBillService();
		if($action == "ic_add"){
			$this->ajaxReturn($ic->genNewICBill($userid));
		} else if ($action == "ic_list"){
			$param = array(
				"ref" => I("request.ref"),
				"startdate" => I("request.startdate"),
				"enddate" => I("request.enddate"),
				"bill_status" => $icRight ? I("request.bill_status") : -1,
				"userid" => $userid,
				"page" => I("request.page") ? I("request.page") : 1
			);
			$this->ajaxReturn($ic->icList($param));
		} else if ($action == "ic_info"){
			$id = I("request.id");
			if(!$id){
				$this->err("缺少参数：id");
			}
			$this->ajaxReturn($ic->icInfo($id));
		} else if ($action == "ic_del"){

			$id = I("request.id");
			if(!$id){
				$this->err("缺少参数：id");
			}
			$params = array(
				"id" => $id,
				"userid" => $userid
			);
			$this->ajaxReturn($ic->icDel($params));
		} else if ($action == "ic_goods_add"){
			$id = I("request.id");
			if(!$id){
				$this->err("缺少参数：id");
			}
			$goods_id = I("request.goods_id");
			$goods_count = I("request.goods_count");
			if(!$goods_id){
				$this->err("缺少参数：goods_id");
			}
			if($goods_count === ""){
				$this->err("缺少参数：goods_count");
			}
			$params = array(
				"id" => $id,
				"userid" => $userid,
				"goods_id" => $goods_id,
				"goods_count" => $goods_count
			);
			$this->ajaxReturn($ic->icGoodsAdd($params));
		} else if ($action == "ic_finish"){
			$id = I("request.id");
			if(!$id){
				$this->err("缺少参数：id");
			}
			$params = array(
				"id" => $id,
				"userid" => $userid
			);
			$this->ajaxReturn($ic->icFinish($params));
		}
		$this->err("缺少参数：action");

	}

	/*
	* 拣货接口
	* 包含多个@action
	* 1,pick_list = 根据参数获取到需要拣货的订单列表(order_id:订单号|date:日期-2015-09-24|time:送货时间-0不限，1上午，2下午|)
	* 2,pick_info = 详情获取
	* 3,pick_return = 拣货完成回传
	*/
	public function pick(){
		$action = I("request.action");
		$userid = I("request.userid");
		//用户id每个操作必须用到，首先做判断
		if(!$userid){
			$this->err("缺少参数：userid");
		}
		//判断用户是否存在
		$us = new UserService();
		$isExist = $us->userExists($userid);
		if(!$isExist){
			$this->err("用户不存在：$userid");
		}
		$pk = new PickService();
		if($action == "pick_list"){
			$params = array(
				"delivery_date" => I("request.delivery_date"),
				"delivery_time" => I("request.delivery_time"),
				"siteid" => I("request.siteid"),
				"limit"  => I("request.limit"),
				"areaid" => I("request.areaid"),
				"delivery_type"=> I("request.delivery_type"),
				"userid" => $userid
			);
			$this->ajaxReturn($pk->pickList($params));
		} else if ($action == "pick_list_pre"){
			$params = array(
				"delivery_date" => I("request.delivery_date"),
				"delivery_time" => I("request.delivery_time"),
				"siteid" => I("request.siteid"),
				"limit"  => I("request.limit"),
				"areaid" => I("request.areaid"),
				"delivery_type"=> I("request.delivery_type"),
				"userid" => $userid
			);
			$this->ajaxReturn($pk->pickPreList($params));
		} else if ($action == "pick_info"){
			//获取订单号
			$order_id = I("request.order_id");
			if(!$order_id){
				$this->err("缺少参数：order_id");
			}
			$params = array(
				"order_id" => $order_id,
				"userid" => $userid
			);
			$this->ajaxReturn($pk->pickInfo($params));
		} else if ($action == "pick_unlock"){
			//获取订单号
			$order_id = I("request.order_id");
			if(!$order_id){
				$this->err("缺少参数：order_id");
			}
			$params = array(
				"order_id" => $order_id
			);
			$this->ajaxReturn($pk->pickUnLock($params));
		} else if ($action == "pick_return"){

			//获取订单号
			$order_id = I("request.order_id");
			if(!$order_id){
				$this->err("缺少参数：order_id");
			}
			$params = array(
				"order_id" => $order_id,
				"oos" => I("request.oos"),
				"oos_goods_list" => I("request.oos_goods_list"),
				"success_goods_list" => I("request.success_goods_list"),
			);
			$this->ajaxReturn($pk->pickReturn($params));
		} else if ($action == 'pick_return_pre'){
			//获取订单号
			$order_id = I("request.order_id");
			if(!$order_id){
				$this->err("缺少参数：order_id");
			}
			$params = array(
				"order_id" => $order_id,
				"oos" => I("request.oos"),
				"oos_goods_list" => I("request.oos_goods_list"),
				"success_goods_list" => I("request.success_goods_list"),
			);
			$this->ajaxReturn($pk->pickReturnPre($params));
		} else if ($action == "edit_area"){//编辑拣货区域
			//获取订单号
			$order_id = I("request.order_id");
			if(!$order_id){
				$this->err("缺少参数：order_id");
			}
			$params = array(
				"ref"    => I("request.order_id"),
				"areaid" => I("request.areaid")
			);
			$ws = new WSBillService();
			$this->ajaxReturn($ws->modifyArea($params));
		} else if ($action == "edit_site"){
			//获取订单号
			$order_id = I("request.order_id");
			if(!$order_id){
				$this->err("缺少参数：order_id");
			}
			$params = array(
				"ref"    => I("request.order_id"),
				"siteid" => I("request.siteid")
			);
			$ws = new WSBillService();
			$this->ajaxReturn($ws->modifySite($params));
		}
		$this->err("缺少参数：action");
	}


	//验收接口
	public function ia(){
		$action = I("request.action");
		$userid = I("request.userid");
		//用户id每个操作必须用到，首先做判断
		if(!$userid){
			$this->err("缺少参数：userid");
		}
		//判断用户是否存在
		$us = new UserService();
		$isExist = $us->userExists($userid);
		if(!$isExist){
			$this->err("用户不存在：$userid");
		}
		$pw = new PWBillService();
		$ia = new IABillService();
		if($action == "pw_list"){
			$pw_ref      = I("request.ref");
			$begindate     = I("request.begindate");
			$enddate     = I("request.enddate");
			$pw_supplier = I("request.supplier");
			$params = array(
				"begindate"   => $begindate,
				"enddate" => $enddate,
				"billid" => $pw_ref,
				"supplier" => $pw_supplier,
				"pda" => 1
			);
			$pw_list_result = $pw->pwbillList($params);
			$pwlist = $pw_list_result["dataList"];
			$totalCount = $pw_list_result["totalCount"];
			$ret = array(
				"success" => true,
				"data" => $pwlist,
				"totalCount" => $totalCount
			);
			$this->ajaxReturn($ret);
			//echo json_encode($ret);
			exit;
		} else if($action == "pw_info"){
			$pwid = I("request.id");
			if(!$pwid){
				$this->err("缺少参数：id");
			}
			$pwinfo = $pw->pwBillInfo($pwid);
			$this->suc($pwinfo);
		} else if($action == "pw_return"){
			$ref = I("request.pwref");
			$items = I("request.items");
			$params = array(
				"pwref" => $ref,
				"items" => $items
			);
			$result = $ia->uploadIABill($params);
			if($result["success"] == false){
				$this->ajaxReturn($result);
			} else {
				$iaid = $result["id"];
				$iaref = M("ia_bill")->where(array("id"=>$iaid))->getField("ref");
				$ret = array(
					"success" => true,
					"data" => array("iaref" => $iaref)
					
				);
				$this->ajaxReturn($ret);
			}
			
		}
		$this->err("api error");
	}

	//同步库存接口
	public function syn_inventory(){
		$goods_code = I("request.goods_code");
		if(!is_array($goods_code)){
			if(strpos($goods_code, ",") > -1){
				$goods_code = explode(",", $goods_code);
			} else {

			}
			$goods_code = array($goods_code);
		}
		$ret = array();
		foreach ($goods_code as $key => $value) {
			$goods_id = M("goods", "t_")->where(array("code"=>$value))->getField("id");
			$sql = "SELECT sum(balance_count) as balance_count , warehouse_id FROM `t_inventory` WHERE `goods_id` = '%s'";
			$data = $db->query($sql, $goods_id);
			$goods = array(
				"goods_code"   => $value,
				"goods_number" => $data[0]['balance_count']
			);
			$ret[] = $goods;
		}
		$this->suc($ret);
	}

	//电商用户接口
	public function mall_user(){
		//电商用户分类
		$user_category = 'CDD1DE38-B811-11E4-8FC9-782BCBD7746B';
		$db = M("customer", "t_");
		$action = I("request.action");
		if(!$action){
			$this->err("parameter miss : action");
		}
		if($action == "add"){
			$data = array(
				"code" => I("request.user_id"),
				"name" => I("request.user_name"),
				"category_id" => $user_category,
				"mobile01" => I("request.mobile"),
				"wecha_id" => I("request.wecha_id"),
				"cardno" => I("request.cardno"),
			);
			foreach ($date as $key => $value) {
				if(!$value){
					$this->err("parameter miss : ".$key);
				}
			}
			//判断是否已经存在
			$map = array(
				"code" => I("request.user_id"),
				"name" => I("request.user_name"),
				"mobile01" => I("request.mobile"),
				"_logic" => "OR"
			);
			$isExist = $db->where($map)->find();
			if($isExist){
				$this->err("user exists");
			}
			$idGen = new IdGenService();
			$id = $idGen->newId();
			$data["id"] = $id;
			$result = $db->add($data);
			if($result){
				$this->suc();
			} else {
				$this->suc("fail to add new user, please retry.");
			}
		} else if ($action == "edit"){
			$data = array(
				"mobile01" => I("request.mobile"),
				"wecha_id" => I("request.wecha_id"),
				"cardno" => I("request.cardno"),
			);
			$map = array(
				"code" => I("request.user_id")
			);
			if(!$map["code"]){
				$this->err("parameter miss : user_id");
			}
			$result = $db->where($map)->save($data);
			$this->suc();
		} else if ($action == "list"){
			$page  = I("request.page");
			if(!$page){
				$page = 1;
			}
			$limit = 100;
			$map = array();
			$start = ($page - 1) * $limit;
			$list = $db->where($map)->limit($start, $limit)->order("code desc")->select();
			$totalCount = $db->where($map)->count();
			$newlist = array();
			foreach ($list as $key => $value) {
				$newlist[$key]["user_id"] = $value["code"];
				$newlist[$key]["user_name"] = $value["name"];
				$newlist[$key]["mobile"] = $value["mobile01"];
				$newlist[$key]["cardno"] = $value["cardno"];
			}
			$extend = array(
				'totalcount' => $totalCount,
				"page"       => $page,
				"limit"      => $limit
			);
			$this->suc($newlist,$extend);
		} else if ($action == "info"){
			$userid = I("request.user_id");
			$map = array(
				'code' => $userid
			);
			if(!$map["code"]){
				$this->err("parameter miss : user_id");
			}
			$data = $db->where($map)->find();
			if($data){
				$this->suc($data);
			} else {
				$this->err("user not exists: ".$userid);
			}
		}
		$this->err("parameter error : action");
	}

	//ERP后台组织机构接口
	public function org(){
		$us = new UserService();
		$action = I("request.action");
		//获取全部列表
		if($action == "list"){
			$org_list = $us->allOrgs();
			$this->suc($org_list);
		}
		$this->err("parameter error : action");
	}

	//ERP后台用户接口
	public function user(){
		$us = new UserService();
		$action = I("request.action");
		$userid = I("request.userid");
		if(!$action){
			$this->err("parameter miss : action");
		}
		if(!$userid){
			$this->err("parameter miss : userid");
		}
		if($action == "info"){
			//读取用户信息
			$user_info = $us->getUserById($userid, true);
			$this->suc($iser_info);
		}  else if($action == "list"){

		}
		$this->err("parameter error : action");
	}

	//短信发送接口
	public function sms(){
		$action  = I("request.action");
		$mobiles = I("request.phone");
		$userid  = I("request.user_id");
		if(!$action){
			$action = "send";
		}
		if(!$mobiles && !$userid){
			$this->err("parameter miss : phone or user_id");
		}
		$sms = new SmsService();
		if($action == "send"){
			$msg = I("request.msg");
			if(!$msg){
				$this->err("parameter miss : msg");
			}
			$params = array(
				"phones" => $mobiles,
				"msg" => $msg
			);
			$ret = $sms->send($params);
			if($ret["success"] == true){
				$this->suc();
			} else {
				$this->err("短信发送失败，请重试.");
			}
		}
	}

	//微信发送接口
	public function wx_message(){
		$action = I("request.action");
		$userid = I("request.user_id");
		$mobile = I("request.phone");
		$sms    = I("request.sms");
		if(!$action){
			$action = "send";
		}
		if(!$userid){
			$this->err("parameter miss : user_id");
		}
		$sms = new SmsService();
		if($action == "send"){
			$msg = I("request.msg");
			if(!$msg){
				$this->err("parameter miss : msg");
			}
			$params = array(
				"userid" => $userid,
				"msg" => $msg,
				"phone" => $mobile,
				"sms" => $sms
			);
			$ret = $sms->wx_send($params);
			if($ret["success"] == true){
				unset($ret["success"]);
				$this->suc($ret);
			} else {
				$this->err("微信发送失败，请重试.");
			}
		}
	}

	//自动发送接口
	public function auto_message(){
		$action = I("request.action");
		$userid = I("request.user_id");
		$mobile = I("request.phone");
		if(!$action){
			$action = "send";
		}
		if(!$user_id && !$mobile){
			$this->err("parameter miss : user_id or phone");
		}
		$sms = new SmsService();
		if($action == "send"){
			$msg = I("request.msg");
			if(!$msg){
				$this->err("parameter miss : msg");
			}
			$params = array(
				"userid" => $userid,
				"msg" => $msg,
				"phone" => $mobile
			);
			$ret = $sms->auto_send($params);
			if($ret["success"] == true){
				$this->suc();
			} else {
				$this->err("发送失败，请重试.");
			}
		}
	}

	/*
	* @远程调用接口
	*/
	public function box(){
		$params = array(
			"sendtype" => intval(I("request.sendtype")),
			"ref" => I("request.ref"),
			"cabinetno" => I("request.cabinetno"),
			"boxno" => I("request.boxno"),
			"id" => I("request.ref"),
			"code" => I("request.code")
		);
		$box = new BoxService();
		$api = new ApiService();
		if($params["sendtype"] === 0){
			$this->ajaxReturn($api->finish($params));
		} else if($params["sendtype"] === 1){
			$this->ajaxReturn($api->user_pick($params));
		}
	}

	/*
	* @送货上门接口
	*/
	public function delivery_pick(){
		$params = array(
			"sendtype" => intval(I("request.sendtype")),
			"ref" => I("request.ref"),
			"id"  => I("request.id")
		);
		$api = new ApiService();
		if($params["sendtype"] === 0){
			//$this->ajaxReturn($api->finish($params));
		} else if($params["sendtype"] === 1){
			$this->ajaxReturn($api->delivery_pick($params));
		}
	}

	/*
	 * @获取access_token
	 */
	public function access_token(){
		$api = new ApiService();
		$token = I("request.token");
		$wxuser = C("WEIXIN");
		if($wxuser["TOKEN"] == $token){
			$access_token = $api->get_access_token();
			$ret = array(
				"access_token" => $access_token
			);
		} else {
			$ret = array(
				"access_token" => ""
			);
		}
		$this->ajaxReturn($ret);
	}

	/*
	 * @根据openid更新user的交互时间
	 */
	public function update_lasttime(){
		$openid = I("request.openid");
		if($openid){
			$map = array(
				"wecha_id" => $openid
			);
			$db = M("customer", "t_");
			if( $db->where($map)->find() ){
				$data = array(
					"lasttime" => time()
				);
				$db->where($map)->save($data);
			} else {
				$db2 = M("wechat_member_enddate", "t_");
				$map = array(
					"openid" => $openid
				);
				$data = array(
					"openid" => $openid,
					"enddate" => time()
				);
				if($db2->where($map)->find()){
					$db2->where($map)->save($data);
				} else {
					$db2->add($data);
				}
			}
			$this->err("update successful");
		} else {
			$this->err("no openid");
		}
	}

	//根据openid获取到柜信息
	public function get_box_info(){
		$api = new ApiService();
		$params = array(
			"openid" => I("request.openid")
		);
		if(!I("request.openid")){
			$this->err("miss parameter openid");
		}
		$this->suc($api->get_box_info($params));
	}


	//拣货
	public function Pick1() {
		$api = new ApiService();
		$id = I("request.id");
		if(I("request.pick_type") == 1){
			$this->ajaxReturn($api->PickOrder($id));
		}
		$this->ajaxReturn($api->Pick($id));
	}
	//出库
	public function Outlib() {
		$api = new ApiService();
		$id = I("request.id");
		$this->ajaxReturn($api->Outlib($id));
	}
	//签收
	public function Finish() {
		$api = new ApiService();
		$id = I("request.id");
		$this->ajaxReturn($api->Finish($id));
	}	
	
	public function ICBill() {
    $params['id'] = '';
    $params['bizDT'] = date('Y-m-d');
    $params['warehouseId'] = '17A72FFA-B3F3-11E4-9DEA-782BCBD7746B';
    $params['bizUserId'] = '6C2A09CD-A129-11E4-9B6A-782BCBD7746B';
    $params['items'] = json_decode(html_entity_decode(I("request.items")), true);
		$ic = new ICBillService();
		$this->ajaxReturn($ic->editICBill(array('jsonStr' => json_encode($params))));
	}
	//获取商品信息
	public function get_good_info(){
		$gs = new ApiService();
		$barCode = I("request.bar_code");
		$goodsCode = I("request.goods_code");
		if(!$barCode && !$goodsCode){
			$this->err("缺少参数：bar_code or goods_code");
		}
		$params = array(
			"barCode" => $barCode,
			"goodsCode" => $goodsCode
		);
		$this->ajaxReturn($gs->getGoodsInfo($params));	
	}


	//修复仓位导入后的遗留问题
	public function fix_goods(){
		$db_c = M("goods_category");
		$db   = M("goods");
		//获取到分类
		$map = array(
			"barCode" => array("eq", ""),
			"LENGTH(code)" => array("lt",7),
			"sale_price" => array("eq",0)
		);
		
		$map = array(
			"category_id" => array("eq", "")
		);
		
		/*
		$list = M("goods_unit")->select();
		foreach ($list as $key => $value) {
			$unit_name = $value["name"];
			$unit_id   = $value["id"];
			$map = array(
				"unit_id" => $unit_name
			);
			$data = array(
				"unit_id" => $unit_id
			);
			$db->where($map)->save($data);
		}
		*/
		/*
		$list = $db->where($map)->select();
		foreach ($list as $key => $value) {
			$data = array(
				"id" => $value["id"],
				"code" => $value["code"],
				"name" => $value["name"]
			);
			$code = $value["code"];
			if(strlen($code) == 2){
				$data["parent_id"] = 0;
			} else if (strlen($code) == 4){
				$data["parent_id"] = $code{0}.$code{1};
			} else if (strlen($code) == 6){
				$data["parent_id"] = $code{0}.$code{1}.$code{2}.$code{3};
			}
			if(strlen($code) > 6){
				continue;
			}
			$db_c->add($data);
		}
		exit;
		*/
		$list = $db->where($map)->select();
		$category = 0;
		foreach ($list as $key => $v) {
			$code = $v["code"];
			if(strlen($code) == 6){
				$category = $code{0}.$code{1}.$code{2}.$code{3};
			} else if(strlen($code) == 4){
				$category = $code{0}.$code{1};
				
			} else {
				$category = $code{0}.$code{1}.$code{2}.$code{3}.$code{4}.$code{5};
			}
			$map = array(
					"id" => $v["id"]
				);
				$data = array(
					"category_id" => $category
				);
				$db->where($map)->data($data)->save();

		}
		
	}

	public function autoAcceptance(){
		$map = array(
			"auto_status" => 0,
			"bill_status" => array("gt", 1)
		);
		$order_info = M("ws_bill")->where($map)->limit(1)->find();
		$auto = new AutoService();
		die(json_encode($auto->autoAcceptance($order_info["id"])));
	}

	public function fix_ia_bill(){
		$map = array(
			"supplier_id" => array("eq",""),
			"type" => 1
		);
		$list = M("ia_bill")->where($map)->select();
		dump($list);
		exit;
		foreach ($list as $key => $value) {
			//读取详情
			$map = array(
				"iabill_id" => $value["id"]
			);
			$detail = M("ia_bill_detail")->where($map)->find();
			//获取到首选供应商
			$map = array(
				"goodsid" => $detail["goods_id"]
			);
			$supplierId = M("supplier_goods", "t_")->where($map)->order("id asc")->getField("supplierid");
			$map = array(
				"id" => $value["id"]
			);
			$data = array(
				"supplier_id" => $supplierId,
				"warehouse_id" => "17A72FFA-B3F3-11E4-9DEA-782BCBD7746B"
			);
			M("ia_bill")->where($map)->save($data);
		}
	}

	public function fix_pw_bill(){
		$map = array(
			"supplier_id" => array("eq",""),
			"type" => 1
		);
		$list = M("pw_bill")->where($map)->select();
		foreach ($list as $key => $value) {
			//读取详情
			$map = array(
				"pwbill_id" => $value["id"]
			);
			$detail = M("pw_bill_detail")->where($map)->find();
			//获取到首选供应商
			$map = array(
				"goodsid" => $detail["goods_id"]
			);
			$supplierId = M("supplier_goods", "t_")->where($map)->order("id asc")->getField("supplierid");
			$map = array(
				"id" => $value["id"]
			);
			$data = array(
				"supplier_id" => $supplierId,
				"warehouse_id" => "17A72FFA-B3F3-11E4-9DEA-782BCBD7746B"
			);
			M("pw_bill")->where($map)->save($data);
		}
	}

	public function fix_pw_ref(){
		$sql = "select id,ref from t_pw_bill where ref like '%s' order by ref desc limit 1000";
		$data_list = M()->query($sql, "PW20160127" . "%");
		$suf = "0001";
		foreach ($data_list as $data) {
			if ($data) {
				$ref = $data["ref"];
				$nextNumber = (substr($ref, 10));
				if(strlen($nextNumber) == 3){
					$suf = "0".$nextNumber;
				}
				$new_ref = "PW20160127".$suf;
				$map = array(
					"id" => $data["id"]
				);
				M("pw_bill")->where($map)->setField("ref", $new_ref);
			}
		}
		
	}

	public function fix_ia_ref(){
		$sql = "select id,ref from t_ia_bill where ref like '%s' order by ref desc limit 1000";
		$data_list = M()->query($sql, "IA20160127" . "%");
		$suf = "0001";
		foreach ($data_list as $data) {
			if ($data) {
				$ref = $data["ref"];
				$nextNumber = (substr($ref, 10));
				if(strlen($nextNumber) == 3){
					$suf = "0".$nextNumber;
				}
				$new_ref = "IA20160127".$suf;
				$map = array(
					"id" => $data["id"]
				);
				M("ia_bill")->where($map)->setField("ref", $new_ref);
			}
		}
		
	}

	/* 修复验收单子的无税数据 */
	public function fix_no_tax_money(){
		$sql = "select * from t_ia_bill_detail where ISNULL(goods_money_no_tax) limit 1000";
		$list = M("ia_bill_detail")->query($sql);
		$gs = new GoodsService(); 
		foreach ($list as $key => $value) {
			$goods_info = $gs->base_get_goods_info($value["goods_id"]);
			$buytax = intval($goods_info["buytax"]) / 100;
			$tax_money = $value["goods_money"] / (1 + $buytax);
			$map = array(
				"id" => $value["id"]
			);
			M("ia_bill_detail")->where($map)->setField("goods_money_no_tax", $tax_money);
		}
	}

	/* 修复验收单子的无税数据 */
	public function fix_no_tax_money_ia(){
		$sql = "select * from t_ia_bill where ISNULL(goods_money_no_tax) limit 1000";
		$list = M("ia_bill")->query($sql);
		$gs = new GoodsService(); 
		foreach ($list as $key => $value) {
			$map = array(
				"iabill_id" => $value["id"]
			);
			$tax_money = M("ia_bill_detail")->where($map)->sum("goods_money_no_tax");
			$map = array(
				"id" => $value["id"]
			);
			M("ia_bill")->where($map)->setField("goods_money_no_tax", $tax_money);
		}
	}

	/* 修复所有商品的父分类 */
	public function fix_goods_parent_cate(){
		$sql = "select * from t_goods where isnull(parent_cate_id) limit 1000";
		$list = M("goods")->query($sql);
		foreach ($list as $key => $value) {
			$cate_id = $value["category_id"];
			$map = array(
				"id" => $cate_id
			);
			$cate = M("goods_category")->where($map)->find();
			if($cate["parent_id"] != 0){
				$map = array(
					"id" => $cate["parent_id"]
				);
				$cate = M("goods_category")->where($map)->find();
				if($cate["parent_id"] != 0){
					$map = array(
						"id" => $cate["parent_id"]
					);
					$cate = M("goods_category")->where($map)->find();
				}
			}
			$map = array(
				"id" => $value["id"]
			);
			M("goods")->where($map)->setField("parent_cate_id", $cate["id"]);
		}
	}

	/* 订单加入退货金额 */
	public function fix_reject_money_for_wsbill(){
		$map = array(
			"bill_status" => 1000
		);
		$list = M("sr_bill")->where($map)->select();
		foreach ($list as $key => $value) {
			$map = array(
				"id" => $value["ws_bill_id"]
			);
			$data = array(
				"reject_money" => $value["rejection_sale_money"]
			);
			M("ws_bill")->where($map)->data($data)->save();
		}
	}

	/* 订单详情加入退货金额和退货数量 */
	public function fix_reject_money_for_wsbill_detail(){
		$list = M("sr_bill_detail")->select();
		foreach ($list as $key => $value) {
			$map = array(
				"id" => $value["wsbilldetail_id"]
			);
			$data = array(
				"reject_money" => $value["rejection_sale_money"]
			);
			M("ws_bill_detail")->where($map)->data($data)->save();
		}
	}

	/* 修复为负数的进货价格 */
	public function fix_inventory_money_for_ws_bill_detail(){
		$sql = "select * from t_ws_bill_detail where ISNULL(inventory_money)";
		$list = M("ws_bill_detail")->query($sql);
		$gs = new GoodsService(); 
		foreach ($list as $key => $value) {
			$goods_info = $gs->base_get_goods_info($value["goods_id"]);
			$map = array(
				"id" => $value["id"]
			);
			if($goods_info["bulk"] == 0){
				$count = $value["apply_num"];
			} else {
				$count = $value["apply_count"];
			}
			$data = array(
				"inventory_money" => $goods_info["lastbuyprice"] * $count,
				"inventory_price" => $goods_info["lastbuyprice"]
			);
			M("ws_bill_detail")->where($map)->data($data)->save();
		}
	}

	/* 修复订单商品表中的供应商字段 */
	public function fix_supplier_id_for_ws_bill_detail(){
		$sql = "select * from t_ws_bill_detail where ISNULL(supplier_id) limit 5000";
		$list = M("ws_bill_detail")->query($sql);
		foreach ($list as $key => $value) {
			$goodsId = $value["goods_id"];
			$supplier = M("ws_bill_detail")->query("select supplierid from t_supplier_goods where goodsid = '$goodsId' limit 1");
			$supplier_id = $supplier[0]["supplierid"];
			$map = array(
				"id" => $value["id"]
			);
			$data = array(
				"supplier_id" => $supplier_id
			);
			M("ws_bill_detail")->where($map)->save($data);
		}
	}
	/* 修复退货订单的钱 */
	public function fix_ia_reject_money(){
		$sql = "select sum(a.reject_money) as reject_money, sum(a.reject_money / (1 + (g.buytax / 100))) as reject_money_no_tax, a.iabill_id from t_ia_bill_detail a inner join t_goods g on a.goods_id = g.id  where a.reject_money > 0 group by iabill_id";
		$list = M("ia_bill_detail")->query($sql);
		foreach ($list as $key => $value) {
			$map = array(
				"id" => $value["iabill_id"]
			);
			$data = array(
				"reject_money" => $value["reject_money"],
				"reject_money_no_tax" => $value["reject_money_no_tax"]
			);
			M("ia_bill")->where($map)->save($data);
		}
	}
	/* 清缓存 */
	public function clear_cache(){
		S("line_list", null);
		S("site_list", null);
	}
	
	public function costprice(){
		$code = I('post.goods_code');
		$year = 'p'.I('post.year');
		$sql = "select DISTINCT(DATE(day)) as '0',price as '1' from {$year} where code='{$code}' order by DATE(day)";
		$res = M()->query($sql);
		$this->ajaxReturn($res);
	}

	/* 导入盘点单子 */
	public function import_ic_bill(){
		$map = array(
			"is_imported" => 0
		);
		$limit = 100;
		$list = M("import_ic")->where($map)->limit($limit)->select();
		$ic = new ICBillService();
		$idGen = new IdGenService();
		$us = new UserService();
		$id = $idGen->newId();
		$ref = $ic->genNewBillRef();
		$bizDT = date('Y-m-d H:i:s');
		$bizUserId = "6C2A09CD-A129-11E4-9B6A-782BCBD7746B";
		$warehouseId = "17A72FFA-B3F3-11E4-9DEA-782BCBD7746B";
		$db = M();
		// 主表
		$sql = "insert into t_ic_bill(id, bill_status, bizdt, biz_user_id, date_created, 
					input_user_id, ref, warehouse_id)
				values ('%s', 0, '%s', '%s', now(), '%s', '%s', '%s')";
		$rc = $db->execute($sql, $id, $bizDT, $bizUserId, $bizUserId, $ref, 
				$warehouseId);
		//生成一个新的盘点单
		$i = 1;
		foreach ($list as $key => $value) {
			$sql = "select g.id, g.lastbuyprice, i.balance_count from t_goods g, t_inventory i where g.id = i.goods_id and g.code = '".$value["goods_id"]."'";
			$data = $db->query($sql);
			if(!$data){
				$sql = "select g.id, g.lastbuyprice from t_goods g  where g.code = '".$value["goods_id"]."'";
				$data = $db->query($sql);
				$data[0]["balance_count"] = 0;
			}
			$sql = "insert into t_ic_bill_detail(id, date_created, goods_id, goods_count, goods_count_before, goods_money,
							show_order, icbill_id, position)
						values ('%s', now(), '%s', %f, %f, %f, %d, '%s', '%s')";
			$goodsId = $data[0]["id"];
			dump($goodsId);
			if (! $goodsId) {
				continue;
			}
			$goodsCount = $value["goods_count"];
			$goodsMoney = $value["goods_count"] * $data[0]["lastbuyprice"];
			$goodsCountBefore = $data[0]["balance_count"];
			
			$rc = $db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsCountBefore, $goodsMoney, 
					$i, $id, $goodsPosition);
			$i++;
			$map = array(
				"is_imported" => 0,
				"id" => $value["id"]
			);
			M("import_ic")->where($map)->setField("is_imported", 1);
			
		}
	}

	/* 同步库存 */
	public function syn_stock(){
		$map = array(
			"is_syn" => 0
		);
		$limit = 100;
		$list = M("syn_stock")->where($map)->limit($limit)->select();
		$goods_stock = array();
		foreach ($list as $key => $value) {
			$map = array(
				"goods_id" => $value["goods_code"]
			);
			if(M("import_ic")->where($map)->find()){

			} else {
				$goodsId = M("goods")->where(array("code"=>$value["goods_code"]))->getField("id");
				$goods_one_stock = array(
					"goods_id" => $goodsId
				);
				$goods_stock[] = $goods_one_stock;
			}
			$map = array(
				"id" => $value["id"]
			);
			M("syn_stock")->where($map)->setField("is_syn", 1);
		}

		if($goods_stock){
			$ms = new MallService();
			$ms->syn_stock($goods_stock);
		}

	}

}
