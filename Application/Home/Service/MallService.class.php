<?php

namespace Home\Service;

/**
 * 与商城通信接口
 * @author dubin
 */
class MallService extends ERPBaseService {
	public $mall_api_url = "http://www.taojiangyin.com/goods_service.php";

	//撤单接口
	public function cancel_order($mall_order_id){
		$data = array(
			"act"          => "cancel_order",
			"order_id"     => $mall_order_id,
			"order_sn"     => $mall_order_sn
		);
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => $order_id,
			"userid" => $order_id,
			"action" => "cancel_order",
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请直接去电商后台修改订单状态");
		}
	}

	//补货接口
	public function reSale($order_ref){
		$act = "reissueGoods";
		$db_order = M("ws_bill", "t_");
		$db_detail = M("ws_bill_detail", "t_");
		$map = array(
			"ref" => $order_ref
		);
		$order = $db_order->where($map)->find();
		if(!$order){
			return $this->bad("要提交的订单不存在");
		}
		if($order["type"] != 1){
			return $this->bad("该订单不是补货单");
		}
		//获取详情
		$map = array(
			"wsbill_id" => $order_ref
		);
		$items = $db_detail->where($map)->select();
		$goods_list = array();
		foreach ($items as $key => $value) {
			$goods_info = $this->base_get_goods_info($value["goods_id"]);
			$goods_list[$key]["goods_code"] = $goods_info["goods_code"];
			$goods_list[$key]["order_id"] = $db_order["order_id"];
			$goods_list[$key]["goods_number"] = $db_order["goods_count"];
			$goods_list[$key]["delivery_date"] = $db_order["delivery_date"];
			$goods_list[$key]["delivery_show"] = $db_order["delivery_date"];
			$goods_list[$key]["last_afternoon"] = $db_order["delivery_time"] == 1 ? "上午":"下午";
			$goods_list[$key]["actual_goods_number"] = 0;
		}
		//构造post数据
		$data = array(
			"act"          => $act,
			//"order_id"        => $order_id,
			//"order" => $refund_goods_list,
			//"order_status" => 4,
			"goods" => $goods_list
		);
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => $order_ref,
			//"userid" => $ws_bill["customer_id"],
			"action" => $act,
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			//$sr_bill_db->where($map_sr)->setField("remark", $remark);
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请重试");
		}
	}

	//订单发货接口
	public function delivery($wsid){
		$db = M();
		$act = "updateOrder";
		$sql = "select * from t_ws_bill where id = '%s'";
		$data = $db->query($sql, $wsid);
		if (!$data) {
			return $this->bad("要提交的订单不存在");
		}
		if ($data[0]['bill_status'] == 3) {
			return $this->bad("已经进行过了该项操作");
		}
		if ($data[0]['bill_status'] < 2) {
			//return $this->bad("要提交的订单未完成拣货");
		}
		$mall_order_id = $data[0]['order_id'];
		$mall_order_sn = $data[0]['order_sn'];
		$order_ref = $data[0]['ref'];
		$saleMoney = $data[0]["sale_money"];
		$remark = $data[0]["remark"];
		$type   = $data[0]["type"];
		$sql = "select * from t_ws_bill_detail where wsbill_id = '%s'";
		$data = $db->query($sql, $data[0]["ref"]);
		$order_str = '';
		$goods_list = array();

		foreach($data as $k => $d){
			$goods_list[$k]["goods_id"] = $d['rec_id'];
			$goods_list[$k]["goods_code"] = $d['goods_code'];
			$goods_list[$k]["goods_price"] = $d['apply_price'];
			$goods_list[$k]["goods_status"] = 0;
			//如果是补发，则为3
			if($type == 1){
				$goods_list[$k]["goods_status"] = 3;
			}
		}
		$data = array(
			"act" => $act,
			"order_status" => 1,
			"actualsurplus" => $saleMoney,
			"order_id" => $mall_order_id,
			"order" => $goods_list
		);
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => $order_ref,
			//"userid" => $ws_bill["customer_id"],
			"action" => $act,
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			$remark = $remark.",已同步电商";

			M("ws_bill", "t_")->where(array("id"=>$wsid))->setField("remark", $remark);
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请重试");
		}
	}

	//商城订单完成后实际扣钱接口
	public function order_finish($order_id, $diff_money, $user_id = "", $mall_order_sn = ""){
		$data = array(
			"act"          => "updatePriceByOrder",
			"order_id"     => $order_id,
			"order_sn"     => $mall_order_sn,
			"user_id"      => $user_id,
			"user_money"   => $this->format_money($diff_money),
			"change_desc"  => "订单完成"
		);
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => $order_id,
			"userid" => $user_id,
			"action" => "order_finish-updatePriceByOrder",
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//更新货运状态 
			$this->order_status($order_id, 1);
			//通信成功，则加入备注
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请重试");
		}
	}

	public function order_status($order_id, $ship_status , $order_status = 1, $delivery_date, $delivery_time){
		$act = "updateOrderStatus";
		if ($ship_status == 1){//发货
			$data = array(
				"act" => $act,
				"order_id" => $order_id,
				"shipping_time" => time(),
				"shipping_status" => $ship_status
			);
		} else if ($ship_status == 2){//到站
			$data = array(
				"act" => $act,
				"order_id" => $order_id,
				"station_time" => time(),
				"shipping_status" => $ship_status
			);
		} else if ($ship_status == 3){
			$data = array(
				"act" => $act,
				"order_id" => $order_id,
				"sign_time" => time(),
				"shipping_status" => $ship_status
			);
		}
		if($order_status){
			$data["order_status"] = $order_status;
		}
		if($delivery_date || $delivery_time){
			$data["delivery_date"] = $delivery_date;
			$data["delivery_time"] = $delivery_time;
		}
		$ret = $this->curlPost($data);
	}
	//退货接口
	public function refund($srid, $new_remark="申请退货"){
		$map_sr = array(
			"id" => $srid
		);
		$sr_bill_db = M("sr_bill", "t_");
		$srbill = $sr_bill_db->where($map_sr)->find();

		if(!$srbill){
			return $this->bad("无法获取退货单信息");
		}
		$remark = $srbill["remark"];
		if(strpos($remark, $new_remark) > -1){
			return $this->bad("该订单进行过该项操作了");
		}
		//获取订单信息
		$ws_bill = M("ws_bill", "t_")->where(array("id" => $srbill["ws_bill_id"]))->find();
		$order_id = $ws_bill["order_id"];
		$sr_bill_detail_db = M("sr_bill_detail", "t_");
		$map = array(
			"srbill_id" => $srbill["id"]
		);
		$refund_goods_list = $sr_bill_detail_db->where($map)->select();
		if(!$refund_goods_list){
			return $this->bad("没有需要退货的商品");
		}
		foreach ($refund_goods_list as $key => $value) {
			$map = array(
				"id" => $value['wsbilldetail_id']
			);
			$ws_goods_detail = M("ws_bill_detail", "t_")->where($map)->find();
			$refund_goods_list[$key]["goods_id"] = $ws_goods_detail["rec_id"];
			$refund_goods_list[$key]["goods_code"] = $ws_goods_detail["goods_code"];
			$refund_goods_list[$key]["goods_price"] = $ws_goods_detail["apply_price"];
			$refund_goods_list[$key]["goods_status"] = 1;
			//$mall_order_sn = $ws_goods_detail["order_sn"] ? $ws_goods_detail["order_sn"] : $mall_order_sn;
		}
		$remark = $remark.",".$new_remark;
		//修改订单状态为退货中
		$data = array(
			"act"          => "updateOrder",
			"order_id"        => $order_id,
			"order" => $refund_goods_list,
			"order_status" => 4,
			"actualsurplus" => 0
		);
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => $order_id,
			//"userid" => $ws_bill["customer_id"],
			"action" => "refund-updateOrder",
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			$sr_bill_db->where($map_sr)->setField("remark", $remark);
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请重试");
		}
	}

	//金钱操作1，退货 2，缺货 3 补发
	public function money($rec_id, $type="refund", $actual_money = 0){
		if($type == "refund"){
			$map = array(
				"ref" => $rec_id
			);
			$sr_bill_db = M("sr_bill", "t_");
			$srbill = $sr_bill_db->where($map)->find();
			//根据退款单，获取订单
			$ws_bill = M("ws_bill", "t_")->where(array("id" => $srbill["ws_bill_id"]))->find();
			if(!$srbill){
				return $this->bad("无法获取退货单信息");
			}
			$remark = $srbill["remark"];
			//如果已经退款过了，则无法完成
			if(strpos($remark, "已退款") > -1){
				return $this->bad("该订单已经退过款了，不能重复退款");
			}
			$remark.=",已退款";
			$amount = $srbill["rejection_sale_money"];
			if($actual_money && $actual_money < $amount){
				$amount = $actual_money;
			}
			$order_id = $ws_bill["order_id"];
			//根据customerid获取userid
			$cus = M("customer")->where(array("id"=>$ws_bill["customer_id"]))->find();
			$user_id = $cus["code"];
			$data = array(
				"act"          => "updatePrice",
				"order_id"        => $order_id,
				"user_id"      => $user_id,
				"user_money"   => $amount,
				"change_desc"  => "订单$order_id退款"
			);
			$ret = $this->curlPost($data);
			//记录日志
			$log = array(
				"rec_id" => $order_id,
				"userid" => $ws_bill["customer_id"],
				"action" => "refund-updatePrice",
				"data"   => json_encode($data),
				"time"   => date("Y-m-d H:i:s", time()),
				"return" => json_encode($ret)
			);
			$this->log($log);
			if($ret && $ret["error"] == 0){
				//通信成功，则加入备注
				return $this->ok();
			} else {
				return $this->bad("与电商通信失败，请重试");
			}
		}
		return $this->bad("参数错误");
	}

	//库存同步接口,增量同步，只传递增加的量
	public function syn_inventory($goods_stock_array, $remark = "采购入库"){
		$act = "updateProductNum";
		$data = array(
			"act"          => $act,
			"goods" => $goods_stock_array
		);
		foreach ($goods_stock_array as $key => $value) {
			$goods_number = $this->base_convert_stock($value["goods_code"], $value["goods_number"]);
			$goods_stock_array[$key]["goods_number"] = round($goods_number);
		}
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => 0,
			"userid" => $remark,
			"action" => "syn_inventory-$act",
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请重试");
		}
	}

	//库存同步接口，同步总量和增量
	public function syn_stock($goods_stock, $remark = "库存盘点"){
		$act = "updateStock";
		$data = array(
			"act"          => $act
		);
		$goods_stock_new = array();
		foreach ($goods_stock as $key => $value) {
			$goods_id = $value["goods_id"] ? $value["goods_id"] : $value["goods_code"];
			$goods_info = $this->base_get_goods_info($goods_id);
			if($goods_info){
				$goods_stock[$key]["goods_number"] = $this->base_get_goods_stock($goods_info["id"], true);
				//需要扣除已经下单的但是没发货的电商订单中的商品
				$sql = "select sum(goods_count) as goods_count,goods_id from t_ws_bill_detail where goods_id = '".$goods_info["id"]."' and wsbill_id in (select ref from t_ws_bill where type < 10 and bill_status < 2)";
				$sold_data = M()->query($sql);
				$sold_number = intval($sold_data[0]["goods_count"]);
				$goods_stock[$key]["goods_number"] = $goods_stock[$key]["goods_number"] - $sold_number;
				$goods_stock[$key]["goods_code"]   = $goods_info["code"];
				$goods_stock_new[] = $goods_stock[$key];
			}
		}
		$data["goods"] = $goods_stock_new;
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => 0,
			"userid" => $remark,
			"action" => "syn_inventory-$act",
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请重试");
		}
	}

	//回调电商接口，设置同步订单成功
	public function syn_order_success($order_id){
		$act = "syn_order_success";
		$data = array(
			"act"          => $act,
			"order_id" => $order_id
		);
		$ret = $this->curlPost($data);
		//记录日志
		$log = array(
			"rec_id" => $order_id,
			"userid" => "",
			"action" => $act,
			"data"   => json_encode($data),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$this->log($log);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			return $this->ok();
		} else {
			return $this->bad("与电商通信失败，请重试");
		}
	}


	//日志记录
	public function log($data){
		$db = M("api_log", "t_");
		$db->add($data);
	}

	//同步商城接口
	public function Mall_Api($act, $data) {
		$mall_url = "http://www.taojiangyin.com/goods_service.php?act=$act&$data";
		$rs = json_decode(file_get_contents($mall_url), true);
		if($_GET['debug'] == 'true'){
			echo $mall_url;	
		}
		return $rs;
	}	

	public function curlPost($data, $url=0, $showError=1){
		if(!$data){
			return false;
		}
		if(!$url){
			$url = $this->mall_api_url;
		}
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
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		$errorno=curl_errno($ch);
		if ($errorno) {
			return false;
		}else{
			$js=json_decode($tmpInfo,1);
			return $js;
		}
	}
}