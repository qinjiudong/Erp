<?php

namespace Home\Common;

/**
 * FId常数值
 *
 
('1001', '1001', '加工单生成', '加工单生成'),
('1002', '1002', '加工单审核', '加工单审核'),
('1004', '1004', '首页', '首页'),
('2001', '2001', '采购单生成', '采购单生成'),
('2002', '2002', '采购单审核', '采购单审核'),
('2003', '2003', '采购退货', '采购退货'),
('9004', '9004', '业务设置', '业务设置'),
('3001', '3001', '库存查询', '库存查询'),
('3003', '3003', '库存补货', '库存补货'),
('3004', '3004', '库存盘点', '库存盘点'),
('3005', '3005', '库存损溢', '库存损溢'),
('3006', '3006', '验收单', '验收单'),
('3007', '3007', '调拨单', '调拨单'),
('4001', '4001', '销售出库', '销售出库'),
('4002', '4002', '拣货管理', '拣货管理'),
('4003', '4003', '退换货管理', '退换货管理'),
('5001', '5001', '客户档案', '客户档案'),
('6001', '6001', '应收账款管理', '应收账款管理'),
('6002', '6002', '应付账款管理', '应付账款管理'),
('6003', '6003', '现金收支', '现金收支'),
('7001', '7001', '财务报表', '财务报表'),
('7002', '7002', '销售统计', '销售统计'),
('8001', '8001', '商品', '商品'),
('8003', '8003', '仓位管理', '仓位管理'),
('8004', '8004', '供应商档案', '供应商档案');

 * @author JY
 */
class FIdConst {
	
	/**
	 * 首页
	 */
	const HOME = "-9997";
	
	/**
	 * 重新登录
	 */
	const RELOGIN = "-9999";
	
	/**
	 * 修改我的密码
	 */
	const CHANGE_MY_PASSWORD = "-9996";
	
	/**
	 * 使用帮助
	 */
	const HELP = "-9995";
	
	/**
	 * 关于
	 */
	const ABOUT = "-9994";
	
	/**
	 * 购买商业服务
	 */
	const ERP_SERVICE = "-9993";
	
	/**
	 * 用户管理
	 */
	const USR_MANAGEMENT = "-8999";
	
	/**
	 * 权限管理
	 */
	const PERMISSION_MANAGEMENT = "-8996";
	
	/**
	 * 业务日志
	 */
	const BIZ_LOG = "-8997";
	//业务设置
	const BIZ_CONFIG = "9004";
	
	
	//加工单生成
	const PROCESSIONG_SINGLE_CREATE = "1001";
	//加工单审核
	const PROCESSIONG_SINGLE_VERIFY = "1002";

	//首页
	const ERP_INDEX = "1004";
	

	//采购单生成
	const PURCHASE_WAREHOUSE = "2001";
	//采购单审核
	const PURCHASE_VERIFY = "2002";
	//采购退货
	const PURCHASE_REJECTION = "2003";
	
	//库存查询
	const INVENTORY_QUERY = "3001";
	//库存补货
	const INVENTORY_PURCHASE = "3003";
	//库存盘点
	const INVENTORY_CHECK = "3004";
	//库存损溢
	const INVENTORY_LOSS = "3005";
	//验收单
	const INVENTORY_VERIFY = "3006";
	//调拨单
	const INVENTORY_TRANSFER = "3007";
	
	
	//销售出库
	const WAREHOUSING_SALE = "4001";
	//拣货管理
	const WAREHOUSING_PICK = "4002";
	//退换货管理
	const SALE_REJECTION = "4003";
	//补货管理
	const SALE_RE_SALE   = '4004';
	
	
	//客户档案
	const CUSTOMER = "5001";


	//应收账款管理
	const RECEIVING = "6001";
	//应付账款管理
	const PAYABLES = "6002";
	//现金收支
	const CASH_INDEX = "6003";
	/**
	 * 预收款管理
	 */
	const PRE_RECEIVING = "6004";
	
	
	//财务报表
	//const REPORT_FINANCE = "7001";
	//销售统计
	//const REPORT_SALE = "7002";
	
	/**
	 * 销售日报表(按商品汇总)
	 */
	const REPORT_SALE_DAY_BY_GOODS = "7001";
	
	/**
	 * 销售日报表(按客户汇总)
	 */
	const REPORT_SALE_DAY_BY_CUSTOMER = "7002";
	
	/**
	 * 销售日报表(按仓库汇总)
	 */
	const REPORT_SALE_DAY_BY_WAREHOUSE = "7003";
	
	/**
	 * 销售日报表(按业务员汇总)
	 */
	const REPORT_SALE_DAY_BY_BIZUSER = "7004";
	
	/**
	 * 销售月报表(按商品汇总)
	 */
	const REPORT_SALE_MONTH_BY_GOODS = "7005";
	
	/**
	 * 销售月报表(按客户汇总)
	 */
	const REPORT_SALE_MONTH_BY_CUSTOMER = "7006";
	
	/**
	 * 销售月报表(按仓库汇总)
	 */
	const REPORT_SALE_MONTH_BY_WAREHOUSE = "7008";
	
	/**
	 * 销售月报表(按业务员汇总)
	 */
	const REPORT_SALE_MONTH_BY_BIZUSER = "7009";
	
	/**
	 * 安全库存明细表
	 */
	const REPORT_SAFETY_INVENTORY = "7010";
	
	/**
	 * 安全库存调拨表
	 */
	const REPORT_TRANSFER_INVENTORY = "7020";
	
	/**
	 * 应收账款账龄分析表
	 */
	const REPORT_RECEIVABLES_AGE = "7011";
	
	/**
	 * 应付账款账龄分析表
	 */
	const REPORT_PAYABLES_AGE = "7012";
	
	/**
	 * 库存超上限明细表
	 */
	const REPORT_INVENTORY_UPPER = "7013";

	/* 保质期报表 */
	const SHELF_LIFE = "7014";
	
	/* 销售报表- 新增 */
	const SALE_REPORT_FORM = "7015";

	/* 进销存报表 */
	const IN_OUT_FORM = "7016";

	/* 验收报表 */
	const YAN_SHOU_FORM = "7017";

	/* 退货报表 */
	const TUIHUO_FORM = "7018";

 	/* 损溢报表 */
 	const SUNYI_FORM = "7019";
 	
	//商品
	const GOODS = "8001";
	//店铺管理
	const SHOP = "8002";
	//仓位管理
	const POSITION = "8003";
	//供应商档案
	const SUPPLIER = "8004";
	//站点管理
	const SITE = "8005";
	

	/**
	 * 基础数据-仓库
	 */
	const WAREHOUSE = "1003";
	


}
