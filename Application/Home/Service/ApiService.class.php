<?php

namespace Home\Service;

/**
 * 通用外部调用接口
 */
class ApiService extends ERPBaseService {
	//出库
	public function Outlib($order_id) {
		
		if ($order_id) {
		$ref = $order_id;
		$db = M();
		$sql = "select * from t_ws_bill where ref = '%s'";
		$data = $db->query($sql, $order_id);
		if (!$data) {
			return $this->bad("要提交的订单不存在");
		}
		if ($data[0]['bill_status'] == 3) {
			return $this->bad("要提交的订单已经出库了");
		}
		if ($data[0]['bill_status'] < 2) {
			//return $this->bad("要提交的订单未完成拣货");
		}
		$user_id = $this->get_mall_user_id_by_customerid($data[0]["customer_id"]);
		$userid = $user_id;
		$phone  = $data[0]['tel'];
		$customerId = $data[0]["customer_id"];
		$mall_order_id = $data[0]['order_id'];
		$mall_order_sn = $data[0]['order_sn'];
		$saleMoney = $data[0]["sale_money"];
		$warehouseId = $data[0]["warehouse_id"];
		$bizDT = date("Y-m-d H:i:s", time());
		$bizUserId = $data[0]["biz_user_id"];
		$order_uuid = $data[0]["id"];
		$order_type = $data[0]["type"];
		/*
		$sql = "select * from t_ws_bill_detail where wsbill_id = '%s'";
		$data = $db->query($sql, $order_id);
		$order_str = '';
		foreach($data as $k => $d){
			$order_str .= '&order[' . $k . '][goods_id]=' . $d['rec_id'] . '&order['.$k.'][goods_price]=' . $d['apply_price'];
		}
		
		$mall_result = $this->Mall_Api('updateOrder', "order_status=3&order_surplus=$saleMoney&order_id=" . $mall_order_id . $order_str);
		if($mall_result['error'] != 0){
			return $this->bad("同步数据出错,请联系管理员");
		}
		*/
		$ms = new MallService();
		$ret = $ms->delivery($data[0]["id"]);
		if($ret["success"]== false){
			//return $ret;
		}
		$id = $order_id;
		$this->time_log("开始");
		$db->startTrans();
			try {
				//进行出库操作
				$sql = "select * 
					from t_ws_bill_detail 
					where wsbill_id = '%s' 
					order by show_order ";
				$items = $db->query($sql, $id);
				if (! $items) {
					$db->rollback();
					return $this->bad("订单详细商品不存在，无法出库");
				}
				
				foreach ( $items as $v ) {
					$goods_info = $this->base_get_goods_info($v["goods_id"]);
					//如果该商品拥有对应的基础商品
					if($goods_info["baseid"] && $goods_info["packrate"]){
						$base_goods_id   = $goods_info["baseid"];
						$packRate = $goods_info["packrate"];
						$base_goods_info = $this->base_get_goods_info($base_goods_id);
						if (! $base_goods_info ) {
							$db->rollback();
							return $this->bad("要出库的商品不存在(商品后台id = {$goods_info})");
						}
						$itemId  = $v["id"];
						$goodsId = $base_goods_id;
						$goodsCount = $goods_info["bulk"] == 0 ? round(floatval($v["apply_num"]) * $packRate, 3) : intval($v["apply_count"] * $packRate);
						$goodsPrice = floatval($v["apply_price"] / $goodsCount);
						$apply_price_no_tax = 0;
						$selltax = intval($goods_info["selltax"]) / 100;
						$apply_price_no_tax = $v["apply_price"] / ( 1 + $selltax);
						$goods_info = $base_goods_info;
					} else {
						$itemId  = $v["id"];
						$goodsId = $v["goods_id"];
						$goodsCount = $goods_info["bulk"] == 0 ? floatval($v["apply_num"]) : intval($v["apply_count"]);
						$goodsPrice = floatval($v["apply_price"] / $goodsCount);
						$apply_price_no_tax = 0;
						$selltax = intval($goods_info["selltax"]) / 100;
						$apply_price_no_tax = $v["apply_price"] / ( 1 + $selltax);
					}
					
					
					$sql = "select code, name from t_goods where id = '%s' ";
					$data = $db->query($sql, $goodsId);
					if (! $goods_info ) {
						$db->rollback();
						return $this->bad("要出库的商品不存在(商品后台id = {$goodsId})");
					}
					$goodsCode = $goods_info["code"];
					$goodsName = $goods_info["name"];
					//dump($goodsCount);
					if ($goodsCount <= 0) {
						//$db->rollback();
						//return $this->bad("商品[{$goodsCode} {$goodsName}]的出库数量需要是正数");
						//如果实际出库数量是0，那么直接跳过这个商品
						continue;
					}
					
					// 库存总账
					$sql = "select out_count, out_money, balance_count, balance_price,
							balance_money from t_inventory 
							where warehouse_id = '%s' and goods_id = '%s' ";
					$data = $db->query($sql, $warehouseId, $goodsId);
					if (! $data) {
						//$db->rollback();
						//return $this->bad("商品 [{$goodsCode} {$goodsName}] 在仓库 [{$warehouseName}] 中没有存货，无法出库");
					}
					$balanceCount = $data[0]["balance_count"];
					if ($balanceCount < $goodsCount) {
						//需要自动生成需补货的单
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
					//如果是联营的商品，则价格为
					if($goods_info["mode"] == 1){
						if($goods_info["rebaterate"]){
							$outPriceDetail = $this->calc_inv_price($goods_info["sale_price"], $goods_info["rebaterate"]);
						} else {
							$outPriceDetail = $goods_info["lastbuyprice"];
						}
						
					} else {
						$outPriceDetail = $goods_info["lastbuyprice"] ? $goods_info["lastbuyprice"] : 0;
					}
					//价格为单价
					$outPriceDetail = $outPriceDetail <= 0 ? $goodsPrice : $outPriceDetail;
					$outMoneyDetail = $goodsCount * $outPriceDetail;
					$outCount += $goodsCount;
					$outPrice = $outMoney / $outCount;
					

					//出库价格计算
					$outPriceDetail2 = $goodsPrice ? $goodsPrice : $goods_info["sale_price"];
					$outMoneyDetail2 = $v["apply_price"];
					$sql = "update t_inventory 
							set out_count = %f, out_price = %f, out_money = %f,
							    balance_count = %f, balance_money = %f 
							where warehouse_id = '%s' and goods_id = '%s' ";
					$db->execute($sql, $outCount, $outPrice, $outMoney, $balanceCount, $balanceMoney, $warehouseId, $goodsId);
					
					// 库存明细账
					$sql = "insert into t_inventory_detail(out_count, out_price, out_money, 
							balance_count, balance_price, balance_money, warehouse_id,
							goods_id, biz_date, biz_user_id, date_created, ref_number, ref_type) 
							values(%f, %f, %f, %f, %f, %f, '%s', '%s', '%s', '%s', now(), '%s', '销售出库')";
					$db->execute($sql, $goodsCount, $outPriceDetail2, $outMoneyDetail2, $balanceCount, $balancePrice, $balanceMoney, $warehouseId, $goodsId, $bizDT, $bizUserId, $ref);
					//获取主供应商
					$supplier = $db->query("select supplierid from t_supplier_goods where goodsid = '$goodsId' limit 1");
					$supplier_id = $supplier[0]["supplierid"];
					// 单据本身的记录
					$sql = "update t_ws_bill_detail 
							set inventory_price = %f, inventory_money = %f, supplier_id = '$supplier_id' ,apply_price_no_tax = '$apply_price_no_tax' 
							where id = '%s' ";
					$db->execute($sql, $outPriceDetail, $outMoneyDetail, $itemId);

					// 更新商品的最后销售记录
					$sql = "update t_goods set lastsale = '".time()."' where id = '".$goodsId."'";
					$db->execute($sql);
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
					$balanceMoney += 0;
					
					$sql = "update t_receivables
							set rv_money = %f,  balance_money = %f , act_money = %f 
							where ca_id = '%s' and ca_type = 'customer' ";
					$db->execute($sql, $rvMoney, $balanceMoney, $rvMoney, $customerId);
				} else {
					$sql = "insert into t_receivables (id, rv_money, act_money, balance_money,
							ca_id, ca_type) values ('%s', %f, $saleMoney, %f, '%s', 'customer')";
					$idGen = new IdGenService();
					$db->execute($sql, $idGen->newId(), $saleMoney, 0, $customerId);
				}
				
				// 应收明细账
				$sql = "insert into t_receivables_detail (id, rv_money, act_money, balance_money,
						ca_id, ca_type, date_created, ref_number, ref_type, biz_date) 
						values('%s', %f, $saleMoney, %f, '%s', 'customer', now(), '%s', '销售出库', '%s')";
				$idGen = new IdGenService();
				$db->execute($sql, $idGen->newId(), $saleMoney, 0, $customerId, $ref, $bizDT);
				
				// 单据本身设置为已经提交出库
				$sql = "select sum(inventory_money) as sum_inventory_money 
						from t_ws_bill_detail 
						where wsbill_id = '%s' ";
				$data = $db->query($sql, $id);
				$sumInventoryMoney = $data[0]["sum_inventory_money"];
				
				$profit = $saleMoney - $sumInventoryMoney;
				//将状态更新为已出库
				$sql = "update t_ws_bill 
						set bill_status = 3, inventory_money = %f, profit = %f , auto_status = 0 
						where ref = '%s' ";

				$db->execute($sql, $sumInventoryMoney, $profit, $id);
				
				$log = "销售单出库，单号 = {$ref}";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "销售出库");
				//$sql = "update t_ws_bill set bill_status = 3  where ref = '%s' ";
				//$db->execute($sql, $order_id);
				$db->commit();
				$this->time_log("提交");
				//判断是否所有订单都结束了，扣款
				$map1 = array(
					"order_id" => $mall_order_id,
					"type" => 0
				);
				$map2 = array(
					"order_id" => $mall_order_id,
					"bill_status" => array("gt", 1),
					"type" => 0
				);
				$order_db = M("ws_bill", "t_");
				if($order_type == 1){
					
					$diff_money = -$saleMoney;
					$ms = new MallService();
					$ms->order_finish($mall_order_id, $diff_money, $user_id, $mall_order_sn);
				} else {
					if($order_db->where($map1)->count() == $order_db->where($map2)->count()){
						//获取实际总金额
						$mall_order = $order_db->where($map2)->field("sum(sale_money) as total_sale_money, sum(mall_money) as total_mall_money, sum(discount) as total_discount")->find();
						$total_money = $mall_order["total_sale_money"];
						$total_mall_money = $mall_order["total_mall_money"];
						$total_discount   = $mall_order["total_discount"];
						$diff_money = $total_mall_money - $total_money;
						//判断是不是有折扣
						//如果有差额就扣款
						//if($diff_money!=0){
							$ms = new MallService();
							$ms->order_finish($mall_order_id, $diff_money, $user_id, $mall_order_sn);
						//}
					} else {

					}
				}
				$this->time_log("电商通信");
				
				//发送微信，首先获取是否需要发送微信

				$is_weixin = $this->getConfig("2002-03");
				if($is_weixin == 1){
					$weixin_content = $this->getConfig("10000-01");
					$weixin_content = str_replace("{order_ref}", $ref, $weixin_content);
					$weixin_content = str_replace("{code}", $code, $weixin_content);
					$sms = new SmsService();
					$params = array(
						"userid" => $userid,
						"msg" => $weixin_content
					);
					//$sms->wx_send($params);
				}
				//是否需要发送短信
				$is_sms = $this->getConfig("2002-04");
				if($is_sms == 1){
					$sms_content = $this->getConfig("10000-01");
					$sms_content = str_replace("{order_ref}", $ref, $sms_content);
					$sms_content = str_replace("{code}", $code, $sms_content);
					$sms = new SmsService();
					$params = array(
						"phones" => $phone,
						"msg" => $sms_content
					);
					//$sms->send($params);
				}
				//通知柜系统
				$box = new BoxService();
				$box->notify_box($ref);
				$this->time_log("柜系统通信");
				//联营商品自动采购验收
				$auto = new AutoService();
				//$auto->autoAcceptance($order_uuid);
				return $this->ok();
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		}
		return $this->bad("fail");
	}	

	//拣货
	public function Pick($id, $arr = array()) {
		$db = M();
		
		$sql = "select * from t_ws_bill_detail where id = '%s' ";
		$data = $db->query($sql, $id);
		if (!$data) {
			return $this->bad("提交的数据不正确");
		}
		$good_id = $data[0]['goods_id'];
		$apply_price = !empty(I("request.apply_price")) ? I("request.apply_price") : $data[0]['goods_money'];
		$apply_count = !empty(I("request.apply_count")) ? I("request.apply_count") : $data[0]['goods_count'];
		$apply_num = !empty(I("request.apply_num")) ? I("request.apply_num") : $data[0]['goods_attr'];
		if(!empty($arr)){
			$apply_price = !empty($arr['apply_price']) ? $arr['apply_price'] : $data[0]['goods_money'];
			$apply_count = !empty($arr['apply_count']) ? $arr['apply_count'] : $data[0]['goods_count'];
			$apply_num = !empty($arr['apply_num']) ? $arr['apply_num'] : $data[0]['goods_attr'];
		}
		$sql = "select * from t_ws_bill where ref = '%s' and bill_status in (0, 1, 2)";
		$wsbill_id = $data[0]["wsbill_id"];
		$rec_id = $data[0]["rec_id"];
		$data = $db->query($sql, $wsbill_id);
		if (!$data) {
			return $this->bad("已经发货，无法修改");
		}
		//检查库存
		$get_good_info = $this->get_good_info($good_id);
		$balance_count = $get_good_info['balance_count'] - $apply_count;
		$msg = '';
		$mall_order_id = $data[0]['order_id'];//商城订单id
		$db->startTrans();
			try {
				if($balance_count < 0){//加入采购单列表
					$msg = '商品:' .$get_good_info['name'] . ' 库存不足，还差 ' . $balance_count . '个，已经添加到补货列表<br>';
				}
				$sql = "update t_ws_bill_detail set is_picked = 1 ,apply_price = '$apply_price', apply_count = '$apply_count', apply_num = '$apply_num' where id = '%s' ";
				$db->execute($sql, $id);
				$sql = "select wsbill_id from t_ws_bill_detail where wsbill_id = '%s' and is_picked = 0";
				$data = $db->query($sql, $wsbill_id);
				if (!$data) {
					$status = 2;
				}else{
					$status = 1;
				}
				$mall_result = $this->Mall_Api('updateOrder', 'shipping_status=' . $status . '&order_id=' . $mall_order_id . '&order[1][goods_id]=' . $rec_id . '&order[1][goods_price]=' . $apply_price );
				if($mall_result['error'] != 0){
					return $this->bad("同步数据出错,请联系管理员");
				}
				$sql = "update t_ws_bill set bill_status = '%s'  where ref = '%s' ";
				$db->execute($sql, $status, $wsbill_id);
				$db->commit();
				return $this->ok($msg);
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		
		return $this->bad("fail");
	}
	//批量出库
	public function batchOutLib($params){
		//sleep(5);
		$db = M();
		$order_refs = $params["order_ref"];
		$goods_code = $params["goods_code"];
		if(!$goods_code){
			return $this->bad("请输入需批量出库的商品编码");
		}
		//查询商品是否存在
		$map = array(
			"code" => $goods_code
		);
		$goods_info = M("goods")->where($map)->find();
		if(!$goods_info){
			return $this->bad("该编码不存在");
		}
		if($goods_info["bulk"] == 0){
			return $this->bad("计重商品无法批量出库");
		}
		$order_ref_arr = explode(",", $order_refs);
		$order_count = count($order_ref_arr);
		foreach ($order_ref_arr as $ref) {
			if(!$ref){
				continue;
			}
			
			$map = array(
				"ref" => $ref
			);
			$order = M("ws_bill", "t_")->where($map)->find();
			//如果订单已经出库，则略过
			if($order["bill_status"] != 0){
				continue;
			}
			//需要确认是否只包含该code的商品
			$order_detail = M("ws_bill_detail")->where(array("wsbill_id"=>$ref))->select();
			if(count($order_detail) > 1 || count($order_detail) <= 0){
				continue;
			}
			//批量出库的时候需要填入默认的出库价格和出库数量
			$sql = "update t_ws_bill_detail set apply_price = goods_money, apply_count = goods_count where wsbill_id='$ref'";
			$db->execute($sql);
			$result = $this->Outlib($ref);
		}
		if($result["success"] == false){
			if(!$result){
				$result = array(
					"msg" => "没有选择正确的订单",
					"success" => false
				);
			}
			return $result;
		} else {
			return $this->ok("$order_count");
		}
		
	}
	//整单拣货
	public function PickOrder($id) {
		$db = M();
		$items = json_decode(html_entity_decode(I("request.items")), true);
		$sql = "select * from t_ws_bill where id = '%s' or ref = '%s'";
		$data = $db->query($sql, $id, $id);
		if (!$data) {
			return $this->bad("提交的数据不正确");
		}
		$msg = '';
		foreach($items as $i){
			$rs = $this->Pick($i['id'], $i);	
			if(!$rs['success']){
				return $this->bad($rs['msg']);
			}
			if($rs['id']){
				$msg .= $rs['id'];
			}
		}
		$db->startTrans();
			try {
				
				$sql = "update t_ws_bill_detail set is_picked = 1  where wsbill_id = '%s' ";
				$db->execute($sql, $data[0]['ref']);

				$sql = "update t_ws_bill set bill_status = 2  where id = '%s' ";
				$db->execute($sql, $data[0]['id']);
				$db->commit();
				return $this->ok($msg);
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		
		return $this->bad("fail");
	}	
	//货物到站
	public function Finish($params) {
		$db = M();
		$id = $params["id"];
		$cabinetno = $params['cabinetno'];
		$boxno = $params['boxno'];
		$code  = $params['code'];
		$pick_type = $params['pick_type'];
		if(!$pick_type){
			$pick_type = "自提";
		}
		$sql = "select * from t_ws_bill where id = '%s' or ref = '%s'";
		$data = $db->query($sql, $id, $id);
		if (!$data) {
			return $this->bad("提交的数据不正确");
		}
		if ($data[0]['bill_status'] < 2) {
			return $this->bad("订单状态不正确，不能入站");
		}
		$ref = $data[0]['ref'];
		$customer_id = $data[0]['customer_id'];
		$customer = M("customer", "t_")->where(array("id"=>$customer_id))->find();
		$phone = $data[0]['tel'];
		$userid = $customer["code"];
		$now = time();
		$sitename = $data[0]['sitename'];
		$box   = new BoxService();
		$db->startTrans();
			try {
				$mall_order_id = $data[0]['order_id'];
				$ms = new MallService();
				$result = $ms->order_status($mall_order_id, 2, 5);
				$sql = "update t_ws_bill set bill_status = 4 ,pick_code='$code', stock_time = $now, cabinetno = '$cabinetno', boxno = '$boxno', pick_type='$pick_type' where id = '%s' ";
				$db->execute($sql, $data[0]['id']);
				//存入柜子日志
				$box_data = array(
					"cabinetno" => $cabinetno,
					"boxno" => $boxno,
					"ref" => $ref,
					"siteid" => $data[0]['siteid'],
					"sitename" => $data[0]['sitename'],
					"code" => $code,
					"data" => json_encode($params)
				);
				$box->stock($box_data);
				$db->commit();
				//获取对应的生鲜柜的信息
				$map = array(
					"no" => $cabinetno
				);
				$_sitename = M("cabinet")->where($map)->getField("name");
				if($_sitename){
					$sitename = $_sitename;
				}
				//发送微信，首先获取是否需要发送微信
				$is_weixin = $this->getConfig("2002-03");
				$boxinfo = $this->get_box_info_by_no($cabinetno, $boxno);
				if($is_weixin == 1){
					$weixin_content = $this->getConfig("10000-02");
					$weixin_content = str_replace("{order_ref}", $ref, $weixin_content);
					$weixin_content = str_replace("{code}", $code, $weixin_content);
					$weixin_content = str_replace("{boxinfo}", $boxinfo, $weixin_content);
					//$weixin_content = str_replace("{cno}", $cabinetno, $weixin_content);
					//$weixin_content = str_replace("{bno}", $boxno, $weixin_content);
					$sms = new SmsService();
					$params = array(
						"userid" => $userid,
						"msg" => $weixin_content
					);
					$sms->wx_send($params);
				}
				//是否需要发送短信
				$is_sms = $this->getConfig("2002-04");
				if($is_sms == 1){
					$sms_content = $this->getConfig("10000-02");
					$sms_content = str_replace("{order_ref}", $ref, $sms_content);
					$sms_content = str_replace("{code}", $code, $sms_content);
					$sms_content = str_replace("{boxinfo}", $boxinfo, $sms_content);
					//$sms_content = str_replace("{cno}", $cabinetno, $sms_content);
					//$sms_content = str_replace("{bno}", $boxno, $sms_content);
					//dump($sms_content);
					$sms = new SmsService();
					$params = array(
						"phones" => $phone,
						"msg" => $sms_content
					);
					//$sms->send($params);
				}
				return $this->ok();
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		
		return $this->bad("fail");
	}

	//自助取货
	public function user_pick($params){
		$db = M();
		$id = $params["id"];
		$cabinetno = $params['cabinetno'];
		$boxno = $params['boxno'];
		$code  = $params['code'];
		$pick_type = $params['pick_type'];
		if(!$pick_type){
			$pick_type = "自提";
		}
		$sql = "select * from t_ws_bill where id = '%s' or ref = '%s'";
		$data = $db->query($sql, $id, $id);
		if (!$data) {
			return $this->bad("提交的数据不正确");
		}
		if ($data[0]['bill_status'] < 2) {
			return $this->bad("订单状态不正确，不能取货");
		}
		$ref = $data[0]['ref'];
		$customer_id = $data[0]['customer_id'];
		$customer = M("customer", "t_")->where(array("id"=>$customer_id))->find();
		$phone = $data[0]['tel'];
		$userid = $customer["code"];
		$now = time();
		
		$box   = new BoxService();
		$db->startTrans();
			try {
				$mall_order_id = $data[0]['order_id'];
				$ms = new MallService();
				$result = $ms->order_status($mall_order_id, 2, 5);
				$sql = "update t_ws_bill set bill_status = 5 , pick_time = $now, pick_type='$pick_type' where id = '%s' ";
				$db->execute($sql, $data[0]['id']);
				//dump($data);
				//取出柜子的日志
				$box_data = array(
					"cabinetno" => $cabinetno,
					"boxno" => $boxno,
					"ref" => $ref,
					"siteid" => $data[0]['siteid'],
					"sitename" => $data[0]['sitename'],
					"code" => $code,
					"data" => json_encode($params)
				);
				$box->pick($box_data);
				$db->commit();
				return $this->ok();
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		
		return $this->bad("fail");
	}

	//送货上门
	public function delivery_pick($params){
		$db = M();
		$id = $params["id"];
		$cabinetno = $params['cabinetno'];
		$boxno = $params['boxno'];
		$code  = $params['code'];
		$pick_type = $params['pick_type'];
		if(!$pick_type){
			$pick_type = "送货上门";
		}
		$sql = "select * from t_ws_bill where id = '%s' or ref = '%s'";
		$data = $db->query($sql, $id, $id);
		if (!$data) {
			return $this->bad("提交的数据不正确");
		}
		if ($data[0]['bill_status'] < 2) {
			return $this->bad("订单状态不正确，不能取货");
		}
		$ref = $data[0]['ref'];
		$customer_id = $data[0]['customer_id'];
		$customer = M("customer", "t_")->where(array("id"=>$customer_id))->find();
		$phone = $data[0]['tel'];
		$userid = $customer["code"];
		$now = time();
		
		$box   = new BoxService();
		$db->startTrans();
			try {
				$mall_order_id = $data[0]['order_id'];
				$ms = new MallService();
				$result = $ms->order_status($mall_order_id, 2, 5);
				$sql = "update t_ws_bill set bill_status = 5 , pick_time = $now, pick_type='$pick_type' where id = '%s' ";
				$db->execute($sql, $data[0]['id']);
				$db->commit();
				return $this->ok();
			} catch ( Exception $exc ) {
				$db->rollback();
				return $this->bad("数据库错误，请联系管理员");
			}
		
		return $this->bad("fail");
	}

	//同步商城接口
	public function Mall_Api($act, $data) {
		$mall_url = "http://mall.wifijiangyin.com/goods_service.php?act=$act&$data";
		$rs = json_decode(file_get_contents($mall_url), true);
		if($_GET['debug'] == 'true'){
			echo $mall_url;	
		}
		return $rs;
	}	
	
	//获取某件商品信息和库存数量
	public function get_good_info($params) {
		$db = M();
		$sql = "select g.id as goods_id, g.code, g.name, g.spec, u.name as unit_name 
				from t_goods g, t_goods_unit u 
				where (g.code = '%s' or g.barcode = '%s') and g.unit_id = u.id 
				";
		//$sql = "SELECT * FROM `t_goods` WHERE `code` like '%s' or `barCode` = '%s'";
		//echo $sql;exit;
		$rs = $db->query($sql, $params["goodsCode"], $params["barCode"]);
		if(!$rs){
			return $this->bad("商品不存在");
		}
		$good_info = $rs[0];
		$sql = "SELECT balance_count FROM `t_inventory` WHERE `goods_id` LIKE '%s'";
		$data = $db->query($sql, $good_info['id']);
		$good_info['balance_count'] = floatval($data[0]['balance_count']);
		return $good_info;
	}

	public function getGoodsInfo($params){
		$db = M();
		$sql = "select g.id as goods_id, g.code,g.barcode, g.name, g.spec, u.name as unit_name 
				from t_goods g, t_goods_unit u 
				where (g.code = '%s' or g.barcode = '%s') and g.unit_id = u.id 
				";
		$rs = $db->query($sql, $params["goodsCode"], $params["barCode"]);
		if(!$rs){
			return $this->bad("商品不存在");
		}
		//获取仓库和库存
		$good_info = $rs[0];
		$sql = "SELECT balance_count,warehouse_id FROM `t_inventory` WHERE `goods_id` = '%s'";
		$data = $db->query($sql, $good_info['goods_id']);
		$good_info['balance_count'] = floatval($data[0]['balance_count']);
		$wid = $data[0]['warehouse_id'];
		$map = array(
			"id" => $wid
		);
		$good_info['warehouse_name'] = M("warehouse", "t_")->where($map)->getField("name");
		$map = array(
			"code" => $good_info["code"]
		);
		$position_id = M("position", "t_")->where($map)->getField("position_id");
		if($position_id){
			$map = array(
				"id" => $position_id
			);
			$good_info['position_name'] = M("position_category", "t_")->where($map)->getField("name");
		} else {
			$good_info['position_name'] = "";
		}
		return $this->suc($good_info);
	}

	public function get_box_info($params){
		$openid = $params["openid"];
		$user = $this->get_customer_by_openid($openid);
		$map = array(
			"customer_id" => $user["id"],
			"bill_status" => 4
		);
		$list = M("ws_bill")->field("sitename,address,cabinetno,boxno,pick_code,pick_type")->where($map)->select();
		$list = $list ? $list : array();
		return $list;
	}

}