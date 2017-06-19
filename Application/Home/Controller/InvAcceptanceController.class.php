<?php
namespace Home\Controller;
use Think\Controller;

use Home\Service\UserService;
use Home\Service\IABillService;
use Home\Common\FIdConst;

/**
 * 验收Controller
 * @author dubin
 *
 */
class InvAcceptanceController extends Controller {

	public function index(){
		$us = new UserService();
		
		$this->assign("title", "验收入库");
		$this->assign("uri", __ROOT__ . "/");
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);

		if ($us->hasPermission(FIdConst::INVENTORY_VERIFY)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function iabillList() {
		if (IS_POST || IS_GET) {
			$ps = new IABillService();
			$params = array(
				"page" => I("request.page"),
				"start" => I("request.start"),
				"limit" => I("request.limit"),
				"billid" => I("request.iabillid"),
				"begindate" => I("request.begindate"),
				"enddate" => I("request.enddate"),
				"supplier" => I("request.supplier"),
				"type"   => I("request.type"),
				"auto" => I("request.auto"),
				"editStatus" => I("request.editStatus"),
				"goods_code" => I("request.goods_code")
			);
			$this->ajaxReturn($ps->iabillList($params));
		}
	}

	public function iaBillDetailList() {
		if (IS_POST || IS_GET) {
			$pwbillId = I("request.pwBillId");
			$ps = new IABillService();
			$this->ajaxReturn($ps->iaBillDetailList($pwbillId));
		}
	}

	public function commitIABill(){
		if (IS_POST){
//			$json = I("post.jsonStr");
			$id = I("post.id");
			$ps = new IABillService();
			$this->ajaxReturn($ps->commitIABill($id));
		}
	}

	public function shoukuanIABill(){
		if (IS_POST){
//			$json = I("post.jsonStr");
			$id = I("post.id");
			$ps = new IABillService();
			$this->ajaxReturn($ps->shoukuanIABill($id));
		}
	}

	public function querenIABill(){
		if (IS_POST){
//			$json = I("post.jsonStr");
			$id = I("post.id");
			$ps = new IABillService();
			$this->ajaxReturn($ps->querenIABill($id));
		}
	}
	public function editIABill(){
		if (IS_POST){
			$json = I("post.jsonStr");
			$ps = new IABillService();
			$this->ajaxReturn($ps->editIABill($json));
		}
	}
	
	public function iaBillInfo() {
		if (IS_POST) {
			$id = I("post.id");
			$ps = new IABillService();
			$this->ajaxReturn($ps->iaBillInfo($id));
		}
	}
}