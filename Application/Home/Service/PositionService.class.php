<?php

namespace Home\Service;

use Home\Service\IdGenService;
use Home\Service\BizlogService;

/**
 * 仓位档案Service
 *
 * @author 李静波
 */
class PositionService extends ERPBaseService {

	public function categoryList($params) {
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$contact = $params["contact"];
		$mobile = $params["mobile"];
		$tel = $params["tel"];
		$qq = $params["qq"];
		
		$sql = "select c.id, c.code, c.name, count(s.id) as cnt 
				from t_position_category c 
				left join t_position s 
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
	
	public function goods($pid) {
		$sql = "select * from t_position 
				where position_id = '%s'";
		$data = M()->query($sql, $pid);
		$result = array();
		foreach ( $data as $key => $value ) {
			$result[$key]["id"] = $value["id"];
			$result[$key]["code"] = $value["code"];
			$tmp = M()->query("SELECT * FROM `t_goods` WHERE `code` = '" . $value["code"] . "'");
			$result[$key]["name"] = $tmp[0]["name"];

		}
		return $result;
	}
	public function deletegood($id) {
		$db = M();
		$sql = "select * from t_position where id = '%s' ";
		$data = $db->query($sql, $id);
		if(empty($data)){
			return $this->bad("商品不存在,无法删除");
		} 
		$db->execute("delete from t_position where id = '%s' ", $id);
		$log = "删除仓位中商品： 商品编号 = {$data[0]['code']}, 仓位id = {$data[0]['position_id']}";
		$bs = new BizlogService();
		$bs->insertBizlog($log, "基础数据-仓位档案");
		return $this->ok();
	}
		
	public function editgoods($position_id, $code, $id) {

		$db = M();
		$sql = "select * from t_position_category where id  = '%s'";
		$position_info = $db->query($sql, $position_id);
		if (empty($position_info)) {
				return $this->bad("请选择正确库位");
		}
		if ($id) {
			// 编辑
			// 检查分类编码是否已经存在
			$sql = "select count(*) as cnt from t_position where code = '%s' and position_id = '%s' and id <> '%s' ";
			$data = $db->query($sql, $code, $position_id, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的商品已经存在");
			}
			
			$sql = "update t_position 
					set code = '%s', position_id = '%s' 
					where id = '%s' ";
			$db->execute($sql, $code, $position_id, $id);
			
			$log = "编辑仓位商品: 编码 = $code, 分类名 = $position_id";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-仓位档案");
		} else {
			// 新增
			// 检查是否已经存在
			$sql = "select count(*) as cnt from t_position where position_id = '%s' and code = '%s' ";
			$data = $db->query($sql, $position_id, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("商品编码为 [$code] 的商品已在本库位存在");
			}
			$sql = "insert into t_position (position_id, code) values ('%s', '%s') ";
			$db->execute($sql, $position_id, $code);
			
			$log = "新增仓位分类：编码 = $code, 分类名 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-仓位档案");
		}
		
		return $this->ok($id);
	}
			
	public function positioninfos($pid) {
		$sql = "select * from t_position_category 
				where id = '%s'";
		$data = M()->query($sql, $pid);
		if($data[0]['pid']){
			$sql = "select name from t_position_category 
				where id = '%s'";
			$pid = M()->query($sql, $data[0]['pid']);
		}else{
			$sql = "select name from t_position_category 
				where wherehouse_id = '%s'";
			$pid = M()->query($sql, $data[0]['wherehouse_id']);
		}
		$data[0]['pidname'] = $pid[0]['name'];
		return $data[0];
	}
	
	public function positionList($params) {
		$code = $params["code"];
		$name = $params["name"];
		$node = $params["node"];
		if($node == "root"){
			//第一步查询仓库,第二步查询仓位
			$sql = "select id, name,code from t_warehouse 
					order by inited desc, code asc";
			$db = M();
			$warehouseList1 = $db->query($sql);
			$result = array();
			// 第一级组织
			foreach ( $warehouseList1 as $i => $pos1 ) {
				$result[$i]["id"] = $pos1["id"];
				$result[$i]["text"] = $pos1["name"];
				$result[$i]["orgCode"] = $pos1["code"];
				$result[$i]["fullName"] = $pos1["name"];
				$tmp = $db->query("SELECT count(id) as num FROM `t_position` WHERE `position_id` in (SELECT id FROM `t_position_category` WHERE `wherehouse_id` = '" . $pos1["id"] . "')");
				$result[$i]["goods_num"] = $tmp[0]['num'];
				$result[$i]["pid"] = 0;
				$result[$i]["wherehouse_id"] = $pos1["id"];
				
				$result[$i]["children"] = $this->get_position_cate('9');
				$result[$i]["leaf"] = 0;
				$result[$i]["expanded"] = $i == 0 ? true : false;
			}
			return $result;
		} else {
			$db = M();
			$sql = "select id, name, code, full_name, wherehouse_id
				from t_position_category 
				where pid = '%s'
				order by sort asc, code asc";
				$posList = $db->query($sql, $node);
				$result = array();
				foreach($posList as $k => $v){
					$result[$k]["id"] = $v["id"];
					$result[$k]["text"] = $v["name"];
					$result[$k]["orgCode"] = $v["code"];
					$result[$k]["fullName"] = $v["full_name"];
					$result[$k]["leaf"] = 0;
					$result[$k]["expanded"] = true;
					$tmp = $db->query("SELECT count(id) as num FROM `t_position` WHERE `position_id` = '" . $v["id"] . "'");

					$result[$k]["wherehouse_id"] = $v["wherehouse_id"];
					$result[$k]["goods_num"] = $tmp[0]['num'];
					$result[$k]["pid"] = $v["id"];
				}
			return $result;
		}
		
	}
  public function get_position_cate($pid){
  				if(strlen($pid) >= 8){
  					return array();
  				}
  				$list = S("list");
  				if(!$list){
  					$list = M("position_category")->select();
  				}
  				$ret = array();
  				foreach ($list as $key => $value) {
  					if($value["pid"] == $pid){
  						$ret[] = $value;
  					}
  				}
  				$result = array();
  				foreach ($ret as $k => $v) {
  					$result[$k]["id"] = $v["id"];
					$result[$k]["text"] = $v["name"];
					$result[$k]["orgCode"] = $v["code"];
					$result[$k]["fullName"] = $v["full_name"];
					$result[$k]["leaf"] = 0;
					$result[$k]["expanded"] = true;
					$result[$k]["wherehouse_id"] = $v["wherehouse_id"];
					$result[$k]["goods_num"] = 0;
					$result[$k]["pid"] = $v["id"];
  				}
  				return $result;
  				$db = M();
				$sql = "select id, name, code, full_name, wherehouse_id
					from t_position_category 
					where pid = '%s'
					order by sort asc, code asc";
					$posList = $db->query($sql, $pid);
					$result = array();
					foreach($posList as $k => $v){
						$result[$k]["id"] = $v["id"];
						$result[$k]["text"] = $v["name"];
						$result[$k]["orgCode"] = $v["code"];
						$result[$k]["fullName"] = $v["full_name"];
						$result[$k]["leaf"] = 0;
						$result[$k]["expanded"] = true;
						$tmp = $db->query("SELECT count(id) as num FROM `t_position` WHERE `position_id` = '" . $v["id"] . "'");

						$result[$k]["wherehouse_id"] = $v["wherehouse_id"];
						$result[$k]["goods_num"] = $tmp[0]['num'];
						$result[$k]["pid"] = $v["id"];
						if(strlen($v["code"]) <= 6){
							$arr = $this->get_position_cate($v["id"]);
							foreach($arr as $a){
								$arr_tmp[] = $a;	
							}
							$result[$k]["children"] = $arr;
						}
					}
			//$result[$pid]["children"] = $c3;

			
  		return $result;
  }
	public function editCategory($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		
		$db = M();
		
		if ($id) {
			// 编辑
			// 检查分类编码是否已经存在
			$sql = "select count(*) as cnt from t_position_category where code = '%s' and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的分类已经存在");
			}
			//获取到fullname
			$map = array(
				"id" => $id
			);
			$pos = M("position_category")->where($map)->find();
			if($pos["pid"] == 0){
				$fullname = $name;
			} else {
				$map = array(
					"id" => $pos["pid"]
				);
				$pos_parent = M("position_category")->where($map)->find();
				$fullname = $pos_parent["full_name"]."\\".$name;
			}
			$sql = "update t_position_category 
					set code = '%s', name = '%s' , full_name = '%s'
					where id = '%s' ";
			$db->execute($sql, $code, $name, $fullname, $id);
			
			$log = "编辑仓位分类: 编码 = $code, 分类名 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-仓位档案");
		} else {
			// 新增
			// 检查分类编码是否已经存在
			$sql = "select count(*) as cnt from t_position_category where code = '%s' ";
			$data = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的分类已经存在");
			}
			
			$idGen = new IdGenService();
			$id = $idGen->newId();
			
			$sql = "insert into t_position_category (id, code, name) values ('%s', '%s', '%s') ";
			$db->execute($sql, $id, $code, $name);
			
			$log = "新增仓位分类：编码 = $code, 分类名 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-仓位档案");
		}
		
		return $this->ok($id);
	}

	public function deleteCategory($params) {
		$id = $params["id"];
		
		$db = M();
		$data = $db->query("select code, name from t_position_category where id = '%s' ", $id);
		if (! $data) {
			return $this->bad("要删除的分类不存在");
		}
		
		$category = $data[0];
		
		$query = $db->query("select count(*) as cnt from t_position where category_id = '%s' ", $id);
		$cnt = $query[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("当前分类 [{$category['name']}] 下还有仓位档案，不能删除");
		}
		
		$db->execute("delete from t_position_category where id = '%s' ", $id);
		$log = "删除仓位分类： 编码 = {$category['code']}, 分类名称 = {$category['name']}";
		$bs = new BizlogService();
		$bs->insertBizlog($log, "基础数据-仓位档案");
		
		return $this->ok();
	}

	public function editPosition($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$fullname = $params["fullname"];
		$pid = intval($params["pid"]);
		$wherehouse_id = $params["wherehouse_id"];
		$sort = intval($params["sort"]);
		$ps = new PinyinService();
		$py = $ps->toPY($name);
		//$categoryId = $params["categoryId"];
		$db = M();
		$sql = "select * from t_position_category where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			// 编辑
			// 检查编码是否已经存在
			$sql = "select count(*) as cnt from t_position_category where code = '%s'  and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的仓位已经存在");
			}
			//获取到fullname
			$map = array(
				"id" => $id
			);
			$pos = M("position_category")->where($map)->find();
			if($pos["pid"] == 0){
				$fullname = $name;
			} else {
				$map = array(
					"id" => $pos["pid"]
				);
				$pos_parent = M("position_category")->where($map)->find();
				$fullname = $pos_parent["full_name"]."\\".$name;
			}
			$sql = "update t_position_category 
					set code = '%s', name = '%s', pid = '%s', sort = '%s', 
					wherehouse_id = '%s', full_name = '%s'
					where id = '%s'  ";
			
			$db->execute($sql, $code, $name, $pid, $sort, $wherehouse_id, $fullname, $id);
			
			$log = "编辑仓位：编码 = $code, 名称 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-仓库档案");
		} else {
			// 新增
			// 检查编码是否已经存在
			$sql = "select * from t_position_category where code = '%s' ";
			$data = $db->query($sql, $code);
			if ($data) {
				return $this->bad("编码为 [$code] 的库位已经存在");
			}
			if($pid){
				$map = array(
					"id" => $pid
				);
				$pos_parent = M("position_category")->where($map)->find();
				$fullname = $pos_parent["full_name"]."\\".$name;
			} else {
				$fullname = $name;
			}
			$sql = "insert into t_position_category (code, name, pid, sort, wherehouse_id, full_name) 
					values ('%s', '%s', '%s', '%s', '%s', '%s')  ";
			$db->execute($sql, $code, $name, $pid, $sort, $wherehouse_id, $fullname);
			$log = "新增库位：编码 = {$code}, 名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-库位档案");
		}
		return $this->ok($id);
	}

	public function deletePosition($params) {
		$id = $params["id"];
		$db = M();
		$sql = "select code, name from t_position_category where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的仓位档案不存在");
		}
		$code = $data[0]["code"];
		$name = $data[0]["name"];
		
		// 判断是否能删除仓位
		$sql = "select count(*) as cnt from t_position where position_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("仓位档案 [{$code} {$name}] 已经有商品，不能删除");
		}
		// 判断是否能删除仓位
		$sql = "select id from t_position_category where pid = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			return $this->bad("仓位 [{$code} {$name}] 下面有库位，不能删除");
		}
		
		$db->startTrans();
		try {
			$sql = "delete from t_position_category where id = '%s' ";
			$db->execute($sql, $id);
			$log = "删除仓位档案：编码 = {$code},  名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-仓位档案");
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
		
		$sql = "select id, code, name from t_position
				where code like '%s' or name like '%s' or py like '%s' 
				order by code 
				limit 20";
		$key = "%{$queryKey}%";
		return M()->query($sql, $key, $key, $key);
	}

	public function positionInfo($params) {
		$id = $params["id"];
		
		$result = array();
		
		$db = M();
		$sql = "select category_id, code, name, contact01, qq01, mobile01, tel01,
					contact02, qq02, mobile02, tel02, address, address_shipping,
					init_payables, init_payables_dt
				from t_position
				where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			$result["categoryId"] = $data[0]["category_id"];
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
			$d = $data[0]["init_payables_dt"]; 
			if ($d) {
				$result["initPayablesDT"] = $this->toYMD($d);
			}
		}
		
		return $result;
	}
}