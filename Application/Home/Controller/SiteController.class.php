<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\SiteService;
use Home\Common\FIdConst;


/**
 * 站点管理Service
 *
 * @author XH
 */
class SiteController extends Controller {

	public function index() {
		$us = new UserService();
		
		$this->assign("title", "站点管理");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::SITE)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function lineList(){
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
			$ss = new SiteService();
			$this->ajaxReturn($ss->lineList($params));
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
			$ss = new SiteService();
			$this->ajaxReturn($ss->categoryList($params));
		}
	}

	public function siteList() {
		if (IS_POST) {
			$params = array(
					"categoryId" => I("post.categoryId"),
					"lineId" => I("post.lineId"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"address" => I("post.address"),
					"contact" => I("post.contact"),
					"mobile" => I("post.mobile"),
					"tel" => I("post.tel"),
					"qq" => I("post.qq"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			$ss = new SiteService();
			$this->ajaxReturn($ss->siteList($params));
		}
	}

	public function editLine(){
		if(IS_POST){
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"des"  => I("post.des")
			);
			$ss = new SiteService();
			$this->ajaxReturn($ss->editLine($params));
		}
	}

	public function editCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"des"  => I("post.des")
			);
			$ss = new SiteService();
			$this->ajaxReturn($ss->editCategory($params));
		}
	}

	public function deleteCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SiteService();
			$this->ajaxReturn($ss->deleteCategory($params));
		}
	}

	public function editSite() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"address" => I("post.address"),
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
					"initPayablesDT" => I("post.initPayablesDT"),
					"attr" => I("post.attr"),
					"freight" => I("post.freight"),
					"houses" => I("post.houses"),
					"lineId" => I("post.lineId"),
					"sort" => I("post.sort")
			);
			$ss = new SiteService();
			$this->ajaxReturn($ss->editSite($params));
		}
	}

	public function deleteSite() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SiteService();
			$this->ajaxReturn($ss->deleteSite($params));
		}
	}

	public function queryData() {
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$ss = new SiteService();
			$this->ajaxReturn($ss->queryData($queryKey));
		}
	}
	
	public function siteInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new SiteService();
			$this->ajaxReturn($ss->siteInfo($params));
		}
	}

	public function initLine(){
		$list = M("siteline")->select();
		foreach ($list as $key => $value) {
			//查询到line
			$line = M("site_line")->where(array("name"=>$value["line"]))->find();
			$map = array(
				"name" => $value["site"]
			);
			M("site")->where($map)->setField("line_id", $line["id"]);
		}
	}
}
