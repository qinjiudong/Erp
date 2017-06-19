<?php

namespace Home\Service;
use Home\Service\MallService;
/**
 * 验收入库Service
 *
 * @author dubin
 */
class IABillService extends ERPBaseService {

	public function iabillList($params) {
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$billid_sql = "";
		$supplier = $params["supplier"];
		$goods_code = $params["goods_code"];
		if($billid = $params["billid"]){
			$billid_sql = " and p.ref like '%$billid%' ";
		}
		$billdate_sql = "";
		if($begindate = $params["begindate"]){
			$billdate_sql .= " and p.biz_dt >= '$begindate' ";
		}
		if($enddate = $params["enddate"]){
			$billdate_sql .= " and p.biz_dt <= '$enddate' ";
		}
		$supplier_sql = "";
		if($supplier){
			$supplier_sql = " and p.supplier_id in (select id from t_supplier where code = '$supplier' or name like '%$supplier%')";
		}
		if($goods_code){
			$goods_sql = " and p.id in (select iabill_id from t_ia_bill_detail where goods_id in (select id from t_goods where code = '$goods_code'))";
		}
		$type = $params["type"];
		$bill_status_str = "0,1,2,3";
		if($type == "verify"){
			$bill_status_str = "2";
		}
		$auto = $params["auto"];
		if($auto === ""){
			
		} else {
			$auto_sql = " and p.type = $auto ";
		}
		$editStatus = $params["editStatus"];
		if($editStatus === ""){
			
		} else {
			$editStatus_sql = " and p.bill_status = $editStatus ";
		}
		$db = M();
		
		$sql = "select p.id, p.bill_status, p.ref, p.biz_dt, u1.name as biz_user_name, u2.name as input_user_name,p.caiwuqueren,p.caiwushoukuan, 
				p.goods_money, w.name as warehouse_name, s.name as supplier_name, s.code as supplier_code 
				from t_ia_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2 
				where p.bill_status in ($bill_status_str) $billid_sql $billdate_sql and p.warehouse_id = w.id and p.supplier_id = s.id 
				and p.biz_user_id = u1.id and p.input_user_id = u2.id $supplier_sql $auto_sql $editStatus_sql $goods_sql
				order by p.ref desc 
				limit $start,$limit";
		if(I("request.act") == 'export'){
			$limit = 20000;
		}
		$data = $db->query($sql);
		$result = array();
		$list[] = array('单号', '日期', '供应商','供应商编码',
								'金额', '业务员', '录单人','财务确认','财务收款');
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["ref"] = $v["ref"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["biz_dt"]));
			$result[$i]["supplierName"] = $v["supplier_name"];
			$result[$i]["supplierCode"] = $v["supplier_code"];
			$result[$i]["warehouseName"] = $v["warehouse_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["caiwuqueren"] = $v["caiwuqueren"];
			$result[$i]["caiwushoukuan"] = $v["caiwushoukuan"];
			$bill_status_str = "";
			if($v["bill_status"] == 1){
				$bill_status_str = "已入库";
			} 
            else{
				$bill_status_str = "审核中";
            }
			$result[$i]["billStatus"] = $bill_status_str;
			$result[$i]["amount"] = $v["goods_money"];
			$list[] = array($v["ref"],$result[$i]["bizDate"],$result[$i]["supplierName"],$result[$i]["supplierCode"],$v["goods_money"],$result[$i]["bizUserName"],$result[$i]["inputUserName"],$result[$i]["caiwuqueren"],$result[$i]["caiwushoukuan"]);
		}

		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '验收单导出数据.csv';
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
				from t_ia_bill p, t_warehouse w, t_supplier s, t_user u1, t_user u2 
				where p.bill_status in (0,1,2,3) $billid_sql $billdate_sql and p.warehouse_id = w.id and p.supplier_id = s.id 
				and p.biz_user_id = u1.id and p.input_user_id = u2.id $supplier_sql $auto_sql $goods_sql";
		$data = $db->query($sql);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function iaBillDetailList($pwbillId) {
		$sql = "select p.id, g.code, g.name, g.spec, g.bulk, g.buytax, g.barcode, u.name as unit_name, p.goods_count, p.goods_price, p.goods_money, p.goods_type 
				from t_ia_bill_detail p, t_goods g, t_goods_unit u 
				where p.iabill_id = '%s' and p.goods_id = g.id and g.unit_id = u.id 
				order by p.show_order ";
		$data = M()->query($sql, $pwbillId);
		$result = array();
		$total_money = 0;
		$total_count = 0;
		$list[] = array('编码', '条码', '品名','规格',
								'数量', '单位', '单价','金额(含税)','税','金额(无税)');
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["goodsCode"] = $v["code"];
			$result[$i]["goodsBarCode"] = $v["barcode"];
			$result[$i]["goodsName"] = $v["name"];
			$result[$i]["goodsSpec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitName"] = "kg";
			}
			$result[$i]["goodsCount"] = $v["goods_count"];
			$result[$i]["goodsMoney"] = $v["goods_money"];
			$result[$i]["goodsTaxMoney"] = ($v["goods_money"]/(100+$v['buytax']))*$v['buytax'];
			$result[$i]["goodsTax"] = $v['buytax'];
			$result[$i]["goodsNoTaxMoney"] = $v["goods_money"]-$result[$i]["goodsTaxMoney"];
			$result[$i]["goodsPrice"] = $v["goods_price"];
			$result[$i]["goodsType"] = $v["goods_type"] == 0 ? "正常商品":"赠品";
			$total_money += $v["goods_money"];
			$total_count += $v["goods_count"];
			$list[] = array($v["code"],$result[$i]["goodsBarCode"],$result[$i]["goodsName"],$result[$i]["goodsSpec"],$result[$i]["goodsCount"],$result[$i]["unitName"],$result[$i]["goodsPrice"],$result[$i]["goodsMoney"]);
		}
		/* 导出数据 */
		if(I("request.act") == 'export'){	//导出数据
			$change_name = '验收单明细导出.csv';
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
		$i++;
		$result[$i]["id"] = 0;
		$result[$i]["goodsCount"] = $total_count;
		$result[$i]["goodsMoney"] = $total_money;
		
		return $result;
	}

	public function uploadIABill($params){
		$pwref = $params["pwref"];
		$items = $params['items'];
		$items = preg_replace("/\&quot\;/", '"', $items);
		$bill  = json_decode(html_entity_decode($items), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		//查询采购单是否存在
		$map = array(
			"ref" => $pwref
		);
		$pwinfo = M("pw_bill")->where($map)->find();
		if(!$pwinfo){
			return $this->bad("采购单不存在");
		}
		if($pwinfo["bill_status"] == 0 || $pwinfo["bill_status"] == 2 || $pwinfo["bill_status"] == 3){
			return $this->bad("采购单状态不正确，必须要通过审核");
		}
		$json = array(
			"id" => "",
			"bizDT" => date("Y-m-d H:i:s"),
			"warehouseId" => $pwinfo["warehouse_id"],
			"supplierId"  => $pwinfo["supplier_id"],
			"bizUserId"   => $pwinfo["biz_user_id"],
			"pwbillId"    => $pwinfo["id"],
			"items"       => $bill
		);
		return $this->commitIABill(json_encode($json));
	}

	public function editIABill($json) {
		$bill = json_decode(html_entity_decode($json), true);
		if ($bill == null) {
			return $this->bad("传入的参数错误，不是正确的JSON格式");
		}
		
		$id = $bill["id"];
		$bizDT = $bill["bizDT"];
		$warehouseId = $bill["warehouseId"];
		$supplierId = $bill["supplierId"];
		$bizUserId = $bill["bizUserId"];
		$pw_billid = $bill["pwbillId"];
		$db = M();
		if(empty($pw_billid)){
			return $this->bad("请选择一个采购单");
		}

		$map = array(
			"id" => $pw_billid
		);
		$pw_bill_info = M("pw_bill", "t_")->where($map)->find();
		if (!$pw_bill_info) {
			return $this->bad("选择的采购单不存在");
		}
		//是否已经入库了
		if($pw_bill_info["bill_status"] == 5){
			//return $this->bad("选择的采购单已完成验收");
		}
		//查询是否存在该采购单相对的验收单
		/*
		$map = array(
			"pw_billid" => $pw_billid
		);
		$ia_bill_info = M("ia_bill", "t_")->where($map)->find();
		if($ia_bill_info){

		}
		*/
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
			$sql = "select ref, bill_status from t_ia_bill where id = '%s' ";
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
				$sql = "delete from t_ia_bill_detail where pwbill_id = '%s' ";
				$db->execute($sql, $id);
				
				// 明细记录
				$items = $bill["items"];
				$item_count = 0;
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
							if(array_key_exists("goodsPrice", $item)){
								$goodsPrice = floatval($item["goodsPrice"]);
							} else {
								$map = array(
									"goods_id" =>  $goodsId,
									"pwbill_id" => $pw_billid
								);
								$goodsPrice = M("pw_bill_detail")->where($map)->getField("goods_price");
							}
							$goods_info = $this->base_get_goods_info($goodsId);
							$buytax = $goods_info["buytax"] / 100;
							
							$goodsMoney = $goodsCount * $goodsPrice;
							$goodsMoneyNoTax = $goodsMoney / (1 + $buytax);
							
							$sql = "insert into t_ia_bill_detail (id, date_created, goods_id, goods_count, goods_price,
									goods_money,  pwbill_id, show_order, goods_type, goods_money_no_tax)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d,%f )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, $goodsType, $goodsMoneyNoTax);
							$item_count++;
						}
					}
				}

				
				$sql = "select sum(goods_money) as goods_money from t_ia_bill_detail 
						where pwbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$totalMoney = $data[0]["goods_money"];
				$sql = "update t_ia_bill 
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
				$sql = "insert into t_ia_bill (id, ref, supplier_id, warehouse_id, biz_dt, 
						biz_user_id, bill_status, date_created, goods_money, input_user_id, pw_billid) 
						values ('%s', '%s', '%s', '%s', '%s', '%s', 0, now(), 0, '%s', '%s')";
				
				$ref = $this->genNewBillRef();
				$us = new UserService();
				$input_user_id = $us->getLoginUserId() ? $us->getLoginUserId() : $bizUserId;
				$db->execute($sql, $id, $ref, $supplierId, $warehouseId, $bizDT, $bizUserId, $input_user_id, $pw_billid);
				
				// 明细记录
				$items = $bill["items"];
				$item_count = 0;
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
							$map = array(
								"goods_id" =>  $goodsId,
								"pwbill_id" => $pw_billid
							);
							$pw_detail_info = M("pw_bill_detail")->where($map)->find();
							$pw_detail_total = M("pw_bill_detail")->field("sum(goods_count) as total_goods_count")->where($map)->find();
							$pw_detail_info["goods_count"] = $pw_detail_total["total_goods_count"];
							if(!$pw_detail_info){
								$db->rollback();
								return $this->bad("采购单中不存在".$pw_detail_info["goods_id"]."商品");
							}

							//查看已经验收过的数量是否超过了采购单中的数量
							$map = array(
								"goods_id"   => $goodsId,
								"pwbill_id"  => $pw_billid,
								"goods_type" => 0
							);
							$in_count_arr = M("ia_bill_detail")->where($map)->field("sum(goods_count) as total_goods_count")->find();
							$in_count = $in_count_arr["total_goods_count"];
							$in_count = $in_count ? $in_count : 0;
							if( $pw_detail_info["goods_count"] < ($in_count + $goodsCount) ){
								$left_count = $pw_detail_info["goods_count"] - $in_count;
								//$db->rollback();
								//return $this->bad("验收商品的数量已经超出了本采购单的数量,剩余".$left_count);
							}
							if(array_key_exists("goodsPrice", $item)){
								$goodsPrice = floatval($item["goodsPrice"]);
							} else {
								
								$goodsPrice = $pw_detail_info["goods_price"];
							}
							$goods_info = $this->base_get_goods_info($goodsId);
							$buytax = $goods_info["buytax"] / 100;
							$goodsMoney = $goodsCount * $goodsPrice;
							$goodsMoneyNoTax = $goodsMoney / (1 + $buytax);
							$sql = "insert into t_ia_bill_detail 
									(id, date_created, goods_id, goods_count, goods_price,
									goods_money,  iabill_id, show_order, goods_type, pwbill_id, goods_money_no_tax)
									values ('%s', now(), '%s', %f, %f, %f, '%s', %d, %d, '%s', %f )";
							$db->execute($sql, $idGen->newId(), $goodsId, $goodsCount, $goodsPrice, $goodsMoney, $id, $i, $goodsType, $pw_billid,$goodsMoneyNoTax );
							$item_count++;
						}
					}
				}
				if($item_count <= 0){
					$db->rollback();
					return $this->bad("验收单中不存在商品");
				}
				$sql = "select sum(goods_money) as goods_money from t_ia_bill_detail 
						where iabill_id = '%s' ";
				$data = $db->query($sql, $id);
				$totalMoney = $data[0]["goods_money"];
				$sql = "update t_ia_bill
						set goods_money = %f 
						where id = '%s' ";
				$db->execute($sql, $totalMoney, $id);
				
				//把对应的采购单状态修改为已验收
				$sql = "update t_pw_bill
						set bill_status = 4
						where id = '%s' ";
				//$db->execute($sql, $pw_billid);

				$log = "新建验收入库单: 单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "验收入库");
				
				$db->commit();
			} catch ( Exception $exc ) {
				$db->rollback();
				
				return $this->bad("数据库操作错误，请联系管理员");
			}
		}
		
		return $this->ok($id);
	}

	private function genNewBillRef() {
		$pre = "IA";
		$mid = date("Ymd");
		
		$sql = "select ref from t_ia_bill where ref like '%s' order by ref desc limit 1";
		$data = M()->query($sql, $pre . $mid . "%");
		$suf = "0001";
		if ($data) {
			$ref = $data[0]["ref"];
			$nextNumber = intval(substr($ref, 10)) + 1;
			$suf = str_pad($nextNumber, 4, "0", STR_PAD_LEFT);
		}
		
		return $pre . $mid . $suf;
	}

	public function iaBillInfo($id) {
		$result["id"] = $id;
		
		$db = M();
		$sql = "select p.ref, p.bill_status, p.pw_billid, p.supplier_id, s.name as supplier_name, 
				p.warehouse_id, w.name as  warehouse_name, 
				p.biz_user_id, u.name as biz_user_name, p.biz_dt 
				from t_ia_bill p, t_supplier s, t_warehouse w, t_user u 
				where p.id = '%s' and p.supplier_id = s.id and p.warehouse_id = w.id 
				  and p.biz_user_id = u.id";
		$data = $db->query($sql, $id);
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
			$result["pw_billid"] = $v["pw_billid"];
			$result["bizDT"] = date("Y-m-d", strtotime($v["biz_dt"]));
			
			$items = array();
			$sql = "select p.id, p.goods_id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, 
					p.goods_count, p.goods_price, p.goods_money, p.goods_type 
					from t_ia_bill_detail p, t_goods g, t_goods_unit u 
					where p.goods_Id = g.id and g.unit_id = u.id and p.iabill_id = '%s' 
					order by p.show_order";
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
				$items[$i]["goodsPrice"] = $v["goods_price"];
				$items[$i]["goodsMoney"] = $v["goods_money"];
				$items[$i]["goodsType"] = $v["goods_type"];
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

	public function commitIABill($id) {
//		$result = $this->editIABill($json);
//		if($result["success"] == false){
//			return $result;
//		}
//		$id = $result["id"];
		$db = M();
		$sql = "select ref, warehouse_id, bill_status, biz_dt, biz_user_id,  goods_money, supplier_id 
				from t_ia_bill 
				where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要提交的验收单不存在");
		}
		$billStatus = $data[0]["bill_status"];
		$yes_status_arr = array(1);
		if (in_array($billStatus, $yes_status_arr)) {
			return $this->bad("验收单已经入库完结，无法继续入库");
		}

		$ref = $data[0]["ref"];
		$bizDT = $data[0]["biz_dt"];
		$bizUserId = $data[0]["biz_user_id"];
		$billPayables = floatval($data[0]["goods_money"]);
		$supplierId = $data[0]["supplier_id"];
		$warehouseId = $data[0]["warehouse_id"];
		$pw_billid   = $data[0]["pw_billid"];
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
				from t_ia_bill_detail 
				where iabill_id = '%s' order by show_order";
		$items = $db->query($sql, $id);
		if (! $items) {
			return $this->bad("验收单没有验收明细记录，不能提交");
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
		$db->startTrans();
		//库存同步所需数组
		$goods_stock_array = array();
		try {
			foreach ( $items as $v ) {
				$goodsCount = floatval($v["goods_count"]);
				$goodsPrice = floatval($v["goods_price"]);
				$goodsMoney = floatval($v["goods_money"]);
				$goodsId = $v["goods_id"];
				$goods_info = $this->base_get_goods_info($goodsId);
				if($goods_info["oversold"] != 1){
					//如果不允许超卖，则需要同步更新库存
					$goods_stock = array(
						"goods_code"  => $goods_info["code"],
						"goods_number" => $goodsCount
					);
					$goods_stock_array[] = $goods_stock;
				}

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
						values (%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '验收入库')";
				$db->execute($sql, $goodsCount, $goodsPrice, $goodsMoney, $balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref);
				//如果进价大于0不为赠品，则更新该商品的最后进价lastBuyPrice
				if($goodsPrice > 0){
					$sql = "update t_goods set lastBuyPrice = '$goodsPrice' where id = '%s' ";
					$db->execute($sql, $goodsId);
				}
				
			}
			
			$sql = "update t_pw_bill set bill_status = 5 where id = '%s' ";
			$db->execute($sql, $pw_billid);
			//更新验收单状态和根据供货商账期更新付款日期
			$map = array(
				"id" => $supplierId
			);
			$supplier_info = M("supplier", "t_")->where($map)->find();
			//获取无税金额
			$sql = "select sum(goods_money_no_tax) as goods_money_no_tax from t_ia_bill_detail where iabill_id = '$id'";
			$detail_data = $db->query($sql);
			$detail_goods_money_no_tax = $detail_data[0]["goods_money_no_tax"];
			//账期默认30天，验收入库后更新付款日期
			$pay_day  = intval($supplier_info["period"]) ? intval($supplier_info["period"]) : 30;
			$pay_time = time() + $pay_day * 24 * 3600;
			$pay_time_date = date("Y-m-d H:i:s", $pay_time);
			$sql = "update t_ia_bill set bill_status = 1, pay_time = '%s', goods_money_no_tax = $detail_goods_money_no_tax where id = '%s' ";
			$db->execute($sql, $pay_time_date, $id);
			//根据供货商的经营方式,确定应付款项和应收款项,如果是联营的话需要扣除返点。供应商要有返点率
			if($supplier_info["mode"] == 1 && $supplier_info["rebaterate"]){
				$rate = $supplier_info["rebaterate"];
				if(strpos($rate, "%") > -1){
					$rate = str_replace("%", "", $rate);
					$rate = floatval($rate / 100);
				}
				$billPayables = floatval((1-$rate) * $billPayables);
			}
			//应付明细账
			$sql = "insert into t_payables_detail (id, pay_money, act_money, balance_money,
					ca_id, ca_type, date_created, ref_number, ref_type, biz_date)
					values ('%s', %f, 0, %f, '%s', 'supplier', now(), '%s', '验收入库', '%s')";
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
			$log = "验收入库: 单号 = {$ref}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "验收入库");
			
			$db->commit();
			//同步电商库存
			if($goods_stock_array){
				$ms = new MallService();
				$ms->syn_inventory($goods_stock_array);
			}
		} catch ( Exception $exc ) {
			$db->rollback();
			return $this->bad("数据库操作错误，请重试或者联系管理员");
		}
		
		return $this->ok($id);
	}

	public function querenIABill($id) {
		$db = M();
		$sql = "select ref,caiwuqueren from t_ia_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要确认的验收单不存在");
		}
		$querenStatus = $data[0]["caiwuqueren"];
		if ($querenStatus) {
			return $this->bad("验收单已经确认");
		}
					
        $sql = "update t_ia_bill 
                set caiwuqueren = 1
                where id = '%s' ";
        $db->query($sql, $id);
		
		return $this->ok($id);
	}

	public function shoukuanIABill($id) {
		$db = M();
		$sql = "select ref,caiwushoukuan from t_ia_bill where id = '%s' ";
		$data = $db->query($sql, $id);
		
		if (! $data) {
			return $this->bad("要收款的验收单不存在");
		}
		$shoukuanStatus = $data[0]["caiwushoukuan"];
		if ($shoukuanStatus) {
			return $this->bad("验收单已经付款");
		}
					
        $sql = "update t_ia_bill 
                set caiwushoukuan = 1
                where id = '%s' ";
        $db->query($sql, $id);
		
		return $this->ok($id);
	}
}