<?php

namespace Home\Service;
use Home\Service\MallService;
/**
 * 库存盘点Service
 *
 * @author 李静波
 */
class ICBillService extends ERPBaseService {

	/**
	 * 生成新的盘点单单号
	 *
	 * @return string
	 */
	public function genNewBillRef() {
		$pre = "IC";
		$mid = date("Ymd");
		
		$sql = "select ref from t_ic_bill where ref like '%s' order by ref desc limit 1";
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
	 * 生成新的盘点单
	 *
	 * @return string
	 */
	public function genNewICBill($userid, $warehouse_id = ""){
		$idGen = new IdGenService();
		$ref = $this->genNewBillRef();
		$id = $idGen->newId();
		$db = M();
		$bizDT =date("Y-m-d H:i:s",time());
		$bizUserId = $userid;
		if($warehouse_id){
			$warehouseId = $warehouse_id;
		} else {
			$warehouse = $this->base_get_default_warehouse();
			$warehouseId = $warehouse["warehouseId"];
		}
		
		$sql = "insert into t_ic_bill(id, bill_status, bizdt, biz_user_id, date_created, 
							input_user_id, ref, warehouse_id)
						values ('%s', -1, '%s', '%s', now(), '%s', '%s', '%s')";
		$rc = $db->execute($sql, $id, $bizDT, $bizUserId, $bizUserId, $ref, 
						$warehouseId);
		if (!$rc) {
			$this->bad($this->sqlError());
		}
		$data = array(
			"id" => $id,
			"ref" => $ref,
			"bill_status" => -1
		);
		return $this->suc($data);
	}

	//获取所有盘点单列表
	public function icList($params){
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		if(!$limit){
			$limit = 50;
		}
		$start = ($page - 1) * $limit;
		$status = $params["bill_status"];
		$begindate = $params["startdate"];
		$enddate = $params["enddate"];
		$ref = $params["ref"];
		$userid = $params["userid"];
		$db = M();
		$status_sql = "";
		if($status != ""){
			$status_sql = " and t.bill_status = $status ";
		}
		$date_sql = "";
		if($begindate){
			$date_sql .= " and t.bizdt >= '$begindate' ";
		}
		if($enddate){
			$date_sql .= " and t.bizdt <= '$enddate' ";
		}
		$ref_sql = "";
		if($ref){
			$ref_sql = " and t.ref like '%$ref%' ";
		}
		$user_sql = "";
		if($userid){
			$user_sql = " and t.biz_user_id = '$userid' ";
		}
		$sql = "select t.id, t.ref, t.bizdt, t.bill_status,
		w.name as warehouse_name,
		u.name as biz_user_name,
		u1.name as input_user_name
		from t_ic_bill t, t_warehouse w, t_user u, t_user u1
		where t.warehouse_id = w.id
		and t.biz_user_id = u.id
		and t.input_user_id = u1.id
		$status_sql $date_sql $ref_sql $user_sql
		order by t.ref desc
		limit $start , $limit
		";
		$data = $db->query($sql);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$status_str = "";
			if($v["bill_status"] == -1){
				$status_str = "盘点中";
			} else if ($v["bill_status"] == 0){
				$status_str = "审核中";
			} else if ($v["bill_status"] == 1000){
				$status_str = "盘点结束";
			}
			$result[$i]["billStatus"] = $v["bill_status"];
			$result[$i]["billStatusStr"] = $status_str;
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
		}
		
		$sql = "select count(*) as cnt
				from t_ic_bill t, t_warehouse w, t_user u, t_user u1
				where t.warehouse_id = w.id
				  and t.biz_user_id = u.id
				  and t.input_user_id = u1.id $status_sql $date_sql $ref_sql $user_sql 
				";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"success" => true,
				"data" => $result,
				"totalCount" => $cnt
		);
	}

	public function icInfo($id){
		$info = $this->icBillInfo(array("id" => $id));
		$items = $info["items"];
		return array(
				"success" => true,
				"data" => $items
		);
	}

	public function icDel($params){
		$id = $params["id"];
		$userid = $params["userid"];
		$db = M("ic_bill", "t_");
		$bill = $db->where(array("id" => $id))->find();
		$status = $bill["bill_status"];
		if(!$bill){
			return $this->bad("盘点单不存在");
		}
		if($status == 1000){
			return $this->bad("盘点单已经入库了，无法删除");
		}
		//如果不是自己的单子，无法删除
		if($userid != $bill["input_user_id"]){
			return $this->bad("不是你的单子，无法删除");
		}
		$map2 = array(
			"icbill_id" => $id
		);
		$map1 = array(
			"id" => $id
		);
		M("ic_bill_detail", "t_")->where($map2)->delete();
		$db->where($map1)->delete();
		return $this->ok();
	}


	//加入商品列表
	public function icGoodsAdd($params){
		// 明细表
		$idGen = new IdGenService();
		$us = new UserService();
		$id = $params["id"];
		$db = M("ic_bill", "t_");
		$bill = $db->where(array("id" => $id))->find();
		$userid = $params["userid"];
		if(!$bill){
			return $this->bad("盘点单不存在");
		}
		$status = $bill["bill_status"];
		if($status == 1000){
			return $this->bad("盘点单已经入库了，无法修改");
		}
		if($status == 0){
			return $this->bad("盘点单正在审核，无法修改");
		}
		//如果不是自己的单子，无法删除
		if($userid != $bill["input_user_id"]){
			return $this->bad("不是你的单子，无法修改");
		}
		$goodsId = $params["goods_id"];
		$goodsCount = $params["goods_count"];
		$goodsMoney = $this->getGoodsInPrice($goodsId) * $goodsCount;
		//首先删除已有的
		$map = array(
			"icbill_id" => $id,
			"goods_id" => $goodsId
		);
		M("ic_bill_detail", "t_")->where($map)->delete();
		$sql = "SELECT balance_count FROM `t_inventory` WHERE `goods_id` LIKE '%s'";
		$data = $db->query($sql, $goodsId);
		$goodsCountBefore = floatval($data[0]['balance_count']);
		$sql = "insert into t_ic_bill_detail(id, date_created, goods_id, goods_count, goods_money, 
					show_order, icbill_id, goods_count_before)
				values ('%s', now(), '%s', %f, %f, %d, '%s', '$s')";
		//foreach ( $items as $i => $v ) {
			
			if (! $goodsId) {
				return $this->bad("缺少参数:goods_id");
			}
			$rc = $db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsMoney, 
					1, $id, $goodsCountBefore);
			if (! $rc) {
				return $this->bad("操作失败，请重试");
			}
		//}
		return $this->ok();
	}

	public function icFinish($params){
		$idGen = new IdGenService();
		$us = new UserService();
		$id = $params["id"];
		$db = M("ic_bill", "t_");
		$bill = $db->where(array("id" => $id))->find();
		$status = $bill["bill_status"];
		if(!$bill){
			return $this->bad("盘点单不存在");
		}
		if($status == 1000){
			return $this->bad("盘点单已经入库了");
		}
		if($status == 0){
			return $this->bad("盘点单已经完成了");
		}
		$map = array(
			"id" => $id
		);
		$db->where($map)->setField("bill_status", 0);
		return $this->ok();
	}

	public function icBillInfo($params) {
		$id = $params["id"];
		
		$result = array();
		
		if ($id) {
			// 编辑
			$db = M();
			$sql = "select t.ref, t.bill_status, t.bizdt, t.biz_user_id, u.name as biz_user_name,
						w.id as warehouse_id, w.name as warehouse_name
					from t_ic_bill t, t_user u, t_warehouse w
					where t.id = '%s' and t.biz_user_id = u.id
					      and t.warehouse_id = w.id";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $result;
			}
			
			$result["bizUserId"] = $data[0]["biz_user_id"];
			$result["bizUserName"] = $data[0]["biz_user_name"];
			$result["ref"] = $data[0]["ref"];
			$result["billStatus"] = $data[0]["bill_status"];
			$result["bizDT"] = date("Y-m-d", strtotime($data[0]["bizdt"]));
			$result["warehouseId"] = $data[0]["warehouse_id"];
			$result["warehouseName"] = $data[0]["warehouse_name"];
			
			$items = array();
			$sql = "select t.id, t.position, g.id as goods_id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, 
						t.goods_count, t.goods_money ,t.goods_count_before 
				from t_ic_bill_detail t, t_goods g, t_goods_unit u
				where t.icbill_id = '%s' and t.goods_id = g.id and g.unit_id = u.id
				order by t.show_order ";
			
			$data = $db->query($sql, $id);
			foreach ( $data as $i => $v ) {
				$items[$i]["id"] = $v["id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$items[$i]["goodsCode"] = $v["code"];
				$items[$i]["goodsName"] = $v["name"];
				$items[$i]["goodsSpec"] = $v["spec"];
				$items[$i]["unitName"] = $v["unit_name"];
				if($v["bulk"] == 0){
					$items[$i]["unitName"] = "kg";
				}
				$items[$i]["goodsCount"] = $v["goods_count"];
				$items[$i]["goodsCountBefore"] = $v["goods_count_before"];
				$items[$i]["goodsCountDiff"] = $v["goods_count"] - $v["goods_count_before"];
				$items[$i]["goodsMoney"] = $v["goods_money"];
				$items[$i]["position"] = $v["position"];
			}
			
			$result["items"] = $items;
		} else {
			// 新建
			$us = new UserService();
			$result["bizUserId"] = $us->getLoginUserId();
			$result["bizUserName"] = $us->getLoginUserName();
			//获取默认仓库
			$warehouse = $this->base_get_default_warehouse();
			$result["warehouseName"] = $warehouse["warehouseName"];
			$result["warehouseId"] = $warehouse["warehouseId"];
		}
		
		return $result;
	}

	public function editICBill($params) {
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
			return $this->bad("盘点仓库不存在，无法保存");
		}
		
		$bizUserId = $bill["bizUserId"];
		$sql = "select name from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		if (! $data) {
			return $this->bad("业务人员不存在，无法保存");
		}
		
		$items = $bill["items"];
		if (!$items[0]['goodsId']) {
			return $this->bad("盘点商品不能为空");
		}
		$idGen = new IdGenService();
		$us = new UserService();
		
		if ($id) {
			// 编辑单据
			$db->startTrans();
			try {
				$sql = "select ref, bill_status from t_ic_bill where id = '%s' ";
				$data = $db->query($sql, $id);
				if (! $data) {
					$db->rollback();
					return $this->bad("要编辑的盘点点不存在，无法保存");
				}
				
				$ref = $data[0]["ref"];
				$billStatus = $data[0]["bill_status"];
				if ($billStatus != 0) {
					$db->rollback();
					return $this->bad("盘点单(单号：$ref)已经提交，不能再编辑");
				}
				
				// 主表
				$sql = "update t_ic_bill
						set bizdt = '%s', biz_user_id = '%s', date_created = now(), 
							input_user_id = '%s', warehouse_id = '%s'
						where id = '%s' ";
				$rc = $db->execute($sql, $bizDT, $bizUserId, $us->getLoginUserId(), $warehouseId, 
						$id);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				// 明细表
				$sql = "delete from t_ic_bill_detail where icbill_id = '%s' ";
				$db->execute($sql, $id);
				
				$sql = "insert into t_ic_bill_detail(id, date_created, goods_id, goods_count, goods_count_before, goods_money,
							show_order, icbill_id, position)
						values ('%s', now(), '%s', %f, %f, %f, %d, '%s', '%s')";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					if (! $goodsId) {
						continue;
					}
					$goodsCount = $v["goodsCount"];
					$goodsMoney = $v["goodsMoney"];
					if($v["goodsCount"] == 0){
						$goodsMoney = 0;
					}
					$goodsCountBefore = $v["goodsCountBefore"];
					$goodsPosition = $v["goodsPosition"];
					$rc = $db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsCountBefore, $goodsMoney, 
							$i, $id, $goodsPosition);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				}
				
				$bs = new BizlogService();
				$log = "编辑盘点单，单号：$ref";
				$bs->insertBizlog($log, "库存盘点");
				
				$db->commit();
			} catch ( Exception $e ) {
				$db->rollback();
				return $this->sqlError();
			}
		} else {
			// 新建单据
			$db->startTrans();
			try {
				$id = $idGen->newId();
				$ref = $this->genNewBillRef();
				
				// 主表
				$sql = "insert into t_ic_bill(id, bill_status, bizdt, biz_user_id, date_created, 
							input_user_id, ref, warehouse_id)
						values ('%s', 0, '%s', '%s', now(), '%s', '%s', '%s')";
				$rc = $db->execute($sql, $id, $bizDT, $bizUserId, $us->getLoginUserId(), $ref, 
						$warehouseId);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				// 明细表
				$sql = "insert into t_ic_bill_detail(id, date_created, goods_id, goods_count, goods_count_before, goods_money,
							show_order, icbill_id, position)
						values ('%s', now(), '%s', %f, %f, %f, %d, '%s', '%s')";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					if (! $goodsId) {
						continue;
					}
					$goodsCount = $v["goodsCount"];
					$goodsMoney = $v["goodsMoney"];
					if($v["goodsCount"] == 0){
						$goodsMoney = 0;
					}
					$goodsCountBefore = $v["goodsCountBefore"];
					$goodsPosition = $v["goodsPosition"];
					$rc = $db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsCountBefore, $goodsMoney, 
							$i, $id, $goodsPosition);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				}


				$bs = new BizlogService();
				$log = "新建盘点单，单号：$ref";
				$bs->insertBizlog($log, "库存盘点");
				
				$db->commit();
			} catch ( Exception $e ) {
				$db->rollback();
				return $this->sqlError();
			}
		}
		//需要自动盘点
			//	$//commit_rs = $this->commitICBill(array('id' => $id));
				//if($commit_rs['success'] != 1){
				//	return $this->bad($commit_rs['msg']);
			//	}
		return $this->ok($id);
	}

	public function icbillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		
		$sql = "select t.id, t.ref, t.bizdt, t.bill_status,
		w.name as warehouse_name,
		u.name as biz_user_name,
		u1.name as input_user_name
		from t_ic_bill t, t_warehouse w, t_user u, t_user u1
		where t.warehouse_id = w.id
		and t.biz_user_id = u.id
		and t.input_user_id = u1.id
		order by t.ref desc
		limit $start , $limit
		";
		$data = $db->query($sql);
		$result = array();
		foreach ( $data as $i => $v ) {
			if($v["bill_status"] == -1){
				$status_str = "盘点中";
			} else if ($v["bill_status"] == 0){
				$status_str = "审核中";
			} else if ($v["bill_status"] == 1000){
				$status_str = "盘点结束";
			}
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["billStatus"] = $status_str;
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
		}
		
		$sql = "select count(*) as cnt
				from t_ic_bill t, t_warehouse w, t_user u, t_user u1
				where t.warehouse_id = w.id
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

	public function icBillDetailList($params) {
		$id = $params["id"];
		$result = array();
		
		$db = M();
		$sql = "select t.id as tid, g.code,g.id, g.name, g.spec,g.bulk, u.name as unit_name, t.goods_count, t.goods_money,t.goods_count_before 
				from t_ic_bill_detail t, t_goods g, t_goods_unit u
				where t.icbill_id = '%s' and t.goods_id = g.id and g.unit_id = u.id
				order by t.show_order ";
		
		$data = $db->query($sql, $id);
		$icbill = M("ic_bill")->where(array("id"=>$id))->find();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["tid"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
			$result[$i]["goodsCount"] = $v["goods_count"];
			$result[$i]["goodsMoney"] = $v["goods_money"];
			if($icbill["bill_status"] == 1000){
				$result[$i]["nowgoodsCount"] =  $v["goods_count_before"];
			} else {
				$temp = $db->query("SELECT balance_count FROM `t_inventory` WHERE `goods_id` = '%s'", $v["id"]);
				$balance_count = $temp[0]["balance_count"];

				if($balance_count && $balance_count != $v["goods_count_before"]){
					$map = array(
						"id" => $v["tid"]
					);
					M("ic_bill_detail")->where($map)->setField("goods_count_before", $balance_count);
				}
				$result[$i]["nowgoodsCount"] =  $balance_count ? $balance_count : 0;
			}
			
		}
		
		return $result;
	}

	public function deleteICBill($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select ref, bill_status from t_ic_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要删除的盘点单不存在");
		}
		
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		
		if ($billStatus == 1000) {
			return $this->bad("盘点单(单号：$ref)已经提交，不能被删除");
		}
		
		$db->startTrans();
		try {
			$sql = "delete from t_ic_bill_detail where icbill_id = '%s' ";
			$db->execute($sql, $id);
			
			$sql = "delete from t_ic_bill where id = '%s' ";
			$db->execute($sql, $id);
			
			$bs = new BizlogService();
			$log = "删除盘点单，单号：$ref";
			$bs->insertBizlog($log, "库存盘点");
			
			$db->commit();
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->sqlError();
		}
		
		return $this->ok();
	}

	public function commitICBill($params) {
		$id = $params["id"];
		$db = M();
		$goods_stock = array();
		$db->startTrans();
		try {
			$sql = "select ref, bill_status, warehouse_id, bizdt, biz_user_id 
					from t_ic_bill 
					where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				$db->rollback();
				return $this->bad("要提交的盘点单不存在");
			}
			$ref = $data[0]["ref"];
			$billStatus = $data[0]["bill_status"];
			if ($billStatus != 0) {
				$db->rollback();
				return $this->bad("盘点单(单号：$ref)已经提交，不能再次提交");
			}
			$warehouseId = $data[0]["warehouse_id"];
			$bizDT = date("Y-m-d", strtotime($data[0]["bizdt"]));
			$bizUserId = $data[0]["biz_user_id"];
			
			$sql = "select name, inited from t_warehouse where id = '%s' ";
			$data = $db->query($sql, $warehouseId);
			if (! $data) {
				$db->rollback();
				return $this->bad("要盘点的仓库不存在");
			}
			$inited = $data[0]["inited"];
			$warehouseName = $data[0]["name"];
			if ($inited != 1) {
				$db->rollback();
				return $this->bad("仓库[$warehouseName]还没有建账，无法做盘点操作");
			}
			
			$sql = "select name from t_user where id = '%s' ";
			$data = $db->query($sql, $bizUserId);
			if (! $data) {
				$db->rollback();
				return $this->bad("业务人员不存在，无法完成提交");
			}
			
			$sql = "select goods_id, goods_count, goods_money
					from t_ic_bill_detail
					where icbill_id = '%s' 
					order by show_order ";
			$items = $db->query($sql, $id);
			if (! $items) {
				$db->rollback();
				return $this->bad("盘点单没有明细信息，无法完成提交");
			}
			
			foreach ( $items as $i => $v ) {
				$goodsId = $v["goods_id"];
				$goodsCount = $v["goods_count"];
				$goodsMoney = $v["goods_money"];
				$goods_info = $this->base_get_goods_info($goodsId);
				$lastbuyprice = $goods_info["lastbuyprice"];
				$goods_one_stock = array(
					"goods_id" => $goodsId
				);
				$goods_stock[] = $goods_one_stock;
				// 检查商品是否存在
				$sql = "select code, name, spec from t_goods where id = '%s' ";
				$data = $db->query($sql, $goodsId);
				if (! $data) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条记录的商品不存在，无法完成提交");
				}
				
				if ($goodsCount < 0) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条记录的商品盘点后库存数量不能为负数");
				}
				if ($goodsMoney < 0) {
					$db->rollback();
					$index = $i + 1;
					return $this->bad("第{$index}条记录的商品盘点后库存金额不能为负数");
				}
				if ($goodsCount == 0) {
					if ($goodsMoney != 0) {
						$db->rollback();
						$index = $i + 1;
						return $this->bad("第{$index}条记录的商品盘点后库存数量为0的时候，库存金额也必须为0");
					}
				}
				
				$sql = "select balance_count, balance_money, in_count, in_money, out_count, out_money 
						from t_inventory
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $warehouseId, $goodsId);
				if (! $data) {
					// 这种情况是：没有库存，做盘盈入库
					$inCount = $goodsCount;
					$inMoney = $goodsMoney;
					$inPrice = 0;
					if ($inCount != 0) {
						$inPrice = $inMoney / $inCount;
					}
					
					// 库存总账
					$sql = "insert into t_inventory(in_count, in_price, in_money, balance_count, balance_price,
							balance_money, warehouse_id, goods_id)
							values (%f, %f, %f, %f, %f, %f, '%s', '%s')";
					$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $inCount, $inPrice, 
							$inMoney, $warehouseId, $goodsId);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
					
					// 库存明细账
					$sql = "insert into t_inventory_detail(in_count, in_price, in_money, balance_count, balance_price,
							balance_money, warehouse_id, goods_id, biz_date, biz_user_id, date_created, ref_number,
							ref_type)
							values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '库存盘点-盘盈入库')";
					$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $inCount, $inPrice, 
							$inMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref);
					if (! $rc) {
						$db->rollback();
						return $this->sqlError();
					}
				} else {
					$balanceCount = $data[0]["balance_count"];
					$balanceMoney = $data[0]["balance_money"];
					
					if ($goodsCount > $balanceCount) {
						// 盘盈入库
						$inCount = $goodsCount - $balanceCount;
						$inMoney = $lastbuyprice * $inCount;
						$inPrice = $inMoney / $inCount;
						$balanceCount = $goodsCount;
						$balanceMoney = $goodsMoney;
						$balancePrice = $balanceMoney / $balanceCount;
						$totalInCount = $data[0]["in_count"] + $inCount;
						$totalInMoney = $data[0]["in_money"] + $inMoney;
						$totalInPrice = $totalInMoney / $totalInCount;
						
						// 库存总账
						$sql = "update t_inventory
								set in_count = %f, in_price = %f, in_money = %f, 
								    balance_count = %f, balance_price = %f,
							        balance_money = %f
								where warehouse_id = '%s' and goods_id = '%s' ";
						$rc = $db->execute($sql, $totalInCount, $totalInPrice, $totalInMoney, $balanceCount, 
								$balancePrice, $balanceMoney, $warehouseId, $goodsId);
						if (! $rc) {
							$db->rollback();
							return $this->sqlError();
						}
						
						// 库存明细账
						$sql = "insert into t_inventory_detail(in_count, in_price, in_money, balance_count, balance_price,
							balance_money, warehouse_id, goods_id, biz_date, biz_user_id, date_created, ref_number,
							ref_type)
							values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '库存盘点-盘盈入库')";
						$rc = $db->execute($sql, $inCount, $inPrice, $inMoney, $balanceCount, 
								$balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, 
								$bizUserId, $ref);
						if (! $rc) {
							$db->rollback();
							return $this->sqlError();
						}
					} else {
						// 盘亏出库
						$outCount = $balanceCount - $goodsCount;
						$outMoney = $lastbuyprice * $outCount;
						$outPrice = 0;
						if ($outCount != 0) {
							$outPrice = $outMoney / $outCount;
						}
						$balanceCount = $goodsCount;
						$balanceMoney = $goodsMoney;
						$balancePrice = 0;
						if ($balanceCount != 0) {
							$balancePrice = $balanceMoney / $balanceCount;
						}
						
						$totalOutCount = $data[0]["out_count"] + $outCount;
						$totalOutMoney = $data[0]["out_money"] + $outMoney;
						$totalOutPrice = $totalOutMoney / $totalOutCount;
						
						// 库存总账
						$sql = "update t_inventory
								set out_count = %f, out_price = %f, out_money = %f, 
								    balance_count = %f, balance_price = %f,
							        balance_money = %f
								where warehouse_id = '%s' and goods_id = '%s' ";
						$rc = $db->execute($sql, $totalOutCount, $totalOutPrice, $totalOutMoney, $balanceCount, 
								$balancePrice, $balanceMoney, $warehouseId, $goodsId);
						if (! $rc) {
							//$db->rollback();
							//return $this->sqlError();
						}
						
						// 库存明细账
						$sql = "insert into t_inventory_detail(out_count, out_price, out_money, balance_count, balance_price,
							balance_money, warehouse_id, goods_id, biz_date, biz_user_id, date_created, ref_number,
							ref_type)
							values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '库存盘点-盘亏出库')";
						$rc = $db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, 
								$balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, 
								$bizUserId, $ref);
						if (! $rc) {
							$db->rollback();
							return $this->sqlError();
						}
					}
				}
			}
			// 修改单据本身状态
			$sql = "update t_ic_bill
					set bill_status = 1000
					where id = '%s' ";
			$rc = $db->execute($sql, $id);
			if (! $rc) {
				$db->rollback();
				return $this->sqlError();
			}
			
			// 记录业务日志
			$bs = new BizlogService();
			$log = "提交盘点单，单号：$ref";
			$bs->insertBizlog($log, "库存盘点");
			
			$db->commit();
			//提交之后，同步电商库存
			if($goods_stock){
				$ms = new MallService();
				$ms->syn_stock($goods_stock);
			}
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->sqlError();
		}
		
		return $this->ok($id);
	}
}