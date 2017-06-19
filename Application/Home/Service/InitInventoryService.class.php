<?php

namespace Home\Service;

/**
 * 库存建账Service
 *
 * @author 李静波
 */
class InitInventoryService extends ERPBaseService {

	public function warehouseList() {
		return M()->query("select id, code, name, inited from t_warehouse order by code");
	}

	public function initInfoList($params) {
		$warehouseId = $params["warehouseId"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		$sql = "select v.id, g.code, g.name, g.spec, v.balance_count, v.balance_price, 
				v.balance_money, u.name as unit_name, v.biz_date 
				from t_inventory_detail v, t_goods g, t_goods_unit u 
				where v.goods_id = g.id and g.unit_id = u.id and v.warehouse_id = '%s' 
				and ref_type = '库存建账' 
				order by g.code 
				limit " . $start . ", " . $limit;
		$data = $db->query($sql, $warehouseId);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["goodsCount"] = $v["balance_count"];
			$result[$i]["goodsUnit"] = $v["unit_name"];
			$result[$i]["goodsMoney"] = $v["balance_money"];
			$result[$i]["goodsPrice"] = $v["balance_price"];
			$result[$i]["initDate"] = date("Y-m-d", strtotime($v["biz_date"]));
		}
		
		$sql = "select count(*) as cnt from t_inventory_detail 
				where warehouse_id = '%s' and ref_type = '库存建账' ";
		$data = $db->query($sql, $warehouseId);
		
		return array(
				"initInfoList" => $result,
				"totalCount" => $data[0]["cnt"]
		);
	}

	public function goodsCategoryList() {
		return M()->query("select id, code, name from t_goods_category order by code");
	}

	public function goodsList($params) {
		$warehouseId = $params["warehouseId"];
		$categoryId = $params["categoryId"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		$sql = "select g.id, g.code, g.name, g.spec, v.balance_count, v.balance_price, 
				v.balance_money, u.name as unit_name, v.biz_date 
				from t_goods g inner join t_goods_unit u 
				on g.unit_id = u.id and g.category_id = '%s' 
				left join t_inventory_detail v
				on g.id = v.goods_id and v.ref_type = '库存建账' 
				and v.warehouse_id = '%s' 
				order by g.code 
				limit " . $start . ", " . $limit;
		$data = $db->query($sql, $categoryId, $warehouseId);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["goodsCount"] = $v["balance_count"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsMoney"] = $v["balance_money"];
			$result[$i]["goodsPrice"] = $v["balance_price"];
			$result[$i]["initDate"] = $v["biz_date"];
		}
		
		$sql = "select count(*) as cnt from t_goods where category_id = '%s' ";
		$data = $db->query($sql, $categoryId);
		
		return array(
				"goodsList" => $result,
				"totalCount" => $data[0]["cnt"]
		);
	}

	public function commitInitInventoryGoods($params) {
		$warehouseId = $params["warehouseId"];
		$goodsId = $params["goodsId"];
		$goodsCount = intval($params["goodsCount"]);
		$goodsMoney = floatval($params["goodsMoney"]);
		
		if ($goodsCount <= 0) {
			return $this->bad("期初数量不能小于0");
		}
		
		if ($goodsMoney < 0) {
			return $this->bad("期初金额不能为负数");
		}
		
		$goodsPrice = $goodsMoney / $goodsCount;
		
		$db = M();
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在");
		}
		if ($data[0]["inited"] != 0) {
			return $this->bad("仓库 [{$data[0]["name"]}] 已经建账完成，不能再次建账");
		}
		
		$sql = "select name from t_goods where id = '%s' ";
		$data = $db->query($sql, $goodsId);
		if (! $data) {
			return $this->bad("商品不存在");
		}
		$sql = "select count(*) as cnt from t_inventory_detail 
				where warehouse_id = '%s' and goods_id = '%s' and ref_type <> '库存建账' ";
		$data = $db->query($sql, $warehouseId, $goodsId);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("当前商品已经有业务发生，不能再建账");
		}
		
		$db->startTrans();
		try {
			// 总账
			$sql = "select id from t_inventory where warehouse_id = '%s' and goods_id = '%s' ";
			$data = $db->query($sql, $warehouseId, $goodsId);
			if (! $data) {
				$sql = "insert into t_inventory (warehouse_id, goods_id, in_count, in_price, 
						in_money, balance_count, balance_price, balance_money) 
						values ('%s', '%s', %d, %f, %f, %d, %f, %f) ";
				$db->execute($sql, $warehouseId, $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, $goodsPrice, $goodsMoney);
			} else {
				$id = $data[0]["id"];
				$sql = "update t_inventory  
						set in_count = %d, in_price = %f, in_money = %f, 
						balance_count = %d, balance_price = %f, balance_money = %f 
						where id = %d ";
				$db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, $goodsPrice, $goodsMoney, $id);
			}
			
			// 明细账
			$sql = "select id from t_inventory_detail  
					where warehouse_id = '%s' and goods_id = '%s' and ref_type = '库存建账' ";
			$data = $db->query($sql, $warehouseId, $goodsId);
			if (! $data) {
				$sql = "insert into t_inventory_detail (warehouse_id, goods_id,  in_count, in_price,
						in_money, balance_count, balance_price, balance_money,
						biz_date, biz_user_id, date_created,  ref_number, ref_type)
						values ('%s', '%s', %d, %f, %f, %d, %f, %f, curdate(), '%s', now(), '', '库存建账')";
				$us = new UserService();
				$db->execute($sql, $warehouseId, $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, $goodsPrice, $goodsMoney, $us->getLoginUserId());
			} else {
				$id = $data[0]["id"];
				$sql = "update t_inventory_detail 
						set in_count = %d, in_price = %f, in_money = %f,
						balance_count = %d, balance_price = %f, balance_money = %f,
						biz_date = curdate()  
						where id = %d ";
				$db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $goodsCount, $goodsPrice, $goodsMoney, $id);
			}
			
			$db->commit();
		} catch ( Exception $exc ) {
			$db->rollback();
			
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok();
	}

	public function finish($params) {
		$warehouseId = $params["warehouseId"];
		$db = M();
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在");
		}
		$inited = $data[0]["inited"];
		$name = $data[0]["name"];
		if ($inited == 1) {
			return $this->bad("仓库 [{$name}] 已经建账完毕");
		}
		
		$db->startTrans();
		try {
			$sql = "update t_warehouse set inited = 1 where id = '%s' ";
			$db->execute($sql, $warehouseId);
			
			$sql = "update t_inventory_detail set biz_date = curdate() 
					where warehouse_id = '%s' and ref_type = '库存建账' ";
			$db->execute($sql, $warehouseId);
			
			$log = "仓库 [{$name}] 建账完毕";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "库存");
			
			$db->commit();
		} catch ( Exception $exc ) {
			$db->rollback();
			
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok();
	}

	public function cancel($params) {
		$warehouseId = $params["warehouseId"];
		$db = M();
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在");
		}
		$inited = $data[0]["inited"];
		$name = $data[0]["name"];
		if ($inited != 1) {
			return $this->bad("仓库 [{$name}] 还没有标记为建账完毕，无需取消建账标志");
		}
		$sql = "select count(*) as cnt from t_inventory_detail 
				where warehouse_id = '%s' and ref_type <> '库存建账' ";
		$data = $db->query($sql, $warehouseId);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("仓库 [{$name}] 中已经发生出入库业务，不能再取消建账标志");
		}
		
		$sql = "update t_warehouse set inited = 0 where id = '%s' ";
		$db->execute($sql, $warehouseId);
		
		$log = "仓库 [{$name}] 取消建账完毕标志";
		$bs = new BizlogService();
		$bs->insertBizlog($log, "库存");
		
		return $this->ok();
	}
}