<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\InventoryService;
use Home\Common\FIdConst;
use Home\Service\ITBillService;
use Home\Service\MallService;
/**
 * 损溢Controller
 * @author XH
 * @modify dubin
 *
 */
class InvLossController extends Controller {

	public function InvCheck() {
		$us = new UserService();
		
		$this->assign("title", "库存损溢");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::INVENTORY_QUERY)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}
	

	public function warehouseList() {
		if (IS_POST) {
			$is = new InventoryService();
			$this->ajaxReturn($is->warehouseList());
		}
	}

	public function reasonList(){
		if (IS_POST) {
			$list = M("reason")->select();
			$count = count($list);
			$arr = array(
				"dataList" => $list,
				"totalCount" => $count
			);
			$this->ajaxReturn($arr);
		}
	}


	/**
	 * 损溢单主表信息列表
	 */
	public function itbillList() {
		if (IS_POST) {
			$params = array(
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit"),
					"ref" => I("post.ref"),
					"begindate" => I("post.begindate"),
					"enddate" => I("post.enddate")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->InvLossList($params));
		}
	}

	/**
	 * 新建或编辑损溢单
	 */
	public function editITBill() {
		if (IS_POST) {
			$params = array(
					"jsonStr" => I("post.jsonStr")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->editInvLossBill($params));
		}
	}

	/**
	 * 获取单个损溢单的信息
	 */
	public function itBillInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->InvLossBillInfo($params));
		}
	}

	/**
	 * 损溢单明细信息
	 */
	public function itBillDetailList() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->InvLossDetailList($params));
		}
	}

	/**
	 * 删除损溢单
	 */
	public function deleteITBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->deleteITBill($params));
		}
	}

	/**
	 * 提交损溢单
	 */
	public function commitITBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->commitInvLoss($params));
		}
	}	
}
