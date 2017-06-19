<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\GoodsService;
use Home\Common\FIdConst;
use Home\Service\BizConfigService;
use Home\Service\Home\Service;

/**
 * 商品Controller
 * @author 李静波
 *
 */
class GoodsController extends Controller {

	public function chengma() {
		header('Content-type: application/txt'); 
		header('Content-Disposition: attachment; filename="秤码.txt"'); 
		$db = M();
		$rs = $data = $db->query('select * from t_goods order by code');//语句可以适当加一些过滤条件
		$out = '';	//格式:00142,,,28.00,,,,0,,,1,1,,芒果掂多芒果西米露, 简单如: 编号、价格、保质期、记重商品、改价方式、商品名
		
		foreach ($rs as $v) {
		  $barcode = $v['barcode'];
		  if($barcode{0} == 5 && $barcode{1} == 1){
		  	$new_barcode = "";
		  	for($i = 0 ; $i < strlen($barcode) ; $i++){
		  		if($i > 1){
		  			$new_barcode.= $barcode{$i};
		  		}
		  	}
		  	//判断是否特价
		  	if($v["promote_price"] > 0){
		  		if(strtotime($v["promote_begin_time"]) < time() && time() < strtotime($v["promote_end_time"])){
		  			$v['sale_price'] = $v["promote_price"];
		  		}
		  	}
		  	$out .= $new_barcode . ',,,' . $v['sale_price'] . ',,,,0,,,' . $v['bulk'] . ',1,,' . $v['name'] . ",\r\n";
		  } else {
		  	$new_barcode = $barcode;
		  }
		  
		}
		$file_name = "Application/Runtime/Temp/chengma_" . rand(1000, 9999) . ".txt";
		file_put_contents($file_name, iconv("UTF-8", "GB2312//IGNORE", $out));
		readfile("$file_name"); return;
	}
	public function index() {
		$us = new UserService();
		
		$this->assign("title", "商品");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::GOODS)) {
			//$ts = new BizConfigService();
			//$this->assign("useTU", $ts->goodsUsesTwoUnits());
			
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function unitIndex() {
		$us = new UserService();
		
		$this->assign("title", "商品计量单位");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::GOODS_UNIT)) {
			$this->display();
		} else {
			redirect("Home/User/login");
		}
	}

	public function allUnits() {
		if (IS_POST) {
			$gs = new GoodsService();
			$this->ajaxReturn($gs->allUnits());
		}
	}

	public function editUnit() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"name" => I("post.name")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->editUnit($params));
		}
	}

	public function deleteUnit() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->deleteUnit($params));
		}
	}

	public function allCategories() {
		if (IS_POST ) {
			$gs = new GoodsService();
			$params = array(
					"code" => I("post.code"),
					"name" => I("post.name"),
					"spec" => I("post.spec")
			);
			$this->ajaxReturn($gs->allCategories($params));
		}
	}

	public function getCategoryTree(){
		if (IS_POST || IS_GET) {
			$gs = new GoodsService();
			$params = array(
					"code" => I("post.code"),
					"name" => I("post.name"),
					"spec" => I("post.spec")
			);
			$this->ajaxReturn($gs->getGoodsCategoryList($params));
		}
	}

	public function editCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"parentid" => I("post.parentId")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->editCategory($params));
		}
	}

	public function deleteCategory() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->deleteCategory($params));
		}
	}

	public function goodsList() {
		if (IS_POST || (IS_GET && I("request.act"))) {
			$params = array(
					"categoryId" => I("request.categoryId"),
					"code" => I("request.code"),
					"name" => I("request.name"),
					"spec" => I("request.spec"),
					"barcode" => I("request.barcode"),
					"is_delete" => I("request.is_delete"),
					"supplier_code" => I("request.supplier_code"),
					"page" => I("request.page"),
					"start" => I("request.start"),
					"limit" => I("request.limit")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->goodsList($params));
		}
	}

	public function editGoods() {
		if (IS_POST) {
			/*
			$params = array(
					"id" => I("post.id"),
					"categoryId" => I("post.categoryId"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"spec" => I("post.spec"),
					"unitId" => I("post.unitId"),
					"salePrice" => I("post.salePrice")
			);
			*/
			$posts = I("post.");
			$gs = new GoodsService();
			$this->ajaxReturn($gs->editGoods($posts));
		}
	}

	public function editGoodsTU() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"categoryId" => I("post.categoryId"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"spec" => I("post.spec"),
					"unitId" => I("post.unitId"),
					"salePrice" => I("post.salePrice"),
					"purchaseUnitId" => I("post.purchaseUnitId"),
					"purchasePrice" => I("post.purchasePrice"),
					"psFactor" => I("post.psFactor")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->editGoodsTU($params));
		}
	}

	public function deleteGoods() {
		if (IS_POST) {
			$params = array(
					"id" => I("post.id"),
					"recover" => I("post.recover")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->deleteGoods($params));
		}
	}

	public function queryData() {
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$querySupplierid = I("post.supplierid");
			$goodsBulk = I("post.bulk");
			$warehouseId = I("post.warehouseId");
			$positon     = I("post.position");
			$gs = new GoodsService();
			$this->ajaxReturn($gs->queryData($queryKey, $querySupplierid, $goodsBulk, $warehouseId, $position));
		}
	}

	public function queryDataForPC(){
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$warehouseId = I("post.warehouseId");
			$goodsBulk = I("post.bulk");
			$gs = new GoodsService();
			$queryData = array(
				"queryKey" => $queryKey,
				"warehouseId" => $warehouseId,
				"goodsBulk" => $goodsBulk
			);
			$this->ajaxReturn($gs->queryDataForPC($queryData));
		}
	}

	public function queryDataWithSalePrice() {
		if (IS_POST) {
			$queryKey = I("post.queryKey");
			$batch    = I("post.batch");
			$gs = new GoodsService();
			$this->ajaxReturn($gs->queryDataWithSalePrice($queryKey, $batch));
		}
	}
	
	// TU: Two Units 商品双单位
	public function goodsListTU() {
		if (IS_POST) {
			$params = array(
					"categoryId" => I("post.categoryId"),
					"code" => I("post.code"),
					"name" => I("post.name"),
					"spec" => I("post.spec"),
					"page" => I("post.page"),
					"start" => I("post.start"),
					"limit" => I("post.limit")
			);
			$gs = new GoodsService();
			$this->ajaxReturn($gs->goodsListTU($params));
		}
	}

	public function goodsInfo() {
		if (IS_POST) {
			$id = I("post.id");
			$gs = new GoodsService();
			$data = $gs->getGoodsInfo($id);
			$data["units"] = $gs->allUnits();
			$data["sameunits"] = $gs->allSameUnits();
			$status = array(
				array("id"=>"0","name"=>'尚未生效'),
				array("id"=>"1","name"=>'正常商品'),
			);
			$data["status_arr"] = $status;
			$this->ajaxReturn($data);
		}
	}

	public function goodsInfoTU() {
		if (IS_POST) {
			$id = I("post.id");
			$gs = new GoodsService();
			$data = $gs->getGoodsInfoTU($id);
			$data["units"] = $gs->allUnits();
			$this->ajaxReturn($data);
		}
	}

	public function batchSynGoods(){
		$page = $_REQUEST["page"];
		$limit = $_REQUEST["limit"];
		if(!$limit || !intval($limit)){
			$limit = 20;
		}
		if($page == 1){
			$data = array(
				"syn" => 0
			);
			//把所有商品都置为未同步
			//M("goods")->where(array("code" => array("neq", "")))->data($data)->save();
		}
		
		$gs = new GoodsService();
		return $this->ajaxReturn($gs->syn($limit));
	}
}
