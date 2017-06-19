<?php

namespace Home\Service;

/**
 * 采购退货出库单Service
 *
 * @author dubin
 */
class PRBillService extends ERPBaseService {

	/**
	 * 生成新的采购退货出库单单号
	 *
	 * @return string
	 */
	private function genNewBillRef() {
		$pre = "PR";
		$mid = date("Ymd");
		
		$sql = "select ref from t_pr_bill where ref like '%s' order by ref desc limit 1";
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

	public function prBillInfo($params) {
		$id = $params["id"];
		
		$result = array();
		
		if ($id) {
			// 编辑
			$db = M();
			$sql = "select p.ref, p.bill_status, p.warehouse_id, w.name as warehouse_name,
						p.biz_user_id, u.name as biz_user_name, ia.ref as pwbill_ref,
						s.name as supplier_name, s.id as supplier_id,
						p.pw_bill_id as pwbill_id, p.bizdt
					from t_pr_bill p, t_warehouse w, t_user u, t_ia_bill ia, t_supplier s
					where p.id = '%s' 
						and p.warehouse_id = w.id
						and p.biz_user_id = u.id
						and p.pw_bill_id = ia.id
						and p.supplier_id = s.id ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $result;
			}
			
			$result["ref"] = $data[0]["ref"];
			$result["billStatus"] = $data[0]["bill_status"];
			$result["bizUserId"] = $data[0]["biz_user_id"];
			$result["bizUserName"] = $data[0]["biz_user_name"];
			$result["warehouseId"] = $data[0]["warehouse_id"];
			$result["warehouseName"] = $data[0]["warehouse_name"];
			$result["pwbillRef"] = $data[0]["pwbill_ref"];
			$result["supplierId"] = $data[0]["supplier_id"];
			$result["supplierName"] = $data[0]["supplier_name"];
			$result["pwbillId"] = $data[0]["pwbill_id"];
			$result["bizDT"] = $this->toYMD($data[0]["bizdt"]);
			
			$items = array();
			$sql = "select p.pwbilldetail_id as id, p.goods_id, g.code as goods_code, g.name as goods_name,
						g.spec as goods_spec, g.bulk, u.name as unit_name, p.goods_count,
						p.goods_price, p.goods_money, p.rejection_goods_count as rej_count,
						p.rejection_goods_price as rej_price, p.rejection_money as rej_money
					from t_pr_bill_detail p, t_goods g, t_goods_unit u
					where p.prbill_id = '%s'
						and p.goods_id = g.id
						and g.unit_id = u.id
					order by p.show_order";
			$data = $db->query($sql, $id);
			foreach ( $data as $i => $v ) {
				$items[$i]["id"] = $v["id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$items[$i]["goodsCode"] = $v["goods_code"];
				$items[$i]["goodsName"] = $v["goods_name"];
				$items[$i]["goodsSpec"] = $v["goods_spec"];
				$items[$i]["unitName"] = $v["unit_name"];
				if($v["bulk"] == 0){
					$items[$i]["unitName"] = "kg";
				}
				$items[$i]["goodsCount"] = $v["goods_count"];
				$items[$i]["goodsPrice"] = $v["goods_price"];
				$items[$i]["goodsMoney"] = $v["goods_money"];
				$items[$i]["rejCount"] = $v["rej_count"];
				$items[$i]["rejPrice"] = $v["rej_price"];
				$items[$i]["rejMoney"] = $v["rej_money"];
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

	public function editPRBill($params) {
		$json = $params["jsonStr"];
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$db = M();
		
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$warehouseId = $bill["warehouseId"];
		$sql = "select name from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("选择的仓库不存在，无法保存");
		}
		
		$bizUserId = $bill["bizUserId"];
		$sql = "select name from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		if (! $data) {
			return $this->bad("选择的业务人员不存在，无法保存");
		}
		
		$pwBillId = $bill["pwBillId"];
		$sql = "select supplier_id from t_ia_bill where id = '%s' ";
		$data = $db->query($sql, $pwBillId);
		if (! $data) {
			return $this->bad("选择验收单不存在，无法保存");
		}
		$supplierId = $data[0]["supplier_id"];
		
		$items = $bill["items"];
		
		$idGen = new IdGenService();
		$us = new UserService();
		
		if ($id) {
			// 编辑采购退货出库单
			$db->startTrans();
			try {
				$sql = "select ref, bill_status
						from t_pr_bill
						where id = '%s' ";
				$data = $db->query($sql, $id);
				if (! $data) {
					$db->rollback();
					return $this->bad("要编辑的采购退货出库单不存在");
				}
				$ref = $data[0]["ref"];
				$billStatus = $data[0]["bill_status"];
				if ($billStatus != 0) {
					$db->rollback();
					return $this->bad("采购退货出库单(单号：$ref)已经提交，不能再被编辑");
				}
				
				// 明细表
				$sql = "delete from t_pr_bill_detail where prbill_id = '%s' ";
				$db->execute($sql, $id);
				
				$sql = "insert into t_pr_bill_detail(id, date_created, goods_id, goods_count, goods_price,
						goods_money, rejection_goods_count, rejection_goods_price, rejection_money, show_order,
						prbill_id, pwbilldetail_id)
						values ('%s', now(), '%s', %f, %f, %f, %f, %f, %f, %d, '%s', '%s')";
				foreach ( $items as $i => $v ) {
					$pwbillDetailId = $v["id"];
					$goodsId = $v["goodsId"];
					$goodsCount = $v["goodsCount"];
					$goodsPrice = $v["goodsPrice"];
					$goodsMoney = $goodsCount * $goodsPrice;
					$rejCount = $v["rejCount"];
					$rejPrice = $v["rejPrice"];
					$rejMoney = $rejCount * $rejPrice;
					$rc = $db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, 
							$goodsMoney, $rejCount, $rejPrice, $rejMoney, $i, $id, $pwbillDetailId);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				}
				
				$sql = "select sum(rejection_money) as rej_money 
						from t_pr_bill_detail 
						where prbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$rejMoney = $data[0]["rej_money"];
				
				$sql = "update t_pr_bill
						set rejection_money = %f,
							bizdt = '%s', biz_user_id = '%s',
							date_created = now(), input_user_id = '%s',
							warehouse_id = '%s'
						where id = '%s' ";
				$rc = $db->execute($sql, $rejMoney, $bizDT, $bizUserId, $us->getLoginUserId(), 
						$warehouseId, $id);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				$bs = new BizlogService();
				$log = "编辑采购退货出库单，单号：$ref";
				$bs->insertBizlog($log, "采购退货出库");
				
				$db->commit();
			} catch ( Exception $e ) {
				$db->rollback();
				return $this->sqlError();
			}
		} else {
			// 新增采购退货出库单
			$db->startTrans();
			try {
				$id = $idGen->newId();
				$ref = $this->genNewBillRef();
				
				// 主表
				$sql = "insert into t_pr_bill(id, bill_status, bizdt, biz_user_id, supplier_id, date_created,
							input_user_id, ref, warehouse_id, pw_bill_id)
						values ('%s', 0, '%s', '%s', '%s', now(), '%s', '%s', '%s', '%s')";
				$rc = $db->execute($sql, $id, $bizDT, $bizUserId, $supplierId, 
						$us->getLoginUserId(), $ref, $warehouseId, $pwBillId);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				// 明细表
				$sql = "insert into t_pr_bill_detail(id, date_created, goods_id, goods_count, goods_price,
						goods_money, rejection_goods_count, rejection_goods_price, rejection_money, show_order,
						prbill_id, pwbilldetail_id)
						values ('%s', now(), '%s', %f, %f, %f, %f, %f, %f, %d, '%s', '%s')";
				foreach ( $items as $i => $v ) {
					$pwbillDetailId = $v["id"];
					$goodsId = $v["goodsId"];
					$goodsCount = $v["goodsCount"];
					$goodsPrice = $v["goodsPrice"];
					$goodsMoney = $goodsCount * $goodsPrice;
					$rejCount = $v["rejCount"];
					$rejPrice = $v["rejPrice"];
					$rejMoney = $rejCount * $rejPrice;
					
					$rc = $db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, 
							$goodsMoney, $rejCount, $rejPrice, $rejMoney, $i, $id, $pwbillDetailId);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				}
				
				$sql = "select sum(rejection_money) as rej_money 
						from t_pr_bill_detail 
						where prbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$rejMoney = $data[0]["rej_money"];
				
				$sql = "update t_pr_bill
						set rejection_money = %f
						where id = '%s' ";
				$rc = $db->execute($sql, $rejMoney, $id);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				$bs = new BizlogService();
				$log = "新建采购退货出库单，单号：$ref";
				$bs->insertBizlog($log, "采购退货出库");
				
				$db->commit();
			} catch ( Exception $e ) {
				$db->rollback();
				return $this->sqlError();
			}
		}
		
		return $this->ok($id);
	}

	public function selectPWBillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$ref = $params["ref"];
		$supplierId = $params["supplierId"];
		$warehouseId = $params["warehouseId"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		
		$result = array();
		
		$db = M();
		
		$sql = "select p.id, p.ref, p.biz_dt, s.name as supplier_name, p.goods_money,
					w.name as warehouse_name, u1.name as biz_user_name, u2.name as input_user_name
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u1, t_user u2
				where (p.supplier_id = s.id) 
					and (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id) 
					and (p.input_user_id = u2.id)
					and (p.bill_status = 1000)";
		$queryParamas = array();
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParamas[] = "%$ref%";
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParamas[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParamas[] = $warehouseId;
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParamas[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParamas[] = $toDT;
		}
		
		$sql .= " order by p.ref desc limit %d, %d";
		$queryParamas[] = $start;
		$queryParamas[] = $limit;
		
		$data = $db->query($sql, $queryParamas);
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = $this->toYMD($v["biz_dt"]);
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["amount"] = $v["goods_money"];
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
		}
		
		$sql = "select count(*) as cnt
				from t_pw_bill p, t_supplier s, t_warehouse w, t_user u1, t_user u2
				where (p.supplier_id = s.id)
					and (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.bill_status = 1000)";
		$queryParamas = array();
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParamas[] = "%$ref%";
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParamas[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParamas[] = $warehouseId;
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParamas[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParamas[] = $toDT;
		}
		
		$data = $db->query($sql, $queryParamas);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function selectIABillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$ref = $params["ref"];
		$supplierId = $params["supplierId"];
		$warehouseId = $params["warehouseId"];
		$fromDT = $params["fromDT"];
		$toDT = $params["toDT"];
		$goodsId = $params["goodsId"];
		
		$result = array();
		
		$db = M();
		
		$sql = "select p.id, p.ref, p.biz_dt, s.name as supplier_name, p.goods_money,
					w.name as warehouse_name, u1.name as biz_user_name, u2.name as input_user_name
				from t_ia_bill p, t_supplier s, t_warehouse w, t_user u1, t_user u2
				where (p.supplier_id = s.id) 
					and (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id) 
					and (p.input_user_id = u2.id)
					and (p.bill_status = 1)";
		$queryParamas = array();
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParamas[] = "%$ref%";
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParamas[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParamas[] = $warehouseId;
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParamas[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParamas[] = $toDT;
		}
		if($goodsId) {
			$sql .= " and (p.id in (select iabill_id from t_ia_bill_detail where goods_id = '$goodsId')) ";
		}
		
		$sql .= " order by p.ref desc limit %d, %d";
		$queryParamas[] = $start;
		$queryParamas[] = $limit;
		
		$data = $db->query($sql, $queryParamas);
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = $this->toYMD($v["biz_dt"]);
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["amount"] = $v["goods_money"];
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
		}
		
		$sql = "select count(*) as cnt
				from t_ia_bill p, t_supplier s, t_warehouse w, t_user u1, t_user u2
				where (p.supplier_id = s.id)
					and (p.warehouse_id = w.id)
					and (p.biz_user_id = u1.id)
					and (p.input_user_id = u2.id)
					and (p.bill_status = 1000)";
		$queryParamas = array();
		
		if ($ref) {
			$sql .= " and (p.ref like '%s') ";
			$queryParamas[] = "%$ref%";
		}
		if ($supplierId) {
			$sql .= " and (p.supplier_id = '%s') ";
			$queryParamas[] = $supplierId;
		}
		if ($warehouseId) {
			$sql .= " and (p.warehouse_id = '%s') ";
			$queryParamas[] = $warehouseId;
		}
		if ($fromDT) {
			$sql .= " and (p.biz_dt >= '%s') ";
			$queryParamas[] = $fromDT;
		}
		if ($toDT) {
			$sql .= " and (p.biz_dt <= '%s') ";
			$queryParamas[] = $toDT;
		}
		if($goodsId) {
			$sql .= " and (p.id in (select iabill_id from t_ia_bill_detail where goods_id = '$goodsId')) ";
		}
		$data = $db->query($sql, $queryParamas);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function getPWBillInfoForPRBill($params) {
		$id = $params["id"];
		$result = array();
		
		$db = M();
		
		$sql = "select p.ref,s.id as supplier_id, s.name as supplier_name,
					w.id as warehouse_id, w.name as warehouse_name 
				from t_pw_bill p, t_supplier s, t_warehouse w
				where p.supplier_id = s.id
					and p.warehouse_id = w.id
					and p.id = '%s' ";
		
		$data = $db->query($sql, $id);
		if (! $data) {
			return $result;
		}
		
		$result["ref"] = $data[0]["ref"];
		$result["supplierId"] = $data[0]["supplier_id"];
		$result["supplierName"] = $data[0]["supplier_name"];
		$result["warehouseId"] = $data[0]["warehouse_id"];
		$result["warehouseName"] = $data[0]["warehouse_name"];
		
		$items = array();
		
		$sql = "select p.id, g.id as goods_id, g.code as goods_code, g.name as goods_name,
					g.spec as goods_spec, u.name as unit_name, g.bulk, 
					p.goods_count, p.goods_price, p.goods_money
				from t_pw_bill_detail p, t_goods g, t_goods_unit u
				where p.goods_id = g.id
					and g.unit_id = u.id
					and p.pwbill_id = '%s'
				order by p.show_order ";
		$data = $db->query($sql, $id);
		foreach ( $data as $i => $v ) {
			$items[$i]["id"] = $v["id"];
			$items[$i]["goodsId"] = $v["goods_id"];
			$items[$i]["goodsCode"] = $v["goods_code"];
			$items[$i]["goodsName"] = $v["goods_name"];
			$items[$i]["goodsSpec"] = $v["goods_spec"];
			$items[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$items[$i]["unitName"] = "kg";
			}
			$items[$i]["goodsCount"] = $v["goods_count"];
			$items[$i]["goodsPrice"] = $v["goods_price"];
			$items[$i]["goodsMoney"] = $v["goods_money"];
			$items[$i]["rejPrice"] = $v["goods_price"];
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	public function getIABillInfoForPRBill($params) {
		$id = $params["id"];
		$goodsId = $params["goodsId"];
		$result = array();
		
		$db = M();
		
		$sql = "select p.ref,s.id as supplier_id, s.name as supplier_name,
					w.id as warehouse_id, w.name as warehouse_name 
				from t_ia_bill p, t_supplier s, t_warehouse w
				where p.supplier_id = s.id
					and p.warehouse_id = w.id
					and p.id = '%s' ";
		
		$data = $db->query($sql, $id);
		if (! $data) {
			return $result;
		}
		
		$result["ref"] = $data[0]["ref"];
		$result["supplierId"] = $data[0]["supplier_id"];
		$result["supplierName"] = $data[0]["supplier_name"];
		$result["warehouseId"] = $data[0]["warehouse_id"];
		$result["warehouseName"] = $data[0]["warehouse_name"];
		
		$items = array();
		$goods_sql = "";
		if($goodsId){
			$goods_sql = " and p.goods_id = '$goodsId' ";
		}
		$sql = "select p.id, g.id as goods_id, g.code as goods_code, g.name as goods_name,
					g.spec as goods_spec, g.bulk, u.name as unit_name, 
					p.goods_count, p.goods_price, p.goods_money
				from t_ia_bill_detail p, t_goods g, t_goods_unit u
				where p.goods_id = g.id
					and g.unit_id = u.id
					and p.iabill_id = '%s' $goods_sql 
				order by p.show_order ";
		$data = $db->query($sql, $id);
		foreach ( $data as $i => $v ) {
			$items[$i]["id"] = $v["id"];
			$items[$i]["goodsId"] = $v["goods_id"];
			$items[$i]["goodsCode"] = $v["goods_code"];
			$items[$i]["goodsName"] = $v["goods_name"];
			$items[$i]["goodsSpec"] = $v["goods_spec"];
			$items[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$items[$i]["unitName"] = "kg";
			}
			$items[$i]["goodsCount"] = $v["goods_count"];
			$items[$i]["goodsPrice"] = $v["goods_price"];
			$items[$i]["goodsMoney"] = $v["goods_money"];
			$items[$i]["rejPrice"] = $v["goods_price"];
			$items[$i]["rejCount"] = 0;
			$items[$i]["rejMoney"] = 0;
		}
		
		$result["items"] = $items;
		
		return $result;
	}

	public function prbillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begindate = $params["begindate"];
		$enddate   = $params["enddate"];
		$ysRef   = $params["ysRef"];
		$thRef   = $params["thRef"];
		$spplier   = $params["spplier"];
		$where = "";
		if($ysRef) $where .= " and a.ref='{$ysRef}' ";
		if($thRef) $where .= " and p.ref='{$thRef}' ";
		if($spplier) $where .= " and (s.name='{$spplier}' or s.code='{$spplier}') ";
		
		$db = M();
		$result = array();
		$sql = "select p.id, p.ref, p.bill_status, w.name as warehouse_name, p.bizdt,a.ref as ysref,
					p.rejection_money, u1.name as biz_user_name, u2.name as input_user_name,p.caiwuqueren,
					s.name as supplier_name
				from t_pr_bill p, t_warehouse w, t_user u1, t_user u2, t_supplier s,t_ia_bill a
				where p.warehouse_id = w.id and p.pw_bill_id=a.id
					and p.biz_user_id = u1.id
					and p.input_user_id = u2.id
					and p.supplier_id = s.id".$where;
        if($begindate){
            $begindate = str_replace("T", " ", $begindate);
            $sql .= " and p.date_created>='$begindate'";
        }
        if($enddate){
            $enddate = str_replace("T", " ", $enddate);
            $sql .= " and p.date_created<='$enddate'";
        }
        $sql .= " order by p.ref desc
				limit %d, %d";
		$data = $db->query($sql, $start, $limit);
		$list[] = array('单号','验收单号' ,'日期', '供应商',
								'金额', '业务员', '录单人', '财务确认');
		foreach ( $data as $i => $v ) {
			$result[$i]["ysref"] = $v["ysref"];
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["billStatus"] = $v["bill_status"] == 0 ? "待出库" : "已出库";
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["rejMoney"] = $v["rejection_money"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["caiwuqueren"] = $v["caiwuqueren"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["bizDT"] = $this->toYMD($v["bizdt"]);
			$list[] = array($v["ref"],$result[$i]["ysref"],$result[$i]["bizDT"],$result[$i]["supplierName"],$v["rejection_money"],$result[$i]["bizUserName"],$result[$i]["inputUserName"]);
		}

		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '采购退货单导出数据.csv';
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$change_name.'"');
			header('Cache-Control: max-age=0');
			$file = fopen('php://output', 'a');
		}
		if(I("request.act") == 'export'){	//导出数据
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


		
		$sql = "select count(*) as cnt
				from t_pr_bill p, t_warehouse w, t_user u1, t_user u2, t_supplier s,t_ia_bill a
				where p.warehouse_id = w.id and p.pw_bill_id=a.id
					and p.biz_user_id = u1.id
					and p.input_user_id = u2.id
					and p.supplier_id = s.id ".$where;
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function querenPRBill($id) {
		$db = M();
		$sql = "select ref,caiwuqueren from t_pr_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要确认的退货单不存在");
		}
		$querenStatus = $data[0]["caiwuqueren"];
		if ($querenStatus) {
			return $this->bad("退货单已经确认");
		}
					
        $sql = "update t_pr_bill 
                set caiwuqueren = 1
                where id = '%s' ";
        $db->query($sql, $id);
		
		return $this->ok($id);
	}

	public function prBillDetailList($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select g.code, g.name, g.spec, g.bulk, u.name as unit_name, g.buytax, 
					p.rejection_goods_count as rej_count, p.rejection_goods_price as rej_price, 
					p.rejection_money as rej_money
				from t_pr_bill_detail p, t_goods g, t_goods_unit u
				where p.goods_id = g.id and g.unit_id = u.id and p.prbill_id = '%s'
					and p.rejection_goods_count > 0
				order by p.show_order";
		$result = array();
		$data = $db->query($sql, $id);
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
			$result[$i]["rejCount"] = $v["rej_count"];
			$result[$i]["rejPrice"] = $v["rej_price"];
			$result[$i]["rejMoney"] = $v["rej_money"];
			$result[$i]["rejTax"] = $v["buytax"];
			$result[$i]["rejTaxMoney"] = ($v["rej_money"]/(100+$v["buytax"]))*$v["buytax"];
			$result[$i]["rejNoTaxMoney"] = $v["rej_money"]-$result[$i]["rejTaxMoney"];
		}
		
		return $result;
	}

	public function deletePRBill($params) {
		$id = $params["id"];
		
		$db = M();
		
		$db->startTrans();
		try {
			$sql = "select ref, bill_status from t_pr_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				$db->rollback();
				return $this->bad("要删除的采购退货出库单不存在");
			}
			$ref = $data[0]["ref"];
			$billStatus = $data[0]["bill_status"];
			if ($billStatus != 0) {
				$db->rollback();
				return $this->bad("采购退货出库单(单号：$ref)已经提交，不能被删除");
			}
			
			$sql = "delete from t_pr_bill_detail where prbill_id = '%s'";
			$db->execute($sql, $id);
			
			$sql = "delete from t_pr_bill where id = '%s' ";
			$db->execute($sql, $id);
			
			$bs = new BizlogService();
			$log = "删除采购退货出库单，单号：$ref";
			$bs->insertBizlog($log, "采购退货出库");
			
			$db->commit();
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->sqlError();
		}
		
		return $this->ok();
	}

	public function commitPRBill($params) {
		$id = $params["id"];
		$prid = $id;
		$db = M();
		$goods_stock_array = array();
		$db->startTrans();
		try {
			$sql = "select ref, bill_status, warehouse_id, bizdt, biz_user_id, rejection_money,
					supplier_id
					from t_pr_bill 
					where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				$db->rollback();
				return $this->bad("要提交的采购退货出库单不存在");
			}
			$ref = $data[0]["ref"];
			$billStatus = $data[0]["bill_status"];
			$warehouseId = $data[0]["warehouse_id"];
			$bizDT = $this->toYMD($data[0]["bizdt"]);
			$bizUserId = $data[0]["biz_user_id"];
			$allRejMoney = $data[0]["rejection_money"];
			$supplierId = $data[0]["supplier_id"];
			$pw_bill_id = $data[0]["pw_bill_id"];
			
			if ($billStatus != 0) {
				$db->rollback();
				return $this->bad("采购退货出库单(单号：$ref)已经提交，不能再次提交");
			}
			$sql = "select name, inited from t_warehouse where id = '%s' ";
			$data = $db->query($sql, $warehouseId);
			if (! $data) {
				$db->rollback();
				return $this->bad("要出库的仓库不存在");
			}
			$warehouseName = $data[0]["name"];
			$inited = $data[0]["inited"];
			if ($inited != 1) {
				$db->rollback();
				return $this->bad("仓库[$warehouseName]还没有完成库存建账，不能进行出库操作");
			}
			$sql = "select name from t_user where id = '%s' ";
			$data = $db->query($sql, $bizUserId);
			if (! $data) {
				$db->rollback();
				return $this->bad("业务人员不存在，无法完成提交操作");
			}
			$sql = "select name from t_supplier where id = '%s' ";
			$data = $db->query($sql, $supplierId);
			if (! $data) {
				$db->rollback();
				return $this->bad("供应商不存在，无法完成提交操作");
			}
			
			$sql = "select goods_id, rejection_goods_count as rej_count,
						goods_count
					from t_pr_bill_detail
					where prbill_id = '%s'
					order by show_order";
			$items = $db->query($sql, $id);

			foreach ( $items as $i => $v ) {
				$goodsId = $v["goods_id"];
				$rejCount = $v["rej_count"];
				$goodsCount = $v["goods_count"];
				
				if ($rejCount == 0) {
					continue;
				}
				$goods_info = $this->base_get_goods_info($goodsId);
				if($goods_info["oversold"] != 1){
					//如果不允许超卖，则需要同步更新库存
					$goods_stock = array(
						"goods_code"  => $goods_info["code"],
						"goods_number" => -$rejCount
					);
					$goods_stock_array[] = $goods_stock;
				}
				if ($rejCount < 0) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条记录的退货数量不能为负数");
				}
				if ($rejCount > $goodsCount) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条记录的退货数量不能大于采购数量");
				}
				
				// 库存总账
				$sql = "select balance_count, balance_price, balance_money,
							out_count, out_money
						from t_inventory
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $warehouseId, $goodsId);
				if (! $data) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条商品库存不足，无法退货");
				}
				$balanceCount = $data[0]["balance_count"];
				$balancePrice = $data[0]["balance_price"];
				$balanceMoney = $data[0]["balance_money"];
				if ($rejCount > $balanceCount) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条商品库存不足，无法退货");
				}
				$totalOutCount = $data[0]["out_count"];
				$totalOutMoney = $data[0]["out_money"];
				
				$outCount = $rejCount;
				$outMoney = $balancePrice * $outCount;
				if ($outCount == $balanceCount) {
					$outMoney = $balanceMoney;
				}
				$outPrice = $outMoney / $outCount;
				$totalOutCount += $outCount;
				$totalOutMoney += $outMoney;
				$totalOutPrice = $totalOutMoney / $totalOutCount;
				$balanceCount -= $outCount;
				if ($balanceCount == 0) {
					$balanceMoney = 0;
					$balancePrice = 0;
				} else {
					$balanceMoney -= $outMoney;
					$balancePrice = $balanceMoney / $balanceCount;
				}
				
				$sql = "update t_inventory
						set out_count = %d, out_price = %f, out_money = %f,
							balance_count = %d, balance_price = %f, balance_money = %f
						where warehouse_id = '%s' and goods_id = '%s' ";
				$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, 
						$balancePrice, $balanceMoney, $warehouseId, $goodsId);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError(1);
				}
				
				// 库存明细账
				$sql = "insert into t_inventory_detail(out_count, out_price, out_money, balance_count,
							balance_price, balance_money, warehouse_id, goods_id, biz_date, biz_user_id,
							date_created, ref_number, ref_type)
						values (%d, %f, %f, %d, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '采购退货出库')";
				$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, 
						$balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, 
						$ref);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError(2);
				}
			}
			
			$idGen = new IdGenService();
			
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
					$db->rollback();
					return $this->sqlError();
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
				if (! $rc && $rvMoney != 0) {
					$db->rollback();
					echo M()->getLastSql();
					return $this->sqlError(3);
				}
			}
			
			// 应收明细账
			$sql = "insert into t_receivables_detail(id, rv_money, act_money, balance_money, ca_id, ca_type,
						biz_date, date_created, ref_number, ref_type)
					values ('%s', %f, 0, %f, '%s', 'supplier', '%s', now(), '%s', '采购退货出库')";
			$rc = $db->execute($sql, $idGen->newId(), $allRejMoney, $allRejMoney, $supplierId, 
					$bizDT, $ref);
			
			// 修改单据本身的状态
			$sql = "update t_pr_bill
					set bill_status = 1000
					where id = '%s' ";
			$rc = $db->execute($sql, $id);
			if (! $rc) {
				$db->rollback();
				return $this->sqlError(4);
			}
			
			// 记录业务日志
			$bs = new BizlogService();
			$log = "提交采购退货出库单，单号：$ref";
			$bs->insertBizlog($log, "采购退货出库");
			//如果退的不是本月的单子，则重新生成一条信息
			/*
			$iabill = M("ia_bill")->where(array("id"=>$pw_bill_id))->find();
			if($iabill){
				//如果验收单不是当月的，则需要新建一个0元验收单作为退货单子
				$ia_month = date("m", strtotime($iabill["date_created"]));
				$now_month = date("m");
				if($ia_month != $now_month){
					//新增一个0元验收单
					$data = $iabill;
					$new_ia_id = $idGen->newId();
					$iabill["id"] = $new_ia_id;
					$iabill["biz_dt"] = date("Y-m-d");
					$iabill["date_created"] = date("Y-m-d H:i:s");
					$iabill["goods_money"] = 0;
					$iabill["goods_money"] = 0;
					$iabill["type"] = 99;
					$iabill["ref"] = $iabill["ref"]."01";
					M("ia_bill")->add($iabill);
					$sql = "select d1.rejection_money, d2.*
					from t_pr_bill_detail d1
					inner join t_ia_bill_detail d2 on d1.pwbilldetail_id = d2.id
					where d1.prbill_id = '$prid'
					order by show_order";
					$items = $db->query($sql);
					foreach ($items as $item) {
						//计算无税价格
						$goodsId = $item["goods_id"];
						$goods_info = $this->base_get_goods_info($goodsId);
						$rejMoney = $item["rejection_money"];
						$rejMoneyNoTax = round( $rejMoney / ( 1 + ( $goods_info["buytax"] / 100 ) ), 2);
						$data_item = $item;
						unset($data_item["rejection_money"]);
						$data_item["date_created"] = $iabill["date_created"];
						$data_item["goods_count"] = 0;
						$data_item["goods_money"] = 0;
						$data_item["goods_money_no_tax"] = 0;
						$data_item["iabill_id"] = $new_ia_id;
						$data_item["reject_money"] = $rejMoney;
						$data_item["reject_money_no_tax"] = $rejMoneyNoTax;
						M("ia_bill_detail")->add($data_item);
					}
				}
			}
			*/
			$db->commit();

			//退货完成后同步电商库存
			if($goods_stock_array){
				$ms = new MallService();
				$ms->syn_inventory($goods_stock_array, "采购退货");
			}

		} catch ( Exception $e ) {
			$db->rollback(5);
			return $this->sqlError();
		}
		
		return $this->ok($id);
	}
}