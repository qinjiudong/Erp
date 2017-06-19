<?php
ini_set("display_errors", "On");
error_reporting(E_ALL | E_STRICT);
$serverName = $_SERVER['SERVER_NAME'];
if(array_key_exists("action", $_GET)){
	$action = $_GET["action"];
} else {
	$action = "";
}

if(!$action){
	$action = "SendBoxState";
}

$soap = new SoapClient("http://www.psi.com/box_server.php?wsdl");
$param = array(
	"1",
	"120150920803934",
	"1",
	"10"
);
//var_dump($soap->__getFunctions());
try {
 
 $p = $soap->__soapCall($action,$param);
 print_r($p);
}catch (SoapFault $sf){

}  

	

?>