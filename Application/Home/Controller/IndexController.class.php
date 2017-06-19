<?php
namespace Home\Controller;
use Think\Controller;

use Home\Service\UserService;
use Home\Service\TodoService;
use Home\Service\MallService;
use Home\Service\BoxService;

class IndexController extends Controller {
    public function index(){
		$us = new UserService();
		
		$this->assign("title", "首页");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		//获取已有的权限
		$permissions = $us->allPermission();
		//待办事项三大块

		//数据汇总三大块
		
		if ($us->hasPermission()) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
    }

    public function todoList(){
    	$ts = new TodoService();
    	$this->ajaxReturn($ts->getTodoList());
    }

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


    public function syn_order(){
    	$action = I("request.action");
    	$db     = M("ws_bill");
    	$db_detail = M("ws_bill_detail");
    	$db_user = M("customer");
    	$us = new UserService();
    	if(!$action){
    		$action = "add";
    	}
    	//电商订单
    	$order_info = json_decode(htmlspecialchars_decode(I("request.order_info")), 1);
    	$goods_info = json_decode(htmlspecialchars_decode(I("request.goods_info")), 1);
    	$user_info  = json_decode(htmlspecialchars_decode(I("request.user_info")), 1);
    	//erp订单初始化
    	$order      = json_decode(htmlspecialchars_decode(I("request.order")), 1);
    	$order_id   = $order_info["order_id"];
    	//查看是否已经同步过了，防止重复同步
    	$map = array(
    		"order_id" => $order_id
    	);
    	$is_exist_order = $db->where($map)->find();
    	if($is_exist_order){
    		$this->err("该订单已经同步过了");
    	}
    	if(!$order_info || !$goods_info){
    		$this->err("订单信息不完整");
    	}
    	//首先同步用户
    	$user_cate = 'CDD1DE38-B811-11E4-8FC9-782BCBD7746B';
    	$map = array('code' => $order_info['user_id']);
    	$user_exist = $db_user->where($map)->find();
    	if(!$user_exist){
    		$custome_id = $us->genUUID();
    		$new_user = array(
    			'code' => $order_info['user_id'], 
    			'id' => $custome_id,
				'name' => $user_info['user_name'], 
				'category_id' => $user_cate, 
				'wecha_id'=>$user_info['openid']
			);
			$new_user["contact01"] = $order_info['consignee'];
			$new_user["address"] = $order_info['address'];
			$new_user["mobile01"] = $user_info['mobile_phone'];
			$new_user["wecha_id"] = $user_info['openid'];
			if($user_info['openid']){
				$new_user["lasttime"] = time();
			}
			$db_user->add($new_user);
    	} else {
    		$custome_id = $user_exist["id"];
    	}
    	$order["customer_id"] = $custome_id;
    	//分区信息写入
    	$order["areaid"]   = 1000;
		$order["areaname"] = "未分区";
		if($order["sitename"]){
			$map = array("name"=> $order["sitename"]);
			$site_count = M("site")->where($map)->count();
			if($site_count > 1){
				$site = M("site")->where(array("id"=>$order["siteid"]))->find();
			} else {
				$site = M("site")->where($map)->find();
			}
			$map = array("id"=> $site["category_id"]);
			$area = M("site_category")->where($map)->find();
			$order["areaid"]   = $area["id"];
			$order["areaname"] = $area["name"];
		} else {//如果不存在，则匹配历史记录，如果不存在历史记录，则需人工匹配了
			$map = array("address"=> $order["address"]);
			$address_site = M("address_site")->where($map)->find();
			if($address_site){
				$order["areaid"]   = $address_site["areaid"];
				$order["areaname"] = $address_site["areaname"];
				$order["siteid"]   = $address_site["siteid"];
				$order["sitename"] = $address_site["sitename"];
			}
		}
		$ref = '1' . $order_info['order_sn'];
    	//开始分单
		$goods_list = array();
		//是否要冷热分单
		$diff_storage = false;
		foreach ($goods_info as $g) {
			$erp_goods_info = M("goods")->where(array('code' => $g['goods_code']))->find();
			$goods_type = $erp_goods_info['storage'] < 1 ? 0 : $erp_goods_info['storage'];//商品属性:0常温、1冷藏
			if($diff_storage == false){
				$goods_type = 0;
			}
			$goods_list[$goods_type][] = array( 'date_created' => $order["bizdt"],
				'goods_id' => $erp_goods_info['id'], 'goods_count' => $g['goods_number'], 'goods_price' => $g['goods_price'], 'goods_money' => $g['goods_price'] * $g["goods_number"],
				'goods_code' => $g['goods_code'], 'goods_sale_price' =>$erp_goods_info['sale_price'], 'rec_id' => $g['rec_id'], 'market_price' => $g['market_price'], 'goods_attr' => $g['goods_attr'],
				'order_sn' => $order_info['order_sn'], 'delivery_date' => $g['delivery_date'], 'delivery_time' => $g['last_afternoon'], 'bulk' => $erp_goods_info['bulk'], 'discount'=>$g['discount']);
			$order['delivery_date'] = $g['delivery_date'];
			$order['delivery_time'] = $g['last_afternoon'] == "上午" ? 1 : 2 ;
		}
		//常温冷藏订单开始分上下午的单
		$new_goods_list = array();
		if($goods_list[0]){
			foreach ($goods_list[0] as $g) {
				$date_key = $g['delivery_date']."_".$g['delivery_time'];
				$new_goods_list[0][$date_key][] = $g;
			}
		}
		if($goods_list[1]){
			foreach ($goods_list[1] as $g) {
				$date_key = $g['delivery_date']."_".$g['delivery_time'];
				$new_goods_list[1][$date_key][] = $g;
			}
		}
		$i = 0;
		$db->startTrans();
		if (!empty($new_goods_list[0])) {
			foreach ($new_goods_list[0] as $date) {
				if($date){
					$order_price = 0;
					$order["ref"] = $ref . $i;
					foreach ($date as $good) {
						$order_price += $good['goods_money'];
						$good['wsbill_id'] = $order["ref"];
						$order['delivery_date'] = $good['delivery_date'];
						$order['delivery_time'] = $good['delivery_time'] == "上午" ? 1 : 2 ;
						$rs = $db_detail->add($good);
						if(!$rs){
							$db->rollback();
							$this->err("导入订单出错");
						}
					}
					$order['sale_money'] = $order_price;
					$order['mall_money'] = $order_price;
					$order['storage'] = 0;
					$rs = $db->add($order);
					//只记录一次折扣
					$order["discount"] = 0;
					if(!$rs){
						$db->rollback();
						$this->err("导入订单出错");
					}	
					$order["shipping_fee"] = 0; //只记录一次运费	
					$i++;
				}
			}
		}

		if (!empty($new_goods_list[1])) {
			foreach ($new_goods_list[1] as $date) {
				if($date){
					$order_price = 0;
					$order["ref"] = $ref . $i;
					foreach ($date as $good) {
						$order_price += $good['goods_money'];
						$good['wsbill_id'] = $order["ref"];
						$order['delivery_date'] = $good['delivery_date'];
						$order['delivery_time'] = $good['delivery_time'] == "上午" ? 1 : 2 ;
						$rs = $db_detail->add($good);
						if(!$rs){
							$db->rollback();
							$this->err("导入订单出错");
						}
					}
					$order['sale_money'] = $order_price;
					$order['mall_money'] = $order_price;
					$order['storage'] = 1;
					$rs = $db->add($order);
					//只记录一次折扣
					if(!$rs){
						$db->rollback();
						$this->err("导入订单出错");
					}	
					$i++;
				}
			}
		}
		//$ms = new MallService();
		//$ms->syn_order_success($order_id);
		$db->commit();
		$this->suc(1);
	}

	public function change_type(){  //JX订单改类型
		$oid = I("request.oid");
		$v = strpos($oid,'JX');
		$db     = M("ws_bill");
		$data['type'] = 5;
		if(!$v && $v !== 0){
		}else{
			$db->where("order_sn='$oid'")->save($data); 
		}		
	}	
	
	public function fix_notify_box(){
		$map = array(
			"status" => 3,
			"code" => "1ConnectionString 属性尚未初始化。",
			"is_bu" => 0
		);
		$limit = 20;
		$list = M("box_log")->where($map)->limit($limit)->select();
		$box = new BoxService();
		foreach ($list as $key => $value) {
			$map = array(
				"id" => $value["id"]
			);
			M("box_log")->where($map)->setField("is_bu", 1);
			$box->notify_box($value["ref"]);
		}
		die($limit." finished");
	}

}