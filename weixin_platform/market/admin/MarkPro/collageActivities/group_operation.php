<?php
header("Content-type: text/html; charset=utf-8"); 
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../../weixinpl/proxy_info.php');
require('../../../../weixinpl/function_model/collageActivities.php');
_mysql_query("SET NAMES UTF8");
$collageActivities = new collageActivities($customer_id);//自定义类


$op = $configutil->splash_new($_POST["op"]);
$group_id = $configutil->splash_new($_POST["group_id"]);
$group_status = $configutil->splash_new($_POST["group_status"]);
$refund_way = $configutil->splash_new($_POST["refund_way"]);
$operation_user = $_SESSION['username'];



switch($op){
case 'refund_all':
	 $query = "INSERT INTO collage_group_operation_log(
											customer_id,
											group_id,
											type,
											operation_user,
											createtime,
											isvalid
										) VALUES (
											".$customer_id.",
											".$group_id.",
											1,
											'".$operation_user."',
											now(),
											true
										)"; 

	_mysql_query($query) or die('Query failed:'.mysql_error());
		
	$result = $collageActivities->refund_all($group_id,$group_status,$customer_id,$refund_way);
	
	if($result['code'] == 10001 || $result['code'] == 30000 ){
		$query2 = "UPDATE collage_group_order_t SET refund_status=2 WHERE id=".$group_id;
			_mysql_query($query2) or die('Query2 failed:'.mysql_error());
	}
	if($result['code'] == 10002 ){
		$query3 = "UPDATE collage_group_order_t SET refund_status=3 WHERE id=".$group_id;
			_mysql_query($query3) or die('Query3 failed:'.mysql_error());
	}
	
	if($result['code'] >= 40000 ){
		
	}
	
	
	//返回结果
	echo json_encode($result);
	
	
break;

case 'finish':
	$query = "INSERT INTO collage_group_operation_log(
											customer_id,
											group_id,
											type,
											operation_user,
											createtime,
											isvalid
										) VALUES (
											".$customer_id.",
											".$group_id.",
											2,
											'".$operation_user."',
											now(),
											true
										)"; 

	_mysql_query($query) or die('Query failed:'.mysql_error());
	$finish_result = $collageActivities->collage_success_operate($customer_id, $group_id, 1);
	//返回结果
	echo json_encode($finish_result);
break;
case 'census':
	$census_result = 0;
	$census_result = $collageActivities->select_census($group_id,$customer_id);
	
	//返回结果
	echo json_encode($census_result);
break;
}


?>