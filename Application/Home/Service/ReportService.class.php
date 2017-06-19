<?php

namespace Home\Service;

/**
 * 新报表Service
 *
 * @author dubin
 */
class ReportService extends ERPBaseService {

	/**
	 * 进销存报表
	 */
	public function inOutDataByGoods($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];

		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and d.goods_id = '$goods_id' ";
			$sql2 = " and d.goods_id = '$goods_id' ";
		}
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and w.delivery_date >= '".$begin."' ";
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		$supplier_code = $params["supplier_code"];
		$category_code  = $params["cate_code"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		if($supplier_code){
			$supplier = M("supplier")->where(array("code"=>$supplier_code))->find();
			if($supplier){
				$supplier_id = $supplier["id"];
			}
		}
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and i.goods_id = '$goods_id' ";
			$sql2 = " and i.goods_id = '$goods_id' ";
		}
		$supplier_sql = "";
		if($supplier_id){
			$supplier_sql = " and i.supplier_id = '$supplier_id' ";
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
		$result = array();
		$db = M();
		$sql = "select g.id, g.lastbuyprice, g.bulk, g.mode, g.sale_price, g.gross, g.buytax, g.selltax, s.code as supplier_code,s.name as supplier_name,c.name as category_name,
		g.code, g.name, g.spec, i.buyprice as buyprice, (i.balance_count - i.ia_count + i.sale_count + i.sun_count - i.yi_count - i.pan_in_count + i.pan_out_count) as balance_count,
				sum(i.ia_count) as total_ia_count,
				sum(i.ia_money) as total_ia_money,
				sum(i.ia_money_no_tax) as total_ia_money_no_tax,
				sum(i.sale_count) as total_sale_count,
				sum(i.sale_money) as total_sale_money,
				sum(i.sale_money_no_tax) as total_sale_money_no_tax,
				sum(i.sun_count) as total_sun_count,
				sum(i.sun_money) as total_sun_money,
				sum(i.sun_money_no_tax) as total_sun_money_no_tax,
				sum(i.yi_count) as total_yi_count,
				sum(i.yi_money) as total_yi_money,
				sum(i.yi_money_no_tax) as total_yi_money_no_tax,
				sum(i.pan_in_count) as total_pan_in_count,
				sum(i.pan_in_money) as total_pan_in_money,
				sum(i.pan_in_money_no_tax) as total_pan_in_money_no_tax,
				sum(i.pan_out_count) as total_pan_out_count,
				sum(i.pan_out_money) as total_pan_out_money,
				sum(i.pan_out_money_no_tax) as total_pan_out_money_no_tax 
				from t_goods g 
				left join t_inout_day i on g.id=i.goods_id 
				left join t_supplier s on s.id = i.supplier_id
				left join t_goods_category c on g.parent_cate_id = c.id 
				where g.id = i.goods_id  $time_sql_2 $sql2 $supplier_sql $category_sql group by i.goods_id 
				limit %d, %d";
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$items = $db->query($sql, $start, $limit);
		//dump($db->getLastSql());
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["supplierCode"]  = $v["supplier_code"];
			$result[$i]["supplierName"]  = $v["supplier_name"];
			$result[$i]["categoryName"]  = $v["category_name"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["gross"] = $v["gross"];
			$lastPrice = $v["lastbuyprice"];
			$buyprice  = $v["buyprice"];
			$salePrice = $v["sale_price"];
			$buytax = $v["buytax"] / 100;
			$selltax = $v["selltax"] / 100;
			$result[$i]["lastPrice"] = $buyprice;
			//获取进货数量
			$result[$i]["total_in_count"] = $v["total_ia_count"];
			//获取进货金额
			$result[$i]["total_in_money"] = $v["total_ia_money"];
			$result[$i]["total_in_money_no_tax"] = $v["total_ia_money_no_tax"];
			//升溢数量
			$result[$i]["total_in_count_yi"] = $v["total_yi_count"];
			//获取溢金额
			$result[$i]["total_in_money_yi"] = $v["total_yi_money"];
			$result[$i]["total_in_money_yi_no_tax"] = $v["total_yi_money_no_tax"];
			//获取盘升金额
			$result[$i]["total_in_count_pan"] = $v["total_pan_in_count"];
			//获取盘溢金额
			$result[$i]["total_in_money_pan"] = $v["total_pan_in_money"];
			$result[$i]["total_in_money_pan_no_tax"] = $v["total_pan_in_money_no_tax"];
			//获取销售数量
			/*
			$sql = "select sum(i.apply_count) as total_out_count , sum(i.apply_num) as total_out_num, sum(i.apply_price) as total_out_money , sum(i.reject_money) as reject_money, sum(i.apply_price_no_tax) as total_out_money_no_tax
				from t_ws_bill_detail i 
				where wsbill_id in (select ref from t_ws_bill w where 1 $time_sql_1)  and i.goods_id = '".$v['id']."'";
			$data = $db->query($sql);
			$total_out_count = $v["bulk"] == 0 ? $data[0]["total_out_num"] : $data[0]["total_out_count"];
			*/
			$result[$i]["total_out_count_sale"] = $v["total_sale_count"];
			//获取销售金额
			$salePrice = $v["sale_price"];
			$result[$i]["total_out_money_sale"] = $v["total_sale_money"];
			//不含税销售金额
			$result[$i]["total_out_money_sale_without_tax"] =  $v["total_sale_money_no_tax"];
			//销售成本
			$result[$i]["total_out_money_sale_cost"] = $v["total_sale_count"] * $buyprice;
			//销售毛利
//			$result[$i]["total_out_money_sale_profit"] = $result[$i]["total_out_money_sale"] - $result[$i]["total_out_money_sale_cost"];
//			$result[$i]["total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_without_tax"] - $result[$i]["total_out_money_sale_cost_no_tax"];
			//含税毛利
			//$result[$i]["total_out_money_sale_profit_tax"] = $result[$i]["total_out_money_sale"] - $result[$i]["total_out_money_sale_cost"];
			//耗损数量
			$result[$i]["total_out_count_sun"] = $v["total_sun_count"];
			$result[$i]["total_out_money_sun"] = $v["total_sun_money"];
			$result[$i]["total_out_money_sun_no_tax"] = $v["total_sun_money_no_tax"];
			//盘耗数量
			$result[$i]["total_out_count_pan"] = $v["total_pan_out_count"];
			$result[$i]["total_out_money_pan"] = $v["total_pan_out_money"];
			$result[$i]["total_out_money_pan_no_tax"] = $v["total_pan_out_money_no_tax"];
			//实际毛利
//            $result[$i]["real_total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_profit"]+$result[$i]["total_in_money_pan_no_tax"]+$result[$i]["total_in_money_yi_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"];
			//期初数量
			$result[$i]["begin_balance_count"] = $v["balance_count"];
			$result[$i]["begin_balance_money"] = $v["balance_count"] * $buyprice;
			$result[$i]["begin_balance_money_no_tax"] = round($result[$i]["begin_balance_money"] / (1 + $buytax),2);
			
			$result[$i]["end_balance_count"] = $result[$i]["begin_balance_count"] - $result[$i]["total_out_count_pan"] -
			$result[$i]["total_out_count_sun"] - $result[$i]["total_out_count_sale"] + $result[$i]["total_in_count_pan"]+ 
			$result[$i]["total_in_count_yi"] + $result[$i]["total_in_count"];

			$result[$i]["end_balance_money"] = $result[$i]["end_balance_count"] * $buyprice;
			$result[$i]["end_balance_money_no_tax"] = round($result[$i]["end_balance_money"] / (1 + $buytax),2);
			//销售毛利 = 期初金额无税+进货金额无税-期末金额无税
			//$result[$i]["total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] - $result[$i]["total_in_money_no_tax"],2);
			//实际成本 = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)-期末金额无税
            $result[$i]["real_total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"]-$result[$i]["end_balance_money_no_tax"],2);
            //实际毛利率 = 1-（实际成本/销售金额无税）
            $result[$i]["real_total_out_money_sale_profit_percent"] = round(1-($result[$i]["real_total_out_money_sale_profit"]/$result[$i]["total_out_money_sale_without_tax"]),4)*100;
            //实际成本  = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)
            //$result[$i]["real_total_out_money_sale_cost"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"],2);

		}

		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_for_total .= " and a.biz_dt >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_for_total .= " and a.biz_dt <= '".$end."' ";
		}
		++$i;
		//获取合计数据 - 进货
		$sql = "select  g.buytax, sum(i.goods_count) as total_in_count,
				sum(i.goods_money) as total_in_money , sum(i.reject_money) as reject_money, sum(i.reject_money / (1 + (g.buytax / 100))) as reject_money_no_tax, sum(i.goods_money_no_tax) as total_in_money_no_tax 
				from t_goods g, t_ia_bill_detail i , t_ia_bill a, t_supplier s
				where g.id = i.goods_id and i.iabill_id = a.id and s.id = a.supplier_id $category_sql $time_sql_for_total";
		$sub_total = $db->query($sql);
		//插入统计行
		$result[$i]["total_in_count"] = $sub_total[0]["total_in_count"];
		$result[$i]["total_in_money"] = round($sub_total[0]["total_in_money"], 2) - round($sub_total[0]["reject_money"],2);
		$result[$i]["total_in_money_no_tax"] = round($sub_total[0]["total_in_money_no_tax"] - $sub_total[0]["reject_money_no_tax"], 2);
		$result[$i]["total_tax_money"] = $result[$i]["total_in_money"] - $result[$i]["total_in_money_no_tax"];

		//获取合计数据 - 销售
		/*
		$sql = "select sum(d.apply_price) as goods_money, sum(d.apply_price_no_tax) as goods_money_no_tax ,sum(reject_money) as reject_money 
						from t_ws_bill_detail d  
						where d.wsbill_id in (select ref from t_ws_bill w where w.bill_status > 1 $time_sql_1)";
		$data = $db->query($sql, $dt);
		$saleMoney = $data[0]["goods_money"];
		*/
		$sql = "select 
				sum(i.sale_count) as total_sale_count,
				sum(i.sale_money) as total_sale_money,
				sum(i.sale_money_no_tax) as total_sale_money_no_tax 
				from t_inout_day i , t_goods g 
				where i.goods_id = g.id $category_sql $time_sql_2 $sql2";
		$data = $db->query($sql);
		$result[$i]["total_out_money_sale"] = $data[0]["total_sale_money"];

		$sql = "select COUNT(DISTINCT i.goods_id) as cnt from 
				t_inventory_detail i 
				where 1=1  $time_sql_2 $sql2";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}


	/*
	按照供应商汇总

	*/
	public function inOutDataBySupplier($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$supplier_code = $params["supplier_code"];
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
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and i.goods_id = '$goods_id' ";
			$sql2 = " and i.goods_id = '$goods_id' ";
		}
		$result = array();
		$db = M();
		$supplier_sql = "";
		if($supplier_code){
			$supplier_sql = " and s.code = '$supplier_code' ";
		}
		$sql = "select s.name,s.code,s.id,
				sum(i.month_balance_count) as balance_count, 
				sum(i.month_balance_money) as total_balance_money, 
				sum(i.month_balance_money_no_tax) as total_balance_money_no_tax,
				sum(i.ia_count) as total_ia_count,
				sum(i.ia_money) as total_ia_money,
				sum(i.ia_money_no_tax) as total_ia_money_no_tax,
				sum(i.sale_count) as total_sale_count,
				sum(i.sale_money) as total_sale_money,
				sum(i.sale_money_no_tax) as total_sale_money_no_tax,
				sum(i.buyprice*i.sale_count) as sale_money_cost,
				sum(i.buyprice*i.sale_count / (1 + g.buytax/100)) as sale_money_cost_no_tax,
				sum(i.sun_count) as total_sun_count,
				sum(i.sun_money) as total_sun_money,
				sum(i.sun_money_no_tax) as total_sun_money_no_tax,
				sum(i.yi_count) as total_yi_count,
				sum(i.yi_money) as total_yi_money,
				sum(i.yi_money_no_tax) as total_yi_money_no_tax,
				sum(i.pan_in_count) as total_pan_in_count,
				sum(i.pan_in_money) as total_pan_in_money,
				sum(i.pan_in_money_no_tax) as total_pan_in_money_no_tax,
				sum(i.pan_out_count) as total_pan_out_count,
				sum(i.pan_out_money) as total_pan_out_money,
				sum(i.pan_out_money_no_tax) as total_pan_out_money_no_tax

				from t_supplier s, t_inout_day i , t_goods g 
				where i.supplier_id = s.id and i.goods_id = g.id $supplier_sql $time_sql_2 group by i.supplier_id  
				limit %d, %d";
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$items = $db->query($sql, $start, $limit);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["gross"] = $v["gross"];
			$result[$i]["lastPrice"] = $lastPrice;
			//获取进货数量
			$result[$i]["total_in_count"] = $v["total_ia_count"];
			//获取进货金额
			$result[$i]["total_in_money"] = $v["total_ia_money"];
			$result[$i]["total_in_money_no_tax"] = $v["total_ia_money_no_tax"];
			//升溢数量
			$result[$i]["total_in_count_yi"] = $v["total_yi_count"];
			//获取溢金额
			$result[$i]["total_in_money_yi"] = $v["total_yi_money"];
			$result[$i]["total_in_money_yi_no_tax"] = $v["total_yi_money_no_tax"];
			//获取盘升金额
			$result[$i]["total_in_count_pan"] = $v["total_pan_in_count"];
			//获取盘溢金额
			$result[$i]["total_in_money_pan"] = $v["total_pan_in_money"];
			$result[$i]["total_in_money_pan_no_tax"] = $v["total_pan_in_money_no_tax"];
			//获取销售数量
			$result[$i]["total_out_count_sale"] = $v["total_sale_count"];
			//获取销售金额
			$salePrice = $v["sale_price"];
			$result[$i]["total_out_money_sale"] = $v["total_sale_money"];
			//不含税销售金额
			$result[$i]["total_out_money_sale_without_tax"] =  $v["total_sale_money_no_tax"];
			//销售成本
			$result[$i]["total_out_money_sale_cost"] = $v["sale_money_cost"];
			//销售成本无税
			$result[$i]["total_out_money_sale_cost_no_tax"] = $v["sale_money_cost_no_tax"];
			//销售毛利
//			$result[$i]["total_out_money_sale_profit"] = $result[$i]["total_out_money_sale"] - $result[$i]["total_out_money_sale_cost"];
//			$result[$i]["total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_without_tax"] - $result[$i]["total_out_money_sale_cost_no_tax"];
			//含税毛利
			//$result[$i]["total_out_money_sale_profit_tax"] = $result[$i]["total_out_money_sale"] - $result[$i]["total_out_money_sale_cost"];
			//耗损数量
			$result[$i]["total_out_count_sun"] = $v["total_sun_count"];
			$result[$i]["total_out_money_sun"] = $v["total_sun_money"];
			$result[$i]["total_out_money_sun_no_tax"] = $v["total_sun_money_no_tax"];
			//盘耗数量
			$result[$i]["total_out_count_pan"] = $v["total_pan_out_count"];
			$result[$i]["total_out_money_pan"] = $v["total_pan_out_money"];
			$result[$i]["total_out_money_pan_no_tax"] = $v["total_pan_out_money_no_tax"];
			//实际毛利
//            $result[$i]["real_total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_profit"]+$result[$i]["total_in_money_pan_no_tax"]+$result[$i]["total_in_money_yi_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"];
			//期初数量
			$result[$i]["begin_balance_count"] = $v["balance_count"];
			$result[$i]["begin_balance_money"] = $v["total_balance_money"];
			$result[$i]["begin_balance_money_no_tax"] = $v["total_balance_money_no_tax"];
			
			$result[$i]["end_balance_count"] = $result[$i]["begin_balance_count"] - $result[$i]["total_out_count_pan"] -
			$result[$i]["total_out_count_sun"] - $result[$i]["total_out_count_sale"] + $result[$i]["total_in_count_pan"]+ 
			$result[$i]["total_in_count_yi"] + $result[$i]["total_in_count"];

			$result[$i]["end_balance_money"] = $result[$i]["begin_balance_money"] - $result[$i]["total_out_money_pan"] -
			$result[$i]["total_out_money_sun"] - $result[$i]["total_out_money_sale_cost"] + $result[$i]["total_in_money_pan"]+ 
			$result[$i]["total_in_money_yi"] + $result[$i]["total_in_money"];

			$result[$i]["end_balance_money_no_tax"] = $result[$i]["begin_balance_money_no_tax"] - $result[$i]["total_out_money_pan_no_tax"] -
			$result[$i]["total_out_money_sun_no_tax"] - $result[$i]["total_out_money_sale_cost_no_tax"] + $result[$i]["total_in_money_pan_no_tax"]+ 
			$result[$i]["total_in_money_yi_no_tax"] + $result[$i]["total_in_money_no_tax"];
			//销售毛利 = 期初金额无税+进货金额无税-期末金额无税
			//$result[$i]["total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] - $result[$i]["total_in_money_no_tax"],2);
			//实际成本 = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)-期末金额无税
            $result[$i]["real_total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"]-$result[$i]["end_balance_money_no_tax"],2);
            //实际毛利率 = 1-（实际成本/销售金额无税）
            $result[$i]["real_total_out_money_sale_profit_percent"] = round(1-($result[$i]["real_total_out_money_sale_profit"]/$result[$i]["total_out_money_sale_without_tax"]),4)*100;
            //实际成本  = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)
            //$result[$i]["real_total_out_money_sale_cost"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"],2);

		}
		
		$sql = "select count(*) as cnt from t_supplier";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}




	/*
	按照分类汇总

	*/
	public function inOutDataByCate($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
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
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and i.goods_id = '$goods_id' ";
			$sql2 = " and i.goods_id = '$goods_id' ";
		}
		$cate_code = $params["cate_code"];
		if($cate_code){
			$cate_sql = " and s.code = '$cate_code' ";
		} else {
			$cate_sql = " and s.parent_id = '0' ";
		}
		$result = array();
		$db = M();
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$sql = "select s.name,s.code,s.id,
				sum(i.month_balance_count) as balance_count, 
				sum(i.month_balance_money) as total_balance_money, 
				sum(i.month_balance_money_no_tax) as total_balance_money_no_tax,
				sum(i.ia_count) as total_ia_count,
				sum(i.ia_money) as total_ia_money,
				sum(i.ia_money_no_tax) as total_ia_money_no_tax,
				sum(i.sale_count) as total_sale_count,
				sum(i.sale_money) as total_sale_money,
				sum(i.sale_money_no_tax) as total_sale_money_no_tax,
				sum(i.buyprice*i.sale_count) as sale_money_cost,
				sum(i.buyprice*i.sale_count / (1 + g.buytax/100)) as sale_money_cost_no_tax,
				sum(i.sun_count) as total_sun_count,
				sum(i.sun_money) as total_sun_money,
				sum(i.sun_money_no_tax) as total_sun_money_no_tax,
				sum(i.yi_count) as total_yi_count,
				sum(i.yi_money) as total_yi_money,
				sum(i.yi_money_no_tax) as total_yi_money_no_tax,
				sum(i.pan_in_count) as total_pan_in_count,
				sum(i.pan_in_money) as total_pan_in_money,
				sum(i.pan_in_money_no_tax) as total_pan_in_money_no_tax,
				sum(i.pan_out_count) as total_pan_out_count,
				sum(i.pan_out_money) as total_pan_out_money,
				sum(i.pan_out_money_no_tax) as total_pan_out_money_no_tax

				from t_goods_category s, t_goods g, t_inout_day i 
				where i.goods_id = g.id and g.parent_cate_id = s.id $cate_code $time_sql_2 group by g.parent_cate_id  
				limit %d, %d";
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$items = $db->query($sql, $start, $limit);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["gross"] = $v["gross"];
			$result[$i]["lastPrice"] = $lastPrice;
			//获取进货数量
			$result[$i]["total_in_count"] = $v["total_ia_count"];
			//获取进货金额
			$result[$i]["total_in_money"] = $v["total_ia_money"];
			$result[$i]["total_in_money_no_tax"] = $v["total_ia_money_no_tax"];
			//升溢数量
			$result[$i]["total_in_count_yi"] = $v["total_yi_count"];
			//获取溢金额
			$result[$i]["total_in_money_yi"] = $v["total_yi_money"];
			$result[$i]["total_in_money_yi_no_tax"] = $v["total_yi_money_no_tax"];
			//获取盘升金额
			$result[$i]["total_in_count_pan"] = $v["total_pan_in_count"];
			//获取盘溢金额
			$result[$i]["total_in_money_pan"] = $v["total_pan_in_money"];
			$result[$i]["total_in_money_pan_no_tax"] = $v["total_pan_in_money_no_tax"];
			//获取销售数量
			$result[$i]["total_out_count_sale"] = $v["total_sale_count"];
			//获取销售金额
			$salePrice = $v["sale_price"];
			$result[$i]["total_out_money_sale"] = $v["total_sale_money"];
			//不含税销售金额
			$result[$i]["total_out_money_sale_without_tax"] =  $v["total_sale_money_no_tax"];
			//销售成本
			$result[$i]["total_out_money_sale_cost"] = $v["sale_money_cost"];
			//销售成本无税
			$result[$i]["total_out_money_sale_cost_no_tax"] = $v["sale_money_cost_no_tax"];
			//销售毛利
//			$result[$i]["total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_without_tax"] - $result[$i]["total_out_money_sale_cost_no_tax"];
			//含税毛利
			//$result[$i]["total_out_money_sale_profit_tax"] = $result[$i]["total_out_money_sale"] - $result[$i]["total_out_money_sale_cost"];
			//耗损数量
			$result[$i]["total_out_count_sun"] = $v["total_sun_count"];
			$result[$i]["total_out_money_sun"] = $v["total_sun_money"];
			$result[$i]["total_out_money_sun_no_tax"] = $v["total_sun_money_no_tax"];
			//盘耗数量
			$result[$i]["total_out_count_pan"] = $v["total_pan_out_count"];
			$result[$i]["total_out_money_pan"] = $v["total_pan_out_money"];
			$result[$i]["total_out_money_pan_no_tax"] = $v["total_pan_out_money_no_tax"];
			//实际毛利
//            $result[$i]["real_total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_profit"]+$result[$i]["total_in_money_pan_no_tax"]+$result[$i]["total_in_money_yi_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"];
			//期初数量
			$result[$i]["begin_balance_count"] = $v["balance_count"];
			$result[$i]["begin_balance_money"] = $v["total_balance_money"];
			$result[$i]["begin_balance_money_no_tax"] = $v["total_balance_money_no_tax"];
			
			$result[$i]["end_balance_count"] = $result[$i]["begin_balance_count"] - $result[$i]["total_out_count_pan"] -
			$result[$i]["total_out_count_sun"] - $result[$i]["total_out_count_sale"] + $result[$i]["total_in_count_pan"]+ 
			$result[$i]["total_in_count_yi"] + $result[$i]["total_in_count"];

			$result[$i]["end_balance_money"] = $result[$i]["begin_balance_money"] - $result[$i]["total_out_money_pan"] -
			$result[$i]["total_out_money_sun"] - $result[$i]["total_out_money_sale_cost"] + $result[$i]["total_in_money_pan"]+ 
			$result[$i]["total_in_money_yi"] + $result[$i]["total_in_money"];

			$result[$i]["end_balance_money_no_tax"] = $result[$i]["begin_balance_money_no_tax"] - $result[$i]["total_out_money_pan_no_tax"] -
			$result[$i]["total_out_money_sun_no_tax"] - $result[$i]["total_out_money_sale_cost_no_tax"] + $result[$i]["total_in_money_pan_no_tax"]+ 
			$result[$i]["total_in_money_yi_no_tax"] + $result[$i]["total_in_money_no_tax"];
			//销售毛利 = 期初金额无税+进货金额无税-期末金额无税
			//$result[$i]["total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] - $result[$i]["total_in_money_no_tax"],2);
			//实际成本 = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)-期末金额无税
            $result[$i]["real_total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"]-$result[$i]["end_balance_money_no_tax"],2);
            //实际毛利率 = 1-（实际成本/销售金额无税）
            $result[$i]["real_total_out_money_sale_profit_percent"] = round(1-($result[$i]["real_total_out_money_sale_profit"]/$result[$i]["total_out_money_sale_without_tax"]),4)*100;
            //实际成本  = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)
            //$result[$i]["real_total_out_money_sale_cost"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"],2);


		}
		
		$sql = "select count(*) as cnt from t_goods_category";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}


	/*  按照小类别汇总  */
	public function inOutDataBySmallCate($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
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
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and i.goods_id = '$goods_id' ";
			$sql2 = " and i.goods_id = '$goods_id' ";
		}
		$cate_code = $params["cate_code"];
		if($cate_code){
			$cate_sql = " and s.code = '$cate_code' ";
		} else {
			$cate_sql = " and s.parent_id != '0' ";
		}
		$result = array();
		$db = M();
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$sql = "select s.name,s.code,s.id,
				sum(i.month_balance_count) as balance_count, 
				sum(i.month_balance_money) as total_balance_money, 
				sum(i.month_balance_money_no_tax) as total_balance_money_no_tax,
				sum(i.ia_count) as total_ia_count,
				sum(i.ia_money) as total_ia_money,
				sum(i.ia_money_no_tax) as total_ia_money_no_tax,
				sum(i.sale_count) as total_sale_count,
				sum(i.sale_money) as total_sale_money,
				sum(i.sale_money_no_tax) as total_sale_money_no_tax,
				sum(i.buyprice*i.sale_count) as sale_money_cost,
				sum(i.buyprice*i.sale_count / (1 + g.buytax/100)) as sale_money_cost_no_tax,
				sum(i.sun_count) as total_sun_count,
				sum(i.sun_money) as total_sun_money,
				sum(i.sun_money_no_tax) as total_sun_money_no_tax,
				sum(i.yi_count) as total_yi_count,
				sum(i.yi_money) as total_yi_money,
				sum(i.yi_money_no_tax) as total_yi_money_no_tax,
				sum(i.pan_in_count) as total_pan_in_count,
				sum(i.pan_in_money) as total_pan_in_money,
				sum(i.pan_in_money_no_tax) as total_pan_in_money_no_tax,
				sum(i.pan_out_count) as total_pan_out_count,
				sum(i.pan_out_money) as total_pan_out_money,
				sum(i.pan_out_money_no_tax) as total_pan_out_money_no_tax

				from t_goods_category s, t_goods g, t_inout_day i 
				where i.goods_id = g.id and g.category_id = s.id $cate_code $time_sql_2 group by g.category_id  
				limit %d, %d";
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$items = $db->query($sql, $start, $limit);
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["gross"] = $v["gross"];
			$result[$i]["lastPrice"] = $lastPrice;
			//获取进货数量
			$result[$i]["total_in_count"] = $v["total_ia_count"];
			//获取进货金额
			$result[$i]["total_in_money"] = $v["total_ia_money"];
			$result[$i]["total_in_money_no_tax"] = $v["total_ia_money_no_tax"];
			//升溢数量
			$result[$i]["total_in_count_yi"] = $v["total_yi_count"];
			//获取溢金额
			$result[$i]["total_in_money_yi"] = $v["total_yi_money"];
			$result[$i]["total_in_money_yi_no_tax"] = $v["total_yi_money_no_tax"];
			//获取盘升金额
			$result[$i]["total_in_count_pan"] = $v["total_pan_in_count"];
			//获取盘溢金额
			$result[$i]["total_in_money_pan"] = $v["total_pan_in_money"];
			$result[$i]["total_in_money_pan_no_tax"] = $v["total_pan_in_money_no_tax"];
			//获取销售数量
			$result[$i]["total_out_count_sale"] = $v["total_sale_count"];
			//获取销售金额
			$salePrice = $v["sale_price"];
			$result[$i]["total_out_money_sale"] = $v["total_sale_money"];
			//不含税销售金额
			$result[$i]["total_out_money_sale_without_tax"] =  $v["total_sale_money_no_tax"];
			//销售成本
			$result[$i]["total_out_money_sale_cost"] = $v["sale_money_cost"];
			//销售成本无税
			$result[$i]["total_out_money_sale_cost_no_tax"] = $v["sale_money_cost_no_tax"];
			//销售毛利
//			$result[$i]["total_out_money_sale_profit"] = $result[$i]["total_out_money_sale"] - $result[$i]["total_out_money_sale_cost"];
//			$result[$i]["total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_without_tax"] - $result[$i]["total_out_money_sale_cost_no_tax"];
			//含税毛利
			//$result[$i]["total_out_money_sale_profit_tax"] = $result[$i]["total_out_money_sale"] - $result[$i]["total_out_money_sale_cost"];
			//耗损数量
			$result[$i]["total_out_count_sun"] = $v["total_sun_count"];
			$result[$i]["total_out_money_sun"] = $v["total_sun_money"];
			$result[$i]["total_out_money_sun_no_tax"] = $v["total_sun_money_no_tax"];
			//盘耗数量
			$result[$i]["total_out_count_pan"] = $v["total_pan_out_count"];
			$result[$i]["total_out_money_pan"] = $v["total_pan_out_money"];
			$result[$i]["total_out_money_pan_no_tax"] = $v["total_pan_out_money_no_tax"];
			//实际毛利
//            $result[$i]["real_total_out_money_sale_profit"] = $result[$i]["total_out_money_sale_profit"]+$result[$i]["total_in_money_pan_no_tax"]+$result[$i]["total_in_money_yi_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"];
			//期初数量
			$result[$i]["begin_balance_count"] = $v["balance_count"];
			$result[$i]["begin_balance_money"] = $v["total_balance_money"];
			$result[$i]["begin_balance_money_no_tax"] = $v["total_balance_money_no_tax"];
			
			$result[$i]["end_balance_count"] = $result[$i]["begin_balance_count"] - $result[$i]["total_out_count_pan"] -
			$result[$i]["total_out_count_sun"] - $result[$i]["total_out_count_sale"] + $result[$i]["total_in_count_pan"]+ 
			$result[$i]["total_in_count_yi"] + $result[$i]["total_in_count"];

			$result[$i]["end_balance_money"] = $result[$i]["begin_balance_money"] - $result[$i]["total_out_money_pan"] -
			$result[$i]["total_out_money_sun"] - $result[$i]["total_out_money_sale_cost"] + $result[$i]["total_in_money_pan"]+ 
			$result[$i]["total_in_money_yi"] + $result[$i]["total_in_money"];

			$result[$i]["end_balance_money_no_tax"] = $result[$i]["begin_balance_money_no_tax"] - $result[$i]["total_out_money_pan_no_tax"] -
			$result[$i]["total_out_money_sun_no_tax"] - $result[$i]["total_out_money_sale_cost_no_tax"] + $result[$i]["total_in_money_pan_no_tax"]+ 
			$result[$i]["total_in_money_yi_no_tax"] + $result[$i]["total_in_money_no_tax"];
			//销售毛利 = 期初金额无税+进货金额无税-期末金额无税
			//$result[$i]["total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] - $result[$i]["total_in_money_no_tax"],2);
			//实际成本 = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)-期末金额无税
            $result[$i]["real_total_out_money_sale_profit"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"]-$result[$i]["end_balance_money_no_tax"],2);
            //实际毛利率 = 1-（实际成本/销售金额无税）
            $result[$i]["real_total_out_money_sale_profit_percent"] = round(1-($result[$i]["real_total_out_money_sale_profit"]/$result[$i]["total_out_money_sale_without_tax"]),4)*100;
            //实际成本  = 期初金额无税+进货金额无税+升溢金额(无税)+盘升金额(无税)-耗损金额(无税)-盘耗金额(无税)
            //$result[$i]["real_total_out_money_sale_cost"] = round($result[$i]["begin_balance_money_no_tax"] + $result[$i]["total_in_money_no_tax"] + $result[$i]["total_in_money_yi_no_tax"]+$result[$i]["total_in_money_pan_no_tax"]-$result[$i]["total_out_money_sun_no_tax"]-$result[$i]["total_out_money_pan_no_tax"],2);


		}
		
		$sql = "select count(*) as cnt from t_goods_category";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}


	/*  验收报表 -- 按照商品明细汇总    */

	public function yanshouDataByGoods($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$sql1 = "";
		$sql2 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and a.biz_dt >= '".$begin."' ";
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and a.biz_dt <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and i.goods_id = '$goods_id' ";
			$sql2 = " and i.goods_id = '$goods_id' ";
		}
		//是否包含了供应商帅选
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id   = $supplier_info["id"];
		}
		if($supplier_id){
			$sql1 .= " and a.supplier_id = '$supplier_id' ";
			//$sql2 .= " and a.supplier_id = '$supplier_id' ";
		}
		$result = array();
		$db = M();
		$sql = "select g.id, g.lastbuyprice, g.sale_price, g.gross, g.buytax, g.selltax, g.code, g.name, g.spec, g.barcode,  i.goods_count as total_in_count,
				i.goods_money as total_in_money , i.reject_count, i.reject_money, i.reject_money_no_tax, a.ref as ref_number,s.name as supplier_name, s.code as supplier_code , a.biz_dt 
				from t_goods g, t_ia_bill_detail i , t_ia_bill a, t_supplier s
				where g.id = i.goods_id and i.iabill_id = a.id and s.id = a.supplier_id $time_sql_1 $sql1  
				order by a.biz_dt desc 
				limit %d, %d";
		if(I("request.act") == 'export'){
			$limit = 20000;
		}
		$items = $db->query($sql, $start, $limit);
		//dump($db->getLastSql());
		$list[] = array('商品编码', '商品名称', '条码', '供应商编码', '供应商', '单据', 
								'日期', '数量', '无税金额', '税金', '含税金额', '退货金额','无税退货金额','退货税金',
								'税率', '0-金额','0-税金','13-金额','13-税金','17-金额','17-税金');
		
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["gross"] = $v["gross"];
			$result[$i]["buytax"] = $v["buytax"];
			$result[$i]["ref_number"] = $v["ref_number"];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["ref_number"] = $v["ref_number"];
			$result[$i]["date"] = $v["biz_dt"];
			$lastPrice = $v["lastbuyprice"];
			$salePrice = $v["sale_price"];
			$buytax = $v["buytax"] / 100;
			$selltax = $v["selltax"] / 100;
			$result[$i]["lastPrice"] = $lastPrice;
			//获取进货数量
			/*
			$sql = "select sum(i.in_count) as total_in_count 
				from t_inventory_detail i 
				where (i.ref_type='验收入库' or i.ref_type='自动验收入库') $time_sql_2 and i.goods_id = '".$v['id']."'";
			$data = $db->query($sql);
			*/
			$total_in_count = $v["total_in_count"] - $v["reject_count"];
			$result[$i]["total_in_count"] = $total_in_count;
			//获取含税进货价格
			$result[$i]["total_in_money"] = $v["total_in_money"];
			//计算不含税的进货价格
			$result[$i]["total_in_money_no_tax"] = round($result[$i]["total_in_money"] / (1 + $buytax), 2);
			//计算税金
			$result[$i]["total_tax_money"] = $result[$i]["total_in_money"] - $result[$i]["total_in_money_no_tax"];
			//统计退货金额
			$result[$i]["total_reject_money"] = $v["reject_money"];
			$result[$i]["total_reject_money_no_tax"] = $v["reject_money_no_tax"];
			$result[$i]["total_reject_money_tax"] = $v["reject_money"] - $v["reject_money_no_tax"];
			$result[$i]["end_balance_count"] = $total_out_count;
			$result[$i]["end_balance_money"] = $total_out_count * $lastPrice;
			$list[] = array($v["code"], $v["name"], $v["barcode"], $v["supplier_code"],$v["supplier_name"] , $v["ref_number"] ,
				$v["biz_dt"], $v["total_in_count"], $result[$i]["total_in_money_no_tax"],$result[$i]["total_tax_money"],$v["total_in_money"],$result[$i]["total_reject_money"],$result[$i]["total_reject_money_no_tax"],$result[$i]["total_reject_money_tax"],
				 $buytax, 0, 0, 0,0,0,0);
		}
		
		//计算合计数据
		$sql = "select  g.buytax, sum(i.goods_count) as total_in_count,
				sum(i.goods_money) as total_in_money , sum(i.reject_money) as reject_money, sum(i.reject_count) as reject_count, sum(i.reject_money / (1 + (g.buytax / 100))) as reject_money_no_tax, sum(i.goods_money / (1 + (g.buytax / 100))) as total_in_money_no_tax 
				from t_goods g, t_ia_bill_detail i , t_ia_bill a, t_supplier s
				where g.id = i.goods_id and i.iabill_id = a.id and s.id = a.supplier_id $time_sql_1 $sql1  ";
		$sql = "select  g.buytax, sum(i.goods_count) as total_in_count,
				sum(i.goods_money) as total_in_money , sum(i.reject_money) as reject_money, sum(i.reject_count) as reject_count, sum(i.reject_money / (1 + (g.buytax / 100))) as reject_money_no_tax, sum(i.goods_money_no_tax) as total_in_money_no_tax 
				from t_goods g, t_ia_bill_detail i , t_ia_bill a, t_supplier s
				where g.id = i.goods_id and i.iabill_id = a.id and s.id = a.supplier_id $time_sql_1 $sql1  ";
		$sub_total = $db->query($sql);
		//插入统计行
		++$i;
		$result[$i]["total_in_count"] = $sub_total[0]["total_in_count"] - $sub_total[0]["reject_count"];
		$result[$i]["total_in_money"] = round($sub_total[0]["total_in_money"], 2) - round($sub_total[0]["reject_money"],2);
		$result[$i]["total_in_money_no_tax"] = round($sub_total[0]["total_in_money_no_tax"] - $sub_total[0]["reject_money_no_tax"], 2);
		$result[$i]["total_tax_money"] = $result[$i]["total_in_money"] - $result[$i]["total_in_money_no_tax"];
		$list[] = array('', '', '', '','' , 
				'', $result[$i]["total_in_count"], $result[$i]["total_in_money_no_tax"],$result[$i]["total_tax_money"],$v["total_in_money"],$result[$i]["total_reject_money"],
				 $buytax, 0, 0, 0,0,0,0);
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '验收报表商品明细导出数据.csv';
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
		
		$sql = "select COUNT(*) as cnt from 
				t_inventory_detail i 
				where 1=1 and (i.ref_type='验收入库' or i.ref_type='自动验收入库') $time_sql_2 $sql2";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/*  验收报表 -- 按照供应商汇总    */

	public function yanshouDataBySupplier($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$sql1 = "";
		$sql2 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and w.delivery_date >= '".$begin."' ";
			$time_sql_2 .= " and a.biz_dt >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and a.biz_dt <= '".$end."' ";
		}

		$dt = $params["dt"];
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id = $supplier_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($supplier_id){
			$sql1 = " and a.supplier_id = '$supplier_id' ";
			$sql2 = " and a.supplier_id = '$supplier_id' ";
		}
		$result = array();
		$db = M();
		$sql = "select sum(a.goods_money) as total_money, sum(a.goods_money_no_tax) as total_money_no_tax , sum(a.reject_money) as reject_money, sum(a.reject_money_no_tax) as reject_money_no_tax, s.name as supplier_name, s.code as supplier_code 
			    from t_ia_bill a, t_supplier s where a.supplier_id = s.id $time_sql_2 $sql2 group by a.supplier_id 
				order by biz_dt desc limit %d, %d";
		if(I("request.act") == 'export'){
			$limit = 20000;
		}
		$start = 0;
		$limit = 1000;
		$items = $db->query($sql, $start, $limit);
		//dump($db->getLastSql());
		$list[] = array('商品编码', '商品名称', '条码', '供应商编码', '供应商', '单据', 
								'日期', '数量', '无税金额', '税金', '含税金额', '退款金额','无税退货金额','退货税金',
								'税率', '0-金额','0-税金','13-金额','13-税金','17-金额','17-税金');
		$all_total_money = 0;
		$all_total_money_no_tax = 0;
		$all_total_tax = 0;
		$all_total_reject_money = 0;
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["supplier_code"];
			$result[$i]["goodsName"] = $v["supplier_name"];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["date"] = $v["biz_date"];
			$result[$i]["ref_number"] = $v["ref_number"];
			$lastPrice = $v["lastbuyprice"];
			$salePrice = $v["sale_price"];
			$buytax = $v["buytax"] / 100;
			$selltax = $v["selltax"] / 100;
			$result[$i]["lastPrice"] = $lastPrice;
			//获取进货金额
			$result[$i]["total_in_money"] = $v["total_money"];
			$result[$i]["total_in_money_no_tax"] = $v["total_money_no_tax"];
			$result[$i]["total_tax_money"] = $v["total_money"] - $v["total_money_no_tax"];
			$result[$i]["total_reject_money"] = $v["reject_money"];
			$result[$i]["total_reject_money_no_tax"] = $v["reject_money_no_tax"];
			$result[$i]["total_reject_money_tax"] = $v["reject_money"] - $v["reject_money_no_tax"];
			$all_total_money+= $result[$i]["total_in_money"];
			$all_total_money_no_tax += $result[$i]["total_in_money_no_tax"];
			$all_total_tax += $result[$i]["total_tax_money"];
			$all_total_reject_money+=$result[$i]["total_reject_money"];
			$list[] = array($v["code"], $v["name"], $v["barcode"], $v["supplier_code"],$v["supplier_name"] ,$v["ref_number"], 
				$v["biz_dt"], $v["total_in_count"], $result[$i]["total_in_money_no_tax"],$result[$i]["total_tax_money"],$result[$i]["total_in_money"],$result[$i]["total_reject_money"],$result[$i]["total_reject_money_no_tax"],$result[$i]["total_reject_money_tax"],
				 $buytax, 0, 0, 0,0,0,0);
		}
		//计入统计行
		++$i;
		$result[$i]["total_in_money"] = $all_total_money;
		$result[$i]["total_in_money_no_tax"] = $all_total_money_no_tax;
		$result[$i]["total_tax_money"] = $all_total_tax;
		$result[$i]["total_reject_money"] = $all_total_reject_money;
		$list[] = array('', '', '', '','' , 
				'', '', $all_total_money_no_tax,$all_total_tax,$all_total_money,
				 0, 0, 0, 0,0,0,0);
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '验收报表供应商汇总导出数据.csv';
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
		
		$sql = "select COUNT(DISTINCT a.supplier_id) as cnt from 
				t_ia_bill a 
				where 1=1  $time_sql_2 $sql2";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/*  验收报表 -- 按照分类汇总    */

	public function yanshouDataByCate($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$sql1 = "";
		$sql2 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and w.delivery_date >= '".$begin."' ";
			$time_sql_2 .= " and a.biz_dt >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and a.biz_dt <= '".$end."' ";
		}

		$dt = $params["dt"];
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id = $supplier_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($supplier_id){
			$sql1 = " and a.supplier_id = '$supplier_id' ";
			$sql2 = " and a.supplier_id = '$supplier_id' ";
		}
		$result = array();
		$db = M();
		$sql = "select sum(a.goods_money) as total_money, s.name as supplier_name, s.code as supplier_code 
			    from t_ia_bill a, t_supplier s where a.supplier_id = s.id $time_sql_2 $sql2 group by a.supplier_id 
				order by biz_dt desc limit %d, %d";
		$items = $db->query($sql, $start, $limit);
		//dump($db->getLastSql());

		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["supplier_code"];
			$result[$i]["goodsName"] = $v["supplier_name"];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["date"] = $v["biz_date"];
			//$result[$i]["ref_number"] = $v["ref_number"];
			$lastPrice = $v["lastbuyprice"];
			$salePrice = $v["sale_price"];
			$buytax = $v["buytax"] / 100;
			$selltax = $v["selltax"] / 100;
			$result[$i]["lastPrice"] = $lastPrice;
			//获取进货金额
			$result[$i]["total_in_money"] = $v["total_money"];

		}
		
		$sql = "select COUNT(DISTINCT a.supplier_id) as cnt from 
				t_ia_bill a 
				where 1=1  $time_sql_2 $sql2";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/* 按照供应商税率  */
	public function yanshouDataBySupplierTax($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$sql1 = "";
		$sql2 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and w.delivery_date >= '".$begin."' ";
			$time_sql_2 .= " and a.biz_dt >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and a.biz_dt <= '".$end."' ";
		}

		$dt = $params["dt"];
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id = $supplier_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($supplier_id){
			$sql1 = " and a.supplier_id = '$supplier_id' ";
			$sql2 = " and a.supplier_id = '$supplier_id' ";
		}
		$result = array();
		$db = M();
		$sql = "select sum(a.goods_money) as total_money, sum(a.reject_money) as reject_money, s.name as supplier_name, s.code as supplier_code ,a.supplier_id 
			    from t_ia_bill a, t_supplier s where a.supplier_id = s.id $time_sql_2 $sql2 group by a.supplier_id 
				order by biz_dt desc limit %d, %d";
		$start = 0;
		$limit = 1000;
		$items = $db->query($sql, $start, $limit);
		$list[] = array('商品编码', '商品名称', '条码', '供应商编码', '供应商', 
								'日期', '数量', '无税金额', '税金', '含税金额', 
								'税率', '0-金额','0-税金','13-金额','13-税金','17-金额','17-税金');
		//dump($db->getLastSql());
		$all_total_0 = 0;
		$all_total_13 = 0;
		$all_total_13_no_tax = 0;
		$all_total_13_tax = 0;
		$all_total_17 = 0;
		$all_total_17_no_tax = 0;
		$all_total_17_tax = 0;
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["supplier_code"];
			$result[$i]["goodsName"] = $v["supplier_name"];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			//首先获取到0税率的商品
			$sql = "select sum(goods_money) as total_money, sum(reject_money) as reject_money from t_ia_bill_detail where goods_id in (select id from t_goods where buytax = 0) and iabill_id in (select a.id from t_ia_bill a where 1=1 and a.supplier_id = '".$v["supplier_id"]."' $sql2 )";
			$data = $db->query($sql);
			$result[$i]["total_money_tax_0"] = $data[0]["total_money"] - $data[0]["reject_money"];
			$all_total_0+=$result[$i]["total_money_tax_0"];
			$result[$i]["tax_0"] = 0;
			//获取13税率的商品
			$sql = "select sum(goods_money) as total_money,sum(reject_money) as reject_money from t_ia_bill_detail where goods_id in (select id from t_goods where buytax = 13) and iabill_id in (select a.id from t_ia_bill a where 1=1 and a.supplier_id = '".$v["supplier_id"]."' $sql2 )";
			$data = $db->query($sql);
			$result[$i]["total_money_tax_13"] = $data[0]["total_money"] - $data[0]["reject_money"];
			$result[$i]["total_money_no_tax_13"] = round($data[0]["total_money"] / 1.13, 2);
			$result[$i]["tax_13"] = $result[$i]["total_money_tax_13"] - $result[$i]["total_money_no_tax_13"];
			$all_total_13 += $result[$i]["total_money_tax_13"];
			$all_total_13_no_tax += $result[$i]["total_money_no_tax_13"];
			$all_total_13_tax += $result[$i]["tax_13"];
			//获取17税率的商品
			$sql = "select sum(goods_money) as total_money,sum(reject_money) as reject_money from t_ia_bill_detail where goods_id in (select id from t_goods where buytax = 17) and iabill_id in (select a.id from t_ia_bill a where 1=1 and a.supplier_id = '".$v["supplier_id"]."' $sql2 )";
			$data = $db->query($sql);
			$result[$i]["total_money_tax_17"] = $data[0]["total_money"] - $data[0]["reject_money"];
			$result[$i]["total_money_no_tax_17"] = round($data[0]["total_money"] / 1.17, 2);
			$result[$i]["tax_17"] = $result[$i]["total_money_tax_17"] - $result[$i]["total_money_no_tax_17"];
			$all_total_17 += $result[$i]["total_money_tax_17"];
			$all_total_17_no_tax += $result[$i]["total_money_no_tax_17"];
			$all_total_17_tax += $result[$i]["tax_17"];
			$list[] = array($v["code"], $v["name"], $v["barcode"], $v["supplier_code"],$v["supplier_name"] , 
				$v["biz_dt"], $v["total_in_count"], $result[$i]["total_in_money_no_tax"],$result[$i]["total_tax_money"],$v["total_in_money"],
				 $buytax, $result[$i]["total_money_tax_0"], $result[$i]["tax_0"], $result[$i]["total_money_tax_13"],$result[$i]["tax_13"],$result[$i]["total_money_tax_17"],$result[$i]["tax_17"]);
		}
		/* 加入统计数据 */
		++$i;
		$result[$i]["total_money_tax_0"] = $all_total_0;
		$result[$i]["tax_0"] = 0;
		$result[$i]["total_money_tax_13"] = $all_total_13;
		$result[$i]["tax_13"] = $all_total_13 - $all_total_13_no_tax;
		$result[$i]["total_money_tax_17"] = $all_total_17;
		$result[$i]["tax_17"] = $all_total_17 - $all_total_17_no_tax;
		$list[] = array('', '', '', '','' , 
				'', '', '','','',
				 '', $result[$i]["total_money_tax_0"], $result[$i]["tax_0"], $result[$i]["total_money_tax_13"],$result[$i]["tax_13"],$result[$i]["total_money_tax_17"],$result[$i]["tax_17"]);
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '验收报表供应商税率导出数据.csv';
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
		
		$sql = "select COUNT(DISTINCT a.supplier_id) as cnt from 
				t_ia_bill a 
				where 1=1  $time_sql_2 $sql2";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}


	/*  验收报表 -- 按照商品总量汇总    */

	public function yanshouDataByGoodsSum($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$sql1 = "";
		$sql2 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and a.biz_dt >= '".$begin."' ";
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and a.biz_dt <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = "";
		$sql2 = "";
		if($goods_id){
			$sql1 = " and i.goods_id = '$goods_id' ";
			$sql2 = " and i.goods_id = '$goods_id' ";
		}
		//是否包含了供应商帅选
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id   = $supplier_info["id"];
		}
		if($supplier_id){
			$sql1 .= " and a.supplier_id = '$supplier_id' ";
			//$sql2 .= " and a.supplier_id = '$supplier_id' ";
		}
		$result = array();
		$db = M();
		$sql = "select g.id, g.lastbuyprice, g.sale_price, g.gross, g.buytax, g.selltax, g.code, g.name, g.spec, g.barcode,  sum(i.goods_count) as total_in_count,
				sum(i.goods_money) as total_in_money , sum(i.reject_money) as reject_money, a.ref as ref_number,s.name as supplier_name, s.code as supplier_code , a.biz_dt 
				from t_goods g, t_ia_bill_detail i , t_ia_bill a, t_supplier s
				where g.id = i.goods_id and i.iabill_id = a.id and s.id = a.supplier_id $time_sql_1 $sql1 group by i.goods_id
				order by a.biz_dt desc 
				limit %d, %d";
		if(I("request.act") == 'export'){
			$limit = 20000;
		}
		$items = $db->query($sql, $start, $limit);
		//dump($db->getLastSql());
		$list[] = array('商品编码', '商品名称', '条码', '供应商编码', '供应商', 
								'日期', '数量', '无税金额', '税金', '含税金额', 
								'税率', '0-金额','0-税金','13-金额','13-税金','17-金额','17-税金');
		
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = $dt;
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["gross"] = $v["gross"];
			$result[$i]["buytax"] = $v["buytax"];
			$result[$i]["ref_number"] = $v["ref_number"];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["date"] = $v["biz_dt"];
			//$result[$i]["ref_number"] = $v["ref_number"];
			$lastPrice = $v["lastbuyprice"];
			$salePrice = $v["sale_price"];
			$buytax = $v["buytax"] / 100;
			$selltax = $v["selltax"] / 100;
			$result[$i]["lastPrice"] = $lastPrice;
			//获取进货数量
			/*
			$sql = "select sum(i.in_count) as total_in_count 
				from t_inventory_detail i 
				where (i.ref_type='验收入库' or i.ref_type='自动验收入库') $time_sql_2 and i.goods_id = '".$v['id']."'";
			$data = $db->query($sql);
			*/
			$total_in_count = $v["total_in_count"];
			$result[$i]["total_in_count"] = $total_in_count;
			//获取含税进货价格
			$result[$i]["total_in_money"] = $v["total_in_money"] - $v["reject_money"];
			//计算不含税的进货价格
			$result[$i]["total_in_money_no_tax"] = round($v["total_in_money"] / (1 + $buytax), 2);
			//计算税金
			$result[$i]["total_tax_money"] = $result[$i]["total_in_money"] - $result[$i]["total_in_money_no_tax"];

			$result[$i]["end_balance_count"] = $total_out_count;
			$result[$i]["end_balance_money"] = $total_out_count * $lastPrice;
			$list[] = array($v["code"], $v["name"], $v["barcode"], $v["supplier_code"],$v["supplier_name"] , 
				$v["biz_dt"], $v["total_in_count"], $result[$i]["total_in_money_no_tax"],$result[$i]["total_tax_money"],$v["total_in_money"],
				 $buytax, 0, 0, 0,0,0,0);
		}
		
		//计算合计数据
		$sql = "select  g.buytax, sum(i.goods_count) as total_in_count,
				sum(i.goods_money) as total_in_money , sum(i.reject_money) as reject_money, sum(i.reject_money / (1 + (g.buytax / 100))) as reject_money_no_tax, sum(i.goods_money / (1 + (g.buytax / 100))) as total_in_money_no_tax 
				from t_goods g, t_ia_bill_detail i , t_ia_bill a, t_supplier s
				where g.id = i.goods_id and i.iabill_id = a.id and s.id = a.supplier_id $time_sql_1 $sql1  ";
		$sql = "select  g.buytax, sum(i.goods_count) as total_in_count,
				sum(i.goods_money) as total_in_money , sum(i.reject_money) as reject_money, sum(i.reject_money / (1 + (g.buytax / 100))) as reject_money_no_tax, sum(i.goods_money_no_tax) as total_in_money_no_tax 
				from t_goods g, t_ia_bill_detail i , t_ia_bill a, t_supplier s
				where g.id = i.goods_id and i.iabill_id = a.id and s.id = a.supplier_id $time_sql_1 $sql1  ";
		$sub_total = $db->query($sql);
		//插入统计行
		++$i;
		$result[$i]["total_in_count"] = $sub_total[0]["total_in_count"];
		$result[$i]["total_in_money"] = round($sub_total[0]["total_in_money"], 2) - round($sub_total[0]["reject_money"],2);
		$result[$i]["total_in_money_no_tax"] = round($sub_total[0]["total_in_money_no_tax"] - $sub_total[0]["reject_money_no_tax"], 2);
		$result[$i]["total_tax_money"] = $result[$i]["total_in_money"] - $result[$i]["total_in_money_no_tax"];
		$list[] = array('', '', '', '','' , 
				'', $result[$i]["total_in_count"], $result[$i]["total_in_money_no_tax"],$result[$i]["total_tax_money"],$v["total_in_money"],
				 $buytax, 0, 0, 0,0,0,0);
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '验收报表商品明细导出数据.csv';
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
		
		$sql = "select COUNT(*) as cnt from 
				t_inventory_detail i 
				where 1=1 and (i.ref_type='验收入库' or i.ref_type='自动验收入库') $time_sql_2 $sql2";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}



	/*  退货报表 -- 按照商品明细汇总    */

	public function tuihuoDataByGoods($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$reason = $params["reason"];
		$sql1 = "";
		$sql2 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and a.bizdt >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and a.bizdt <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = " and a.bill_status = 1000 ";
		$sql2 = "";
		if($goods_id){
			$sql1 .= " and i.goods_id = '$goods_id' ";
			$sql2 .= " and i.goods_id = '$goods_id' ";
		}
		if($reason){
			$sql1 .= " and a.reason = '$reason'";
			$sql2 .= " and a.reason = '$reason'";
		}
		//是否包含了供应商帅选
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id   = $supplier_info["id"];
		}
		if($supplier_id){
			$sql1 .= " and a.supplier_id = '$supplier_id' ";
		}
		$result = array();
		$db = M();
		$sql = "select g.id, g.lastbuyprice, g.sale_price, g.gross, g.buytax, g.selltax, g.code, g.name, g.spec, g.barcode,  
				i.goods_count as sale_count,
				i.rejection_goods_count,
				i.rejection_goods_actual_count,
				i.rejection_sale_money as rejection_goods_money,
				a.ref as ref_number,
				a.bizdt ,
				a.reason
				from t_goods g, t_sr_bill a , t_sr_bill_detail i 
				where g.id = i.goods_id and i.srbill_id = a.id  $time_sql_1 $sql1  
				order by a.bizdt desc 
				limit %d, %d";
		if(I("request.act") == 'export'){
			$limit = 20000;
		}
		$items = $db->query($sql, $start, $limit);
		//dump($db->getLastSql());
		$list[] = array('商品编码', '商品名称', '条码',
								'日期', '退货数量', '退货金额','实际入库数' );
		
		foreach ( $items as $i => $v ) {
			$result[$i]["bizDT"] = date("Y-m-d",strtotime($v["bizdt"]));
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["reason"] = $v["reason"];
			$result[$i]["gross"] = $v["gross"];
			$result[$i]["buytax"] = $v["buytax"];
			$result[$i]["ref_number"] = $v["ref_number"];
			$result[$i]["sale_count"] = $v["sale_count"];
			$result[$i]["reject_goods_count"] = $v["rejection_goods_count"];
			$result[$i]["reject_goods_actual_count"] = $v["rejection_goods_actual_count"];
			$result[$i]["reject_goods_money"] = $v["rejection_goods_money"];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["date"] = $v["bizdt"];
			//$result[$i]["ref_number"] = $v["ref_number"];
			$lastPrice = $v["lastbuyprice"];
			$salePrice = $v["sale_price"];
			$buytax = $v["buytax"] / 100;
			$selltax = $v["selltax"] / 100;
			$result[$i]["lastPrice"] = $lastPrice;
			$list[] = array($v["code"],$v["name"],$v["barcode"],$v["bizdt"],$v["reject_goods_count"],$v["reject_goods_money"],$v["reject_goods_actual_count"]);
		}
		
		//计算合计数据
		$sql = "select  
				sum(i.goods_count) as sale_count,
				sum(i.rejection_goods_count) as reject_goods_count,
				sum(i.rejection_goods_actual_count) as reject_goods_actual_count,
				sum(i.rejection_sale_money)  as reject_goods_money 
				from t_sr_bill_detail i where i.srbill_id in 
				(select id from t_sr_bill a where 1 $time_sql_1 $sql1 )";
		$sub_total = $db->query($sql);
		//插入统计行
		++$i;
		$result[$i]["sale_count"] = $sub_total[0]["sale_count"];
		$result[$i]["reject_goods_count"] = $sub_total[0]["reject_goods_count"];
		$result[$i]["reject_goods_actual_count"] = $sub_total[0]["reject_goods_actual_count"];
		$result[$i]["reject_goods_money"] = $sub_total[0]["reject_goods_money"];

		$list[] = array('', '', '', '',
				$result[$i]["reject_goods_count"], $result[$i]["reject_goods_money"],
				$result[$i]["reject_goods_actual_count"]);
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '退货商品明细导出数据.csv';
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
		
		$sql = "select  count(*) as cnt
				from t_sr_bill a , t_sr_bill_detail i 
				where i.srbill_id = a.id  $time_sql_1 $sql1 ";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/*  损溢报表 -- 按照商品明细汇总    */

	public function sunyiDataByGoods($params){
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		$page  = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$reason = $params["reason"];
		$sql1 = "";
		$time_sql_1 = "";
		$time_sql_2 = "";
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and a.bizdt >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and a.bizdt <= '".$end."' ";
		}

		$dt = $params["dt"];
		$goods_code = $params["goods_code"];
		$goods_id   = $params["goods_id"];
		if($goods_code){
			$goods_info = $this->base_get_goods_info($goods_code);
			$goods_id = $goods_info["id"];
		}
		
		$sql1 = " and a.bill_status = 1000 ";
		if($goods_id){
			$sql1 .= " and i.goods_id = '$goods_id' ";
		}
		if($reason){
			$sql1 .= " and a.remark = '$reason'";
		}
		//是否包含了供应商帅选
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id   = $supplier_info["id"];
		}
		if($supplier_id){
			$sql1 .= " and a.supplier_id = '$supplier_id' ";
		}
		$result = array();
		$db = M();
		$sql = "select g.id, g.lastbuyprice, g.sale_price, g.gross, g.buytax, g.selltax, g.code, g.name, g.spec, g.barcode,  
				i.goods_count,
				i.goods_price,
				i.goods_money,
				a.ref as ref_number,
				a.bizdt ,
				a.remark
				from t_goods g, t_il_bill a , t_il_bill_detail i 
				where g.id = i.goods_id and i.itbill_id = a.id  $time_sql_1 $sql1  
				order by a.bizdt desc 
				limit %d, %d";
		if(I("request.act") == 'export'){
			$limit = 20000;
		}
		$items = $db->query($sql, $start, $limit);
//		$sql2 = "select g.id, g.lastbuyprice, g.sale_price, g.gross, g.buytax, g.selltax, g.code, g.name, g.spec, g.barcode,   
//				i.goods_count,
//				i.goods_count_before,
//				a.ref as ref_number,
//				a.bizdt 
//				from t_goods g, t_ic_bill a , t_ic_bill_detail i 
//				where g.id = i.goods_id and i.icbill_id = a.id  $time_sql_1 $sql3  
//				order by a.bizdt desc 
//				limit %d, %d";
		$sql2 = "(select g.id, g.lastbuyprice, g.sale_price, g.gross, g.buytax, g.selltax, g.code, g.name, g.spec, g.barcode,   
				i.goods_count,
				i.goods_count_before,
				a.ref as ref_number,
				a.bizdt ,
				a.remark
				from t_goods g, t_ic_bill a , t_ic_bill_detail i 
				where g.id = i.goods_id and i.icbill_id = a.id  $time_sql_1 $sql1 
				order by a.bizdt desc 
				limit %d, %d) union all (select g.id, g.lastbuyprice, g.sale_price, g.gross, g.buytax, g.selltax, g.code, g.name, g.spec, g.barcode,  
				i.goods_count,
				i.goods_count_before,
				a.ref as ref_number,
				a.bizdt ,
				a.remark
				from t_goods g, t_il_bill a , t_il_bill_detail i 
				where g.id = i.goods_id and i.itbill_id = a.id  $time_sql_1 $sql1 
				order by a.bizdt desc 
				limit %d, %d)";
		$items2 = $db->query($sql2, $start, $limit, $start, $limit);
		//dump(M()->getLastSql());
		//dump($db->getLastSql());
//        $item = array_merge($items,$items2);
		$list[] = array('商品编码', '商品名称', '条码',
								'日期', '损溢数量', '损溢金额','原因' );
		
		foreach ( $items2 as $i => $v ) {
//          if(isset($v["goods_money"])) {
//            
//          }
//          else {
//            $v["goods_count"] = $v["goods_count"]-$v["goods_count_before"];
//            $v["goods_money"] = $v["goods_count"]*$v["lastbuyprice"];
//            $v["remark"] = '盘点';
//          }
			$result[$i]["bizDT"] = date("Y-m-d",strtotime($v["bizdt"]));
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["reason"] = $v["reason"];
			$result[$i]["gross"] = $v["gross"];
			$result[$i]["buytax"] = $v["buytax"];
			$result[$i]["ref_number"] = $v["ref_number"];
			$result[$i]["sale_count"] = $v["sale_count"];
			$result[$i]["goods_count"] = $v["goods_count"]-$v['goods_count_before'];
			$result[$i]["goods_money"] = ($v['goods_count']-$v['goods_count_before'])*$v['sale_price'];
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["date"] = $v["bizdt"];
			$result[$i]["reason"] = $v["remark"];
			//$result[$i]["ref_number"] = $v["ref_number"];
			$lastPrice = $v["lastbuyprice"];
			$salePrice = $v["sale_price"];
			$buytax = $v["buytax"] / 100;
			$selltax = $v["selltax"] / 100;
			$result[$i]["lastPrice"] = $lastPrice;
			$list[] = array($v["code"],$v["name"],$v["barcode"],$v["bizdt"],$result[$i]["goods_count"],$result[$i]["goods_money"],$v["remark"]);
		}
		
		//计算合计数据
		$sql = "select  
				sum(i.goods_count) as goods_count,
				sum(i.goods_price) as goods_price,
				sum(i.goods_money) as goods_money
				from t_il_bill_detail i where i.itbill_id in 
				(select id from t_il_bill a where 1 $time_sql_1 $sql1 )";
		$sub_total = $db->query($sql);
		$sqls = "select  
				sum(i.goods_count) as goods_count,
				sum(i.goods_price) as goods_price,
				sum(i.goods_money) as goods_money
				from t_il_bill_detail i where i.itbill_id in 
				(select id from t_il_bill a where 1 $time_sql_1 $sql1 )";
		$sub_totals = $db->query($sqls);
		//插入统计行
		++$i;
		$result[$i]["goods_count"] = $sub_total[0]["goods_count"]+$sub_totals[0]["goods_count"];
//		$result[$i]["goods_price"] = $sub_total[0]["goods_price"];
		$result[$i]["goods_money"] = $sub_total[0]["goods_money"]+$sub_totals[0]["goods_money"];

		$list[] = array('', '', '', '',
				$result[$i]["goods_count"], $result[$i]["goods_money"]);
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '损溢商品明细导出数据.csv';
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
		
		$sql = "select  count(*) as cnt
				from t_il_bill a , t_il_bill_detail i 
				where i.itbill_id = a.id  $time_sql_1 $sql1 ";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		$sqls = "select  count(*) as cnt
				from t_ic_bill a , t_ic_bill_detail i 
				where i.icbill_id = a.id  $time_sql_1 $sql1 ";
		$datas = $db->query($sqls);
		$cnts = $datas[0]["cnt"];
          $totalCount = $cnts+$cnt;
		if(!$reason){
          $totalCount = $totalCount/2;
		}
		
		return array(
				"dataList" => $result,
				"totalCount" => $totalCount
		);
	}

}