<?php

namespace Home\Service;

use Home\Service\MallService;
/**
 * 应付账款Service
 *
 * @author 李静波
 */
class PayablesService extends ERPBaseService {

	public function payCategoryList($params) {
		$id = $params["id"];
		if ($id == "supplier") {
			return M()->query("select id,  code, name from t_supplier_category order by code");
		} else {
			return M()->query("select id,  code, name from t_customer_category order by code");
		}
	}

	public function payList($params) {
		$caType = $params["caType"];
		$categoryId = $params["categoryId"];
		$mobile = $params["mobile"];
		$ref    = $params["ref"];
		$start_date   = $params["start_date"];
		$end_date   = $params["end_date"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		if ($caType == "supplier") {

			$where = "";
			if($mobile){
				$where .= " and (s.mobile01 = '$mobile' or s.name='$mobile') ";
			}
			if($ref){
				$where .= " and p.ca_id in (select ca_id from t_payables_detail where ca_type='supplier' and ref_number='$ref')";
				//$where = " and p.ca_id in (select customer_id from t_ws_bill where ref = '$ref' )";
				
			}
			if($start_date && $end_date){
				$where .= " and p.ca_id in (select ca_id from t_payables_detail where ca_type='supplier' and biz_date >= '$start_date' and biz_date <= '$end_date')";
			}

			$sql = "select p.id, p.pay_money, p.act_money, p.balance_money, s.id as ca_id, s.code, s.name 
					from t_payables p, t_supplier s 
					where p.ca_id = s.id and p.ca_type = 'supplier' and s.category_id = '%s' $where 
					order by s.code 
					limit " . $start . ", " . $limit;
			$data = $db->query($sql, $categoryId);
			$result = array();
			foreach ( $data as $i => $v ) {
				$result[$i]["id"] = $v["id"];
				$result[$i]["caId"] = $v["ca_id"];
				$result[$i]["code"] = $v["code"];
				$result[$i]["name"] = $v["name"];
				$result[$i]["payMoney"] = $v["pay_money"];
				$result[$i]["actMoney"] = $v["act_money"];
				$result[$i]["balanceMoney"] = $v["balance_money"];
			}
			
			$sql = "select count(*) as cnt from t_payables p, t_supplier s 
					where p.ca_id = s.id and p.ca_type = 'supplier' and s.category_id = '%s' ";
			$data = $db->query($sql, $categoryId);
			$cnt = $data[0]["cnt"];
			
			return array(
					"dataList" => $result,
					"totalCount" => $cnt
			);
		} else {
			$where = "";
			if($mobile){
				$where .= " and (s.mobile01 = '$mobile' or s.name='$mobile') ";
			}
			if($ref){
				$where .= " and ( p.ca_id in (select ca_id from t_payables_detail where ca_type='customer' and ref_number='$ref' )
						or p.ca_id in (select customer_id from t_ws_bill where ref = '$ref' ) )";
				//$where = " and p.ca_id in (select customer_id from t_ws_bill where ref = '$ref' )";
				
			}
			if($start_date && $end_date){
				$where .= " and p.ca_id in (select ca_id from t_payables_detail where ca_type='customer' and biz_date >= '$start_date' and biz_date <= '$end_date')";
			}

			$sql = "select p.id, p.pay_money, p.act_money, p.balance_money, s.id as ca_id, s.code, s.name, s.mobile01 
					from t_payables p, t_customer s 
					where p.ca_id = s.id and p.ca_type = 'customer' and s.category_id = '%s' $where 
					order by s.code 
					limit " . $start . ", " . $limit;
			$data = $db->query($sql, $categoryId);
			$result = array();
			foreach ( $data as $i => $v ) {
				$result[$i]["id"] = $v["id"];
				$result[$i]["caId"] = $v["ca_id"];
				$result[$i]["code"] = $v["code"];
				$result[$i]["name"] = $v["name"];
				$result[$i]["mobile"] = $v["mobile01"];
				$result[$i]["payMoney"] = $v["pay_money"];
				$result[$i]["actMoney"] = $v["act_money"];
				$result[$i]["balanceMoney"] = $v["balance_money"];
			}
			
			$sql = "select count(*) as cnt from t_payables p, t_customer s 
					where p.ca_id = s.id and p.ca_type = 'customer' and s.category_id = '%s' ";
			$data = $db->query($sql, $categoryId);
			$cnt = $data[0]["cnt"];
			
			return array(
					"dataList" => $result,
					"totalCount" => $cnt
			);
		}
	}

	public function payDetailList($params) {
		$caType = $params["caType"];
		$caId = $params["caId"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$start_date   = $params["start_date"];
		$end_date   = $params["end_date"];
		$db = M();
		$where = "";
		if($start_date && $end_date){
			$where .= " and biz_date >= '$start_date' and biz_date <= '$end_date'";
		}
		$sql = "select id, ref_type, ref_number, pay_money, act_money, balance_money, date_created, biz_date 
				from t_payables_detail 
				where ca_type = '%s' and ca_id = '%s' $where order by biz_date desc
				limit " . $start . ", " . $limit;
		$data = $db->query($sql, $caType, $caId);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["refType"] = $v["ref_type"];
			$result[$i]["refNumber"] = $v["ref_number"];
			$result[$i]["bizDT"] = date("Y-m-d", strtotime($v["biz_date"]));
			$result[$i]["dateCreated"] = $v["date_created"];
			$result[$i]["payMoney"] = $v["pay_money"];
			$result[$i]["actMoney"] = $v["act_money"];
			$result[$i]["balanceMoney"] = $v["balance_money"];
		}
		
		$sql = "select count(*) as cnt from t_payables_detail 
				where ca_type = '%s' and ca_id = '%s' ";
		$data = $db->query($sql, $caType, $caId);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}
	public function payDetailListAll($params) {
		$caType = $params["caType"];
		$caId = $params["caId"];
		
		$db = M();
		
		$sql = "select id, ref_type, ref_number, pay_money, act_money, balance_money, date_created, biz_date 
				from t_payables_detail 
				where ca_type = '%s' and ca_id = '%s' ";
		$data = $db->query($sql, $caType, $caId);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["refType"] = $v["ref_type"];
			$result[$i]["refNumber"] = $v["ref_number"];
			$result[$i]["bizDT"] = date("Y-m-d", strtotime($v["biz_date"]));
			$result[$i]["dateCreated"] = $v["date_created"];
			$result[$i]["payMoney"] = $v["pay_money"];
			$result[$i]["actMoney"] = $v["act_money"];
			$result[$i]["balanceMoney"] = $v["balance_money"];
		}
		
		$sql = "select count(*) as cnt from t_payables_detail 
				where ca_type = '%s' and ca_id = '%s' ";
		$data = $db->query($sql, $caType, $caId);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function payRecordList($params) {
		$refType = $params["refType"];
		$refNumber = $params["refNumber"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		
		$sql = "select u.name as biz_user_name, bu.name as input_user_name, p.id, 
				p.act_money, p.biz_date, p.date_created, p.remark 
				from t_payment p, t_user u, t_user bu 
				where p.ref_type = '%s' and p.ref_number = '%s' 
				and  p.pay_user_id = u.id and p.input_user_id = bu.id 
				limit " . $start . ", " . $limit;
		$data = $db->query($sql, $refType, $refNumber);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["actMoney"] = $v["act_money"];
			$result[$i]["bizDate"] = date("Y-m-d", strtotime($v["biz_date"]));
			$result[$i]["dateCreated"] = date("Y-m-d", strtotime($v["date_created"]));
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
			$result[$i]["remark"] = $v["remark"];
		}
		
		$sql = "select count(*) as cnt from t_payment 
				where ref_type = '%s' and ref_number = '%s' ";
		$data = $db->query($sql, $refType, $refNumber);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => 0
		);
	}

	public function addPayment($params) {
		$refType = $params["refType"];
		$refNumber = $params["refNumber"];
		$bizDT = $params["bizDT"];
		$actMoney = $params["actMoney"];
		$bizUserId = $params["bizUserId"];
		$remark = $params["remark"];
		if (! $remark) {
			$remark = "";
		}
		
		$db = M();
		$billId = "";
		if ($refType == "采购入库") {
			$sql = "select id from t_pw_bill where ref = '%s' ";
			$data = $db->query($sql, $refNumber);
			if (! $data) {
				return $this->bad("单号为 {$refNumber} 的采购入库不存在，无法付款");
			}
			$billId = $data[0]["id"];
		}
		
		$db->startTrans();
		try {
			$sql = "insert into t_payment (id, act_money, biz_date, date_created, input_user_id,
					pay_user_id,  bill_id,  ref_type, ref_number, remark) 
					values ('%s', %f, '%s', now(), '%s', '%s', '%s', '%s', '%s', '%s')";
			$idGen = new IdGenService();
			$us = new UserService();
			$db->execute($sql, $idGen->newId(), $actMoney, $bizDT, $us->getLoginUserId(), $bizUserId, $billId, $refType, $refNumber, $remark);
			//如果是退货单，则需要通知卡券系统退款
			if($refType == "销售退货入库"){
				$ms = new MallService();
				$ret = $ms->money($refNumber, "refund", $actMoney);
				if($ret["success"] == false){
					$db->rollback();
					return $ret;
				}
			}
			$log = "为 {$refType} - 单号：{$refNumber} 付款：{$actMoney}元";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "应付账款管理");
			
			// 应付明细账
			$sql = "select balance_money, act_money, ca_type, ca_id 
					from t_payables_detail 
					where ref_type = '%s' and ref_number = '%s' ";
			$data = $db->query($sql, $refType, $refNumber);
			$caType = $data[0]["ca_type"];
			$caId = $data[0]["ca_id"];
			$balanceMoney = $data[0]["balance_money"];
			$actMoneyNew = $data[0]["act_money"];
			$actMoneyNew += $actMoney;
			$balanceMoney -= $actMoney;
			$sql = "update t_payables_detail 
					set act_money = %f, balance_money = %f 
					where ref_type = '%s' and ref_number = '%s' 
					and ca_id = '%s' and ca_type = '%s' ";
			$db->execute($sql, $actMoneyNew, $balanceMoney, $refType, $refNumber, $caId, $caType);
			
			// 应付总账
			$sql = "select balance_money, act_money 
					from t_payables 
					where ca_type = '%s' and ca_id = '%s' ";
			$data = $db->query($sql, $caType, $caId);
			$balanceMoneyTotal = $data[0]["balance_money"];
			$actMoneyTotal = $data[0]["act_money"];
			$actMoneyTotal += $actMoney;
			$balanceMoneyTotal -= $actMoney;
			$sql = "update t_payables 
					set act_money = %f, balance_money = %f 
					where ca_type = '%s' and ca_id = '%s' ";
			$db->execute($sql, $actMoneyTotal, $balanceMoneyTotal, $caType, $caId);
			
			$db->commit();
		} catch ( Exception $ex ) {
			$db->rollback();
			return $this->bad("数据库错误，请联系管理员");
		}
		
		return $this->ok();
	}

	public function refreshPayInfo($params) {
		$id = $params["id"];
		$data = M()->query("select act_money, balance_money from t_payables  where id = '%s' ", $id);
		return array(
				"actMoney" => $data[0]["act_money"],
				"balanceMoney" => $data[0]["balance_money"]
		);
	}

	public function refreshPayDetailInfo($params) {
		$id = $params["id"];
		$data = M()->query("select act_money, balance_money from t_payables_detail  where id = '%s' ", $id);
		return array(
				"actMoney" => $data[0]["act_money"],
				"balanceMoney" => $data[0]["balance_money"]
		);
	}
}