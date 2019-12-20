<?php
header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../weixinpl/common/common_from.php');
require('../../../weixinpl/mshop/tax_function.php');			//行邮税方法

$pageNum = 0;//页数
if(!empty($_GET['pageNum'])){
	$pageNum = $configutil->splash_new($_GET['pageNum']);
}
$currtype= 1;
if(!empty($_GET["currtype"])){
	$currtype = $_GET["currtype"];
}
$start_time = '';
if(!empty($_GET["start_time"])){
	$start_time = $_GET["start_time"];
}
$end_time = '';
if(!empty($_GET["end_time"])){
	$end_time = $_GET["end_time"];
}
require('../../../market/web/collageActivities/my_collages_list_model.php');