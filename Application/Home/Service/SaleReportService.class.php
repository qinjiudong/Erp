<?php

namespace Home\Service;

/**
 * 销售报表Service
 *
 * @author 李静波
 */
class SaleReportService extends ERPBaseService {

	/**
	 * 销售日报表(按商品汇总) - 查询数据
	 */
	public function saleDayByGoodsQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		$huizong_type = $params["huizong_type"];
		$supplier_code = $params["supplier_code"];
		$category_code = $params["category_code"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		if($supplier_code){
			$map = array(
				"code" => $supplier_code
			);
			$supplier = M("supplier")->where($map)->find();
			$supplier_id = $supplier["id"];
		}
		$category_sql = "";
		if($category_code){
			$cate = M("goods_category")->where(array("code"=>$category_code))->find();
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
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and g.id = '$goods_id' ";
			$sql2 = " and g.id = '$goods_id' ";
		}
		if($supplier_id){
			$sql1 = " and wd.supplier_id = '$supplier_id'";
			$sql2 = " and wd.supplier_id = '$supplier_id' ";
			$supplier_sql_1 = " and s.id = '$supplier_id'";
			$supplier_sql_2 = " and s.id = '$supplier_id'";
		}
		$result = array();
		$db = M();
		//如果是按商品或者分类汇总
		if($huizong_type == 1){ //供应商
			/*
			$sql = "select s.code,s.name, sum(wd.apply_count) as total_count, sum(wd.apply_num) as total_weight, sum(wd.apply_price) as total_money, sum(reject_money) as reject_money , sum(apply_price_no_tax) as total_money_no_tax 
				,sum(inventory_money) as inventory_money 
				from t_supplier s, t_ws_bill_detail wd  where wd.supplier_id = s.id and wd.wsbill_id in (select ref from t_ws_bill where delivery_date = '$dt' and bill_status > 1) $sql1 
				group by wd.supplier_id
				limit %d, %d";
			*/
			$sql = "select s.name,s.code,s.id,i.buyprice,
				sum(i.sale_count) as total_count,

				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_supplier s, t_inout_day i 
				where i.supplier_id = s.id and i.biz_date = '$dt' $supplier_sql_1 group by i.supplier_id  
				limit %d, %d";
		} else {
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice, sum(wd.apply_count) as total_count, sum(wd.apply_num) as total_weight, sum(wd.apply_price) as total_money
				,sum(inventory_money) as inventory_money 
				from t_goods g, t_ws_bill_detail wd  where wd.goods_id = g.id and wd.wsbill_id in (select ref from t_ws_bill where delivery_date = '$dt' and bill_status > 1) $sql1 $category_sql
				group by wd.goods_id
				limit %d, %d";
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,s.code as supplier_code,s.name as supplier_name,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_goods g ,t_inout_day i 
				left join t_supplier s on i.supplier_id = s.id 
				where i.goods_id = g.id and i.biz_date = '$dt' $sql1 $category_sql group by i.goods_id 
				limit %d, %d";
		}
		
		//如果是导出
		if($_REQUEST["act"] == "export"){
			$start = 0;
			$limit = 10000;
		}
		$items = $db->query($sql,$start, $limit);
		$list[] = array('编码', '名称', '条码',
						'数量', '进价', '销售金额', '销售金额(无税)'
						);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"]  = $v["unit_name"];
			$result[$i]["buyMoney"]  = $v["buyprice"];
			$result[$i]["barCode"]   = $v["barcode"];
			$result[$i]["supplierCode"]  = $v["supplier_code"];
			$result[$i]["supplierName"]  = $v["supplier_name"];
			$selltax = $v["selltax"] / 100;
			$goodsId = $v["id"];
			/*
			$sql = "select sum(d.apply_price) as goods_money, sum(d.inventory_money) as inventory_money,
						sum(d.apply_count) as goods_count, sum(d.apply_num) as goods_weight 
					from t_ws_bill w, t_ws_bill_detail d
					where w.ref = d.wsbill_id and w.delivery_date = '%s' and d.goods_id = '%s' 
						and w.bill_status > 1";
			$data = $db->query($sql, $dt, $goodsId);
			*/
			$saleCount = $v["total_count"];
			if($v["bulk"] == 0 && $huizong_type != 1){
				$saleCount = $v["total_weight"];
			}
			if (! $saleCount) {
				$saleCount = 0;
			}
			$saleMoney = $v["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $v["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			$result[$i]["saleCount"] = $saleCount;
			$result[$i]["buyMoneyTotal"] = $v["inventory_money"] ? $v["inventory_money"] : $saleCount * $v["lastbuyprice"];
			//毛利
			$result[$i]["profit"] = $saleMoney - $result[$i]["buyMoneyTotal"];
			//毛利率
			$result[$i]["rate"] = ( round($result[$i]["profit"] / $saleMoney, 3) * 100 )."%";
			//计算税率
			if($huizong_type == 1){
				$result[$i]["saleMoneyNoTax"] = $v["total_money_no_tax"];
			} else {
				$result[$i]["saleMoneyNoTax"] = $v["total_money_no_tax"];
			}
			
			$list[] = array($v["code"],$v["name"],$v["barcode"],$saleCount,$v["lastbuyprice"],$saleMoney,$result[$i]["saleMoneyNoTax"] );
		}
		

		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '日报表导出数据.csv';
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
		if($huizong_type == 1){
			$sql = "select COUNT(DISTINCT wd.supplier_id) as cnt
				from t_goods g, t_ws_bill_detail wd  where wd.goods_id = g.id and wd.wsbill_id in (select ref from t_ws_bill where delivery_date = '$dt' and bill_status > 1) $sql1 ";
		} else {
			$sql = "select COUNT(DISTINCT wd.goods_id) as cnt
				from t_goods g, t_ws_bill_detail wd  where wd.goods_id = g.id and wd.wsbill_id in (select ref from t_ws_bill where delivery_date = '$dt' and bill_status > 1) $sql1 $category_sql ";
		}
		
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
				
		);
	}

	private function saleDaySummaryQueryData($params) {
		$dt = $params["dt"];
		
		$result = array();
		$result[0]["bizDT"] = $dt;
		$sql1 = "";
		$sql2 = "";
		$db = M();
		$huizong_type = $params["huizong_type"];
		$category_code = $params["category_code"];
		if($huizong_type == 2){
			//首先获取所有的品类
			$cate_list = M("goods_category")->where(array("parent_id" => 0))->select();
			foreach ($cate_list as $key => $value) {
				
			}
		} else {
			$cate_list = array(array("id" => 0));
		}

		$category_sql = "";
		if($category_code){
			$cate = M("goods_category")->where(array("code"=>$category_code))->find();
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

		$list[] = array('商品名称', '金额', '优惠金额', '运费',
						'0-金额','0-无税','13-金额','13-无税','17-金额','17-无税');
		$total_in_money = 0;
		foreach ($cate_list as $key => $value) {
			$cate_sql  = "";
			$total_sql = "";
			$goods_sql = "";
			//计算所有的进价
			$total_in_money = 0;
			if($huizong_type == 2){
				$cate_sql  = " and i.goods_id in (select id from t_goods where parent_cate_id = '".$value["id"]."') ";
				$total_sql = " and i.goods_id in (select id from t_goods where parent_cate_id = '".$value["id"]."') ";
				$goods_sql = " and g.parent_cate_id = '".$value["id"]."' ";
				$total_sql_ws = " and w.ref in (select wsbill_id from t_ws_bill_detail where goods_id in (select id from t_goods where parent_cate_id = '".$value["id"]."')) ";
			} else {
				if($category_sql){
					$total_sql = " and i.goods_id in (select id from t_goods where category_id in ($categorystr) ) ";
					$goods_sql = $category_sql;
					$total_sql_ws = " and w.ref in (select wsbill_id from t_ws_bill_detail where goods_id in (select id from t_goods where category_id in ($categorystr) )) ";
				}
				
				
			}
			//首先获取所有的销售和优惠金额
			$sql = "select sum(w.discount) as discount,sum(w.shipping_fee) as shipping_fee 
						from t_ws_bill w, t_ws_bill_detail d 
						where w.ref = d.wsbill_id and w.delivery_date = '%s'  
							and w.bill_status > 1 $total_sql_ws ";
			$data1 = $db->query($sql, $dt);
			//获取日报表中的所有销售
			$sql = "select i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_inout_day i 
				where i.biz_date = '$dt' $total_sql ";
			$data = $db->query($sql);
			$saleMoney = $data[0]["total_money"];
			$total_in_money = $data[0]["inventory_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$key]["name"] = $value["name"]?$value["name"]:"汇总";
			$result[$key]["saleMoney"] =  $saleMoney;
			$result[$key]["saleMoneyNoTax"] = $data[0]["total_money_no_tax"];
			$result[$key]["discount"] = $data1[0]["discount"];
			$result[$key]["shipping_fee"] = $data1[0]["shipping_fee"];
			//首先获取税率13的销售
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count * i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_goods g ,t_inout_day i 
				where i.goods_id = g.id and i.biz_date = '$dt' and g.selltax=13 $goods_sql ";
			$data = $db->query($sql, $dt);
			$saleMoney = $data[0]["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			//$total_in_money+=$saleInventoryMoney;
			$result[$key]["saleMoney_13"] = $saleMoney;
			$result[$key]["saleMoneyNoTax_13"] = round($saleMoney / 1.13, 2);
			//获取税率为17的销售
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_goods g ,t_inout_day i 
				where i.goods_id = g.id and i.biz_date = '$dt' and g.selltax=17 $goods_sql ";
			$data = $db->query($sql, $dt);
			$saleMoney = $data[0]["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			//$total_in_money+=$saleInventoryMoney;
			$result[$key]["saleMoney_17"] = $saleMoney;
			$result[$key]["saleMoneyNoTax_17"] = round($saleMoney / 1.17, 2);
			//获取税率为0的销售
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_goods g ,t_inout_day i 
				where i.goods_id = g.id and i.biz_date = '$dt' and (g.selltax=0 or ISNULL(g.selltax)) $goods_sql ";
			$data = $db->query($sql, $dt);
			$saleMoney = $data[0]["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			//$total_in_money+=$saleInventoryMoney;
			$result[$key]["saleMoney_0"] = $saleMoney;
			$result[$key]["saleMoneyNoTax_0"] = $saleMoney;
			$profit = $result[$key]["saleMoney"] - $total_in_money - $result[$key]["discount"];
			$result[$key]["profit"] = $profit;
			$result[$key]["rate"] = sprintf("%0.2f", $profit / $result[$key]["saleMoney"] * 100) . "%";
			$list[] = array($result[$key]["name"],$result[$key]["saleMoney"],$result[$key]["discount"],$result[$key]["saleMoney_0"],
							$result[$key]["saleMoneyNoTax_0"],$result[$key]["saleMoney_13"],$result[$key]["saleMoneyNoTax_13"],
							$result[$key]["saleMoney_17"],$result[$key]["saleMoneyNoTax_17"]
				);
		}
		
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '日报表汇总导出数据.csv';
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
		return $result;
	}

	/**
	 * 销售日报表(按商品汇总) - 查询汇总数据
	 */
	public function saleDayByGoodsSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleDaySummaryQueryData($params);
	}

	/**
	 * 销售日报表(按客户汇总) - 查询数据
	 */
	public function saleDayByCustomerQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$dt = $params["dt"];
		
		$result = array();
		
		$db = M();
		$sql = "select c.id, c.code, c.name
				from t_customer c
				where c.id in(
					select distinct w.customer_id
					from t_ws_bill w
					where w.delivery_date = '%s' and w.bill_status > 1
					union
					select distinct s.customer_id
					from t_sr_bill s
					where s.bizdt = '%s' and s.bill_status > 1
					)
				order by c.code
				limit %d, %d";
		$items = $db->query($sql, $dt, $dt, $start, $limit);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["customerCode"] = $v["code"];
			$result[$i]["customerName"] = $v["name"];
			
			$customerId = $v["id"];
			$sql = "select sum(w.sale_money) as goods_money, sum(w.inventory_money) as inventory_money
					from t_ws_bill w
					where w.bizdt = '%s' and w.customer_id = '%s'
						and w.bill_status = 1000";
			$data = $db->query($sql, $dt, $customerId);
			$saleMoney = $data[0]["goods_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			
			$sql = "select sum(s.rejection_sale_money) as rej_money,
						sum(s.inventory_money) as rej_inventory_money
					from t_sr_bill s
					where s.bizdt = '%s' and s.customer_id = '%s'
						and s.bill_status = 1000 ";
			$data = $db->query($sql, $dt, $customerId);
			$rejSaleMoney = $data[0]["rej_money"];
			if (! $rejSaleMoney) {
				$rejSaleMoney = 0;
			}
			$rejInventoryMoney = $data[0]["rej_inventory_money"];
			if (! $rejInventoryMoney) {
				$rejInventoryMoney = 0;
			}
			
			$result[$i]["rejMoney"] = $rejSaleMoney;
			
			$m = $saleMoney - $rejSaleMoney;
			$result[$i]["m"] = $m;
			$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
			$result[$i]["profit"] = $profit;
			if ($m > 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
		}
		
		$sql = "select count(*) as cnt
				from t_customer c
				where c.id in(
					select distinct w.customer_id
					from t_ws_bill w
					where w.delivery_date = '%s' and w.bill_status > 1
					union
					select distinct s.customer_id
					from t_sr_bill s
					where s.bizdt = '%s' and s.bill_status = 1000
					)";
		$data = $db->query($sql, $dt, $dt);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 销售日报表(按客户汇总) - 查询汇总数据
	 */
	public function saleDayByCustomerSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleDaySummaryQueryData($params);
	}

	/**
	 * 销售日报表(按仓库汇总) - 查询数据
	 */
	public function saleDayByWarehouseQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$dt = $params["dt"];
		
		$result = array();
		
		$db = M();
		$sql = "select w.id, w.code, w.name
				from t_warehouse w
				where w.id in(
					select distinct w.warehouse_id
					from t_ws_bill w
					where w.delivery_date = '%s' and w.bill_status > 1
					union
					select distinct s.warehouse_id
					from t_sr_bill s
					where s.bizdt = '%s' and s.bill_status = 1000
					)
				order by w.code
				limit %d, %d";
		$items = $db->query($sql, $dt, $dt, $start, $limit);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["warehouseCode"] = $v["code"];
			$result[$i]["warehouseName"] = $v["name"];
			
			$warehouseId = $v["id"];
			$sql = "select sum(w.sale_money) as goods_money, sum(w.inventory_money) as inventory_money
					from t_ws_bill w
					where w.delivery_date = '%s' and w.warehouse_id = '%s'
						and w.bill_status > 1";
			$data = $db->query($sql, $dt, $warehouseId);
			$saleMoney = $data[0]["goods_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			
			$sql = "select sum(s.rejection_sale_money) as rej_money,
						sum(s.inventory_money) as rej_inventory_money
					from t_sr_bill s
					where s.bizdt = '%s' and s.warehouse_id = '%s'
						and s.bill_status = 1000 ";
			$data = $db->query($sql, $dt, $warehouseId);
			$rejSaleMoney = $data[0]["rej_money"];
			if (! $rejSaleMoney) {
				$rejSaleMoney = 0;
			}
			$rejInventoryMoney = $data[0]["rej_inventory_money"];
			if (! $rejInventoryMoney) {
				$rejInventoryMoney = 0;
			}
			
			$result[$i]["rejMoney"] = $rejSaleMoney;
			
			$m = $saleMoney - $rejSaleMoney;
			$result[$i]["m"] = $m;
			$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
			$result[$i]["profit"] = $profit;
			if ($m > 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
		}
		
		$sql = "select count(*) as cnt
				from t_warehouse c
				where c.id in(
					select distinct w.warehouse_id
					from t_ws_bill w
					where w.delivery_date = '%s' and w.bill_status > 1
					union
					select distinct s.warehouse_id
					from t_sr_bill s
					where s.bizdt = '%s' and s.bill_status = 1000
					)";
		$data = $db->query($sql, $dt, $dt);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 销售日报表(按仓库汇总) - 查询汇总数据
	 */
	public function saleDayByWarehouseSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleDaySummaryQueryData($params);
	}

	/**
	 * 销售日报表(按业务员汇总) - 查询数据
	 */
	public function saleDayByBizuserQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$dt = $params["dt"];
		
		$result = array();
		
		$db = M();
		$sql = "select u.id, u.org_code, u.name
				from t_user u
				where u.id in(
					select distinct w.biz_user_id
					from t_ws_bill w
					where w.delivery_date = '%s' and w.bill_status > 1
					union
					select distinct s.biz_user_id
					from t_sr_bill s
					where s.bizdt = '%s' and s.bill_status = 1000
					)
				order by u.org_code
				limit %d, %d";
		$items = $db->query($sql, $dt, $dt, $start, $limit);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["userName"] = $v["name"];
			$result[$i]["userCode"] = $v["org_code"];
			
			$userId = $v["id"];
			$sql = "select sum(w.sale_money) as goods_money, sum(w.inventory_money) as inventory_money
					from t_ws_bill w
					where w.delivery_date = '%s' and w.biz_user_id = '%s'
						and w.bill_status > 1";
			$data = $db->query($sql, $dt, $userId);
			$saleMoney = $data[0]["goods_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			
			$sql = "select sum(s.rejection_sale_money) as rej_money,
						sum(s.inventory_money) as rej_inventory_money
					from t_sr_bill s
					where s.bizdt = '%s' and s.biz_user_id = '%s'
						and s.bill_status = 1000 ";
			$data = $db->query($sql, $dt, $userId);
			$rejSaleMoney = $data[0]["rej_money"];
			if (! $rejSaleMoney) {
				$rejSaleMoney = 0;
			}
			$rejInventoryMoney = $data[0]["rej_inventory_money"];
			if (! $rejInventoryMoney) {
				$rejInventoryMoney = 0;
			}
			
			$result[$i]["rejMoney"] = $rejSaleMoney;
			
			$m = $saleMoney - $rejSaleMoney;
			$result[$i]["m"] = $m;
			$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
			$result[$i]["profit"] = $profit;
			if ($m > 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
		}
		
		$sql = "select count(*) as cnt
				from t_user u
				where u.id in(
					select distinct w.biz_user_id
					from t_ws_bill w
					where w.delivery_date = '%s' and w.bill_status > 1
					union
					select distinct s.biz_user_id
					from t_sr_bill s
					where s.bizdt = '%s' and s.bill_status = 1000
					)";
		$data = $db->query($sql, $dt, $dt);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 销售日报表(按业务员汇总) - 查询汇总数据
	 */
	public function saleDayByBizuserSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleDaySummaryQueryData($params);
	}

	/**
	 * 销售月报表(按商品汇总) - 查询数据
	 */
	public function saleMonthByGoodsQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$huizong_type = $params["huizong_type"];
		$supplier_code = $params["supplier_code"];
		$year = $params["year"];
		$month = $params["month"];
		if(!$month){
			$month = $_REQUEST["month"];
		}
		if(!$year){
			$year = $_REQUEST["year"];
		}
		$result = array();
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		$category_code = $params["category_code"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		if($supplier_code){
			$map = array(
				"code" => $supplier_code
			);
			$supplier = M("supplier")->where($map)->find();
			$supplier_id = $supplier["id"];
		}
		$category_sql = "";
		if($category_code){
			$cate = M("goods_category")->where(array("code"=>$category_code))->find();
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
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and g.id = '$goods_id' ";
			$sql2 = " and g.id = '$goods_id' ";
		}
		if($supplier_id){
			$sql1 = " and supplier_id = '$supplier_id'";
			$sql2 = " and supplier_id = '$supplier_id' ";
		}
		$db = M();
		if($huizong_type == 1){ //供应商
			$sql = "select s.code,s.name, sum(wd.apply_count) as total_count, sum(wd.apply_num) as total_weight, sum(wd.apply_price) as total_money, sum(reject_money) as reject_money , sum(apply_price_no_tax) as total_money_no_tax 
				,sum(inventory_money) as inventory_money 
				from t_supplier s, t_ws_bill_detail wd  where wd.supplier_id = s.id and wd.wsbill_id in (select ref from t_ws_bill where year(delivery_date) = $year and month(delivery_date) = $month and bill_status > 1) $sql1 
				group by wd.supplier_id
				limit %d, %d";
			$sql = "select s.name,s.code,s.id,i.buyprice,
				sum(i.sale_count) as total_count,

				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_supplier s, t_inout_day i 
				where s.id = i.supplier_id and year(i.biz_date) = $year and month(i.biz_date) = $month $sql1 group by i.supplier_id  
				limit %d, %d";
		} else {
			$sql = "select g.code,g.name,g.spec,g.selltax,g.id,g.bulk ,g.lastbuyprice, sum(wd.apply_count) as total_count, sum(wd.apply_num) as total_weight, sum(wd.apply_price) as total_money
					from t_goods g, t_ws_bill_detail wd  where wd.goods_id = g.id and wd.wsbill_id in (select ref from t_ws_bill where year(delivery_date) = $year and month(delivery_date) = $month  and bill_status > 1) $sql1 $category_sql 
					group by wd.goods_id
					limit %d, %d";
			$sql = "select g.code,g.name,g.spec,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,s.code as supplier_code,s.name as supplier_name,
					sum(i.sale_count) as total_count,

					sum(i.sale_count) as total_weight,
					sum(i.sale_count *i.buyprice) as inventory_money,
					sum(i.sale_money) as total_money,
					sum(i.sale_money_no_tax) as total_money_no_tax 
					from t_inout_day i 
					left join t_goods g on g.id = i.goods_id 
					left join t_supplier s on i.supplier_id = s.id 
					where year(i.biz_date) = $year and month(i.biz_date) = $month $sql1 $category_sql group by i.goods_id
					limit %d, %d";
		}
		//如果是导出
		if($_REQUEST["act"] == "export"){
			$start = 0;
			$limit = 10000;
		}
//        print_r($sql);echo '</br>';
		$items = $db->query($sql,$start, $limit);
		$list[] = array('编码', '名称', '条码','供应商编码','供应商名称',
						'数量', '进价', '销售金额', '销售金额(无税)'
						);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"]  = $v["unit_name"];
			$result[$i]["buyMoney"]  = $v["buyprice"];
			$result[$i]["supplierCode"]  = $v["supplier_code"];
			$result[$i]["supplierName"]  = $v["supplier_name"];
			$selltax = $v["selltax"] / 100;
			$goodsId = $v["id"];
			$saleCount = $v["total_count"];
			if($v["bulk"] == 0 && $huizong_type != 1){
				$saleCount = $v["total_weight"];
			}
			if (! $saleCount) {
				$saleCount = 0;
			}
			$saleMoney = $v["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $v["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			$result[$i]["saleCount"] = $saleCount;
			$result[$i]["buyMoneyTotal"] = $v["inventory_money"] ? $v["inventory_money"] : $saleCount * $v["lastbuyprice"];
			//毛利
			$result[$i]["profit"] = $saleMoney - $result[$i]["buyMoneyTotal"];
			//毛利率
			$result[$i]["rate"] = ( round($result[$i]["profit"] / $saleMoney, 3) * 100 )."%";
			//计算税率
			if($huizong_type == 1){
				$result[$i]["saleMoneyNoTax"] = $v["total_money_no_tax"];
			} else {
				$result[$i]["saleMoneyNoTax"] = $v["total_money_no_tax"];
			}
			if($saleCount == 0){
				$result[$i]["saleMoneyNoTax"] = 0;
				$result[$i]["saleMoney"] = 0;
				$saleMoney = 0;
				
			}
			$list[] = array($v["code"],$v["name"],$v["barcode"],$v["supplier_code"],$v["supplier_name"],$saleCount,$v["lastbuyprice"],$saleMoney,$result[$i]["saleMoneyNoTax"] );
		}

		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '月报表导出数据.csv';
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
		if($huizong_type == 1){
			$sql = "select COUNT(DISTINCT wd.supplier_id) as cnt
				from t_goods g, t_ws_bill_detail wd  where wd.goods_id = g.id and wd.wsbill_id in (select ref from t_ws_bill where year(delivery_date) = $year and month(delivery_date) = $month and bill_status > 1) $sql1 ";
		} else {
			$sql = "select COUNT(DISTINCT wd.goods_id) as cnt
				from t_goods g, t_ws_bill_detail wd  where wd.goods_id = g.id and wd.wsbill_id in (select ref from t_ws_bill where year(delivery_date) = $year and month(delivery_date) = $month and bill_status > 1) $sql1 $category_sql ";
		}
		
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	private function saleMonthSummaryQueryData($params) {
		$year = $params["year"];
		$month = $params["month"];
		if(!$month){
			$month = $_REQUEST["month"];
		}
		if(!$year){
			$year = $_REQUEST["year"];
		}
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		$category_code = $params["category_code"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and d.goods_id = '$goods_id' ";
			$sql2 = " and d.goods_id = '$goods_id' ";
		}
		$result = array();
		if ($month < 10) {
			$result[0]["bizDT"] = "$year-0$month";
		} else {
			$result[0]["bizDT"] = "$year-$month";
		}
		
		$db = M();
		$huizong_type = $params["huizong_type"];
		if($huizong_type == 2){
			//首先获取所有的品类
			$cate_list = M("goods_category")->where(array("parent_id" => 0))->select();
			foreach ($cate_list as $key => $value) {
				
			}
		} else {
			$cate_list = array(array("id" => 0));
		}
		$category_sql = "";
		if($category_code){
			$cate = M("goods_category")->where(array("code"=>$category_code))->find();
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
		$sql = "select sum(d.apply_price) as goods_money, sum(w.discount) as discount
					from t_ws_bill w, t_ws_bill_detail d
					where w.ref = d.wsbill_id and year(w.delivery_date) = $year and month(w.delivery_date) = $month
						and w.bill_status > 1";
		$list[] = array('商品名称', '金额', '优惠金额', '运费',
						'0-金额','0-无税','13-金额','13-无税','17-金额','17-无税');
		foreach ($cate_list as $key => $value) {
			$cate_sql  = "";
			$total_sql = "";
			$goods_sql = "";
			$total_in_money = 0;
			$total_sql_ws = "";
			if($huizong_type == 2){
				$cate_sql  = " and i.goods_id in (select id from t_goods where parent_cate_id = '".$value["id"]."') ";
				$total_sql = " and i.goods_id in (select id from t_goods where parent_cate_id = '".$value["id"]."') ";
				$goods_sql = " and g.parent_cate_id = '".$value["id"]."' ";
				$total_sql_ws = " and w.ref in (select d1.wsbill_id from t_ws_bill_detail d1, t_goods g1  where d1.goods_id = g1.id and g1.parent_cate_id = '".$value["id"]."') ";
			} else {
				if($category_sql){
					$total_sql = " and i.goods_id in (select id from t_goods where category_id in ($categorystr) ) ";
					$goods_sql = $category_sql;
					$total_sql_ws = " and w.ref in (select d1.wsbill_id from t_ws_bill_detail d1, t_goods g1  where d1.goods_id = g1.id and g1.category_id in ($categorystr) ) ";
				}
				if(!$total_sql_ws){
					$total_sql_ws = " and 1=1";
				}
				
			}
			//首先获取所有的销售
			$sql = "select sum(w.discount) as discount,sum(w.shipping_fee) as shipping_fee 
						from t_ws_bill w 
						where year(w.delivery_date) = $year and month(w.delivery_date) = $month
							and w.bill_status > 1 $total_sql_ws  ";
			$data1 = $db->query($sql, $dt);
			//获取月报表中的数据
			$sql = "select i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_inout_day i 
				where year(i.biz_date) = $year and month(i.biz_date) = $month $total_sql ";
			$data = $db->query($sql);
			$saleMoney = $data[0]["total_money"];
			$total_in_money = $data[0]["inventory_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$key]["name"] = $value["name"]?$value["name"]:"汇总";
			$result[$key]["saleMoney"] =  $saleMoney;
			$result[$key]["saleMoneyNoTax"] = $data[0]["total_money_no_tax"];
			$result[$key]["discount"] = $data1[0]["discount"];
			$result[$key]["shipping_fee"] = $data1[0]["shipping_fee"];
			//首先获取税率13的销售
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_goods g ,t_inout_day i 
				where i.goods_id = g.id and year(i.biz_date) = $year and month(i.biz_date) = $month and g.selltax=13 $goods_sql ";
			$data = $db->query($sql, $dt);
			$saleMoney = $data[0]["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$key]["saleMoney_13"] = $saleMoney;
			$result[$key]["saleMoneyNoTax_13"] = round($saleMoney / 1.13,2);
			//获取税率为17的销售
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_goods g ,t_inout_day i 
				where i.goods_id = g.id and year(i.biz_date) = $year and month(i.biz_date) = $month and g.selltax=17 $goods_sql ";
			$data = $db->query($sql, $dt);
			$saleMoney = $data[0]["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$key]["saleMoney_17"] = $saleMoney;
			$result[$key]["saleMoneyNoTax_17"] = round($saleMoney / 1.17, 2);
			//获取税率为0的销售
			$sql = "select g.code,g.name,g.spec,g.barcode,g.selltax,g.id,g.bulk ,g.lastbuyprice,i.buyprice,
				sum(i.sale_count) as total_count,
				sum(i.sale_count) as total_weight,
				sum(i.sale_count *i.buyprice) as inventory_money,
				sum(i.sale_money) as total_money,
				sum(i.sale_money_no_tax) as total_money_no_tax 
				from t_goods g ,t_inout_day i 
				where i.goods_id = g.id and year(i.biz_date) = $year and month(i.biz_date) = $month and (g.selltax=0 or ISNULL(g.selltax)) $goods_sql ";
			$data = $db->query($sql, $dt);
			$saleMoney = $data[0]["total_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}

			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}

			$result[$key]["saleMoney_0"] = $saleMoney;
			$result[$key]["saleMoneyNoTax_0"] = $saleMoney;
			$profit = $result[$key]["saleMoney"] - $total_in_money - $result[$key]["discount"];
			$result[$key]["profit"] = $profit;
			$result[$key]["rate"] = sprintf("%0.2f", $profit / $result[$key]["saleMoney"] * 100) . "%";
			if ($m > 0) {
				$result[$key]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
			$list[] = array($result[$key]["name"],$result[$key]["saleMoney"],$result[$key]["discount"],$result[$key]["saleMoney_0"],
							$result[$key]["saleMoneyNoTax_0"],$result[$key]["saleMoney_13"],$result[$key]["saleMoneyNoTax_13"],
							$result[$key]["saleMoney_17"],$result[$key]["saleMoneyNoTax_17"]
				);
		}
		
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '月报表汇总导出数据.csv';
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

		return $result;
	}

	/**
	 * 销售月报表(按商品汇总) - 查询汇总数据
	 */
	public function saleMonthByGoodsSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleMonthSummaryQueryData($params);
	}

	/**
	 * 销售月报表(按客户汇总) - 查询数据
	 */
	public function saleMonthByCustomerQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$year = $params["year"];
		$month = $params["month"];
		
		$result = array();
		
		$db = M();
		$sql = "select c.id, c.code, c.name
				from t_customer c
				where c.id in(
					select distinct w.customer_id
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.bill_status > 1
					union
					select distinct s.customer_id
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d 
						and s.bill_status = 1000
					)
				order by c.code
				limit %d, %d";
		$items = $db->query($sql, $year, $month, $year, $month, $start, $limit);
		foreach ( $items as $i => $v ) {
			if ($month < 10) {
				$result[$i]["bizDT"] = "$year-0$month";
			} else {
				$result[$i]["bizDT"] = "$year-$month";
			}
			
			$result[$i]["customerCode"] = $v["code"];
			$result[$i]["customerName"] = $v["name"];
			
			$customerId = $v["id"];
			$sql = "select sum(w.sale_money) as goods_money, sum(w.inventory_money) as inventory_money
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d 
						and w.customer_id = '%s'
						and w.bill_status > 1";
			$data = $db->query($sql, $year, $month, $customerId);
			$saleMoney = $data[0]["goods_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			
			$sql = "select sum(s.rejection_sale_money) as rej_money,
						sum(s.inventory_money) as rej_inventory_money
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d 
						and s.customer_id = '%s'
						and s.bill_status = 1000 ";
			$data = $db->query($sql, $year, $month, $customerId);
			$rejSaleMoney = $data[0]["rej_money"];
			if (! $rejSaleMoney) {
				$rejSaleMoney = 0;
			}
			$rejInventoryMoney = $data[0]["rej_inventory_money"];
			if (! $rejInventoryMoney) {
				$rejInventoryMoney = 0;
			}
			
			$result[$i]["rejMoney"] = $rejSaleMoney;
			
			$m = $saleMoney - $rejSaleMoney;
			$result[$i]["m"] = $m;
			$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
			$result[$i]["profit"] = $profit;
			if ($m > 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
		}
		
		$sql = "select count(*) as cnt
				from t_customer c
				where c.id in(
					select distinct w.customer_id
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.bill_status > 1
					union
					select distinct s.customer_id
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d 
						and s.bill_status = 1000
					)
				";
		$data = $db->query($sql, $year, $month, $year, $month);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 销售月报表(按客户汇总) - 查询汇总数据
	 */
	public function saleMonthByCustomerSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleMonthSummaryQueryData($params);
	}

	/**
	 * 销售月报表(按仓库汇总) - 查询数据
	 */
	public function saleMonthByWarehouseQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$year = $params["year"];
		$month = $params["month"];
		
		$result = array();
		
		$db = M();
		$sql = "select w.id, w.code, w.name
				from t_warehouse w
				where w.id in(
					select distinct w.warehouse_id
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.bill_status > 1
					union
					select distinct s.warehouse_id
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d
						and s.bill_status = 1000
					)
				order by w.code
				limit %d, %d";
		$items = $db->query($sql, $year, $month, $year, $month, $start, $limit);
		foreach ( $items as $i => $v ) {
			if ($month < 10) {
				$result[$i]["bizDT"] = "$year-0$month";
			} else {
				$result[$i]["bizDT"] = "$year-$month";
			}
			
			$result[$i]["warehouseCode"] = $v["code"];
			$result[$i]["warehouseName"] = $v["name"];
			
			$warehouseId = $v["id"];
			$sql = "select sum(w.sale_money) as goods_money, sum(w.inventory_money) as inventory_money
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.warehouse_id = '%s'
						and w.bill_status > 1";
			$data = $db->query($sql, $year, $month, $warehouseId);
			$saleMoney = $data[0]["goods_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			
			$sql = "select sum(s.rejection_sale_money) as rej_money,
						sum(s.inventory_money) as rej_inventory_money
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d
						and s.warehouse_id = '%s'
						and s.bill_status = 1000 ";
			$data = $db->query($sql, $year, $month, $warehouseId);
			$rejSaleMoney = $data[0]["rej_money"];
			if (! $rejSaleMoney) {
				$rejSaleMoney = 0;
			}
			$rejInventoryMoney = $data[0]["rej_inventory_money"];
			if (! $rejInventoryMoney) {
				$rejInventoryMoney = 0;
			}
			
			$result[$i]["rejMoney"] = $rejSaleMoney;
			
			$m = $saleMoney - $rejSaleMoney;
			$result[$i]["m"] = $m;
			$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
			$result[$i]["profit"] = $profit;
			if ($m > 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
		}
		
		$sql = "select count(*) as cnt
				from t_warehouse w
				where w.id in(
					select distinct w.warehouse_id
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.bill_status > 1
					union
					select distinct s.warehouse_id
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d
						and s.bill_status = 1000
					)
				";
		$data = $db->query($sql, $year, $month, $year, $month);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 销售月报表(按仓库汇总) - 查询汇总数据
	 */
	public function saleMonthByWarehouseSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleMonthSummaryQueryData($params);
	}

	/**
	 * 销售月报表(按业务员汇总) - 查询数据
	 */
	public function saleMonthByBizuserQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$year = $params["year"];
		$month = $params["month"];
		
		$result = array();
		
		$db = M();
		$sql = "select u.id, u.org_code as code, u.name
				from t_user u
				where u.id in(
					select distinct w.biz_user_id
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.bill_status > 1
					union
					select distinct s.biz_user_id
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d
						and s.bill_status = 1000
					)
				order by u.org_code
				limit %d, %d";
		$items = $db->query($sql, $year, $month, $year, $month, $start, $limit);
		foreach ( $items as $i => $v ) {
			if ($month < 10) {
				$result[$i]["bizDT"] = "$year-0$month";
			} else {
				$result[$i]["bizDT"] = "$year-$month";
			}
			
			$result[$i]["userCode"] = $v["code"];
			$result[$i]["userName"] = $v["name"];
			
			$userId = $v["id"];
			$sql = "select sum(w.sale_money) as goods_money, sum(w.inventory_money) as inventory_money
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.biz_user_id = '%s'
						and w.bill_status > 1";
			$data = $db->query($sql, $year, $month, $userId);
			$saleMoney = $data[0]["goods_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			
			$sql = "select sum(s.rejection_sale_money) as rej_money,
						sum(s.inventory_money) as rej_inventory_money
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d
						and s.biz_user_id = '%s'
						and s.bill_status = 1000 ";
			$data = $db->query($sql, $year, $month, $userId);
			$rejSaleMoney = $data[0]["rej_money"];
			if (! $rejSaleMoney) {
				$rejSaleMoney = 0;
			}
			$rejInventoryMoney = $data[0]["rej_inventory_money"];
			if (! $rejInventoryMoney) {
				$rejInventoryMoney = 0;
			}
			
			$result[$i]["rejMoney"] = $rejSaleMoney;
			
			$m = $saleMoney - $rejSaleMoney;
			$result[$i]["m"] = $m;
			$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
			$result[$i]["profit"] = $profit;
			if ($m > 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
		}
		
		$sql = "select count(*) as cnt
				from t_user u
				where u.id in(
					select distinct w.biz_user_id
					from t_ws_bill w
					where year(w.delivery_date) = %d and month(w.delivery_date) = %d
						and w.bill_status > 1
					union
					select distinct s.biz_user_id
					from t_sr_bill s
					where year(s.bizdt) = %d and month(s.bizdt) = %d
						and s.bill_status = 1000
					)
				";
		$data = $db->query($sql, $year, $month, $year, $month);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 销售月报表(按业务员汇总) - 查询汇总数据
	 */
	public function saleMonthByBizuserSummaryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		return $this->saleMonthSummaryQueryData($params);
	}

		/**
	 * 销售月报表(按商品汇总) - 查询数据
	 */
	public function saleReportByGoodsQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$begin = $params["begin"];
		$end = $params["end"];
		
		$result = array();
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		$sql1 = "";
		$sql2 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$time_sql_1 = " and w.delivery_date >= '".$begin."' ";
			$time_sql_2 = " and s.bizdt >= '".$begin."' ";
		}
		if($end){
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and s.bizdt <= '".$end."' ";
		}
		if($goods_id){
			$sql1 .= " and d.goods_id = '$goods_id' ";
			$sql2 .= " and d.goods_id = '$goods_id' ";
		}
		$db = M();
		$sql = "select g.id, g.code, g.name, g.spec, g.bulk, u.name as unit_name
				from t_goods g, t_goods_unit u
				where g.unit_id = u.id and g.id in(
					select distinct d.goods_id
					from t_ws_bill w, t_ws_bill_detail d
					where w.ref = d.wsbill_id $time_sql_1 
						and w.bill_status > 1 $sql1 
					union
					select distinct d.goods_id
					from t_sr_bill s, t_sr_bill_detail d
					where s.id = d.srbill_id $time_sql_2  
						and s.bill_status = 1000 $sql2
					) 
				limit %d, %d";
		$items = $db->query($sql, $start, $limit);
		
		foreach ( $items as $i => $v ) {
			//$result[$i]["bizDT"] = "$year-0$month";
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"]  = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"]  = "kg";
			}
			$goodsId = $v["id"];
			$sql = "select sum(d.apply_price) as goods_money, sum(d.inventory_money) as inventory_money,
						sum(d.goods_count) as goods_count ,sum(d.apply_num) as goods_weight 
					from t_ws_bill w, t_ws_bill_detail d
					where w.ref = d.wsbill_id $time_sql_1  
						and d.goods_id = '%s'
						and w.bill_status > 1";
			$data = $db->query($sql, $goodsId);
			$saleCount = $data[0]["goods_count"];
			if (! $saleCount) {
				$saleCount = 0;
			}
			$saleMoney = $data[0]["goods_money"];
			if (! $saleMoney) {
				$saleMoney = 0;
			}
			$weightCount = $data[0]["goods_weight"];
			if(! $weightCount){
				$weightCount = 0;
			}
			$saleInventoryMoney = $data[0]["inventory_money"];
			if (! $saleInventoryMoney) {
				$saleInventoryMoney = 0;
			}
			$result[$i]["saleMoney"] = $saleMoney;
			$result[$i]["saleCount"] = $saleCount;
			if($v["bulk"] == 0){
				$result[$i]["saleCount"] = $weightCount;
				$saleCount = $weightCount;
			}
			$sql = "select sum(d.rejection_goods_count) as rej_count,
						sum(d.rejection_sale_money) as rej_money,
						sum(d.inventory_money) as rej_inventory_money
					from t_sr_bill s, t_sr_bill_detail d
					where s.id = d.srbill_id $time_sql_2  
						and d.goods_id = '%s'
						and s.bill_status = 1000 ";

			$data = $db->query($sql, $goodsId);
			$rejCount = $data[0]["rej_count"];
			if (! $rejCount) {
				$rejCount = 0;
			}
			$rejSaleMoney = $data[0]["rej_money"];
			if (! $rejSaleMoney) {
				$rejSaleMoney = 0;
			}
			$rejInventoryMoney = $data[0]["rej_inventory_money"];
			if (! $rejInventoryMoney) {
				$rejInventoryMoney = 0;
			}
			
			$result[$i]["rejCount"] = $rejCount;
			$result[$i]["rejMoney"] = $rejSaleMoney;
			
			$c = $saleCount - $rejCount;
			$m = $saleMoney - $rejSaleMoney;
			$result[$i]["c"] = $c;
			$result[$i]["m"] = $m;
			$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
			$result[$i]["profit"] = $profit;
			if ($m > 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
			}
		}

		/*
		$sql = "select count(*) as cnt
				from t_goods g
				where g.id in(
					select distinct d.goods_id
					from t_ws_bill w, t_ws_bill_detail d
					where w.ref = d.wsbill_id $time_sql_1 and w.bill_status > 1 $sql1 
					union
					select distinct d.goods_id
					from t_sr_bill s, t_sr_bill_detail d
					where s.id = d.srbill_id $time_sql_2 and s.bill_status = 1000 $sql1 
					)
				";
		*/
		$sql = "select count(*) as cnt from t_ws_bill_detail w where 1 = 1 $time_sql_1 ";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}


	public function saleReportByGoodsSummaryQueryData($params) {
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		$begin = $params["begin"];
		$end = $params["end"];
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and d.goods_id = '$goods_id' ";
			$sql2 = " and d.goods_id = '$goods_id' ";
		}
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$time_sql_1 .= " and w.delivery_date >= '".$begin."' ";
			$time_sql_2 .= " and s.bizdt >= '".$begin."' ";
		}
		if($end){
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and s.bizdt <= '".$end."' ";
		}
		$result = array();
		$db = M();
		$sql = "select sum(d.apply_price) as goods_money, sum(d.inventory_money) as inventory_money
					from t_ws_bill w, t_ws_bill_detail d
					where w.ref = d.wsbill_id $time_sql_1 
						and w.bill_status > 1 $sql1 ";
		$data = $db->query($sql, $year, $month);
		$saleMoney = $data[0]["goods_money"];
		if (! $saleMoney) {
			$saleMoney = 0;
		}
		$saleInventoryMoney = $data[0]["inventory_money"];
		if (! $saleInventoryMoney) {
			$saleInventoryMoney = 0;
		}
		$result[0]["saleMoney"] = $saleMoney;
		
		$sql = "select  sum(d.rejection_sale_money) as rej_money,
						sum(d.inventory_money) as rej_inventory_money
					from t_sr_bill s, t_sr_bill_detail d
					where s.id = d.srbill_id $time_sql_2 
						and s.bill_status = 1000 $sql2 ";
		$data = $db->query($sql);
		$rejSaleMoney = $data[0]["rej_money"];
		if (! $rejSaleMoney) {
			$rejSaleMoney = 0;
		}
		$rejInventoryMoney = $data[0]["rej_inventory_money"];
		if (! $rejInventoryMoney) {
			$rejInventoryMoney = 0;
		}
		
		$result[0]["rejMoney"] = $rejSaleMoney;
		
		$m = $saleMoney - $rejSaleMoney;
		$result[0]["m"] = $m;
		$profit = $saleMoney - $rejSaleMoney - $saleInventoryMoney + $rejInventoryMoney;
		$result[0]["profit"] = $profit;
		if ($m > 0) {
			$result[0]["rate"] = sprintf("%0.2f", $profit / $m * 100) . "%";
		}
		
		return $result;
	}

}