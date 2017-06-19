<?php

namespace Home\Service;

/**
 * 加工入库Service
 *
 * @author 李静波
 */
class PCBillService extends ERPBaseService {

	public function pcbillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$billid_sql = "";
		if($billid = $params["billid"]){
			$billid_sql = " and p.ref like '%$billid%' ";
		}
		$billdate_sql = "";
		if($billdate = $params["billdate"]){
			if(strpos($billdate, "T") > -1){
				//$billdate_arr = explode("T", $billdate);
				//$billdate = $billdate_arr[0];
				$billdate = str_replace("T", " ", $billdate);
			}
			$billdate_sql = " and p.biz_dt = '$billdate' ";
		}
		$type = $params["type"];
		$_bill_status_str = "0,1,2,3";
		if($type == "verify"){
			$_bill_status_str = "2,5";
		}
		$db = M();
		
		$sql = "select p.id, p.bill_status, p.ref, p.biz_dt, u1.name as biz_user_name, u2.name as input_user_name, 
				 w1.name as in_warehouse_name, w2.name as out_warehouse_name 
				from t_pc_bill p, t_warehouse w1, t_warehouse w2, t_user u1, t_user u2 
				where p.bill_status in ($_bill_status_str) $billid_sql $billdate_sql and p.in_warehouse_id = w1.id and p.out_warehouse_id = w2.id 
				and p.biz_user_id = u1.id and p.input_user_id = u2.id 
				order by p.ref desc 
				limit $start,$limit";
		//die($sql);
		//exit;
		$data = $db->query($sql);
		$result = array();
		
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["biz_dt"]));
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["inWarehouseName"] = $v["in_warehouse_name"];
			$result[$i]["outWarehouseName"] = $v["out_warehouse_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$bill_status_str = "";
			if($v["bill_status"] == 0){
				$bill_status_str = "未提交";
			} else if ($v["bill_status"] == 1){
				$bill_status_str = "已审核";
			} else if ($v["bill_status"] == 2){
				$bill_status_str = "审核中";
			} else if ($v["bill_status"] == 3){
				$bill_status_str = "已驳回";
			} else if ($v["bill_status"] == 4){
				$bill_status_str = "已入库";
			} else if ($v["bill_status"] == 5){
				$bill_status_str = "已完成";
			}
			$result[$i]["billStatus"] = $bill_status_str;
			$result[$i]["amount"] = $v["goods_money"];
		}
		
		$sql = "select count(*) as cnt 
				from t_pc_bill p, t_warehouse w, t_user u1, t_user u2 
				where p.bill_status in ($_bill_status_str) $billid_sql $billdate_sql and p.in_warehouse_id = w.id  
				and p.biz_user_id = u1.id and p.input_user_id = u2.id";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function pcBillDetailList($pwbillId) {
		$sql = "select p.id, g.code, g.name, g.spec, u.name as unit_name, p.goods_count, p.goods_count_after, p.goods_count_after_actual, p.goods_bar_code, p.goods_price, p.goods_money, p.type 
				from t_pc_bill_detail p, t_goods g, t_goods_unit u 
				where p.pcbill_id = '%s' and p.goods_id = g.id and g.unit_id = u.id 
				order by p.show_order ";
		$data = M()->query($sql, $pwbillId);
		$result1 = array();
		$result2 = array();
		foreach ( $data as $i => $v ) {
			$result["id"] = $v["id"];
			$result["goodsCode"] = $v["code"];
			$result["goodsName"] = $v["name"];
			$result["goodsSpec"] = $v["spec"];
			$result["unitName"] = $v["unit_name"];
			$result["goodsCount"] = $v["goods_count"];
			$result["goodsCountAfter"] = $v["goods_count_after"];
			$result["goodsCountAfterActual"] = $v["goods_count_after_actual"];
			$result["goodsBarCode"] = $v["goods_bar_code"];
			$result["goodsMoney"] = $v["goods_money"];
			$result["goodsPrice"] = $v["goods_price"];
			if($v["type"] == 0){
				$result1[] = $result;
			} else {
				$result2[] = $result;
			}
			
		}
		
		return array("data1"=>$result1, "data2"=>$result2);
	}

	public function editPCBill($json) {
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$warehouseId = $bill["warehouseId"];
		$inWarehouseId = $bill["inWarehouseId"];
		$outWarehouseId = $bill["outWarehouseId"];
		$supplierId = $bill["supplierId"];
		$bizUserId = $bill["bizUserId"];
		
		$db = M();
		
		$sql = "select count(*) as cnt from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			return $this->bad("入库仓库不存在");
		}
		
		$sql = "select count(*) as cnt from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			return $this->bad("业务人员不存在");
		}
		
		$idGen = new IdGenService();
		if ($id) {
			// 编辑
			$sql = "select ref, bill_status from t_pc_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $this->bad("要编辑的加工单不存在");
			}
			$billStatus = $data[0]["bill_status"];
			$ref = $data[0]["ref"];
			if ($billStatus != 0 && $billStatus != 3) {
				return $this->bad("当前加工单已经提交入库，不能再编辑");
			}
			
			$db->startTrans();
			try {
				$sql = "delete from t_pc_bill_detail where pcbill_id = '%s' ";
				$db->execute($sql, $id);
				
				// 明细记录
				$items = $bill["items"];
				$source_items = $bill["source_items"];
				//成品明细
				foreach ( $items as $i => $item ) {
					$goodsId = $item["goodsId"];
					$goodsCount = $item["goodsCount"];
					$goodsMoney = $item["goodsMoney"] ? $item["goodsMoney"] : 0;
					$goodsPrice = $item["goodsPrice"] ? $item["goodsPrice"] : 0;
					$goodsBarCode = $item["goodsBarCode"];
					$goodsType = $item["goodsType"];
					if ($goodsId != null && $goodsCount != 0) {
						// 检查商品是否存在
						//$sql = "select count(*) as cnt from t_goods where id = '%s' ";
						//$data = $db->query($sql, $goodsId);
						//$cnt = $data[0]["cnt"];
						if (true) {
							
							//$goodsPrice = floatval($item["goodsPrice"]);
							//$goodsMoney = $goodsCount * $goodsPrice;
							
							$sql = "insert into t_pc_bill_detail 
									(id, date_created, goods_id, goods_count,goods_price, 
									goods_money,  pcbill_id, show_order, type, goods_bar_code)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d, %s )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, 1, $goodsBarCode);
						}
					}
				}
				//半成品明细
				foreach ( $source_items as $i => $item ) {
					$goodsId = $item["goodsId"];
					$goodsCount = intval($item["goodsCount"]);
					$goodsCountAfter = $item["goodsCountAfter"];
					$goodsCountAfterActual = $item["goodsCountAfterActual"] ? $item["goodsCountAfterActual"] : $item["goodsCountAfter"];
					$goodsPrice = 0;
					$goodsMoney = 0;
					$goodsType = $item["goodsType"];
					if ($goodsId != null && $goodsCount != 0) {
						// 检查商品是否存在
						$sql = "select count(*) as cnt from t_goods where id = '%s' ";
						$data = $db->query($sql, $goodsId);
						$cnt = $data[0]["cnt"];
						if ($cnt == 1) {
							
							//$goodsPrice = floatval($item["goodsPrice"]);
							//$goodsMoney = $goodsCount * $goodsPrice;
							
							$sql = "insert into t_pc_bill_detail 
									(id, date_created, goods_id, goods_count, goods_price,
									goods_money,  pcbill_id, show_order, goods_count_after, goods_count_after_actual, type)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %f, %f, %d )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, $goodsCountAfter , $goodsCountAfterActual ,0);
						}
					}
				}
				/*
				$sql = "select sum(goods_money) as goods_money from t_pc_bill_detail 
						where pcbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$totalMoney = $data[0]["goods_money"];
				*/
				$sql = "update t_pc_bill 
						set in_warehouse_id = '%s', " . " out_warehouse_id = '%s', biz_dt = '%s' 
						where id = '%s' ";
				$db->execute($sql, $inWarehouseId, $outWarehouseId, $bizDT, $id);
				
				$log = "编辑加工单: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "加工单");
				$db->commit();
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库操作错误，请联系管理员");
			}
		} else {
			$id = $idGen->newId();
			
			$db->startTrans();
			try {
				$sql = "insert into t_pc_bill (id, ref, supplier_id, in_warehouse_id, out_warehouse_id, biz_dt, 
						biz_user_id, bill_status, date_created, goods_money, input_user_id) 
						values ('%s', '%s', '%s', '%s', '%s', '%s','%s', 0, now(), 0, '%s')";
				
				$ref = $this->genNewBillRef();
				$us = new UserService();
				$db->execute($sql, $id, $ref, $supplierId, $inWarehouseId, $outWarehouseId, $bizDT, $bizUserId, $us->getLoginUserId());
				
				// 明细记录
				$items = $bill["items"];
				$source_items = $bill["source_items"];
				//成品明细
				foreach ( $items as $i => $item ) {
					$goodsId = $item["goodsId"];
					$goodsCount = $item["goodsCount"];
					$goodsMoney = $item["goodsMoney"] ? $item["goodsMoney"] : 0;
					$goodsPrice = $item["goodsPrice"] ? $item["goodsPrice"] : 0;
					$goodsBarCode = $item["goodsBarCode"];
					$goodsType = $item["goodsType"];
					if ($goodsId != null && $goodsCount != 0) {
						// 检查商品是否存在
						///$sql = "select count(*) as cnt from t_goods where id = '%s' ";
						//$data = $db->query($sql, $goodsId);
						//$cnt = $data[0]["cnt"];
						if (true) {
							
							//$goodsPrice = floatval($item["goodsPrice"]);
							//$goodsMoney = $goodsCount * $goodsPrice;
							
							$sql = "insert into t_pc_bill_detail 
									(id, date_created, goods_id, goods_count,goods_price, 
									goods_money,  pcbill_id, show_order, type, goods_bar_code)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d, %s )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, 1, $goodsBarCode);
						}
					}
				}
				//半成品明细
				foreach ( $source_items as $i => $item ) {
					$goodsId = $item["goodsId"];
					$goodsCount = intval($item["goodsCount"]);
					$goodsCountAfter = $item["goodsCountAfter"];
					$goodsCountAfterActual = $item["goodsCountAfterActual"] ? $item["goodsCountAfterActual"] : $item["goodsCountAfter"];
					$goodsPrice = 0;
					$goodsMoney = 0;
					$goodsType = $item["goodsType"];
					if ($goodsId != null && $goodsCount != 0) {
						// 检查商品是否存在
						$sql = "select count(*) as cnt from t_goods where id = '%s' ";
						$data = $db->query($sql, $goodsId);
						$cnt = $data[0]["cnt"];
						if ($cnt == 1) {
							
							//$goodsPrice = floatval($item["goodsPrice"]);
							//$goodsMoney = $goodsCount * $goodsPrice;
							
							$sql = "insert into t_pc_bill_detail 
									(id, date_created, goods_id, goods_count, goods_price,
									goods_money,  pcbill_id, show_order, goods_count_after, goods_count_after_actual, type)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %f, %f, %d )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, $goodsCountAfter , $goodsCountAfterActual ,0);
						}
					}
				}
				/*
				$sql = "select sum(goods_money) as goods_money from t_pw_bill_detail 
						where pcbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$totalMoney = $data[0]["goods_money"];
				$sql = "update t_pw_bill
						set goods_money = %f 
						where id = '%s' ";
				$db->execute($sql, $totalMoney, $id);
				*/
				$log = "新建加工单: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "加工单");
				
				$db->commit();
			} catch ( Exception $exc ) {
				$db->rollback();
				
				return $this->bad("数据库操作错误，请联系管理员");
			}
		}
		
		return $this->ok($id);
	}

	private function genNewBillRef() {
		$pre = "PC";
		$mid = date("Ymd");
		
		$sql = "select ref from t_pc_bill where ref like '%s' order by ref desc limit 1";
		$data = M()->query($sql, $pre . $mid . "%");
		$suf = "001";
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, 10)) + 1;
			$suf = str_pad($nextNumber, 3, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	public function pcBillInfo($id) {
		$result["id"] = $id;
		
		$db = M();
		$sql = "select p.ref, p.bill_status, 
				p.in_warehouse_id, w1.name as in_warehouse_name, p.out_warehouse_id, p.in_warehouse_id, w2.name as out_warehouse_name,
				p.biz_user_id, u.name as biz_user_name, p.biz_dt 
				from t_pc_bill p, t_warehouse w1, t_warehouse w2, t_user u 
				where p.id = '%s' and p.in_warehouse_id = w1.id and p.out_warehouse_id = w2.id 
				  and p.biz_user_id = u.id";
		$data = $db->query($sql, $id);
		if ($data) {
			$v = $data[0];
			$result["ref"] = $v["ref"];
			$result["billStatus"] = $v["bill_status"];
			//$result["supplierId"] = $v["supplier_id"];
			//$result["supplierName"] = $v["supplier_name"];
			$result["inWarehouseId"] = $v["in_warehouse_id"];
			$result["outWarehouseId"] = $v["out_warehouse_id"];
			$result["inWarehouseName"] = $v["in_warehouse_name"];
			$result["outWarehouseName"] = $v["out_warehouse_name"];
			$result["bizUserId"] = $v["biz_user_id"];
			$result["bizUserName"] = $v["biz_user_name"];
			$result["bizDT"] = date("Y-m-d", strtotime($v["biz_dt"]));
			
			$items = array();
			$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, g.barcode, u.name as unit_name, 
					p.goods_count, p.goods_price, p.goods_money, p.type, p.goods_count_after, p.goods_count_after_actual, p.goods_bar_code 
					from t_pc_bill_detail p, t_goods g, t_goods_unit u 
					where p.goods_Id = g.id and g.unit_id = u.id and p.pcbill_id = '%s' 
					order by p.show_order";
			$data = $db->query($sql, $id);
			$items = array();
			$source_items = array();
			foreach ( $data as $i => $v ) {
				$item["id"] = $v["id"];
				$item["goodsId"] = $v["goods_id"];
				$item["goodsCode"] = $v["barcode"];

				$item["goodsName"] = $v["name"];
				$item["goodsSpec"] = $v["spec"];
				$item["unitName"] = $v["unit_name"];
				$item["goodsCount"] = $v["goods_count"];
				$item["goodsCountAfter"] = $v["goods_count_after"];
				$item["goodsCountAfterActual"] = $v["goods_count_after_actual"];
				$item["goodsBarCode"] = $v["goods_bar_code"];
				$item["goodsPrice"] = $v["goods_price"];
				$item["goodsMoney"] = $v["goods_money"];
				$item["type"] = $v["type"];
				$item["_goodsCount"] = $v["goods_count"];
				$item["_goodsPrice"] = $v["goods_price"];
				if($v["type"] == 1){
					$items[] = $item;
				} else {
					$item["goodsBarCode"] = $v["barcode"];
					$source_items[] = $item;
				}
				
			}
			
			$result["items"] = $items;
			$result["source_items"] = $source_items;
		} else {
			// 新建加工单
			$us = new UserService();
			$result["bizUserId"] = $us->getLoginUserId();
			$result["bizUserName"] = $us->getLoginUserName();
			
			$ts = new BizConfigService();
			if ($ts->warehouseUsesOrg()) {
				$ws = new WarehouseService();
				$data = $ws->getWarehouseListForLoginUser("2001");
				if (count($data) > 0) {
					$result["inWarehouseId"] = $data[0]["id"];
					$result["inWarehouseName"] = $data[0]["name"];
					$result["outWarehouseId"] = $data[0]["id"];
					$result["outWarehouseName"] = $data[0]["name"];
				}
			} else {
				$sql = "select value from t_config where id = '2001-01' ";
				$data = $db->query($sql);
				if ($data) {
					$warehouseId = $data[0]["value"];
					$sql = "select id, name from t_warehouse where id = '%s' ";
					$data = $db->query($sql, $warehouseId);
					if ($data) {
						$result["inWarehouseId"] = $data[0]["id"];
						$result["inWarehouseName"] = $data[0]["name"];
						$result["outWarehouseId"] = $data[0]["id"];
						$result["outWarehouseName"] = $data[0]["name"];
					}
				}
			}
		}
		
		return $result;
	}

	public function deletePCBill($id) {
		$db = M();
		$sql = "select ref, bill_status from t_pc_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要废弃的加工单不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus != 0 && $billStatus != 2) {
			return $this->bad("当前加工单已经提交审核或入库，不能删除");
		}
		
		$db->startTrans();
		try {
			//$sql = "update from t_pw_bill_detail set where pwbill_id = '%s' ";
			//$db->execute($sql, $id);
			
			$sql = "update t_pc_bill set bill_status = 9999  where id = '%s' ";
			$db->execute($sql, $id);
			
			$log = "废弃加工单: 单号 = {$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "加工单");
			
			$db->commit();
		} catch ( Exception $exc ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok();
	}

	public function verifyPCBill($id){
		$db = M();
		$sql = "select ref, bill_status, biz_dt, biz_user_id,  goods_money 
				from t_pc_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要审核的加工单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(2);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("加工单不是被审核状态，无法审核.");
		}
		//这里修改为仅改变状态，不改变库存，库存修改移动到验收单的提交里
		try{
			$map = array(
				"id" => $id
			);
			$data = array(
				"bill_status" => 1
			);
			M("pc_bill", "t_")->where($map)->save($data);
			return $this->ok($id);
		} catch( Exception $exc ){
			return $this->bad("数据库错误，请联系管理员");
		}
	}

	public function rejectPCBill($id){
		$db = M();
		$sql = "select ref, bill_status, biz_dt, biz_user_id,  goods_money 
				from t_pc_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要驳回的加工单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(2);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("加工单不是被审核状态，无法驳回.");
		}
		//修改状态
		try{
			$map = array(
				"id" => $id
			);
			$data = array(
				"bill_status" => 3
			);
			M("pc_bill", "t_")->where($map)->save($data);
			return $this->ok($id);
		} catch( Exception $exc ){
			return $this->bad("数据库错误，请联系管理员");
		}
	}

	public function queryData($queryKey, $queryDate = "", $queryBillStatus = ""){
		if ($queryKey == null) {
			$queryKey = "";
		}
		$date_sql = "";
		$supplier_goods_arr = array(0);
		$bill_status_sql = "";
		if($queryDate){
			/*
			$map = array(
				"supplierid" => $querySupplierid
			);
			$goods_id_list = M("supplier_goods", "t_")->where($map)->select();
			foreach ($goods_id_list as $key => $value) {
				$supplier_goods_arr[] = $value["goodsid"];
			}
			*/
			//$supplierid_sql = "and g.id in (select goodsid from t_supplier_goods s where s.supplierid = '$querySupplierid' ) ";
			$date_sql = " and p.biz_dt = '$queryDate' ";
		}
		if($queryBillStatus){
			$bill_status_sql = " and p.bill_status in ($queryBillStatus) ";
		}
		$sql = "select p.id, p.biz_dt, p.goods_money, p.ref, w1.name as in_warehouse_name,w2.name as out_warehouse_name 
				from t_pc_bill p, t_warehouse w1, t_warehouse w2 
				where (p.in_warehouse_id = w1.id and p.out_warehouse_id = w2.id) $date_sql $bill_status_sql
				and (p.ref like '%s' or p.biz_dt like '%s') 
				order by p.biz_dt desc  
				limit 20";
		$key = "%{$queryKey}%";
		//dump($sql);
		//exit;
		$data = M()->query($sql, $key, $key);
		return $data;
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["spec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
		}
		
		return $result;
	}

//提交审核
	public function submitPCBill($id){
		$db = M();
		$sql = "select *  
				from t_pc_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要提交的加工单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(0,3);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("加工单已经提交或已经入库，不能再次提交");
		}

		$ref = $data[0]["ref"];
		$bizDT = $data[0]["biz_dt"];
		$bizUserId = $data[0]["biz_user_id"];
		$billPayables = floatval($data[0]["goods_money"]);
		$supplierId = $data[0]["supplier_id"];
		$warehouseId = $data[0]["in_warehouse_id"];
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("要入库的仓库不存在");
		}
		$inited = $data[0]["inited"];
		if ($inited == 0) {
			//return $this->bad("仓库 [{$data[0]['name']}] 还没有完成建账，不能做采购入库的操作");
		}
		
		$sql = "select goods_id, goods_count, goods_price, goods_money 
				from t_pc_bill_detail 
				where pcbill_id = '%s' and type=0 order by show_order";
		$items = $db->query($sql, $id);
		if (! $items) {
			return $this->bad("加工单没有半成品明细记录，不能提交");
		}
		$sql = "select goods_id, goods_count, goods_price, goods_money 
				from t_pc_bill_detail 
				where pcbill_id = '%s' and type=1 order by show_order";
		$items2 = $db->query($sql, $id);
		if (! $items2) {
			return $this->bad("加工单没有成品明细记录，不能提交");
		}
		
		// 检查入库数量和单价不能为负数
		foreach ( $items as $v ) {
			$goodsCount = intval($v["goods_count"]);
			if ($goodsCount <= 0) {
				//return $this->bad("数量不能小于0");
			}
			$goodsPrice = floatval($v["goods_price"]);
			if ($goodsPrice < 0) {
				//return $this->bad("采购单价不能为负数");
			}
		}
		foreach ( $items2 as $v ) {
			$goodsCount = intval($v["goods_count"]);
			if ($goodsCount <= 0) {
				//return $this->bad("数量不能小于0");
			}
			$goodsPrice = floatval($v["goods_price"]);
			if ($goodsPrice < 0) {
				//return $this->bad("采购单价不能为负数");
			}
		}
		//这里修改为仅改变状态，不改变库存，库存修改移动到验收单的提交里
		try{
			$map = array(
				"id" => $id
			);
			$data = array(
				"bill_status" => 2
			);
			M("pc_bill", "t_")->where($map)->save($data);
			return $this->ok($id);
		} catch( Exception $exc ){
			return $this->bad("数据库错误，请联系管理员");
		}
	}

	public function commitPCBill($id) {
		$db = M();
		$sql = "select ref, in_warehouse_id, out_warehouse_id, bill_status, biz_dt, biz_user_id,  goods_money, supplier_id 
				from t_pc_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要提交的加工单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(2);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("加工单已经提交或已经入库，不能再次提交");
		}

		$ref = $data[0]["ref"];
		$bizDT = $data[0]["biz_dt"];
		$bizUserId = $data[0]["biz_user_id"];
		$billPayables = floatval($data[0]["goods_money"]);
		$supplierId = $data[0]["supplier_id"];
		$inWarehouseId = $data[0]["in_warehouse_id"];
		$outWarehouseId = $data[0]["out_warehouse_id"];
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $inWarehouseId);
		if (! $data) {
			return $this->bad("要入库的仓库不存在");
		}
		$inited = $data[0]["inited"];
		if ($inited == 0) {
			//return $this->bad("仓库 [{$data[0]['name']}] 还没有完成建账，不能做采购入库的操作");
		}
		
		$sql = "select goods_id, goods_count, goods_price, goods_money , goods_bar_code, goods_count_after_actual, goods_count_after 
				from t_pc_bill_detail 
				where pcbill_id = '%s' and type=0 order by show_order";
		$items_source = $db->query($sql, $id);
		if (! $items_source) {
			return $this->bad("加工单没有半成品明细记录，不能提交");
		}
		$sql = "select goods_id, goods_count, goods_price, goods_money 
				from t_pc_bill_detail 
				where pcbill_id = '%s' and type=1 order by show_order";
		$items = $db->query($sql, $id);
		if (! $items) {
			return $this->bad("加工单没有成品明细记录，不能提交");
		}
		// 检查入库数量和单价不能为负数
		foreach ( $items_source as $v ) {
			$goodsCount = intval($v["goods_count"]);
			if ($goodsCount <= 0) {
				//return $this->bad("数量不能小于0");
			}
			$goodsCountAfterActual = floatval($v["goods_count_after_actual"]);
			if ($goodsCountAfterActual < 0) {
				return $this->bad("剩余实际库存不能为负数");
			}
		}
		foreach ( $items as $v ) {
			$goodsCount = intval($v["goods_count"]);
			if ($goodsCount <= 0) {
				//return $this->bad("数量不能小于0");
			}
			$goodsPrice = floatval($v["goods_price"]);
			if ($goodsPrice < 0) {
				//return $this->bad("采购单价不能为负数");
			}
		}
		//dump($items_source);
		//exit;
		//开始提交入库
		$db->startTrans();
		try {
			//首先是原料出库
			foreach ( $items_source as $v ) {
				$itemId = $v["id"];
				$goodsId = $v["goods_id"];
				$goodsCount = floatval($v["goods_count"]);
				$goodsPrice = floatval($v["goods_price"]);
				$goodsCountAfter = floatval($v["goods_count_after"]);
				$goodsCountAfterActual = floatval($v["goods_count_after_actual"]);
				$goodsCount = $goodsCount - $goodsCountAfterActual;
				$sql = "select code, name from t_goods where id = '%s' ";
				$data = $db->query($sql, $goodsId);
				if (! $data) {
					$db->rollback();
					return $this->bad("半成品不存在(商品后台id = {$goodsId})");
				}
				$goodsCode = $data[0]["code"];
				$goodsName = $data[0]["name"];
				if ($goodsCount <= 0) {
					$db->rollback();
					return $this->bad("商品[{$goodsCode} {$goodsName}]的出库数量需要是正数");
				}
				
				// 库存总账
				$sql = "select out_count, out_money, balance_count, balance_price,
						balance_money from t_inventory 
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $outWarehouseId, $goodsId);
				if (! $data) {
					$db->rollback();
					return $this->bad("商品 [{$goodsCode} {$goodsName}] 在仓库 [{$warehouseName}] 中没有存货，无法出库");
				}
				$balanceCount = $data[0]["balance_count"];
				if ($balanceCount < $goodsCount) {
					$db->rollback();
					return $this->bad("商品 [{$goodsCode} {$goodsName}] 在仓库 [{$warehouseName}] 中存货数量不足，无法出库");
				}
				//加工不做实际出库操作，只做损益记录
				/*
				$balancePrice = $data[0]["balance_price"];
				$balanceMoney = $data[0]["balance_money"];
				$outCount = $data[0]["out_count"];
				$outMoney = $data[0]["out_money"];
				$balanceCount -= $goodsCount;
				if ($balanceCount == 0) {
					// 当全部出库的时候，金额也需要全部转出去
					$outMoney += $balanceMoney;
					$outPriceDetail = $balanceMoney / $goodsCount;
					$outMoneyDetail = $balanceMoney;
					$balanceMoney = 0;
				} else {
					$outMoney += $goodsCount * $balancePrice;
					$outPriceDetail = $balancePrice;
					$outMoneyDetail = $goodsCount * $balancePrice;
					$balanceMoney -= $goodsCount * $balancePrice;
				}
				$outCount += $goodsCount;
				$outPrice = $outMoney / $outCount;
				
				$sql = "update t_inventory 
						set out_count = %d, out_price = %f, out_money = %f,
						    balance_count = %f, balance_money = %f 
						where warehouse_id = '%s' and goods_id = '%s' ";
				$db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, $balanceMoney, $outWarehouseId, $goodsId);
				
				// 库存明细账
				$sql = "insert into t_inventory_detail(out_count, out_price, out_money, 
						balance_count, balance_price, balance_money, warehouse_id,
						goods_id, biz_date, biz_user_id, date_created, ref_number, ref_type) 
						values(%d, %f, %f, %d, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '加工出库')";
				$db->execute($sql, $goodsCount, $outPriceDetail, $outMoneyDetail, $balanceCount, $balancePrice, $balanceMoney, $outWarehouseId, $goodsId, $bizDT, $bizUserId, $ref);
				*/
				//如果实际称重之后有损耗，则计入库存损耗
				$lossCount = $goodsCountAfter - $goodsCountAfterActual;
				if($lossCount > 0){
					$goodsCount = $lossCount;
					$balancePrice = $data[0]["balance_price"];
					$balanceMoney = $data[0]["balance_money"];
					$outCount = $data[0]["out_count"];
					$outMoney = $data[0]["out_money"];
					$balanceCount -= $goodsCount;
					if ($balanceCount == 0) {
						// 当全部出库的时候，金额也需要全部转出去
						$outMoney += $balanceMoney;
						$outPriceDetail = $balanceMoney / $goodsCount;
						$outMoneyDetail = $balanceMoney;
						$balanceMoney = 0;
					} else {
						$outMoney += $goodsCount * $balancePrice;
						$outPriceDetail = $balancePrice;
						$outMoneyDetail = $goodsCount * $balancePrice;
						$balanceMoney -= $goodsCount * $balancePrice;
					}
					$outCount += $lossCount;
					// 库存明细账，损
					$sql = "update t_inventory 
						set out_count = %f, out_price = %f, out_money = %f,
						    balance_count = %f, balance_money = %f 
						where warehouse_id = '%s' and goods_id = '%s' ";
					$db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, $balanceMoney, $outWarehouseId, $goodsId);
					$sql = "insert into t_inventory_detail(out_count, out_price, out_money, 
						balance_count, balance_price, balance_money, warehouse_id,
						goods_id, biz_date, biz_user_id, date_created, ref_number, ref_type) 
						values(%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '加工单损')";
					$db->execute($sql, $lossCount, $outPriceDetail, $outMoneyDetail, $balanceCount, $balancePrice, $balanceMoney, $outWarehouseId, $goodsId, $bizDT, $bizUserId, $ref);
					//dump(M()->getLastSql());
					//exit;
				}
				if($lossCount < 0){
					//溢
					$goodsCount = $lossCount;
					$lossCount = $goodsCountAfterActual - $goodsCountAfter;
					$inCount = intval($data[0]["in_count"]);
					$inMoney = floatval($data[0]["in_money"]);
					$balanceCount = intval($data[0]["balance_count"]);
					$balanceMoney = floatval($data[0]["balance_money"]);
					
					$inCount += $lossCount;
					$inMoney += $goodsPrice;
					$inPrice = $inMoney / $inCount;
					
					$balanceCount += $goodsCount;
					$balanceMoney += $goodsMoney;
					$balancePrice = $balanceMoney / $balanceCount;
					
					$sql = "update t_inventory 
							set in_count = %f, in_price = %f, in_money = %f,
							balance_count = %f, balance_price = %f, balance_money = %f 
							where warehouse_id = '%s' and goods_id = '%s' ";
					$db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, $balanceMoney, $inWarehouseId, $goodsId);
					// 库存明细账
					$sql = "insert into t_inventory_detail (in_count, in_price, in_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id, biz_date,
						biz_user_id, date_created, ref_number, ref_type)
						values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '加工单溢')";
					$db->execute($sql, $lossCount, $goodsPrice, $goodsMoney, $balanceCount, $balancePrice, $balanceMoney, $outWarehouseId, $goodsId, $bizDT, $bizUserId, $ref);
				}
				// 单据本身的记录
				
				$sql = "update t_pc_bill
						set bill_status = %d
						where id = '%s' ";
				$db->execute($sql, 5, $id);
				
			}
			/*
			foreach ( $items as $v ) {
				$goodsCount = intval($v["goods_count"]);
				$goodsPrice = floatval($v["goods_price"]);
				$goodsMoney = floatval($v["goods_money"]);
				$goodsId = $v["goods_id"];
				
				$balanceCount = 0;
				$balanceMoney = 0;
				$balancePrice = (float)0;
				// 库存总账
				$sql = "select in_count, in_money, balance_count, balance_money 
						from t_inventory 
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $inWarehouseId, $goodsId);
				if ($data) {
					$inCount = intval($data[0]["in_count"]);
					$inMoney = floatval($data[0]["in_money"]);
					$balanceCount = intval($data[0]["balance_count"]);
					$balanceMoney = floatval($data[0]["balance_money"]);
					
					$inCount += $goodsCount;
					$inMoney += $goodsMoney;
					$inPrice = $inMoney / $inCount;
					
					$balanceCount += $goodsCount;
					$balanceMoney += $goodsMoney;
					$balancePrice = $balanceMoney / $balanceCount;
					
					$sql = "update t_inventory 
							set in_count = %d, in_price = %f, in_money = %f,
							balance_count = %d, balance_price = %f, balance_money = %f 
							where warehouse_id = '%s' and goods_id = '%s' ";
					$db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, $balanceMoney, $inWarehouseId, $goodsId);
				} else {
					$inCount = $goodsCount;
					$inMoney = $goodsMoney;
					$inPrice = $inMoney / $inCount;
					$balanceCount += $goodsCount;
					$balanceMoney += $goodsMoney;
					$balancePrice = $balanceMoney / $balanceCount;
					
					$sql = "insert into t_inventory (in_count, in_price, in_money, balance_count,
							balance_price, balance_money, warehouse_id, goods_id)
							values (%d, %f, %f, %d, %f, %f, '%s', '%s')";
					$db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, $balanceMoney, $inWarehouseId, $goodsId);
				}
				
				// 库存明细账
				$sql = "insert into t_inventory_detail (in_count, in_price, in_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id, biz_date,
						biz_user_id, date_created, ref_number, ref_type)
						values (%d, %f, %f, %d, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '加工入库')";
				$db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount, $balancePrice, $balanceMoney, $inWarehouseId, $goodsId, $bizDT, $bizUserId, $ref);
			}
			
			$sql = "update t_pc_bill set bill_status = 5 where id = '%s' ";
			$db->execute($sql, $id);
			*/
			// 应付明细账
			/*
			$sql = "insert into t_payables_detail (id, pay_money, act_money, balance_money,
					ca_id, ca_type, date_created, ref_number, ref_type, biz_date)
					values ('%s', %f, 0, %f, '%s', 'supplier', now(), '%s', '采购入库', '%s')";
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
			*/
			// 日志
			$log = "提交加工入库: 单号 = {$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "加工入库");
			
			$db->commit();
		} catch ( Exception $exc ) {
			$db->rollback();
			return $this->bad("数据库操作错误，请重试或者联系管理员");
		}
		
		return $this->ok($id);
	}
}