<?php

namespace Home\Service;

use Home\Service\ITBillService;
/**
 * 销售退货入库单Service
 *
 * @author dubin
 */
class SRBillService extends ERPBaseService {

	public $billStatus = array("0"=>"待入库", "1"=>"已入库", "2"=>"已退款", "1000" => "已完成");
	public $orderStatus = array('-1' => '待提交', '0' => '待拣货', '1' => '拣货中' , '2' => '已拣货出库', '3' => '已拣货出库', '4' => '已到站', '5' => '已取货', '6' => '退货');
	public function notify_mall($srbill_id){
		//首先写入订单备注，标明该订单有退货
		//通知电商需要退货
	}

	/**
	 * 销售退货入库单主表信息列表
	 */
	public function srbillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$reason = $params["reason"];
		$begin = $params["begin"];
		$end = $params["end"];
		$SR_ID = $params["SR_ID"];
		if($begin){
			$begin = $begin . " 00:00:00";
		}
		if($end){
			$end = $end . " 23:59:59";
		}
		/*
		$db = M();
		$sql = "select w.id, w.ref, w.bizdt, c.name as customer_name, u.name as biz_user_name,
				 user.name as input_user_name, h.name as warehouse_name, w.rejection_sale_money,
				 w.bill_status 
				 from t_sr_bill w, t_customer c, t_user u, t_user user, t_warehouse h 
				 where w.customer_id = c.id and w.biz_user_id = u.id 
				 and w.input_user_id = user.id and w.warehouse_id = h.id 
				 order by w.ref desc 
				 limit %d, %d";
		$data = $db->query($sql, $start, $limit);
		*/
		$result = array();
		$map = array(

		);
		if($reason){
			$map["reason"] = $reason;
		}
		if($SR_ID){
			$map["ref"] = $SR_ID;
		}
		if($begin){
			$map["bizdt"] = array("egt", $begin);
		}
		if($end){
			$map["bizdt"] = array("elt", $end);
		}
		if($begin && $end){
			$map["bizdt"] = array("between", array($begin, $end));
		}
		$db = M("sr_bill", "t_");
		$data = $db->order("ref desc")->where($map)->limit($start, $limit)->select();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["customerName"]  = $this->get_customer_name_by_id($v["customer_id"]);
			$result[$i]["warehouseName"] = $this->get_warehouse_name_by_id($v["warehouse_id"]);
			$result[$i]["inputUserName"] = $this->get_user_name_by_id($v["input_user_id"]);
			$result[$i]["bizUserName"]   = $this->get_user_name_by_id($v["biz_user_id"]);
			$result[$i]["billStatus"]    = $this->billStatus[$v["bill_status"]];
			$result[$i]["amount"]        = $v["rejection_sale_money"];
			$result[$i]["order_ref"]     = $this->get_order_ref_by_id($v["ws_bill_id"]);
			$result[$i]["reason"]     = $v["reason"];
		}
		
		
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$list[] = array('状态', '单号', '关联订单号', 
			'业务日期' ,'客户' ,'退货金额', '入库仓库', '业务员', '录单人');
			
			foreach ( $result as $i => $v ) {
				if(empty($v["id"])){continue;}
				$tmp[$i][] = $v["billStatus"];
				$tmp[$i][] = $v["ref"];
				$tmp[$i][] = $v["order_ref"];
				$tmp[$i][] = $v["bizDate"];
				$tmp[$i][] = $v["customerName"];
				$tmp[$i][] = $v["amount"];
				$tmp[$i][] = $v["warehouseName"];
				$tmp[$i][] = $v["bizUserName"];
				$tmp[$i][] = $v["inputUserName"];
				$list[] = $tmp[$i];		
			}
			$change_name = '导出数据.csv';
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$change_name.'"');
			header('Cache-Control: max-age=0');
			$file = fopen('php://output', 'a');
			foreach ($list as $k => $line){
				//$list[$k] = iconv('utf-8', 'gbk', $line);
				foreach ($line as $key => $value) {
					$list[$k][$key] = iconv('utf-8', 'gbk', $value);
				}
  				
			}
  			foreach ($list as $key => $value) {
  				fputcsv($file,$value);
  			}
			fclose($file);exit;
		}
		$cnt = $db->where($map)->count();
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 销售退货入库单明细信息列表
	 */
	public function srBillDetailList($params) {
		$id = $params["id"];
		$db = M();
		
		$sql = "select s.id, g.code, g.name, g.spec, g.bulk, u.name as unit_name,
				   s.rejection_goods_count, s.rejection_goods_price, s.rejection_sale_money, s.rejection_goods_actual_count 
				from t_sr_bill_detail s, t_goods g, t_goods_unit u
				where s.srbill_id = '%s' and s.goods_id = g.id and g.unit_id = u.id
					and s.rejection_goods_count > 0
				order by s.show_order";
		$data = $db->query($sql, $id);
		
		$result = array();
		
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
			$result[$i]["rejCount"] = $v["rejection_goods_count"];
			$result[$i]["rejPrice"] = $v["rejection_goods_price"];
			$result[$i]["rejSaleMoney"] = $v["rejection_sale_money"];
			$result[$i]["rejActualCount"] = $v["rejection_goods_actual_count"];
		}
		return $result;
	}

	/**
	 * 获得退货入库单单据数据
	 */
	public function srBillInfo($params) {
		$id = $params["id"];
		
		$us = new UserService();
		
		if (! $id) {
			// 新增单据
			$result["bizUserId"] = $us->getLoginUserId();
			$result["bizUserName"] = $us->getLoginUserName();
			return $result;
		} else {
			// 编辑单据
			$db = M();
			$result = array();
			$sql = "select w.id, w.ref, w.bill_status, w.bizdt, w.reason, c.id as customer_id, c.name as customer_name, 
					 u.id as biz_user_id, u.name as biz_user_name,
					 h.id as warehouse_id, h.name as warehouse_name, wsBill.ref as ws_bill_ref 
					 from t_sr_bill w, t_customer c, t_user u, t_warehouse h, t_ws_bill wsBill 
					 where w.customer_id = c.id and w.biz_user_id = u.id 
					 and w.warehouse_id = h.id 
					 and w.id = '%s' and wsBill.id = w.ws_bill_id";
			$data = $db->query($sql, $id);
			if ($data) {
				$result["ref"] = $data[0]["ref"];
				$result["billStatus"] = $data[0]["bill_status"];
				$result["bizDT"] = date("Y-m-d", strtotime($data[0]["bizdt"]));
				$result["customerId"] = $data[0]["customer_id"];
				$result["customerName"] = $data[0]["customer_name"];
				$result["warehouseId"] = $data[0]["warehouse_id"];
				$result["warehouseName"] = $data[0]["warehouse_name"];
				$result["bizUserId"] = $data[0]["biz_user_id"];
				$result["bizUserName"] = $data[0]["biz_user_name"];
				$result["wsBillRef"] = $data[0]["ws_bill_ref"];
				$result["reason"] = $data[0]["reason"];
			}
			
			$sql = "select d.id, g.id as goods_id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, d.goods_count, 
					d.goods_price, d.goods_money, 
					d.rejection_goods_count, d.rejection_goods_price, d.rejection_sale_money, d.rejection_goods_actual_count, 
					d.wsbilldetail_id
					 from t_sr_bill_detail d, t_goods g, t_goods_unit u 
					 where d.srbill_id = '%s' and d.goods_id = g.id and g.unit_id = u.id
					 order by d.show_order";
			$data = $db->query($sql, $id);
			$items = array();
			foreach ( $data as $i => $v ) {
				$items[$i]["id"] = $v["wsbilldetail_id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$items[$i]["goodsCode"] = $v["code"];
				$items[$i]["goodsName"] = $v["name"];
				$items[$i]["goodsSpec"] = $v["spec"];
				$items[$i]["unitName"] = $v["unit_name"];
				$items[$i]["goodsCount"] = $v["goods_count"];
				$items[$i]["goodsPrice"] = $v["goods_price"];
				$items[$i]["goodsMoney"] = $v["goods_money"];
				$items[$i]["rejCount"] = $v["rejection_goods_count"];
				$items[$i]["rejPrice"] = $v["rejection_goods_price"];
				$items[$i]["rejMoney"] = $v["rejection_sale_money"];
				$items[$i]["rejActualCount"] = $v["rejection_goods_actual_count"];
				if($v["bulk"] == 0){
					$items[$i]["unitName"] = "kg";
				}
			}
			
			$result["items"] = $items;
			
			return $result;
		}
	}

	public function selectWSBillList($params){
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$ref = $params["ref"];
		$customerId = $params["customerId"];
		$user_query = $params["user_query"];
		$warehouseId = $params["warehouseId"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		$refundStatus = $params["refundStatus"];
		$mobile = $params["mobile"];
		$db = M("ws_bill", "t_");
		$map = array(
			"bill_status" => array("gt", 1)
		);
		if($ref){
			$map["ref"] = array("like","%$ref%");
		}
		if($user_query){
			$map["consignee|tel"] = $user_query;
		}
		if($warehouseId){
			$map["warehouse_id"] = $warehouseId;
		}
		if($fromDT){
			$map["bizdt"] = array("egt", $fromDT);
		}
		if($toDT){
			$map["bizdt"] = array("elt", $toDT);
		}
		if($refundStatus && is_numeric($refundStatus)){
			$map["refund_status"] = $refundStatus;
		} else {
			$map["refund_status"] = array("in", array(0,1,2));
		}
		if($mobile){
			$map["tel"] = $mobile;
		}
		if($customerId){
			$map["customer_id"] = $customerId;
		}
//		$list = $db->field("*")->where($map)->order("ref desc")->limit($start, $limit)->select();
		$list = $db->alias("b")
                ->join('t_warehouse w ON w.id=b.warehouse_id')
                ->join('t_user u ON u.id=b.input_user_id')
                ->join('t_customer c ON c.id=b.customer_id')
                ->field("b.id,b.ref,b.bizdt,b.consignee,b.mall_money,b.sale_money,b.tel,b.remark,b.bill_status,b.siteid,b.sitename,w.name as warehouseName,u.name as inputUserName,c.name as customerName")->where($map)->order("ref desc")->limit($start, $limit)->select();
//		return $db->getLastSql();
//        $count = $db->where($map)->count();
		$result = array();
		
		foreach ( $list as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["customerName"] = $v["consignee"];
//			$result[$i]["warehouseName"] = $this->get_warehouse_name_by_id($v["warehouse_id"]);
			$result[$i]["warehouseName"] = $v["warehouseName"];
//			$result[$i]["inputUserName"] = $this->get_user_name_by_id($v["input_user_id"]);
//			$result[$i]["bizUserName"] = $this->get_user_name_by_id($v["biz_user_id"]);
			$result[$i]["inputUserName"] = $v["inputUserName"];
			$result[$i]["bizUserName"] = $v["inputUserName"];
			$result[$i]["amount"] = $v["mall_money"];
			$result[$i]["sale_money"] = $v["sale_money"];
			$result[$i]["tel"] = $v["tel"];
			$result[$i]["remark"] = $v["remark"];
			$result[$i]["bill_status_str"] = $this->orderStatus[$v["bill_status"]];
			$result[$i]["siteid"] = $v["siteid"];
			$result[$i]["sitename"] = $v["sitename"];
			if($v["type"] == 10){
//				$customer = $this->get_customer_name_by_id($v["customer_id"]);
//				$result[$i]["customerName"] = $customer;
				$result[$i]["customerName"] = $v["customerName"];
			}
		}
		return array(
				"dataList" => $result,
//				"totalCount" => $count
				"totalCount" => 1
		);
	}

	/**
	 * 新增或编辑销售退货入库单
	 */
	public function editSRBill($params) {
		$json = $params["jsonStr"];
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = M();
		$idGen = new IdGenService();
		
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$customerId = $bill["customerId"];
		$warehouseId = $bill["warehouseId"];
		$bizUserId = $bill["bizUserId"];
		$items = $bill["items"];
		$wsBillId = $bill["wsBillId"];
		$reason = $bill["remark"];
		
		if (! $id) {
			$sql = "select count(*) as cnt from t_ws_bill where id = '%s' ";
			$data = $db->query($sql, $wsBillId);
			$cnt = $data[0]["cnt"];
			if ($cnt != 1) {
				return $this->bad("选择的销售订单不存在");
			}
			$sql = "select * from t_ws_bill where id = '%s' ";
			$data = $db->query($sql, $wsBillId);
			$billStatus = $data[0]["bill_status"];
//			if($billStatus == 6){
//				return $this->bad("订单正在退货中，无法再次退货");
//			}
			/*
			$sql = "select count(*) as cnt from t_customer where id = '%s' ";
			$data = $db->query($sql, $customerId);
			$cnt = $data[0]["cnt"];
			if ($cnt != 1) {
				return $this->bad("选择的客户不存在");
			}
			*/
		}
		
		$sql = "select count(*) as cnt from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		$cnt = $data[0]["cnt"];
		if ($cnt != 1) {
			return $this->bad("选择的仓库不存在");
		}
		
		if ($id) {
			// 编辑
			$sql = "select bill_status, ref from t_sr_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $this->bad("要编辑的销售退货入库单不存在");
			}
			$billStatus = $data[0]["bill_status"];
			if ($billStatus != 0) {
				return $this->bad("销售退货入库单已经提交，不能再编辑");
			}
			$ref = $data[0]["ref"];
			
			$db->startTrans();
			try {
				$sql = "update t_sr_bill
						set bizdt = '%s', biz_user_id = '%s', date_created = now(),
						   input_user_id = '%s', warehouse_id = '%s', reason = '$reason' 
						where id = '%s' ";
				$us = new UserService();
				$db->execute($sql, $bizDT, $bizUserId, $us->getLoginUserId(), $warehouseId, $id);
				
				// 退货明细
				$sql = "delete from t_sr_bill_detail where srbill_id = '%s' ";
				$db->execute($sql, $id);
				
				foreach ( $items as $i => $v ) {
					$wsBillDetailId = $v["id"];
					$sql = "select inventory_price, goods_count, goods_price, goods_money
							from t_ws_bill_detail 
							where id = '%s' ";
					$data = $db->query($sql, $wsBillDetailId);
					if (! $data) {
						continue;
					}
					$goodsCount = $data[0]["goods_count"];
					$goodsPrice = $data[0]["goods_price"];
					$goodsMoney = $data[0]["goods_money"];
					$inventoryPrice = $data[0]["inventory_price"];
					$rejCount = $v["rejCount"];
					$rejMoney = $v["rejMoney"];
					$rejPrice = round(floatval($v["rejMoney"] / $v["rejCount"]),2);
					$rejActualCount = $v["rejActualCount"];
					if ($rejCount == null) {
						$rejCount = 0;
					}
					$rejSaleMoney = $rejMoney;
					$inventoryMoney = $rejCount * $inventoryPrice;
					$goodsId = $v["goodsId"];
					
					$sql = "insert into t_sr_bill_detail(id, date_created, goods_id, goods_count, goods_money,
						goods_price, inventory_money, inventory_price, rejection_goods_count, 
						rejection_goods_price, rejection_sale_money, show_order, srbill_id, wsbilldetail_id, rejection_goods_actual_count)
						values('%s', now(), '%s', %f, %f, %f, %f, %f, %f,
						%f, %f, %d, '%s', '%s', %f) ";
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsMoney, 
							$goodsPrice, $inventoryMoney, $inventoryPrice, $rejCount, $rejPrice, 
							$rejSaleMoney, $i, $id, $wsBillDetailId, $rejActualCount);
				}
				
				// 更新主表的汇总信息
				$sql = "select sum(rejection_sale_money) as rej_money,
						sum(inventory_money) as inv_money
						from t_sr_bill_detail 
						where srbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$rejMoney = $data[0]["rej_money"];
				$invMoney = $data[0]["inv_money"];
				$profit = $invMoney - $rejMoney;
				$sql = "update t_sr_bill
						set rejection_sale_money = %f, inventory_money = %f, profit = %f
						where id = '%s' ";
				$db->execute($sql, $rejMoney, $invMoney, $profit, $id);
				
				$bs = new BizlogService();
				$log = "编辑销售退货入库单，单号：{$ref}";
				$bs->insertBizlog($log, "销售退货入库");
				
				$db->commit();
				
				return $this->ok($id);
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		} else {
			// 新增
			$db->startTrans();
			try {
				$id = $idGen->newId();
				$ref = $this->genNewBillRef();
				$sql = "insert into t_sr_bill(id, bill_status, bizdt, biz_user_id, customer_id, 
						  date_created, input_user_id, ref, warehouse_id, ws_bill_id, reason)
						values ('%s', 0, '%s', '%s', '%s', 
						  now(), '%s', '%s', '%s', '%s', '$reason')";
				$us = new UserService();
				$db->execute($sql, $id, $bizDT, $bizUserId, $customerId, $us->getLoginUserId(), 
						$ref, $warehouseId, $wsBillId);
				
				foreach ( $items as $i => $v ) {
					$wsBillDetailId = $v["id"];
					$sql = "select inventory_price, goods_count, goods_price, goods_money
							from t_ws_bill_detail 
							where id = '%s' ";
					$data = $db->query($sql, $wsBillDetailId);
					if (! $data) {
						continue;
					}
					$goodsCount = $data[0]["goods_count"];
					$goodsPrice = $data[0]["goods_price"];
					$goodsMoney = $data[0]["goods_money"];
					$inventoryPrice = $data[0]["inventory_price"];
					$rejCount = $v["rejCount"];
					$rejMoney = $v["rejMoney"];
					$rejPrice = round(floatval($v["rejMoney"] / $v["rejCount"]),2);
					$rejActualCount = $v["rejActualCount"];
					if ($rejCount == null) {
						$rejCount = 0;
					}
					$rejSaleMoney = $rejMoney;
					$inventoryMoney = $rejCount * $inventoryPrice;
					$goodsId = $v["goodsId"];
					
					$sql = "insert into t_sr_bill_detail(id, date_created, goods_id, goods_count, goods_money,
						goods_price, inventory_money, inventory_price, rejection_goods_count, 
						rejection_goods_price, rejection_sale_money, show_order, srbill_id, wsbilldetail_id, rejection_goods_actual_count)
						values('%s', now(), '%s', %f, %f, %f, %f, %f, %f,
						%f, %f, %d, '%s', '%s', %f) ";
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsMoney, 
							$goodsPrice, $inventoryMoney, $inventoryPrice, $rejCount, $rejPrice, 
							$rejSaleMoney, $i, $id, $wsBillDetailId, $rejActualCount);
				}
				
				// 更新主表的汇总信息
				$sql = "select sum(rejection_sale_money) as rej_money,
						sum(inventory_money) as inv_money
						from t_sr_bill_detail 
						where srbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$rejMoney = $data[0]["rej_money"];
				$invMoney = $data[0]["inv_money"];
				$profit = $invMoney - $rejMoney;
				$sql = "update t_sr_bill
						set rejection_sale_money = %f, inventory_money = %f, profit = %f
						where id = '%s' ";
				$db->execute($sql, $rejMoney, $invMoney, $profit, $id);
				//更新销售订单的状态和补货状态
				$data = array(
					"bill_status" => 6,
					"refund_status" => 1
				);
				M("ws_bill", "t_")->where(array("id" => $wsBillId))->save($data);
				
				$bs = new BizlogService();
				$log = "新建销售退货入库单，单号：{$ref}";
				$bs->insertBizlog($log, "销售退货入库");
				
				$db->commit();
				//通知商城
				$ms = new MallService();
				$ret = $ms->refund($id);
				if($ret["success"] == false){
					return $ret;
				}
				
				return $this->ok($id);
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		}
	}

	/**
	 * 获得销售出库单的信息
	 */
	public function getWSBillInfoForSRBill($params) {
		$result = array();
		
		$id = $params["id"];
		$db = M("ws_bill", "t_");
		$map = array(
			"id" => $id
		);
		$data = $db->where($map)->find();
		if (! $data) {
			return $result;
		}
		if($data["type"] == 10){
			$data["consignee"] = $this->get_customer_name_by_id($data["customer_id"]);
		}
		$result["ref"] = $data["ref"];
		$result["customerName"] = $data["consignee"];
		$result["warehouseId"] = $data["warehouse_id"];
		$result["warehouseName"] = $this->get_warehouse_name_by_id($data["warehouse_id"]);
		$result["customerId"] = $data["customer_id"];
		//获取折扣
		$map = array(
			"order_id" => $data["order_id"]
		);
		$discount = $db->where($map)->field("sum(discount) as discount")->getField("discount");
		$result["discount"] = $data["discount"] > 0 ? $data["discount"] : 0;
		$sql = "select d.id, g.id as goods_id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, d.goods_count, 
					d.goods_price, d.goods_money, d.apply_num, d.apply_price, d.apply_count 
				from t_ws_bill_detail d, t_goods g, t_goods_unit u 
				where d.wsbill_id = '%s' and d.goods_id = g.id and g.unit_id = u.id
				order by d.show_order";
		$data = $db->query($sql, $result["ref"]);
		$db_detail = M("ws_bill_detail", "t_");
		$map = array(
			"wsbill_id" => $result["ref"]
		);
		$data = $db_detail->where($map)->select();
		$items = array();
		
		foreach ( $data as $i => $v ) {
			$items[$i]["id"] = $v["id"];
			$items[$i]["goodsId"] = $v["goods_id"];
			$map = array(
				"id" => $v["goods_id"]
			);
			$goods = M("goods", "t_")->where($map)->find();
			//dump($goods);
			$items[$i]["goodsCode"] = $goods["code"];
			$items[$i]["goodsName"] = $goods["name"];
			$items[$i]["goodsSpec"] = $goods["spec"];
			$items[$i]["unitName"] = $this->get_unit_name_by_id($goods["unit_id"]);
			if($v["bulk"] == 0){
				$items[$i]["unitName"] = "kg";
				$items[$i]["goodsCount"] = $v["apply_num"];
				$goodsPrice = round($v["apply_price"] / $v["apply_num"], 2);
				$items[$i]["goodsPrice"] = $goodsPrice;
				$items[$i]["goodsMoney"] = $v["apply_price"];
			} else {
				$goodsPrice = $v["goods_price"];
				$items[$i]["goodsCount"] = $v["apply_count"];
				$items[$i]["goodsPrice"] = $v["goods_price"];
				$items[$i]["goodsMoney"] = $v["apply_price"];
			}
			
			$items[$i]["rejPrice"] = $goodsPrice;
			$items[$i]["rejMoney"] = $v["apply_price"];
			$items[$i]["rejCount"] = $items[$i]["goodsCount"];
		}
		
		$result["items"] = $items;
		
		return $result;
	}


	//获得销售出库单的信息，用于补货
	public function getWSBillInfoForRSBill($params){
		$result = array();
		
		$id = $params["id"];
		$db = M("ws_bill", "t_");
		$map = array(
			"id" => $id
		);
		$data = $db->where($map)->find();
		if (! $data) {
			return $result;
		}
		$result["ref"] = $data["ref"];
		$result["customerName"] = $data["consignee"];
		$result["warehouseId"] = $data["warehouse_id"];
		$result["warehouseName"] = $this->get_warehouse_name_by_id($data["warehouse_id"]);
		$result["customerId"] = $data["customer_id"];
		//读取站点信息
		$result["sitename"] = $data["sitename"];
		$result["siteid"] = $data["siteid"];
		$result["mobile"] = $data["tel"];
		$result["address"] = $data["address"];
		$result["id"] = $data["id"];
		$refund_status = $data["refund_status"];
		$items = array();
		if($refund_status == 1){
			//如果是退货，则读取退货表
			$data = M()->query("select * from t_sr_bill_detail where wsbilldetail_id in (select id from t_ws_bill_detail where wsbill_id = '".$result["ref"]."')");
			//dump(M()->getLastSql());
			foreach ( $data as $i => $v ) {

				$items[$i]["id"] = $v["id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$map = array(
					"id" => $v["goods_id"]
				);
				$goods = M("goods", "t_")->where($map)->find();
				//dump($goods);
				$items[$i]["goodsCode"] = $goods["code"];
				$items[$i]["goodsName"] = $goods["name"];
				$items[$i]["goodsSpec"] = $goods["spec"];
				$items[$i]["unitName"] = $this->get_unit_name_by_id($goods["unit_id"]);
				$items[$i]["goodsCount"] = $v["rejection_goods_count"];
				$items[$i]["goodsPrice"] = $goods["sale_price"];
				$items[$i]["goodsMoney"] = $v["rejection_goods_count"] * $items[$i]["goodsPrice"];//$v["goods_money"];
			}
		} else if ($refund_status == 2){
			//如果是缺货，则读取缺货表
			$map = array(
				"order_id" => $result["ref"]
			);
			$data = M("wsbill_out")->where($map)->select();
			foreach ( $data as $i => $v ) {

				//读取订单商品表
				$map = array(
					'wsbill_id'  => $result["ref"],
					"goods_code" => $v["goods_code"]
				);
				$detail = M("ws_bill_detail")->where($map)->find();
				$items[$i]["id"] = $v["id"];
				
				$map = array(
					"code" => $v["goods_code"]
				);
				$goods = M("goods", "t_")->where($map)->find();
				$goods_price = $detail["goods_price"] ? $detail["goods_price"] : $goods["sale_price"];
				$items[$i]["goodsId"] = $detail["goods_id"];
				$items[$i]["goodsCode"] = $goods["code"];
				$items[$i]["goodsName"] = $goods["name"];
				$items[$i]["goodsSpec"] = $goods["spec"];
				$items[$i]["unitName"] = $this->get_unit_name_by_id($goods["unit_id"]);
				$items[$i]["goodsCount"] = $v["goods_count"];
				$items[$i]["goodsPrice"] = $goods_price;
				$items[$i]["goodsMoney"] = $v["goods_count"] * $items[$i]["goodsPrice"];//$v["goods_money"];
			}
		} else {

		}
		//根据订单补货类别，获取不同的表
		/*
		$sql = "select d.id, g.id as goods_id, g.code, g.name, g.spec, u.name as unit_name, d.goods_count, 
					d.goods_price, d.goods_money 
				from t_ws_bill_detail d, t_goods g, t_goods_unit u 
				where d.wsbill_id = '%s' and d.goods_id = g.id and g.unit_id = u.id
				order by d.show_order";
		$data = $db->query($sql, $result["ref"]);
		*/
		$db_detail = M("ws_bill_detail", "t_");
		$map = array(
			"wsbill_id" => $result["ref"]
		);
		
		
		$result["items"] = $items;
		
		return $result;
	}

	/**
	 * 生成新的销售退货入库单单号
	 *
	 * @return string
	 */
	private function genNewBillRef() {
		$pre = "SR";
		$mid = date("Ymd");
		
		$sql = "select ref from t_sr_bill where ref like '%s' order by ref desc limit 1";
		$data = M()->query($sql, $pre . $mid . "%");
		$sufLength = 3;
		$suf = str_pad("1", $sufLength, "0", STR_PAD_LEFT);
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, 10)) + 1;
			$suf = str_pad($nextNumber, $sufLength, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	/**
	 * 删除销售退货入库单
	 */
	public function deleteSRBill($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select bill_status, ref from t_sr_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的销售退货入库单不存在");
		}
		
		$billStatus = $data[0]["bill_status"];
		$ref = $data[0]["ref"];
		if ($billStatus != 0) {
			return $this->bad("销售退货入库单[单号: {$ref}]已经提交，不能删除");
		}
		
		$db->startTrans();
		try {
			$sql = "delete from t_sr_bill_detail where srbill_id = '%s'";
			$db->execute($sql, $id);
			
			$sql = "delete from t_sr_bill where id = '%s' ";
			$db->execute($sql, $id);
			
			$bs = new BizlogService();
			$log = "删除销售退货入库单，单号：{$ref}";
			$bs->insertBizlog($log, "销售退货入库");
			
			$db->commit();
			return $this->ok();
		} catch ( Exception $ex ) {
			$db->rollback();
			return $this->bad("数据库操作失败，请联系管理员");
		}
	}

	/**
	 * 提交销售退货入库单
	 */
	public function commitSRBill($params) {
		$id = $params["id"];
		
		$db = M();
		
		$sql = "select ref, bill_status, warehouse_id, customer_id, bizdt, ws_bill_id,
					biz_user_id, rejection_sale_money  
				from t_sr_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要提交的销售退货入库单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$ref = $data[0]["ref"];
		if ($billStatus != 0) {
			return $this->bad("销售退货入库单(单号:{$ref})已经提交，不能再次提交");
		}
		$caiwu_remark = "销售退货入库";
		$source_order = M("ws_bill")->find($data[0]["ws_bill_id"]);
		if($source_order["type"] == 10){
			$caiwu_remark = "手动销售退货入库";
		}
		$warehouseId = $data[0]["warehouse_id"];
		$customerId = $data[0]["customer_id"];
		$bizDT = $data[0]["bizdt"];
		$bizUserId = $data[0]["biz_user_id"];
		$rejectionSaleMoney = $data[0]["rejection_sale_money"];
		$caiwu_pay_money = $data[0]["rejection_sale_money"];
		$wsbillid = $data[0]["ws_bill_id"];
		//如果有折扣，则需要打折
		$map = array(
			"order_id" => $source_order["order_id"]
		);
		$total_order = M("ws_bill")->where($map)->field("sum(sale_money) as total_sale_money, sum(discount) as total_discount")->find();
		if($total_order['total_discount'] > 0){
			//计算折扣
			$rebat = ($total_order['total_sale_money'] - $total_order['total_discount']) / $total_order['total_sale_money'];
			if($rebat < 0){
				$rebat = 0;
			}
			$caiwu_pay_money = round($rebat * $caiwu_pay_money, 2);
		} else {

		}
		$sql = "select name from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在，无法提交");
		}
		
		$sql = "select name from t_customer where id = '%s' ";
		$data = $db->query($sql, $customerId);
		if (! $data) {
			return $this->bad("客户不存在，无法提交");
		}
		
		$sql = "select name from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		if (! $data) {
			return $this->bad("业务人员不存在，无法提交");
		}
		
		// 检查退货数量
		// 1、不能为负数
		// 2、累计退货数量不能超过销售的数量
		$sql = "select wsbilldetail_id, rejection_goods_count, goods_id
				from t_sr_bill_detail
				where srbill_id = '%s' 
				order by show_order";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("销售退货入库单(单号:{$ref})没有退货明细，无法提交");
		}
		
		foreach ( $data as $i => $v ) {
			$wsbillDetailId = $v["wsbilldetail_id"];
			$rejCount = $v["rejection_goods_count"];
			$goodsId = $v["goods_id"];
			
			// 退货数量为负数
			if ($rejCount < 0) {
				$sql = "select code, name, spec
						from t_goods
						where id = '%s' ";
				$data = $db->query($sql, $goodsId);
				if ($data) {
					$goodsInfo = "编码：" . $data[0]["code"] . " 名称：" . $data[0]["name"] . " 规格：" . $data[0]["spec"];
					return $this->bad("商品({$goodsInfo})退货数量不能为负数");
				} else {
					return $this->bad("商品退货数量不能为负数");
				}
			}
			
			// 累计退货数量不能超过销售数量
			$goods_info = $this->base_get_goods_info($goodsId);
			$sql = "select apply_count,apply_num from t_ws_bill_detail where id = '%s' ";
			$data = $db->query($sql, $wsbillDetailId);
			$saleGoodsCount = 0;
			if ($data) {
				if($goods_info["bulk"] == 0){
					$saleGoodsCount = $data[0]["apply_num"];
				} else {
					$saleGoodsCount = $data[0]["apply_count"];
				}
			}
			$sql = "select sum(d.rejection_goods_count) as rej_count
					from t_sr_bill s, t_sr_bill_detail d
					where s.id = d.srbill_id and s.bill_status <> 0 
					  and d.wsbilldetail_id = '%s' ";
			$data = $db->query($sql, $wsbillDetailId);
			$totalRejCount = $data[0]["rej_count"] + $rejCount;
			if ($totalRejCount > $saleGoodsCount) {
				$sql = "select code, name, spec
						from t_goods
						where id = '%s' ";
				$data = $db->query($sql, $goodsId);
				if ($data) {
					$goodsInfo = "编码：" . $data[0]["code"] . " 名称：" . $data[0]["name"] . " 规格：" . $data[0]["spec"];
					return $this->bad("商品({$goodsInfo})累计退货数量不超过销售量");
				} else {
					return $this->bad("商品累计退货数量不超过销售量");
				}
			}
		}
		
		$db->startTrans();
		try {
			$sql = "select goods_id, wsbilldetail_id, rejection_goods_count, rejection_goods_actual_count, inventory_money ,rejection_sale_money 
				from t_sr_bill_detail
				where srbill_id = '%s' 
				order by show_order";
			$items = $db->query($sql, $id);
			$invLoss = array();
			foreach ( $items as $i => $v ) {
				$goodsId = $v["goods_id"];
				$rejCount = $v["rejection_goods_count"];
				$rejMoney = $v["inventory_money"];
				$rejActualCount = $v["rejection_goods_actual_count"];
				if ($rejCount == 0) {
					continue;
				}
				$rejPrice = $rejMoney / $rejCount;
				
				$sql = "select in_count, in_money, balance_count, balance_money
						from t_inventory
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $warehouseId, $goodsId);
				if (! $data) {
					continue;
				}
				$inv_data = $data;
				//写入退货金额
				$sql = "update t_ws_bill_detail set reject_money = reject_money+".$v["rejection_sale_money"]." where id = '".$v['wsbilldetail_id']."'";
				$db->execute($sql);
				//如果是联营商品，则需要扣除供应商款
				$goods_info = $this->base_get_goods_info($goodsId);
				
				if($goods_info){
					if($goods_info["mode"] == 1){
						//获取供应商
						$map = array(
							"goodsid" => $goodsId
						);
						$s_g = M("supplier_goods")->where($map)->find();
						$map = array(
							"id" => $s_g["supplierid"]
						);
						$supplier = M("supplier")->where($map)->find();
						$bizDT = date("Y-m-d H:i:s");
						if($supplier){
							//财务总账
							$idGen = new IdGenService();
							$supplierId = $supplier["id"];
							$allRejMoney = $rejMoney;
							// 应收总账
							$sql = "select rv_money, balance_money
									from t_receivables
									where ca_id = '%s' and ca_type = 'supplier'";
							$data = $db->query($sql, $supplierId);
							
							if (! $data) {
								$sql = "insert into t_receivables(id, rv_money, act_money, balance_money, ca_id, ca_type)
										values ('%s', %f, 0, %f, '%s', 'supplier')";
										
								$rc = $db->execute($sql, $idGen->newId(), $allRejMoney, $allRejMoney, $supplierId);
								
								if (! $rc) {
									//$db->rollback();
									//return $this->sqlError();
								}
							} else {
								$rvMoney = $data[0]["rv_money"];
								$balanceMoney = $data[0]["balance_money"];
								$rvMoney += $allRejMoney;
								$balanceMoney += $allRejMoney;
								$sql = "update t_receivables
										set rv_money = %f, balance_money = %f
										where ca_id = '%s' and ca_type = 'supplier' ";
								$rc = $db->execute($sql, $rvMoney, $balanceMoney, $supplierId);
								if (! $rc) {
									//$db->rollback();
									//return $this->sqlError();
								}
								
							}
							
							// 应收明细账
							$sql = "insert into t_receivables_detail(id, rv_money, act_money, balance_money, ca_id, ca_type,
										biz_date, date_created, ref_number, ref_type)
									values ('%s', %f, 0, %f, '%s', 'supplier', '%s', now(), '%s', '联营销售退货')";
							$rc = $db->execute($sql, $idGen->newId(), $allRejMoney, $allRejMoney, $supplierId, 
									$bizDT, $ref);
						}
					}
				}
				$totalInCount = $inv_data[0]["in_count"];
				$totalInMoney = $inv_data[0]["in_money"];
				$totalBalanceCount = $inv_data[0]["balance_count"];
				$totalBalanceMoney = $inv_data[0]["balance_money"];
				if(!$inv_data){
					$db->rollback();
					return $this->bad("数据库错误，请联系管理员");
				}
				$totalInCount += $rejCount;
				$totalInMoney += $rejMoney;
				$totalInPrice = $totalInMoney / $totalInCount;
				$totalBalanceCount += $rejCount;
				$totalBalanceMoney += $rejMoney;
				$totalBalancePrice = $totalBalanceMoney / $totalBalanceCount;
				
				// 库存明细账
				$sql = "insert into t_inventory_detail(in_count, in_price, in_money,
						balance_count, balance_price, balance_money, ref_number, ref_type,
						biz_date, biz_user_id, date_created, goods_id, warehouse_id)
						values (%f, %f, %f, 
						%f, %f, %f, '%s', '$caiwu_remark',
						'%s', '%s', now(), '%s', '%s')";
				$db->execute($sql, $rejCount, $rejPrice, $rejMoney, $totalBalanceCount, 
						$totalBalancePrice, $totalBalanceMoney, $ref, $bizDT, $bizUserId, $goodsId, 
						$warehouseId);
				
				// 库存总账
				$sql = "update t_inventory
						set in_count = %f, in_price = %f, in_money = %f,
						  balance_count = %f, balance_price = %f, balance_money = %f
						where goods_id = '%s' and warehouse_id = '%s' ";
				$db->execute($sql, $totalInCount, $totalInPrice, $totalInMoney, $totalBalanceCount, 
						$totalBalancePrice, $totalBalanceMoney, $goodsId, $warehouseId);
				//如果退货数跟实际入库数不一样，则自动增加损溢
				$countDiff = $rejCount - $rejActualCount;
				if($countDiff > 0){
					$invLossGoods = array(
						"goodsId" => $goodsId,
						"goodsCount" => $countDiff,
						"bill_type" => "损",
					);
					$invLoss[] = $invLossGoods;
				}
			}
			
			$idGen = new IdGenService();
			
			// 应付账款总账
			$sql = "select pay_money, balance_money
					from t_payables
					where ca_id = '%s' and ca_type = 'customer' ";
			$data = $db->query($sql, $customerId);
			if ($data) {
				$totalPayMoney = $data[0]["pay_money"];
				$totalBalanceMoney = $data[0]["balance_money"];
				
				$totalPayMoney += $caiwu_pay_money;
				$totalBalanceMoney += $caiwu_pay_money;
				$sql = "update t_payables
						set pay_money = %f, balance_money = %f
						where ca_id = '%s' and ca_type = 'customer' ";
				$db->execute($sql, $totalPayMoney, $totalBalanceMoney, $customerId);
			} else {
				
				$sql = "insert into t_payables (id, ca_id, ca_type, pay_money, balance_money, act_money)
						values ('%s', '%s', 'customer', %f, %f, %f)";
				$db->execute($sql, $idGen->newId(), $customerId, $caiwu_pay_money, 
						$caiwu_pay_money, 0);
			}
			
			// 应付账款明细账
			$sql = "insert into t_payables_detail(id, ca_id, ca_type, pay_money, balance_money,
					biz_date, date_created, ref_number, ref_type, act_money)
					values ('%s', '%s', 'customer', %f, %f,
					 '%s', now(), '%s', '$caiwu_remark', 0)";
			$db->execute($sql, $idGen->newId(), $customerId, $caiwu_pay_money, 
					$caiwu_pay_money, $bizDT, $ref);
			
			// 把单据本身的状态修改为已经提交
			$sql = "update t_sr_bill
					set bill_status = 1000
					where id = '%s' ";
			$db->execute($sql, $id);

			//写入原订单的退货金额
			$sql = "update t_ws_bill set reject_money = reject_money+$rejectionSaleMoney where id = '$wsbillid'";
			$db->execute($sql);
			//根据实际入库数量新增损益
			if($invLoss){
				$inv = array(
					"id" => "",
					"fromWarehouseId" => $warehouseId,
					"bizDT" => date("Y-m-d", time()),
					"items" => $invLoss
				);
				$it = new ITBillService();

				$r = $it->autoIlBill($inv);
				if($r["success"] == false){
					$db->rollback();
					return $this->bad($r["msg"]);
				}
				$invId = $r["id"];
			}
			// 记录业务日志
			$log = "提交$caiwu_remark单，单号：{$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "销售退货入库");

			$db->commit();
			//提交损益单
			if($invLoss){
				//$it->commitInvLoss(array("id"=>$invId));
			}
			return $this->ok($id);
		} catch ( Exception $ex ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系管理员");
		}
	}
}