<?php

namespace Home\Service;

/**
 * 库间调拨Service
 *
 * @author 李静波
 */
class ITBillService extends ERPBaseService {

	/**
	 * 生成新的调拨单单号
	 *
	 * @return string
	 */
	private function genNewBillRef() {
		$pre = "IT";
		$mid = date("Ymd");
		
		$sql = "select ref from t_it_bill where ref like '%s' order by ref desc limit 1";
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
	 * 生成新的损溢单单号
	 *
	 * @return string
	 */
	private function genNewILBillRef() {
		$pre = "IL";
		$mid = date("Ymd");
		
		$sql = "select ref from t_il_bill where ref like '%s' order by ref desc limit 1";
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
	 * 调拨单主表列表信息
	 */
	public function itbillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		
		$sql = "select t.id, t.ref, t.bizdt, t.bill_status,
					fw.name as from_warehouse_name,
					tw.name as to_warehouse_name,
					u.name as biz_user_name,
					u1.name as input_user_name
				from t_it_bill t, t_warehouse fw, t_warehouse tw,
				   t_user u, t_user u1
				where t.from_warehouse_id = fw.id 
				  and t.to_warehouse_id = tw.id
				  and t.biz_user_id = u.id
				  and t.input_user_id = u1.id
				order by t.ref desc
				limit $start , $limit
				";
		$data = $db->query($sql);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["billStatus"] = $v["bill_status"] == 0 ? "待调拨" : "已调拨";
			$result[$i]["fromWarehouseName"] = $v["from_warehouse_name"];
			$result[$i]["toWarehouseName"] = $v["to_warehouse_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
		}
		
		$sql = "select count(*) as cnt
				from t_it_bill t, t_warehouse fw, t_warehouse tw,
				   t_user u, t_user u1
				where t.from_warehouse_id = fw.id 
				  and t.to_warehouse_id = tw.id
				  and t.biz_user_id = u.id
				  and t.input_user_id = u1.id
				";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}
	/**
	 * 损溢单主表列表信息
	 */
	public function InvLossList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$ref   = $params["ref"];
		$begindate = $params["begindate"];
		$enddate = $params["enddate"];
		$status = $params["bill_status"];
		$status_sql = " and t.bill_status in (0,1000) ";
		$db = M();
		$query_sql = "";
		if($ref){
			$query_sql .= " and t.ref like '%$ref%' ";
		}
		if($begindate){
			$query_sql .= " and t.bizdt >= '$begindate' ";
		}
		if($enddate){
			$query_sql .= " and t.bizdt <= '$enddate' ";
		}
		$sql = "select t.id, t.ref, t.bizdt, t.bill_status, t.remark, 
					fw.name as from_warehouse_name,
					u.name as biz_user_name,
					u1.name as input_user_name
				from t_il_bill t, t_warehouse fw,
				   t_user u, t_user u1
				where t.from_warehouse_id = fw.id and t.biz_user_id = u.id and t.input_user_id = u1.id $status_sql $query_sql 
				order by t.date_created desc
				limit $start , $limit
				";//echo $sql;exit;
		$data = $db->query($sql);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["billStatus"] = $v["bill_status"] == 0 ? "待提交" : "已完成";
			$result[$i]["fromWarehouseName"] = $v["from_warehouse_name"];
			$result[$i]["toWarehouseName"] = $v["to_warehouse_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["remark"] = $v["remark"];
		}
		
		$sql = "select count(*) as cnt
				from t_il_bill t, t_warehouse fw, t_warehouse tw,
				   t_user u, t_user u1
				where t.from_warehouse_id = fw.id 
				  and t.biz_user_id = u.id
				  and t.input_user_id = u1.id $status_sql $query_sql 
				";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}
  //损益单提交
	public function editInvLossBill($params) {
		$json = $params["jsonStr"];
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		$db = M();
		
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$fromWarehouseId = $bill["fromWarehouseId"];
		$sql = "select name from t_warehouse where id = '%s' ";
		$remark = $bill["remark"];
		$data = $db->query($sql, $fromWarehouseId);
		if (! $data) {
			return $this->bad("请选择仓库");
		}
		
		$us = new UserService();
		$bizUserId = $us->getLoginUserId();
		$sql = "select name from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		if (! $data) {
			return $this->bad("业务人员不存在，无法保存");
		}
		
		$items = $bill["items"];
		$items_info = '';
		foreach ($items as $k => $v) {
		 	 if(!empty($v['goodsId'])){
		 	 	
			 	 if(empty($v['goodsCount'])){
					 return $this->bad("请填写损溢数量");
			 	 }
			 	 if($v['bill_type'] == '损'){
			 	 	$items[$k]['goodsCount'] =  - abs($v['goodsCount']);
			 	 	$items[$k]['goodsMoney'] =  - abs($v['goodsMoney']);
			 	 	
			 	 }
			 	 if(empty($v['bill_type'])){
					 return $this->bad("请填写损溢类型");
			 	 }
		 	 	$items_info = 1;	break;
		 	 }
		} 
		if (!$items_info) {
			return $this->bad("没有详细内容，无法保存");
		}	
			
		$idGen = new IdGenService();
		
		if ($id) {
			// 编辑
			$sql = "select ref, bill_status from t_il_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $this->bad("要编辑的损溢单不存在");
			}
			$ref = $data[0]["ref"];
			$billStatus = $data[0]["bill_status"];
			if ($billStatus != 0) {
				return $this->bad("损溢单(单号：$ref)已经提交，不能被编辑");
			}
			
			$db->startTrans();
			try {
				$sql = "update t_il_bill
						set bizdt = '%s', biz_user_id = '%s', date_created = now(),
						    input_user_id = '%s', from_warehouse_id = '%s', to_warehouse_id = '%s', remark = '%s' 
						where id = '%s' ";
				
				$db->execute($sql, $bizDT, $us->getLoginUserId(), $us->getLoginUserId(), $fromWarehouseId, 
						$toWarehouseId, $remark , $id);
				
				$sql = "delete from t_il_bill_detail where itbill_id = '%s' ";
				$db->execute($sql, $id);
				
				$sql = "insert into t_il_bill_detail(id, date_created, goods_id, goods_count, show_order, itbill_id, goods_price, goods_money)
						values ('%s', now(), '%s', %f, %d, '%s', '%f', '%f')";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					if (! $goodsId) {
						continue;
					}
					if($v['bill_type'] == '损'){
				 		$goodsCount =  - abs($v['goodsCount']);
				 	} else {
				 		$goodsCount =  abs($v['goodsCount']);
				 	}
					$goodsPrice = $v["goodsPrice"];
					$goodsMoney = $v["goodsMoney"];
					if(!$goodsPrice){
						$goods_info = $this->base_get_goods_info($goodsId);
						$goodsPrice = $goods_info["lastbuyprice"];
						$goodsMoney = $goodsPrice * $goodsCount;
					}
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $i, $id, $goodsPrice, $goodsMoney);
				}
				
				$bs = new BizlogService();
				$log = "编辑损溢单，单号：$ref";
				$bs->insertBizlog($log, "库存损溢");
				
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系系统管理员");
			}
		} else {
			// 新增
			$db->startTrans();
			try {
				$sql = "insert into t_il_bill(id, bill_status, bizdt, biz_user_id,
						date_created, input_user_id, ref, from_warehouse_id, to_warehouse_id, remark)
						values ('%s', 0, '%s', '%s', now(), '%s', '%s', '%s', '%s', '%s')";
				$id = $idGen->newId();
				$ref = $this->genNewILBillRef();
				
				$db->execute($sql, $id, $bizDT, $us->getLoginUserId(), $us->getLoginUserId(), $ref, 
						$fromWarehouseId, $toWarehouseId, $remark);
				$sql = "insert into t_il_bill_detail(id, date_created, goods_id, goods_count, goods_price, goods_money, show_order, itbill_id)
						values ('%s', now(), '%s', %f, '%f', '%f', %d, '%s')";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					if (! $goodsId) {
						continue;
					}
					if($v['bill_type'] == '损'){
				 		$goodsCount =  - abs($v['goodsCount']);
				 	} else {
				 		$goodsCount =  abs($v['goodsCount']);
				 	}
					$goodsPrice = $v["goodsPrice"];
					$goodsMoney = $v["goodsMoney"];
					if(!$goodsPrice){
						$goods_info = $this->base_get_goods_info($goodsId);
						$goodsPrice = $goods_info["lastbuyprice"];
						$goodsMoney = $goodsPrice * $goodsCount;
					}
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $i, $id);
				}
				
				$bs = new BizlogService();
				$log = "新建损溢单，单号：$ref";
				$bs->insertBizlog($log, "库存损溢");
				
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系系统管理员");
			}
		}
		
		return $this->ok($id);
	}

	public function editITBill($params) {
		$json = $params["jsonStr"];
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = M();
		
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$fromWarehouseId = $bill["fromWarehouseId"];
		$sql = "select name from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $fromWarehouseId);
		if (! $data) {
			return $this->bad("调出仓库不存在，无法保存");
		}
		
		$toWarehouseId = $bill["toWarehouseId"];
		$sql = "select name from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $toWarehouseId);
		if (! $data) {
			return $this->bad("调入仓库不存在，无法保存");
		}
		
		$bizUserId = $bill["bizUserId"];
		$sql = "select name from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		if (! $data) {
			return $this->bad("业务人员不存在，无法保存");
		}
		
		if ($fromWarehouseId == $toWarehouseId) {
			return $this->bad("调出仓库和调入仓库不能是同一个仓库");
		}
		
		$items = $bill["items"];
		
		$idGen = new IdGenService();
		$us = new UserService();
		
		if ($id) {
			// 编辑
			$sql = "select ref, bill_status from t_it_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $this->bad("要编辑的调拨单不存在");
			}
			$ref = $data[0]["ref"];
			$billStatus = $data[0]["bill_status"];
			if ($billStatus != 0) {
				return $this->bad("调拨单(单号：$ref)已经提交，不能被编辑");
			}
			
			$db->startTrans();
			try {
				$sql = "update t_it_bill
						set bizdt = '%s', biz_user_id = '%s', date_created = now(),
						    input_user_id = '%s', from_warehouse_id = '%s', to_warehouse_id = '%s'
						where id = '%s' ";
				
				$db->execute($sql, $bizDT, $bizUserId, $us->getLoginUserId(), $fromWarehouseId, 
						$toWarehouseId, $id);
				
				$sql = "delete from t_it_bill_detail where itbill_id = '%s' ";
				$db->execute($sql, $id);
				
				$sql = "insert into t_it_bill_detail(id, date_created, goods_id, goods_count, show_order, itbill_id)
						values ('%s', now(), '%s', %d, %d, '%s')";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					if (! $goodsId) {
						continue;
					}
					
					$goodsCount = $v["goodsCount"];
					
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $i, $id);
				}
				
				$bs = new BizlogService();
				$log = "编辑调拨单，单号：$ref";
				$bs->insertBizlog($log, "库间调拨");
				
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系系统管理员");
			}
		} else {
			// 新增
			$db->startTrans();
			try {
				$sql = "insert into t_it_bill(id, bill_status, bizdt, biz_user_id,
						date_created, input_user_id, ref, from_warehouse_id, to_warehouse_id)
						values ('%s', 0, '%s', '%s', now(), '%s', '%s', '%s', '%s')";
				$id = $idGen->newId();
				$ref = $this->genNewBillRef();
				
				$db->execute($sql, $id, $bizDT, $bizUserId, $us->getLoginUserId(), $ref, 
						$fromWarehouseId, $toWarehouseId);
				
				$sql = "insert into t_it_bill_detail(id, date_created, goods_id, goods_count, show_order, itbill_id)
						values ('%s', now(), '%s', %d, %d, '%s')";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					if (! $goodsId) {
						continue;
					}
					
					$goodsCount = $v["goodsCount"];
					
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $i, $id);
				}
				
				$bs = new BizlogService();
				$log = "新建调拨单，单号：$ref";
				$bs->insertBizlog($log, "库间调拨");
				
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系系统管理员");
			}
		}
		
		return $this->ok($id);
	}

	public function itBillInfo($params) {
		$id = $params["id"];
		
		$result = array();
		
		if ($id) {
			// 编辑
			$db = M();
			$sql = "select t.ref, t.bill_status, t.bizdt, t.biz_user_id, u.name as biz_user_name,
						wf.id as from_warehouse_id, wf.name as from_warehouse_name,
						wt.id as to_warehouse_id, wt.name as to_warehouse_name
					from t_it_bill t, t_user u, t_warehouse wf, t_warehouse wt
					where t.id = '%s' and t.biz_user_id = u.id
					      and t.from_warehouse_id = wf.id
					      and t.to_warehouse_id = wt.id";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $result;
			}
			
			$result["bizUserId"] = $data[0]["biz_user_id"];
			$result["bizUserName"] = $data[0]["biz_user_name"];
			$result["ref"] = $data[0]["ref"];
			$result["billStatus"] = $data[0]["bill_status"];
			$result["bizDT"] = date("Y-m-d", strtotime($data[0]["bizdt"]));
			$result["fromWarehouseId"] = $data[0]["from_warehouse_id"];
			$result["fromWarehouseName"] = $data[0]["from_warehouse_name"];
			$result["toWarehouseId"] = $data[0]["to_warehouse_id"];
			$result["toWarehouseName"] = $data[0]["to_warehouse_name"];
			
			$items = array();
			$sql = "select t.id, g.id as goods_id, g.lastbuyprice, g.code, g.name, g.spec, u.name as unit_name, t.goods_count 
				from t_it_bill_detail t, t_goods g, t_goods_unit u
				where t.itbill_id = '%s' and t.goods_id = g.id and g.unit_id = u.id
				order by t.show_order ";
			
			$data = $db->query($sql, $id);
			foreach ( $data as $i => $v ) {
				$items[$i]["id"] = $v["id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$items[$i]["goodsCode"] = $v["code"];
				$items[$i]["goodsName"] = $v["name"];
				$items[$i]["goodsSpec"] = $v["spec"];
				$items[$i]["unitName"] = $v["unit_name"];
				$items[$i]["goodsCount"] = $v["goods_count"];
			}
			
			$result["items"] = $items;
		} else {
			// 新建
			$us = new UserService();
			$result["bizUserId"] = $us->getLoginUserId();
			$result["bizUserName"] = $us->getLoginUserName();
		}
		
		return $result;
	}

	public function InvLossBillInfo($params) {
		$id = $params["id"];
		
		$result = array();
		
		if ($id) {
			// 编辑
			$db = M();
			$sql = "select t.ref, t.bill_status, t.bizdt, t.biz_user_id, t.remark, u.name as biz_user_name,
						wf.id as from_warehouse_id, wf.name as from_warehouse_name,
						wt.id as to_warehouse_id, wt.name as to_warehouse_name
					from t_il_bill t, t_user u, t_warehouse wf, t_warehouse wt
					where t.id = '%s'
					      and t.from_warehouse_id = wf.id";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $result;
			}
			
			$result["bizUserId"] = $data[0]["biz_user_id"];
			$result["bizUserName"] = $data[0]["biz_user_name"];
			$result["ref"] = $data[0]["ref"];
			$result["billStatus"] = $data[0]["bill_status"];
			$result["bizDT"] = date("Y-m-d", strtotime($data[0]["bizdt"]));
			$result["fromWarehouseId"] = $data[0]["from_warehouse_id"];
			$result["fromWarehouseName"] = $data[0]["from_warehouse_name"];
			$result["toWarehouseId"] = $data[0]["to_warehouse_id"];
			$result["toWarehouseName"] = $data[0]["to_warehouse_name"];
			$result["remark"] = $data[0]["remark"];
			
			$items = array();
			$sql = "select t.id, g.id as goods_id, g.code, g.lastbuyprice, g.name, g.spec, g.bulk, u.name as unit_name, t.goods_count,t.goods_price,t.goods_money 
				from t_il_bill_detail t, t_goods g, t_goods_unit u
				where t.itbill_id = '%s' and t.goods_id = g.id and g.unit_id = u.id
				order by t.show_order ";
			
			$data = $db->query($sql, $id);
			foreach ( $data as $i => $v ) {
				$items[$i]["id"] = $v["id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$items[$i]["goodsCode"] = $v["code"];
				$items[$i]["goodsName"] = $v["name"];
				$items[$i]["goodsSpec"] = $v["spec"];
				$items[$i]["unitName"] = $v["unit_name"];
				if($v["goods_count"] < 0){
					$items[$i]["bill_type"] = "损";
				} else {
					$items[$i]["bill_type"] = "溢";
				}
				if($v["goods_price"] == 0){
					$items[$i]["goodsMoney"] = abs($v["lastbuyprice"] * $v["goods_count"]);
					$items[$i]["goodsPrice"] = $v["lastbuyprice"];
				}
				$items[$i]["goodsCount"] = $v["goods_count"];
				$items[$i]["goodsMoney"] = $v["goods_money"];
				$items[$i]["goodsPrice"] = $v["goods_price"];

				if($v["bulk"] == 0){
					$items[$i]["unitName"] = "kg";
				}

			}
			
			$result["items"] = $items;
		} else {
			// 新建
			$us = new UserService();
			$result["bizUserId"] = $us->getLoginUserId();
			$result["bizUserName"] = $us->getLoginUserName();
		}
		
		return $result;
	}
	public function InvLossDetailList($params) {
		$id = $params["id"];
		
		$result = array();
		
		$db = M();
		$sql = "select t.id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, t.goods_count ,t.goods_price,t.goods_money
				from t_il_bill_detail t, t_goods g, t_goods_unit u
				where t.itbill_id = '%s' and t.goods_id = g.id and g.unit_id = u.id
				order by t.show_order ";
		
		$data = $db->query($sql, $id);
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsCount"] = $v["goods_count"];
			$result[$i]["goodsMoney"] = $v["goods_money"];
			$result[$i]["goodsPrice"] = $v["goods_price"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
		}
		
		return $result;
	}
	public function itBillDetailList($params) {
		$id = $params["id"];
		
		$result = array();
		
		$db = M();
		$sql = "select t.id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, t.goods_count 
				from t_it_bill_detail t, t_goods g, t_goods_unit u
				where t.itbill_id = '%s' and t.goods_id = g.id and g.unit_id = u.id
				order by t.show_order ";
		
		$data = $db->query($sql, $id);
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
			$result[$i]["goodsCount"] = $v["goods_count"];
		}
		
		return $result;
	}

	public function deleteITBill($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select ref, bill_status from t_il_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要删除的损溢单不存在");
		}
		
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		
		if ($billStatus != 0) {
			return $this->bad("损溢单(单号：$ref)已经提交，不能被删除");
		}
		
		$db->startTrans();
		try {
			$sql = "delete from t_il_bill_detail where itbill_id = '%s' ";
			$db->execute($sql, $id);
			
			$sql = "delete from t_il_bill where id = '%s' ";
			$db->execute($sql, $id);
			
			$bs = new BizlogService();
			$log = "删除损溢单，单号：$ref";
			$bs->insertBizlog($log, "损溢调整");
			
			$db->commit();
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系系统管理员");
		}
		
		return $this->ok();
	}

	public function DeleteITBills($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select ref, bill_status from t_it_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要删除的调拨单不存在");
		}
		
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		
		if ($billStatus != 0) {
			return $this->bad("调拨单(单号：$ref)已经提交，不能被删除");
		}
		
		$db->startTrans();
		try {
			$sql = "delete from t_it_bill_detail where itbill_id = '%s' ";
			$db->execute($sql, $id);
			
			$sql = "delete from t_it_bill where id = '%s' ";
			$db->execute($sql, $id);
			
			$bs = new BizlogService();
			$log = "删除调拨单，单号：$ref";
			$bs->insertBizlog($log, "调拨调整");
			
			$db->commit();
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系系统管理员");
		}
		
		return $this->ok();
	}

	/**
	 * 提交调拨单
	 */
	public function commitITBill($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select ref, bill_status, from_warehouse_id, to_warehouse_id,
					bizdt, biz_user_id
				from t_it_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要提交的调拨单不存在，无法提交");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus != 0) {
			return $this->bad("调拨单(单号：$ref)已经提交，不能再次提交");
		}
		
		$bizUserId = $data[0]["biz_user_id"];
		$bizDT = date("Y-m-d", strtotime($data[0]["bizdt"]));
		
		$fromWarehouseId = $data[0]["from_warehouse_id"];
		$toWarehouseId = $data[0]["to_warehouse_id"];
		
		// 检查仓库是否存在，仓库是否已经完成建账
		$sql = "select name , inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $fromWarehouseId);
		if (! $data) {
			return $this->bad("调出仓库不存在，无法进行调拨操作");
		}
		$warehouseName = $data[0]["name"];
		$inited = $data[0]["inited"];
		if ($inited != 1) {
			return $this->bad("仓库：$warehouseName 还没有完成建账，无法进行调拨操作");
		}
		
		$sql = "select name , inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $toWarehouseId);
		if (! $data) {
			return $this->bad("调入仓库不存在，无法进行调拨操作");
		}
		$warehouseName = $data[0]["name"];
		$inited = $data[0]["inited"];
		if ($inited != 1) {
			return $this->bad("仓库：$warehouseName 还没有完成建账，无法进行调拨操作");
		}
		
		if ($fromWarehouseId == $toWarehouseId) {
			return $this->bad("调出仓库和调入仓库不能是同一个仓库");
		}
		
		$db->startTrans();
		try {
			$sql = "select goods_id, goods_count 
					from t_it_bill_detail 
					where itbill_id = '%s' 
					order by show_order";
			$items = $db->query($sql, $id);
			foreach ( $items as $i => $v ) {
				$goodsId = $v["goods_id"];
				$goodsCount = $v["goods_count"];
				// 检查商品Id是否存在
				$sql = "select code, name, spec from t_goods where id = '%s' ";
				$data = $db->query($sql, $goodsId);
				if (! $data) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条明细记录中的商品不存在，无法完成提交");
				}
				$goodsCode = $data[0]["code"];
				$goodsName = $data[0]["name"];
				$goodsSpec = $data[0]["spec"];
				
				// 检查调出数量是否为正数
				if ($goodsCount <= 0) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条明细记录中的调拨数量不是正数，无法完成提交");
				}
				
				// 检查调出库存是否足够
				$sql = "select balance_count, balance_price, balance_money, out_count, out_money 
						from t_inventory
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $fromWarehouseId, $goodsId);
				if (! $data) {
					$db->rollback();
					return $this->bad("商品[$goodsCode $goodsName $goodsSpec]库存不足，无法调拨");
				}
				$balanceCount = $data[0]["balance_count"];
				$balancePrice = $data[0]["balance_price"];
				$balanceMoney = $data[0]["balance_money"];
				if ($balanceCount < $goodsCount) {
					$db->rollback();
					return $this->bad("商品[$goodsCode $goodsName $goodsSpec]库存不足，无法调拨");
				}
				$totalOutCount = $data[0]["out_count"];
				$totalOutMoney = $data[0]["out_money"];
				
				// 调出库 - 明细账
				$outPrice = $balancePrice;
				$outCount = $goodsCount;
				$outMoney = $outCount * $outPrice;
				if ($outCount == $balanceCount) {
					// 全部出库，这个时候金额全部转移
					$outMoney = $balanceMoney;
					$balanceCount = 0;
					$balanceMoney = 0;
				} else {
					$balanceCount -= $outCount;
					$balanceMoney -= $outMoney;
				}
				$totalOutCount += $outCount;
				$totalOutMoney += $outMoney;
				$totalOutPrice = $totalOutMoney / $totalOutCount;
				$sql = "insert into t_inventory_detail(out_count, out_price, out_money, balance_count,
						balance_price, balance_money, warehouse_id, goods_id, biz_date, biz_user_id, date_created,
						ref_number, ref_type)
						values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(),
						'%s', '调拨出库')";
				$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, 
						$balancePrice, $balanceMoney, $fromWarehouseId, $goodsId, $bizDT, $bizUserId, $ref);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				// 调出库 - 总账
				$sql = "update t_inventory
						set out_count = %d, out_price = %f, out_money = %f,
							balance_count = %d, balance_price = %f, balance_money = %f
						where warehouse_id = '%s' and goods_id = '%s'";
				$rc = $db->execute($sql, $totalOutCount, $totalOutPrice, $totalOutMoney, 
						$balanceCount, $balancePrice, $balanceMoney, $fromWarehouseId, $goodsId);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				// 调入库 - 总账
				$inCount = $outCount;
				$inPrice = $outPrice;
				$inMoney = $outMoney;
				$balanceCount = 0;
				$balanceMoney = 0;
				$balancePrice = 0;
				$sql = "select balance_count, balance_money, in_count, in_money from
						t_inventory
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $toWarehouseId, $goodsId);
				if (! $data) {
					// 在总账中还没有记录
					$balanceCount = $inCount;
					$balanceMoney = $inMoney;
					$balancePrice = $inPrice;
					
					$sql = "insert into t_inventory(in_count, in_price, in_money, balance_count,
							balance_price, balance_money, warehouse_id, goods_id)
							values (%d, %f, %f, %d, %f, %f, '%s', '%s')";
					$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, 
							$balanceMoney, $toWarehouseId, $goodsId);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
					
					
				} else {
					$balanceCount = $data[0]["balance_count"];
					$balanceMoney = $data[0]["balance_money"];
					$totalInCount = $data[0]["in_count"];
					$totalInMoney = $data[0]["in_money"];
					
					$balanceCount += $inCount;
					$balanceMoney += $inMoney;
					$balancePrice = $balanceMoney / $balanceCount;
					$totalInCount += $inCount;
					$totalInMoney += $inMoney;
					$totalInPrice = $totalInMoney / $totalInCount;
					
					$sql = "update t_inventory
							set in_count = %d, in_price = %f, in_money = %f,
							    balance_count = %d, balance_price = %f, balance_money = %f
							where warehouse_id = '%s' and goods_id = '%s' ";
					$rc = $db->execute($sql, $totalInCount, $totalInPrice, $totalInMoney, 
							$balanceCount, $balancePrice, $balanceMoney, $toWarehouseId, $goodsId);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				}
				
				// 调入库 - 明细账
				$sql = "insert into t_inventory_detail(in_count, in_price, in_money, balance_count, 
						balance_price, balance_money, warehouse_id, goods_id, ref_number, ref_type,
						biz_date, biz_user_id, date_created)
						values (%d, %f, %f, %d, %f, %f, '%s', '%s', '%s', '调拨入库', '%s', '%s', now())";
				$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice,
						$balanceMoney, $toWarehouseId, $goodsId, $ref, $bizDT, $bizUserId);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
			}
			
			// 修改调拨单单据状态为已调拨
			$sql = "update t_it_bill
					set bill_status = 1000
					where id = '%s' ";
			$rc = $db->execute($sql, $id);
			if (! $rc) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
			
			// 记录业务日志
			$bs = new BizlogService();
			$log = "提交调拨单，单号: $ref";
			$bs->insertBizlog($log, "库间调拨");
			
			$db->commit();
			return $this->ok($id);
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系系统管理员");
		}
	}
	/**
	 * 提交损溢单
	 */
	public function commitInvLoss($params) {
		$id = $params["id"];

		$db = M();
		$sql = "select ref, bill_status, from_warehouse_id, to_warehouse_id,
					bizdt, biz_user_id
				from t_il_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要提交的损溢单不存在，无法提交");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		if ($billStatus != 0) {
			return $this->bad("损溢单(单号：$ref)已经提交，不能再次提交");
		}
		
		$bizUserId = $data[0]["biz_user_id"];
		$bizDT = date("Y-m-d", strtotime($data[0]["bizdt"]));
		
		$fromWarehouseId = $data[0]["from_warehouse_id"];
		$toWarehouseId = $data[0]["to_warehouse_id"];
		$toWarehouseId = $fromWarehouseId;
		// 检查仓库是否存在，仓库是否已经完成建账
		$sql = "select name , inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $fromWarehouseId);
		if (! $data) {
			return $this->bad("仓库不存在，无法进行损溢操作");
		}
		$warehouseName = $data[0]["name"];
		$inited = $data[0]["inited"];

		$goods_stock = array();
		$db->startTrans();
		try {
			$sql = "select goods_id, goods_count 
					from t_il_bill_detail 
					where itbill_id = '%s' 
					order by show_order";
			$items = $db->query($sql, $id);
			foreach ( $items as $i => $v ) {
				$goodsId = $v["goods_id"];
				$goodsCount = $v["goods_count"];
				// 检查商品Id是否存在
				$sql = "select code, name, spec, lastbuyprice from t_goods where id = '%s' ";
				$data = $db->query($sql, $goodsId);
				if (! $data) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条明细记录中的商品不存在，无法完成提交");
				}
				$goodsCode = $data[0]["code"];
				$goodsName = $data[0]["name"];
				$goodsSpec = $data[0]["spec"];
				$lastbuyprice = $data[0]["lastbuyprice"];
				// 检查数量是否为正数，判断是否损溢
				$action = "";
				if ($goodsCount < 0) {
					//$db->rollback();
					$action = "out";
					$index = $i + 1;
					//return $this->bad("第{$index}条明细记录中的调拨数量不是正数，无法完成提交");
				} else if ($goodsCount > 0){
					$action = "in";
				} else {
					continue;
				}
				$goods_one_stock = array(
					"goods_id" => $goodsId,
					"goods_code" => $goodsCode,
					"goods_number" => $goodsCount
				);
				$goods_stock[] = $goods_one_stock;
				// 检查调出库存是否足够
				$sql = "select balance_count, balance_price, balance_money, out_count, out_money 
						from t_inventory
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $fromWarehouseId, $goodsId);
				if (! $data) {
					$db->rollback();
					return $this->bad("商品[$goodsCode $goodsName $goodsSpec]不存在");
				}
				$balanceCount = $data[0]["balance_count"];
				$balancePrice = $lastbuyprice ? $lastbuyprice : $data[0]["balance_price"];
				$balanceMoney = $balancePrice * $balanceCount;
				if ($balanceCount < $goodsCount) {
					//$db->rollback();
					//return $this->bad("商品[$goodsCode $goodsName $goodsSpec]库存不足，无法损失");
				}
				$totalOutCount = $data[0]["out_count"];
				$totalOutMoney = $data[0]["out_money"];
				
				// 调出库 - 明细账
				$outPrice = $balancePrice;
				$outCount = abs($goodsCount);
				$outMoney = $outCount * $outPrice;
				if($action == "out"){

					if ($outCount == $balanceCount) {
						// 全部出库，这个时候金额全部转移
						$outMoney = $balanceMoney;
						$balanceCount = 0;
						$balanceMoney = 0;
					} else {
						$balanceCount -= $outCount;
						$balanceMoney -= $outMoney;
					}
					$totalOutCount += $outCount;
					$totalOutMoney += $outMoney;
					$totalOutPrice = $totalOutMoney / $totalOutCount;
					$sql = "insert into t_inventory_detail(out_count, out_price, out_money, balance_count,
							balance_price, balance_money, warehouse_id, goods_id, biz_date, biz_user_id, date_created,
							ref_number, ref_type)
							values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(),
							'%s', '库存损溢-损')";
					$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, 
							$balancePrice, $balanceMoney, $fromWarehouseId, $goodsId, $bizDT, $bizUserId, $ref);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
					
					// 调出库 - 总账
					$sql = "update t_inventory
							set out_count = %f, out_price = %f, out_money = %f,
								balance_count = %f, balance_price = %f, balance_money = %f
							where warehouse_id = '%s' and goods_id = '%s'";
					$rc = $db->execute($sql, $totalOutCount, $totalOutPrice, $totalOutMoney, 
							$balanceCount, $balancePrice, $balanceMoney, $fromWarehouseId, $goodsId);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				} else if ($action == "in"){
					// 调入库 - 总账
					$inCount = $outCount;
					$inPrice = $outPrice;
					$inMoney = $outMoney;
					$balanceCount = 0;
					$balanceMoney = 0;
					$balancePrice = 0;
					$sql = "select balance_count, balance_money, in_count, in_money from
							t_inventory
							where warehouse_id = '%s' and goods_id = '%s' ";
					$data = $db->query($sql, $toWarehouseId, $goodsId);
					if (! $data) {
						// 在总账中还没有记录
						$balanceCount = $inCount;
						$balanceMoney = $inMoney;
						$balancePrice = $inPrice;
						
						$sql = "insert into t_inventory(in_count, in_price, in_money, balance_count,
								balance_price, balance_money, warehouse_id, goods_id)
								values (%f, %f, %f, %f, %f, %f, '%s', '%s')";
						$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice, 
								$balanceMoney, $toWarehouseId, $goodsId);
						if (! $rc) {
							$db->rollback();
							return $this->sqlError();
						}
						
						
					} else {
						$balanceCount = $data[0]["balance_count"];
						$balanceMoney = $data[0]["balance_money"];
						$totalInCount = $data[0]["in_count"];
						$totalInMoney = $data[0]["in_money"];
						
						$balanceCount += $inCount;
						$balanceMoney += $inMoney;
						$balancePrice = $balanceMoney / $balanceCount;
						$totalInCount += $inCount;
						$totalInMoney += $inMoney;
						$totalInPrice = $totalInMoney / $totalInCount;
						
						$sql = "update t_inventory
								set in_count = %f, in_price = %f, in_money = %f,
								    balance_count = %f, balance_price = %f, balance_money = %f
								where warehouse_id = '%s' and goods_id = '%s' ";
						$rc = $db->execute($sql, $totalInCount, $totalInPrice, $totalInMoney, 
								$balanceCount, $balancePrice, $balanceMoney, $toWarehouseId, $goodsId);
						if (! $rc) {
							$db->rollback();
							return $this->sqlError();
						}
					}
					
					// 调入库 - 明细账
					$sql = "insert into t_inventory_detail(in_count, in_price, in_money, balance_count, 
							balance_price, balance_money, warehouse_id, goods_id, ref_number, ref_type,
							biz_date, biz_user_id, date_created)
							values (%f, %f, %f, %d, %f, %f, '%s', '%s', '%s', '库存损溢-溢', '%s', '%s', now())";
					$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, $balancePrice,
							$balanceMoney, $toWarehouseId, $goodsId, $ref, $bizDT, $bizUserId);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				}
				
			}
			
			// 修改损益单单据状态为已损溢
			$sql = "update t_il_bill
					set bill_status = 1000
					where id = '%s' ";
			$rc = $db->execute($sql, $id);
			if (! $rc) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
			
			// 记录业务日志
			$bs = new BizlogService();
			$log = "提交损益单，单号: $ref";
			$bs->insertBizlog($log, "库存损溢");
			
			$db->commit();
			//同步电商
			if($goods_stock){
				$ms = new MallService();
				$ms->syn_inventory($goods_stock, "手动损溢-同步");
			}
			return $this->ok($id);
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系系统管理员");
		}
	}


	//自动添加损溢单
	public function autoIlBill($params){
		$json = $params["jsonStr"];
		if($json){
			$bill = json_decode(html_entity_decode($json), true);
			if ($bill == null) {
				return $this->bad("传入的参数错误，不是正确的JSON格式");
			}
		} else {
			$bill = $params;
		}
		
		$db = M();
		
		$id = "";
		$bizDT = $bill["bizDT"];
		$fromWarehouseId = $bill["fromWarehouseId"];
		$sql = "select name from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $fromWarehouseId);
		if (! $data) {
			return $this->bad("请选择仓库");
		}
		
		$us = new UserService();
		$bizUserId = $us->getLoginUserId();
		$sql = "select name from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		if (! $data) {
			//return $this->bad("业务人员不存在，无法保存");
		}
		
		$items = $bill["items"];
		$items_info = '';
		foreach ($items as $k => $v) {
		 	 if(!empty($v['goodsId'])){
		 	 	
			 	 if(empty($v['goodsCount'])){
					 return $this->bad("请填写损溢数量");
			 	 }
			 	 
			 	 if($v['bill_type'] == '损'){
			 	 	$items[$k]['goodsCount'] = 0 - abs($v['goodsCount']);
			 	 }
			 	 if(empty($v['bill_type'])){
					 return $this->bad("请填写损溢类型");
			 	 }
		 	 	$items_info = 1;	break;
		 	 }
		} 
		if (!$items_info) {
			return $this->bad("没有详细内容，无法保存");
		}	
			
		$idGen = new IdGenService();
		
		if ($id) {
			
		} else {
			// 新增
			//$db->startTrans();
			try {
				$sql = "insert into t_il_bill(id, bill_status, bizdt, biz_user_id,
						date_created, input_user_id, ref, from_warehouse_id, to_warehouse_id)
						values ('%s', 0, '%s', '%s', now(), '%s', '%s', '%s', '%s')";
				$id = $idGen->newId();
				$ref = $this->genNewBillRef();
				
				$db->execute($sql, $id, $bizDT, $us->getLoginUserId(), $us->getLoginUserId(), $ref, 
						$fromWarehouseId, $toWarehouseId);
				$sql = "insert into t_il_bill_detail(id, date_created, goods_id, goods_count, goods_price, goods_money, show_order, itbill_id)
						values ('%s', now(), '%s', %f, %f, %f, %d, '%s')";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					$goods_info = $this->base_get_goods_info($goodsId);
					if (! $goodsId) {
						continue;
					}
					if($v['bill_type'] == '损'){
				 		$goodsCount =  - abs($v['goodsCount']);
				 	} else {
				 		$goodsCount =  abs($v['goodsCount']);
				 	}
					$goodsMoney = $goods_info["lastbuyprice"] * $goodsCount;
					$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount,$goods_info["lastbuyprice"],$goodsMoney, $i, $id);
				}
				
				$bs = new BizlogService();
				$log = "新建损溢单，单号：$ref";
				$bs->insertBizlog($log, "库存损溢");
				
				//$db->commit();
			} catch ( Exception $ex ) {
				//$db->rollback();
				return $this->bad("数据库错误，请联系系统管理员");
			}
		}
		//提交损溢单
		//$ret = $this->commitInvLoss(array("id"=>$id));
		$ret["success"] = true;
		if($ret["success"] == false){
			return $this->bad($ret["msg"]);
		} else {
			return $this->ok($id);
		}
		
	}

	/**
	 * 库存采购单列表信息
	 */
	public function PurchaseList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		
		$sql = "select t.id, t.ref, t.bizdt, t.bill_status,
					fw.name as from_warehouse_name,
					tw.name as to_warehouse_name,
					u.name as biz_user_name,
					u1.name as input_user_name
				from t_il_bill t, t_warehouse fw, t_warehouse tw,
				   t_user u, t_user u1
				where t.from_warehouse_id = fw.id 
				order by t.ref desc
				limit $start , $limit
				";//echo $sql;exit;
		$data = $db->query($sql);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["billStatus"] = $v["bill_status"] == 0 ? "待提交" : "已完成";
			$result[$i]["fromWarehouseName"] = $v["from_warehouse_name"];
			$result[$i]["toWarehouseName"] = $v["to_warehouse_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
		}
		
		$sql = "select count(*) as cnt
				from t_il_bill t, t_warehouse fw, t_warehouse tw,
				   t_user u, t_user u1
				where t.from_warehouse_id = fw.id 
				  and t.to_warehouse_id = tw.id
				  and t.biz_user_id = u.id
				  and t.input_user_id = u1.id
				";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}
    
    public function uplode() {
      $filename = $_FILES['file']['tmp_name'];
      if (empty ($filename)) {
          echo '请选择要导入的CSV文件！';
          exit;
      }
      $handle = fopen($filename, 'r');
      $result = $this->input_csv($handle); //解析csv
      $len_result = count($result);
      if($len_result==0){
          echo '没有任何数据！';
          exit;
      }
      return array('list'=>$result,'count'=>$len_result);
    }
    public function input_csv($handle) {
        $out = array ();
        $n = 0;
        while ($data = fgetcsv($handle, 10000)) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++) {
                $out[$n][$i] = $data[$i];
            }
            $n++;
        }
        return $out;
    }
}