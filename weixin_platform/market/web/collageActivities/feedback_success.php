<?php
header("Content-type: text/html; charset=utf-8");
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');
//头文件
require('../../../weixinpl/common/common_from.php');
 
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta content="telephone=no" name="format-detection">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
	    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link href="/weixinpl/mshop/css/collage_activity/reset.css" type="text/css" rel="stylesheet">
		<link href="/weixinpl/mshop/css/collage_activity/style.css" type="text/css" rel="stylesheet">
	</head>
	<body>
		<div class="success">
			<img class="imgbox" src="/weixinpl/mshop/images/collage_activity/gouzi.png">
			<p class="p01">反馈成功</p>
			<p class="p02">感谢您真诚的建议^^我们竭尽全力做到更好！</p>
			<p class="p03"><span class="limit-time">5</span>S秒后自动返回商城首页</p>
			<div class="btn_array">
				<div class="btn" onclick="toCollageIndex()"><p>返回拼团首页</p></div>
				<div class="btn" onclick="toPersonalCenter()"><p>返回个人中心</p></div>
			</div>			
		</div>
		<script src="/weixinpl/mshop/js/jquery-1.12.1.min.js"></script>
		<!--悬浮按钮-->
		<?php  include_once('../../../weixinpl/mshop/float.php');?>
		<!--悬浮按钮-->
	</body>
	<script>
		var limitTime = 5,
			customer_id_en = '<?php echo $customer_id_en;?>';
			
		//自动跳转商城首页
		var setIntervalId = setInterval(function(){
			if( limitTime > 0 ){
				limitTime--;
				$('.limit-time').text(limitTime);
			} else {
				clearInterval(setIntervalId);
				window.location.href = '/weixinpl/common_shop/jiushop/index.php?customer_id='+customer_id_en;
			}
		},1000);
		
		//返回拼团首页
		function toCollageIndex(){
			window.location.href = '';
		}
		
		//返回个人中心
		function toPersonalCenter(){
			window.location.href = '/weixinpl/mshop/personal_center.php?customer_id='+customer_id_en;
		}
	</script>
</html>
