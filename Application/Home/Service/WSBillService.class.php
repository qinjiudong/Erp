<?php

namespace Home\Service;

/**
 * 销售出库Service
 *
 * @author dubin
 */
class WSBillService extends ERPBaseService {
	public $orderStatus = array('-1' => '待提交', '0' => '待拣货', '1' => '拣货中' , '2' => '已拣货出库', '3' => '已拣货出库', '4' => '已到站', '5' => '已取货', '6' => '退货');
	public $orderStatusStyle = array(
		"-1" => "color:#ccc",
		"0"  => "color:#aaa",
		"1"  => "color:#888",
		"2"  => "color:#666",
		"3"  => "color:#555",
		"4"  => "color:green",
		"5"  => "color:red",
		"6"  => "color:red"
	);

	public function get_line_by_site($siteid){
		$site = M("site")->where(array("id"=>$siteid))->find();
		$site_list = S("site_list");
		if(!$site_list){
			$site_list = M("site")->select();
			S("site_list", $site_list, 600);
		}
		$lines = S("line_list");
		if(!$lines){
			$line_list = M("site_line")->select();
			$lines = array();
			foreach ($line_list as $key => $value) {
				$lines[$value["id"]] = $value;
			}
			S("line_list", $lines, 600);
		}
		
		
		foreach ($site_list as $key => $value) {
			if($value["id"] == $siteid){
				return $lines[$value['line_id']];
			}
		}
	}

	public function pickbillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$limit = 2000;
		$db = M();
		$order_status = $this->orderStatus;

		

		$result = array();	
		$where = '';

		if(I("request.delivery_time")){//送货时间
			$delivery_time_arr[1] = '上午';
			$delivery_time_arr[2] = '下午';
			$where .= ' and delivery_time = '.I("request.delivery_time").' ';
		}
		$start_time = !empty(I("request.start_time")) ? substr(I("request.start_time"),0, 10) : date('Y-m-d');
		$end_time = !empty(I("request.end_time")) ? substr(I("request.end_time"),0, 10) : date('Y-m-d');
		$order_time_start = I("request.order_time_start");
		$order_time_end = I("request.order_time_end");
		$mobile = I("request.tel");
		//加入时间
		$where .= ' and type in (0,1) and delivery_date >= "' . $start_time . '" and delivery_date <= "' . $end_time . '"';//送货日期
		if($order_time_start){
			//$where .=" and date_created > $order_time_start ";
		}
		if($order_time_end){
			//$where .=" and date_created < $order_time_end ";
		}
		$areaid   = I("request.areaid");
		if($areaid){
			$where .= " and w.siteid in (select id from t_site where line_id = '$areaid') ";//送货区域
		}
		$address  = I("request.address");
		if($address){
			$where .= " and w.address like '%$address%' ";//送货区域
		}
		if($mobile){
			$where .= " and w.tel like '%$mobile%' ";//手机
		}
		if(I("request.pick_type") == 1){//汇总

			$list[] = array('状态','电商单号', '订单号', '订单类型', '收件人', '站点', '线路','区域', '收货地址', '收货电话', '导入日期', '送货日期', '送货时间', '订单金额', '订单ID');
			if(I("request.pick_status")){//拣货状态 1待拣货 2拣货中 3拣货完成
				$pick_status_arr = explode(',', I("request.pick_status"));
				foreach ($pick_status_arr as $v) {
				 	$tmp[] = $v - 1;
				} 
				$where .= ' and (bill_status in (' . implode($tmp, ',') . ')';
				if(in_array(3, $pick_status_arr)){
					$where .= ' or bill_status >=2 ';
				}
				$where .= ')';
			}
			if(I("request.order_sn")){//根据订单搜索
				$where .= ' and ref like "%' . I("request.order_sn") . '%" ';
			}
			//站点搜索
			if($site = I("request.site")){
				$where .= " and sitename = '$site' ";
			}
			$full_child_sql = "";
			$goodsCode = I("request.goods_code");
			$goodsBar = I("request.goods_bar");
			$supplier = I("request.supplier");

			if( $goodsCode || $goodsBar || $supplier){
				$child_sql = "select wsbill_id from t_ws_bill_detail wbd where 1=1 ";
				$only_code = I("request.only_code");
				if($only_code === "true"){
					if($goodsCode){
						$map = array(
							"code" => $goodsCode
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsId){
							$goodsBar = "";
							$supplier = "";
							$child_sql.= " and wsbill_id in (select wsbill_id from t_ws_bill_detail where goods_id = '$goodsId') group by wsbill_id having count(wsbill_id) = 1";
						}
						$full_child_sql = " and w.ref in ($child_sql)";
					}
				} else {
					if($goodsCode){
						$map = array(
							"code" => $goodsCode
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsId){
							$goodsBar = "";
							$supplier = "";
							$child_sql.= " and goods_id = '$goodsId' ";
						}
					}
					if($goodsBar){
						$map = array(
							"barCode" => $goodsBar
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsCode){
							$goodsBar = "";
							$supplier = "";
							$child_sql.= " and goods_id = '$goodsId' ";
						}
					}
					if($supplier && !$supplierid){
						$map = array(
							"name" => array("like", "%$supplier%")
						);
						$supplierid = M("supplier", "t_")->where($map)->getField("id");
					}
					if($supplierid){
						$child_sql.= " and goods_id in (select goodsid from t_supplier_goods where supplierid = '$supplierid') ";
					}
					$full_child_sql = " and w.ref in ($child_sql)";
				}
			}
			$sql = "select w.*, c.name as customer_name, u.name as biz_user_name,
					user.name as input_user_name, h.name as warehouse_name
					from t_ws_bill w, t_customer c, t_user u, t_user user, t_warehouse h 
					where w.customer_id = c.id and w.biz_user_id = u.id " . $where  ." $full_child_sql and w.input_user_id = user.id and w.warehouse_id = h.id 
					order by w.id desc 
					limit $start, $limit";
			$data = $db->query($sql);
			$order_sn_array = array();
			foreach ( $data as $i => $v ) {
				$result[$i]["id"] = $v["id"];
				$result[$i]["order_sn"] = $v["order_sn"];
				if(!in_array($v["order_sn"], $order_sn_array)){
					$order_sn_array[] = $v["order_sn"];
				}
				$result[$i]["ref"] = $v["ref"];
				$result[$i]["billStatus"] = $order_status[$v["bill_status"]];
				$result[$i]["ordertype"] = $v["type"] == '1' ? '补货订单' : '正常订单';
				//$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
				$result[$i]["importDate"] = date("Y-m-d", strtotime($v["bizdt"]));//导入日期
				$result[$i]["delivery_date"] = $v["delivery_date"];//送货日期
				$result[$i]["delivery_time"] = $v["delivery_time"] == 1 ? "上午" : "下午";//送货时间
				$result[$i]["customerName"] = $v["customer_name"];
				//$result[$i]["address"] = $v["sitename"]."-".$v["address"];
				$result[$i]["tel"] = $v["tel"];
				//$result[$i]["consignee"] = $v["consignee"];
				$result[$i]["warehouseName"] = $v["warehouse_name"];
				$result[$i]["inputUserName"] = $v["input_user_name"];
				$result[$i]["bizUserName"] = $v["biz_user_name"];
				$result[$i]["address"] = $v["sitename"]."-".$v["address"];
				$result[$i]["consignee"] = $v["consignee"];
				$result[$i]["tel"] = $v["tel"];
				$result[$i]["amount"] = $v["sale_money"];
				$result[$i]["remark"] = $v["remark"];
				$result[$i]["areaname"] = $v["areaname"];
				$line = $this->get_line_by_site($v["siteid"]);
				$result[$i]["areaname"] = $line["name"];

			$list[] = array($order_status[$v["bill_status"]],$v["order_sn"], $v["ref"], $result[$i]["ordertype"], $v["consignee"],$v["sitename"] , $line["name"],$v["areaname"], $v["address"], $v["tel"],
				 $result[$i]["importDate"], $result[$i]["delivery_date"], $result[$i]["delivery_time"], $v["sale_money"], $v["id"]);
			}
			//加入统计
			$i++;
			$result[$i]["ref"] = count($list) - 1;
			$result[$i]["order_sn"] = count($order_sn_array);
			$list[] = array('',$result[$i]["order_sn"], $result[$i]["ref"], '', '', '', '', '', '', '',
				 '', '', '', '');
			$sql = "select count(*) as cnt 
					from t_ws_bill w, t_customer c, t_user u, t_user user, t_warehouse h 
					where w.customer_id = c.id and w.biz_user_id = u.id " . $where  ." $full_child_sql and w.input_user_id = user.id and w.warehouse_id = h.id";
			$data = $db->query($sql);
			$cnt = $data[0]["cnt"];
		}
		
		if(I("request.pick_type") == 2){//明细
			$child_sql = "select ref from t_ws_bill where type in (0,1) ";
			$where     = "";
			if(I("request.sell_type")){//计数单位
				$bulk = I("request.sell_type") == 2 ? 0 : 1;
				$where .= ' and bulk = "' . $bulk . '"';
			}
			if(I("request.pick_status")){//拣货状态 1待拣货 2拣货中 3拣货完成
				$pick_status_arr = explode(',', I("request.pick_status"));
				foreach ($pick_status_arr as $v) {
				 	$tmp[] = $v - 1;
				} 
				$child_sql .= ' and (bill_status in (' . implode($tmp, ',') . ')';
				if(in_array(3, $pick_status_arr)){
					$child_sql .= ' or bill_status >=2 ';
				}
				$child_sql .= ')';
			}
			if(I("request.order_sn")){//根据订单搜索
				$child_sql .= ' and ref like "%' . I("request.order_sn") . '%" ';
			}
			$full_child_sql = "";
			$site = I("request.site");
			if($site){
				$child_sql .= " and sitename = '$site' ";
			}
			if($areaid){
				$child_sql .= " and siteid in (select id from t_site where line_id = '$areaid') ";
			}
			if($address){
				$child_sql .= " and address like '%$address%'";//送货区域
			}
			if($mobile){
				$child_sql .= " and tel like '%$mobile%' ";//手机
			}	
			$goodsCode = I("request.goods_code");
			$goodsBar = I("request.goods_bar");
			$supplier = I("request.supplier");
			if(I("request.delivery_time")){//送货时间
				$delivery_time_arr[1] = '上午';
				$delivery_time_arr[2] = '下午';
				$child_sql .= ' and delivery_time = '.I("request.delivery_time").' ';
			}
			$start_time = !empty(I("request.start_time")) ? substr(I("request.start_time"),0, 10) : date('Y-m-d');
			$end_time = !empty(I("request.end_time")) ? substr(I("request.end_time"),0, 10) : date('Y-m-d');
			if($start_time){
				$child_sql .= " and delivery_date >= '$start_time' ";//开始送货日期
			}
			if($end_time){
				$child_sql .= " and delivery_date <= '$end_time' ";//结束送货日期
			}
			if( $goodsCode || $goodsBar || $supplier){
				$only_code = I("request.only_code");
				if(false){
					if($goodsCode){
						$map = array(
							"code" => $goodsCode
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsId){
							$goodsBar = "";
							$supplier = "";
							$child_sql.= " and wsbill_id in (select wsbill_id from t_ws_bill_detail where goods_id = '$goodsId') group by wsbill_id having count(wsbill_id) = 1";
						}
						$full_child_sql = " and w.ref in ($child_sql)";
					}
				} else {
					if($goodsCode){
						$map = array(
							"code" => $goodsCode
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsId){
							$goodsBar = "";
							$supplier = "";
							$where.= " and goods_id = '$goodsId' ";
						}
					}
					if($goodsBar){
						$map = array(
							"barCode" => $goodsBar
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsCode){
							$goodsBar = "";
							$supplier = "";
							$where.= " and goods_id = '$goodsId' ";
						}
					}
					if($supplier && !$supplierid){
						$map = array(
							"name" => array("like", "%$supplier%")
						);
						$supplierid = M("supplier", "t_")->where($map)->getField("id");
					}
					if($supplierid){
						$where.= " and goods_id in (select goodsid from t_supplier_goods where supplierid = '$supplierid') ";
					}
					//$full_child_sql = " and w.ref in ($child_sql)";
				}
			}
			$full_child_sql = " and wsbill_id in ($child_sql) ";
			$sql = "select *
					from t_ws_bill_detail
					where 1=1 $where $full_child_sql
					order by id desc 
					limit $start, $limit";
			$data = $db->query($sql);
			$list[] = array('状态', '订单号', '商品名称', '商品编号', '商品条码', '送货日期', '送货时间', '订单类型', '计数单位', '订货重量', '订货份数', '商品单价', '执行重量', '执行份数', '执行单价', '订单商品ID');

			foreach ( $data as $i => $v ) {
				$good_info = $db->query("select *	from t_goods where id like '" . $v['goods_id']  . "'");
				
				$result[$i]["id"] = $v["id"];
				$result[$i]["good_name"] = $good_info[0]["name"];
				$result[$i]["good_code"] = $good_info[0]["code"];
				$result[$i]["barCode"] = $good_info[0]["barcode"];
				$result[$i]["ref"] = $v["wsbill_id"];
				$result[$i]["billStatus"] = $v["is_picked"] == 0 ? '未拣货' : '已拣货';
				$result[$i]["ordertype"] = substr($v["wsbill_id"], 0, 1) == '2' ? '手动订单' : '正常订单';
				$result[$i]["bulk"] = $good_info["bulk"] == 0 ? '按件' : '称重';
				$result[$i]["g_count"] = $v["goods_count"];			//份数
				$result[$i]["goods_money"] = $v["goods_money"];	//单价
				$result[$i]["goods_attr"] = $v["goods_attr"];		//重量
				$result[$i]["apply_price"] = $v["apply_price"];									//执行价格
				$result[$i]["apply_count"] = $v["apply_count"];									//执行份数
				$result[$i]["apply_num"] = $v["apply_num"];										//执行重量
				
				$result[$i]["delivery_date"] = $v["delivery_date"];//送货日期
				$result[$i]["delivery_time"] = $v["delivery_time"];//送货时间
				
			$list[] = array($result[$i]["billStatus"], $result[$i]["ref"], $result[$i]["good_name"], $result[$i]["good_code"], $result[$i]["barCode"],
			 $result[$i]["delivery_date"], $result[$i]["delivery_time"], $result[$i]["ordertype"], $result[$i]["bulk"],
			  $result[$i]["goods_attr"], $result[$i]["g_count"], $result[$i]["goods_money"], $result[$i]["apply_num"], $result[$i]["apply_count"], $result[$i]["apply_price"], $v["id"]);
			}	
			$sql = "select count(*) as cnt
					from t_ws_bill_detail
					where 1=1 $where $full_child_sql";
			$data = $db->query($sql);
			$cnt  = $data[0]["cnt"];
		}
		if(I("request.pick_type") == 3){//预拣
			$child_sql = "select ref from t_ws_bill where type in (0,1)";
			$where     = "";
			if(I("request.sell_type")){//计数单位
				$bulk = I("request.sell_type") == 2 ? 0 : 1;
				$where .= ' and w.bulk = "' . $bulk . '"';
			}
			if(I("request.pick_status")){//拣货状态 1待拣货 2拣货中 3拣货完成
				$pick_status_arr = explode(',', I("request.pick_status"));
				foreach ($pick_status_arr as $v) {
				 	$tmp[] = $v - 1;
				} 
				$child_sql .= ' and (bill_status in (' . implode($tmp, ',') . ')';
				if(in_array(3, $pick_status_arr)){
					//$child_sql .= ' or bill_status >=2 ';
				}
				$child_sql .= ')';
			}
			if(I("request.order_sn")){//根据订单搜索
				$child_sql .= ' and ref like "%' . I("request.order_sn") . '%" ';
			}
			$full_child_sql = "";
			$site = I("request.site");
			if($site){
				$child_sql .= " and sitename = '$site' ";
			}
			if($areaid){
				$child_sql .= " and siteid in (select id from t_site where line_id = '$areaid') ";
			}
			if($address){
				$child_sql .= " and address like '%$address%'";//送货区域
			}
			if($order_time_start){
				//$child_sql .=" and date_created > $order_time_start ";
			}
			if($order_time_end){
				//$child_sql .=" and date_created < $order_time_end ";
			}
			if($mobile){
				$child_sql .= " and tel like '%$mobile%' ";//手机
			}	
			$goodsCode = I("request.goods_code");
			$goodsBar = I("request.goods_bar");
			$supplier = I("request.supplier");
			if(I("request.delivery_time")){//送货时间
				$delivery_time_arr[1] = '上午';
				$delivery_time_arr[2] = '下午';
				$child_sql .= ' and delivery_time = '.I("request.delivery_time").' ';
			}
			$start_time = !empty(I("request.start_time")) ? substr(I("request.start_time"),0, 10) : date('Y-m-d');
			$end_time = !empty(I("request.end_time")) ? substr(I("request.end_time"),0, 10) : date('Y-m-d');
			if($start_time){
				$child_sql .= " and delivery_date >= '$start_time' ";//开始送货日期
			}
			if($end_time){
				$child_sql .= " and delivery_date <= '$end_time' ";//结束送货日期
			}
			if( $goodsCode || $goodsBar || $supplier){
				$only_code = I("request.only_code");
				if(false){
					if($goodsCode){
						$map = array(
							"code" => $goodsCode
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsId){
							$goodsBar = "";
							$supplier = "";
							$child_sql.= " and wsbill_id in (select wsbill_id from t_ws_bill_detail where goods_id = '$goodsId') group by wsbill_id having count(wsbill_id) = 1";
						}
						$full_child_sql = " and w.ref in ($child_sql)";
					}
				} else {
					if($goodsCode){
						$map = array(
							"code" => $goodsCode
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsId){
							$goodsBar = "";
							$supplier = "";
							$where.= " and w.goods_id = '$goodsId' ";
						}
					}
					if($goodsBar){
						$map = array(
							"barCode" => $goodsBar
						);
						$goodsId = M("goods", "t_")->where($map)->getField("id");
						if($goodsCode){
							$goodsBar = "";
							$supplier = "";
							$where.= " and w.goods_id = '$goodsId' ";
						}
					}
					if($supplier && !$supplierid){
						$map = array(
							"name" => array("like", "%$supplier%")
						);
						$supplierid = M("supplier", "t_")->where($map)->getField("id");
					}
					if($supplierid){
						$where.= " and w.goods_id in (select goodsid from t_supplier_goods where supplierid = '$supplierid') ";
					}
					//$full_child_sql = " and w.ref in ($child_sql)";
				}
			}
			$full_child_sql = " and w.wsbill_id in ($child_sql) ";
			$position_info_tmp = $db->query("SELECT p.code, pc.name, pc.full_name, pc.code as p_code  FROM t_position p, t_position_category pc
			WHERE p.position_id = pc.id");
			foreach ($position_info_tmp as $p){
					$position_info[$p['code']] = array('name' => $p['name'], 'fullname' => $p['full_name'], 'p_code' => $p['p_code']);
			}
			//dump($position_info);
			/*
			$sql = "SELECT sum(w.goods_count) as g_count, w.goods_money, s.name as supplier_name, s.code as supplier_code, i.balance_count,  g.*  FROM t_ws_bill_detail w, t_goods g, t_supplier s, t_supplier_goods sg, t_inventory i 
			WHERE w.goods_id = g.id and sg.supplierid = s.id and sg.goodsid = w.goods_id and i.goods_id = w.goods_id $where $full_child_sql 
			group by w.goods_id
			order by w.id desc
			limit $start, $limit";
			*/

			//dump($sql);
			/*
			$sql = "SELECT sum(w.goods_count) as g_count, w.goods_money, s.name as supplier_name, i.balance_count,  g.*  FROM t_ws_bill_detail w, t_goods g, t_supplier s, t_supplier_goods sg, t_inventory i 
			WHERE w.goods_id = g.id and sg.supplierid = s.id and sg.goodsid = w.goods_id $where $full_child_sql 
			group by w.goods_id
			order by w.id desc
			limit $start, $limit";
			*/
			$sql = "SELECT sum(w.goods_count) as g_count, sum(w.apply_num) as g_num, sum(w.apply_count) as g_apply_count, w.goods_money,  g.*  FROM t_ws_bill_detail w, t_goods g  
			WHERE w.goods_id = g.id $where $full_child_sql 
			group by w.goods_id
			order by w.id desc
			limit $start, $limit";
			//如果是要导出汇总数据，则变换sql语句
			if(I("request.export_type") == "2"){
				$sql = "SELECT sum(w.goods_count) as g_count, w.goods_money,w.wsbill_id,  g.name,g.code,g.category_id,s.line_id,l.name as linename FROM t_ws_bill_detail w 
				left join t_goods g on  w.goods_id = g.id
				left join t_ws_bill w1 on w1.ref = w.wsbill_id 
				left join t_site s on s.id = w1.siteid 
left join t_site_line l on l.id = s.line_id
				WHERE w1.delivery_date='$start_time' and w1.delivery_time = '".I("request.delivery_time")."' 
				group by w.goods_id, s.line_id
				order by w.id desc
				limit 10000";
				$data = $db->query($sql);
				$line_arr = array();
				$new_goods_arr = array();
				foreach ($data as $key => $value) {
					//判断是否存在这个商品
					if(array_key_exists($value["code"], $new_goods_arr)){
						$new_goods_arr[$value["code"]][$value['linename']] = $value["g_count"];
						$new_goods_arr[$value["code"]]['total_count'] += $value["g_count"];
					} else {
						$new_goods_arr[$value["code"]] = $value;
						$new_goods_arr[$value["code"]][$value['linename']] = $value["g_count"];
						$new_goods_arr[$value["code"]]['total_count'] = $value["g_count"];
					}
					if(in_array($value['linename'], $line_arr)){

					} else {
						$line_arr[] = $value['linename'];
					}
				}
				$arr = array('商品编号', '商品名称', '总份数');
				foreach ($line_arr as $line) {
					$arr[] = $line;
				}
				$list[] = $arr;
				foreach ($new_goods_arr as $g) {
					$arr = array(
						$g['code'],$g['name'],$g['total_count']
					);
					foreach ($line_arr as $line) {
						$arr[] = $g[$line];
					}
					$list[] = $arr;
				}
				$change_name = $start_time . '--' . $end_time . '预检路线汇总数据.csv';
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="'.$change_name.'"');
				header('Cache-Control: max-age=0');
				$file = fopen('php://output', 'a');
				foreach ($list as $k => $line){
					//$list[$k] = iconv('utf-8', 'gbk', $line);
					foreach ($line as $key => $value) {
						$list[$k][$key] = iconv('utf-8', 'gbk', $value);
					}
	  				
	  			}
	  			foreach ($list as $key => $value) {
	  				fputcsv($file,$value);
	  			}
				fclose($file);
				exit;
			}
			$data = $db->query($sql);
			$list[] = array('商品编号', '商品名称', '商品条码', '计数单位', '订货重量', '订货份数', '商品单价', '仓位编码', '仓位名称', '供应商','供应商编码','库存','重量','数量');
			$total_goods_count = 0;
			foreach ( $data as $i => $v ) {
				$result[$i]["id"] = $v["id"];
				$result[$i]["good_code"] = $v["code"];
				$result[$i]["good_name"] = $v["name"];
				$result[$i]["barCode"] =   $v["barcode"];
				$result[$i]["ordertype"] = substr($v["wsbill_id"], 0, 1) == '2' ? '手动订单' : '正常订单';
				$result[$i]["bulk"] = $v["bulk"] == 0 ? '计重' : '计个';
				$result[$i]["g_count"] = $v["g_count"];		//份数
				$total_goods_count += $v["g_count"];
				$result[$i]["goods_money"] = $v["sale_price"]; //$v["goods_money"];	//单价
				$result[$i]["goods_attr"] =  $v["g_count"] * $v["convert"];		//重量
				$result[$i]["position_code"] = $position_info[$v["code"]]['p_code'];
				$result[$i]["position_name"] = $position_info[$v["code"]]['fullname'];
				$result[$i]["good_name"] = $v["name"];
				//获取supplierid
				$map = array(
					"goodsid" => $v["id"]
				);
				$supplier_id = M("supplier_goods")->where($map)->order("id desc")->getField("supplierid");
				$map = array(
					"id" => $supplier_id
				);
				$supplier = M("supplier")->where($map)->find();
				$result[$i]["supplier_name"] = $supplier["name"];
				$result[$i]["supplier_code"] = $supplier["code"];
				$result[$i]["stock"] = $this->base_get_goods_stock($v["id"]);
				$list[] = array($result[$i]["good_code"], $result[$i]["good_name"], $result[$i]["barCode"], $result[$i]["bulk"],

				$result[$i]["goods_attr"], $result[$i]["g_count"], $result[$i]["goods_money"], $result[$i]["position_code"], $result[$i]["position_name"],$result[$i]["supplier_name"],$result[$i]["supplier_code"],$result[$i]["stock"],$v["g_num"], $v["g_apply_count"]);

			}
			$i++;
			$result[$i]['id'] = 0;
			$result[$i]["good_code"] = "汇总";
			$result[$i]['g_count'] = $total_goods_count;
			$list[] = array($result[$i]["good_code"], $result[$i]["good_name"], $result[$i]["barCode"], $result[$i]["bulk"],
				$result[$i]["goods_attr"], $result[$i]["g_count"], $result[$i]["goods_money"], $result[$i]["position_code"], $result[$i]["position_name"],$result[$i]["supplier_name"],$result[$i]["supplier_code"],$result[$i]["stock"]);
			$sql = "SELECT count(DISTINCT w.goods_id) as cnt, w.goods_id FROM t_ws_bill_detail w where 1=1 $where $full_child_sql group by w.goods_id";
			//dump($sql);
			//$data = $db->query($sql);
			//$cnt = $data[0]["cnt"];
			$cnt = count($result);
		}



		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$pick_type = array(1 => '汇总', 2 => '明细', 3 => '预拣');
			$change_name = $start_time . '--' . $end_time . $pick_type[I("request.pick_type")] . '数据.csv';
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
		return array(
				"dataList" => $result,
				"totalCount" => $cnt,
				"totalProperty" => $cnt
		);
	}

	public function wsBillInfo($params) {
		$id = $params["id"];
		$us = new UserService();
		$result = array();
		$result["canEditGoodsPrice"] = $this->canEditGoodsPrice();
		$result["canEditGoodsPrice"] = true;
		
		if (! $id) {
			// 新建销售出库单
			$result["bizUserId"] = $us->getLoginUserId();
			$result["bizUserName"] = $us->getLoginUserName();
			
			$ts = new BizConfigService();
			if ($ts->warehouseUsesOrg()) {
				$ws = new WarehouseService();
				$data = $ws->getWarehouseListForLoginUser("2002");
				if (count($data) > 0) {
					$result["warehouseId"] = $data[0]["id"];
					$result["warehouseName"] = $data[0]["name"];
				}
			} else {
				$db = M();
				$sql = "select value from t_config where id = '2002-02' ";
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
			return $result;
		} else {
			// 编辑
			$db = M();
			$sql = "select w.id, w.ref, w.bill_status, w.bizdt, w.type, w.tel, w.address, w.consignee, c.id as customer_id, c.name as customer_name, 
					  u.id as biz_user_id, u.name as biz_user_name, 
					  h.id as warehouse_id, h.name as warehouse_name 
					from t_ws_bill w, t_customer c, t_user u, t_warehouse h 
					where w.customer_id = c.id and w.biz_user_id = u.id 
					  and w.warehouse_id = h.id 
					  and w.id = '%s' ";
			$data = $db->query($sql, $id);
			$type = $data[0]["type"];
			if ($data) {
				$result["ref"] = $data[0]["ref"];
				$result["billStatus"] = $data[0]["bill_status"];
				$result["bizDT"] = date("Y-m-d", strtotime($data[0]["bizdt"]));
				$result["customerId"] = $data[0]["customer_id"];
				$result["customerName"] = $data[0]["customer_name"];
				$result["warehouseId"] = $data[0]["warehouse_id"];
				$result["warehouseName"] = $data[0]["warehouse_name"];
				$result["bizUserId"] = $data[0]["biz_user_id"];
				$result["bizUserName"] = $data[0]["biz_user_name"];
				$result["tel"] = $data[0]["tel"];
				$result["address"] = $data[0]["address"];
				$result["consignee"] = $data[0]["consignee"];
			}
			
			$sql = "select d.*, g.id as goods_id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, d.goods_count, 
					d.goods_price, d.goods_money 
					from t_ws_bill_detail d, t_goods g, t_goods_unit u 
					where d.wsbill_id = '%s' and d.goods_id = g.id and g.unit_id = u.id
					order by d.show_order";
			$data = $db->query($sql, $result["ref"]);
			$items = array();
			foreach ( $data as $i => $v ) {
				$items[$i]["id"] = $v["id"];
				$items[$i]["goodsId"] = $v["goods_id"];
				$items[$i]["goodsCode"] = $v["code"];
				$items[$i]["goodsName"] = $v["name"];
				$items[$i]["goodsSpec"] = $v["spec"];
				$items[$i]["unitName"] = $v["unit_name"];
				$items[$i]["goodsCount"] = $v["goods_count"];
				$items[$i]["goodsPrice"] = $v["goods_price"]; //$v["goods_price"];
				$items[$i]["goodsMoney"] = $v["goods_money"];
				$items[$i]["bulk"] = $v["bulk"];
				$items[$i]["bulkStr"] = $v["bulk"] == 0 ? '计重' : '计件';
				$items[$i]["g_count"] = $v["goods_count"];			//份数
				$items[$i]["goods_money"] = $v["goods_money"];	//单价
				$items[$i]["goods_attr"] = $v["goods_attr"];		//重量
				if($v["bulk"] == 0){
					$items[$i]["apply_price"] = $v["apply_price"] ? $v["apply_price"] : $v["goods_money"];									//执行价格
					$items[$i]["apply_count"] = $v["apply_count"] ? $v["apply_count"] : $v["goods_count"];									//执行份数
					$items[$i]["apply_num"]   = $v["apply_num"];
				} else {
					$items[$i]["apply_price"] = $v["apply_price"] ? $v["apply_price"] : $v["goods_money"];									//执行价格
					$items[$i]["apply_count"] = $v["apply_count"] ? $v["apply_count"] : $v["goods_count"];									//执行份数
					$items[$i]["apply_num"]   = 0;
				}
				if($type == 10){
					if($v["bulk"] == 0){
						$items[$i]["unitName"] = "kg";
						$items[$i]["goodsCount"] = $v["apply_num"];
					} else {
						
					}
				}
											//执行重量
				$items[$i]["remark"] = $v["remark"];
				$items[$i]["delivery_date"] = $v["delivery_date"];//送货日期
				$items[$i]["delivery_time"] = $v["delivery_time"];//送货时间
			}
			
			$result["items"] = $items;
			
			return $result;
		}
	}

	/**
	 * 判断是否可以编辑商品销售单价
	 *
	 * @return boolean true:可以编辑销售单价
	 */
	private function canEditGoodsPrice() {
		// 首先判断业务设置中是否允许销售出库编辑销售单价（全局控制）
		$db = M();
		$sql = "select value from t_config where id = '2002-01' ";
		$data = $db->query($sql);
		if (! $data) {
			return false;
		}
		
		$v = intval($data[0]["value"]);
		if ($v == 0) {
			return false;
		}
		
		$us = new UserService();
		// 在业务设置中启用编辑的前提下，还需要判断对应的权限（具体的用户）
		return $us->hasPermission("2002-01");
	}


	/* 新增或编辑批发出库单 */
	public function editBatchWSBill($params){
		$json = $params["jsonStr"];
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$warehouseId = $bill["warehouseId"];
		$customerId = $bill["customerId"];
		$tel = $bill["telNumber"];
		$address = $bill["AddressInfo"];
		$consign = $bill["ConsignInfo"];
		$bizUserId = $bill["bizUserId"];
		$BillType  = $bill["BillType"] ? $bill["BillType"] : 10;
		$items = $bill["items"];
		$db = M();
		$sql = "select * from t_customer where id = '%s' ";
		$data = $db->query($sql, $customerId);
		$user_info = $data[0];
		if(!$data){
			return $this->bad("客户信息不存在");
		}
		$idGen = new IdGenService();
		if ($id) {
			// 编辑
			$sql = "select ref, bill_status from t_ws_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $this->bad("要编辑的单子不存在");
			}
			$ref = $data[0]["ref"];
			$billStatus = $data[0]["bill_status"];
			if ($billStatus != 0) {
				return $this->bad("订单[单号：{$ref}]已经提交出库了，不能再编辑");
			}
			
			$db->startTrans();
			try {
				$sql = "delete from t_ws_bill_detail where wsbill_id = '%s' ";
				$db->execute($sql, $ref);
				$sql = "insert into t_ws_bill_detail (
						id, date_created, goods_id, goods_count, goods_price, goods_money,
						show_order, wsbill_id, sell_type) 
						values ('%s', now(), '%s', %d, %f, %f, %d, '%s', %d)";
				//判断是否同时包含冷藏和常温的商品
				$storage = "";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					$goodsCount = $v["goodsCount"];
					$goods_info = $this->base_get_goods_info($goodsId);
					
					if ($goodsId && $goods_info) {
						if(!$v["goodsCount"]){
							continue;
						}
						$goodsPrice = floatval($v["goodsPrice"]);
						$goodsMoney = $goodsCount * $goodsPrice;
						if($goods_info["bulk"] == 0){
							$apply_num = $goodsCount;
							$apply_price = $goodsMoney;
							$apply_money = $goodsMoney;
							$goodsCount = 1;
							$apply_count = 1;
						} else {
							$apply_num = 0;
							$apply_price = $goodsMoney;
							$apply_money = $goodsMoney;
							$apply_count = $goodsCount;
						}
						$detail = array(
							"id" => $idGen->newId(),
							"date_created" => date("Y-m-d H:i:s"),
							"goods_id" => $goodsId,
							"goods_count" => $goodsCount,
							"goods_price" => $goodsPrice,
							"goods_money" => $goodsMoney,
							"show_order" => $i,
							"wsbill_id" => $ref,
							"sell_type" => $goods_info["bulk"],
							"bulk" => $goods_info["bulk"],
							"apply_count" => $apply_count,
							"apply_num" => $apply_num,
							"apply_price" => $apply_price
						);
						M("ws_bill_detail")->add($detail);
						//$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $i, $id, $goods_info["bulk"]);
					}
				}
				$sql = "select sum(goods_money) as sum_goods_money from t_ws_bill_detail where wsbill_id = '%s' ";
				$data = $db->query($sql, $ref);
				$sumGoodsMoney = $data[0]["sum_goods_money"];
				
				$sql = "update t_ws_bill 
						set sale_money = %f, customer_id = '%s', warehouse_id = '%s', 
						biz_user_id = '%s', bizdt = '%s', consignee='%s', tel='%s', address='%s' 
						where id = '%s' ";
				$db->execute($sql, $sumGoodsMoney, $customerId, $warehouseId, $bizUserId, $bizDT, $consign, $tel, $address, $id);
				
				$log = "编辑订单，单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "手动销售出库");
				
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		} else {
			// 新增
			$db->startTrans();
			try {
				$id = $idGen->autoId('t_ws_bill');
				$ref = $this->genNewBillRef();
				$us = new UserService();
				$order_data = array(
					"id" => "",
					"bill_status" => 0,
					"bizdt" => $bizDT,
					"biz_user_id" => $bizUserId,
					"customer_id" => $customerId,
					"consignee" => $consign,
					"tel" => $tel,
					"address" => $address,
					"date_created" => date("Y-m-d H:i:s"),
					"input_user_id" => $us->getLoginUserId(),
					"ref" => $ref,
					"warehouse_id" => $warehouseId,
					"type" => $BillType,
					"delivery_date" => date("Y-m-d")
				);
				$id = M("ws_bill")->add($order_data);
				foreach ( $items as $i => $v ) {

					$goodsId = $v["goodsId"];
					$goodsCount = $v["goodsCount"];
					$goods_info = $this->base_get_goods_info($goodsId);
					
					if ($goodsId && $goods_info) {
						if(!$v["goodsCount"]){
							continue;
						}
						$goodsPrice = floatval($v["goodsPrice"]);
						$goodsMoney = $goodsCount * $goodsPrice;
						if($goods_info["bulk"] == 0){
							$apply_num = $goodsCount;
							$apply_price = $goodsMoney;
							$apply_money = $goodsMoney;
							$goodsCount = 1;
							$apply_count = 1;
						} else {
							$apply_num = 0;
							$apply_price = $goodsMoney;
							$apply_money = $goodsMoney;
							$apply_count = $goodsCount;
						}
						$detail = array(
//							"id" => $idGen->newId(),
							"id" => '',
							"date_created" => date("Y-m-d H:i:s"),
							"goods_id" => $goodsId,
							"goods_count" => $goodsCount,
							"goods_price" => $goodsPrice,
							"goods_money" => $goodsMoney,
							"show_order" => $i,
							"wsbill_id" => $ref,
							"sell_type" => $goods_info["bulk"],
							"bulk" => $goods_info["bulk"],
							"apply_count" => $apply_count,
							"apply_num" => $apply_num,
							"apply_price" => $apply_price
						);
						/*
						dump($detail);
						$db->rollback();
						exit;
						*/
						M("ws_bill_detail")->add($detail);
						//$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $i, $id, $goods_info["bulk"]);
					}
					
				}
				$sql = "select sum(goods_money) as sum_goods_money from t_ws_bill_detail where wsbill_id = '%s' ";
				$data = $db->query($sql, $ref);
				$sumGoodsMoney = $data[0]["sum_goods_money"];
				
				$sql = "update t_ws_bill set sale_money = %f where id = '%s' ";
				$db->execute($sql, $sumGoodsMoney, $id);
				
				$log = "新增订单，单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "手动销售出库");
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		}
		
		return $this->ok($id);
	}

	/**
	 * 新增或编辑电商销售出库单
	 */
	public function editWSBill($params) {
		$json = $params["jsonStr"];
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$warehouseId = $bill["warehouseId"];
		$customerId = $bill["customerId"];
		$bizUserId = $bill["bizUserId"];
		$WSBillId  = $bill["WSBillId"] ? $bill["WSBillId"] : "";
		$BillType  = $bill["BillType"] ? $bill["BillType"] : 0;
		$items = $bill["items"];
		$delivery_time = $bill["deliveryTime"];
		$delivery_date = substr($bill["deliveryDate"],0,10);
		$siteid = $bill["siteId"];
		$address = $bill["address"];
		$consignee  = $bill["consignee"];
		$mobile  = $bill["mobile"];
		//return $this->bad($delivery_time);
		$db = M();
		$sql = "select * from t_customer where id = '%s' ";
		$data = $db->query($sql, $customerId);
		$user_info = $data[0];
		if(!$address){
			$address = $user_info["address"];
		}
		if(!$mobile){
			$mobile = $user_info["mobile01"];
		}
		if(!$consignee){
			$consignee = $user_info["contact01"];
		}
		$idGen = new IdGenService();
		$site = M("site")->where(array("id"=>$siteid))->find();
		if(!$site){
			return $this->bad("站点不存在");
		}
		$area = $this->get_area_by_siteid($siteid);

		if ($id) {
			// 编辑
			$sql = "select ref, bill_status from t_ws_bill where id = '%s' ";
			$data = $db->query($sql, $id);
			if (! $data) {
				return $this->bad("要编辑的单子不存在");
			}
			$ref = $data[0]["ref"];
			$billStatus = $data[0]["bill_status"];
			if ($billStatus != 0) {
				return $this->bad("订单[单号：{$ref}]已经提交出库了，不能再编辑");
			}
			
			$db->startTrans();
			try {
				$sql = "delete from t_ws_bill_detail where wsbill_id = '%s' ";
				$db->execute($sql, $id);
				$sql = "insert into t_ws_bill_detail (id, date_created, goods_id, 
						goods_count, goods_price, goods_money, 
						show_order, wsbill_id, sell_type, goods_code) values ('%s', now(), '%s', %d, %f, %f, %d, '%s', %d, '%s')";
				//判断是否同时包含冷藏和常温的商品
				$storage = "";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					$goods_info = $this->base_get_goods_info($goodsId);
					if($storage === ""){
						$storage = $goods_info["storage"];
					} else {
						if($goods_info["storage"] === $storage){

						} else {
							return $this->bad("一个订单中不能同时包含常温和冷藏商品");
						}
					}
					
					if ($goodsId) {
						$goodsCount = intval($v["goodsCount"]);
						$goodsPrice = floatval($v["goodsPrice"]);
						$goodsMoney = $goodsCount * $goodsPrice;
						
						$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $i, $id, $goods_info["bulk"], $goods_info["code"]);
					}
				}
				$sql = "select sum(goods_money) as sum_goods_money from t_ws_bill_detail where wsbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$sumGoodsMoney = $data[0]["sum_goods_money"];
				
				$sql = "update t_ws_bill 
						set sale_money = %f, customer_id = '%s', warehouse_id = '%s', 
						biz_user_id = '%s', bizdt = '%s' 
						where id = '%s' ";
				$db->execute($sql, $sumGoodsMoney, $customerId, $warehouseId, $bizUserId, $bizDT, $id);
				
				$log = "编辑订单，单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "销售出库");
				
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		} else {
			// 新增
			$db->startTrans();
			try {
				$id = $idGen->autoId('t_ws_bill');
				if($BillType == 1){
					$map = array(
						"id" => $WSBillId
					);
					$old_ref = M("ws_bill", "t_")->where($map)->getField("ref");
					$old_order = M("ws_bill", "t_")->where($map)->find();
					if(!$old_ref){
						return $this->bad("不存在该订单需要补货");
					}
					$ref = $this->genNewBillRefByRef($old_ref);
				} else {
					$old_order = array(
						"order_id" => "",
						"order_sn" => ""
					);
					$ref = $this->genNewBillRef();
				}
				
				$sql = "insert into t_ws_bill(id, bill_status, bizdt, biz_user_id, customer_id,  date_created,
						input_user_id, ref, warehouse_id,	consignee,address,tel, delivery_time, delivery_date, type, rel_id, order_id, order_sn, siteid,sitename,areaid,areaname) 
						values ('%s', 0, '%s', '%s', '%s', now(), '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')";
				$us = new UserService();
				$db->execute($sql, $id, $bizDT, $bizUserId, $customerId, $us->getLoginUserId(), $ref, $warehouseId,
					 $consignee, $address, $mobile, $delivery_time, $delivery_date, $BillType, $WSBillId, $old_order["order_id"], $old_order["order_sn"], $site["id"],$site["name"],$area["id"],$area["name"]);

				$sql = "insert into t_ws_bill_detail (date_created, goods_id, 
						goods_count, goods_price, goods_money,
						show_order, wsbill_id, sell_type, goods_code) values (now(), '%s', %f, %f, %f, %d, '%s', %d, '%s')";
				//判断是否同时包含冷藏和常温的商品
				$storage = "";
				foreach ( $items as $i => $v ) {
					$goodsId = $v["goodsId"];
					$goods_info = $this->base_get_goods_info($goodsId);
					if($storage === ""){
						$storage = $goods_info["storage"];
					} else {
						if($goods_info["storage"] === $storage){

						} else {
							return $this->bad("一个订单中不能同时包含常温和冷藏商品");
						}
					}
					if ($goodsId) {
						$goodsCount = intval($v["goodsCount"]);
						$goodsPrice = floatval($v["goodsPrice"]);
						$goodsMoney = $goodsCount * $goodsPrice;
						
						$db->execute($sql, $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $i, $ref, $good_info["bulk"],$goods_info["code"]);
					}
					
				}
				$sql = "select sum(goods_money) as sum_goods_money from t_ws_bill_detail where wsbill_id = '%s' ";
				$data = $db->query($sql, $ref);
				$sumGoodsMoney = $data[0]["sum_goods_money"];
				
				$sql = "update t_ws_bill set sale_money = %f where id = '%s' ";
				$db->execute($sql, $sumGoodsMoney, $id);
				
				$log = "新增订单，单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "销售出库");
				$db->commit();
			} catch ( Exception $ex ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		}
		
		return $this->ok($id);
	}

	/**
	 * 生成新的销售出库单单号
	 *
	 * @return string
	 */
	private function genNewBillRef() {
		$pre = "2";
		$mid = date("Ymd");
		
		$sql = "select ref from t_ws_bill where ref like '%s' order by id desc limit 1";
		$data = M()->query($sql, $pre . $mid . "%");
		$sufLength = 3;
		$suf = str_pad("1", $sufLength, "0", STR_PAD_LEFT);
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, 9)) + 1;
			$suf = str_pad($nextNumber, $sufLength, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	/**
	 * 根据订单号，生成新的补货单号
	 *
	 * @return string
	 */
	public function genNewBillRefByRef($ref){
		$len = strlen($ref);
		$pre = "";
		for ($i=0; $i < $len-1 ; $i++) { 
			$pre.=$ref{$i};
		}
		$suf = $ref{$len};
		$suf++;
		//如果是1则继续自增，因为1是冷藏单保留单号
		if($suf == 1){
			$suf++;
		}
		$newref = $pre.$suf;
		$db = M("ws_bill", "t_");
		$map = array(
			"ref" => $newref
		);
		while ($order = $db->where($map)->find()) {
			$suf++;
			$newref = $pre.$suf;
			$map["ref"] = $newref;
			if($suf > 100){
				break;
			}
		}
		return $newref;
	}

	public function wsbillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$type  = $params["type"];
		$startdate = $params["startdate"];
		$enddate   = $params["enddate"];
		$mobile    = $params["mobile"];
		$username  = $params["username"];
		$ordertype = $params["ordertype"];
		$search_add_time_start = $params["search_add_time_start"];
		$search_add_time_end = $params["search_add_time_end"];
		$search_mall_order_ref = $params["search_mall_order_ref"];
		$search_bill_status = $params["search_bill_status"];
		$search_customer    = $params["search_customer"];
		$search_sql = " and 1 = 1 ";
		$is_inout = true;
		$inout_sql = "";
		if($type == 1){
			$search_sql .= " and w.type = $type ";
			$is_inout = false;
		}
		if($search_add_time_start){
			$search_add_time_start = str_replace("T", " ", $search_add_time_start);
			$search_sql .= " and w.date_created >= '$search_add_time_start' ";
			$is_inout = false;
		}
		if($search_add_time_end){

			$search_add_time_end = str_replace("T", " ", $search_add_time_end);
			if($search_add_time_end == $search_add_time_start){
				$search_add_time_end = date("Y-m-d H:i:s", strtotime($search_add_time_end) + 24*3600);
			}
			$search_sql .= " and w.date_created <= '$search_add_time_end' ";
			$is_inout = false;
		}
		if($search_bill_status != -2 && $search_bill_status !== ""){
			$search_sql .= " and w.bill_status = '$search_bill_status' ";
			$is_inout = false;
		}
		if($search_mall_order_ref){
			$search_sql .= " and (w.order_sn = '$search_mall_order_ref' or w.ref = '$search_mall_order_ref')  ";
			$is_inout = false;
		}
		if($startdate){
			$startdate = str_replace("T", " ", $startdate);
			$startdate = substr($startdate,0,10). " 00:00:00";
			$search_sql .= " and w.delivery_date >= '$startdate' ";
			$inout_sql .= " and biz_date >= '$startdate' ";
		}
		if($enddate){
			$enddate = str_replace("T", " ", $enddate);
			$enddate = substr($enddate,0,10)." 23:59:59";
			$search_sql .= " and w.delivery_date <= '$enddate' ";
			$inout_sql  .= " and biz_date <= '$enddate' ";
		}
		if($mobile){
			$search_sql .= " and w.tel = '$mobile'";
			$is_inout = false;
		}
		if($username){
			$search_sql .= " and w.consignee = '$username'";
			$is_inout = false;
		}
		if($sitename){
			$search_sql .= " and w.sitename = '$sitename'";
			$is_inout = false;
		}
		if($areaname){
			$search_sql .= " and w.areaname = '$areaname'";
			$is_inout = false;
		}
		$search_id = "select id from t_customer where code = '$search_customer' or name like '%$search_customer%'";
		$idres = M()->query($search_id);
		foreach ($idres as $k=>$v){
			$idarr[] = $v['id'];
		}
		$idstr = implode("','",$idarr);		
		if($search_customer){
			//$search_sql .= " and w.customer_id in (select id from t_customer where code = '$search_customer' or name='$search_customer') ";
			$search_sql .= " and w.customer_id in ('$idstr') ";
			$is_inout = false;
		}
		if($ordertype == 1){
			$search_sql .= " and w.type in (0,1)";
			$is_inout = false;
		} else if ($ordertype == 10){
			$search_sql .= " and w.type = 10";
			$is_inout = false;
		} else {

		}
		if(I("request.act") == 'export'){
			$limit = 1000;
		}
		$db = M();
		$sql = "select w.id, w.ref, w.bizdt, w.date_created, w.siteid, w.sitename, w.delivery_date, w.type, w.order_sn,w.shipping_fee, 
				w.discount, w.remark, w.delivery_time, w.remark, w.storage, w.cabinetno, w.boxno, w.pick_code, w.stock_time, 
				c.name as customer_name, u.name as biz_user_name,w.address,w.consignee,w.tel,
				user.name as input_user_name, h.name as warehouse_name, w.sale_money,w.reject_money,
				w.bill_status 
				from t_ws_bill w, t_customer c, t_user u, t_user user, t_warehouse h 
				where w.customer_id = c.id and w.biz_user_id = u.id $search_sql 
				  and w.input_user_id = user.id and w.warehouse_id = h.id 
				order by w.delivery_date desc, w.date_created desc 
				limit %d, %d";
		$data = $db->query($sql, $start, $limit);
		$result = array();
		$order_status = $this->orderStatus;

		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["order_sn"] = $v["order_sn"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["bizDate"] = $v["date_created"];
			$result[$i]["customerName"] = $v["customer_name"];
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["address"] = $v["sitename"].$v["address"];
			$result[$i]["deliveryTime"] = $v["delivery_date"].($v["delivery_time"] == 1 ? " 上午":" 下午");

			$result[$i]["consignee"] = $v["consignee"];
			$result[$i]["tel"] = $v["tel"];
			$result[$i]["billStatus"] = $order_status[$v["bill_status"]];
			$result[$i]["billStatusStr"] = "<span style='".$this->orderStatusStyle[$v["bill_status"]]."'>".$order_status[$v["bill_status"]]."</span>";
			$result[$i]["amount"] = $v["sale_money"] - $v["reject_money"];
			$result[$i]["shipping_fee"] = $v["shipping_fee"];
			$result[$i]["discount"] = $v["discount"];
			$result[$i]["realamount"] = $v["sale_money"] - $v["reject_money"] - $v["discount"] + $v["shipping_fee"];
			$result[$i]["remark"] = $v["remark"];
			$result[$i]["pick_code"] = $v["pick_code"];
			$result[$i]["stock_time_str"] = $v['stock_time'] ? date("Y-m-d H:i:s", $v['stock_time']) : "";
			$result[$i]["siteid"] = $v["siteid"];
			$result[$i]["sitename"] = $v["sitename"];
			//获取柜子信息
			if($v["cabinetno"]){
				$box_info = $this->get_box_info_by_no($v["cabinetno"], $v["boxno"]);
				$box_info = $v["pick_code"] ? $box_info."提取码".$v["pick_code"] : $box_info;
			} else {
				$box_info = "";
			}
			$result[$i]["box"] = $box_info;
			if($v["type"] == 10 && I("request.act") != 'export'){
				$result[$i]["deliveryTime"]  = "";
				if($v["bill_status"] == 1000){
					$result[$i]["billStatusStr"] = "已出库";
				}
				//$result[$i]["billStatusStr"] = $v["bill_status"] == 1000 ? "已出库":"未出库";
				$result[$i]["type"]  = $v["type"];
				//要查询是否已经付款了
				$map = array(
					"ref_number" => $v["ref"]
				);
				$rv = M("receivables_detail")->where($map)->find();
				if($rv["act_money"] <= 0){
					$result[$i]["payStatusStr"] = "未付款";
				} else {
					$result[$i]["payStatusStr"] = "<span style='color:green'>已付款</span>";
				}
				$result[$i]["billStatusStr"] = "[".$result[$i]["payStatusStr"]."]".$result[$i]["billStatusStr"];
			}
		}
		$sql = "select count(*) as cnt, sum(w.sale_money) as total_sale_money, sum(w.discount) as total_discount_money ,sum(w.reject_money) as total_reject_money 
				from t_ws_bill w, t_customer c, t_user u, t_user user, t_warehouse h 
				where w.customer_id = c.id and w.biz_user_id = u.id $search_sql 
				  and w.input_user_id = user.id and w.warehouse_id = h.id ";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		$totalAmount = $data[0]["total_sale_money"];
		$totalDiscountAmount = $data[0]["total_discount_money"];
		$totalRejectMoney = $data[0]["total_reject_money"];

		//计算销售金额
		if(!$is_inout){
				$sql = "select sum(apply_price) as total_money, sum(reject_money) as reject_money 
				from t_ws_bill_detail where wsbill_id in (select ref from t_ws_bill w where 1 $search_sql )";
				if($type == 10){
					$sql = "select sum(sale_money) as total_money, sum(reject_money) as reject_money 
					from t_ws_bill w where 1 $search_sql";
				}
				$data = $db->query($sql);
		} else {
			$sql = "select sum(sale_money) as total_money  
				from t_inout_day where 1 $inout_sql ";
				$data = $db->query($sql);
		}
		
		$data = $db->query($sql);
		//加入统计行
		if(count($result) > 0){
			//获取退货的金额
			++$i;
			$result[$i]["amount"] = $data[0]["total_money"];
			$result[$i]["discount"] = $totalDiscountAmount;
			//净销售
			$result[$i]["address"] = "净销售:".($result[$i]["amount"] - $data[0]["reject_money"]);
			$result[$i]["amount"] = "";
			
		}
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$list[] = array('状态', '电商编号', '单号', '下单时间', '送货日期', '客户', '收货人', 
			'电话' ,'收获地址' ,'销售金额','运费','实付金额', '柜信息', '折扣', '备注');
			
			foreach ( $result as $i => $v ) {
				if(empty($v["id"])){continue;}
				$tmp[$i][] = $v["billStatus"];
				$tmp[$i][] = $v["order_sn"];
				$tmp[$i][] = $v["ref"];
				$tmp[$i][] = $v["bizDate"];
				$tmp[$i][] = $v["deliveryTime"];
				$tmp[$i][] = $v["customerName"];
				$tmp[$i][] = $v["consignee"];
				$tmp[$i][] = $v["tel"];
				$tmp[$i][] = $v["address"];
				$tmp[$i][] = $v["amount"];
				$tmp[$i][] = $v["shipping_fee"];
				$tmp[$i][] = $v["amount"]+$v["shipping_fee"];
				$tmp[$i][] = $v["box"];
				$tmp[$i][] = $v["discount"];
				$tmp[$i][] = $v["remark"];
				$list[] = $tmp[$i];		
			}
			$change_name = '导出数据.csv';
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$change_name.'"');
			header('Cache-Control: max-age=0');
			$file = fopen('php://output', 'a');
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
		return array(
				"dataList" => $result,
				"totalCount" => $cnt,
				"totalAmount" => $totalAmount
		);
	}

	public function wsBillDetailList($params) {
		$billId = $params["billId"];
		$sql = "select d.id, g.code, g.name, g.spec,g.bulk, u.name as unit_name, d.goods_count, 
				d.goods_price, d.goods_money, d.apply_num, d.apply_count, d.apply_price
				from t_ws_bill_detail d, t_goods g, t_goods_unit u 
				where d.wsbill_id = '%s' and d.goods_id = g.id and g.unit_id = u.id 
				order by d.show_order";
		$data = M()->query($sql, $billId);
		$result = array();
		$bill = M("ws_bill")->where(array("ref"=>$billId))->find();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["goodsCount"] = $v["goods_count"];
			$result[$i]["goodsPrice"] = $v["goods_price"];
			$result[$i]["goodsMoney"] = $v["goods_money"];
			$result[$i]["applyCount"] = $v["apply_count"];
			$result[$i]["applyNum"]   = $v["apply_num"];
			$result[$i]["applyPrice"] = $v["apply_price"];
			if($bill["type"] == 10){
				if($v["bulk"] == 0){
					$result[$i]["unitName"] = "kg";
					$result[$i]["goodsCount"] = $v["apply_num"];

				}
			}
		}
		
		return $result;
	}
    //查询需要打印单据的数据
	public function wsBillDetailListPrint($params) {
		$billId = $params["billId"];
		$sql1 = "select w.id, w.ref, w.bizdt, w.date_created, w.siteid, w.sitename, w.delivery_date, w.type, w.order_sn, 
				w.discount, w.remark, w.delivery_time, w.remark, w.storage, w.cabinetno, w.boxno, w.pick_code, w.stock_time, 
				c.name as customer_name, u.name as biz_user_name,w.address,w.consignee,w.tel,
				user.name as input_user_name, h.name as warehouse_name, w.sale_money,w.reject_money,
				w.bill_status 
				from t_ws_bill w, t_customer c, t_user u, t_user user, t_warehouse h 
				where w.customer_id = c.id and w.biz_user_id = u.id and w.input_user_id = user.id and w.warehouse_id = h.id 
				and  w.ref = '%s'";
		$data1 = M()->query($sql1, $billId);
		$sql2 = "select d.id, g.code, g.name, g.spec,g.bulk, u.name as unit_name, d.goods_count, 
				d.goods_price, d.goods_money, d.apply_num, d.apply_count, d.apply_price, b.ref, b.date_created, b.areaname, b.sitename, b.address 
				from t_ws_bill_detail d, t_goods g, t_goods_unit u, t_ws_bill b 
				where d.wsbill_id = '%s' and d.goods_id = g.id and g.unit_id = u.id and b.ref=d.wsbill_id 
				order by d.show_order";
		$data2 = M()->query($sql2, $billId);
		$result = array();
  		$bill = M("ws_bill")->where(array("ref"=>$billId))->find();
		foreach ( $data2 as $i => $v ) {
			$result['rows'][$i]["ProductID"] = $v["code"];
			$result['rows'][$i]["ProductName"] = $v["name"];
			$result['rows'][$i]["Quantity"] = $v["apply_num"]?$v["apply_num"]:$v["apply_count"];
			$result['rows'][$i]["UnitPrice"] = $v["goods_price"];
			$result['rows'][$i]["Amount"] = $v["goods_money"];
			$result['rows'][$i]["Spec"] = $v["unit_name"];
			if($bill["type"] == 10){
				if($v["bulk"] == 0){
					$result['rows'][$i]["Spec"] = "kg";

				}
			}
		}
        foreach($data1 as $i=>$v) {
            $result['OrderID'] = $v["ref"];
            $result['OrderDate'] = $v["date_created"];
            $result['consign'] = $v["consignee"];
            $result['tel'] = $v["tel"];
            $result['customerName'] = $v["customer_name"];
            $result['Address'] = $v["areaname"].$v["sitename"].$v["address"];          
        }
		
        $XMLText = "<xml>";
        // 得到报表的其它参数
        $XMLText .= "<Group>";
        foreach ( $result as $key => $value ) {
            if (! is_array ( $value )) {
                $XMLText .= "<" . $key . ">" . $value . "</" . $key . ">";
            }
        }
        $XMLText .= "</Group>";
        
        // 得到报表明细字段
//        $XMLText .= "<rows>";
        foreach ( $result ['rows'] as $key => $value ) {
            $XMLText .= "<Detail>";
            foreach ( $value as $keySub => $valueSub ) {
                $XMLText .= "<" . $keySub . ">" . $valueSub . "</" . $keySub . ">";
            }
            $XMLText .= "</Detail>";
        }
//        $XMLText .= "</rows>";
        
        
        $XMLText .= "</xml>";
        
        return $XMLText;
//		return $result;
	}

	public function deleteWSBill($params) {
		$id = $params["id"];
		$db = M();
		$sql = "select ref, bill_status, type from t_ws_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的销售出库单不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		$billType = $data[0]["type"];
		if ($billStatus != 0) {
			return $this->bad("销售出库单已经提交出库，不能删除");
		}
		if($billType != 10){
			return $this->bad("销售出库单不是手动提交的，不能删除");
		}
		
		$db->startTrans();
		try {
			$sql = "delete from t_ws_bill_detail where wsbill_id = '%s' ";
			$db->execute($sql, $ref);
			$sql = "delete from t_ws_bill where id = '%s' ";
			$db->execute($sql, $id);
			
			$log = "删除销售出库单，单号: {$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "销售出库");
			$db->commit();
		} catch ( Exception $ex ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok();
	}

	public function deleteMallWSBill($params) {
		$id = $params["id"];
		$db = M();
		$sql = "select ref, bill_status, type, order_id from t_ws_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的销售出库单不存在");
		}
		$ref = $data[0]["ref"];
		$billStatus = $data[0]["bill_status"];
		$billType = $data[0]["type"];
		$mall_order_id = $data[0]["order_id"];
		if ($billStatus != 0) {
			return $this->bad("销售出库单已经提交出库，不能删除");
		}
		if($billType == 10){
			return $this->bad("手动销售订单无法删除");
		}
		//获取所有的订单
		$map = array(
			"order_id" => $mall_order_id
		);
		$bill_list = M("ws_bill")->where($map)->select();
		$db->startTrans();
		try {
			foreach ($bill_list as $key => $value) {
				$ref = $value["ref"];
				$id  = $value["id"];
				$sql = "delete from t_ws_bill_detail where wsbill_id = '%s' ";
				$db->execute($sql, $ref);
				$sql = "delete from t_ws_bill where id = '%s' ";
				$db->execute($sql, $id);
				
				$log = "撤销销售出库单，单号: {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "销售出库");
			}
			
			$db->commit();
			$ms = new MallService();
			$ret = $ms->cancel_order($mall_order_id);
			return $ret;
		} catch ( Exception $ex ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok();
	}

	/**
	 * 提交销售出库单
	 */
	public function commitWSBill($params) {
		$id = $params["id"];
		//$api = new ApiService();
		//return $api->Outlib($id);
		$db = M();
		$sql = "select id, ref, bill_status, customer_id, warehouse_id, biz_user_id, bizdt, sale_money ,type 
				from t_ws_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			$sql = "select id, ref, bill_status, customer_id, warehouse_id, biz_user_id, bizdt, sale_money ,type 
				from t_ws_bill where ref = '%s' ";
			$data = $db->query($sql, $id);
			if(!$data){
				return $this->bad("要提交的销售出库单不存在");
			}
		}
		$bill_id = $data[0]["id"];
		$ref = $data[0]["ref"];
		$bizDT = $data[0]["bizdt"];
		$bizUserId = $data[0]["biz_user_id"];
		$billStatus = $data[0]["bill_status"];
		$saleMoney = $data[0]["sale_money"];
		$billType = $data[0]["type"];
		if ($billStatus != 0) {
			return $this->bad("销售出库单已经提交出库，不能再次提交");
		}
		if($billType != 10){
			return $this->bad("销售出库单不是手动订单，无法手动提交出库");
		}
		$customerId = $data[0]["customer_id"];
		$warehouseId = $data[0]["warehouse_id"];
		$sql = "select count(*) as cnt from t_customer where id = '%s' ";
		$data = $db->query($sql, $customerId);
		$cnt = $data[0]["cnt"];
		if ($cnt != 1) {
			return $this->bad("客户不存在");
		}
		$sql = "select name, inited from t_warehouse where id = '%s' ";
		$data = $db->query($sql, $warehouseId);
		if (! $data) {
			return $this->bad("仓库不存在");
		}
		$warehouseName = $data[0]["name"];
		/*
		$inited = $data[0]["inited"];
		if ($inited != 1) {
			return $this->bad("仓库 [{$warehouseName}]还没有建账，不能进行出库操作");
		}
		*/
		$sql = "select name as cnt from t_user where id = '%s' ";
		$data = $db->query($sql, $bizUserId);
		if (! $data) {
			return $this->bad("操作员不存在");
		}
		
		$db->startTrans();
		try {
			$sql = "select * 
					from t_ws_bill_detail 
					where wsbill_id = '%s' 
					order by show_order ";
			$items = $db->query($sql, $id);
			if (! $items) {
				$db->rollback();
				return $this->bad("手动销售单没有商品明细记录，无法出库");
			}
			$goods_stock = array();
			foreach ( $items as $v ) {
				$itemId = $v["id"];
				$goodsId = $v["goods_id"];
				
				$goods_info = $this->base_get_goods_info($goodsId);
				if (! $goods_info) {
					$db->rollback();
					return $this->bad("要出库的商品不存在(商品后台id = {$goodsId})");
				}
				if($goods_info["bulk"] == 0){
					$goodsCount = floatval($v["apply_num"]);
					$goodsPrice = floatval($v["goods_price"]);
				} else {
					$goodsCount = intval($v["apply_count"]);
					$goodsPrice = floatval($v["goods_price"]);
				}
				$goodsCode = $goods_info["code"];
				$goodsName = $goods_info["name"];
				if ($goodsCount <= 0) {
					$db->rollback();
					return $this->bad("商品[{$goodsCode} {$goodsName}]的出库数量需要是正数");
				}

				$goods_one_stock = array(
					"goods_id" => $goodsId,
					"goods_code" => $goods_info["code"],
					"goods_number" => -$goodsCount

				);
				$goods_stock[] = $goods_one_stock;
				
				// 库存总账
				$sql = "select out_count, out_money, balance_count, balance_price,
						balance_money from t_inventory 
						where warehouse_id = '%s' and goods_id = '%s' ";
				$data = $db->query($sql, $warehouseId, $goodsId);
				if (! $data) {
					//$db->rollback();
					//return $this->bad("商品 [{$goodsCode} {$goodsName}] 在仓库 [{$warehouseName}] 中没有存货，无法出库");
					//加入库存
					$sql2 = "insert into t_inventory(balance_count, balance_price, balance_money, warehouse_id, goods_id) 
							values(0,0,0,'$warehouseId','$goodsId')";
					if($warehouseId && $goodsId){
						$db->execute($sql2);
					} else {
						$db->rollback();
						return $this->bad("商品 [{$goodsCode} {$goodsName}] 在仓库 [{$warehouseName}] 中没有存货，无法出库");
					}
					$data = "";
					$data = $db->query($sql);
					
				}
				$balanceCount = $data[0]["balance_count"];
				if ($balanceCount < $goodsCount) {
					//$db->rollback();
					//return $this->bad("商品 [{$goodsCode} {$goodsName}] 在仓库 [{$warehouseName}] 中存货数量不足，无法出库");
				}
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
				$outPriceDetail = $goodsPrice >= 0 ? $goodsPrice : $goods_info["sale_price"];
				$outMoneyDetail = $outPriceDetail * $goodsCount;
				$outCount += $goodsCount;
				$outPrice = $outMoney / $outCount;
				
				$sql = "update t_inventory 
						set out_count = %f, out_price = %f, out_money = %f,
						    balance_count = %f, balance_money = %f 
						where warehouse_id = '%s' and goods_id = '%s' ";
				$db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, $balanceMoney, $warehouseId, $goodsId);
				
				// 库存明细账
				$sql = "insert into t_inventory_detail(out_count, out_price, out_money, 
						balance_count, balance_price, balance_money, warehouse_id,
						goods_id, biz_date, biz_user_id, date_created, ref_number, ref_type) 
						values(%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '手动销售出库')";
				$db->execute($sql, $goodsCount, $outPriceDetail, $outMoneyDetail, $balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref);
				
				// 单据本身的记录
				$sql = "update t_ws_bill_detail 
						set inventory_price = %f, inventory_money = %f
						where id = '%s' ";
				$db->execute($sql, $outPriceDetail, $outMoneyDetail, $itemId);
			}
			
			// 应收总账
			$sql = "select rv_money, balance_money 
					from t_receivables 
					where ca_id = '%s' and ca_type = 'customer' ";
			$data = $db->query($sql, $customerId);
			if ($data) {
				$rvMoney = $data[0]["rv_money"];
				$balanceMoney = $data[0]["balance_money"];
				
				$rvMoney += $saleMoney;
				$balanceMoney += $saleMoney;
				
				$sql = "update t_receivables
						set rv_money = %f,  balance_money = %f 
						where ca_id = '%s' and ca_type = 'customer' ";
				$db->execute($sql, $rvMoney, $balanceMoney, $customerId);
			} else {
				$sql = "insert into t_receivables (id, rv_money, act_money, balance_money,
						ca_id, ca_type) values ('%s', %f, 0, %f, '%s', 'customer')";
				$idGen = new IdGenService();
				$db->execute($sql, $idGen->newId(), $saleMoney, $saleMoney, $customerId);
			}
			
			// 应收明细账
			$sql = "insert into t_receivables_detail (id, rv_money, act_money, balance_money,
					ca_id, ca_type, date_created, ref_number, ref_type, biz_date) 
					values('%s', %f, 0, %f, '%s', 'customer', now(), '%s', '销售出库', '%s')";
			$idGen = new IdGenService();
			$db->execute($sql, $idGen->newId(), $saleMoney, $saleMoney, $customerId, $ref, $bizDT);
			
			// 单据本身设置为已经提交出库
			$sql = "select sum(inventory_money) as sum_inventory_money 
					from t_ws_bill_detail 
					where wsbill_id = '%s' ";
			$data = $db->query($sql, $id);
			$sumInventoryMoney = $data[0]["sum_inventory_money"];
			
			$profit = $saleMoney - $sumInventoryMoney;
			
			$sql = "update t_ws_bill 
					set bill_status = 1000, inventory_money = %f, profit = %f 
					where id = '%s' ";
			$db->execute($sql, $sumInventoryMoney, $profit, $bill_id);
			
			$log = "提交销售出库单，单号 = {$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "销售出库");
			$db->commit();
			//提交之后，同步电商库存
			if($goods_stock){
				$ms = new MallService();
				$ms->syn_inventory($goods_stock, "批发出库");
			}
		} catch ( Exception $ex ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok($id);
	}


    //修改送货地址
    public function modifyAddress($params){
        $address  = $params["address_detail"];
        $order_id  = $params["id"];
        $order_ref = $params["ref"];
        $order_db = M("ws_bill");
        if($order_id){
            $order_id_arr = explode(",", $order_id);
            foreach ($order_id_arr as $v) {
                $map = array(
                    "id" => $v
                );
                $data = array(
                    "address" => $address
                );
                $order_db->where($map)->save($data);
            }
        } else if($order_ref){
            $order_ref_arr = explode(",", $order_ref);
            foreach ($order_ref_arr as $v) {
                $map = array(
                    "ref" => $v
                );
                $data = array(
                    "address" => $address
                );
                $order_db->where($map)->save($data);
            }
        }
        return $this->ok("修改成功");
    }

	//修改送货区域
	public function modifyArea($params){
		$areaid    = $params["areaid"];
		$areacode  = $params["areacode"];
		$order_id  = $params["id"];
		$order_ref = $params["ref"];
		//获取areadid或者areacode
		if($areaid){
			$map = array(
				"id" => $areaid
			);
		} else {
			$map = array(
				"code" => $areacode
			);
		}
		$area = M("site_category")->where($map)->find();
		if(!$area){
			return $this->bad("送货区域不存在");
		}
		$order_db = M("ws_bill");
		$address_site_db = M("address_site");
		//获取地址

		if($order_id){
			$order_id_arr = explode(",", $order_id);
			foreach ($order_id_arr as $v) {
				$map = array(
					"id" => $v
				);
				$order = $order_db->where($map)->find();
				$address = $order["address"];
				$map_address = array(
					"address" => $address
				);
				$data = array(
					"address"  => $address,
					"areaid"   => $area["id"],
					"areaname" => $area["name"],
					"time"     => time()
				);
				if($address_site_db->where($map_address)->find()){
					$address_site_db->where($map_address)->save($data);
				} else {
					$address_site_db->add($data);
				}
				$data = array(
					"areaid"   => $area["id"],
					"areaname" => $area["name"]
				);
				$order_db->where($map)->save($data);
			}
		} else if($order_ref){
			$order_ref_arr = explode(",", $order_ref);
			foreach ($order_ref_arr as $v) {
				$map = array(
					"ref" => $v
				);
				$order = $order_db->where($map)->find();
				$address = $order["address"];
				$map_address = array(
					"address" => $address
				);
				$data = array(
					"address"  => $address,
					"areaid"   => $area["id"],
					"areaname" => $area["name"],
					"time"     => time()
				);
				if($address_site_db->where($map_address)->find()){
					$address_site_db->where($map_address)->save($data);
				} else {
					$address_site_db->add($data);
				}
				$data = array(
					"areaid"   => $area["id"],
					"areaname" => $area["name"]
				);
				$order_db->where($map)->save($data);
			}
		}
		return $this->ok("修改成功");

	}

	//修改送货站点
	public function modifySite($params){
		$siteid = $params["siteid"];
		$order_id  = $params["id"];
		$order_ref = $params["ref"];
		//查询到站点
		$map = array(
			"id" => $siteid
		);
		$site   = M("site")->where($map)->find();
		if(!$site){
			return $this->bad("站点不存在");
		}
		$map = array(
			"id" => $site["category_id"]
		);
		$area = M("site_category")->where($map)->find();
		if(!$area){
			return $this->bad("送货区域不存在");
		}
		$address_site_db = M("address_site");
		$order_db = M("ws_bill");
		if($order_id){
			$order_id_arr = explode(",", $order_id);
			foreach ($order_id_arr as $v) {
				$map = array(
					"id" => $v
				);
				$order = $order_db->where($map)->find();
				$address = $order["address"];
				$map_address = array(
					"address" => $address
				);
				$data = array(
					"address"  => $address,
					"areaid"   => $area["id"],
					"areaname" => $area["name"],
					"siteid"   => $site["id"],
					"sitename" => $site["name"],
					"time"     => time()
				);
				if($address_site_db->where($map_address)->find()){
					$address_site_db->where($map_address)->save($data);
				} else {
					$address_site_db->add($data);
				}
				$data = array(
					"areaid"   => $area["id"],
					"areaname" => $area["name"],
					"siteid"   => $site["id"],
					"sitename" => $site["name"],
					"pick_type" => "自提"
				);
				$order_db->where($map)->save($data);
			}
		} else if($order_ref){
			$order_ref_arr = explode(",", $order_ref);
			foreach ($order_ref_arr as $v) {
				$map = array(
					"ref" => $v
				);
				$order = $order_db->where($map)->find();
				$address = $order["address"];
				$map_address = array(
					"address" => $address
				);
				$data = array(
					"address"  => $address,
					"areaid"   => $area["id"],
					"areaname" => $area["name"],
					"siteid"   => $site["id"],
					"sitename" => $site["name"],
					"time"     => time()
				);
				if($address_site_db->where($map_address)->find()){
					$address_site_db->where($map_address)->save($data);
				} else {
					$address_site_db->add($data);
				}
				$data = array(
					"areaid"   => $area["id"],
					"areaname" => $area["name"],
					"siteid"   => $site["id"],
					"sitename" => $site["name"],
					"pick_type" => "自提"
				);
				$order_db->where($map)->save($data);
			}
		}
		return $this->ok("修改成功");
	}

	//修改站点或者配送区域
	public function editAllSite($params){
		//如果是修改siteid，则直接修改，无需修改areaid
		if($params["siteid"]){
			return $this->modifySite($params);
		} else {
			if($params["areaid"]){
				return $this->modifyArea($params);
			}
            if($params["address_detail"]){
                return $this->modifyAddress($params);
            }
		}
		return $this->bad("缺少参数");
	}

	//获取站点列表和区域列表
	public function getAllSite(){
		$site_list = M("site")->select();
		$area_list = M("site_category")->field("id,code,name")->select();
		$data = array(
			"extData" => array(
				//"site" => $site_list,
				"area" => $area_list
			)
		);
		return $data;
	}

	//加入订单到待检列表
	public function toPick($params){
		$order_ref = $params["order_ref"];
		$order = M("ws_bill")->where(array("ref"=>$order_ref))->find();
	}


	public function wsbillList_for_mall_total($params){
		$startdate = $params["startdate"];
		$enddate   = $params["enddate"];
		$siteid    = $params["siteid"];
		$search_add_time_start = $params["request.search_add_time_start"];
		$search_add_time_end = $params["search_add_time_end"];
		$delivery_time = $params["delivery_time"];
		$search_sql = "where w.type in (0,1) and w.sale_money > w.reject_money ";
		if($search_add_time_start){
			$search_add_time_start = str_replace("T", " ", $search_add_time_start);
			$search_sql .= " and w.date_created >= '$search_add_time_start' ";
		}
		if($search_add_time_end){
			$search_add_time_end = str_replace("T", " ", $search_add_time_end);
			$search_sql .= " and w.date_created <= '$search_add_time_end' ";
		}
		if($startdate){
			$startdate = str_replace("T", " ", $startdate);
			$startdate = substr($startdate,0,10). " 00:00:00";
			$search_sql .= " and w.delivery_date >= '$startdate' ";
		}
		if($enddate){
			$enddate = str_replace("T", " ", $enddate);
			$enddate = substr($enddate,0,10)." 23:59:59";
			$search_sql .= " and w.delivery_date <= '$enddate' ";
		}
		if($delivery_time){
			$search_sql .= " and w.delivery_time = '$delivery_time' ";
		}
		if($siteid){
			if(strpos($siteid, ",") > -1){
				$site_arr = explode(",", $siteid);
				foreach ($site_arr as $key => $value) {
					$site_arr[$key] = "'$value'";
				}
				$site_where = "(".implode(",", $site_arr).")";
				$search_sql .= " and w.siteid in $site_where ";
			} else {
				$search_sql .= " and w.siteid = '$siteid' ";
			}
			
		}
		$db = M();
		$sql = "select count(*) as cnt,sum(w.mall_money) as total_mall_money, sum(w.sale_money) as total_sale_money, sum(w.discount) as total_discount_money ,sum(w.reject_money) as total_reject_money 
				from t_ws_bill w $search_sql";
		$data = $db->query($sql);
		$list = array(
			"erp_order_count" => $data[0]["cnt"],
			"erp_order_money" => $data[0]["total_sale_money"] - $data[0]["total_reject_money"]
		);
		$sql = "select count(DISTINCT w.order_id) as cnt 
				from t_ws_bill w $search_sql";
		$data = $db->query($sql);
		$list["mall_order_money"] = $data[0]["total_mall_money"] - $data[0]["total_reject_money"];
		$list["mall_order_count"] = $data[0]["cnt"];
		$list["avg_money"] = round($list["erp_order_money"]/$list["mall_order_count"], 2);
		return $list;
	}

	public function wsbillList_for_mall($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$type  = $params["type"];
		$siteid    = $params["siteid"];
		$startdate = $params["startdate"];
		$enddate   = $params["enddate"];
		$mobile    = $params["mobile"];
		$username  = $params["username"];
		$ordertype = $params["ordertype"];
		$search_add_time_start = $params["request.search_add_time_start"];
		$search_add_time_end = $params["search_add_time_end"];
		$search_mall_order_ref = $params["search_mall_order_ref"];
		$search_bill_status = $params["search_bill_status"];
		$search_customer    = $params["search_customer"];
		$delivery_time = $params["delivery_time"];
		$search_sql = " and w.type in (0,1) ";
		if($search_add_time_start){
			$search_add_time_start = str_replace("T", " ", $search_add_time_start);
			$search_sql .= " and w.date_created >= '$search_add_time_start' ";
			$is_inout = false;
		}
		if($search_add_time_end){
			$search_add_time_end = str_replace("T", " ", $search_add_time_end);
			$search_sql .= " and w.date_created <= '$search_add_time_end' ";
		}
		if($search_bill_status != -2 && $search_bill_status !== ""){
			$search_sql .= " and w.bill_status = '$search_bill_status' ";
		}
		if($search_mall_order_ref){
			$search_sql .= " and (w.order_sn = '$search_mall_order_ref' or w.ref = '$search_mall_order_ref')  ";
		}
		if($startdate){
			$startdate = str_replace("T", " ", $startdate);
			$startdate = substr($startdate,0,10). " 00:00:00";
			$search_sql .= " and w.delivery_date >= '$startdate' ";
			$inout_sql .= " and biz_date >= '$startdate' ";
		}
		if($enddate){
			$enddate = str_replace("T", " ", $enddate);
			$enddate = substr($enddate,0,10)." 23:59:59";
			$search_sql .= " and w.delivery_date <= '$enddate' ";
			$inout_sql  .= " and biz_date <= '$enddate' ";
		}
		if($mobile){
			$search_sql .= " and w.tel = '$mobile'";
			$is_inout = false;
		}
		if($username){
			$search_sql .= " and w.consignee = '$username'";
			$is_inout = false;
		}
		if($sitename){
			$search_sql .= " and w.sitename = '$sitename'";
			$is_inout = false;
		}
		if($areaname){
			$search_sql .= " and w.areaname = '$areaname'";
			$is_inout = false;
		}
		if($search_customer){
			$search_sql .= " and w.customer_id in (select id from t_customer where code = '$search_customer' or name='$search_customer') ";
			$is_inout = false;
		}
		if($delivery_time){
			$search_sql .= " and w.delivery_time = '$delivery_time' ";
		}
		if($siteid){
			if(strpos($siteid, ",") > -1){
				$site_arr = explode(",", $siteid);
				foreach ($site_arr as $key => $value) {
					$site_arr[$key] = "'$value'";
				}
				$site_where = "(".implode(",", $site_arr).")";
				$search_sql .= " and w.siteid in $site_where ";
			} else {
				$search_sql .= " and w.siteid = '$siteid' ";
			}
		}
		if(I("request.act") == 'export'){
			$limit = 1000;
		}
		$db = M();
		$sql = "select w.id, w.ref, w.bizdt, w.date_created, w.siteid, w.sitename, w.delivery_date, w.type, w.order_sn, 
				w.discount, w.remark, w.delivery_time, w.remark, w.storage, w.cabinetno, w.boxno, w.pick_code, w.stock_time, 
				c.name as customer_name, u.name as biz_user_name,w.address,w.consignee,w.tel,
				user.name as input_user_name, w.sale_money,w.reject_money,
				w.bill_status 
				from t_ws_bill w, t_customer c, t_user u, t_user user 
				where w.customer_id = c.id and w.biz_user_id = u.id $search_sql 
				  and w.input_user_id = user.id 
				order by w.delivery_date desc, w.date_created desc 
				limit %d, %d";
		$data = $db->query($sql, $start, $limit);
		$result = array();
		$order_status = $this->orderStatus;

		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["order_sn"] = $v["order_sn"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["bizdt"]));
			$result[$i]["bizDate"] = $v["date_created"];
			$result[$i]["customerName"] = $v["customer_name"];
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["address"] = $v["sitename"].$v["address"];
			$result[$i]["deliveryTime"] = $v["delivery_date"].($v["delivery_time"] == 1 ? " 上午":" 下午");

			$result[$i]["consignee"] = $v["consignee"];
			$result[$i]["tel"] = $v["tel"];
			$result[$i]["billStatus"] = $order_status[$v["bill_status"]];
			$result[$i]["billStatusStr"] = "<span style='".$this->orderStatusStyle[$v["bill_status"]]."'>".$order_status[$v["bill_status"]]."</span>";
			$result[$i]["amount"] = $v["sale_money"] - $v["reject_money"];
			$result[$i]["discount"] = $v["discount"];
			$result[$i]["remark"] = $v["remark"];
			$result[$i]["pick_code"] = $v["pick_code"];
			$result[$i]["stock_time_str"] = $v['stock_time'] ? date("Y-m-d H:i:s", $v['stock_time']) : "";
			$result[$i]["siteid"] = $v["siteid"];
			$result[$i]["sitename"] = $v["sitename"];
			$result[$i]["reject_money"] = $v["reject_money"];
			//获取柜子信息
			$result[$i]["box"] = $box_info;
		}
		$sql = "select count(*) as cnt, sum(w.sale_money) as total_sale_money, sum(w.discount) as total_discount_money ,sum(w.reject_money) as total_reject_money 
				from t_ws_bill w, t_customer c, t_user u, t_user user, t_warehouse h 
				where w.customer_id = c.id and w.biz_user_id = u.id $search_sql 
				  and w.input_user_id = user.id and w.warehouse_id = h.id ";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		$totalAmount = $data[0]["total_sale_money"];
		$totalDiscountAmount = $data[0]["total_discount_money"];
		$totalRejectMoney = $data[0]["total_reject_money"];

		//计算销售金额
		/*
		if(!$is_inout){
				$sql = "select sum(apply_price) as total_money, sum(reject_money) as reject_money 
				from t_ws_bill_detail where wsbill_id in (select ref from t_ws_bill w where 1 $search_sql )";
				if($type == 10){
					$sql = "select sum(sale_money) as total_money, sum(reject_money) as reject_money 
					from t_ws_bill w where 1 $search_sql";
				}
				$data = $db->query($sql);
		} else {
			$sql = "select sum(sale_money) as total_money  
				from t_inout_day where 1 $inout_sql ";
				$data = $db->query($sql);
		}
		*/
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$list[] = array('状态', '电商编号', '单号', '下单时间', '送货日期', '客户', '收货人', 
			'电话' ,'收获地址' ,'销售金额', '柜信息', '折扣', '备注');
			
			foreach ( $result as $i => $v ) {
				if(empty($v["id"])){continue;}
				$tmp[$i][] = $v["billStatus"];
				$tmp[$i][] = $v["order_sn"];
				$tmp[$i][] = $v["ref"];
				$tmp[$i][] = $v["bizDate"];
				$tmp[$i][] = $v["deliveryTime"];
				$tmp[$i][] = $v["customerName"];
				$tmp[$i][] = $v["consignee"];
				$tmp[$i][] = $v["tel"];
				$tmp[$i][] = $v["address"];
				$tmp[$i][] = $v["amount"];
				$tmp[$i][] = $v["box"];
				$tmp[$i][] = $v["discount"];
				$tmp[$i][] = $v["remark"];
				$list[] = $tmp[$i];		
			}
			$change_name = '导出数据.csv';
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$change_name.'"');
			header('Cache-Control: max-age=0');
			$file = fopen('php://output', 'a');
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
		return array(
				"dataList" => $result,
				"totalCount" => $cnt,
				"totalAmount" => $totalAmount
		);
	}

}