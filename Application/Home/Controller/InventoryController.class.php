<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\InventoryService;
use Home\Common\FIdConst;
use Home\Service\ITBillService;
set_time_limit(0);
/**
 * 库存Controller
 * @author XH
 *
 */
class InventoryController extends Controller {

	public function initIndex() {
		$us = new UserService();
		
		$this->assign("title", "库存建账");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::INVENTORY_INIT)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function inventoryQuery() {
		$us = new UserService();
		
		$this->assign("title", "库存查询");
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
	public function InvLoss() {
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
	
	public function Purchase() {
		$us = new UserService();
		
		$this->assign("title", "库存补货");
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
	public function InvTransfer() {
		$us = new UserService();
		
		$this->assign("title", "库间调拨");
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
	public function InvCheck() {
		$us = new UserService();
		
		$this->assign("title", "库存盘点");
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

	public function inventoryList() {
		if (IS_POST) {
			$params = array(
					"warehouseId" => I("post.warehouseId"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"spec" => I("post.spec"),
					"barCode" => I("post.barCode"),
					"supplier" => I("post.supplier"),
					"category" => I("post.category"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			$is = new InventoryService();
			$this->ajaxReturn($is->inventoryList($params));
		}
	}

	public function inventoryDetailList() {
		if (IS_POST) {
			$params = array(
					"warehouseId" => I("post.warehouseId"),
					"goodsId" => I("post.goodsId"),
					"dtFrom" => I("post.dtFrom"),
					"dtTo" => I("post.dtTo"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			$is = new InventoryService();
			$this->ajaxReturn($is->inventoryDetailList($params));
		}
	}

	/**
	 * 盘点单主表信息列表
	 */
	public function itbillList() {
		if (IS_POST) {
			$params = array(
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
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
			
			$this->ajaxReturn($is->itBillInfo($params));
		}
	}

	/**
	 * 盘点单明细信息
	 */
	public function itBillDetailList() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->itBillDetailList($params));
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
	 * 提交盘点单
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
	/**
	 * 删除采购单
	 */
	public function deletePurchase() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$is = new ITBillService();
			$this->ajaxReturn($is->deletePurchase($params));
		}
	}
	/**
	 * 盘点单主表信息列表
	 */
	public function PurchaseList() {
		if (IS_POST) {
			$params = array(
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$is = new ITBillService();
			
			$this->ajaxReturn($is->PurchaseList($params));
		}
	}

}
