<?php

namespace Home\Service;

use Home\Service\IdGenService;
use Home\Service\BizlogService;

/**
 * 站点管理Service
 *
 * @author XH
 */
class SiteService extends ERPBaseService {

	public function categoryList($params) {
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		
		$sql = "select c.id, c.code, c.name, c.des, count(s.id) as cnt 
				from t_site_category c 
				left join t_site s 
				on (c.id = s.category_id)";
		$queryParam = array();
		if ($code) {
			$sql .= " and (s.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (s.name like '%s' or s.py like '%s' ) ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($address) {
			$sql .= " and (s.address like '%s' or s.address_shipping like '%s') ";
			$queryParam[] = "%{$address}%";
			$queryParam[] = "%{$address}%";
		}
		if ($contact) {
			$sql .= " and (s.contact01 like '%s' or s.contact02 like '%s' ) ";
			$queryParam[] = "%{$contact}%";
			$queryParam[] = "%{$contact}%";
		}
		if ($mobile) {
			$sql .= " and (s.mobile01 like '%s' or s.mobile02 like '%s' ) ";
			$queryParam[] = "%{$mobile}%";
			$queryParam[] = "%{$mobile}";
		}
		if ($tel) {
			$sql .= " and (s.tel01 like '%s' or s.tel02 like '%s' ) ";
			$queryParam[] = "%{$tel}%";
			$queryParam[] = "%{$tel}";
		}
		if ($qq) {
			$sql .= " and (s.qq01 like '%s' or s.qq02 like '%s' ) ";
			$queryParam[] = "%{$qq}%";
			$queryParam[] = "%{$qq}";
		}
		$sql .= " group by c.id
				order by c.code";
		
		return M()->query($sql, $queryParam);
	}

	public function lineList($params) {
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		
		$sql = "select c.id, c.code, c.name, c.des, count(s.id) as cnt 
				from t_site_line c 
				left join t_site s 
				on (c.id = s.line_id)";
		$queryParam = array();
		if ($code) {
			$sql .= " and (s.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (s.name like '%s' or s.py like '%s' ) ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($address) {
			$sql .= " and (s.address like '%s' or s.address_shipping like '%s') ";
			$queryParam[] = "%{$address}%";
			$queryParam[] = "%{$address}%";
		}
		if ($contact) {
			$sql .= " and (s.contact01 like '%s' or s.contact02 like '%s' ) ";
			$queryParam[] = "%{$contact}%";
			$queryParam[] = "%{$contact}%";
		}
		if ($mobile) {
			$sql .= " and (s.mobile01 like '%s' or s.mobile02 like '%s' ) ";
			$queryParam[] = "%{$mobile}%";
			$queryParam[] = "%{$mobile}";
		}
		if ($tel) {
			$sql .= " and (s.tel01 like '%s' or s.tel02 like '%s' ) ";
			$queryParam[] = "%{$tel}%";
			$queryParam[] = "%{$tel}";
		}
		if ($qq) {
			$sql .= " and (s.qq01 like '%s' or s.qq02 like '%s' ) ";
			$queryParam[] = "%{$qq}%";
			$queryParam[] = "%{$qq}";
		}
		$sql .= " group by c.id
				order by c.code";
		
		return M()->query($sql, $queryParam);
	}

	public function siteList($params) {
		$categoryId = $params["categoryId"];
		$lineId = $params["lineId"];
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		if($categoryId){
			$sql = "select id, category_id, line_id, code, name, contact01, qq01, tel01, mobile01, 
				contact02, qq02, tel02, mobile02, init_payables, init_payables_dt, 
				address, address_shipping ,sort
				from t_site 
				where (category_id = '%s')";
		} else {
			$sql = "select id, category_id, line_id, code, name, contact01, qq01, tel01, mobile01, 
				contact02, qq02, tel02, mobile02, init_payables, init_payables_dt, 
				address, address_shipping ,sort
				from t_site 
				where (line_id = '%s')";
		}
		
		$queryParam = array();
		$queryParam[] = $categoryId ? $categoryId : $lineId;
		if ($code) {
			$sql .= " and (code like '%s' ) ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (name like '%s' or py like '%s' ) ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($address) {
			$sql .= " and (address like '%s' or address_shipping like '%s') ";
			$queryParam[] = "%$address%";
			$queryParam[] = "%$address%";
		}
		if ($contact) {
			$sql .= " and (contact01 like '%s' or contact02 like '%s' ) ";
			$queryParam[] = "%{$contact}%";
			$queryParam[] = "%{$contact}%";
		}
		if ($mobile) {
			$sql .= " and (mobile01 like '%s' or mobile02 like '%s' ) ";
			$queryParam[] = "%{$mobile}%";
			$queryParam[] = "%{$mobile}";
		}
		if ($tel) {
			$sql .= " and (tel01 like '%s' or tel02 like '%s' ) ";
			$queryParam[] = "%{$tel}%";
			$queryParam[] = "%{$tel}";
		}
		if ($qq) {
			$sql .= " and (qq01 like '%s' or qq02 like '%s' ) ";
			$queryParam[] = "%{$qq}%";
			$queryParam[] = "%{$qq}";
		}
		$queryParam[] = $start;
		$queryParam[] = $limit;
		$sql .= " order by code 
				limit %d, %d";
		$result = array();
		$db = M();
		$data = $db->query($sql, $queryParam);
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["categoryId"] = $v["category_id"];
			$result[$i]["lineId"] = $v["line_id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["address"] = $v["address"];
			$result[$i]["addressShipping"] = $v["address_shipping"];
			$result[$i]["contact01"] = $v["contact01"];
			$result[$i]["qq01"] = $v["qq01"];
			$result[$i]["tel01"] = $v["tel01"];
			$result[$i]["mobile01"] = $v["mobile01"];
			$result[$i]["contact02"] = $v["contact02"];
			$result[$i]["qq02"] = $v["qq02"];
			$result[$i]["tel02"] = $v["tel02"];
			$result[$i]["mobile02"] = $v["mobile02"];
			$result[$i]["initPayables"] = $v["init_payables"];
			$result[$i]["sort"] = $v["sort"];
			if ($v["init_payables_dt"]) {
				$result[$i]["initPayablesDT"] = date("Y-m-d", strtotime($v["init_payables_dt"]));
			}
		}
		
		$sql = "select count(*) as cnt from t_site where (category_id  = '%s') ";
		$queryParam = array();
		$queryParam[] = $categoryId;
		if ($code) {
			$sql .= " and (code like '%s' ) ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (name like '%s' or py like '%s' ) ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($address) {
			$sql .= " and (address like '%s') ";
			$queryParam[] = "%$address%";
		}
		if ($contact) {
			$sql .= " and (contact01 like '%s' or contact02 like '%s' ) ";
			$queryParam[] = "%{$contact}%";
			$queryParam[] = "%{$contact}%";
		}
		if ($mobile) {
			$sql .= " and (mobile01 like '%s' or mobile02 like '%s' ) ";
			$queryParam[] = "%{$mobile}%";
			$queryParam[] = "%{$mobile}";
		}
		if ($tel) {
			$sql .= " and (tel01 like '%s' or tel02 like '%s' ) ";
			$queryParam[] = "%{$tel}%";
			$queryParam[] = "%{$tel}";
		}
		if ($qq) {
			$sql .= " and (qq01 like '%s' or qq02 like '%s' ) ";
			$queryParam[] = "%{$qq}%";
			$queryParam[] = "%{$qq}";
		}
		$data = $db->query($sql, $queryParam);
		
		return array(
				"siteList" => $result,
				"totalCount" => $data[0]["cnt"]
		);
	}

	public function editLine($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$des  = $params["des"];
		$db = M();
		
		if ($id) {
			// 编辑
			// 检查分类编码是否已经存在
			$sql = "select count(*) as cnt from t_site_line where code = '%s' and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的路线已经存在");
			}
			
			$sql = "update t_site_line 
					set code = '%s', name = '%s' , des = '%s' 
					where id = '%s' ";
			$db->execute($sql, $code, $name, $des, $id);
			
			$log = "编辑路线分类: 编码 = $code, 分类名 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-路线档案");
		} else {
			// 新增
			// 检查分类编码是否已经存在
			$sql = "select count(*) as cnt from t_site_line where code = '%s' ";
			$data = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的分类已经存在");
			}
			
			$idGen = new IdGenService();
			$id = $idGen->newId();
			
			$sql = "insert into t_site_line (id, code, name, des) values ('%s', '%s', '%s', '%s') ";
			$db->execute($sql, $id, $code, $name, $des);
			
			$log = "新增路线分类：编码 = $code, 分类名 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-路线档案");
		}
		
		return $this->ok($id);
	}

	public function editCategory($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$des  = $params["des"];
		$db = M();
		
		if ($id) {
			// 编辑
			// 检查分类编码是否已经存在
			$sql = "select count(*) as cnt from t_site_category where code = '%s' and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的分类已经存在");
			}
			
			$sql = "update t_site_category 
					set code = '%s', name = '%s' , des = '%s' 
					where id = '%s' ";
			$db->execute($sql, $code, $name, $des, $id);
			
			$log = "编辑供应商分类: 编码 = $code, 分类名 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-供应商档案");
		} else {
			// 新增
			// 检查分类编码是否已经存在
			$sql = "select count(*) as cnt from t_site_category where code = '%s' ";
			$data = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的分类已经存在");
			}
			
			$idGen = new IdGenService();
			$id = $idGen->newId();
			
			$sql = "insert into t_site_category (id, code, name, des) values ('%s', '%s', '%s', '%s') ";
			$db->execute($sql, $id, $code, $name, $des);
			
			$log = "新增供应商分类：编码 = $code, 分类名 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-供应商档案");
		}
		
		return $this->ok($id);
	}

	public function deleteCategory($params) {
		$id = $params["id"];
		
		$db = M();
		$data = $db->query("select code, name from t_site_category where id = '%s' ", $id);
		if (! $data) {
			return $this->bad("要删除的分类不存在");
		}
		
		$category = $data[0];
		
		$query = $db->query("select count(*) as cnt from t_site where category_id = '%s' ", $id);
		$cnt = $query[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("当前分类 [{$category['name']}] 下还有供应商档案，不能删除");
		}
		
		$db->execute("delete from t_site_category where id = '%s' ", $id);
		$log = "删除供应商分类： 编码 = {$category['code']}, 分类名称 = {$category['name']}";
		$bs = new BizlogService();
		$bs->insertBizlog($log, "基础数据-供应商档案");
		
		return $this->ok();
	}

	public function editSite($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$addressShipping = $params["addressShipping"];
		$contact01 = $params["contact01"];
		$mobile01 = $params["mobile01"];
		$tel01 = $params["tel01"];
		$qq01 = $params["qq01"];
		$contact02 = $params["contact02"];
		$mobile02 = $params["mobile02"];
		$tel02 = $params["tel02"];
		$qq02 = $params["qq02"];
		$initPayables = $params["initPayables"];
		$initPayablesDT = $params["initPayablesDT"];
		$attr = $params["attr"];
		$freight = $params["freight"];
		$houses = $params["houses"];
		$sort = $params["sort"];
		$ps = new PinyinService();
		$py = $ps->toPY($name);
		
		$categoryId = $params["categoryId"];
		$lineId =  $params["lineId"];
		
		$db = M();
		
		$sql = "select count(*) as cnt from t_site_category where id = '%s' ";
		$data = $db->query($sql, $categoryId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			return $this->bad("站点分类不存在");
		}

		$sql = "select count(*) as cnt from t_site_line where id = '%s' ";
		$data = $db->query($sql, $lineId);
		$cnt = $data[0]["cnt"];
		if ($cnt == 0) {
			return $this->bad("路线不存在");
		}
		
		if ($id) {
			// 编辑
			// 检查编码是否已经存在
			$sql = "select count(*) as cnt from t_site where code = '%s'  and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的供应商已经存在");
			}
			
			$sql = "update t_site 
					set code = '%s', name = '%s', category_id = '%s', py = '%s', 
					contact01 = '%s', qq01 = '%s', tel01 = '%s', mobile01 = '%s', 
					contact02 = '%s', qq02 = '%s', tel02 = '%s', mobile02 = '%s',
					address = '%s', address_shipping = '%s', attr = %d, freight = '%s', houses = %d , line_id = '%s' , sort= '$sort'
					where id = '%s' ";
			
			$db->execute($sql, $code, $name, $categoryId, $py, $contact01, $qq01, $tel01, $mobile01, 
					$contact02, $qq02, $tel02, $mobile02, $address, $addressShipping, $attr, $freight, $houses, $lineId, $id);
			//dump($db->getLastSql());
			$log = "编辑站点：编码 = $code, 名称 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-站点档案");
		} else {
			// 新增
			$idGen = new IdGenService();
			$id = $idGen->newId();
			
			// 检查编码是否已经存在
			$sql = "select count(*) as cnt from t_site where code = '%s' ";
			$data = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的站点已经存在");
			}
			
			$sql = "insert into t_site (id, category_id, code, name, py, contact01, 
					qq01, tel01, mobile01, contact02, qq02,
					tel02, mobile02, address, address_shipping, attr, freight, houses, line_id) 
					values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
							'%s', '%s', '%s', '%s', %d, '%f', %d, '%s')  ";
			$db->execute($sql, $id, $categoryId, $code, $name, $py, $contact01, $qq01, $tel01, 
					$mobile01, $contact02, $qq02, $tel02, $mobile02, $address, $addressShipping, $attr, $freight, $houses, $lineId);
			
			$log = "新增站点：编码 = {$code}, 名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-站点档案");
		}
		
		// 处理应付期初余额
		
		$initPayables = floatval($initPayables);
		if ($initPayables && $initPayablesDT) {
			$sql = "select count(*) as cnt 
					from t_payables_detail 
					where ca_id = '%s' and ca_type = 'site' and ref_type <> '应付账款期初建账' ";
			$data = $db->query($sql, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				// 已经有往来业务发生，就不能修改应付账了
				return $this->ok($id);
			}
			
			$sql = "update t_site 
					set init_payables = %f, init_payables_dt = '%s' 
					where id = '%s' ";
			$db->execute($sql, $initPayables, $initPayablesDT, $id);
			
			// 应付明细账
			$sql = "select id from t_payables_detail 
					where ca_id = '%s' and ca_type = 'site' and ref_type = '应付账款期初建账' ";
			$data = $db->query($sql, $id);
			if ($data) {
				$payId = $data[0]["id"];
				$sql = "update t_payables_detail 
						set pay_money = %f ,  balance_money = %f , biz_date = '%s', date_created = now(), act_money = 0 
						where id = '%s' ";
				$db->execute($sql, $initPayables, $initPayables, $initPayablesDT, $payId);
			} else {
				$idGen = new IdGenService();
				$payId = $idGen->newId();
				$sql = "insert into t_payables_detail (id, pay_money, act_money, balance_money, ca_id,
						ca_type, ref_type, ref_number, biz_date, date_created) 
						values ('%s', %f, 0, %f, '%s', 'site', '应付账款期初建账', '%s', '%s', now()) ";
				$db->execute($sql, $payId, $initPayables, $initPayables, $id, $id, $initPayablesDT);
			}
			
			// 应付总账
			$sql = "select id from t_payables where ca_id = '%s' and ca_type = 'site' ";
			$data = $db->query($sql, $id);
			if ($data) {
				$pId = $data[0]["id"];
				$sql = "update t_payables 
						set pay_money = %f ,  balance_money = %f , act_money = 0 
						where id = '%s' ";
				$db->execute($sql, $initPayables, $initPayables, $pId);
			} else {
				$idGen = new IdGenService();
				$pId = $idGen->newId();
				$sql = "insert into t_payables (id, pay_money, act_money, balance_money, ca_id, ca_type)
						values ('%s', %f, 0, %f, '%s', 'site') ";
				$db->execute($sql, $pId, $initPayables, $initPayables, $id, $initPayablesDT);
			}
		}
		
		return $this->ok($id);
	}

	public function deleteSite($params) {
		$id = $params["id"];
		$db = M();
		$sql = "select code, name from t_site where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的站点档案不存在");
		}
		$code = $data[0]["code"];
		$name = $data[0]["name"];
		
		// 判断是否能删除供应商
		/*
		$sql = "select count(*) as cnt from t_ws_bill where site_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("站点 [{$code} {$name}] 在订单中已经被使用，不能删除");
		}
		*/
		
		$db->startTrans();
		try {
			$sql = "delete from t_site where id = '%s' ";
			$db->execute($sql, $id);
			
			$log = "删除站点：编码 = {$code},  名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-删除站点");
			
			$db->commit();
		} catch ( Exception $exc ) {
			$db->rollback();
			
			return $this->bad("数据库操作失败，请联系管理员");
		}
		return $this->ok();
	}

	public function queryData($queryKey) {
		if ($queryKey == null) {
			$queryKey = "";
		}
		
		$sql = "select id, code, name from t_site
				where code like '%s' or name like '%s' or py like '%s' 
				order by code 
				limit 20";
		$key = "%{$queryKey}%";
		return M()->query($sql, $key, $key, $key);
	}

	public function siteInfo($params) {
		$id = $params["id"];
		
		$result = array();
		
		$db = M();
		$sql = "select * 
				from t_site
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$result["categoryId"] = $data[0]["category_id"];
			$result["lineId"] = $data[0]["line_id"];
			$result["code"] = $data[0]["code"];
			$result["name"] = $data[0]["name"];
			$result["contact01"] = $data[0]["contact01"];
			$result["qq01"] = $data[0]["qq01"];
			$result["mobile01"] = $data[0]["mobile01"];
			$result["tel01"] = $data[0]["tel01"];
			$result["contact02"] = $data[0]["contact02"];
			$result["qq02"] = $data[0]["qq02"];
			$result["mobile02"] = $data[0]["mobile02"];
			$result["tel02"] = $data[0]["tel02"];
			$result["address"] = $data[0]["address"];
			$result["addressShipping"] = $data[0]["address_shipping"];
			$result["initPayables"] = $data[0]["init_payables"];
			$result["attr"] = $data[0]["attr"];
			$result["freight"] = $data[0]["freight"];
			$result["houses"] = $data[0]["houses"];
			$result["sort"] = $data[0]["sort"];
			$d = $data[0]["init_payables_dt"]; 
			if ($d) {
				$result["initPayablesDT"] = $this->toYMD($d);
			}
		}
		
		return $result;
	}
}