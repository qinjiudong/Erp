<?php

namespace Home\Service;

use Home\Service\IdGenService;
use Home\Service\BizlogService;

/**
 * 仓位档案Service
 *
 * @author 李静波
 */
class ShopService extends ERPBaseService {

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
	
	public function goods($params) {
		$shopcode = $params["shopcode"];
		$code = $params["code"];
		$name = $params["name"];
		$spec = $params["spec"];
		$is_delete = $params["is_delete"] ? 1 : 0;
		$page = $params["page"];
		$start = $params["start"];
		$limit = $params["limit"];
		$barcode = $params["barcode"];
		$db = M();
		$result = array();
		$sql = "select i.balance_count, g.basecode,g.packrate, g.id, g.code, g.name, g.sale_price, g.promote_price, g.bulk, g.spec, g.is_delete,  g.unit_id ,g.barcode,g.status,g.lastbuyprice,g.oversold,s.code as supplier_code,s.name as supplier_name from t_goods g left join t_inventory i on g.id = i.goods_id left join (select goodsid,supplierid from t_supplier_goods group by goodsid) as sg on sg.goodsid = g.id left join t_supplier s on s.id = sg.supplierid where g.is_delete = $is_delete and i.balance_count>0";
		$queryParam = array();
		if ($code) {
			$sql .= " and (g.code like '%s') ";
			$queryParam[] = "%{$code}%";
		}
		if ($name) {
			$sql .= " and (g.name like '%s' or g.py like '%s') ";
			$queryParam[] = "%{$name}%";
			$queryParam[] = "%{name}%";
		}
		if ($spec) {
			$sql .= " and (g.spec like '%s')";
			$queryParam[] = "%{$spec}%";
		}
		if($barcode){
			$sql .= " and (g.barcode like '%s')";
			$queryParam[] = "%{$barcode}%";
		}
		if($shopcode){
            if($shopcode === '1') {
              $shopcode = '17A72FFA-B3F3-11E4-9DEA-782BCBD7746B';
            }
			$sql .= " and (i.warehouse_id = '%s')";
			$queryParam[] = $shopcode;
		}
        
        if(!$shopcode && ($code || $name || $barcode)) {
			$sql .= " and group by warehouse_id";          
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
			if($v["promote_price"] > 0){
				$gross = round( (($v["promote_price"] - $v["lastbuyprice"]) / $v["promote_price"]) * 100, 1 );
				$result[$i]["salePrice"] = $v["promote_price"];
			} else {
				$gross = round( (($v["sale_price"] - $v["lastbuyprice"]) / $v["sale_price"]) * 100, 1);
				$result[$i]["salePrice"] = $v["sale_price"];
			}
			$result[$i]["gross"] = $gross;
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
		
		$sql = "select count(*) as cnt from t_inventory i left join t_goods g on g.id = i.goods_id where g.is_delete = $is_delete and i.balance_count>0";
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
		if($shopcode){
			$sql .= " and (i.warehouse_id = '%s')";
			$queryParam[] = $shopcode;
		}
		$data = $db->query($sql, $queryParam);
		$totalCount = $data[0]["cnt"];
		
		return array(
				"goodsList" => $result,
				"totalCount" => $totalCount
		);
	}
    
	public function goodsTransfer($params) {
      $code = $params['code'];
      $number = $params['number'];
      $inShopId = $params['inShopId'];
      $outShopId = $params['outShopId'];
      $outShopName = $params['outShopName'];
      $inShopName = $params['inShopName'];
      if(!$outShopId) {
		return $this->bad("请选择调出店铺");
      }
      if(!$inShopId) {
		return $this->bad("请选择调入店铺");
      }
      if($inShopId === $outShopId) {
		return $this->bad("调入跟调出不能为同一个店铺");
      }
	  $db = M();
      //调出店铺是仓库
      if($outShopId === '1') {
        //判断调出数量是否超限
		$sql = "select balance_count from t_inventory as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s' ";
		$data = $db->query($sql, $code);
        if(empty($data)) {
          return $this->bad("请输入正确的商品条码");
        }
        if($data[0]['balance_count']<$number) {
          return $this->bad("该商品在".$outShopName."中的库存不足".$number);
        }
        else{
          //判断调入店铺是否有该商品
          $sql = "select i.id, g.name from t_inventory_shop as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s' and i.shop_id='%s' ";
          $datas = $db->query($sql, $code,$inShopId);
          if(empty($datas)) {
            $sql = "select i.* from t_inventory as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s'";
            $data = $db->query($sql, $code);
            //调拨入库
			$sql = "insert into t_inventory_shop (balance_count, balance_money, balance_price, goods_id, in_count, in_money, in_price, out_count, out_money, out_price, in_count_2, in_money_2, in_price_2, out_count_2, out_money_2, out_price_2, balance_count_2, balance_money_2, balance_price_2, warehouse_id, shop_id) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ";
			$db->execute($sql, $number, $data[0]['balance_money'], $data[0]['balance_price'], $data[0]['goods_id'], $data[0]['in_count'], $data[0]['in_money'], $data[0]['in_price'], $data[0]['out_count'], $data[0]['out_money'], $data[0]['out_price'], $data[0]['in_count_2'], $data[0]['in_money_2'], $data[0]['in_price_2'], $data[0]['out_count_2'], $data[0]['out_money_2'], $data[0]['out_price_2'], $data[0]['balance_count_2'], $data[0]['balance_money_2'], $data[0]['balance_price_2'], $data[0]['warehouse_id'], $inShopId);
            //调拨出库
            $sql = "update t_inventory set balance_count = balance_count-'".$number."' where id = '%s' ";
            $db->execute($sql, $data[0]['id']);
          }
          else {
            $sql = "select i.id from t_inventory as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s'";
            $datass = $db->query($sql, $code);
            //调拨入库
            $sql = "update t_inventory_shop set balance_count = balance_count+'".$number."' where id = '%s' ";
            $db->execute($sql, $datas[0]['id']);
            //调拨出库
            $sql = "update t_inventory set balance_count = balance_count-'".$number."' where id = '%s' ";
            $db->execute($sql, $datass[0]['id']);
          }
          //调拨日志
          $log = "调拨商品: 从".$outShopName."调拨出库 ".$datas[0]['name']." ".$number."件，调拨入库".$inShopName;
          $bs = new BizlogService();
          $bs->insertBizlog($log, "调拨商品库存");
          return $this->ok($inShopId);
        }
      }
      elseif($inShopId === '1') {
        //判断调出数量是否超限
		$sql = "select balance_count,i.id from t_inventory_shop as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s' and i.shop_id='%s' ";
		$data = $db->query($sql, $code, $outShopId);
        if(empty($data)) {
          return $this->bad("请输入正确的商品条码");
        }
        if($data[0]['balance_count']<$number) {
          return $this->bad("该商品在".$outShopName."中的库存不足".$number);
        }
        else {
            $sql = "select i.id from t_inventory as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s'";
            $datass = $db->query($sql, $code);
            //调拨出库
            $sql = "update t_inventory_shop set balance_count = balance_count-'".$number."' where id = '%s' ";
            $db->execute($sql, $data[0]['id']);
            //调拨入库
            $sql = "update t_inventory set balance_count = balance_count+'".$number."' where id = '%s' ";
            $db->execute($sql, $datass[0]['id']);
            //调拨日志
            $log = "调拨商品: 从".$outShopName."调拨出库 ".$datas[0]['name']." ".$number."件，调拨入库".$inShopName;
            $bs = new BizlogService();
            $bs->insertBizlog($log, "调拨商品库存");
            return $this->ok($inShopId);
        }
      }
      else {
            //判断调出数量是否超限
            $sql = "select balance_count,i.id from t_inventory_shop as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s' and i.shop_id='%s' ";
            $data = $db->query($sql, $code, $outShopId);
            if(empty($data)) {
              return $this->bad("请输入正确的商品条码");
            }
            if($data[0]['balance_count']<$number) {
              return $this->bad("该商品在".$outShopName."中的库存不足".$number);
            }
            else {
              //判断调入店铺是否有该商品
              $sql = "select i.id, g.name from t_inventory_shop as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s' and i.shop_id='%s' ";
              $datas = $db->query($sql, $code,$inShopId);
              $sql = "select i.id, g.name from t_inventory_shop as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s' and i.shop_id='%s' ";
              $datas2 = $db->query($sql, $code,$outShopId);
              if(empty($datas)) {
                $sql = "select i.* from t_inventory_shop as i left join t_goods as g on g.id=i.goods_id where g.barCode = '%s' and i.shop_id='%s' ";
                $data = $db->query($sql, $code,$outShopId);
                //调拨入库
                $sql = "insert into t_inventory_shop (balance_count, balance_money, balance_price, goods_id, in_count, in_money, in_price, out_count, out_money, out_price, in_count_2, in_money_2, in_price_2, out_count_2, out_money_2, out_price_2, balance_count_2, balance_money_2, balance_price_2, warehouse_id, shop_id) values ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') ";
                $db->execute($sql, $number, $data[0]['balance_money'], $data[0]['balance_price'], $data[0]['goods_id'], $data[0]['in_count'], $data[0]['in_money'], $data[0]['in_price'], $data[0]['out_count'], $data[0]['out_money'], $data[0]['out_price'], $data[0]['in_count_2'], $data[0]['in_money_2'], $data[0]['in_price_2'], $data[0]['out_count_2'], $data[0]['out_money_2'], $data[0]['out_price_2'], $data[0]['balance_count_2'], $data[0]['balance_money_2'], $data[0]['balance_price_2'], $data[0]['warehouse_id'], $inShopId);
                //调拨出库
                $sql = "update t_inventory_shop set balance_count = balance_count-'".$number."' where id = '%s' ";
                $db->execute($sql, $datas2[0]['id']);
              }
              else {
                //调拨入库
                $sql = "update t_inventory_shop set balance_count = balance_count+'".$number."' where id = '%s' ";
                $db->execute($sql,$datas[0]['id']);
                //调拨出库
                $sql = "update t_inventory_shop set balance_count = balance_count-'".$number."' where id = '%s' ";
                $db->execute($sql,$datas2[0]['id']);
              }
              
              $log = "调拨商品: 从".$outShopName."调拨出库 ".$datas[0]['name']." ".$number."件，调拨入库".$inShopName;
              $bs = new BizlogService();
              $bs->insertBizlog($log, "调拨商品库存");
              return $this->ok($inShopId);
            }
      }
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
			
	public function shopinfos($pid) {
		$sql = "select * from t_shop 
				where id = '%s'";
		$data = M()->query($sql, $pid);
		return $data[0];
	}
	
	public function shopList() {
			$db = M();
			$sql = "select * from t_shop where is_delete=1 order by sort asc, code asc";
				$posList = $db->query($sql);
				$result = array();
				foreach($posList as $k => $v){
					$result[$k]["id"] = $v["id"];
					$result[$k]["name"] = $v["name"];
					$result[$k]["code"] = $v["code"];
				}
			return $result;
		
	}
	
	public function UserList() {
			$db = M();
			$sql = "select id,name,login_name from t_user";
				$posList = $db->query($sql);
				$result = array();
				foreach($posList as $k => $v){
					$result[$k]["id"] = $v["id"];
					$result[$k]["name"] = $v["name"];
					$result[$k]["login_name"] = $v["login_name"];
				}
			return $result;
		
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

	public function editShop($params) {
		$id = $params["id"];
		$code = $params["code"];
		$name = $params["name"];
		$address = $params["address"];
		$shopkeeper = $params["shopkeeper"];
		$remark = $params["remark"];
		$sort = intval($params["sort"]);
		//$categoryId = $params["categoryId"];
		$db = M();
		$sql = "select * from t_shop where id = '%s' ";
		$data = $db->query($sql, $id);
		if ($data) {
			// 编辑
			// 检查编码是否已经存在
			$sql = "select count(*) as cnt from t_shop where code = '%s'  and id <> '%s' and is_delete=1 ";
			$data = $db->query($sql, $code, $id);
			$cnt = $data[0]["cnt"];
			if ($cnt > 0) {
				return $this->bad("编码为 [$code] 的店铺已经存在");
			}
			//获取到fullname
			$fullname = '';
			$sql = "update t_shop 
					set code = '%s', name = '%s', remark = '%s', sort = '%s', 
					shopkeeper = '%s', address='%s'
					where id = '%s'  ";
			$db->execute($sql, $code, $name, $remark, $sort, $shopkeeper, $address, $id);
			$sql = "update t_warehouse set code='%s', name='%s' where id='%s'  ";
			$db->execute($sql, $code, $name, $id);
			
			$log = "编辑店铺：编码 = $code, 名称 = $name";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-店铺档案");
		} else {
			// 新增
			// 检查编码是否已经存在
			$sql = "select * from t_shop where code = '%s' and is_delete=1 ";
			$data = $db->query($sql, $code);
			if ($data) {
				return $this->bad("编码为 [$code] 的店铺已经存在");
			}
			$sql = "insert into t_shop (code, name, remark, sort, shopkeeper, created, address) 
					values ('%s','%s', '%s', '%s', '%s',  now(), '%s')  ";
			$db->execute($sql, $code, $name, $remark, $sort, $shopkeeper,$address);
			$sql = "select id from t_shop where code = '%s'";
			$_id = $db->query($sql, $code);
			$cnt = $data[0]["cnt"];            
			$sql = "insert into t_warehouse (id, code, inited, name) 
					values ('%s','%s', '1', '%s')  ";
			$db->execute($sql,$_id[0]["id"], $code, $name);
			$log = "新增店铺：编码 = {$code}, 名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-店铺档案");
		}
		return $this->ok($id);
	}

	public function deleteShop($params) {
		$id = $params["id"];
		$db = M();
		$sql = "select code, name from t_shop where id = '%s' and is_delete=1 ";
		$data = $db->query($sql, $id);
		if (! $data) {
			return $this->bad("要删除的店铺档案不存在");
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
		
		$db->startTrans();
		try {
			$sql = "update t_shop set is_delete=0 where id = '%s'  ";
			$db->execute($sql, $id);
            $db->execute("delete from t_warehouse where id = '%s' ", $id);
			$log = "删除店铺档案：编码 = {$code},  名称 = {$name}";
			$bs = new BizlogService();
			$bs->insertBizlog($log, "基础数据-店铺档案");
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