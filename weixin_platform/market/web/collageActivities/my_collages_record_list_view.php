<?php
header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../weixinpl/common/common_from.php');
require('../../../weixinpl/function_model/collageActivities.php');
require('../../../weixinpl/mshop/select_skin.php');
require('../../../weixinpl/proxy_info.php');
$collageActivities = new collageActivities($customer_id);

$status = 0;
if( $_COOKIE['collges_status'] ){
	$status = $_COOKIE['collges_status'];
	setcookie('collges_status');
}

$type = 0;
if( $_COOKIE['collges_type'] ){
	$type = $_COOKIE['collges_type'];
	setcookie('collges_type');
}
$start_time = '';
if( $_COOKIE['start_time'] ){
	$start_time = $_COOKIE['collges_start_time'];
	setcookie('start_time');
}
$end_time = '';
if( $_COOKIE['end_time'] ){
	$end_time = $_COOKIE['collges_end_time'];
	setcookie('end_time');
}


$filed = "cp.name AS pname,cp.default_imgurl,got.id as group_id,got.price,got.success_num,wu.weixin_name,wu.name AS uname,got.status,cot.totalprice,cot.batchcode,cot.status as cstatus,cot.is_head,cot.is_refund,got.is_win,cot.id as cid,got.join_num,got.endtime,got.pid,got.type,ae.type_name,cso.recovery_time as is_paytime";
$condition = array(
	'got.customer_id' => $customer_id,
    'ae.customer_id' => $customer_id,
    'ae.isvalid' => true,
	'cot.isvalid' => true,
	'status' => '-1',
	'paystyle' => '-1',
	'wco.status_1' => '-1',
	'cot.user_id' => $user_id,
	'cot.is_head' => 2
);
if( $status > 0 ){
	if( 3 == $status ){//中奖的
		$condition['got.is_win'] = 1;
	}elseif( 2 == $status ){//拼团失败，没有退款
		$condition['got.status'] = $status;
		$condition['cot.is_refund'] = 0;
		$condition['cot.status2'] = 6;
	}elseif(  4 == $status  ){//拼团成功
		$condition['cot.status'] = '5';
		$condition['status2'] = '(3,4)';
	}elseif(  5 == $status  ){//未付款
		$condition['cot.status'] = 1;
	}else{
		if( $status == 1 ){//非退款
			$condition['cot.status1'] = '6';
		}
		$condition['got.status'] = $status;
	}
}
if( !empty( $start_time ) ){
	$condition['start_time'] = $start_time;
}
if( !empty( $end_time ) ){
	$condition['end_time'] = $end_time;
}
if( 1 == $type ){
	$condition['cot.is_head'] = 1;
}
$jcondition = array(
	'got.customer_id' => $customer_id,
    'ae.customer_id' => $customer_id,
    'ae.isvalid' => true,
	'cot.isvalid' => true,
	'cot.is_head' => 2,
	'status' => '-1',
	'wco.status_1' => '-1',
	'cot.user_id' => $user_id
);
$jcount = $collageActivities->get_user_crew_order($jcondition,'count(1) as jcount');//参团数量
$ccondition = array(
	'got.customer_id' => $customer_id,
    'ae.customer_id' => $customer_id,
    'ae.isvalid' => true,
	'cot.isvalid' => true,
	'cot.is_head' => 1,
	'status' => '-1',
	'wco.status_1' => '-1',
	'cot.user_id' => $user_id
);
$ocount = $collageActivities->get_user_crew_order($ccondition,'count(1) as ocount');//开团数量
$condition['LIMIT'] = '0,15';
$list = $collageActivities->get_user_crew_order($condition,$filed);
define("InviteUrl",Protocol.$http_host."/market/web/collageActivities/my_collages_record_list_view.php?customer_id=");
$linkurl =InviteUrl.$customer_id_en;
?>

<!DOCTYPE html>
<html>
<head>
	<title>我的拼团记录</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta content="no" name="apple-touch-fullscreen">
	<meta name="MobileOptimized" content="320" />
	<meta name="format-detection" content="telephone=no">
	<meta name=apple-mobile-web-app-capable content=yes>
	<meta name=apple-mobile-web-app-status-bar-style content=black>
	<meta http-equiv="pragma" content="nocache">
	<link rel="stylesheet" href="/weixinpl/mshop/css/style.css" />
	<link rel="stylesheet" type="text/css" href="/weixinpl/mshop/css/daterangepicker.css">
	<link href="/weixinpl/mshop/css/mobiscroll.custom-2.6.2.min.css" rel="stylesheet" type="text/css">
	<link rel="stylesheet" href="/weixinpl/mshop/css/goods/my_collages_record_list_view.css" />
	<link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/css_<?php echo $skin ?>.css" />
	<link rel="stylesheet" href="/weixinpl/mshop/css/order_css/global.css" />
	<script type="text/javascript" src="/weixinpl/mshop/assets/js/jquery.min.js"></script>
	<script src="/weixinpl/mshop/js/r_global_brain.js" type="text/javascript"></script>
	<script src="/weixinpl/mshop/js/global.js" type="text/javascript"></script>
	<script src="/weixinpl/mshop/js/r_pinterest.js" type="text/javascript"></script>
	<script src="/weixinpl/mshop/js/mobiscroll.custom-2.6.2.min.js" type="text/javascript"></script>

	<style>
		.box2,.box,.box3{
			position: fixed;
			left: 0;
			z-index: 999;
		}
		.box2{
			top: 0;
		}
		.box{
			top: 37px;
		}
		.box3{
			top: 84px;
		}
		.date-picker-wrapper{position: fixed;z-index: 999;}
		#daterangepickeript{width: 185px;margin-top: 3.5px;}

	</style>
</head>
<body>
	<div style="height:126px;"></div>
	<div class="box2">
		<button <?php if( empty( $type ) ){echo 'class="box2Left skin-bg"';}else{echo 'class="box2Right"';} ?> data-type='0'>我参团(<?php echo $jcount['data'][0]['jcount']; ?>)</button>
		<button <?php if( $type == 1 ){echo 'class="box2Left skin-bg"';}else{echo 'class="box2Right"';} ?> data-type='1'>我开团(<?php echo $ocount['data'][0]['ocount']; ?>)</button>
	</div>

	<div class="box">
		<!-- 原来的日期插件需要选择两次 -->
		<!-- <input data-time="start_time" class="select_time" id="start_time" />
		<label data-time="start_time" class="select_time">
			<img src="img/icon8.png" />
		</label>
		<span>至</span>
		<input data-time="end_time" class="select_time" id="end_time"/>
		<label data-time="end_time" class="select_time">
			<img src="img/icon8.png" />
		</label> -->
		<!-- 换成双日历插件 -->
		<img src="img/icon8.png"/>
		<input type="text" disalbed readonly id="daterangepickeript" value="" />
		<input type="hidden" id="start_time" name="start_time" value=""/>
		<input type="hidden" id="end_time" name="end_time" value=""/>
		<img src="img/icon21.png" />
		<button onclick="searchData()">搜索</button>
	</div>

	<div class="box3">
		<div class="box3C" data-status='0' style="width: 16%;">
			<p <?php if( empty( $status ) ){echo 'class="box3CSelected skin-color skin-bd"';} ?>>全部</p>
		</div>
		<div class="box3C" data-status='4' style="width: 16%;">
			<p <?php if( $status == 4 ){echo 'class="box3CSelected skin-color skin-bd"';} ?>>成团</p>
		</div>
		<!-- <div class="divide"></div> -->
		<div class="box3C" data-status='1' style="width: 16%;">
			<p <?php if( $status == 1 ){echo 'class="box3CSelected skin-color skin-bd"';} ?>>进行</p>
		</div>
		<div class="box3C" data-status='5' style="width: 20%;">
			<p style="width: 60%;"<?php if( $status == 5 ){echo 'class="box3CSelected skin-color skin-bd"';} ?>>未付款</p>
		</div>
		<!-- <div class="divide"></div> -->
		<div class="box3C" data-status='2' style="width: 16%;">
			<p <?php if( $status == 2 ){echo 'class="box3CSelected skin-color skin-bd"';} ?>>失败</p>
			</div>
		<!-- <div class="divide"></div> -->
		<div class="box3C" data-status='3' style="width: 16%;">
			<p <?php if( $status == 3 ){echo 'class="box3CSelected skin-color skin-bd"';} ?>>中奖</p>
		</div>
	</div>

	<div class="content">
		<?php
			foreach($list['data'] as $key => $val){

            $coefficient = -1;
            $query1 = "SELECT coefficient FROM collage_bbt_order_extend WHERE batchcode='".$val['batchcode']."'";
            $result1 = _mysql_query($query1) or die('Query1 failed:'.mysql_error());
            $coefficient = mysql_fetch_assoc($result1)['coefficient'];
		?>
		<div class="line">
			<div class="time">
				<?php if( 1 == $val['status'] ){ ?>
				<p class="timeP1 countdown" data-id="<?php echo $val['cid']; ?>" data-end="<?php echo strtotime($val['endtime']); ?>">剩余时间：<span id="time_<?php echo $val['cid']; ?>"></span></p>
				<?php } ?>
				<?php
					if( $val['is_win'] == 1 ){
						echo '<span class="timeS2">恭喜中奖</span>';
					}else{
						if( $val['cstatus'] == 3 && $val['is_refund'] == 1 ){
							echo '<span class="timeS1">待退款...</span>';
						}else if( $val['cstatus'] == 6 ){
							echo '<span class="timeS1">已退款...</span>';
						}else{
							switch($val['status']){
								case 1:
									if($val['is_paytime'] != null)
									{
										echo '<span class="timeS1">拼团中(待付款)</span>';
									}else{
										echo '<span class="timeS1">拼团中...</span>';
									}

								break;
								case 2:
                                    if($val['is_win'] == 2){
                                        echo '<span class="timeS3">很遗憾，没能中奖</span>';
                                    }else{
                                        echo '<span class="timeS3">拼团失败</span>';
                                    }
								break;
								case 3:
									echo '<span class="timeS2">拼团成功</span>';
								break;
								case 4:
									echo '<span class="timeS1">待抽奖...</span>';
								break;
								case 5:
									echo '<span class="timeS1">成团成功</span>';
								break;
								case 6:
									echo '<span class="timeS1">成团失败</span>';
								break;
								case -1:
									echo '<span class="timeS1">拼团中(待付款)</span>';
								break;
							}
						}

					}
				?>
			</div>
			<div class="content1" onclick="gotoProduct(<?php echo $val['group_id']; ?>)">
				<div class="line-img">
					<img class="detImg" src="
                    <?php
                    if(empty($val['default_imgurl'])){
                        $query6 = "select imgurl from weixin_commonshop_product_imgs where isvalid=true and customer_id=".$customer_id." and product_id=".$val['pid']." limit 1";

                        $result6 = _mysql_query($query6) or die('query failed6'.mysql_error());
                        while($row6 = mysql_fetch_object($result6)){
                            $product_default_imgurl = $row6->imgurl;	//商品封面图
                        }
                        echo $product_default_imgurl;
                    }else{
                        echo $val['default_imgurl'];
                    }
                    ?>" />
					<span class="cell_icon">
					<?php
                        echo $val['type_name'];
					?>
					</span>
				</div>

				<div class="detTitle">
					<p class="order5"><?php echo $val['pname']; ?></p>
					<p class="order6">发起人：<?php echo $val['uname']; ?>|<?php echo $val['weixin_name']; ?></p>
                    <?php if($coefficient>0){?>
                        <p class="order7"><?php echo $val['type_name'];?> X<?php echo $coefficient;?></p>
                    <?php }?>
					<p class="order8">
						<span class="t2"><?php if(OOF_P != 2) echo OOF_S ?>&nbsp;<?php echo $val['price']; ?>&nbsp;<?php if(OOF_P == 2) echo OOF_S ?></span>
						<span class="t1"><?php echo $val['success_num']; ?>人团</span>
					</p>
				</div>
			</div>

			<div class="orderState">
				<p class="osPrice">实付款<?php if(OOF_P != 2) echo OOF_S ?>&nbsp;<span class="t1"><?php echo $val['totalprice']; ?></span>&nbsp;<?php if(OOF_P == 2) echo OOF_S ?><span class="t2">  (免运费)</span></p>
			</div>


            <div class="all_Btn">
				<div class="osBtn">
				<?php
				switch($val['status']){
					case 1:
				?>
						<?php if(  $val['is_head'] != 1 && $val['cstatus'] == 2 ){ ?>
							<button class="osBtn1" onclick="gotoRefunds('<?php echo $val['batchcode']; ?>')">申请退款</button>
							<div class="osBtnL skin-bd skin-color">还差<?php echo $val['success_num'] - $val['join_num']; ?>人</div>
							<div class="osBtnR skin-bg" onclick="invite(<?php echo $val['group_id']; ?>)">邀请朋友</div>
						<?php
						}elseif( $val['cstatus'] == 3 || $val['cstatus'] == 6  ){
						?>
							<!--button class="osBtn1" onclick="gotoOrder('<?php echo $val['batchcode']; ?>')">订单详情</button-->
						<?php
						}else{
						?>
						<div class="osBtnL skin-bd skin-color">还差<?php echo $val['success_num'] - $val['join_num']; ?>人</div>
						<div class="osBtnR skin-bg" onclick="invite(<?php echo $val['group_id']; ?>)">邀请朋友</div>
						<?php } ?>
					<?php
						break;
					?>
					<?php
						case 4:
					?>
							<!--<button class="osBtn2" onclick="gotoJoinGroup()">更多拼团</button>-->
					<?php
						break;
						case 3:
					?>
					<!--button class="osBtn1" onclick="gotoOrder('<?php echo $val['batchcode']; ?>')">订单详情</button-->
					<?php
						break;
						case 5:
					?>
					<!--button class="osBtn1" onclick="gotoOrder('<?php echo $val['batchcode']; ?>')">订单详情</button-->
					<?php
						break;
						case 6:
					?>
					<!--button class="osBtn1" onclick="gotoOrder('<?php echo $val['batchcode']; ?>')">订单详情</button-->
					<?php
						break;
						case 2:
					?>
					<button class="osBtn1" onclick="gotoMoneybag()">查看零钱</button>
					<?php
						break;
					?>
			<?php
				}
			?>
				<button class="osBtn1" onclick="gotoOrder('<?php echo $val['batchcode']; ?>')">订单详情</button>
				</div>
			</div>

		</div>
		<?php } ?>
	</div>
</body>
<script type="text/javascript" src="/weixinpl/mshop/js/moment.min.js"></script>
<script type="text/javascript" src="./js/jquery.daterangepicker.min.js"></script>
<script>
	var customer_id 	= '<?php echo $customer_id; ?>';
	var customer_id_en  = '<?php echo $customer_id_en; ?>';
	var user_id  		= '<?php echo $user_id; ?>';
	var type			= <?php echo $type; ?>;//搜索类型
	var start_time		= '<?php echo $start_time; ?>';//开始时间
	var end_time		= '<?php echo $end_time; ?>';//结束时间
	var status			= <?php echo $status; ?>;//搜索状态
	var downFlag	   = false;	// 是否加载全部
	var	pageNum 	   = 0;	// 起始页码
	var	isLock 	       = false;// 是否继续加载
	var pagesize       = 15;//每页数据数量
	var timeInterval   = new Array();//定时器对象数组
	var runtimes 	   = 0;
	var text_v 		   = '请选择时间';

    var share_url = '<?php echo InviteUrl;?>';
    var title = '我的拼团记录';
    var desc = '我的拼团记录';
    var imgUrl = '<?php echo Protocol.$_SERVER['HTTP_HOST']?>/weixinpl/common/images_V6.0/contenticon/is_head.png';

	$(function(){
		deal_cookie();	//处理本地缓存
		countdown();
		$(".box2 button").click(function(){
			$(this).removeClass("box2Right").addClass("box2Left skin-bg");
			$(this).siblings(".box2Left").removeClass("box2Left skin-bg").addClass("box2Right");
			type = $(this).data('type');
			search_ajax_data(type,start_time,end_time,status,get_search_data,1);
		})

		$(".box3C").click(function(){
			$(this).children("p").addClass("box3CSelected skin-color skin-bd");
			$(this).siblings(".box3C").children("P").removeClass("box3CSelected skin-color skin-bd");
			status = $(this).data('status');

			search_ajax_data(type,start_time,end_time,status,get_search_data,1);
		})

		$('.select_time').scroller($.extend({preset : 'date'},{
			theme: 'android-ics light',
			mode: 'scroller',
			display: 'modal',
			lang: 'zh',
			onSelect: function(textVale,inst){
				var time = $(this).data('time');
				$('#'+time).val(textVale);
			},
			onClose:function(textVale,inst){
				var time = $(this).data('time');
				$('#'+time).val(textVale);
			}
		}));
	});



	function searchData(){
		start_time = $('#start_time').val();
		end_time = $('#end_time').val();
		if( start_time != '' && end_time != '' && end_time < start_time){
			showAlertMsg('提示','开始时间必须小于结束时间','知道了');
			return;
		}
		search_ajax_data(type,start_time,end_time,status,get_search_data,1);
	}

	addEvent(window, "scroll", function(){
		if (document.body.scrollHeight-getViewPortSize().y <= getScrollOffsets().y+2){
			if(!downFlag){//如果没有全部加载完毕，显示loading图标
				if(pinterest_current>=pinterest_totalItem){//一次数据加载完毕
					pageNum++;
					search_ajax_data(type,start_time,end_time,status,get_search_data,0);//默认加载数据
				}else{
					pinterestInit(pinterestObj,true);
				}
			}else {
				pinterDone();
			}
		}
	});
	//计时器函数
	function GetRTime2(end_time,id){
		var now_time=new Date().getTime().toString().substring(0,10);
		timeval = setInterval(function(){
			$.ajax({type:"HEAD",url:window.location.href,complete:function(x){ now_time = new Date(x.getResponseHeader("Date")).getTime().toString().substring(0,10);}})//获取服务器时间

				if(now_time < end_time){
					surplus_time = end_time - now_time;
					var nMS = surplus_time*1000-runtimes*1000;
					var nD  = Math.floor(nMS/(1000*60*60*24));
					var nH  = Math.floor(nMS/(1000*60*60))%24;
					var nM  = Math.floor(nMS/(1000*60)) % 60;
					var nS  = Math.floor(nMS/1000) % 60;
					if( nH < 10 ){
						nH = '0'+nH;
					}
					if( nM < 10 ){
						nM = '0'+nM;
					}
					if( nS < 10 ){
						nS = '0'+nS;
					}
					var time = nD+' 天 '+nH+'：'+nM+'：'+nS;
					$("#time_"+id).html(time);
				}
				if(now_time==0){ //没有网络的情况下无法获取当前时间
					$("#time_"+id).html("您的网络异常");
				}
		},1000);
		timeInterval.push(timeval);//储存定时器ID
	}
	function search_ajax_data(type,start_time,end_time,status,callback,is_reset){
		/*
		函数说明：ajax获取数据
		*/
		if(is_reset){
			_reset();
		}
		if (isLock ==false &&  downFlag ==false){			//上锁或者数据加载完毕则不继续加载数据

			isLock=true;
			$.ajax({
				   url: "my_collages_record_list_model.php?customer_id="+customer_id_en,
				   data:{
					   type			:	type,
					   start_time	:	start_time,
					   end_time		:	end_time,
					   status		:	status,
					   user_id		:	user_id,
					   pageNum		:	pageNum,
					   pagesize		:	pagesize
				   },
				   type: "POST",
				   dataType:'json',
				   async: false,
				   success:function(result){
				   	console.log(result);

					callback:callback(result,is_reset); //带参回调

				   },
				   error:function(er){

				   }
			});

		}
	}
	function _reset(){	//重置加载参数
		 downFlag	 = false;			// 重置加载全部为false
		 pageNum 	 = 0;				// 重置起始页码
		 isLock 	 = false; 			// 重置继续加载为false
		 $(window).scrollTop(0);	//重置返回顶部
		 var i = 0;
		  for(i=0;i<timeInterval.length;i++){
			   clearInterval(timeInterval[i]); //循环清除计时器
		  }
		  timeInterval = new Array();//重置计时器数组
	}
	//获取搜索产品数据
	function get_search_data(result,is_reset){

		if(is_reset){
			_reset();
		}
		isLock = false;

		var k = 0;
		var html = '';
		$.each(result.data,function(i,val){
			html += '<div class="line">';
			html += '	<div class="time">';

			if( 1 == val.status ){
				html += '<p class="timeP1 countdown" data-id='+val.id+' data-end='+Date.parse(new Date(val.endtime))/1000+'>剩余时间：<span id="time_'+val.id+'"></span></p>	';
			}
			if( val.cstatus == 3 && val.is_refund == 1 ){
				html += '<span class="timeS1">待退款...</span>';
			}else if( val.cstatus == 6 ){
				html += '<span class="timeS1">已退款...</span>';
			}else{
				if( 1 == val.status ){
					if(val.is_paytime != null )
					{
						html += '<span class="timeS1">拼团中(待付款)</span>';
					}else{
						html += '<span class="timeS1">拼团中...</span>';
					}
				}else if( 2 == val.status ){
                    if( 2 == val.is_win ){
                        html += '<span class="timeS3">很遗憾，没能中奖</span>';
                    }else{
                        html += '<span class="timeS3">拼团失败</span>';
                    }
				}else if( 3 == val.status ){
					html += '<span class="timeS2">拼团成功</span>';
				}else if( 4 == val.status ){
					html += '<span class="timeS1">待抽奖...</span>';
				}else if( 5 == val.status ){
					html += '<span class="timeS1">成团成功</span>';
				}else if( 6 == val.status ){
					html += '<span class="timeS1">成团失败</span>';
				}else if( -1 == val.status){
					html += '<span class="timeS1">拼团中(待付款)</span>';
				}
			}

			html += '	</div>';
			html += '	<div class="content1" onclick="gotoProduct('+val.group_id+')">';
			html += '  <div class="line-img">';
			var b = val.batchcode;
			b = "'"+b+"'";
			html += '		<img class="detImg" src="'+val.default_imgurl+'" />';
			html += '<span class="cell_icon">'+val.type_name;
			html += '</span></div>';
			html += '		<div class="detTitle">';
			html += '			<p class="order5">'+val.pname+'</p>';
			html += '			<p class="order6">发起人：';
            if(val.uname==null){
                html += '|';
            }else{
                html += val.uname+'|';
            }

            if(val.weixin_name==null){
                html += '</p>';
            }else{
                html += val.weixin_name+'</p>';
            }

            if(val.coefficient!=null && val.coefficient>0){
                html += '           <p class="order7">'+val.type_name+' X'+val.coefficient+'</p>';
            }
			html += '			<p class="order8"><span class="t2"><?php if(OOF_P != 2) echo OOF_S ?> '+val.price+' <?php if(OOF_P == 2) echo OOF_S ?></span><span class="t1"> '+val.success_num+'人团</span></p>';
			html += '		</div>';
			html += '	</div>';

			html += '	<div class="orderState">';
			html += '		<p class="osPrice">实付款<?php if(OOF_P != 2) echo OOF_S ?> <span class="t1">'+val.totalprice+'</span> <?php if(OOF_P == 2) echo OOF_S ?><span class="t2">  (免运费)</span></p></div>';

            html += '<div class="all_Btn">';
			html += '		<div class="osBtn">';
			if( 1 == val.status ){
				if( val.is_head != 1 && val.cstatus == 2 ){
					html += '<button class="osBtn1" onclick="gotoRefunds('+b+')">申请退款</button>';
					html += '<div class="osBtnL skin-bd skin-color">还差'+(val.success_num - val.join_num)+'人</div>';
					html += '<div class="osBtnR skin-bg" onclick="invite('+val.group_id+')">邀请朋友</div>';
				}else if( val.cstatus == 3 || val.cstatus == 6 ){

				}else{
					html += '<div class="osBtnL skin-bd skin-color">还差'+(val.success_num - val.join_num)+'人</div>';
					html += '<div class="osBtnR skin-bg" onclick="invite('+val.group_id+')">邀请朋友</div>';
				}
			}else if( 4 == val.status ){
				//html += '<button class="osBtn2" onclick="gotoJoinGroup()">更多拼团</button>';
			}else if( 3 == val.status || 5 == val.status || 6 == val.status){

			}else if( 2 == val.status ){
				html += '<button class="osBtn1" onclick="gotoMoneybag()">查看零钱</button>';
			}
			html += '<button class="osBtn1" onclick="gotoOrder('+b+')">订单详情</button>';
			html += '</div>	';
            html += '</div>	';
			html += '</div>	';
			k++;
		});

		if( pageNum == 0 ){
			$('.content').html(html);
		}else{
			$('.content').append(html);
		}
		//数据小于一页数量，说明已经没数据
		if( k < pagesize ){
			downFlag = true;
		}else{
			downFlag = false;
		}
		//运行计时器
		countdown();
	}
	//循环计时器div
	function countdown(){
		$('.countdown').each(function(){
			var id = $(this).data('id');
			var end_time = $(this).data('end');
			GetRTime2(end_time,id)
		});
	}
	function gotoMoneybag(){
		record_location();
		window.location.href = '/weixinpl/mshop/my_moneybag.php?customer_id='+customer_id_en;
	}
	function gotoProduct(group_id){
		record_location();
		window.location.href = './activities_detail_view.php?group_id='+group_id+'&customer_id='+customer_id_en;
	}
	function gotoJoinGroup(){
		record_location();
		window.location.href = 'product_list_view.php?op=popularity&customer_id='+customer_id_en;
	}
	//分享
	function invite(group_id){
		record_location();
		var share_url="activities_detail_view.php?customer_id=<?php echo $customer_id_en;?>&group_id="+group_id+"&share=1";
		window.location.href = share_url;
	}
	function gotoOrder(batchcode){
		record_location();
		window.location.href = '/weixinpl/mshop/orderlist_detail.php?customer_id=<?php echo $customer_id_en; ?>&batchcode='+batchcode+'&user_id=<?php echo $user_id ?>';
	}
	function gotoRefunds(batchcode){
	showConfirmMsg("操作提示","确定申请退款？","确定","取消",function(){
		$.ajax({
			type: 'get',
			url: '/weixinpl/mshop/orderlist_operation.php',
			dataType: 'json',
			data: {
				'op':'collageAftersale',
				'batchcode':batchcode,
				'customer_id':customer_id_en
			},
			success: function(data){
				showAlertMsg("操作提示",data.errmsg,"知道了",function(){
					window.location.reload();
				});
			}
		});
	});
}
	function record_location(){		//记录当前位置
		var _h = $('.content').scrollTop();
		localStorage.setItem("collges_height",_h);	//记录滚动条
		localStorage.setItem("collages_pageNum",pageNum);	//记录滚动条
		document.cookie="collges_type="+type;	//记录状态
		document.cookie="collges_status="+status;	//记录类型
		document.cookie="collges_start_time="+start_time;	//记录开始时间
		document.cookie="collges_end_time="+end_time;	//记录结束时间

	}

	function deal_cookie(){
		/*处理本地储存---start*/

		if( localStorage.getItem("collages_pageNum") ){
			_page = localStorage.getItem("collages_pageNum");	//读取滚动条

			for(i=1;i<=_page;i++){
				pageNum = i;
				search_ajax_data(type,start_time,end_time,status,get_search_data,0);//默认加载数据
			}

		}
		if( localStorage.getItem("collges_height") ){
			var _h = localStorage.getItem("collges_height");	//读取滚动条
			$('.content').scrollTop(_h);
		}
		localStorage.setItem("collges_height",'');	//清空滚动条
		/*处理本地储存---end*/
	}
	// 换日历插件
	/*日期插件*/
        Date.prototype.format = function (fmt) {
            var o = {
                "M+": this.getMonth() + 1,                 //月份
                "d+": this.getDate(),                    //日
                "h+": this.getHours(),                   //小时
                "m+": this.getMinutes(),                 //分
                "s+": this.getSeconds(),                 //秒
                "q+": Math.floor((this.getMonth() + 3) / 3), //季度
                "S": this.getMilliseconds()             //毫秒
            };
            if (/(y+)/.test(fmt)) {
                fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
            }
            for (var k in o) {
                if (new RegExp("(" + k + ")").test(fmt)) {
                    fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
                }
            }
            return fmt;
        }
		$(function () {
			var today = new Date().format('yyyy-MM-dd');
			var beday = GetDateStr(-6);
			$('#daterangepickeript').val(beday+' 至 '+today);//默认日期
			$('#start_time').val(beday);
            $('#end_time').val(today);

            $('#daterangepickeript').dateRangePicker({
                singleMonth: true,
                // showShortcuts: false,
                showTopbar: false,
                startOfWeek: 'monday',
                separator: ' 至 ',
                autoClose: false,
                endDate: new Date(),
            }).bind('datepicker-change', function (event, obj) {
                $('#daterangepickeript').val(obj.value)
                $('#start_time').val(obj.date1.format('yyyy-MM-dd'));
                $('#end_time').val(obj.date2.format('yyyy-MM-dd'));
            });
            $(".next").click();
        })

        function GetDateStr(AddDayCount) {
			var dd = new Date();
			dd.setDate(dd.getDate()+AddDayCount);//获取AddDayCount天后的日期
			var y = dd.getFullYear();
			var m = dd.getMonth()+1;//获取当前月份的日期
			var d = dd.getDate();
			if(m<10)m = '0'+m;
			if(d<10)d = '0'+d;
			return y+"-"+m+"-"+d;
		}

</script>
<?php require('../../../weixinpl/common/share.php'); ?>

<?php
/*判断是否显示底部菜单 start*/
require_once('../../../weixinpl/common/utility_setting_function.php');
$fun = "my_group_record";
$is_publish = check_is_publish(2,$fun,$customer_id);

if($is_publish){
	require_once('../../../weixinpl/mshop/bottom_label.php');
}
/*判断是否显示底部菜单 end*/
?>
<!--引入侧边栏 start-->
<?php
$nav_is_publish = check_nav_is_publish($fun,$customer_id);
include_once('../../../weixinpl/mshop/float.php');
?>
<!--引入侧边栏 end-->
</html>
