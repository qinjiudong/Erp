<?php

namespace Home\Service;

use Home\Service\MallService;
/**
 * 商品Service
 *
 * @author 李静波
 */
class GoodsService extends ERPBaseService {
	public $db;
	public function __construct(){
		$this->db=M('goods','t_');
	}
	public function allUnits() {
		return M()->query("select id, name from t_goods_unit order by name");
	}

	//获取同一商品不同规格的列表
	public function allSameUnits(){
		$list = M("same_goods_spec", "t_")->select();
		return $list;
	}

	public function editUnit($params) {
		$id = $params["id"];
		$name = $params["name"];
		
		$db = M();
		
		if ($id) {
			// 编辑
			// 检查计量单位是否存在
			$sql = "select count(*) as cnt from t_goods_unit where name = '%s' and id <> '%s' ";
			$data = $db->query($sql, $name, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("计量单位 [$name] 已经存在");
			}
			
			$sql = "update t_goods_unit set name = '%s' where id = '%s' ";
			$db->execute($sql, $name, $id);
			
			$log = "编辑计量单位: $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品计量单位");
		} else {
			// 新增
			// 检查计量单位是否存在
			$sql = "select count(*) as cnt from t_goods_unit where name = '%s' ";
			$data = $db->query($sql, $name);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("计量单位 [$name] 已经存在");
			}
			
			$idGen = new IdGenService();
			$id = $idGen->newId();
			$sql = "insert into t_goods_unit(id, name) values ('%s', '%s') ";
			$db->execute($sql, $id, $name);
			
			$log = "新增计量单位: $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品计量单位");
		}
		
		return $this->ok($id);
	}

	public function deleteUnit($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select name from t_goods_unit where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的商品计量单位不存在");
		}
		$name = $data[0]["name"];
		
		// 检查记录单位是否被使用
		$sql = "select count(*) as cnt from t_goods where unit_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品计量单位 [$name] 已经被使用，不能删除");
		}
		
		$sql = "delete from t_goods_unit where id = '%s' ";
		$db->execute($sql, $id);
		
		$log = "删除商品计量单位: $name";
		$bs = new BizlogService();
		$bs->insertBizlog($log, "基础数据-商品计量单位");
		
		return $this->ok();
	}

	public function allCategories($params) {
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		
		$sql = "select c.id, c.code, c.name, count(g.id) as cnt 
				from t_goods_category c
				left join t_goods g 
				on (c.id = g.category_id) ";
		$queryParam = array();
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		
		$sql .= " group by c.id 
				  order by c.code";
		
		return M()->query($sql, $queryParam);
	}

	public function getGoodsCategoryList(){
		//首先获取最高层级
		$db = M("goods_category", "t_");
		//$maxLevel = $db->getField("max(level) as maxlevel");
		//首先获取一级
		$map = array(
			"parent_id" => "0"
		);
		$order = "code asc";
		$categorylist = $db->where($map)->order($order)->select();
		foreach ($categorylist as $key => $v) {
			//获取二级分类
			$map2 = array(
				"parent_id" => $v["id"]
			);
			$categorylist2 = $db->where($map2)->order($order)->select();
			foreach ($categorylist2 as $key2 => $v2) {
				//获取三级分类
				$map3 = array(
					"parent_id" => $v2["id"]
				);
				$categorylist3 = $db->where($map3)->order($order)->select();
				foreach ($categorylist3 as $key3 => $v3) {
					$categorylist3[$key3]["leaf"] = 1;
					$categorylist3[$key3]["children"] = array();
					$categorylist3[$key3]["text"] = $v3["name"];
					$categorylist3[$key3]["parent_name"] = $v2["name"];
				}
				$categorylist2[$key2]["children"] = $categorylist3 ? $categorylist3 : array();
				$categorylist2[$key2]["leaf"] = 0;
				$categorylist2[$key2]["text"] = $v2["name"];
				$categorylist2[$key2]["parent_name"] = $v["name"];
			}
			$categorylist[$key]["children"] = $categorylist2 ? $categorylist2 : array();
			$categorylist[$key]["leaf"] = 0;
			//$categorylist[$key]["expanded"] = true;
			$categorylist[$key]["text"] = $v["name"];
		}
		//dump($categorylist);
		return $categorylist;
	}

	public function editCategory($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$parent_id = $params["parentid"] ? $params["parentid"] : 0;
		$db = M();
		
		if ($id) {
			// 编辑
			// 检查同编码的分类是否存在
			$sql = "select count(*) as cnt from t_goods_category where code = '%s' and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [{$code}] 的分类已经存在");
			}
			
			$sql = "update t_goods_category
					set code = '%s', name = '%s', parent_id = '%s' 
					where id = '%s' ";
			$db->execute($sql, $code, $name, $parent_id, $id);
			
			$log = "编辑商品分类: 编码 = {$code}， 分类名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品");
		} else {
			// 新增
			// 检查同编码的分类是否存在
			$sql = "select count(*) as cnt from t_goods_category where code = '%s' ";
			$data = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [{$code}] 的分类已经存在");
			}
			
			$idGen = new IdGenService();
			$id = $idGen->newId();
			
			$sql = "insert into t_goods_category (id, code, name, parent_id) values ('%s', '%s', '%s', '%s')";
			$db->execute($sql, $id, $code, $name, $parent_id);
			
			$log = "新增商品分类: 编码 = {$code}， 分类名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品");
		}
		return $this->ok($id);
	}

	public function deleteCategory($params) {
		$id = $params["id"];
		
		$db = M();
		$sql = "select code, name from t_goods_category where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的商品分类不存在");
		}
		$code = $data[0]["code"];
		$name = $data[0]["name"];
		
		$sql = "select count(*) as cnt from t_goods where category_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("还有属于商品分类 [{$name}] 的商品，不能删除该分类");
		}
		
		$sql = "delete from t_goods_category where id = '%s' ";
		$db->execute($sql, $id);
		
		$log = "删除商品分类：  编码 = {$code}， 分类名称 = {$name}";
		$bs = new BizlogService();
		$bs->insertBizlog($log, "基础数据-商品");
		
		return $this->ok();
	}

	public function goodsList($params) {
		$categoryId = $params["categoryId"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$is_delete = $params["is_delete"] ? 1 : 0;
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$barcode = $params["barcode"];
		$supplier_code = $params["supplier_code"];
		$db = M();
		$result = array();
		if($categoryId && $categoryId != 'null'){
			$category_array = array("'$categoryId'");
			//查询所有的子节点
			$map = array(
				"parent_id" => $categoryId
			);
			$db = M("goods_category", "t_");
			$list = $db->where($map)->select();
			foreach ($list as $key => $value) {
				$category_array[]  = "'".$value["id"]."'";
				$map = array(
					"parent_id" => $value["id"]
				);
				$list2 = $db->where($map)->select();
				foreach ($list2 as $key2 => $value2) {
					$category_array[]  = "'".$value2["id"]."'";
				}
			}
			//dump($category_array);
			$categorystr = implode(",", $category_array);
		} else {
			if($categoryId && $categoryId != 'null'){
				$categorystr = $categoryId;
			}
			
		}
		$supplier_sql = "";
		if($supplier_code){
			$supplier = M("supplier")->where(array("code"=>$supplier_code))->find();
			$supplier_id = $supplier["id"];
			if($supplier_id){
				$supplier_sql = " and g.id in (select goodsid from t_supplier_goods where supplierid = '$supplier_id')";
			}
		}

		if($is_delete == 1){
			$category_query = " (1=1) ";
		} else {
			if($categorystr){
				$category_query = " ( g.category_id in ($categorystr) ) ";
			} else {
				$category_query = " (1=1) ";
			}
		}
		$sql = "select i.balance_count, g.basecode,g.packrate, g.id, g.code, g.buytax, g.name, g.sale_price, g.promote_price,g.promote_begin_time, promote_end_time, g.bulk, g.spec, g.is_delete,  g.unit_id ,g.barcode,g.status,g.lastbuyprice,g.oversold,s.code as supplier_code,s.name as supplier_name
				from t_goods g left join t_inventory i on g.id = i.goods_id left join (select goodsid,supplierid from t_supplier_goods group by goodsid) as sg on sg.goodsid = g.id left join t_supplier s on s.id = sg.supplierid 
				where g.is_delete = $is_delete and $category_query $supplier_sql";
		$queryParam = array();
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		if($barcode){
			$sql .= " and (g.barcode like '%s')";
			$queryParam[] = "%{$barcode}%";
		}
		
		$sql .= " order by g.code limit %d, %d";
		$queryParam[] = $start;
		$queryParam[] = $limit;
		//echo $sql;
		$data = $db->query($sql, $queryParam);

		$list1[] = array('商品编码',  '条码','商品名称', '规格', '计量单位', '属性',
								'进价', '销售价', '毛利', '负库存', '上架','库存','供应商编码','供应商名称' );
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["salePrice"] = $v["sale_price"];
			$result[$i]["spec"] = $v["spec"];
			$result[$i]["unitId"] = $v["unit_id"];
			$result[$i]["unitName"] = $v["unit_id"];
			$result[$i]["is_delete"] = $v["is_delete"];
			$result[$i]["barcode"] = $v["barcode"];
			$result[$i]["bulk_str"] = $v["bulk"] == 1 ? "计个":"计重";
			$result[$i]["status_str"] = $v["status"] == 1 ? "是":"否";
			$result[$i]["oversold_str"] = $v["oversold"] == 1 ? "是":"否";
			$result[$i]["buyPrice"] = $v["lastbuyprice"];
			$result[$i]["balance_count"] = $v["balance_count"];
			$result[$i]["supplier_code"] = $v["supplier_code"];
			$result[$i]["supplier_name"] = $v["supplier_name"];
			$result[$i]["basecode"] = $v["basecode"];
			$result[$i]["packrate"] = $v["packrate"];
			$gross = 0;
			if($v["promote_price"] > 0 && date('Y-m-d H:i:s',time())>$v["promote_begin_time"] && date('Y-m-d H:i:s',time())<$v["promote_end_time"]){
				$gross = round( (($v["promote_price"] - $v["lastbuyprice"]) / $v["promote_price"]) * 100, 1 );
				$result[$i]["salePrice"] = $v["promote_price"];
			} else {
				$gross = round( (($v["sale_price"] - $v["lastbuyprice"]) / $v["sale_price"]) * 100, 1);
				$result[$i]["salePrice"] = $v["sale_price"];
			}
			$result[$i]["gross"] = $gross;
			$result[$i]["buytax"] = $v["buytax"];
			$list1[] = array($v["code"], $v["barcode"], $v["name"],$v["spec"],$v["unit_id"],$result[$i]["bulk_str"],
							$result[$i]["buyPrice"],$result[$i]["salePrice"],$gross,$result[$i]["oversold_str"],$result[$i]["status_str"],$v["balance_count"],$v["supplier_code"],$v["supplier_name"]
				);
		}

		if(I("request.act") == 'export'){	//导出数据
			$change_name = '商品明细导出数据.csv';
			header('Content-Type: application/vnd.ms-excel');
			header('Content-Disposition: attachment;filename="'.$change_name.'"');
			header('Cache-Control: max-age=0');
			$file = fopen('php://output', 'a');
		}
		if(I("request.act") == 'export'){	//导出数据
			foreach ($list1 as $k => $line){
				//$list[$k] = iconv('utf-8', 'gbk', $line);
				foreach ($line as $key => $value) {
					$list1[$k][$key] = iconv('utf-8', 'gbk', $value);
				}
  				
  			}
  			foreach ($list1 as $key => $value) {
  				fputcsv($file,$value);
  			}
			fclose($file);exit;
		}
		
		$sql = "select count(*) as cnt from t_goods g where $category_query and g.is_delete = $is_delete $supplier_sql";
		$queryParam = array();
		//$queryParam[] = $categoryId;
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		if($barcode){
			$sql .= " and (g.barcode like '%s')";
			$queryParam[] = "%{$barcode}%";
		}
		$data = $db->query($sql, $queryParam);
		$totalCount = $data[0]["cnt"];
		
		return array(
				"goodsList" => $result,
				"totalCount" => $totalCount
		);
	}

	public function editGoods($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$categoryId = $params["category_id"];
		$unitId = $params["unit_id"];
		$sameSpecId = $params["same_spec_id"];
		$salePrice = $params["salePrice"];
		$params["sale_price"] = $salePrice;
		$barcode = $params["barCode"];
		//dump($params);
		$db = M();
		$sql = "select name from t_goods_unit where id = '%s' ";
		$act = "";
		$data = $db->query($sql, $unitId);
		$promote_num = $params["promote_num"];
		$sql = "update t_goods set promote_num={$promote_num} where id='{$id}'";
		$db->execute($sql);
		//首先判断格式 -- 计重商品的规则必须要包含g
		if($params["bulk"] == 0){
			$spec = $params["spec"];
			if(strpos($spec, "g") == false){
				return $this->bad("计重商品规格必须包含单位g");
			}
		}
		//其次判断包装率是否符合规范
		if($params["baseCode"]){
			//判断商品是否存在
			$sql = "select * from t_goods where code = '%s' ";
			$goods_data = $db->query($sql, $params["baseCode"]);
			if(!$goods_data){
				return $this->bad("基础商品编码不存在");
			}
			if($params["baseCode"] == $params["code"]){
				return $this->bad("基础商品编码不能是本身");
				
			}
			$params["baseid"] = $goods_data[0]["id"];
			if(!$params["packRate"] || !is_numeric($params["packRate"])){
				return $this->bad("包装率不符合规范");
			}
			$params["basecode"] = $params["baseCode"];
			$params["packrate"] = $params["packRate"];
		}
		if (! $data) {
			//return $this->bad("计量单位不存在");
			//先查询是否已经存在name
			$map = array(
				"name" => $unitId
			);
			$u = M("goods_unit", "t_")->where($map)->find();
			if($u){
				$unitId = $params["unit_id"] = $u["id"];
			} else {
				//如果计量单位不存在，则自动添加
				$idGen = new IdGenService();
				$uid = $idGen->newId();
				$sql = "insert into t_goods_unit(id, name) values ('%s', '%s') ";
				$db->execute($sql, $uid, $unitId);
				$unitId = $params["unit_id"] = $uid;
				$log = "新增计量单位: $unitId";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "基础数据-商品计量单位");
			}
			
		}
		//查询specid存在否
		$map = array(
			"id" => $sameSpecId
		);
		$data = M("same_goods_spec", "t_")->where($map)->find();
		if(! $data && $sameSpecId){
			//同规格查询是否存在name
			$map = array(
				"name" => $sameSpecId
			);
			$u = M("same_goods_spec", "t_")->where($map)->find();
			if($u){
				$sameSpecId = $params["same_spec_id"] = $u["id"];
			} else {
				//如果同规格单位不存在，则自动添加
				//$idGen = new IdGenService();
				//$uid = $idGen->newId();
				//$sql = "insert into t_same_goods_spec(id, name) values ('', '%s') ";
				$data = array(
					"name" => $sameSpecId
				);
				//$db->execute($sql, $uid, $unitId);
				$result = M("same_goods_spec", "t_")->add($data);
				$sameSpecId = $params["same_spec_id"] = $result;
				//dump($result);
				//exit;
				$log = "新增同规格单位: $sameSpecId";
				$bs = new BizlogService();
				$bs->insertBizlog($log, "基础数据-商品规格单位");
			}
		}

		$sql = "select * from t_goods_category where id = '%s' ";
		$data = $db->query($sql, $categoryId);
		if (! $data) {
			return $this->bad("商品分类不存在");
		}
		
		//获取顶级分类并且赋值
		if($data[0]["parent_id"] && $data[0]["parent_id"] != "0"){
			$sql = "select * from t_goods_category where id = '%s' ";
			$data = $db->query($sql, $data[0]["parent_id"]);
			if($data[0]["parent_id"] && $data[0]["parent_id"] != "0"){
				$sql = "select * from t_goods_category where id = '%s' ";
				$data = $db->query($sql, $data[0]["parent_id"]);
			}
		}
		$parent_id = $data[0]["id"];
		$params["parent_cate_id"] = $parent_id;
		$goodsdb = M('goods','t_');
		if ($id) {
			// 编辑
			$act = "update";
			// 检查商品编码是否唯一
			$sql = "select count(*) as cnt from t_goods where code = '%s' and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [{$code}]的商品已经存在");
			}

			// 检查商品条码是否唯一
			$sql = "select count(*) as cnt from t_goods where barcode = '%s' and id <> '%s' ";
			$data = $db->query($sql, $barcode, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("条码为 [{$barcode}]的商品已经存在");
			}
			
			$ps = new PinyinService();
			$py = $ps->toPY($name);
			
			$sql = "update t_goods
					set code = '%s', name = '%s', spec = '%s', category_id = '%s', 
					    unit_id = '%s', sale_price = %f, py = '%s' 
					where id = '%s' ";
			
			//判断是否售价
			if($params["promote_price"] > 0 && $params["promote_price"] > $params["sale_price"]){
				return $this->bad("促销价不能大于售价");
			}
			if($params["promote_begin_time"] && $params["promote_end_time"] && strtotime($params["promote_begin_time"]) > strtotime($params["promote_end_time"]) ){
				return $this->bad("促销时间不正确");
			}
			//$db->execute($sql, $code, $name, $spec, $categoryId, $unitId, $salePrice, $py, $id);
			//计算毛利
			$lastbuyprice = $params["lastBuyPrice"] ? $params["lastBuyPrice"] : $params["lastbuyprice"];
			if($params["promote_price"] > 0){
				$params["gross"] = round((($params["promote_price"] - $lastbuyprice) / $params["promote_price"]) * 100, 1);
			} else {
				$params["gross"] = round((($params["sale_price"] - $lastbuyprice) / $params["sale_price"]) * 100, 1);
			}
			$goodsdb->data($params)->save();
			//如果有供应商信息，则添加供应商
			$jsonStr = $params["jsonStr"];
			$suppliers = json_decode(html_entity_decode($jsonStr), true);
			//dump($suppliers);
			//删除原有的
			$supplier_goods_db = M("supplier_goods", "t_");
			$map = array(
				"goodsid" => $id

			);
			$supplier_goods_db->where($map)->delete();
			foreach ($suppliers["items"] as $key => $value) {
				if(!$value || !$value["supplierId"]){
					continue;
				}
				$data = array(
					"goodsid" => $id,
					"supplierid" => $value["supplierId"]
				);
				$supplier_goods_db->add($data);
			}
			//echo $goodsdb->getLastSql();
			//计量单位如果不存在，则新增一个
			$map = array(
				"name" => $unitId
			);
			$log = "编辑商品: 商品编码 = {$code}, 品名 = {$name}, 规格型号 = {$spec} ,价格{$params['sale_price']}, 计重{$params['bulk']}, 负库存{$params['oversold']}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品");
		} else {
			// 新增
			$act = "add";
			// 检查商品编码是否唯一
			$sql = "select count(*) as cnt from t_goods where code = '%s' ";
			$data = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [{$code}]的商品已经存在");
			}
			
			// 检查商品条码是否唯一
			$sql = "select count(*) as cnt from t_goods where barcode = '%s' ";
			$data = $db->query($sql, $barcode);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("条码为 [{$barcode}]的商品已经存在");
			}
			$idGen = new IdGenService();
			$id = $idGen->newId();
			$params["id"] = $id;
			$ps = new PinyinService();
			$py = $ps->toPY($name);
			
			$sql = "insert into t_goods (id, code, name, spec, category_id, unit_id, sale_price, py)
					values ('%s', '%s', '%s', '%s', '%s', '%s', %f, '%s')";
			//$db->execute($sql, $id, $code, $name, $spec, $categoryId, $unitId, $salePrice, $py);
			$result = $goodsdb->data($params)->add();
			$supplier_goods_db = M("supplier_goods", "t_");
			if($result){
				$jsonStr = $params["jsonStr"];
				$suppliers = json_decode(html_entity_decode($jsonStr), true);
				foreach ($suppliers["items"] as $key => $value) {
					if(!$value || !$value["supplierId"]){
						continue;
					}
					$data = array(
						"goodsid" => $id,
						"supplierid" => $value["supplierId"]
					);
					$supplier_goods_db->add($data);
				}
			}

			//库存也要新增
			$inv_data = array(
				"balance_count" => 0,
				"balance_money" => 0,
				"balance_price" => 0,
				"goods_id" => $id,
				"warehouse_id" => "17A72FFA-B3F3-11E4-9DEA-782BCBD7746B"
			);
			M("inventory")->add($inv_data);
			
			$log = "新增商品: 商品编码 = {$code}, 品名 = {$name}, 规格型号 = {$spec}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品");
		}
		//获取商品详情，传递给电商
		$this->updateShop($id, $act);
		return $this->ok($id);
	}

	/**
	 * 编辑商品（双单位，TU : Two Units)
	 */
	public function editGoodsTU($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$categoryId = $params["categoryId"];
		$unitId = $params["unitId"];
		$salePrice = $params["salePrice"];
		$purchaseUnitId = $params["purchaseUnitId"];
		$purchasePrice = $params["purchasePrice"];
		$psFactor = $params["psFactor"];
		
		if (floatval($psFactor) <= 0) {
			return $this->bad("采购/销售计量单位转换比例必须大于0");
		}
		
		$db = M();
		$sql = "select name from t_goods_unit where id = '%s' ";
		$data = $db->query($sql, $purchaseUnitId);
		if (! $data) {
			return $this->bad("采购计量单位不存在");
		}
		
		$sql = "select name from t_goods_unit where id = '%s' ";
		$data = $db->query($sql, $unitId);
		if (! $data) {
			return $this->bad("销售计量单位不存在");
		}
		$sql = "select name from t_goods_category where id = '%s' ";
		$data = $db->query($sql, $categoryId);
		if (! $data) {
			return $this->bad("商品分类不存在");
		}
		$goodsdb = M('goods','t_');
		if ($id) {
			// 编辑
			// 检查商品编码是否唯一
			$sql = "select count(*) as cnt from t_goods where code = '%s' and id <> '%s' ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [{$code}]的商品已经存在");
			}
			
			$ps = new PinyinService();
			$py = $ps->toPY($name);
			
			$sql = "update t_goods
					set code = '%s', name = '%s', spec = '%s', category_id = '%s', 
					unit_id = '%s', sale_price = %f, py = '%s',
					purchase_unit_id = '%s', purchase_price = %f, ps_factor = %f 
					where id = '%s' ";
			
			//$db->execute($sql, $code, $name, $spec, $categoryId, $unitId, $salePrice, $py, $purchaseUnitId, $purchasePrice, $psFactor, $id);
			$goodsdb->data($params)->save();
			$log = "编辑商品: 商品编码 = {$code}, 品名 = {$name}, 规格型号 = {$spec}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品");
		} else {
			// 新增
			// 检查商品编码是否唯一
			$sql = "select count(*) as cnt from t_goods where code = '%s' ";
			$data = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [{$code}]的商品已经存在");
			}
			
			$idGen = new IdGenService();
			$id = $idGen->newId();
			$ps = new PinyinService();
			$py = $ps->toPY($name);
			
			$sql = "insert into t_goods (id, code, name, spec, category_id, unit_id, sale_price, py,
					  purchase_unit_id, purchase_price, ps_factor)
					values ('%s', '%s', '%s', '%s', '%s', '%s', %f, '%s', '%s', %f, %f)";
			//$db->execute($sql, $id, $code, $name, $spec, $categoryId, $unitId, $salePrice, $py, $purchaseUnitId, $purchasePrice, $psFactor);
			$goodsdb->data($params)->add();
			$log = "新增商品: 商品编码 = {$code}, 品名 = {$name}, 规格型号 = {$spec}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品");
		}
		
		return $this->ok($id);
	}

	public function deleteGoods($params) {
		$id = $params["id"];
		$recover = $params["recover"];
		$db = M();
		$sql = "select code, name, spec, is_delete from t_goods where id = '%s' ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("选择的商品不存在");
		}
		$code = $data[0]["code"];
		$name = $data[0]["name"];
		$spec = $data[0]["spec"];
		$is_delete = $data[0]["is_delete"];
		if($is_delete == 1 && !$recover){
			return $this->bad("该商品已经被废弃了");
		}
		if($is_delete == 0 && $recover){
			return $this->bad("该商品并未被废弃");
		}
		// 判断商品的库存是否为0，不为0则不能废弃
		if(!$recover){
			$sql = "select balance_count from t_inventory where goods_id = '%s' ";
			$data = $db->query($sql, $id);
			$cnt = $data[0]["balance_count"];
			if ($cnt > 0) {
				return $this->bad("商品[{$code} {$name}]还有库存 ， 不能废弃");
			}
			$delete = 1;
		} else {
			$delete = 0;
		}
		
		/*
		$sql = "select count(*) as cnt from t_ws_bill_detail where goods_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品[{$code} {$name}]已经在销售出库单中使用了，不能废弃");
		}
		
		$sql = "select count(*) as cnt from t_inventory_detail where goods_id = '%s' ";
		$data = $db->query($sql, $id);
		$cnt = $data[0]["cnt"];
		if ($cnt > 0) {
			return $this->bad("商品[{$code} {$name}]在业务中已经使用了，不能废弃");
		}
		*/
		$sql = "update t_goods set is_delete = $delete where id = '%s' ";
		$db->execute($sql, $id);
		
		$log = "删除商品： 商品编码 = {$code}， 品名 = {$name}，规格型号 = {$spec}";
		$bs = new BizlogService();
		$bs->insertBizlog($log, "基础数据-商品");
		$this->updateShop($id, "update");
		return $this->ok();
	}

	public function queryData($queryKey, $querySupplierid = "", $goodsBulk="", $warehouseId="", $position="") {
		if ($queryKey == null) {
			$queryKey = "";
		}
		$supplierid_sql = "";
		$supplier_goods_arr = array(0);

		if($querySupplierid){
			/*
			$map = array(
				"supplierid" => $querySupplierid
			);
			$goods_id_list = M("supplier_goods", "t_")->where($map)->select();
			foreach ($goods_id_list as $key => $value) {
				$supplier_goods_arr[] = $value["goodsid"];
			}
			*/
			$supplierid_sql = "and g.id in (select goodsid from t_supplier_goods s where s.supplierid = '$querySupplierid' ) ";
		}
		$position_sql = "";
		if($position){
			$position_sql = "and g.id in (select code from t_position where position_id = '".$position."')";
		}
		$bulkSql = "";
		if($goodsBulk == "0" || $goodsBulk == "1"){
			$bulkSql = " and g.bulk = $goodsBulk ";
		}
		$sql = "select g.id, g.code, g.name,g.barCode, g.spec, g.bulk, u.name as unit_name, g.lastBuyPrice as last_buy_price 
				from t_goods g, t_goods_unit u 
				where (g.unit_id = u.id) $supplierid_sql $bulkSql $position 
				and (g.code like '%s' or g.name like '%s' or g.barcode like '%s') and g.is_delete=0 
				order by g.code 
				limit 100";
		$key = "%{$queryKey}%";
		//dump($sql);
		//exit;
		$data = M()->query($sql, $key, $key, $key);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["spec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["lastBuyPrice"] = $v["last_buy_price"];
			$result[$i]["barCode"]  = $v["barcode"];
			$result[$i]["position"] = $this->get_full_position($v["code"]);
			$result[$i]["bulk"] = $v["bulk"];
			$result[$i]["bulk_str"] = $v["bulk"] == 0 ? "计重":"计件";
			$result[$i]["unitNamePW"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitNamePW"] = "kg";
			}
			//如果有warehouse参数，则获取该仓库中的数量
			if($warehouseId){
				$map = array(
					"goods_id" => $v["id"],
					"warehouse_id" => $warehouseId
				);
				$d = M("inventory", "t_")->where($map)->find();
				$result[$i]["goodsCountBefore"] = $d ? $d["balance_count"] : 0;
			}
		}
		
		return $result;
	}
	public function queryDataForMOBILE($params){
		$queryKey = $params["queryKey"];
		if ($queryKey == null) {
			$queryKey = "";
		}
		$warehouse_sql = "";
		$supplier_goods_arr = array(0);

		if($warehouse_id = $params["warehouseId"]){
			$supplierid_sql = "and g.id in (select goods_id from t_inventory s where s.warehouse_id = '$warehouse_id') ";
		}
		$bulkSql = "";
		$goodsBulk = $params["goodsBulk"];
		if($goodsBulk == "0" || $goodsBulk == "1"){
			$bulkSql = " and g.bulk = $goodsBulk ";
		}
		$sql = "select g.id, g.code,barCode, g.name, g.spec, u.name as unit_name, g.lastBuyPrice as last_buy_price 
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id) $supplierid_sql $bulkSql 
				and (g.code like '%s' or g.name like '%s' or g.py like '%s') 
				order by g.code 
				limit 1";
		$key = "%{$queryKey}%";
		//dump($sql);
		//exit;
		$data = M()->query($sql, $key, $key, $key);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["barCode"] = $v["barCode"];
			$result[$i]["spec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["lastBuyPrice"] = $v["last_buy_price"];
			//获取库存
			$map = array(
				"goods_id" => $v["id"],
				//"warehouse_id" => $warehouse_id
			);
			//dump($map);
			$inv = M("inventory", "t_")->where($map)->find();
			$result[$i]["goodsCount"] = $inv["balance_count"];
			$map = array(
				"code" => $v["code"]
			);
			$ware = M("position", "t_")->where($map)->find();
			$position = M("position_category", "t_")->where(array('position_id' => $ware['id']))->find();
			$result[$i]["warehouseName"] = $position["name"];
		}
		
		return $result;
	}

	public function queryDataForPC($params){
		$queryKey = $params["queryKey"];
		if ($queryKey == null) {
			$queryKey = "";
		}
		$warehouse_sql = "";
		$supplier_goods_arr = array(0);

		if($warehouse_id = $params["warehouseId"]){
			/*
			$map = array(
				"supplierid" => $querySupplierid
			);
			$goods_id_list = M("supplier_goods", "t_")->where($map)->select();
			foreach ($goods_id_list as $key => $value) {
				$supplier_goods_arr[] = $value["goodsid"];
			}
			*/
			$supplierid_sql = "and g.id in (select goods_id from t_inventory s where s.warehouse_id = '$warehouse_id') ";
		}
		$bulkSql = "";
		$goodsBulk = $params["goodsBulk"];
		if($goodsBulk == "0" || $goodsBulk == "1"){
			$bulkSql = " and g.bulk = $goodsBulk ";
		}
		$sql = "select g.id, g.code, g.barcode, g.name, g.spec, u.name as unit_name, g.lastBuyPrice as last_buy_price 
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id) $supplierid_sql $bulkSql 
				and (g.code like '%s' or g.barcode like '%s' or g.name like '%s' or g.py like '%s') 
				order by g.code 
				limit 100";
		$key = "%{$queryKey}%";
		//dump($sql);
		//exit;
		$data = M()->query($sql, $key, $key, $key, $key);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["barCode"] = $v["barcode"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["spec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["lastBuyPrice"] = $v["last_buy_price"];
			//获取库存
			$map = array(
				"goods_id" => $v["id"],
				"warehouse_id" => $warehouse_id
			);
			//dump($map);
			$inv = M("inventory", "t_")->where($map)->find();
			$result[$i]["goodsCount"] = $inv["balance_count"];
			$map = array(
				"id" => $warehouse_id
			);
			$ware = M("warehouse", "t_")->where($map)->find();
			$result[$i]["warehouseName"] = $ware["name"];
		}
		
		return $result;
	}

	public function queryDataWithSalePrice($queryKey, $batch = false) {
		if ($queryKey == null) {
			$queryKey = "";
		}
		
		$sql = "select g.id, g.code, g.name, g.spec, g.bulk, u.name as unit_name, g.sale_price
				from t_goods g, t_goods_unit u
				where (g.unit_id = u.id)
				and (g.code like '%s' or g.barcode like '%s' or g.name like '%s' or g.py like '%s') 
				order by g.code 
				limit 100";
		$key = "%{$queryKey}%";
		$data = M()->query($sql, $key, $key, $key, $key);
		$result = array();
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["spec"] = $v["spec"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["salePrice"] = $v["sale_price"];
			$result[$i]["unitNamePW"] = $v["unit_name"];
			if($v["bulk"] == 0){
				$result[$i]["unitNamePW"] = "kg";
				if($batch){
					
				} else {
					$spec = str_replace("g", "", $v["spec"]);
					if(is_numeric($spec)){
						$result[$i]["salePrice"] = round(($spec / 1000) * $v["sale_price"], 2);
					}
				}
				
			}
		}
		
		return $result;
	}

	public function goodsListTU($params) {
		$categoryId = $params["categoryId"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		
		$db = M();
		$result = array();
		
		$sql = "select g.id, g.code, g.name, g.sale_price, g.spec,  
					g.unit_id, u.name as unit_name, u2.name as purchase_unit_name,
				    u2.id as purchase_unit_id, g.purchase_price, g.ps_factor
				 from t_goods g
				 left join t_goods_unit u
				 on g.unit_id = u.id 
				 left join t_goods_unit u2
				 on g.purchase_unit_id = u2.id
				 where (g.category_id = '%s') ";
		$queryParam = array();
		$queryParam[] = $categoryId;
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		$sql .= " order by g.code limit " . $start . ", " . $limit;
		$data = $db->query($sql, $queryParam);
		
		foreach ( $data as $i => $v ) {
			$result[$i]["id"] = $v["id"];
			$result[$i]["code"] = $v["code"];
			$result[$i]["name"] = $v["name"];
			$result[$i]["salePrice"] = $v["sale_price"];
			$result[$i]["spec"] = $v["spec"];
			$result[$i]["unitId"] = $v["unit_id"];
			$result[$i]["unitName"] = $v["unit_name"];
			$result[$i]["purchaseUnitId"] = $v["purchase_unit_id"];
			$result[$i]["purchaseUnitName"] = $v["purchase_unit_name"];
			$result[$i]["purchasePrice"] = $v["purchase_price"];
			$result[$i]["psFactor"] = $v["ps_factor"];
		}
		
		$sql = "select count(*) as cnt from t_goods g where (g.category_id = '%s') ";
		$queryParam = array();
		$queryParam[] = $categoryId;
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{$name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		$data = $db->query($sql, $queryParam);
		$totalCount = $data[0]["cnt"];
		
		return array(
				"goodsList" => $result,
				"totalCount" => $totalCount
		);
	}

	public function getGoodsInfo($id) {
		/*
		$sql = "select category_id, code, name, spec, unit_id, sale_price
				from t_goods
				where id = '%s' ";
		$data = M()->query($sql, $id);
		*/
		$data = $this->db->find($id);
		if ($data) {
			/*
			$result = array();
			$result["categoryId"] = $data[0]["category_id"];
			$result["code"] = $data[0]["code"];
			$result["name"] = $data[0]["name"];
			$result["spec"] = $data[0]["spec"];
			$result["unitId"] = $data[0]["unit_id"];
			$result["salePrice"] = $data[0]["sale_price"];
			
			return $result;
			*/
          
          //如果商品过了特价时间，把毛利改回正常毛利
			if($data["promote_price"] > 0 && date('Y-m-d H:i:s',time())>$data["promote_begin_time"] && date('Y-m-d H:i:s',time())<$data["promote_end_time"]){                
                $data["gross"] = round( (($data["promote_price"] - $data["lastbuyprice"]) / $data["promote_price"]) * 100, 1);
                $sql = "update t_goods set gross = '%s' where id='%i'";
                M()->execute($sql, $data["gross"], $data["id"]);
			} else {                
                $data["gross"] = round( (($data["sale_price"] - $data["lastbuyprice"]) / $data["sale_price"]) * 100, 1);
                $sql = "update t_goods set gross = '%s' where id='%i'";
                M()->execute($sql, $data["gross"], $data["id"]);
			}
//			if($data["promote_price"] == 0 || date('Y-m-d H:i:s',time())<$data["promote_begin_time"] || date('Y-m-d H:i:s',time())>$data["promote_end_time"]){
//                $data["gross"] = round( (($data["sale_price"] - $data["lastbuyprice"]) / $data["sale_price"]) * 100, 1);
//                $sql = "update t_goods set gross = '%s' where goods_id='%'";
//                M()->execute($sql, $data["gross"], $data["goods_id"]);
//			}
			//加入类别信息
			$map = array(
				"id" => $data["category_id"]
			);
			$category = M("goods_category", "t_")->where($map)->find();
			$data["category_name"] = $category["name"];
			//加入供应商信息
			$map = array(
				"goodsid" => $id
			);
			$suppliers = M("supplier_goods", "t_")->where($map)->order("id asc")->select();
			$suppliers_arr = array();
			$s_list = array();
			$i = 1;
			foreach ($suppliers as $key => $value) {
				$map = array(
					"id" => $value["supplierid"]
				);
				$s = M("supplier", "t_")->where($map)->find();
				$s["id"] = $i;
				$s["supplierId"] = $value["supplierid"];
				$s["supplierCode"] = $s["code"];
				$s["supplierName"] = $s["name"];
				$s["supplierAddress"] = $s["address"];
				$s["supplierContact01"] = $s["contact01"];
				$s["supplierTel01"] = $s["tel01"];
				$s["supplierMobile01"] = $s["mobile01"];
				$s_list[] = $s;
				$i++;
			}
			$data["s_list"] = $s_list;
			return $data;
		} else {
			return array();
		}
	}

	public function getGoodsInfoTU($id) {
		$sql = "select category_id, code, name, spec, unit_id, sale_price, 
				   purchase_unit_id, purchase_price, ps_factor
				from t_goods
				where id = '%s' ";
		$data = M()->query($sql, $id);
		if ($data) {
			$result = array();
			$result["categoryId"] = $data[0]["category_id"];
			$result["code"] = $data[0]["code"];
			$result["name"] = $data[0]["name"];
			$result["spec"] = $data[0]["spec"];
			$result["unitId"] = $data[0]["unit_id"];
			$result["salePrice"] = $data[0]["sale_price"];
			$result["purchaseUnitId"] = $data[0]["purchase_unit_id"];
			$result["purchasePrice"] = $data[0]["purchase_price"];
			$result["psFactor"] = $data[0]["ps_factor"];
			
			return $result;
		} else {
			return array();
		}
	}

	public function getGoodsInfoForShop($id){
		$data = $this->db->field("*")->find($id);
		if ($data) {
			$map = array(
				"id" => $value["unit_id"]
			);
			$unit_info = M("goods_unit", "t_")->where($map)->order("id asc")->find();
			$ret = array();
			$ret['erp_goods_name'] = $data["name"];
			$ret['goods_code'] = $data["code"];
			$ret['goods_sn'] = $data["number"];
			$ret['shop_price'] = $this->calc_shop_price($data["sale_price"], $data["spec"], $data["bulk"]);
			$ret['goods_origin'] = $data["place"];
			$ret['goods_brand'] = $data["brand"];
			$ret['goods_shelf_life'] = $data["life"];
			$ret['negative_inventory_sales'] = $data["oversold"];
			$ret['operation_mode'] = $data["mode"] == 1 ? "联营":"经销";
			$ret["goods_unit"] = $unit_info["name"];
			$ret["goods_status"] = $data["status"] == 1 ? "正常":'下架';
			$ret["goods_storage"] = $data["storage"] == 1 ? "需冷藏":"常温";
			$ret["same_spec_id"]  = $data["same_spec_id"];
			$ret["bulk"]  = $data["bulk"];
			$ret["spec"]  = $data["spec"];
			$ret["status"]  = $data["status"];
			$ret["oversold"]  = $data["oversold"];
			$ret["is_delete"]  = $data["is_delete"];
			$ret["promote_num"]  = $data["promote_num"];
			//促销同步
			if($data["promote_price"] > 0){
				$ret["is_promote"]  = 1;
				$ret["promote_start_date"] = strtotime($data["promote_begin_time"]) - 8 *3600;
				$ret["promote_end_date"] = strtotime($data["promote_end_time"])  - 8 *3600;
				$ret["promote_price"] = $this->calc_shop_price($data["promote_price"], $data["spec"], $data["bulk"]);
			} else {
				$ret["is_promote"]  = 0;
				$ret["promote_start_date"] = "";
				$ret["promote_end_date"] = "";
				$ret["promote_price"] = "";
			}
			//市场价同步
			$ret["market_price"] = $data["shop_price"];
			if(!$ret["market_price"] || $ret["market_price"] == "" || $ret["market_price"] == 0){
				$ret["market_price"] = round($ret['shop_price'] * 1.2, 2);
			}
			return $ret;
		} else {
			return array();
		}
	}

	public function updateShop($id, $act = "update"){
		$data = $this->getGoodsInfoForShop($id);
		$data["act"] = $act;
		$ret = $this->curlPost($url, $data);

	}

	public function calc_shop_price($price, $spec="", $bulk = 1){
		if($bulk == 1){
			$ret_price = $price;
		} else {
			if(strpos($spec, "g") > -1){
				$kg = str_replace("g", "", $spec);
				if(is_numeric($kg)){
					$ret_price = round(($price / 1000 ) * $kg, 2);
				} else {
					$ret_price = $price;
				}
			} else {
				$ret_price = $price;
			}
		}
		return $ret_price;
	}

	public function curlPost($url, $data,$showError=1){
		if(!$data){
			return false;
		}
		$url = MALL_PATH."goods_service.php";

		$ch = curl_init();
		$header = "Accept-Charset: utf-8;";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		//curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		$errorno=curl_errno($ch);
		if ($errorno) {
			$log = "更新商品: ".$data["erp_goods_name"]."CURL错误".$errorno;
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品更新到电商");
			return array('rt'=>false,'errorno'=>$errorno);
		}else{
			$js=json_decode($tmpInfo,1);
			//写入日志
			$log = "更新商品: ".$data["erp_goods_name"]."状态".json_encode($data).$tmpInfo;
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-商品更新到电商");
			if($js["error"] == 1){
				return false;
			}
			return true;
		}
	}

	public function getGoodsByBarCode($barCode){
		$map = array(
			"barcode" => $barCode
		);
		$goods_info = M("goods", "t_")->where($map)->find();
		//如果是散装条码
		if(!$goods_info && strlen($barCode) >= 15){
			//分析条码，然后获取到商品。
		}
	}

	//批量同步
	public function syn($limit = 20, $page){
		$db = M("goods");
		//获取总数首先
		$total_count = $db->count();
		//开始同步
		$map = array(
			"syn" => 0,
			"parent_cate_id" => array("exp", "isnull()")
		);
		//
		//$goods_list = $db->where($map)->limit($limit)->select();
		$goods_list = $db->query("select * from t_goods where syn=0 and isnull(parent_cate_id) limit $limit");
		dump($goods_list);
		$syn_goods_list = array();
		$syn_goods = array();
		foreach ($goods_list as $key => $data) {
			$syn_goods['erp_goods_name'] = $data["name"];
			$syn_goods['goods_code'] = $data["code"];
			$syn_goods['goods_sn'] = $data["number"];
			$syn_goods['shop_price'] = $this->calc_shop_price($data["sale_price"], $data["spec"], $data["bulk"]);
			$syn_goods['goods_origin'] = $data["place"];
			$syn_goods['goods_brand'] = $data["brand"];
			$syn_goods['goods_shelf_life'] = $data["life"];
			$syn_goods['negative_inventory_sales'] = $data["oversold"];
			$syn_goods['operation_mode'] = $data["mode"] == 1 ? "联营":"经销";
			$syn_goods["goods_unit"] = $data["unit_id"];
			$syn_goods["goods_status"] = $data["status"] == 1 ? "正常":'下架';
			$syn_goods["goods_storage"] = $data["storage"] == 1 ? "需冷藏":"常温";
			$syn_goods["same_spec_id"]  = $data["same_spec_id"];
			$syn_goods["spec"]          = $data["spec"];
			$syn_goods["id"]  = $data["id"];
			$syn_goods["bulk"]  = $data["bulk"];
			$syn_goods["status"]  = $data["status"];
			$syn_goods["oversold"]  = $data["oversold"];
			$syn_goods["goods_brand"]  = $data["brand"];
			$syn_goods["market_price"]  = $data["shop_price"];
			$syn_goods_list[] = $syn_goods;
		}
		$ms = new MallService();
		$act = "update";
		$ids = array();
		foreach ($syn_goods_list as $key => $value) {
			$value["act"] = "update";
			$ret = $this->curlPost($url, $value);
			$ids[] = $value["id"];
		}
		if(!$ids){
			$ids[] = 0;
		}
		$map = array(
			"id" => array("in", $ids)
		);
		$db->where($map)->setField("syn", 1);
		//记录日志
		$log = array(
			"rec_id" => 0,
			"userid" => $remark,
			"action" => "syn-goods-$act",
			"data"   => json_encode($syn_goods_list),
			"time"   => date("Y-m-d H:i:s", time()),
			"return" => json_encode($ret)
		);
		$ms->log($log);
		$return = array(
			"total" => $total_count,
			"current" => $limit * $page
		);
		if($ret && $ret["error"] == 0){
			//通信成功，则加入备注
			return $this->suc($return);
		} else {
			return $this->bad("与电商通信失败，请重试");
		}


	}


}