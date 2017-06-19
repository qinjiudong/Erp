<?php

namespace Home\Service;

/**
 * Portal Service
 *
 * @author 李静波
 */
class PortalService extends ERPBaseService {

	public function inventoryPortal() {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$result = array();
		
		$db = M();
		$sql = "select id, name 
				from t_warehouse 
				where inited = 1
				order by code";
		$data = $db->query($sql);
		foreach ( $data as $i => $v ) {
			$result[$i]["warehouseName"] = $v["name"];
			$warehouseId = $v["id"];
			
			// 库存金额
			$sql = "select sum(i.balance_count * g.lastbuyprice) as balance_money 
					from t_inventory i, t_goods g
					where i.goods_id = g.id and warehouse_id = '%s' ";
			$d = $db->query($sql, $warehouseId);
			if ($d) {
				$m = $d[0]["balance_money"];
				$result[$i]["inventoryMoney"] = $m ? $m : 0;
			} else {
				$result[$i]["inventoryMoney"] = 0;
			}
			// 低于安全库存数量的商品种类
			$sql = "select count(*) as cnt
					from t_inventory i, t_goods g 
					where i.goods_id = g.id and g.mode = 0 and i.balance_count < 10";
			$d = $db->query($sql, $warehouseId);
			$result[$i]["siCount"] = $d[0]["cnt"];
			
			// 超过库存上限的商品种类
			$sql = "select count(*) as cnt
					from t_inventory i, t_goods_si s
					where i.goods_id = s.goods_id and i.warehouse_id = s.warehouse_id
						and s.inventory_upper < i.balance_count 
						and (s.inventory_upper <> 0 and s.inventory_upper is not null)
						and i.warehouse_id = '%s' ";
			$d = $db->query($sql, $warehouseId);
			$result[$i]["iuCount"] = $d[0]["cnt"];

			//销售价低于进货价的商品种类
			$sql = "select count(*) as cnt
					from t_goods
					where sale_price < lastBuyPrice and mode = 0 ";
			$d = $db->query($sql);
			$result[$i]["grossCount"] = $d[0]["cnt"];
		}
		
		return $result;
	}

	public function salePortal() {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$result = array();
		
		$db = M();
		
		// 当月
		$sql = "select year(now()) as y, month(now()) as m";
		$data = $db->query($sql);
		$year = $data[0]["y"];
		$month = $data[0]["m"];
		
		for($i = 0; $i < 6; $i ++) {
			if ($month < 10) {
				$result[$i]["month"] = "$year-0$month";
			} else {
				$result[$i]["month"] = "$year-$month";
			}
			$sql = "select sum(i.sale_money) as total_sale_money from t_inout_day i
					where year(i.biz_date) = $year and month(i.biz_date) = $month
			";
			$data = $db->query($sql);
			$result[$i]["saleMoney"] = $data[0]["total_sale_money"];
			
			if ($saleMoney != 0) {
				$result[$i]["rate"] = sprintf("%0.2f", $profit / $saleMoney * 100) . "%";
			} else {
				$result[$i]["rate"] = "";
			}
			
			// 获得上个月
			if ($month == 1) {
				$month = 12;
				$year -= 1;
			} else {
				$month -= 1;
			}
		}
		
		return $result;
	}

	public function purchasePortal() {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$result = array();
		
		$db = M();
		
		// 当月
		$sql = "select year(now()) as y, month(now()) as m";
		$data = $db->query($sql);
		$year = $data[0]["y"];
		$month = $data[0]["m"];
		
		for($i = 0; $i < 6; $i ++) {
			if ($month < 10) {
				$result[$i]["month"] = "$year-0$month";
			} else {
				$result[$i]["month"] = "$year-$month";
			}
			
			$sql = "select sum(w.goods_money) as goods_money
					from t_ia_bill w
					where w.bill_status = 1 
						and year(w.biz_dt) = %d
						and month(w.biz_dt) = %d";
			$data = $db->query($sql, $year, $month);
			$goodsMoney = $data[0]["goods_money"];
			if (! $goodsMoney) {
				$goodsMoney = 0;
			}
			
			// 扣除退货
			$sql = "select sum(s.rejection_money) as rej_money
					from t_pr_bill s
					where s.bill_status = 1000
						and year(s.bizdt) = %d
						and month(s.bizdt) = %d";
			$data = $db->query($sql, $year, $month);
			$rejMoney = $data[0]["rej_money"];
			if (! $rejMoney) {
				$rejMoney = 0;
			}
			
			$goodsMoney -= $rejMoney;
			
			$result[$i]["purchaseMoney"] = $goodsMoney;
			
			// 获得上个月
			if ($month == 1) {
				$month = 12;
				$year -= 1;
			} else {
				$month -= 1;
			}
		}
		
		return $result;
	}

	public function moneyPortal() {
		if ($this->isNotOnline()) {
			return $this->emptyResult();
		}
		
		$result = array();
		
		$db = M();
		
		// 应收账款
		$result[0]["item"] = "应收账款";
		$sql = "select sum(balance_money) as balance_money
				from t_receivables";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[0]["balanceMoney"] = $balance;
		
		// 账龄30天内
		$sql = "select sum(balance_money) as balance_money
				from t_receivables_detail
				where datediff(current_date(), biz_date) < 30";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[0]["money30"] = $balance;
		
		// 账龄30-60天
		$sql = "select sum(balance_money) as balance_money
				from t_receivables_detail
				where datediff(current_date(), biz_date) <= 60
					and datediff(current_date(), biz_date) >= 30";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[0]["money30to60"] = $balance;
		
		// 账龄60-90天
		$sql = "select sum(balance_money) as balance_money
				from t_receivables_detail
				where datediff(current_date(), biz_date) <= 90
					and datediff(current_date(), biz_date) > 60";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[0]["money60to90"] = $balance;
		
		// 账龄大于90天
		$sql = "select sum(balance_money) as balance_money
				from t_receivables_detail
				where datediff(current_date(), biz_date) > 90";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[0]["money90"] = $balance;
		
		// 应付账款
		$result[1]["item"] = "应付账款";
		$sql = "select sum(balance_money) as balance_money
				from t_payables";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[1]["balanceMoney"] = $balance;
		
		// 账龄30天内
		$sql = "select sum(balance_money) as balance_money
				from t_payables_detail
				where datediff(current_date(), biz_date) < 30";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[1]["money30"] = $balance;
		
		// 账龄30-60天
		$sql = "select sum(balance_money) as balance_money
				from t_payables_detail
				where datediff(current_date(), biz_date) <= 60
					and datediff(current_date(), biz_date) >= 30";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[1]["money30to60"] = $balance;
		
		// 账龄60-90天
		$sql = "select sum(balance_money) as balance_money
				from t_payables_detail
				where datediff(current_date(), biz_date) <= 90
					and datediff(current_date(), biz_date) > 60";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[1]["money60to90"] = $balance;
		
		// 账龄大于90天
		$sql = "select sum(balance_money) as balance_money
				from t_payables_detail
				where datediff(current_date(), biz_date) > 90";
		$data = $db->query($sql);
		$balance = $data[0]["balance_money"];
		if (! $balance) {
			$balance = 0;
		}
		$result[1]["money90"] = $balance;
		
		return $result;
	}
}