<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\ShopService;
use Home\Common\FIdConst;

/**
 * 仓位管理Controller
 *
 * @author xh
 *        
 */
class ShopController extends Controller {

	public function index() {
		$us = new UserService();
		
		$this->assign("title", "店铺管理");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::SHOP)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

//	public function categoryList() {
//		if (IS_POST) {
//			$params = array(
//					"code" => I("post.code"),
//					"name" => I("post.name"),
//					"address" => I("post.address"),
//					"contact" => I("post.contact"),
//					"mobile" => I("post.mobile"),
//					"tel" => I("post.tel"),
//					"qq" => I("post.qq")
//			);
//			$ss = new ShopService();
//			$this->ajaxReturn($ss->categoryList($params));
//		}
//	}

	public function shopList() {
			$ss = new ShopService();
			$this->ajaxReturn($ss->shopList());
	}
	public function UserList() {
			$ss = new ShopService();
			$this->ajaxReturn($ss->UserList());
	}
	//查看商品列表
	public function goods() {
			$params = array(
					"shopcode" => I("request.categoryId"),
					"code" => I("request.code"),
					"name" => I("request.name"),
					"spec" => I("request.spec"),
					"barcode" => I("request.barcode"),
					"is_delete" => I("request.is_delete"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit")
			);
			$ss = new ShopService();
			$this->ajaxReturn($ss->goods($params));
	}
	public function goodsTransfer() {
			$params = array(
					"code" => I("post.code"),
					"number" => I("post.number"),
					"inShopId" => I("post.inShopId"),
					"inShopName" => I("post.inShopName"),
					"outShopName" => I("post.outShopName"),
					"outShopId" => I("post.outShopId")
			);
			$ss = new ShopService();
			$this->ajaxReturn($ss->goodsTransfer($params));
	}
//
//	//修改商品
//	public function editgoods() {
//			$ss = new ShopService();
//			$this->ajaxReturn($ss->editgoods(I('shop_id'),I('code'),I('id')));
//	}
//	//删除商品
//	public function deletegood() {
//			$ss = new ShopService();
//			$this->ajaxReturn($ss->deletegood(I('id')));
//	}
//	public function editCategory() {
//		if (IS_POST) {
//			$params = array(
//					"id" => I("post.id"),
//					"code" => I("post.code"),
//					"name" => I("post.name")
//			);
//			$ss = new ShopService();
//			$this->ajaxReturn($ss->editCategory($params));
//		}
//	}
//
//	public function deleteCategory() {
//		if (IS_POST) {
//			$params = array(
//					"id" => I("post.id")
//			);
//			$ss = new ShopService();
//			$this->ajaxReturn($ss->deleteCategory($params));
//		}
//	}

	public function editShop() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"sort" => I("post.sort"),
					"address" => I("post.address"),
					"shopkeeper" => I("post.shopkeeper"),
					"remark" => I("post.remark"),
			);
			$ss = new ShopService();
			$this->ajaxReturn($ss->editShop($params));
		}
	}

	public function shopinfos() {
		if (IS_POST) {
			$ss = new ShopService();
			$this->ajaxReturn($ss->shopinfos(I("post.id")));
		}
	}

	public function deleteShop() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$ss = new ShopService();
			$this->ajaxReturn($ss->deleteShop($params));
		}
	}
//
//	public function queryData() {
//		if (IS_POST) {
//			$queryKey = I("post.queryKey");
//			$ss = new ShopService();
//			$this->ajaxReturn($ss->queryData($queryKey));
//		}
//	}
//	
//	public function shopInfo() {
//		if (IS_POST) {
//			$params = array(
//					"id" => I("post.id")
//			);
//			$ss = new ShopService();
//			$this->ajaxReturn($ss->shopInfo($params));
//		}
//	}
}
