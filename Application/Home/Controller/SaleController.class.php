<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Common\FIdConst;
use Home\Service\WSBillService;
use Home\Service\SRBillService;
use Home\Service\ApiService;
use Home\Service\MallService;
use Home\Service\PickService;
use Home\Service\AutoService;
/**
 * 销售Controller
 * @author dubin
 *
 */
class SaleController extends Controller {
	//拣货出库
	public function doPick(){
		$params = array(
			
		);
		$items = I('request.items');
		$id = I("request.id");
		$ret = array(
			"success" => false,
			"msg" => ''
		);
		if(!$items){
			$ret["msg"] = "没有选择商品详情";
			$this->ajaxReturn($ret);
		}
		if(!$id){
			$ret["msg"] = "没有选择订单";
			$this->ajaxReturn($ret);
		}
		$items_list = json_decode(html_entity_decode($items), true);
		if(!$items_list){
			$ret["msg"] = "没有详细商品信息";
			$this->ajaxReturn($ret);
		}
		$oos_list = array();
		$success_list = array();
		foreach ($items_list as $key => $value) {
			$goods_code = M("goods", "t_")->where(array("id"=>$value["goodsId"]))->getField("code");
			if(!$value["oos"]){
				$goods = array(
					"goods_code"  => $goods_code,
					"apply_num"   => $value["apply_num"],
					"apply_count" => $value["apply_count"],
					"apply_price" => $value["apply_price"],
				);
				$success_list[] = $goods;
			} else {
				$goods = array(
					"goods_code"  => $goods_code,
					"goods_count"   => $value["goodsCount"],
				);
				$oos_list[] = $goods;
			}
		}
		$params = array(
			"id" => $id,
			"oos" => $oos_list ? 1 : 0,
			"oos_goods_list" => json_encode($oos_list),
			"success_goods_list" => json_encode($success_list)
		);
		$pk = new PickService;
		$this->ajaxReturn($pk->pickReturn($params));
	}

	//批量出库
	public function batchOut(){
		$order_ref  = I("request.order_ref");
		$goods_code = I("request.goods_code");
		$params = array(
			"order_ref" => $order_ref,
			"goods_code" => $goods_code
		);
		$api = new ApiService();
		$this->ajaxReturn($api->batchOutLib($params));
	}

	//补货单提交到待检区（修改状态为0）
	public function toPick(){
		$order_ref  = I("request.id");
		$params = array(
			"order_ref" => $order_ref
		);
		$ws = new WSBillService();
		$this->ajaxReturn($ws->toPick($params));
	}

	//拣货
	public function Pick() {
		$us = new UserService();
		
		$this->assign("title", "拣货管理");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::WAREHOUSING_SALE)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function PickBillList() {
		//if (IS_POST) {
			$params = array(
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit")
			);
			
			$ws = new WSBillService();
			$this->ajaxReturn($ws->pickbillList($params));
		//}
	}

	public function wsIndex() {
		$us = new UserService();
		
		$this->assign("title", "销售出库");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::WAREHOUSING_SALE)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function wsBillInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$ws = new WSBillService();
			$this->ajaxReturn($ws->wsBillInfo($params));
		}
	}

	public function editWSBill() {
		if (IS_POST) {
			$params = array(
					"jsonStr" => I("post.jsonStr")
			);
			$ws = new WSBillService();
			$this->ajaxReturn($ws->editWSBill($params));
		}
	}

	public function editBatchWSBill(){
		if (IS_POST) {
			$params = array(
					"jsonStr" => I("post.jsonStr")
			);
			$ws = new WSBillService();
			$this->ajaxReturn($ws->editBatchWSBill($params));
		}
	}

	public function wsbillList() {
		if (I("request.limit")) {
			$params = array(
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"type" => I("request.type"),
					"startdate" => I("request.startdate"),
					"enddate" => I("request.enddate"),
					"mobile" => I("request.mobile"),
					"username" => I("request.username"),
					"ordertype" => I("request.ordertype"),
					"search_add_time_start" => I("request.search_add_time_start"),
            		"search_add_time_end" => I("request.search_add_time_end"),
            		"search_mall_order_ref"  =>  I("request.search_mall_order_ref"),
            		"search_bill_status"  =>  I("request.search_bill_status"),
            		"search_customer" => I("request.search_customer"),
			);
			$ws = new WSBillService();
			$this->ajaxReturn($ws->wsbillList($params));
		}
	}

	public function wsbillList_for_mall(){
		if (I("request.huizong_type") == 0) {
			$params = array(
					"huizong_type" => I("request.huizong_type"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"type" => I("request.type"),
					"startdate" => I("request.startdate"),
					"enddate" => I("request.enddate"),
					"mobile" => I("request.mobile"),
					"username" => I("request.username"),
					"ordertype" => I("request.ordertype"),
					"search_add_time_start" => I("request.search_add_time_start"),
            		"search_add_time_end" => I("request.search_add_time_end"),
            		"search_mall_order_ref"  =>  I("request.search_mall_order_ref"),
            		"search_bill_status"  =>  I("request.search_bill_status"),
            		"search_customer" => I("request.search_customer"),
            		"siteid" => I("request.siteid"),
            		"delivery_time" => I("request.delivery_time")
			);
			$ws = new WSBillService();
			$this->ajaxReturn($ws->wsbillList_for_mall_total($params));
		} else {
			$params = array(
					"huizong_type" => I("request.huizong_type"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"type" => I("request.type"),
					"startdate" => I("request.startdate"),
					"enddate" => I("request.enddate"),
					"mobile" => I("request.mobile"),
					"username" => I("request.username"),
					"ordertype" => I("request.ordertype"),
					"search_add_time_start" => I("request.search_add_time_start"),
            		"search_add_time_end" => I("request.search_add_time_end"),
            		"search_mall_order_ref"  =>  I("request.search_mall_order_ref"),
            		"search_bill_status"  =>  I("request.search_bill_status"),
            		"search_customer" => I("request.search_customer"),
            		"siteid" => I("request.siteid"),
            		"delivery_time" => I("request.delivery_time")
			);
			$ws = new WSBillService();
			$this->ajaxReturn($ws->wsbillList_for_mall($params));
		}
	}


	public function wsBillDetailList() {
		if (IS_POST) {
			$params = array(
					"billId" => I("post.billId")
			);
			
			$ws = new WSBillService();
			$this->ajaxReturn($ws->wsBillDetailList($params));
		}
	}
    //查询需要打印单据的数据
	public function wsBillDetailListPrint() {
		if (IS_POST) {
			$params = array(
					"billId" => I("post.billId")
			);
			
			$ws = new WSBillService();
			$this->ajaxReturn($ws->wsBillDetailListPrint($params));
		}
	}

	public function deleteWSBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$ws = new WSBillService();
			$this->ajaxReturn($ws->deleteWSBill($params));
		}
	}

	public function deleteMallWSBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$ws = new WSBillService();
			$this->ajaxReturn($ws->deleteMallWSBill($params));
		}
	}

	public function commitWSBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$ws = new WSBillService();
			$this->ajaxReturn($ws->commitWSBill($params));
		}
	}

	public function srIndex() {
		$us = new UserService();
		
		$this->assign("title", "销售退货入库");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::SALE_REJECTION)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function srbillList() {
		if (I("request.limit")) {
			$params = array(
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"reason" => I("request.reason"),
					"begin" => I("request.begin"),
					"end" => I("request.end"),
					"SR_ID" => I("request.SR_ID")
			);
			
			$sr = new SRBillService();
			$this->ajaxReturn($sr->srbillList($params));
		}
	}

	public function srBillDetailList() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.billId")
			);
			
			$sr = new SRBillService();
			$this->ajaxReturn($sr->srBillDetailList($params));
		}
	}

	public function srBillInfo() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$rs = new SRBillService();
			$this->ajaxReturn($rs->srBillInfo($params));
		}
	}

	public function selectWSBillList() {
		if (I("request.limit")) {
			$params = array(
					"ref" => I("request.ref"),
					"customerId" => I("request.customerId"),
					"warehouseId" => I("request.warehouseId"),
					"fromDT" => I("request.fromDT"),
					"toDT" => I("request.toDT"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"refundStatus" => I("request.refundStatus"),
					"mobile" => I("request.mobile")
			);
			
			$rs = new SRBillService();
			$this->ajaxReturn($rs->selectWSBillList($params));
		}
	}

	/**
	 * 新增或者编辑销售退货入库单
	 */
	public function editSRBill() {
		if (IS_POST) {
			$params = array(
					"jsonStr" => I("post.jsonStr")
			);
			
			$rs = new SRBillService();
			$this->ajaxReturn($rs->editSRBill($params));
		}
	}

	//获取用于退货的销售单
	public function getWSBillInfoForSRBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$rs = new SRBillService();
			$this->ajaxReturn($rs->getWSBillInfoForSRBill($params));
		}
	}

	//获取用于补货的销售单
	public function getWSBillInfoForRSBill(){
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$rs = new SRBillService();
			$this->ajaxReturn($rs->getWSBillInfoForRSBill($params));
		}
	}
	/**
	 * 删除销售退货入库单
	 */
	public function deleteSRBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$rs = new SRBillService();
			$this->ajaxReturn($rs->deleteSRBill($params));
		}
	}

	/**
	 * 提交销售退货入库单
	 */
	public function commitSRBill() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			
			$rs = new SRBillService();
			$this->ajaxReturn($rs->commitSRBill($params));
		}
	}

	//补货单列表
	public function reSale(){
		$us = new UserService();
		
		$this->assign("title", "补货订单");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::SALE_RE_SALE)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	//获取站点和区域表
	public function getAllSite(){
		$ws = new WSBillService();
		$this->ajaxReturn($ws->getAllSite());
	}

	//修改站点或者配送区域
	public function editSite(){
		$params = array(
			"siteid" => I("request.siteid"),
			"areaid" => I("request.areaid"),
			"address_detail" => I("request.address_detail"),
			"ref"    => I("request.ref")
		);
		$ws = new WSBillService();
		$this->ajaxReturn($ws->editAllSite($params));
	}
}
