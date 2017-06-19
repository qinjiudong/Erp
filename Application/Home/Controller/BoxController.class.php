<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Common\BoxService;

/**
 * 柜系统模块的控制器
 * @author dubin
 *
 */
class BoxController extends Controller {

	public function index(){
		$params = array(
			"sendtype" => intval(I("request.sendtype")),
			"ref" => I("request.ref"),
			"cabinetno" => I("request.cabinetno"),
			"boxno" => I("request.boxno")
		);
		$box = new BoxService();
		if($params["sendtype"] === 0){
			$this->ajaxReturn($box->stock($params));
		} else if($params["sendtype"] === 1){
			$this->ajaxReturn($box->pick($params));
		}
		
	}
	
}