<?php
header("Content-type: text/html; charset=utf-8");
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');
//头文件  调试请关闭此文件----start
require('../proxy_info.php');
require('../common/common_from.php');
require('select_skin.php');
//头文件  调试请关闭此文件----end

$pay_batchcode = $configutil->splash_new($_GET["pay_batchcode"]);
$batchcode = $configutil->splash_new($_GET["batchcode"]);
$http_host = $_SERVER['HTTP_HOST'];
$new_baseurl = Protocol.$http_host; //新商城图片显示
if( !empty($pay_batchcode) ){
	//根据支付订单号查订单号
	$query = "SELECT batchcode FROM weixin_commonshop_orders WHERE customer_id=".$customer_id." AND pay_batchcode='".$pay_batchcode."' AND isvalid=true GROUP BY batchcode";
	$result = _mysql_query($query) or die('Query failed:'.mysql_error());
	$batchcode = mysql_fetch_assoc($result)['batchcode'];
}

//获取订单信息
$query_crew = "SELECT group_id,activitie_id,is_head,status FROM collage_crew_order_t WHERE batchcode='".$batchcode."' AND customer_id=".$customer_id;
$result_crew = _mysql_query($query_crew) or die('Query_crew failed:'.mysql_error());
$crew_order = mysql_fetch_assoc($result_crew);
$group_id = $crew_order['group_id'];
$activitie_id = $crew_order['activitie_id'];
$is_head = $crew_order['is_head'];
$order_status = $crew_order['status'];

//获取团信息
$query_group = "SELECT status,success_num,join_num,pid,type,lottery_status FROM collage_group_order_t WHERE id='".$group_id."'";
$result_group = _mysql_query($query_group) or die('Query_group failed:'.mysql_error());
$group_order = mysql_fetch_assoc($result_group);
$group_status = $group_order['status'];
if(($group_status==3)&&($group_order['type']==7)){
    $group_status = 7;
}
$success_num = $group_order['success_num'];
$join_num = $group_order['join_num'];
$pid = $group_order['pid'];

//剩余开团人数
$rest_num = $success_num-$join_num;
$rest_num = $rest_num < 0? 0 : $rest_num;


//获取分享信息
$query_share = "SELECT name,default_imgurl,introduce FROM weixin_commonshop_products WHERE id=".$pid;
$result_share = _mysql_query($query_share) or die('Query_share failed:'.mysql_error());
$share_info = mysql_fetch_assoc($result_share);
$pname = $share_info['name'];
$default_imgurl = $share_info['default_imgurl'];
$introduce = $share_info['introduce'];

define("InviteUrl",Protocol.$http_host."/weixinpl/common_shop/jiushop/forward.php?customer_id=".$customer_id_en."&redirect_url=".urlencode(Protocol.$http_host."/market/web/collageActivities/activities_detail_view.php?group_id=".$group_id)."&exp_user_id=".$user_id."&customer_id=".$customer_id_en);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>完成支付</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<meta content="no" name="apple-touch-fullscreen">
		<meta name="MobileOptimized" content="320" />
		<meta name="format-detection" content="telephone=no">
		<meta name=apple-mobile-web-app-capable content=yes>
		<meta name=apple-mobile-web-app-status-bar-style content=black>
		<meta http-equiv="pragma" content="nocache">
		<link rel="stylesheet" href="css/collage_activity/common.css" />
		<link rel="stylesheet" href="css/collage_activity/new_file2.css" />
		<script type="text/javascript" src="./assets/js/jquery.min.js"></script>
		<style>
			.box p{margin-bottom: 0;}
			.box .det{color: #666666;margin-bottom: 80px;margin-top: 10px;font-size: 15px;}
		</style>
	</head>
	<style>
		.share_shadow{
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: #000;
			opacity: 0.6;
			z-index: 999;
			text-align: right;
		}
		.share_guide{
			width: 300px;
			margin-top: -70px;
			margin-right: 20px;
		}
	</style>
	<body>
	<?php
		if( $group_status == 1 && $is_head == 2 ){
			define("InviteUrl",Protocol.$http_host."/weixinpl/common_shop/jiushop/forward.php?customer_id=".$customer_id_en."&redirect_url=".urlencode(Protocol.$http_host."/market/web/collageActivities/activities_detail_view.php?group_id=".$group_id)."&exp_user_id=".$user_id);
            if($order_status==2){
	?>
		<div class="box">
			<img src="./images/collage_activity/icon9.png" />
			<p>恭喜您付款成功！</p>
		</div>
        <!-- 家慧说哪都要有share-btn，故删掉from_type判断 -->
		<button class="btn1 share-btn "><span>差<?php echo $rest_num;?>人，邀请好友参团</span> <img src="./images/collage_activity/icon2_white.png" /> </button>
		<button class="btn2">查看订单</button>
        <?php }elseif($order_status==1){?>
        <div class="box">
			<img src="./images/warning.png" />
			<p style="color: #000000;">待付款</p>
		</div>
		<button class="btn2">查看订单</button>
	<?php
        }
		}
		if( $group_status == 1 && $is_head == 1 ){
			define("InviteUrl",Protocol.$http_host."/weixinpl/common_shop/jiushop/forward.php?customer_id=".$customer_id_en."&redirect_url=".urlencode(Protocol.$http_host."/market/web/collageActivities/activities_detail_view.php?group_id=".$group_id)."&exp_user_id=".$user_id);
	?>
		<div class="box">
			<img src="./images/collage_activity/icon10.png" />
			<p>恭喜您发起成功！</p>
		</div>
        <!-- 家慧说哪都要有share-btn，故删掉from_type判断 -->
		<button class="btn1 share-btn "><span>差<?php echo $rest_num;?>人，邀请好友参团</span> <img src="./images/collage_activity/icon2_white.png" /> </button>
		<button class="btn2">查看订单</button>
        <?php }elseif($group_status == -1 && $is_head == 1){ ?>    
        <div class="box">
			<img src="./images/warning.png" />
			<p style="color: #000000;">待付款</p>
		</div>
		<button class="btn2">查看订单</button>           
	<?php
		}
		if( $group_status == 3 ){
	?>
		<div class="box">
			<img src="./images/collage_activity/icon11.png" />
			<p>恭喜您拼团成功！</p>
			<p class="det">我们会尽快为您发货！</p>
		</div>
		<button class="btn2">查看订单</button>
	<?php
		}
		if( $group_status == 4 ){
	?>
		<div class="box">
			<img src="./images/collage_activity/icon12.png" />
			<p>拼团成功~~等待抽奖！</p>
		</div>
		<button class="btn2">查看订单</button>
	<?php
		}
		if( $group_status == 5 ){
	?>
		<div class="box">
			<img src="./images/collage_activity/icon11.png" />
			<p>恭喜您成团成功！</p>
			<p class="det">我们会尽快为您发货！</p>
		</div>
		<button class="btn2">查看订单</button>
	<?php
		}
    if( $group_status == 7 ){
        ?>
        <div class="box">
            <img src="./images/collage_activity/icon12.png" />
            <p>拼团成功~~等待系统抽奖！</p>
        </div>
        <button class="btn2">查看订单</button>
        <?php
    }
    ?>

	<div class="share_shadow" style="display:none;">
		<img class="share_guide" src="./images/collage_activity/share-guide.png">
	</div>
	</body>
	<script>
		var customer_id_en = '<?php echo $customer_id_en;?>';
		var pay_batchcode = '<?php echo $pay_batchcode;?>';
		var batchcode = '<?php echo $batchcode;?>';
		var share_url = '<?php echo InviteUrl;?>';
		var share_url2 = '<?php echo InviteUrl;?>';
		var title = '<?php echo $pname;?>';
		var desc = '<?php echo mysql_escape_string($introduce);?>';
		var imgUrl = '<?php echo $new_baseurl.$default_imgurl;?>';
		var share_type = 4;

		$('.btn2').click(function(){
			if( pay_batchcode == '' ){
				window.location.href = "orderlist_detail.php?customer_id="+customer_id_en+"&batchcode="+batchcode;
			} else {
				window.location.href = "orderlist_detail.php?customer_id="+customer_id_en+"&pay_batchcode="+pay_batchcode;
			}

		})

		//分享
		$(".share-btn").click(function(){
			$(".share_shadow").show();
		})
		$(".share_shadow").click(function(){
			$(".share_shadow").hide();
		})
	</script>
	<?php require('../common/share.php'); ?>
	<script src="//res.wx.qq.com/open/js/jweixin-1.4.0.js"></script>
	<script type="text/javascript">
		//CRM21448
        window.onload=function(){
	        wx.miniProgram.getEnv(function(res) {
	        	//如果是小程序
	            if(res.miniprogram){
	            	$('.share_guide').css("margin-right","70px");
	            }
	        })
        };
	</script>
</html>