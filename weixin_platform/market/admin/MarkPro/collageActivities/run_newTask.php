<?php
/*
 * 执行拼团失败自动退款任务	
 */
	header("Content-type: text/html; charset=utf-8");
	require('../../../../weixinpl/config.php');
	require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
	require('../../../../weixinpl/proxy_info.php');
	require_once('../../../../weixinpl/function_model/collageActivities.php');
	require_once('../../../../weixinpl/common/utility_shop.php');
	require_once('../../../../weixinpl/common/utility.php');
	require_once('../../../../market/admin/MarkPro/collageActivities/newTask.php');
	$run_class = new run_class();
	$run_class ->run();
	class run_class
	{
		public function run(){
			$CollageTaskClass = new CollageTaskClass();
			$result = $CollageTaskClass->failed_collage_activities_return();//订单号，限制条数 
			if(!$result) {
				echo $result['errmsg'];
			}
		}
	}
?>