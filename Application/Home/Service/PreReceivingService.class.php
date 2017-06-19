<?php

namespace Home\Service;

/**
 * 预收款Service
 *
 * @author 李静波
 */
class PreReceivingService extends ERPBaseService {

	public function addPreReceivingInfo() {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$us = new UserService();
		
		return array(
				"bizUserId" => $us->getLoginUserId(),
				"bizUserName" => $us->getLoginUserName()
		);
	}

	public function returnPreReceivingInfo() {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$us = new UserService();
		
		return array(
				"bizUserId" => $us->getLoginUserId(),
				"bizUserName" => $us->getLoginUserName()
		);
	}

	/**
	 * 收预收款
	 */
	public function addPreReceiving($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$customerId = $params["customerId"];
		$bizUserId = $params["bizUserId"];
		$bizDT = $params["bizDT"];
		$inMoney = $params["inMoney"];
		
		$db = M();
		
		// 检查客户
		$cs = new CustomerService();
		if (! $cs->customerExists($customerId, $db)) {
			return $this->bad("客户不存在，无法预收款");
		}
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		// 检查收款人是否存在
		$us = new UserService();
		if (! $us->userExists($bizUserId, $db)) {
			return $this->bad("收款人不存在");
		}
		
		$inMoney = floatval($inMoney);
		if ($inMoney <= 0) {
			return $this->bad("收款金额需要是正数");
		}
		
		$idGen = new IdGenService();
		
		$db->startTrans();
		try {
			$sql = "select in_money, balance_money from t_pre_receiving
					where customer_id = '%s' ";
			$data = $db->query($sql, $customerId);
			if (! $data) {
				// 总账
				$sql = "insert into t_pre_receiving(id, customer_id, in_money, balance_money)
						values ('%s', '%s', %f, %f)";
				$rc = $db->execute($sql, $idGen->newId(), $customerId, $inMoney, $inMoney);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				// 明细账
				$sql = "insert into t_pre_receiving_detail(id, customer_id, in_money, balance_money, date_created,
							ref_number, ref_type, biz_user_id, input_user_id, biz_date)
						values('%s', '%s', %f, %f, now(), '', '收预收款', '%s', '%s', '%s')";
				$rc = $db->execute($sql, $idGen->newId(), $customerId, $inMoney, $inMoney, 
						$bizUserId, $us->getLoginUserId(), $bizDT);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
			} else {
				$totalInMoney = $data[0]["in_money"];
				$totalBalanceMoney = $data[0]["balance_money"];
				if (! $totalInMoney) {
					$totalInMoney = 0;
				}
				if (! $totalBalanceMoney) {
					$totalBalanceMoney = 0;
				}
				
				$totalInMoney += $inMoney;
				$totalBalanceMoney += $inMoney;
				// 总账
				$sql = "update t_pre_receiving
						set in_money = %f, balance_money = %f
						where customer_id = '%s' ";
				$rc = $db->execute($sql, $totalInMoney, $totalBalanceMoney, $customerId);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
				
				// 明细账
				$sql = "insert into t_pre_receiving_detail(id, customer_id, in_money, balance_money, date_created,
							ref_number, ref_type, biz_user_id, input_user_id, biz_date)
						values('%s', '%s', %f, %f, now(), '', '收预收款', '%s', '%s', '%s')";
				$rc = $db->execute($sql, $idGen->newId(), $customerId, $inMoney, $totalBalanceMoney, 
						$bizUserId, $us->getLoginUserId(), $bizDT);
				if (! $rc) {
					$db->rollback();
					return $this->sqlError();
				}
			}
			
			// 记录业务日志
			$bs = new BizlogService();
			$customerName = $cs->getCustomerNameById($customerId, $db);
			$log = "收取客户[{$customerName}]预收款：{$inMoney}元";
			$bs->insertBizlog($log, "预收款管理");
			
			$db->commit();
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->sqlError();
		}
		
		return $this->ok();
	}

	/**
	 * 退还预收款
	 */
	public function returnPreReceiving($params) {
		if ($this->isNotOnline()) {
			return $this->notOnlineError();
		}
		
		$customerId = $params["customerId"];
		$bizUserId = $params["bizUserId"];
		$bizDT = $params["bizDT"];
		$outMoney = $params["outMoney"];
		
		$db = M();
		
		// 检查客户
		$cs = new CustomerService();
		if (! $cs->customerExists($customerId, $db)) {
			return $this->bad("客户不存在，无法预收款");
		}
		
		// 检查业务日期
		if (! $this->dateIsValid($bizDT)) {
			return $this->bad("业务日期不正确");
		}
		
		// 检查收款人是否存在
		$us = new UserService();
		if (! $us->userExists($bizUserId, $db)) {
			return $this->bad("收款人不存在");
		}
		
		$inMoney = floatval($outMoney);
		if ($outMoney <= 0) {
			return $this->bad("收款金额需要是正数");
		}
		
		$customerName = $cs->getCustomerNameById($customerId, $db);
		
		$idGen = new IdGenService();
		
		$db->startTrans();
		try {
			$sql = "select balance_money, out_money from t_pre_receiving where customer_id = '%s' ";
			$data = $db->query($sql, $customerId);
			$balanceMoney = $data[0]["balance_money"];
			if (! $balanceMoney) {
				$balanceMoney = 0;
			}
			
			if ($balanceMoney < $outMoney) {
				$db->rollback();
				return $this->bad(
						"退款金额{$outMoney}元超过余额。<br /><br />客户[{$customerName}]的预付款余额是{$balanceMoney}元");
			}
			$totalOutMoney = $data[0]["out_money"];
			if (! $totalOutMoney) {
				$totalOutMoney = 0;
			}
			
			// 总账
			$sql = "update t_pre_receiving
					set out_money = %f, balance_money = %f
					where customer_id = '%s' ";
			$totalOutMoney += $outMoney;
			$balanceMoney -= $outMoney;
			$rc = $db->execute($sql, $totalOutMoney, $balanceMoney, $customerId);
			if (! $rc) {
				$db->rollback();
				return $this->sqlError();
			}
			
			// 明细账
			$sql = "insert into t_pre_receiving_detail(id, customer_id, out_money, balance_money,
						biz_date, date_created, ref_number, ref_type, biz_user_id, input_user_id)
					values ('%s', '%s', %f, %f, '%s', now(), '', '退预收款', '%s', '%s')";
			$rc = $db->execute($sql, $idGen->newId(), $customerId, $outMoney, $balanceMoney, $bizDT, 
					$bizUserId, $us->getLoginUserId());
			
			// 记录业务日志
			$bs = new BizlogService();
			$log = "退还客户[{$customerName}]预收款：{$outMoney}元";
			$bs->insertBizlog($log, "预收款管理");
				
			$db->commit();
		} catch ( Exception $e ) {
			$db->rollback();
			return $this->sqlError();
		}
		
		return $this->ok();
	}

	public function prereceivingList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$categoryId = $params["categoryId"];
		
		$db = M();
		$sql = "select r.id, c.id as customer_id, c.code, c.name,
					r.in_money, r.out_money, r.balance_money
				from t_pre_receiving r, t_customer c
				where r.customer_id = c.id and c.category_id = '%s'
				limit %d , %d
				";
		$data = $db->query($sql, $categoryId, $start, $limit);
		
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["customerId"] = $v["customer_id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["inMoney"] = $v["in_money"];
			$result[$i]["outMoney"] = $v["out_money"];
			$result[$i]["balanceMoney"] = $v["balance_money"];
		}
		
		$sql = "select count(*) as cnt
				from t_pre_receiving r, t_customer c
				where r.customer_id = c.id and c.category_id = '%s'
				";
		$data = $db->query($sql, $categoryId);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}

	public function prereceivingDetailList($params) {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$customerId = $params["customerId"];
		
		$db = M();
		$sql = "select d.id, d.ref_type, d.ref_number, d.in_money, d.out_money, d.balance_money,
					d.biz_date, d.date_created,
					u1.name as biz_user_name, u2.name as input_user_name
				from t_pre_receiving_detail d, t_user u1, t_user u2
				where d.customer_id = '%s' and d.biz_user_id = u1.id and d.input_user_id = u2.id
				order by d.date_created
				limit %d , %d
				";
		$data = $db->query($sql, $customerId, $start, $limit);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["refType"] = $v["ref_type"];
			$result[$i]["refNumber"] = $v["ref_number"];
			$result[$i]["inMoney"] = $v["in_money"];
			$result[$i]["outMoney"] = $v["out_money"];
			$result[$i]["balanceMoney"] = $v["balance_money"];
			$result[$i]["bizDT"] = $this->toYMD($v["biz_date"]);
			$result[$i]["dateCreated"] = $v["date_created"];
			$result[$i]["bizUserName"] = $v["biz_user_name"];
			$result[$i]["inputUserName"] = $v["input_user_name"];
		}
		
		$sql = "select count(*) as cnt
				from t_pre_receiving_detail d, t_user u1, t_user u2
				where d.customer_id = '%s' and d.biz_user_id = u1.id and d.input_user_id = u2.id
				";
		
		$data = $db->query($sql, $customerId);
		$cnt = $data[0]["cnt"];
		
		return array(
				"dataList" => $result,
				"totalCount" => $cnt
		);
	}
}