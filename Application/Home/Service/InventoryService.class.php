<?php

namespace Home\Service;

/**
 * 库存 Service
 *
 * @author 李静波
 */
class InventoryService extends ERPBaseService {

	public function warehouseList() {
		return M()->query("select id, code, name from t_warehouse order by code");
	}

	public function inventoryList($params) {
		$warehouseId = $params["warehouseId"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$barCode = $params["barCode"];
		$supplier = $params["supplier"];
		$category = $params["category"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$supplier_sql = "";
		if($supplier){
			$suppliers = M()->query("select * from t_supplier where code='$supplier' or name like '%$supplier%'");
			if($suppliers){
				$supplier = $suppliers[0];
				$supplier_sql = " and v.goods_id in (select goodsid from t_supplier_goods where supplierid = '".$supplier['id']."')";
			}
		}
		$category_sql = "";
		if($category){
			$cate = M("goods_category")->where(array("code"=>$category))->find();
			if($cate){
				$categoryId = $cate["id"];
				$category_array = array("'$categoryId'");
				//查询所有的子节点
				$map = array(
					"parent_id" => $categoryId
				);
				$db = M("goods_category", "t_");
				$list = $db->where($map)->select();
				foreach ($list as $key => $value) {
					$category_array[]  = "'".$value["id"]."'";
					$map = array(
						"parent_id" => $value["id"]
					);
					$list2 = $db->where($map)->select();
					foreach ($list2 as $key2 => $value2) {
						$category_array[]  = "'".$value2["id"]."'";
					}
				}
				//dump($category_array);
				$categorystr = implode(",", $category_array);
				$category_sql = " and ( g.category_id in ($categorystr) )";
			}
		}
		$db = M();
		$queryParams = array();
		$queryParams[] = $warehouseId;
		
		$sql = "select g.id, g.code, g.name, g.spec, g.barCode, g.bulk,g.sale_price as lastprice, u.name as unit_name,
				 v.in_count, v.in_price, v.in_money, v.out_count, v.out_price, v.out_money,
				 v.balance_count, v.balance_price, v.balance_money 
				from t_inventory v, t_goods g, t_goods_unit u
				where (v.warehouse_id = '%s') and (v.goods_id = g.id) and (g.unit_id = u.id) $supplier_sql $category_sql";
		if ($code) {
			$sql .= " and (g.code like '%s')";
			$queryParams[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s')";
			$queryParams[] = "%{$name}%";
			$queryParams[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParams[] = "%{$spec}%";
		}
		if($barCode){
			$sql .= " and (g.barCode like '%s')";
			$queryParams[] = "%{$barCode}%";
		}
		$sql .= " order by g.code
				limit %d, %d";
		$queryParams[] = $start;
		$queryParams[] = $limit;
		
		$data = $db->query($sql, $queryParams);
		
		$result = array();
		
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsId"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["barCode"] = $v["barcode"];
			$result[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
			$result[$i]["inCount"] = $v["in_count"];
			$result[$i]["inPrice"] = $v["lastprice"];
			$result[$i]["inMoney"] = $v["in_money"];
			$result[$i]["outCount"] = $v["out_count"];
			$result[$i]["outPrice"] = $v["out_price"];
			$result[$i]["outMoney"] = $v["out_money"];
			$result[$i]["balanceCount"] = $v["balance_count"];
			$result[$i]["balancePrice"] = $v["balance_price"];
			$result[$i]["balanceMoney"] = $v["lastprice"] * $v["balance_count"];
			$result[$i]["lastPrice"] = $v["lastprice"];
		}
		
		$queryParams = array();
		$queryParams[] = $warehouseId;
		$sql = "select count(*) as cnt 
				from t_inventory v, t_goods g, t_goods_unit u
				where (v.warehouse_id = '%s') and (v.goods_id = g.id) and (g.unit_id = u.id) $supplier_sql $category_sql ";
		if ($code) {
			$sql .= " and (g.code like '%s')";
			$queryParams[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s')";
			$queryParams[] = "%{$name}%";
			$queryParams[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParams[] = "%{$spec}%";
		}
		if($barCode){
			$sql .= " and (g.barCode like '%s')";
			$queryParams[] = "%{$barCode}%";
		}
		$data = $db->query($sql, $queryParams);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function inventoryDetailList($params) {
		$warehouseId = $params["warehouseId"];
		$goodsId = $params["goodsId"];
		$dtFrom = $params["dtFrom"];
		$dtTo = $params["dtTo"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		$sql = "select g.id, g.code, g.name, g.spec, g.bulk, g.lastBuyPrice as lastPrice, u.name as unit_name," . " v.in_count, v.in_price, v.in_money, v.out_count, v.out_price, v.out_money," . "v.balance_count, v.balance_price, v.balance_money," . " v.biz_date,  user.name as biz_user_name, v.ref_number, v.ref_type " . " from t_inventory_detail v, t_goods g, t_goods_unit u, t_user user" . " where v.warehouse_id = '%s' and v.goods_id = '%s' " . "	and v.goods_id = g.id and g.unit_id = u.id and v.biz_user_id = user.id " . "   and (v.biz_date between '%s' and '%s' ) " . " order by v.id " . " limit " . $start . ", " . $limit;
		$data = $db->query($sql, $warehouseId, $goodsId, $dtFrom, $dtTo);
		//dump(M()->getLastSql());
		$result = array();
		
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsId"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
			$result[$i]["inCount"] = $v["in_count"];
			$result[$i]["inPrice"] = $v["in_price"];
			$result[$i]["inMoney"] = $v["in_money"];
			$result[$i]["outCount"] = $v["out_count"];
			$result[$i]["outPrice"] = $v["out_price"];
			$result[$i]["outMoney"] = $v["out_money"];
			$result[$i]["balanceCount"] = $v["balance_count"];
			$result[$i]["balancePrice"] = $v["balance_price"];
			$result[$i]["balanceMoney"] = $v["balance_money"];
			$result[$i]["bizDT"] = date("Y-m-d", strtotime($v["biz_date"]));
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["refNumber"] = $v["ref_number"];
			$result[$i]["refType"] = $v["ref_type"];
			$result[$i]["lastPrice"] = $v["lastprice"];
		}
		
		$sql = "select count(*) as cnt from t_inventory_detail" . " where warehouse_id = '%s' and goods_id = '%s' " . "     and (biz_date between '%s' and '%s')";
		$data = $db->query($sql, $warehouseId, $goodsId, $dtFrom, $dtTo);
		
		return array(
				"details" => $result,
				"totalCount" => $data[0]["cnt"]
		);
	}
}
