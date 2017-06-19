<?php

namespace Home\Controller;

use Think\Controller;
use Home\Service\UserService;
use Home\Service\BizConfigService;
use Home\Common\FIdConst;

/**
 * 业务设置Controller
 * @author 李静波
 *
 */
class BizConfigController extends Controller {

	public function index() {
		$us = new UserService();
		
		$this->assign("title", "业务设置");
		$this->assign("uri", __ROOT__ . "/");
		
		$this->assign("loginUserName", $us->getLoignUserNameWithOrgFullName());
		
		$dtFlag = getdate();
		$this->assign("dtFlag", $dtFlag[0]);
		
		if ($us->hasPermission(FIdConst::BIZ_CONFIG)) {
			$this->display();
		} else {
			redirect(__ROOT__ . "/Home/User/login");
		}
	}

	public function allConfigs() {
		if (IS_POST) {
			$bs = new BizConfigService();
			
			$this->ajaxReturn($bs->allConfigs());
		}
	}

	public function allConfigsWithExtData() {
		if (IS_POST) {
			$bs = new BizConfigService();
			
			$this->ajaxReturn($bs->allConfigsWithExtData());
		}
	}

	public function edit() {
		if (IS_POST) {
			$bs = new BizConfigService();
			
			$params = array(
					"9000-01" => I("post.value9000-01"),
					"9000-02" => I("post.value9000-02"),
					"9000-03" => I("post.value9000-03"),
					"9000-04" => I("post.value9000-04"),
					"9000-05" => I("post.value9000-05"),
					"1003-01" => I("post.value1003-01"),
					"2001-01" => I("post.value2001-01"),
					"2002-01" => I("post.value2002-01"),
					"2002-02" => I("post.value2002-02"),
					"2001-02" => I("post.value2001-02"),
					"2002-03" => I("post.value2002-03"),
					"2002-04" => I("post.value2002-04"),
					"10000-01" => I("post.value10000-01"),
					"10000-02" => I("post.value10000-02"),
					"10000-03" => I("post.value10000-03")

			);
			
			$this->ajaxReturn($bs->edit($params));
		}
	}
}