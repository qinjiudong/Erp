<?php

namespace Home\Service;

/**
 * 一些需要自动化处理的Service
 *
 * @author dubin
 */
class AutoService extends ERPBaseService {

	//根据销售自动生成采购和验收单
	public function autoAcceptance($order_id) {
		if(!$order_id){
			return $this->bad('不存在订单');
		}
		//首先查询该订单是否已经处理过
		$db = M();
		$map = array(
			"order_id" => $order_id
		);
		if( M("pw_bill", "t_")->where($map)->find() ){
			$map = array(
				"id" => $order_id
			);
			M("ws_bill", "t_")->where($map)->setField("auto_status", 1);
			return $this->bad('该订单已经验收过了');
		}
		$order = M("ws_bill", "t_")->find($order_id);
		//是否出库 
		if($order["bill_status"] < 2){
			$map = array(
				"id" => $order_id
			);
			M("ws_bill", "t_")->where($map)->setField("auto_status", 10);
			return $this->bad('该订单还未出库');
		}
		$order_ref = $order["ref"];
		$map = array(
			"wsbill_id" => $order_ref
		);
		$order_goods_list = M("ws_bill_detail", "t_")->where($map)->select();
		//保存需要处理的商品
		$goods_list = array();
		//获取默认仓库
		$defaultWarehouse = $this->base_get_default_warehouse();
		foreach ($order_goods_list as $good) {
			$goods_info = $this->base_get_goods_info($good["goods_id"]);
			if($goods_info["mode"] == 1){
				//获取商品的供应商和默认仓库
				$map = array(
					"goodsid" => $goods_info["id"]
				);
				$supplierId = M("supplier_goods", "t_")->where($map)->order("id asc")->getField("supplierid");
				$good["supplier_id"]  = $supplierId;
				$good["warehouse_id"] = $defaultWarehouse["warehouseId"];
				if(!$good["supplier_id"]){
					$map = array(
						"id" => $order_id
					);
					M("ws_bill", "t_")->where($map)->setField("auto_status", 2);
					//失败的自动验收
					return $this->bad('验收失败哦');
				}
				$good["id"] = $goods_info["id"];
				$good["rebateRate"] = $goods_info["rebaterate"];
				$good["mode"] = $goods_info["mode"];
				$good["bulk"] = $goods_info["bulk"];
				$good["buytax"] = $goods_info["buytax"];
				$goods_list[] = $good;
			}
		}
		//$id = $bill["id"];
		//$bizDT = date("Y-m-d", time());
		$bizDT = $order["delivery_date"];
		$bizUserId = $this->base_get_auto_op_user();
		$input_user_id = $this->base_get_auto_op_user();
		//事务流开始
		$idGen = new IdGenService();
		
		$db->startTrans();
		//处理联营商品，自动生成采购单
		try{
			foreach ($goods_list as $key => $goods) {
				//$bizDT = date("Y-m-d", time());
				$warehouseId = $goods["warehouse_id"];
				$supplierId = $goods["supplier_id"];
				
				$pwid  = $idGen->newId();
				$ref = $this->genNewBillRefAny("PW");
				//生成采购单
				$sql = "insert into t_pw_bill (id, ref, supplier_id, warehouse_id, biz_dt, 
						biz_user_id, bill_status, date_created, goods_money, input_user_id, type , order_id) 
						values ('%s', '%s', '%s', '%s', '%s', '%s', 5, now(), 0, '%s', 1, '$order_id')";
				$us = new UserService();
				$db->execute($sql, $pwid, $ref, $supplierId, $warehouseId, $bizDT, $bizUserId, $input_user_id);
				
				// 明细记录
				//$items = $bill["items"];
				//foreach ( $items as $i => $item ) {
				$goodsId = $goods["id"];
				if($goods["bulk"] == 0){
					$goodsCount = floatval($goods["apply_num"]);
				} else {
					$goodsCount = floatval($goods["apply_count"]);
				}
				
				$goodsType = 0;
					// 检查商品是否存在
				//$sql = "select count(*) as cnt from t_goods where id = '%s' ";
				//$data = $db->query($sql, $goodsId);
				//$cnt = $data[0]["cnt"];
				if (true) {
					if($goods["bulk"] == 0){
						$saleMoney = floatval($goods["apply_price"]);
						$salePrice = round($goods["apply_price"] / $goods["apply_num"], 2);
					} else {
						$saleMoney = floatval($goods["apply_price"]);
						$salePrice = round($goods["apply_price"] / $goods["apply_count"], 2);
					}
					
					$rate = $goods["rebateRate"];
					if(strpos($rate, "%") > -1){
						$rate = str_replace("%", "", $rate);
					}
					$rate = floatval($rate / 100);
					$goodsPrice = round((1 - $rate) * $salePrice, 2);
					$goodsMoney = round((1 - $rate) * $saleMoney, 2);
					//计算无税金额
					$buytax = $goods["buytax"] / 100;
					$goodsMoneyNoTax = $goodsMoney / (1 + $buytax);
					$sql = "insert into t_pw_bill_detail 
							(id, date_created, goods_id, goods_count, goods_price,
							goods_money,  pwbill_id, show_order, goods_type)
							values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d )";
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $pwid, 1, $goodsType);
				}
				//}
				
				$sql = "select sum(goods_money) as goods_money from t_pw_bill_detail 
						where pwbill_id = '%s' ";
				$data = $db->query($sql, $pwid);
				$totalMoney = $data[0]["goods_money"];
				$sql = "update t_pw_bill
						set goods_money = %f 
						where id = '%s' ";
				$db->execute($sql, $totalMoney, $pwid);
				
				$log = "自动新建采购入库单: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "自动采购入库");
				//新建验收单
				$iaid = $idGen->newId();
				$sql = "insert into t_ia_bill (id, ref, supplier_id, warehouse_id, biz_dt, 
						biz_user_id, bill_status, date_created, goods_money, input_user_id, pw_billid, type) 
						values ('%s', '%s', '%s', '%s', '%s', '%s', 5, now(), 0, '%s', '%s', 1)";
				
				$ref = $this->genNewBillRefAny("IA");
				//$us = new UserService();
				$db->execute($sql, $iaid, $ref, $supplierId, $warehouseId, $bizDT, $bizUserId, $bizUserId, $pwid);
				$sql = "insert into t_ia_bill_detail 
									(id, date_created, goods_id, goods_count, goods_price,
									goods_money,  iabill_id, show_order, goods_type, goods_money_no_tax)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d, %f )";
				$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $iaid, 1, $goodsType,$goodsMoneyNoTax );
				$sql = "select sum(goods_money) as goods_money from t_ia_bill_detail 
						where iabill_id = '%s' ";
				$data = $db->query($sql, $iaid);
				$totalMoney = $data[0]["goods_money"];
				$sql = "update t_ia_bill
						set goods_money = %f 
						where id = '%s' ";
				$db->execute($sql, $totalMoney, $iaid);
				$log = "自动新建验收单: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "自动验收入库单");
				//验收入库
				$balanceCount = 0;
				$balanceMoney = 0;
				$balancePrice = (float)0;
				// 库存总账
				$sql = "select in_count, in_money, balance_count, balance_money 
						from t_inventory 
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $warehouseId, $goodsId);
				if ($data) {
					$inCount = floatval($data[0]["in_count"]);
					$inMoney = floatval($data[0]["in_money"]);
					$balanceCount = floatval($data[0]["balance_count"]);
					$balanceMoney = floatval($data[0]["balance_money"]);
					
					$inCount += $goodsCount;
					$inMoney += $goodsMoney;
					$inPrice = $inMoney / $inCount;
					
					$balanceCount += $goodsCount;
					$balanceMoney += $goodsMoney;
					$balancePrice = $balanceMoney / $balanceCount;
					
					$sql = "update t_inventory 
							set in_count = %f, in_price = %f, in_money = %f,
							balance_count = %f, balance_price = %f, balance_money = %f 
							where warehouse_id = '%s' and goods_id = '%s' ";
					$db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId);
				} else {
					$inCount = $goodsCount;
					$inMoney = $goodsMoney;
					$inPrice = $inMoney / $inCount;
					$balanceCount += $goodsCount;
					$balanceMoney += $goodsMoney;
					$balancePrice = $balanceMoney / $balanceCount;
					
					$sql = "insert into t_inventory (in_count, in_price, in_money, balance_count,
							balance_price, balance_money, warehouse_id, goods_id)
							values (%f, %f, %f, %f, %f, %f, '%s', '%s')";
					$db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId);
				}
				// 库存明细账
				$sql = "insert into t_inventory_detail (in_count, in_price, in_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id, biz_date,
						biz_user_id, date_created, ref_number, ref_type)
						values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '自动验收入库')";
				$db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref);
				//如果进价大于0不为赠品，则更新该商品的最后进价lastBuyPrice
				if($goodsPrice > 0){
					$sql = "update t_goods set lastBuyPrice = '$goodsPrice' where id = '%s' ";
					$db->execute($sql, $goodsId);
				}
				$billPayables = floatval($goodsMoney);
				$map = array(
					"id" => $supplierId
				);
				$supplier_info = M("supplier", "t_")->where($map)->find();
				//账期默认30天，验收入库后更新付款日期
				$pay_day  = intval($supplier_info["period"]) ? intval($supplier_info["period"]) : 30;
				$pay_time = time() + $pay_day * 24 * 3600;
				$pay_time_date = date("Y-m-d H:i:s", $pay_time);
				$sql = "update t_ia_bill set bill_status = 1, pay_time = '%s' where id = '%s' ";
				$db->execute($sql, $pay_time_date, $iaid);
				//根据供货商的经营方式,确定应付款项和应收款项,如果是联营的话需要扣除返点。供应商要有返点率
				//应付明细账
				$sql = "insert into t_payables_detail (id, pay_money, act_money, balance_money,
						ca_id, ca_type, date_created, ref_number, ref_type, biz_date)
						values ('%s', %f, 0, %f, '%s', 'supplier', now(), '%s', '自动验收入库', '%s')";
				$idGen = new IdGenService();
				$db->execute($sql, $idGen->newId(), $billPayables, $billPayables, $supplierId, $ref, $bizDT);
				// 应付总账
				$sql = "select id, pay_money 
						from t_payables 
						where ca_id = '%s' and ca_type = 'supplier' ";
				$data = $db->query($sql, $supplierId);
				if ($data) {
					$pId = $data[0]["id"];
					$payMoney = floatval($data[0]["pay_money"]);
					$payMoney += $billPayables;
					
					$sql = "update t_payables 
							set pay_money = %f, balance_money = %f 
							where id = '%s' ";
					$db->execute($sql, $payMoney, $payMoney, $pId);
				} else {
					$payMoney = $billPayables;
					
					$sql = "insert into t_payables (id, pay_money, act_money, balance_money, 
							ca_id, ca_type) 
							values ('%s', %f, 0, %f, '%s', 'supplier')";
					$db->execute($sql, $idGen->newId(), $payMoney, $payMoney, $supplierId);
				}
				// 日志
				$log = "自动验收入库: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "自动验收入库");
				//订单号插入采购单
				$map = array(
					"id" => $pwid
				);
				$data = array(
					"order_id" => $order_id
				);
				M("pw_bill", "t_")->where($map)->save($data);
				//修改订单状态
				/*
				$map = array(
					"id" => $order_id
				);
				M("ws_bill", "t_")->where($map)->setField("auto_status", 1);
				*/
				
			}
			//修改订单状态
			$map = array(
				"id" => $order_id
			);
			M("ws_bill")->where($map)->setField("auto_status", 1);
			$db->commit();
			return $this->ok("自动处理成功");
		} catch ( Exception $exc ) {
			$db->rollback();
			return $this->bad("数据库操作错误，请重试或者联系管理员");
		}
	}


	private function genNewBillRefAny($type = 'PW') {
		$pre = $type;
		$mid = date("Ymd");
		$table = "";
		if($type == "PW"){
			$table = "t_pw_bill";
		} else if ($type == 'IA'){
			$table = "t_ia_bill";
		} else if ($type == 'PC'){
			$table = 't_pc_bill';
		} else {
			$table = "t_pw_bill";
		}
		$sql = "select ref from $table where ref like '%s' order by ref desc limit 1";
		$data = M()->query($sql, $pre . $mid . "%");
		$suf = "0001";
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, 10)) + 1;
			$suf = str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	public function sms($params){
		
	}



	/* 自动生成进销存统计数据 */
	public function tongji($id){
		$detail_db = M("inventory_detail");
		$com_map = array();
		$db = M("inout_day");
		$detail = $detail_db->find($id);
		if(!$detail){
			return $this->bad("No record");
		}
		if($detail["is_tongji"] == 1){
			return $this->bad("Has tongji");
		}
		$type = $detail["ref_type"];
		$date = date("Y-m-d", strtotime($detail["biz_date"]));
		$year = date("Y", strtotime($detail["biz_date"]));
		$month= date("m", strtotime($detail["biz_date"]));
		$map = array(
			"biz_date" => $date,
			"goods_id" => $detail["goods_id"]
		);
		$inout = $db->where($map)->find();
		$goods_info = $this->base_get_goods_info($detail["goods_id"]);
		$buytax = $goods_info["buytax"] / 100;
		$selltax = $goods_info["selltax"] / 100;
		$is_ia = false;
		$map_supplier = array(
			"goodsid" => $detail["goods_id"]
		);
		$suppliers = M("supplier_goods")->where($map_supplier)->select();
		$supplier = $suppliers[0];
		$supplier_id= $supplier["supplierid"];
		if($inout){
		} else {
			$data = array(
				"biz_date" => $date,
				"goods_id" => $detail["goods_id"],
				"buyprice" => $goods_info["lastbuyprice"],
				"supplier_id" => $supplier_id
			);
			$iid = $db->add($data);
			$inout = $db->find($iid);
		}
		$last_sql = "update t_inout_day set ";
		if($type == "自动验收入库" || $type == "验收入库"){
			$inCount = $detail["in_count"];
			$inMoney = $detail["in_money"];
			$inMoneyNoTax = round($inMoney / (1 + $buytax), 2);
			//如果是验收入库，则计算平均值
			$is_ia = true;
			$last_sql .= " ia_count = ia_count + '$inCount', ia_money = ia_money + '$inMoney', ia_money_no_tax = ia_money_no_tax + '$inMoneyNoTax' ";
		} else if ($type == "采购退货出库"){
			$outCount = $detail["out_count"];
			$outMoney = $goods_info["lastbuyprice"] * $outCount;
			$outMoneyNoTax = round($outMoney / (1 + $buytax), 2);
			$last_sql .= " ia_count = ia_count - '$outCount', ia_money = ia_money - '$outMoney', ia_money_no_tax = ia_money_no_tax - '$outMoneyNoTax' ";
		} else if ($type == "销售出库" || $type == "手动销售出库"){
			$outCount = $detail["out_count"];
			$outMoney = $detail["out_money"];
			$outMoneyNoTax = round($outMoney / (1 + $selltax), 2);
			$last_sql .= " sale_count = sale_count + '$outCount', sale_money = sale_money + '$outMoney', sale_money_no_tax = sale_money_no_tax + '$outMoneyNoTax' ";
		} else if ($type == "销售退货入库" || $type == "手动销售退货入库"){
			$inCount = $detail["in_count"];
			$inMoney = $detail["in_money"] ? $detail["in_money"] : ($goods_info["sale_price"] * $inCount);
			$inMoneyNoTax = round($inMoney / (1 + $selltax), 2);
			$last_sql .= " sale_count = sale_count - '$inCount', sale_money = sale_money - '$inMoney', sale_money_no_tax = sale_money_no_tax - '$inMoneyNoTax' ";
		} else if ($type == "库存损溢-损"){
			$outCount = $detail["out_count"];
			$outMoney = $goods_info["lastbuyprice"] * $outCount;
			$outMoneyNoTax = round($outMoney / (1 + $buytax), 2);
			$last_sql .= " sun_count = sun_count + '$outCount', sun_money = sun_money + '$outMoney', sun_money_no_tax = sun_money_no_tax + '$outMoneyNoTax' ";
		} else if ($type == "库存损溢-溢"){
			$inCount = $detail["in_count"];
			$inMoney = $goods_info["lastbuyprice"] * $inCount;
			$inMoneyNoTax = round($inMoney / (1 + $buytax), 2);
			$last_sql .= " yi_count = yi_count + '$inCount', yi_money = yi_money + '$inMoney', yi_money_no_tax = yi_money_no_tax + '$inMoneyNoTax' ";
		} else if ($type == "库存盘点-盘亏出库"){
			$outCount = $detail["out_count"];
			$outMoney = $goods_info["lastbuyprice"] * $outCount;
			$outMoneyNoTax = round($outMoney / (1 + $buytax), 2);
			$last_sql .= " pan_out_count = pan_out_count + '$outCount', pan_out_money = pan_out_money + '$outMoney', pan_out_money_no_tax = pan_out_money_no_tax + '$outMoneyNoTax' ";
		} else if ($type == "库存盘点-盘盈入库"){
			$inCount = $detail["in_count"];
			$inMoney = $goods_info["lastbuyprice"] * $inCount;
			$inMoneyNoTax = round($inMoney / (1 + $buytax), 2);
			$last_sql .= " pan_in_count = pan_in_count + '$inCount', pan_in_money = pan_in_money + '$inMoney', pan_in_money_no_tax = pan_in_money_no_tax + '$inMoneyNoTax' ";
		}
		$last_sql .= ", balance_count = '".$detail["balance_count"]."', balance_money='".$detail["balance_count"]*$goods_info["lastbuyprice"]."', balance_money_no_tax = '".($detail["balance_count"]*$goods_info["lastbuyprice"]) / (1+$buytax)."' where id='".$inout["id"]."'";

		$db->startTrans();
		$db->execute($last_sql);
		//如果是验收入库，则需要计算平均入库单价
		if($is_ia){
			$in_sql = "select sum(ia_money) as ia_money, sum(ia_count) as ia_count from t_inout_day where year(biz_date) = $year and month(biz_date) = $month and goods_id='".$detail["goods_id"]."'";
			$in_data = $db->query($in_sql);
			if($in_data && $in_data[0]["ia_count"] > 0){
				$avg_price = $in_data[0]["ia_money"] / $in_data[0]["ia_count"];
				$in_sql = "update t_inout_day set buyprice = $avg_price where year(biz_date) = $year and month(biz_date) = $month and goods_id = '".$detail["goods_id"]."'";
				$db->execute($in_sql);
			} else {	

			}
			
		}
		$detail_db->where(array("id"=>$detail["id"]))->setField("is_tongji", 1);
		$db->commit();
		return $this->ok("success");
	}




}