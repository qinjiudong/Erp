<?php
return array(
		'URL_CASE_INSENSITIVE' => false,
		'SHOW_ERROR_MSG' => true,
		'DB_TYPE' => 'mysql', // 数据库类型
		'DB_HOST' => getenv("MOPAAS_MYSQL22118_HOST") ? getenv("MOPAAS_MYSQL22118_HOST") : '127.0.0.1', // 服务器地址
		'DB_NAME' => getenv("MOPAAS_MYSQL22118_NAME") ? getenv("MOPAAS_MYSQL22118_NAME") : 'jyerp', // 数据库名
		'DB_USER' => getenv("MOPAAS_MYSQL22118_USER") ? getenv("MOPAAS_MYSQL22118_USER") : 'root', // 用户名
		'DB_PWD' => getenv("MOPAAS_MYSQL22118_PASSWORD") ? getenv("MOPAAS_MYSQL22118_PASSWORD") : 'root', // 密码
		'DB_PORT' => getenv("MOPAAS_MYSQL22118_PORT") ? getenv("MOPAAS_MYSQL22118_PORT") : 3306, // 端口
		'DB_PREFIX' => "t_",
		"SMS" => array(
			"sdk" => "13585065506",
			"code" => "tao88888jy",
			"query_url" => "http://yd.4001185185.com/sdk/smssdk!query.action",
			"send_url"  => "http://yd.4001185185.com/sdk/smssdk!mt.action",
			"rev_url"   => "http://yd.4001185185.com/sdk/smssdk!mo.action"
		),
		'WEIXIN2' => array(
			"wxname" => "大澄网",
			"TOKEN"  => "ybpigp1438245477",
			"APPID"  => "wx77edc9d45b9111e0",
			"APPSECRET" => "d185f9cb8b22b0deb2d233b18374afe5"
		),
		'WEIXIN' => array(
			"wxname" => "淘江阴",
			"TOKEN"  => "taojiangyin",
			"APPID"  => "wx0c4de67a6b3d2a3e",
			"APPSECRET" => "305ee59aba5f38fdebe9e72eadd1cbb5"
		),
		"BOX_SOAP_URL" => "http://218.206.109.225:8090/CarWebService/CardService.asmx?WSDL",
		"WEIXIN_ACCESS_TOKEN_URL" => "http://www.taojiangyin.com/mobile/index.php?m=default&c=wechat&a=access_token&orgid=gh_1d44330c7f85&appid=wx77edc9d45b9111e0"
);
