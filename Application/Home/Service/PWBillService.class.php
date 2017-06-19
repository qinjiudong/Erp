<?php

namespace Home\Service;

/**
 * 采购入库Service
 *
 * @author dubin 
 */
class PWBillService extends ERPBaseService {

	public function pwbillList($params) {
		$page = $params["page"] ? $params["page"] : 1;
		$start = $params["start"] ? $params["start"] : 0;
		$limit = $params["limit"] ? $params["limit"] : 100;
		$begindate = $params["begindate"];
		$enddate   = $params["enddate"];
		$supplier  = $params["supplier"];
		$goodsname = $params["goodsname"];
		$status    = $params["status"];
		$billid_sql = "";
		if($billid = $params["billid"]){
			$billid_sql = " and p.ref like '%$billid%' ";
		}
		$billdate_sql = "";
		if($billdate = $params["billdate"]){
			if(strpos($billdate, "T") > -1){
				$billdate = str_replace("T", " ", $billdate);
			}
			$billdate_sql = " and p.biz_dt = '$billdate' ";
		} else {
			if($begindate){
				$begindate = str_replace("T", " ", $begindate);
				$billdate_sql = " and p.biz_dt > '$begindate' ";
			}
			if($enddate){
				$enddate = str_replace("T", " ", $enddate);
				$billdate_sql .= " and p.biz_dt < '$enddate' ";
			}
		}
		$supplier_sql = "";
		if($supplier){
			$supplier_sql = " and s.name like '%$supplier%'";
		}
		if($status === ""){
			$bill_status_str = "0,1,2,3,4,5";
		} else {
			$bill_status_str = "$status";
		}
		$type = $params["type"];
		
		if($type == "verify"){
			$bill_status_str = "2";
		}
		if($params['pda'] == 1){
			$bill_status_str = "1";
		}
		$auto = $params["auto"];
		if(!$auto){
			$auto = 0;
		}
		$auto_sql = " and p.type = $auto ";
		$db = M();
		$child_sql = "";
		if($goodsname){
			$child_sql = " and p.id in (select pwbill_id from t_pw_bill_detail where goods_id = '$goodsname') ";
		}
		$sql = "select p.id, p.bill_status, p.ref, p.biz_dt, u1.name as biz_user_name, u2.name as input_user_name, u3.name as verify_user_name ,
				p.goods_money, w.name as warehouse_name, s.name as supplier_name ,s.code as supplier_code 
				from t_pw_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2 ,t_user u3 
				where p.bill_status in ($bill_status_str) $billid_sql $billdate_sql $supplier_sql $child_sql and p.warehouse_id = w.id and p.supplier_id = s.id 
				and p.biz_user_id = u1.id and p.input_user_id = u2.id and p.verify_user_id = u3.id $auto_sql
				order by p.ref desc 
				limit $start,$limit";
		//die($sql);
		//exit;
		$bill_status_str_bak = $bill_status_str;
		$data = $db->query($sql);
		$result = array();
		
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["biz_dt"]));
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["verifyUserName"] = $v["verify_user_name"];
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
				$bill_status_str = "已入库";
			}
			$result[$i]["billStatus"] = $bill_status_str;
			$result[$i]["amount"] = $v["goods_money"];
		}
		
		$sql = "select count(*) as cnt 
				from t_pw_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2 
				where p.bill_status in ($bill_status_str_bak) $billid_sql $billdate_sql $supplier_sql $child_sql and p.warehouse_id = w.id and p.supplier_id = s.id 
				and p.biz_user_id = u1.id and p.input_user_id = u2.id $auto_sql";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function pwBillDetailList($pwbillId) {
		$sql = "select p.id, g.code, g.name, g.barcode, g.spec, g.bulk, u.name as unit_name, p.goods_count, p.goods_price, p.goods_money, p.goods_type 
				from t_pw_bill_detail p, t_goods g, t_goods_unit u 
				where p.pwbill_id = '%s' and p.goods_id = g.id and g.unit_id = u.id 
				order by p.show_order ";
		$data = M()->query($sql, $pwbillId);
		$result = array();
		
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsCount"] = $v["goods_count"];
			$result[$i]["goodsMoney"] = $v["goods_money"];
			$result[$i]["goodsPrice"] = $v["goods_price"];
			$result[$i]["goodsType"] = $v["goods_type"] == 0 ? "正常商品":"赠品";
			$result[$i]["goodsBarCode"] = $v["barcode"];
			if($v["bulk"] == 0){
				$result[$i]["unitNamePW"] = "kg";
			} else {
				$result[$i]["unitNamePW"] = $v["unit_name"];
			}
		}
		
		return $result;
	}

	public function editPWBill($json) {
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$warehouseId = $bill["warehouseId"];
		$supplierId = $bill["supplierId"];
		$bizUserId = $bill["bizUserId"];
		
		$db = M();
		
		$sql = "select count(*) as cnt from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			return $this->bad("入库仓库不存在");
		}
		
		$sql = "select count(*) as cnt from t_supplier where id = '%s' ";
		$data = $db->query($sql, $supplierId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			return $this->bad("供应商不存在");
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
			$sql = "select ref, bill_status from t_pw_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $this->bad("要编辑的采购单不存在");
			}
			$billStatus = $data[0]["bill_status"];
			$ref = $data[0]["ref"];
			if ($billStatus != 0) {
				return $this->bad("当前采购入库单已经提交入库，不能再编辑");
			}
			
			$db->startTrans();
			try {
				$sql = "delete from t_pw_bill_detail where pwbill_id = '%s' ";
				$db->execute($sql, $id);
				
				// 明细记录
				$items = $bill["items"];
				foreach ( $items as $i => $item ) {
					$goodsId = $item["goodsId"];
					$goodsCount = floatval($item["goodsCount"]);
					$goodsType = $item["goodsType"];
					if ($goodsId != null && $goodsCount != 0) {
						// 检查商品是否存在
						$sql = "select count(*) as cnt from t_goods where id = '%s' ";
						$data = $db->query($sql, $goodsId);
						$cnt = $data[0]["cnt"];
						if ($cnt == 1) {
							
							$goodsPrice = floatval($item["goodsPrice"]);
							$goodsMoney = $goodsCount * $goodsPrice;
							
							$sql = "insert into t_pw_bill_detail (id, date_created, goods_id, goods_count, goods_price,
									goods_money,  pwbill_id, show_order, goods_type)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, $goodsType);
						}
					}
				}
				
				$sql = "select sum(goods_money) as goods_money from t_pw_bill_detail 
						where pwbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$totalMoney = $data[0]["goods_money"];
				$sql = "update t_pw_bill 
						set goods_money = %f, warehouse_id = '%s', " . " supplier_id = '%s', biz_dt = '%s' 
						where id = '%s' ";
				$db->execute($sql, $totalMoney, $warehouseId, $supplierId, $bizDT, $id);
				
				$log = "编辑采购入库单: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "采购入库");
				$db->commit();
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库操作错误，请联系管理员");
			}
		} else {
			$id = $idGen->newId();
			
			$db->startTrans();
			try {
				$sql = "insert into t_pw_bill (id, ref, supplier_id, warehouse_id, biz_dt, 
						biz_user_id, bill_status, date_created, goods_money, input_user_id) 
						values ('%s', '%s', '%s', '%s', '%s', '%s', 0, now(), 0, '%s')";
				
				$ref = $this->genNewBillRef();
				$us = new UserService();
				$db->execute($sql, $id, $ref, $supplierId, $warehouseId, $bizDT, $bizUserId, $us->getLoginUserId());
				
				// 明细记录
				$items = $bill["items"];
				foreach ( $items as $i => $item ) {
					$goodsId = $item["goodsId"];
					$goodsCount = floatval($item["goodsCount"]);
					$goodsType = $item["goodsType"];
					if ($goodsId != null && $goodsCount != 0) {
						// 检查商品是否存在
						$sql = "select count(*) as cnt from t_goods where id = '%s' ";
						$data = $db->query($sql, $goodsId);
						$cnt = $data[0]["cnt"];
						if ($cnt == 1) {
							
							$goodsPrice = floatval($item["goodsPrice"]);
							$goodsMoney = $goodsCount * $goodsPrice;
							
							$sql = "insert into t_pw_bill_detail 
									(id, date_created, goods_id, goods_count, goods_price,
									goods_money,  pwbill_id, show_order, goods_type)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, $goodsType);
						}
					}
				}
				
				$sql = "select sum(goods_money) as goods_money from t_pw_bill_detail 
						where pwbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$totalMoney = $data[0]["goods_money"];
				$sql = "update t_pw_bill
						set goods_money = %f 
						where id = '%s' ";
				$db->execute($sql, $totalMoney, $id);
				
				$log = "新建采购入库单: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "采购入库");
				
				$db->commit();
			} catch ( Exception $exc ) {
				$db->rollback();
				
				return $this->bad("数据库操作错误，请联系管理员");
			}
		}
		
		return $this->ok($id);
	}

	private function genNewBillRef() {
		$pre = "PW";
		$mid = date("Ymd");
		
		$sql = "select ref from t_pw_bill where ref like '%s' order by ref desc limit 1";
		$data = M()->query($sql, $pre . $mid . "%");
		$suf = "0001";
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, 10)) + 1;
			$suf = str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	public function pwBillInfo($id, $ia = false) {
		$result["id"] = $id;
		
		$db = M();
		$sql = "select p.ref, p.bill_status, p.supplier_id, s.name as supplier_name, p.goods_money, 
				p.warehouse_id, w.name as  warehouse_name, 
				p.biz_user_id, u.name as biz_user_name, p.biz_dt 
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u 
				where p.id = '%s' and p.supplier_id = s.id and p.warehouse_id = w.id 
				  and p.biz_user_id = u.id";
		$data = $db->query($sql, $id);
		if(!$data){
			$sql = "select p.id, p.ref, p.bill_status, p.supplier_id, s.name as supplier_name, p.goods_money, 
				p.warehouse_id, w.name as  warehouse_name, 
				p.biz_user_id, u.name as biz_user_name, p.biz_dt 
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u 
				where p.ref = '%s' and p.supplier_id = s.id and p.warehouse_id = w.id 
				  and p.biz_user_id = u.id";
			$data = $db->query($sql, $id);
			$id   = $data[0]["id"];
		}
		if ($data) {
			$v = $data[0];
			$result["ref"] = $v["ref"];
			$result["billStatus"] = $v["bill_status"];
			$result["supplierId"] = $v["supplier_id"];
			$result["supplierName"] = $v["supplier_name"];
			$result["warehouseId"] = $v["warehouse_id"];
			$result["warehouseName"] = $v["warehouse_name"];
			$result["bizUserId"] = $v["biz_user_id"];
			$result["bizUserName"] = $v["biz_user_name"];
			$result["goodsMoney"] = $v["goods_money"];
			$result["bizDT"] = date("Y-m-d", strtotime($v["biz_dt"]));
			
			$items = array();
			$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, 
					p.goods_count, p.goods_price, p.goods_money, p.goods_type 
					from t_pw_bill_detail p, t_goods g, t_goods_unit u 
					where p.goods_Id = g.id and g.unit_id = u.id and p.pwbill_id = '%s' 
					order by p.show_order";
			$data = $db->query($sql, $id);
			$no = 1;
			foreach ( $data as $i => $v ) {
				$goods_count = $v["goods_count"];
				//如果是新建验收单的时候采购单信息，则总数量需要去除已采购的数量
				if($ia == true){
					$sql = "select sum(goods_count) as s_count from t_ia_bill_detail where goods_id = '".$v["goods_id"]."' and iabill_id in (select id from t_ia_bill where pw_billid = '$id')";
					$detail = $db->query($sql);
					$detail_count = $detail[0]["s_count"] ? $detail[0]["s_count"] : 0;
					$goods_count = $goods_count - $detail_count > 0 ? $goods_count - $detail_count : 0;
				}
				$items[$i]["no"] = $no;
				$items[$i]["id"] = $v["id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$items[$i]["goodsCode"] = $v["code"];
				$items[$i]["goodsName"] = $v["name"];
				$items[$i]["goodsSpec"] = $v["spec"];
				$items[$i]["unitName"] = $v["unit_name"];
				if($v["bulk"] == 0){
					$items[$i]["unitName"] = "kg";
				}
				$items[$i]["goodsCount"] = $goods_count;
				$items[$i]["goodsPrice"] = $v["goods_price"];
				$items[$i]["goodsMoney"] = $v["goods_money"];
				$items[$i]["goodsType"] = $v["goods_type"];
				$items[$i]["_goodsCount"] = $goods_count;
				$items[$i]["_goodsPrice"] = $v["goods_price"];
				$no++;
			}
			
			$result["items"] = $items;
		} else {
			// 新建采购入库单
			$us = new UserService();
			$result["bizUserId"] = $us->getLoginUserId();
			$result["bizUserName"] = $us->getLoginUserName();
			
			$ts = new BizConfigService();
			if ($ts->warehouseUsesOrg()) {
				$ws = new WarehouseService();
				$data = $ws->getWarehouseListForLoginUser("2001");
				if (count($data) > 0) {
					$result["warehouseId"] = $data[0]["id"];
					$result["warehouseName"] = $data[0]["name"];
				}
			} else {
				$sql = "select value from t_config where id = '2001-01' ";
				$data = $db->query($sql);
				if ($data) {
					$warehouseId = $data[0]["value"];
					$sql = "select id, name from t_warehouse where id = '%s' ";
					$data = $db->query($sql, $warehouseId);
					if ($data) {
						$result["warehouseId"] = $data[0]["id"];
						$result["warehouseName"] = $data[0]["name"];
					}
				}
			}
		}
		
		return $result;
	}

	public function deletePWBill($id) {
		$db = M();
		$sql = "select ref, bill_status from t_pw_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要废弃的采购单不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus != 0 || $billStatus != 2) {
			return $this->bad("当前采购单已经提交审核或入库，不能删除");
		}
		
		$db->startTrans();
		try {
			//$sql = "update from t_pw_bill_detail set where pwbill_id = '%s' ";
			//$db->execute($sql, $id);
			
			$sql = "update t_pw_bill set bill_status = 9999  where id = '%s' ";
			$db->execute($sql, $id);
			
			$log = "废弃采购入库单: 单号 = {$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "采购入库");
			
			$db->commit();
		} catch ( Exception $exc ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok();
	}

	public function verifyPWBill($id){
		$db = M();
		$sql = "select ref, warehouse_id, bill_status, biz_dt, biz_user_id,  goods_money, supplier_id 
				from t_pw_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要审核的采购单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(2);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("采购单不是被审核状态，无法审核.");
		}
		//这里修改为仅改变状态，不改变库存，库存修改移动到验收单的提交里
		try{
			$us = new UserService();
			$map = array(
				"id" => $id
			);
			$data = array(
				"bill_status" => 1,
				"verify_user_id" => $us->getLoginUserId()
			);
			M("pw_bill", "t_")->where($map)->save($data);
			return $this->ok($id);
		} catch( Exception $exc ){
			return $this->bad("数据库错误，请联系管理员");
		}
	}

	public function rejectPWBill($id){
		$db = M();
		$sql = "select ref, warehouse_id, bill_status, biz_dt, biz_user_id,  goods_money, supplier_id 
				from t_pw_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要驳回的采购单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(2);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("采购单不是被审核状态，无法驳回.");
		}
		//这里修改为仅改变状态，不改变库存，库存修改移动到验收单的提交里
		try{
			$us = new UserService();
			$map = array(
				"id" => $id
			);
			$data = array(
				"bill_status" => 3,
				"verify_user_id" => $us->getLoginUserId()
			);
			M("pw_bill", "t_")->where($map)->save($data);
			return $this->ok($id);
		} catch( Exception $exc ){
			return $this->bad("数据库错误，请联系管理员");
		}
	}

	public function finishPWBill($id){
		$db = M();
		$sql = "select ref, warehouse_id, bill_status, biz_dt, biz_user_id,  goods_money, supplier_id 
				from t_pw_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要完成的采购单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(1);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("采购单不是审核通过状态，无法完成.");
		}
		//这里修改为仅改变状态，不改变库存，库存修改移动到验收单的提交里
		try{
			$us = new UserService();
			$map = array(
				"id" => $id
			);
			$data = array(
				"bill_status" => 4,
				"verify_user_id" => $us->getLoginUserId()
			);
			M("pw_bill", "t_")->where($map)->save($data);
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
		$sql = "select p.id, p.biz_dt, p.goods_money, p.ref, p.supplier_id, s.code as supplier_code, p.warehouse_id, s.name as supplier, w.name as warehouse
				from t_pw_bill p, t_supplier s, t_warehouse w
				where (p.supplier_id = s.id and p.warehouse_id = w.id) $date_sql $bill_status_sql
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

	public function commitPWBill($id) {
		$db = M();
		$sql = "select ref, warehouse_id, bill_status, biz_dt, biz_user_id,  goods_money, supplier_id 
				from t_pw_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要提交的采购单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(0,3);
		if (!in_array($billStatus, $yes_status_arr)) {
			return $this->bad("采购单已经提交或已经入库，不能再次提交");
		}

		$ref = $data[0]["ref"];
		$bizDT = $data[0]["biz_dt"];
		$bizUserId = $data[0]["biz_user_id"];
		$billPayables = floatval($data[0]["goods_money"]);
		$supplierId = $data[0]["supplier_id"];
		$warehouseId = $data[0]["warehouse_id"];
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
				from t_pw_bill_detail 
				where pwbill_id = '%s' order by show_order";
		$items = $db->query($sql, $id);
		if (! $items) {
			return $this->bad("采购单没有采购明细记录，不能提交");
		}
		
		// 检查入库数量和单价不能为负数
		foreach ( $items as $v ) {
			$goodsCount = floatval($v["goods_count"]);
			if ($goodsCount <= 0) {
				return $this->bad("采购数量不能小于0");
			}
			$goodsPrice = floatval($v["goods_price"]);
			if ($goodsPrice < 0) {
				return $this->bad("采购单价不能为负数");
			}
		}
		//这里修改为仅改变状态，不改变库存，库存修改移动到验收单的提交里
		try{
			$map = array(
				"id" => $id
			);
			$us = new UserService();
			
			$data = array(
				"bill_status" => 2
			);
			M("pw_bill", "t_")->where($map)->save($data);
			return $this->ok($id);
		} catch( Exception $exc ){
			return $this->bad("数据库错误，请联系管理员");
		}
		$db->startTrans();
		try {
			foreach ( $items as $v ) {
				$goodsCount = floatval($v["goods_count"]);
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
						values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '采购入库')";
				$db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref);
			}
			
			$sql = "update t_pw_bill set bill_status = 1000 where id = '%s' ";
			$db->execute($sql, $id);
			
			// 应付明细账
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
			
			// 日志
			$log = "提交采购入库: 单号 = {$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "采购入库");
			
			$db->commit();
		} catch ( Exception $exc ) {
			$db->rollback();
			return $this->bad("数据库操作错误，请重试或者联系管理员");
		}
		
		return $this->ok($id);
	}
}