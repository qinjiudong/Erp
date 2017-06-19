<?php

namespace Home\Service;

/**
 * 柜系统的服务
 *
 * @author dubin
 */
class BoxService extends ERPBaseService {

	//存货
	public function stock($params){
		$db = M("box", "t_");
		$cabinetno = $params["cabinetno"];
		$boxno = $params["boxno"];
		$order_ref = $params["ref"];
		$siteid = $params["siteid"];
		$sitename = $params["sitename"];
		$ref = $params["ref"];
		$time = time();
		$code = $params["code"];
		$status = 0;
		//首先查看订单是否存在
		$order_info = M("ws_bill", "t_")->where(array("ref"=>$ref))->find();
		if(!$order_info){
			return $this->bad("订单不存在");
		}
		$siteid = $order_info["siteid"];
		$sitename = $order_info["sitename"];
		$code = $code ? $code : $order_info["pick_code"];
		$map = array(
			"cabinetno" => $cabinetno,
			"boxno" => $boxno
		);
		$data = array(
			"cabinetno" => $cabinetno,
			"boxno"     => $boxno,
			"sitename"  => $sitename,
			"siteid"    => $siteid,
			"status"    => $status
		);
		//补充柜系统信息，并且更新状态
		if($db->where($map)->find()){
			$db->where($map)->save($data);
		} else {
			$db->add($data);
		}
		
		//加入柜存取日志
		$log = array(
			"ref" => $order_ref,
			"status" => $status,
			"time" => $time,
			"code" => $code
		);
		$this->box_log($log);
		return $this->ok();
	}

	//取货
	public function pick($params){
		$db = M("box", "t_");
		$cabinetno = $params["cabinetno"];
		$boxno = $params["boxno"];
		$order_ref = $params["ref"];
		$siteid = $params["siteid"];
		$sitename = $params["sitename"];
		$ref = $params["ref"];
		$time = time();
		$code = $params["code"];
		$status = 1;
		//首先查看订单是否存在
		$order_info = M("ws_bill", "t_")->where(array("ref"=>$ref))->find();
		if(!$order_info){
			return $this->bad("订单不存在");
		}
		//加入柜存取日志
		$log = array(
			"ref" => $order_ref,
			"status" => $status,
			"time" => $time,
			"code" => $code
		);
		$this->box_log($log);
		return $this->ok();
	}

	//日志记录
	public function box_log($params){
		$db = M("box_log", "t_");
		$db->add($params);
	}


	public function notify_box($params){
		if(is_array($params)){
			$order_ref = $params["ref"];
			$mobile    = $params["mobile"];
			$cardno    = $params["cardno"];
		} else {
			$order_info = M("ws_bill", "t_")->where(array("ref"=>$params))->find();
			$order_ref = $params;
			$mobile    = $order_info["tel"];
			$cardno    = $order_info["cardno"];
		}
		
		$action    = "AcceptOnlineData";
		//请求柜系统的soap接口
		$soap_url = C("BOX_SOAP_URL");
		$soap = new \SoapClient($soap_url);
		$param = array('DataInfo'=>"$order_ref|$mobile|$cardno");
		$p = $soap->__soapCall($action,array('parameters' => $param));
		$resultAction = $action."Result";
		$result = $p->$resultAction;
		//status=3表示上传订单信息，此时code表示接口回传信息
		$log = array(
			"ref" => $order_ref,
			"status" => 3,
			"time" => time(),
			"code" => $result,
			"data" => "$order_ref|$mobile|$cardno"
		);
		$this->box_log($log);
		return $this->ok();
	}
}