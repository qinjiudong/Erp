<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\FIdService;
use Home\Service\BizlogService;
use Home\Service\UserService;

use Home\Common\FIdConst;

/**
 * 主菜单Controller
 * @author 李静波
 *
 */
class MainMenuController extends Controller {

	public function navigateTo() {
		$this->assign("uri", __ROOT__ . "/");

		$fid = I("get.fid");

		$fidService = new FIdService();
		$fidService->insertRecentFid($fid);
		$fidName = $fidService->getFIdName($fid);
		if ($fidName) {
			$bizLogService = new BizlogService();
			$bizLogService->insertBizlog("进入模块：" . $fidName);
		}
		//dump($fid);
		//exit;
		if (!$fid) {
			redirect(__ROOT__ . "/Home");
		}
		switch ($fid) {
			case FIdConst::RELOGIN:
				// 重新登录
				$us = new UserService();
				$us->clearLoginUserInSession();
				redirect(__ROOT__ . "/Home");
				break;
			case FIdConst::CHANGE_MY_PASSWORD:
				// 修改我的密码
				redirect(__ROOT__ . "/Home/User/changeMyPassword");
				break;


			case FIdConst::USR_MANAGEMENT:
				// 用户管理
				redirect(__ROOT__ . "/Home/User");
				break;
			case FIdConst::PERMISSION_MANAGEMENT:
				// 权限管理
				redirect(__ROOT__ . "/Home/Permission");
				break;
			case FIdConst::BIZ_LOG:
				// 业务日志
				redirect(__ROOT__ . "/Home/Bizlog");
				break;
			case FIdConst::WAREHOUSE:
				// 基础数据 - 仓库
				redirect(__ROOT__ . "/Home/Warehouse");
				break;

			case FIdConst::CUSTOMER:
				// 客户关系 - 客户资料
				redirect(__ROOT__ . "/Home/Customer");
				break;

			case FIdConst::BIZ_CONFIG:
				// 业务设置
				redirect(__ROOT__ . "/Home/BizConfig");
				break;

				
			case FIdConst::PROCESSIONG_SINGLE_CREATE:
				// 加工单生成
				redirect(__ROOT__ . "/Home/Processiong/SingleCreate");
				break;				
			case FIdConst::PROCESSIONG_SINGLE_VERIFY:
				// 加工单审核
				redirect(__ROOT__ . "/Home/Processiong/SingleVerify");
				break;
				
								
			case FIdConst::ERP_INDEX:
				// 首页
				redirect(__ROOT__ . "/Home");
				break;		
				
						
			case FIdConst::PURCHASE_WAREHOUSE:
				// 采购单生成
				redirect(__ROOT__ . "/Home/Purchase/pwbillIndex");
				break;				
			case FIdConst::PURCHASE_VERIFY:
				// 采购单审核
				redirect(__ROOT__ . "/Home/PurchaseVerify/index");
				break;				
			case FIdConst::PURCHASE_REJECTION:
				// 采购单审核
				redirect(__ROOT__ . "/Home/PurchaseRej/index");
				break;	
				
			case FIdConst::INVENTORY_QUERY:
				// 库存查询
				redirect(__ROOT__ . "/Home/Inventory/inventoryQuery");
				break;						
			//case FIdConst::INVENTORY_TRANSFER:
				// 库间调拨
			//	redirect(__ROOT__ . "/Home/Inventory/InvTransfer");
			//	break;						
			case FIdConst::INVENTORY_PURCHASE:
				// 库存补货
				redirect(__ROOT__ . "/Home/Inventory/Purchase");
				break;				
			case FIdConst::INVENTORY_CHECK:
				// 库存盘点
				redirect(__ROOT__ . "/Home/Inventory/InvCheck");
				break;						
			case FIdConst::INVENTORY_LOSS:
				// 库存损溢
				redirect(__ROOT__ . "/Home/Inventory/InvLoss");
				break;		
			case FIdConst::INVENTORY_VERIFY:
				// 验收单
				redirect(__ROOT__ . "/Home/InvAcceptance");
				break;						
			case FIdConst::INVENTORY_TRANSFER:
				// 调拨单
				redirect(__ROOT__ . "/Home/InvTransfer");
				break;						
				
			case FIdConst::WAREHOUSING_SALE:
				// 销售出库
				redirect(__ROOT__ . "/Home/Sale/wsIndex");
				break;
			case FIdConst::WAREHOUSING_PICK:
				// 拣货管理
				redirect(__ROOT__ . "/Home/sale/Pick");
				break;
			case FIdConst::SALE_REJECTION:
				// 退换货管理
				redirect(__ROOT__ . "/Home/Sale/srIndex");
				break;
			case FIdConst::SALE_RE_SALE:
				// 补货管理
				redirect(__ROOT__ . "/Home/Sale/reSale");
				break;

	
	
			case FIdConst::RECEIVING:
				// 应收账款管理
				redirect(__ROOT__ . "/Home/Funds/rvIndex");
				break;
			case FIdConst::PAYABLES:
				// 应付账款管理
				redirect(__ROOT__ . "/Home/Funds/payIndex");
				break;

			case FIdConst::CASH_INDEX:
				//现金收支
				redirect(__ROOT__ . "/Home/Funds/cashIndex");
				break;
			case FIdConst::PRE_RECEIVING :
				// 预收款管理
				redirect(__ROOT__ . "/Home/Funds/prereceivingIndex");
				break;
				
			case FIdConst::REPORT_SALE_DAY_BY_GOODS :
				// 销售日报表(按商品汇总)
				redirect(__ROOT__ . "/Home/Report/saleDayByGoods");
				break;
			case FIdConst::REPORT_SALE_DAY_BY_CUSTOMER :
				// 销售日报表(按客户汇总)
				redirect(__ROOT__ . "/Home/Report/saleDayByCustomer");
				break;
			case FIdConst::REPORT_SALE_DAY_BY_WAREHOUSE :
				// 销售日报表(按仓库汇总)
				redirect(__ROOT__ . "/Home/Report/saleDayByWarehouse");
				break;
			case FIdConst::REPORT_SALE_DAY_BY_BIZUSER :
				// 销售日报表(按业务员汇总)
				redirect(__ROOT__ . "/Home/Report/saleDayByBizuser");
				break;
			case FIdConst::REPORT_SALE_MONTH_BY_GOODS :
				// 销售月报表(按商品汇总)
				redirect(__ROOT__ . "/Home/Report/saleMonthByGoods");
				break;
			case FIdConst::REPORT_SALE_MONTH_BY_CUSTOMER :
				// 销售月报表(按客户汇总)
				redirect(__ROOT__ . "/Home/Report/saleMonthByCustomer");
				break;
			case FIdConst::REPORT_SALE_MONTH_BY_WAREHOUSE :
				// 销售月报表(按仓库汇总)
				redirect(__ROOT__ . "/Home/Report/saleMonthByWarehouse");
				break;
			case FIdConst::REPORT_SALE_MONTH_BY_BIZUSER :
				// 销售月报表(按业务员汇总)
				redirect(__ROOT__ . "/Home/Report/saleMonthByBizuser");
				break;
			case FIdConst::REPORT_SAFETY_INVENTORY :
				// 安全库存明细表
				redirect(__ROOT__ . "/Home/Report/safetyInventory");
				break;
			case FIdConst::REPORT_TRANSFER_INVENTORY :
				// 安全库存调拨表
				redirect(__ROOT__ . "/Home/Report/transferInventory");
				break;
			case FIdConst::REPORT_RECEIVABLES_AGE :
				// 应收账款账龄分析表
				redirect(__ROOT__ . "/Home/Report/receivablesAge");
				break;
			case FIdConst::REPORT_PAYABLES_AGE :
				// 应付账款账龄分析表
				redirect(__ROOT__ . "/Home/Report/payablesAge");
				break;
			case FIdConst::REPORT_INVENTORY_UPPER :
				// 库存超上限明细表
				redirect(__ROOT__ . "/Home/Report/inventoryUpper");
				break;
			case FIdConst::SHELF_LIFE :
				// 保质期表
				redirect(__ROOT__ . "/Home/Report/life");
				break;
			case FIdConst::SALE_REPORT_FORM :
				// 普通销售报表
				redirect(__ROOT__ . "/Home/Report/saleReportByGoods");
				break;
			case FIdConst::IN_OUT_FORM :
				// 进销存报表
				redirect(__ROOT__ . "/Home/Reports/Inout");
				break;
			case FIdConst::YAN_SHOU_FORM :
				// 验收报表
				redirect(__ROOT__ . "/Home/Reports/Yanshou");
				break;
			case FIdConst::TUIHUO_FORM :
				// 退货报表
				redirect(__ROOT__ . "/Home/Reports/Tuihuo");
				break;	
			case FIdConst::SUNYI_FORM :
				// 损溢报表
				redirect(__ROOT__ . "/Home/Reports/Sunyi");
				break;	
			case FIdConst::GOODS:
				// 商品
				redirect(__ROOT__ . "/Home/Goods");
				break;
			case FIdConst::POSITION:
				// 仓位管理
				redirect(__ROOT__ . "/Home/Position");
				break;
			case FIdConst::SHOP:
				// 店铺管理
				redirect(__ROOT__ . "/Home/Shop");
				break;
			case FIdConst::SUPPLIER:
				// 供应商档案
				redirect(__ROOT__ . "/Home/Supplier");
				break;
			case FIdConst::SITE:
				// 站点管理
				redirect(__ROOT__ . "/Home/Site");
				break;

			default:
				redirect(__ROOT__ . "/Home");
		}
	}

	/**
	 * 返回生成主菜单的JSON数据
	 * 目前只能处理到生成三级菜单的情况
	 */
	public function mainMenuItems() {
		if (IS_POST) {
			$us = new UserService();

			$sql = "select id, caption, fid from t_menu_item 
					where parent_id is null order by show_order";
			$db = M();
			$m1 = $db->query($sql);
			$result = array();

			$index1 = 0;
			foreach ($m1 as $menuItem1) {

				$children1 = array();

				$sql = "select id, caption, fid from t_menu_item "
						. " where parent_id = '%s' order by show_order ";
				$m2 = $db->query($sql, $menuItem1["id"]);

				// 第二级菜单
				$index2 = 0;
				foreach ($m2 as $menuItem2) {
					$children2 = array();
					$sql = "select id, caption, fid from t_menu_item "
							. " where parent_id = '%s' order by show_order ";
					$m3 = $db->query($sql, $menuItem2["id"]);

					// 第三级菜单
					$index3 = 0;
					foreach ($m3 as $menuItem3) {
						if ($us->hasPermission($menuItem3["fid"])) {
							$children2[$index3]["id"] = $menuItem3["id"];
							$children2[$index3]["caption"] = $menuItem3["caption"];
							$children2[$index3]["fid"] = $menuItem3["fid"];
							$children2[$index3]["children"] = array();
							$index3++;
						}
					}

					if ($us->hasPermission($menuItem2["fid"])) {
						$children1[$index2]["id"] = $menuItem2["id"];
						$children1[$index2]["caption"] = $menuItem2["caption"];
						$children1[$index2]["fid"] = $menuItem2["fid"];
						$children1[$index2]["children"] = $children2;
						$index2++;
					}
				}

				if (count($children1) > 0) {
					$result[$index1] = $menuItem1;
					$result[$index1]["children"] = $children1;
					$index1++;
				}
			}

			$this->ajaxReturn($result);
		}
	}

	/**
	 * 常用功能
	 */
	public function recentFid() {
		if (IS_POST) {
			$fidService = new FIdService();
			$data = $fidService->recentFid();

			$this->ajaxReturn($data);
		}
	}
}
