<?php

namespace Home\Service;

/**
 * 业务设置Service
 *
 * @author 李静波
 */
use Home\Service\UserService;
class TodoService extends ERPBaseService {
	//登录情况下获取登陆者拥有的权限
	public function getTodoList($userid) {
		$us = new UserService();
		$permissions = $us->allPermission($userid);
		$todoList = array();
		$i = 1;
		if(in_array("采购单生成", $permissions)){
			$item = array(
				"id" => $i,
				"todoName"   => "待提交的采购单",
				"totalCount" => $this->getPWBillsCount("0"),
				"url" => __ROOT__ . "/Home/Purchase/pwbillIndex"
			);
			$todoList[] = $item;
			$i++;
			$item = array(
				"id" => $i,
				"todoName"   => "被驳回的采购单",
				"totalCount" => $this->getPWBillsCount("3"),
				"url" => __ROOT__ . "/Home/Purchase/pwbillIndex"
			);
			$todoList[] = $item;
			$i++;
			$item = array(
				"id" => $i,
				"todoName"   => "审核中的采购单",
				"totalCount" => $this->getPWBillsCount("2"),
				"url" => __ROOT__ . "/Home/Purchase/pwbillIndex"
			);
			$todoList[] = $item;
			$i++;
		}
		if(in_array("采购单审核", $permissions)){
			$item = array(
				"id" => $i,
				"todoName"   => "需您审核的采购单",
				"totalCount" => $this->getPWBillsCount("2"),
				"url" => __ROOT__ . "/Home/PurchaseVerify/index"
			);
			$todoList[] = $item;
			$i++;
		}
		if(in_array("验收单", $permissions)){
			$item = array(
				"id" => $i,
				"todoName"   => "待验收的采购单",
				"totalCount" => $this->getPWBillsCount("1"),
				"url" => __ROOT__ . "/Home/InvAcceptance"
			);
			$todoList[] = $item;
			$i++;
		}

		if(in_array("加工单审核", $permissions)){
			$item = array(
				"id" => $i,
				"todoName"   => "需您审核的加工单",
				"totalCount" => $this->getPWBillsCount("1")
			);
			$todoList[] = $item;
			$i++;
		}
		return array(
			"dataList"   => $todoList,
			"totalCount" => count($todoList)
		);

	}

	//根据状态获取采购单的数量
	public function getPWBillsCount($status_str){
		$pwdb = M("pw_bill", "t_");
		$status_arr = explode(",", $status_str);
		$map = array(
			"bill_status" => array("in", $status_arr)
		);
		$count = $pwdb->where($map)->count();
		return $count;
	}

}