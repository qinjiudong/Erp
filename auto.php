<?php
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');
function httpGet($url) {
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
$h = intval(date("H"));
for ($i = 0; $i < 1; $i++) {
	if($h >= 17){
		$result = json_decode(httpGet("http://127.0.0.1:8090/erp/Home/Api/autoAcceptance"), 1);
	}
	if($h < 6 || $h > 17){
		$result2 = json_decode(httpGet("http://127.0.0.1:8090/erp/Home/Auto/dealReportByDetail"), 1);
	}
	
if($result2["success"] == false && $result["success"] == false){
	sleep(5);
	continue;
} else {
	sleep(1);
}

}
 echo "<script language=JavaScript> location.replace(location.href);</script>";