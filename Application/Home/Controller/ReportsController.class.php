<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Common\FIdConst;
use Home\Service\SaleReportService;
use Home\Service\InventoryReportService;
use Home\Service\ReceivablesReportService;
use Home\Service\PayablesReportService;
use Home\Service\ReportService;
set_time_limit(0);
/**
 * 报表Controller
 *
 * @author dubin
 *        
 */
class ReportsController extends Controller {

	/**
	 * 进销存报表
	 */
	public function Inout() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::IN_OUT_FORM)) {
			$this->assign("title", "进销存报表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function InoutQueryData(){
		if(IS_POST || IS_GET){
			$report = new ReportService();
			$params = array(
					"begin" => I("request.begin"),
					"end" => I("request.end"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"goods_code" => I("request.goods_code"),
					"supplier_code" => I("request.supplier_code"),
					"cate_code" => I("request.category_code")
			);
			$huizong_type = I("huizong_type");
			if(!$huizong_type){
				$huizong_type = 0;
			}
			if($huizong_type == 0){
				$ret = ($report->inOutDataByGoods($params));
			} else if ($huizong_type == 1){
				$ret = ($report->inOutDataBySupplier($params));
			} else if ($huizong_type == 2){
				$ret = ($report->inOutDataByCate($params));
			} else if ($huizong_type == 3){
				$ret = ($report->inOutDataBySmallCate($params));
			}

			if($_REQUEST["act"] == "export"){
				$list[] = array('商品编码', '商品名称', '所属分类', '期初数量', '期初金额(税)', '期初金额(无税)', 
								'进货数量', '进货金额', '进货金额(无税)', '升溢数量', '升溢金额','升溢金额(无税)',
								 '盘升数量', '盘升金额', '盘升金额(无税)', '销售数量', '销售金额','销售金额(无税)','销售成本','实际成本','实际毛利率',
								'耗损数量', '耗损金额','耗损金额(无税)','盘耗数量','盘耗金额','盘耗金额(无税)','期末数量','期末金额(税)','期末金额(无税)');
				foreach ($ret["dataList"] as $key => $v) {
					$list[] = array($v['goodsCode'],$v['goodsName'],$v['categoryName'],$v['begin_balance_count'],$v['begin_balance_money'],$v['begin_balance_money_no_tax'],
						$v['total_in_count'],$v['total_in_money'],$v['total_in_money_no_tax'],$v['total_in_count_yi'],$v['total_in_money_yi'],$v['total_in_money_yi_no_tax'],
						$v['total_in_count_pan'],$v['total_in_money_pan'],$v['total_in_money_pan_no_tax'],$v['total_out_count_sale'],$v['total_out_money_sale'],$v['total_out_money_sale_without_tax'],$v['total_out_money_sale_cost'],$v['real_total_out_money_sale_profit'],$v['real_total_out_money_sale_profit_percent'],
						$v['total_out_count_sun'],$v['total_out_money_sun'],$v['total_out_money_sun_no_tax'],$v['total_out_count_pan'],$v['total_out_money_pan'],$v['total_out_money_pan_no_tax'],$v['end_balance_count'],$v['end_balance_money'],$v['end_balance_money_no_tax'],
						);
				}
				
			}
			if(I("request.act") == 'export'){	//导出数据
				$change_name = '进销存报表导出数据.csv';
				header('Content-Type: application/vnd.ms-excel');
				header('Content-Disposition: attachment;filename="'.$change_name.'"');
				header('Cache-Control: max-age=0');
				$file = fopen('php://output', 'a');
			}
			if(I("request.act") == 'export'){	//导出数据
				foreach ($list as $k => $line){
					//$list[$k] = iconv('utf-8', 'gbk', $line);
					foreach ($line as $key => $value) {
						$list[$k][$key] = iconv('utf-8', 'gbk', $value);
					}
	  				
	  			}
	  			foreach ($list as $key => $value) {
	  				fputcsv($file,$value);
	  			}
				fclose($file);exit;
			}
			$this->ajaxReturn($ret);
			
		}

	}


	//验收报表
	public function Yanshou(){
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::YAN_SHOU_FORM)) {
			//初始化今日的验收数据
			$sql = "update t_ia_bill_detail i 
inner join t_pr_bill_detail p on i.id = p.pwbilldetail_id 
set i.reject_money = p.rejection_money 
where p.rejection_money > 0 and i.reject_money <= 0";
			M()->execute($sql);
			$sql = "update t_ia_bill_detail i
inner JOIN t_goods g on i.goods_id = g.id
set i.reject_money_no_tax = i.reject_money / (1 + g.buytax/100)
where i.reject_money > 0";
			M()->execute($sql);

			$sql = "update t_ia_bill a 
left join 
(select sum(goods_money) as total_goods_money, sum(goods_money_no_tax) as total_goods_money_no_tax, sum(reject_money) as total_reject_money, sum(reject_money_no_tax) as total_reject_money_no_tax, iabill_id from t_ia_bill_detail group by iabill_id) i 
on a.id = i.iabill_id 
set a.goods_money_no_tax = i.total_goods_money_no_tax, 
a.reject_money = i.total_reject_money, 
a.reject_money_no_tax = i.total_reject_money_no_tax 
where i.total_reject_money > 0 and (ISNULL(a.goods_money_no_tax) or a.reject_money <=0 or ISNULL(a.reject_money) )";
			M()->execute($sql);
			$this->assign("title", "验收报表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function YanshouQueryData(){
		if(IS_POST || IS_GET){
			$report = new ReportService();
			$params = array(
					"begin" => I("request.begin"),
					"end" => I("request.end"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"goods_code" => I("request.goods_code"),
					"supplier_code" => I("request.supplier_code"),
					"cate_code" => I("request.cate_code")
			);
			$huizong_type = I("huizong_type");
			if(!$huizong_type){
				$huizong_type = 0;
			}
			if($huizong_type == 0){
				$this->ajaxReturn($report->yanshouDataByGoods($params));
			} else if ($huizong_type == 1){
				$this->ajaxReturn($report->yanshouDataBySupplier($params));
			} else if ($huizong_type == 2){
				$this->ajaxReturn($report->yanshouDataByCate($params));
			} else if ($huizong_type == 3){
				$this->ajaxReturn($report->yanshouDataBySupplierTax($params));
			} else if ($huizong_type == 4){
				$this->ajaxReturn($report->yanshouDataByGoodsSum($params));
			}
			
		}

	}


	/* 退货报表 */
	/**
	 * (按商品汇总)
	 */
	public function Tuihuo() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::IN_OUT_FORM)) {
			$this->assign("title", "退货报表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function TuihuoQueryData(){
		if(IS_POST || IS_GET){
			$report = new ReportService();
			$params = array(
					"begin" => I("request.begin"),
					"end" => I("request.end"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"goods_code" => I("request.goods_code"),
					"reason" => I("request.reason"),
					"cate_code" => I("request.cate_code")
			);
			$huizong_type = I("huizong_type");
			if(!$huizong_type){
				$huizong_type = 0;
			}
			if($huizong_type == 0){
				$this->ajaxReturn($report->tuihuoDataByGoods($params));
			} else if ($huizong_type == 1){
				$this->ajaxReturn($report->tuihuoDataByReason($params));
			} else if ($huizong_type == 2){
				//$this->ajaxReturn($report->yanshouDataByCate($params));
			} else if ($huizong_type == 3){
				//$this->ajaxReturn($report->yanshouDataBySupplierTax($params));
			} else if ($huizong_type == 4){
				//$this->ajaxReturn($report->yanshouDataByGoodsSum($params));
			}
			
		}

	}

	/* 损溢报表 */
	/**
	 * (按商品汇总)
	 */
	public function Sunyi() {
		$us = new UserService();
		
		if ($us->hasPermission(FIdConst::SUNYI_FORM)) {
			$this->assign("title", "损溢报表");
			$this->assign("uri", __ROOT__ . "/");
			
			$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
			$dtFlag = getdate();
			$this->assign("dtFlag", $dtFlag[0]);
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function SunyiQueryData(){
		if(IS_POST || IS_GET){
			$report = new ReportService();
			$params = array(
					"begin" => I("request.begin"),
					"end" => I("request.end"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit"),
					"goods_code" => I("request.goods_code"),
					"reason" => I("request.reason"),
					"cate_code" => I("request.cate_code")
			);
			$huizong_type = I("huizong_type");
			if(!$huizong_type){
				$huizong_type = 0;
			}
			if($huizong_type == 0){
				$this->ajaxReturn($report->sunyiDataByGoods($params));
			} else if ($huizong_type == 1){
				$this->ajaxReturn($report->sunyiDataByGoods($params));
			} else if ($huizong_type == 2){
				//$this->ajaxReturn($report->yanshouDataByCate($params));
			} else if ($huizong_type == 3){
				//$this->ajaxReturn($report->yanshouDataBySupplierTax($params));
			} else if ($huizong_type == 4){
				//$this->ajaxReturn($report->yanshouDataByGoodsSum($params));
			}
			
		}
	}




}