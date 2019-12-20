<?php
header("Content-type: text/html; charset=utf-8");
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');
require_once('../../../weixinpl/function_model/systemFeedback.php');

$systemFeedback = new systemFeedback($customer_id);
 
$content = $configutil->splash_new($_POST['content']);
$name = $configutil->splash_new($_POST['name']);
$phone = $configutil->splash_new($_POST['phone']);
$is_anonymous = $configutil->splash_new($_POST['is_anonymous']);
$user_id = $configutil->splash_new($_POST['user_id']);

$values = array(
	'content' => "'".$content."'",
	'name' => "'".$name."'",
	'phone' => "'".$phone."'",
	'is_anonymous' => $is_anonymous,
	'user_id' => $user_id,
	'customer_id' => $customer_id,
	'isvalid' => true,
	'createtime' => 'now()'
);

$result = $systemFeedback->insert_system_feedback($values);

echo json_encode($result);
?>