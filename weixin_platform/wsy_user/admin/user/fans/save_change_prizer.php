<?php

header("Content-type:application/json; charset=utf-8"); 
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/common/utility_shop.php');
require_once(ROOT_DIR."mp/lib/LogOpe.php");  //日志
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

$user_id = $configutil->splash_new($_GET["user_id"]);
$group_id= intval($configutil->splash_new($_GET["group_id"]));
$query_update  = "update `wsy_mark.collage_group_order_t` set `lottery_user_id` = ".$user_id." where `id` = ".$group_id;

_mysql_query($query_update) or die("L72 query error : ".mysql_error());exit;
print_r($query_update);exit;
mysql_close($link);
echo json_encode(123);
//echo "<script>location.href='fans.php?customer_id=".$customer_id_en."&pagenum=".$pagenum."';</script>";
?>