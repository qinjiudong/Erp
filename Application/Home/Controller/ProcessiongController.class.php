<?php
namespace Home\Controller;
use Think\Controller;

use Home\Service\UserService;
use Home\Service\PWBillService;
use Home\Service\PCBillService;
use Home\Common\FIdConst;

/**
 * 加工Controller
 * @author 李静波
 *
 */
class ProcessiongController extends Controller {
    public function SingleCreate(){
		$us = new UserService();
		
		$this->assign("title", "加工单生成");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);

		if ($us->hasPermission(FIdConst::PROCESSIONG_SINGLE_CREATE)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
    }

    public function SingleVerify(){
    	$us = new UserService();
		
		$this->assign("title", "加工审核入库");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);

		if ($us->hasPermission(FIdConst::PROCESSIONG_SINGLE_VERIFY)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
    }
	
	public function pcbillList() {
		if (IS_POST) {
			$ps = new PCBillService();
			$params = array(
				"page" => I("post.page"),
				"start" => I("post.start"),
				"limit" => I("post.limit"),
				"billid" => I("post.pcbillid"),
				"billdate" => I("post.pcbilldate"),
				"type"   => I("get.type")
			);
			$this->ajaxReturn($ps->pcbillList($params));
		}
	}
	
	public function pcBillDetailList() {
		if (IS_POST) {
			$pwbillId = I("post.pcBillId");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->pcBillDetailList($pwbillId));
		}
	}
	
	public function editPCBill() {
		if (IS_POST) {
			$json = I("post.jsonStr");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->editPCBill($json));
		}
	}
	
	public function pcBillInfo() {
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->pcBillInfo($id));
		}
	}
	
	public function deletePCBill() {
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->deletePCBill($id));
		}
	}
	
	public function submitPCBill(){
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->submitPCBill($id));
		}
	}

	public function commitPCBill() {
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->commitPCBill($id));
		}
	}

	public function verifyPCBill(){
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->verifyPCBill($id));
		}
	}

	public function rejectPCBill(){
		if (IS_POST) {
			$id = I("post.id");
			$ps = new PCBillService();
			$this->ajaxReturn($ps->rejectPCBill($id));
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