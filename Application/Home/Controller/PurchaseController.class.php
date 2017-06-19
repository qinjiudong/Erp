<?php
namespace Home\Controller;
use Think\Controller;

use Home\Service\UserService;
use Home\Service\PWBillService;
use Home\Common\FIdConst;

/**
 * 采购Controller
 * @author 李静波
 *
 */
class PurchaseController extends Controller {
    public function pwbillIndex(){
		$us = new UserService();
		
		$this->assign("title", "采购入库");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);

		if ($us->hasPermission(FIdConst::PURCHASE_WAREHOUSE)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
    }
	
	public function pwbillList() {
		if (IS_POST) {
			$ps = new PWBillService();
			$params = array(
				"page" => I("post.page"),
				"start" => I("post.start"),
				"limit" => I("post.limit"),
				"billid" => I("post.pwbillid"),
				"billdate" => I("post.pwbilldate"),

				"begindate" => I("post.begindate"),
				"enddate" => I("post.enddate"),
				"goodsname" => I('post.goodsname'),
				"supplier" => I("post.supplier"),
				"status" => I("post.status"),
				"type"   => I("get.type")
			);
			$this->ajaxReturn($ps->pwbillList($params));
		}
	}
	
	public function pwBillDetailList() {
		if (IS_POST) {
			$pwbillId = I("post.pwBillId");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->pwBillDetailList($pwbillId));
		}
	}
	
	public function editPWBill() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->editPWBill($json));
		}
	}
	
	public function pwBillInfo() {
		if (IS_POST) {
			$id = I("post.id");
			$ia = I("post.ia");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->pwBillInfo($id, $ia));
		}
	}
	
	public function deletePWBill() {
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->deletePWBill($id));
		}
	}
	
	public function commitPWBill() {
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->commitPWBill($id));
		}
	}

	public function verifyPWBill(){
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->verifyPWBill($id));
		}
	}

	public function rejectPWBill(){
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->rejectPWBill($id));
		}
	}

	public function finishPWBill(){
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PWBillService();
			$this->ajaxReturn($ps->finishPWBill($id));
		}
	}

	public function queryData(){
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$queryDate = I("post.queryDate");
			$queryBillStatus = I("post.bill_status");
			$gs = new PWBillService();
			$this->ajaxReturn($gs->queryData($queryKey, $queryDate, $queryBillStatus));
		}
	}
}