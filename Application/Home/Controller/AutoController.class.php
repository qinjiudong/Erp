<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Common\FIdConst;
use Home\Service\AutoService;
/**
 * 自动处理数据接口
 * @author dubin
 *
 */
class AutoController extends Controller {
	public function dealGoodsByOrder(){
		//扫描订单表，自动处理
		$map = array(
			"auto_status" => 0,
			"bill_status" => 2
		);
		$db = M("ws_bill", "t_");
		$order = $db->where($map)->find();
		if(!$order){
			die("left 0");
		}
		$map = array(
			"id" => $order["id"]
		);
		//首先将正在处理的订单处理状态置为2，防止多次处理
		$db->where($map)->setField("auto_status", 2);
		$as = new AutoService();
		$result = $as->autoAcceptance($order["id"]);
		if($result["success"]){
			$db->where($map)->setField("auto_status", 1);
		}
	}

	/* 扫描库存表，生成进销存日报表 */
	public function dealReportByDetail(){
		$map = array(
			"is_tongji" => 0
		);
		$db = M("inventory_detail");
		$detail = $db->where($map)->find();
		if(!$detail){
			die("left 0");
		}
		$map = array(
			"id" => $detail["id"]
		);
		//首先将正在处理的订单处理状态置为2，防止多次处理
		$db->where($map)->setField("is_tongji", 2);
		$as = new AutoService();
		$result = $as->tongji($detail["id"]);
		echo json_encode($result);
		if($result["success"]){
			//$db->where($map)->setField("is_tongji", 1);
		}
	}

	
	
}
