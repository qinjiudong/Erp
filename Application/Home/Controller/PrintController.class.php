<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Common\FIdConst;
use Home\Service\WSBillService;
use Home\Service\SRBillService;
use Home\Service\PWBillService;

/**
 * 打印预览控制器
 * @author XH
 *
 */
class PrintController extends Controller {
	public function pwBillInfo(){
		$pwref = I("get.ref");
		$pw = new PWBillService();
		$map = array(
			"ref" => $pwref
		);
		$pwinfo = M("pw_bill", "t_")->where($map)->find();
		$pwInfo = $pw->pwBillInfo($pwinfo["id"]);
		//dump($pwInfo);
		$this->assign("info", $pwInfo);
		$this->display();
	}
}
