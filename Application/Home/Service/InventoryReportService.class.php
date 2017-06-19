<?php

namespace Home\Service;

/**
 * 库存报表Service
 *
 * @author 李静波
 */
class InventoryReportService extends ERPBaseService {

	/**
	 * 安全库存明细表 - 数据查询
	 */
	public function safetyInventoryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
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
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}
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
		$supplier_sql = "";
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id = $supplier_info["id"];
		}
		if($supplier_id){
			$supplier_sql = " and i.goods_id in (select goodsid from t_supplier_goods where supplierid = '$supplier_id') ";
		}
		$result = array();
		
		$db = M();
		
		$db = M();
		$sql = "select g.id, g.lastbuyprice, g.sale_price, g.gross, g.barcode, g.buytax, g.selltax, g.code, g.name, g.spec, u.name as unit_name, i.balance_count, sum(i.in_count) as total_in_count,
				sum(i.out_count) as total_out_count, sum(i.in_count) as total_in_count ,i.balance_count ,i.goods_id
				from t_goods g, t_goods_unit u, t_inventory_detail i 
				where g.unit_id = u.id and g.id = i.goods_id $time_sql_2 $sql2 $supplier_sql group by i.goods_id 
				order by i.id desc 
				limit %d, %d";
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$data = $db->query($sql, $start, $limit);
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$sql = "select balance_count from t_inventory_detail i where i.goods_id = '".$v["goods_id"]."' $time_sql_2 order by i.id desc limit 1 ";
			$b_data = $db->query($sql);

			$result[$i]["end_balance_count"] = $b_data[0]["balance_count"];
			$result[$i]["end_balance_money"] = $result[$i]["end_balance_count"] * $v["lastbuyprice"];
			$result[$i]["begin_balance_count"] = $result[$i]["end_balance_count"] + $v["total_out_count"] - $v["total_in_count"];
			$result[$i]["begin_balance_money"] = $result[$i]["begin_balance_count"] * $v["lastbuyprice"];
		}
		
		$sql = "select COUNT(DISTINCT i.goods_id) as cnt from 
				t_inventory_detail i 
				where 1=1  $time_sql_2 $sql2 $supplier_sql";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 安全库存调拨明细表 - 数据查询
	 */
	public function transferInventoryQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
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
			$time_sql_2 .= " and i.biz_date >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and i.biz_date <= '".$end."' ";
		}
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
		$supplier_sql = "";
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id = $supplier_info["id"];
		}
		if($supplier_id){
			$supplier_sql = " and i.goods_id in (select goodsid from t_supplier_goods where supplierid = '$supplier_id') ";
		}
		$result = array();
		
		$db = M();
		
		$db = M();
		$sql = "select g.id, g.lastbuyprice, g.sale_price, g.gross, g.barcode, g.buytax, g.selltax, g.code, g.name, g.spec, u.name as unit_name, i.balance_count, sum(i.in_count) as total_in_count,
				sum(i.out_count) as total_out_count, sum(i.in_count) as total_in_count ,i.balance_count ,i.goods_id
				from t_goods g, t_goods_unit u, t_inventory_detail i 
				where g.unit_id = u.id and g.id = i.goods_id $time_sql_2 $sql2 $supplier_sql group by i.goods_id 
				order by i.id desc 
				limit %d, %d";
		if($_REQUEST["act"] == "export"){
			$limit = 10000;
		}
		$data = $db->query($sql, $start, $limit);
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$sql = "select balance_count from t_inventory_detail i where i.goods_id = '".$v["goods_id"]."' $time_sql_2 order by i.id desc limit 1 ";
			$b_data = $db->query($sql);

			$result[$i]["end_balance_count"] = $b_data[0]["balance_count"];
			$result[$i]["end_balance_money"] = $result[$i]["end_balance_count"] * $v["lastbuyprice"];
			$result[$i]["begin_balance_count"] = $result[$i]["end_balance_count"] + $v["total_out_count"] - $v["total_in_count"];
			$result[$i]["begin_balance_money"] = $result[$i]["begin_balance_count"] * $v["lastbuyprice"];
		}
		
		$sql = "select COUNT(DISTINCT i.goods_id) as cnt from 
				t_inventory_detail i 
				where 1=1  $time_sql_2 $sql2 $supplier_sql";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	/**
	 * 库存超上限明细表 - 数据查询
	 */
	public function inventoryUpperQueryData($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$begin = $params["begin"];
		$end   = $params["end"];
		$bill_status = $params["bill_status"];
		$ref   = $params["bill_ref"];
		$sql1 = "";
		$sql2 = "";
		
		$time_sql_1 = "";
		$time_sql_2 = "";
		$ic_bill_sql = "";
		if($bill_status !== ""){
			$ic_bill_sql .= " and b.bill_status = $bill_status ";
		}
		if($ref){
			$ic_bill_sql .= " and b.ref = '$ref' ";
		}
		if($begin){
			$begin = $begin ." 00:00:00";
			$time_sql_1 .= " and w.delivery_date >= '".$begin."' ";
			$time_sql_2 .= " and i.date_created >= '".$begin."' ";
		}
		if($end){
			$end = $end . " 23:59:59";
			$time_sql_1 .= " and w.delivery_date <= '".$end."' ";
			$time_sql_2 .= " and i.date_created <= '".$end."' ";
		}
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
		$supplier_sql = "";
		$supplier_code = $params["supplier_code"];
		if($supplier_code){
			$supplier_info = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id = $supplier_info["id"];
		}
		if($supplier_id){
			$supplier_sql = " and i.goods_id in (select goodsid from t_supplier_goods where supplierid = '$supplier_id') ";
		}
		
		$result = array();
		
		$db = M();
		
		$sql = "select i.*, g.name,g.code,g.spec,g.barcode,g.lastbuyprice,g.sale_price,b.ref, b.bill_status from t_ic_bill_detail i left join t_ic_bill b on b.id = i.icbill_id 
				left join t_goods g on g.id = i.goods_id 
				 where 1=1 $time_sql_2 $sql2 $supplier_sql $ic_bill_sql 
				order by i.date_created desc 
				limit %d, %d";
		if($_REQUEST["act"] == "export"){
			$start = 0;
			$limit = 10000;
		}
		$data = $db->query($sql, $start, $limit);
		$list = array();
		$list[] = array('状态','商品编码', '条码', '商品名称', '规格','进价',
								'盘点前', '盘点后', '差异', '单号', '日期', );
		foreach ( $data as $i => $v ) {
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["ref"] = $v["ref"];
			if($v["bill_status"] == -1){
				$status_str = "盘点中";
			} else if ($v["bill_status"] == 0){
				$status_str = "审核中";
			} else if ($v["bill_status"] == 1000){
				$status_str = "盘点结束";
			}
			$result[$i]["lastPrice"] = $v["lastbuyprice"];
			$result[$i]["bill_status_str"] = $status_str;
			$result[$i]["beforeCount"] = $v["goods_count_before"];
			$result[$i]["goodsCount"] = $v["goods_count"];
			$result[$i]["diffCount"] = $v["goods_count"] - $v["goods_count_before"];
			$result[$i]["date"] = $v["date_created"];
			$list[] = array($status_str,$v["code"],$v["barcode"],$v["name"],$v["spec"], $v["lastbuyprice"],
				$v["goods_count_before"],$v["goods_count"],$result[$i]["diffCount"],$v["ref"],$v["date_created"]);

		}

		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '盘点汇总导出数据.csv';
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
		
		$sql = "select count(*) as cnt from t_ic_bill_detail i  where 1 $time_sql_2 $sql2 $supplier_sql
				";
		
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}
}