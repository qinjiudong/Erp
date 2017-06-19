<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\WarehouseService;
use Home\Common\FIdConst;
use Home\Service\BizConfigService;

/**
 * 仓库Controller
 * @author 李静波
 *
 */
class WarehouseController extends Controller {

	public function index() {
		$us = new UserService();
		
		$this->assign("title", "仓库");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::WAREHOUSE)) {
			$ts = new BizConfigService();
			$this->assign("warehouseUsesOrg", $ts->warehouseUsesOrg());
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function warehouseList() {
		if (IS_POST) {
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->warehouseList());
		}
	}

	public function editWarehouse() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name")
			);
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->editWarehouse($params));
		}
	}

	public function deleteWarehouse() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->deleteWarehouse($params));
		}
	}

	public function queryData() {
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$fid = I("post.fid");
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->queryData($queryKey, $fid));
		}
	}

	public function warehouseOrgList() {
		if (IS_POST) {
			$params = array(
					"warehouseId" => I("post.warehouseId"),
					"fid" => I("post.fid")
			);
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->warehouseOrgList($params));
		}
	}

	public function allOrgs() {
		$ws = new WarehouseService();
		
		$this->ajaxReturn($ws->allOrgs());
	}

	public function addOrg() {
		if (IS_POST) {
			$params = array(
					"warehouseId" => I("post.warehouseId"),
					"fid" => I("post.fid"),
					"orgId" => I("post.orgId")
			);
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->addOrg($params));
		}
	}

	public function deleteOrg() {
		if (IS_POST) {
			$params = array(
					"warehouseId" => I("post.warehouseId"),
					"fid" => I("post.fid"),
					"orgId" => I("post.orgId")
			);
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->deleteOrg($params));
		}
	}

	public function orgViewWarehouseList() {
		if (IS_POST) {
			$params = array(
					"orgId" => I("post.orgId")
			);
			$ws = new WarehouseService();
			$this->ajaxReturn($ws->orgViewWarehouseList($params));
		}
	}
}