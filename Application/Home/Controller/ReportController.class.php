<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Common\FIdConst;
use Home\Service\SaleReportService;
use Home\Service\InventoryReportService;
use Home\Service\ReceivablesReportService;
use Home\Service\PayablesReportService;
set_time_limit(0);
/**
 * 报表Controller
 *
 * @author 李静波
 *        
 */
class ReportController extends Controller {

	/**
	 * 销售日报表(按商品汇总)
	 */
	public function saleDayByGoods() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_DAY_BY_GOODS)) {
			$this->assign("title", "销售日报表(按商品汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售日报表(按商品汇总) - 查询数据
	 */
	public function saleDayByGoodsQueryData() {
		if (IS_POST || IS_GET) {
			$params = array(
					"dt" => I("request.dt"),
					"goods_code" => I("request.goods_code"),
					"goods_id" => I("request.goods_id"),
					"supplier_code" => I("request.supplier_code"),
					"category_code" => I("request.category_code"),
					"huizong_type" => I("request.huizong_type"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByGoodsQueryData($params));
		}
	}

	/**
	 * 销售日报表(按商品汇总) - 查询汇总数据
	 */
	public function saleDayByGoodsSummaryQueryData() {
		if (IS_POST || IS_GET) {
			$params = array(
					"dt" => I("request.dt"),
					"goods_code" => I("request.goods_code"),
					"goods_id" => I("request.goods_id"),
					"supplier_code" => I("request.supplier_code"),
					"huizong_type" => I("request.huizong_type"),
					"category_code" => I("request.category_code"),
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByGoodsSummaryQueryData($params));
		}
	}

	/**
	 * 销售日报表(按客户汇总)
	 */
	public function saleDayByCustomer() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_DAY_BY_CUSTOMER)) {
			$this->assign("title", "销售日报表(按客户汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售日报表(按客户汇总) - 查询数据
	 */
	public function saleDayByCustomerQueryData() {
		if (IS_POST) {
			$params = array(
					"dt" => I("post.dt"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByCustomerQueryData($params));
		}
	}

	/**
	 * 销售日报表(按客户汇总) - 查询汇总数据
	 */
	public function saleDayByCustomerSummaryQueryData() {
		if (IS_POST) {
			$params = array(
					"dt" => I("post.dt")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByCustomerSummaryQueryData($params));
		}
	}

	/**
	 * 销售日报表(按仓库汇总)
	 */
	public function saleDayByWarehouse() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_DAY_BY_WAREHOUSE)) {
			$this->assign("title", "销售日报表(按仓库汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售日报表(按仓库汇总) - 查询数据
	 */
	public function saleDayByWarehouseQueryData() {
		if (IS_POST) {
			$params = array(
					"dt" => I("post.dt"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByWarehouseQueryData($params));
		}
	}

	/**
	 * 销售日报表(按仓库汇总) - 查询汇总数据
	 */
	public function saleDayByWarehouseSummaryQueryData() {
		if (IS_POST) {
			$params = array(
					"dt" => I("post.dt")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByWarehouseSummaryQueryData($params));
		}
	}

	/**
	 * 销售日报表(按业务员汇总)
	 */
	public function saleDayByBizuser() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_DAY_BY_BIZUSER)) {
			$this->assign("title", "销售日报表(按业务员汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售日报表(按业务员汇总) - 查询数据
	 */
	public function saleDayByBizuserQueryData() {
		if (IS_POST) {
			$params = array(
					"dt" => I("post.dt"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByBizuserQueryData($params));
		}
	}

	/**
	 * 销售日报表(按业务员汇总) - 查询汇总数据
	 */
	public function saleDayByBizuserSummaryQueryData() {
		if (IS_POST) {
			$params = array(
					"dt" => I("post.dt")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleDayByBizuserSummaryQueryData($params));
		}
	}

	/**
	 * 销售月报表(按商品汇总)
	 */
	public function saleMonthByGoods() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_MONTH_BY_GOODS)) {
			$this->assign("title", "销售月报表(按商品汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售月报表(按商品汇总) - 查询数据
	 */
	public function saleMonthByGoodsQueryData() {
		if (IS_POST || IS_GET) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month"),
					"goods_code" => I("post.goods_code"),
					"supplier_code" => I("request.supplier_code"),
					"category_code" => I("request.category_code"),
					"goods_id" => I("post.goods_id"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit"),
					"huizong_type" => I("request.huizong_type"),
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByGoodsQueryData($params));
		}
	}

	/**
	 * 销售月报表(按商品汇总) - 查询汇总数据
	 */
	public function saleMonthByGoodsSummaryQueryData() {
		if (IS_POST || IS_GET) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month"),
					"goods_code" => I("post.goods_code"),
					"goods_id" => I("post.goods_id"),
					"huizong_type" => I("request.huizong_type"),
					"category_code" => I("request.category_code"),
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByGoodsSummaryQueryData($params));
		}
	}

	/**
	 * 销售月报表(按客户汇总)
	 */
	public function saleMonthByCustomer() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_MONTH_BY_CUSTOMER)) {
			$this->assign("title", "销售月报表(按客户汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售月报表(按客户汇总) - 查询数据
	 */
	public function saleMonthByCustomerQueryData() {
		if (IS_POST) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByCustomerQueryData($params));
		}
	}

	/**
	 * 销售月报表(按客户汇总) - 查询汇总数据
	 */
	public function saleMonthByCustomerSummaryQueryData() {
		if (IS_POST) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByCustomerSummaryQueryData($params));
		}
	}

	/**
	 * 销售月报表(按仓库汇总)
	 */
	public function saleMonthByWarehouse() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_MONTH_BY_WAREHOUSE)) {
			$this->assign("title", "销售月报表(按仓库汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售月报表(按仓库汇总) - 查询数据
	 */
	public function saleMonthByWarehouseQueryData() {
		if (IS_POST) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByWarehouseQueryData($params));
		}
	}

	/**
	 * 销售月报表(按仓库汇总) - 查询汇总数据
	 */
	public function saleMonthByWarehouseSummaryQueryData() {
		if (IS_POST) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByWarehouseSummaryQueryData($params));
		}
	}

	/**
	 * 销售月报表(按业务员汇总)
	 */
	public function saleMonthByBizuser() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SALE_MONTH_BY_BIZUSER)) {
			$this->assign("title", "销售月报表(按业务员汇总)");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售月报表(按业务员汇总) - 查询数据
	 */
	public function saleMonthByBizuserQueryData() {
		if (IS_POST) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByBizuserQueryData($params));
		}
	}

	/**
	 * 销售月报表(按业务员汇总) - 查询汇总数据
	 */
	public function saleMonthByBizuserSummaryQueryData() {
		if (IS_POST) {
			$params = array(
					"year" => I("post.year"),
					"month" => I("post.month")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleMonthByBizuserSummaryQueryData($params));
		}
	}

	/**
	 * 安全库存明细表
	 */
	public function safetyInventory() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_SAFETY_INVENTORY)) {
			$this->assign("title", "安全库存明细表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 安全库存明细表 - 查询数据
	 */
	public function safetyInventoryQueryData() {
		if (IS_POST || IS_GET) {
			$params = array(
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"goods_code" => I("request.goods_code"),
					"supplier_code" => I("request.supplier_code"),
					"cate_code" => I("request.cate_code"),
					"begin" => I("request.begin"),
					"end" => I("request.end")
			);
			
			$is = new InventoryReportService();
			
			$this->ajaxReturn($is->safetyInventoryQueryData($params));
		}
	}

	/**
	 * 安全库存明细表
	 */
	public function transferInventory() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_TRANSFER_INVENTORY)) {
			$this->assign("title", "库存调拨明细表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 安全库存明细表 - 查询数据
	 */
	public function transferInventoryQueryData() {
		if (IS_POST || IS_GET) {
			$params = array(
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"goods_code" => I("request.goods_code"),
					"out_shop" => I("request.out_shop"),
					"in_shop" => I("request.in_shop"),
					"begin" => I("request.begin"),
					"end" => I("request.end")
			);
			
			$is = new InventoryReportService();
			
			$this->ajaxReturn($is->transferInventoryQueryData($params));
		}
	}

	/**
	 * 应收账款账龄分析表
	 */
	public function receivablesAge() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_RECEIVABLES_AGE)) {
			$this->assign("title", "应收账款账龄分析表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 应收账款账龄分析表 - 数据查询
	 */
	public function receivablesAgeQueryData() {
		if (IS_POST) {
			$params = array(
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new ReceivablesReportService();
			
			$this->ajaxReturn($rs->receivablesAgeQueryData($params));
		}
	}

	/**
	 * 应收账款账龄分析表 - 当期汇总数据查询
	 */
	public function receivablesSummaryQueryData() {
		if (IS_POST) {
			$rs = new ReceivablesReportService();
			
			$this->ajaxReturn($rs->receivablesSummaryQueryData());
		}
	}

	/**
	 * 应付账款账龄分析表
	 */
	public function payablesAge() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_PAYABLES_AGE)) {
			$this->assign("title", "应付账款账龄分析表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 应付账款账龄分析表 - 数据查询
	 */
	public function payablesAgeQueryData() {
		if (IS_POST) {
			$params = array(
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$ps = new PayablesReportService();
			
			$this->ajaxReturn($ps->payablesAgeQueryData($params));
		}
	}

	/**
	 * 应付账款账龄分析表 - 当期汇总数据查询
	 */
	public function payablesSummaryQueryData() {
		if (IS_POST) {
			$ps = new PayablesReportService();
			
			$this->ajaxReturn($ps->payablesSummaryQueryData());
		}
	}

	/**
	 * 库存超上限明细表
	 */
	public function inventoryUpper() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::REPORT_INVENTORY_UPPER)) {
			$this->assign("title", "库存超上限明细表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 库存超上限明细表 - 查询数据
	 */
	public function inventoryUpperQueryData() {
		if (IS_POST || IS_GET) {
			$params = array(
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"goods_code" => I("request.goods_code"),
					"supplier_code" => I("request.supplier_code"),
					"cate_code" => I("request.cate_code"),
					"begin" => I("request.begin"),
					"end" => I("request.end"),
					"bill_status" => I("request.bill_status"),
					"bill_ref" => I("request.bill_ref")
			);
			
			$is = new InventoryReportService();
			
			$this->ajaxReturn($is->inventoryUpperQueryData($params));
		}
	}

	public function life(){
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SHELF_LIFE)) {
			$this->assign("title", "保质期报表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}
	//获取保质期1/3内没有销售的商品
	public function lifeDataQuery(){
		$day = intval($_POST["day"]);
		if(!$day){
			$day = 5;
		}
		$time = time();
		$page = intval(I("request.page")) ? intval(I("request.page")) : 1;
		$limit = intval(I("request.limit")) ? intval(I("request.limit")) : 20;
		$start = ($page - 1) * $limit;
		$sql = "select g.*, i.balance_count from t_goods g, t_inventory i where g.id = i.goods_id and g.is_delete = 0 and g.life > 10 and (g.lastsale + (g.life/3)*24*3600) < $time limit $start, $limit";
		$data = M()->query($sql);
		$result = array();
		foreach ($data as $key => $value) {
			$result[$key]["goodsCode"] = $value["code"];
			$result[$key]["goodsName"] = $value["name"];
			$result[$key]["goodsUnit"] = $value["unit_id"];
			$result[$key]["goodsBar"]  = $value["barcode"];
			$result[$key]["goodsSpec"]  = $value["spec"];
			$result[$key]["goodsLife"]  = $value["life"];
			$result[$key]["goodsLastSaleTime"]  = $value["lastsale"] ? date("Y-m-d", $value["lastsale"]) :"从未卖过";
			$result[$key]["balanceCount"] = $value["balance_count"];
			$result[$key]["status_str"] = $value["status"] == 1 ? "是":"否";
		}
		$sql = "select count(*) as cnt from t_goods where life > 10 and (lastsale + (life/3)*24*3600) < $time";
		$r = M()->query($sql);
		$count = $r[0]['cnt'];
		$ret = array(
			"dataList" => $result ,
			"totalCount" => $count
		);
		$this->ajaxReturn($ret);
	}

	public function saleReportByGoods() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SALE_REPORT_FORM)) {
			$this->assign("title", "商品销售报表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	/**
	 * 销售月报表(按商品汇总) - 查询数据
	 */
	public function saleReportByGoodsQueryData() {
		if (IS_POST) {
			set_time_limit(100);
			$params = array(
					"begin" => I("post.begin"),
					"end" => I("post.end"),
					"goods_code" => I("post.goods_code"),
					"goods_id" => I("post.goods_id"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleReportByGoodsQueryData($params));
		}
	}

	/**
	 * 销售月报表(按商品汇总) - 查询汇总数据
	 */
	public function saleReportByGoodsSummaryQueryData() {
		if (IS_POST) {
			set_time_limit(100);
			$params = array(
					"begin" => I("post.begin"),
					"end" => I("post.end"),
					"goods_code" => I("post.goods_code"),
					"goods_id" => I("post.goods_id"),
			);
			
			$rs = new SaleReportService();
			
			$this->ajaxReturn($rs->saleReportByGoodsSummaryQueryData($params));
		}
	}
}