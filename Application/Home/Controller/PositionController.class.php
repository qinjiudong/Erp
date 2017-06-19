<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\PositionService;
use Home\Common\FIdConst;

/**
 * 仓位管理Controller
 *
 * @author xh
 *        
 */
class PositionController extends Controller {

	public function index() {
		$us = new UserService();
		
		$this->assign("title", "仓位管理");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::POSITION)) {
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
			$ss = new PositionService();
			$this->ajaxReturn($ss->categoryList($params));
		}
	}

	public function positionList() {
			$params = array(
					"code" => I("request.code"),
					"name" => I("request.name"),
					"node" => I("request.node")
			);
			$ss = new PositionService();
			$this->ajaxReturn($ss->positionList($params));
	}
	//查看商品列表
	public function goods() {
			$ss = new PositionService();
			$this->ajaxReturn($ss->goods(I('position_id')));
	}

	//修改商品
	public function editgoods() {
			$ss = new PositionService();
			$this->ajaxReturn($ss->editgoods(I('position_id'),I('code'),I('id')));
	}
	//删除商品
	public function deletegood() {
			$ss = new PositionService();
			$this->ajaxReturn($ss->deletegood(I('id')));
	}
	public function editCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name")
			);
			$ss = new PositionService();
			$this->ajaxReturn($ss->editCategory($params));
		}
	}

	public function deleteCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new PositionService();
			$this->ajaxReturn($ss->deleteCategory($params));
		}
	}

	public function editPosition() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"pid" => I("post.pid"),
					"sort" => I("post.sort"),
					"fullname" => I("post.fullname"),
					"wherehouse_id" => I("post.wherehouse_id")
			);
			$ss = new PositionService();
			$this->ajaxReturn($ss->editPosition($params));
		}
	}

	public function positioninfos() {
		if (IS_POST) {
			$ss = new PositionService();
			$this->ajaxReturn($ss->positioninfos(I("post.id")));
		}
	}

	public function deletePosition() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new PositionService();
			$this->ajaxReturn($ss->deletePosition($params));
		}
	}

	public function queryData() {
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$ss = new PositionService();
			$this->ajaxReturn($ss->queryData($queryKey));
		}
	}
	
	public function positionInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new PositionService();
			$this->ajaxReturn($ss->positionInfo($params));
		}
	}
}
