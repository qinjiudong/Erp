<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\SupplierService;
use Home\Common\FIdConst;

/**
 * 供应商档案Controller
 *
 * @author 李静波
 *        
 */
class SupplierController extends Controller {

	public function index() {
		$us = new UserService();
		
		$this->assign("title", "供应商档案");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::SUPPLIER)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function categoryList() {
		if (IS_POST) {
			$params = array(
					"code" => I("post.code"),
					"name" => I("post.name"),
					"address" => I("post.address"),
					"contact" => I("post.contact"),
					"mobile" => I("post.mobile"),
					"tel" => I("post.tel"),
					"qq" => I("post.qq")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->categoryList($params));
		}
	}

	public function supplierList() {
		if (IS_POST) {
			$params = array(
					"categoryId" => I("post.categoryId"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"period" => I("post.period"),
					"address" => I("post.address"),
					"contact" => I("post.contact"),
					"mobile" => I("post.mobile"),
					"tel" => I("post.tel"),
					"qq" => I("post.qq"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->supplierList($params));
		}
	}

	public function editCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->editCategory($params));
		}
	}

	public function deleteCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->deleteCategory($params));
		}
	}

	public function editSupplier() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"address" => I("post.address"),
					"period" => I("post.period"),
					"mode"   => I("post.mode"),
					"addressShipping" => I("post.addressShipping"),
					"contact01" => I("post.contact01"),
					"mobile01" => I("post.mobile01"),
					"tel01" => I("post.tel01"),
					"qq01" => I("post.qq01"),
					"contact02" => I("post.contact02"),
					"mobile02" => I("post.mobile02"),
					"tel02" => I("post.tel02"),
					"qq02" => I("post.qq02"),
					"categoryId" => I("post.categoryId"),
					"initPayables" => I("post.initPayables"),
					"initPayablesDT" => I("post.initPayablesDT")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->editSupplier($params));
		}
	}

	public function deleteSupplier() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->deleteSupplier($params));
		}
	}

	public function queryData() {
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$ss = new SupplierService();
			$this->ajaxReturn($ss->queryData($queryKey));
		}
	}
	
	public function supplierInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SupplierService();
			$this->ajaxReturn($ss->supplierInfo($params));
		}
	}
}
