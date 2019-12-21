<?php

header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../weixinpl/proxy_info.php');
require('../../../weixinpl/common/common_from.php');
require('../../../weixinpl/function_model/collageActivities.php');
require('../../../weixinpl/mshop/select_skin.php');

$collageActivities = new collageActivities($customer_id);

$result = $collageActivities->lottery(4,$customer_id);
print_r($result);exit;

