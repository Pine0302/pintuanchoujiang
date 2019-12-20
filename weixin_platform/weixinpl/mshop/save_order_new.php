<?php
header("Content-type: text/html; charset=utf-8");
require_once('../config.php');
require_once ROOT_DIR . 'weixinpl/common/common_ext.php';
require_once ROOT_DIR . 'weixinpl/common/utility_fun.php';
/* 判断是否pc端下单 */
$from_pc=i2post('from_pc',0);
if($from_pc){
	$customer_id 	= i2post('customer_id_en',0); 
}
require_once('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST, DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

$user_id_en = i2post('user_id_en', '0');
if($user_id_en) {
	$user_id = passport_decrypt($user_id_en);
} else {
	$user_id = $_SESSION["user_id_" . $customer_id];
}

//订单业务逻辑
require_once(ROOT_DIR . 'weixinpl/mshop/save_order.class.php');
$save_order = new save_order($customer_id, $user_id);
$save_order->main();

