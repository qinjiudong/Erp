<?php

namespace Home\Service;

/**
 * 拣货Service
 *
 * @author dubin
 */
class PickService extends ERPBaseService {

	public $orderStatus = array('0' => '待拣货', '1' => '拣货中' , '2' => '已拣货', '3' => '已出库', '4' => '已入站', '5' => '已取货', '6' => '退货');
	public $orderDeliveryTimeStatus = array("全部","上午","下午");
	public $storageStatus = array("常温", "冷藏");
	public $bulkStatus = array("计重", "计个");

	//可拣货的列表查询
	public function pickPreList($params){
		//查询到的列表
		$order_db = M("ws_bill", "t_");
		$delivery_date = $params["delivery_date"];
		$delivery_time = $params["delivery_time"];
		$siteid = $params["siteid"];
		$limit  = intval($params["limit"]) ? intval($params["limit"]) : 100;
		if($limit > 500){
			$limit = 500;
		}
		
		if(!$delivery_date){
			return $this->bad("缺少参数：delivery_date");
		}
		$map = array(
			"bill_status" => array("in",array(0,1)),
			"delivery_date" => $delivery_date
		);
		if($delivery_time){
			$map["delivery_time"] = $delivery_time;
		}
		if($siteid){
			$map["siteid"] = $siteid;
		}
		if($delivery_type == 0){
			$map["pick_type"] = "自提";
		}
		if($delivery_type == 1){
			$map["pick_type"] = "到家";
		}
		if($areaid && !$siteid){
			//获取所有的站点
			$site_list = M("site")->where(array("line_id"=>$areaid))->select();
			$site_array = array();
			foreach ($site_list as $key => $value) {
				$site_array[] = $value["id"];
			}
			$map["siteid"] = array("in", $site_array);
		}
		$order_list = $order_db->where($map)->limit($limit)->select();
		//遍历订单，是否库存充足
		$new_order_list = array();
		foreach ($order_list as $key => $value) {
			$map = array(
				"wsbill_id" => $value["ref"]
			);
			$goods_list = M("ws_bill_detail")->where($map)->select();
			$is_full = true;
			foreach ($goods_list as $k => $goods) {
				$stock = $this->base_get_goods_stock($goods["goods_id"]);
				//echo $stock;
				if($stock < $goods["goods_count"]){
					$is_full = false;
					break;
				}
			}
			if($is_full == false){
				continue;
			}
			$order_id_list[] = $value["ref"];
		}
		//$totalCount = $order_db->where($map)->count();
		$totalCount = count($order_id_list);
		foreach ($order_list as $key => $value) {
			//$order_id_list[] = $value["ref"];
		}
		$ret = array(
			"success" => true,
			"data" => $order_id_list,
			"totalCount" => $totalCount
		);
		return $ret;
	}

	//列表查询
	public function pickList($params){
		//查询到的列表
		$order_db = M("ws_bill", "t_");
		$delivery_date = $params["delivery_date"];
		$delivery_time = $params["delivery_time"];
		$siteid = $params["siteid"];
		$limit  = intval($params["limit"]) ? intval($params["limit"]) : 100;
		$areaid = $params["areaid"];
		$delivery_type= $params["delivery_type"];
		if($limit > 500){
			$limit = 500;
		}
		
		if(!$delivery_date){
			return $this->bad("缺少参数：delivery_date");
		}
		$map = array(
			"bill_status" => array("in",array(0,1)),
			"delivery_date" => $delivery_date
		);
		if($delivery_time){
			$map["delivery_time"] = $delivery_time;
		}
		if($siteid){
			$map["siteid"] = $siteid;
		}
		if($delivery_type === 0){
			$map["pick_type"] = "自提";
		}
		if($delivery_type == 1){
			$map["pick_type"] = "到家";
		}
		if($areaid && !$siteid){
			//获取所有的站点
			$site_list = M("site")->where(array("line_id"=>$areaid))->select();
			$site_array = array();
			foreach ($site_list as $key => $value) {
				$site_array[] = $value["id"];
			}
			$map["siteid"] = array("in", $site_array);
		}
		$order_list = $order_db->where($map)->limit($limit)->select();
		$totalCount = $order_db->where($map)->count();
		$order_id_list = array();
		foreach ($order_list as $key => $value) {
			$order_id_list[] = $value["ref"];
		}
		$ret = array(
			"success" => true,
			"data" => $order_id_list,
			"totalCount" => $totalCount
		);
		return $ret;
	}

	public function pickUnLock($params){
		$order_db = M("ws_bill", "t_");
		$order_info = $order_db->where(array("ref" => $params["order_id"]))->find();
		if(!$order_info){
			return $this->bad("订单不存在");
		}
		$data = array(
			"lock" => 0,
			"lock_time" => time()
		);
		$order_db->where(array("ref" => $params["order_id"]))->save($data);
		$ret = array(
			"success" => true,
			"msg" => "解锁成功"
		);
		return $ret;
	}


	//详情查询
	public function pickInfo($params){
		$order_db = M("ws_bill", "t_");
		$order_db_detail = M("ws_bill_detail", "t_");
		$order_info = $order_db->where(array("ref" => $params["order_id"]))->find();
		if(!$order_info){
			return $this->bad("订单不存在");
		}
		//状态不对也不回传
		$status = $order_info["bill_status"];
		$lock   = $order_info["lock"];
		$lock_time = $order_info["lock_time"];
		if($status > 1){
			//return $this->bad("订单状态不正确");
		}
		if($lock == 1){
			//判断是不是该拣货人的单子
			if($params["userid"] == $order_info["input_user_id"]){
				
			} else {
				//如果在3分钟内，则提醒锁定
				if($lock_time + 180 > time()){
					return $this->bad("订单正在拣货中");
				} else {

				}
			}
			
		}
		//如果获取成功，就将状态修改为拣货中,锁定改订单,加入拣货人
		if($status > 1){

		} else {
			$data = array(
				"bill_status" => 1,
				"lock" => 1,
				"lock_time" => time(),
				"input_user_id" => $params["userid"]
			);
			$order_db->where(array("ref" => $params["order_id"]))->save($data);
		}
		
		$ret = array(
			"id"  => $order_info["id"],
			"ref" => $order_info["ref"],
			"bill_status" => $order_info["bill_status"],
			//"siteid" => $order_info['siteid'],
			"sitename" => $order_info["sitename"],
			"order_date" => date("Y-m-d", strtotime($order_info["bizdt"])),
			"delivery_date" => $order_info["delivery_date"],
			"delivery_time" => $order_info["delivery_time"],
			"delivery_time_str" => $this->orderDeliveryTimeStatus[$order_info["delivery_time"]],
			"besttime"      => $order_info["besttime"],
			"consignee"     => $order_info["consignee"],
			"address"       => $order_info["areaname"]."/".$order_info["sitename"]."/".$order_info["address"],
			"tel"           => $order_info["tel"],
			"sale_money" => $order_info["sale_money"],
			"storage" => $order_info["storage"],
			"storage_str" => $this->storageStatus[$order_info["storage"]],
			"mall_order_ref"  => $order_info["order_sn"],
			"is_out_of_stock" => 0,
			"discount" => $order_info["discount"],
			"shipping_fee" => $order_info["shipping_fee"],
			"pick_type" => $order_info["pick_type"]
		);
		//获取到商品信息
		$map_goods = array(
			"wsbill_id" => $order_info["ref"]
		);
		$goods_list = $order_db_detail->where($map_goods)->select();
		$goods_list_array = array();
		$is_out_of_stock = false;
		foreach ($goods_list as $key => $value) {
			$goods_info = $this->base_get_goods_info($value["goods_id"]);
			$goods_price = $value["goods_price"];
			if(!$goods_price){
				//$goods_price = round($value["goods_money"] / $value["goods_code"], 2);
			}
			//计算公斤价
			if($goods_info["bulk"] == 0){
				$spec = $goods_info["spec"];
				if(strpos($spec, "g") > -1){
					$spec = str_replace("g", "", $spec);
					$kg_price = round(($goods_price / $spec) * 1000, 2);
					if(is_numeric($kg_price)){
						$goods_price = $kg_price;
					}
				} else {
					
				}
			} else {

			}
			$goods = array(
				"detail_id"  => $value["id"],
				"goods_id"   => $value["goods_id"],
				"goods_name" => $goods_info["name"],
				"goods_code" => $value["goods_code"],
				"goods_count" => $value["goods_count"],
				"goods_money" => $value["goods_money"],
				"bulk" => $goods_info["bulk"],
				"bar_code" => $goods_info["barcode"],
				"goods_price" => $goods_price,
				"bulk_str" => $this->bulkStatus[$goods_info["bulk"]],
				"goods_position" => $this->get_full_position($value["goods_code"]),
				"apply_count" => $value["apply_count"] ? $value["apply_count"] : 0,
				"apply_num"   => $value["apply_num"] ? $value["apply_num"]:0,
				"apply_money" => $value["apply_price"] ? $value["apply_price"]:0
			);
			$goods_list_array[] = $goods;
		}
		$ret["goods_list"] = $goods_list_array;
		return $this->suc($ret);

	}
	//api - 执行拣货
	public function pickReturnPre(){
		$order_db = M("ws_bill", "t_");
		$order_db_detail = M("ws_bill_detail", "t_");
		$order_db_out = M("wsbill_out", "t_");
		if($params["order_id"]){
			$order_info = $order_db->where(array("ref" => $params["order_id"]))->find();
		}
		if($params["id"]){
			$order_info = $order_db->where(array("id" => $params["id"]))->find();
			$params["order_id"] = $order_info["ref"];
		}
		if(!$order_info){
			return $this->bad("订单不存在");
		}
		//状态不对也不回传
		$status = $order_info["bill_status"];
		if($status > 1){
			return $this->bad("订单状态不正确");
		}
		//dump($params);
		//双引号替换
		$params["oos_goods_list"] = preg_replace("/\&quot\;/", '"', $params["oos_goods_list"]);
		$params["success_goods_list"] = preg_replace("/\&quot\;/", '"', $params["success_goods_list"]);
		//成功商品写入执行价格和执行数量
		$success_goods_list = json_decode($params["success_goods_list"], 1);
		$sale_money = 0;
		foreach ($success_goods_list as $key => $value) {
			$map_goods = array(
				"wsbill_id" => $order_info["ref"],
				"goods_code"=> $value["goods_code"]
			);
			if($value["detail_id"]){
				$map_goods = array(
					"id" => $value["detail_id"]
				);
			}
			$goods = $order_db_detail->where($map_goods)->find();
			$goods_info = $this->base_get_goods_info($goods["goods_id"]);
			$goods_price = $goods["goods_price"] > 0 ? $goods["goods_price"] : $this->format_money($goods["goods_money"]/$goods["goods_count"]);
			
			//算出公斤价格
			if($goods_info["bulk"] == 0){
				$spec = $goods_info["spec"];
				if(strpos($spec, "g")){
					$spec = str_replace("g", "", $spec);
					$kg_price = round(($goods_price / $spec) * 1000, 2);
					if(is_numeric($kg_price)){
						$goods_info["sale_price"] = $kg_price;
					}
				} else {
					
				}
			} else {

			}
			//计算实际金额
			if(floatval($value["apply_money"]) > 0){
				$apply_price = $value["apply_money"];
			} else {
				$apply_price = $goods_info["bulk"] == 1 ? $this->format_money($value["apply_count"] * $goods_price) : $this->format_money($value["apply_num"] * $goods_info["sale_price"]);
			}
			$data = array(
				"apply_count" => $value["apply_count"],
				"apply_price" => $apply_price,
				"apply_num"   => $value["apply_num"] ? $value["apply_num"] : 0,
			);
			if($value["apply_price"] > 0){
				$data["apply_price"] = $value["apply_price"];
			}
			$sale_money+= $data["apply_price"];
			//更新仓库价格
			if($goods_info["mode"] == 0){
				if($goods_info["bulk"] == 0){
					$invMoney = $goods_info["lastbuyprice"] * $value["apply_num"];
					$invPrice = $goods_info["lastbuyprice"];
				} else {
					$invMoney = $goods_info["lastbuyprice"] * $value["apply_count"];
					$invPrice = $goods_info["lastbuyprice"];
				}
			} else {
				$lastprice = $this->calc_inv_price($goods_info["sale_price"], $goods_info["rebateRate"]);
				if($goods_info["bulk"] == 0){
					$invMoney = $lastprice * $value["apply_num"];
					$invPrice = $lastprice;
				} else {
					$invMoney = $lastprice * $value["apply_count"];
					$invPrice = $lastprice;
				}
			}
			$data["inventory_price"] = $invPrice;
			$data["inventory_money"] = $invMoney;
			
			$order_db_detail->where($map_goods)->save($data);
		}
		//修改订单的状态
		$data = array(
			"sale_money" => $sale_money,
			"bill_status" => 2
		);
		$order_info = $order_db->where(array("ref" => $params["order_id"]))->save($data);
		//出库
		$api = new ApiService();
		return $api->Outlib($params["order_id"]);
	}
	//api - 执行拣货
	public function pickReturn($params){
		$order_db = M("ws_bill", "t_");
		$order_db_detail = M("ws_bill_detail", "t_");
		$order_db_out = M("wsbill_out", "t_");
		if($params["order_id"]){
			$order_info = $order_db->where(array("ref" => $params["order_id"]))->find();
		}
		if($params["id"]){
			$order_info = $order_db->where(array("id" => $params["id"]))->find();
			$params["order_id"] = $order_info["ref"];
		}
		if(!$order_info){
			return $this->bad("订单不存在");
		}
		//状态不对也不回传
		$status = $order_info["bill_status"];
		if($status > 1){
			return $this->bad("订单状态不正确");
		}
		//dump($params);
		//双引号替换
		$params["oos_goods_list"] = preg_replace("/\&quot\;/", '"', $params["oos_goods_list"]);
		$params["success_goods_list"] = preg_replace("/\&quot\;/", '"', $params["success_goods_list"]);
		//查看是否存在缺货
		if($params["oos"]){
			//如果有缺货商品，则记录到表中
			$oos_goods_list = json_decode($params["oos_goods_list"], 1);
			foreach ($oos_goods_list as $key => $value) {
				$map_goods = array(
					"wsbill_id" => $order_info["ref"],
					"goods_code"=> $value
				);
				$goods = $order_db_detail->where($map_goods)->find();
				$out = array(
					"order_id"    => $order_info['ref'],
					"goods_code"  => $value["goods_code"],
					"goods_count" => $value["goods_count"]
				);
				$map = array(
					"order_id"    => $order_info['ref'],
					"goods_code"  => $value["goods_code"]
				);
				if($order_db_out->where($map)->find()){
					$order_db_out->where($map)->save($out);
				} else {
					$order_db_out->add($out);
				}
				$order_db_detail->where($map_goods)->setField("remark", "缺货");
			}
			//该订单号写入缺货备注和缺货状态，以便补货时可以选择
			$data = array(
				'refund_status' => 2,
				"remark" => $order_info["remark"].",缺货"
			);
			$order_db->where(array("ref" => $params["order_id"]))->save($data);

		}
		//成功商品写入执行价格和执行数量
		$success_goods_list = json_decode($params["success_goods_list"], 1);
		$sale_money = 0;
		$is_out_of_stock = false;
		foreach ($success_goods_list as $key => $value) {
			$map_goods = array(
				"wsbill_id" => $order_info["ref"],
				"goods_code"=> $value["goods_code"]
			);
			if($value["detail_id"]){
				$map_goods = array(
					"id" => $value["detail_id"]
				);
			}
			$goods = $order_db_detail->where($map_goods)->find();
			//库存是否充足
			$balance_count = $this->base_get_goods_stock($goods["goods_id"]);

			if($value["apply_count"] > $balance_count){
				$goods_info = $this->base_get_goods_info($goods["goods_id"]);
				if($goods_info["mode"] == 0){
					//$is_out_of_stock = true;
					//break;
				}
				
			}
			$goods_info = $this->base_get_goods_info($goods["goods_id"]);

			$goods_price = $goods["goods_price"] > 0 ? $goods["goods_price"] : $this->format_money($goods["goods_money"]/$goods["goods_count"]);
			
			//算出公斤价格
			if($goods_info["bulk"] == 0){
				$spec = $goods_info["spec"];
				if(strpos($spec, "g")){
					$spec = str_replace("g", "", $spec);
					$kg_price = round(($goods_price / $spec) * 1000, 2);
					if(is_numeric($kg_price)){
						$goods_info["sale_price"] = $kg_price;
					}
				} else {
					
				}
			} else {

			}
			//计算实际金额
			if(floatval($value["apply_money"]) >= 0){
				$apply_price = $value["apply_money"];
			} else {
				$apply_price = $goods_info["bulk"] == 1 ? $this->format_money($value["apply_count"] * $goods_price) : $this->format_money($value["apply_num"] * $goods_info["sale_price"]);
			}
			$data = array(
				"apply_count" => $value["apply_count"],
				"apply_price" => $apply_price,
				"apply_num"   => $value["apply_num"] ? $value["apply_num"] : 0,
			);
			$sale_money+= $data["apply_price"];
			$order_db_detail->where($map_goods)->save($data);
		}
		if($is_out_of_stock){
			return $this->bad("该拣货单库存不足");
		}
		//修改订单的状态
		$data = array(
			"sale_money" => $sale_money,
			"bill_status" => 2
		);
		$order_info = $order_db->where(array("ref" => $params["order_id"]))->save($data);
		//出库
		$api = new ApiService();
		return $api->Outlib($params["order_id"]);
	}

}