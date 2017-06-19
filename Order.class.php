<?php 
class Order {
	public $url;


	public function __construct(){
		$serverName = $_SERVER['SERVER_NAME'];
		$this->url = "http://218.206.109.225:8090/erp/Home/Api/box";
	}
	/*
	*@params 
	* SendType 上传类型 0 配送人员将货物存入机柜 1 客户将货物取走
	* IndentNo 订单号
	* CabinetNo 机柜编号
	* BoxNo 机箱编号
	* 
	*/
	public function SendBoxState($SendType, $IndentNo, $CabinetNo, $BoxNo, $kxmm){
		//根据类别判断方法
		if($SendType >= 0){
			$params = array(
				"sendtype" => $SendType,
				"ref" => $IndentNo,
				"cabinetno" => $CabinetNo,
				"boxno" => $BoxNo,
				"code" => $kxmm
			);
			$query_str = http_build_query($params);
			$url = $this->url. "?" .$query_str;
			$result = $this->httpGet($url);
			if($result){
				$ret = json_decode($result,1);
				if($ret["success"] == true){
					return 0;
				}
			}
			return 1;
		}
	}
	
	public function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_URL, $url);

		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}



}