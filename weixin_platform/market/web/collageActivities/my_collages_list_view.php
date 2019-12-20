<?php
header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../weixinpl/common/common_from.php');
require('../../../weixinpl/mshop/tax_function.php');			//行邮税方法
require('../../../weixinpl/proxy_info.php');
require('../../../weixinpl/mshop/select_skin.php');
$pageNum = 0;//页数

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
$is_orderActivist = -1;//订单售后维权开关 0、关闭 1、开启
$is_receipt		  =  0;//
$sql_order = "select is_orderActivist,is_receipt from weixin_commonshops_extend where isvalid=true and customer_id=".$customer_id;
$result_order = _mysql_query($sql_order) or die('sql_score failed:'.mysql_error());
while($row_order = mysql_fetch_object($result_order)){
	$is_receipt 	  = $row_order->is_receipt;
	$is_orderActivist = $row_order->is_orderActivist;
}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>我的拼团</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<meta content="no" name="apple-touch-fullscreen">
		<meta name="MobileOptimized" content="320" />
		<meta name="format-detection" content="telephone=no">
		<meta name=apple-mobile-web-app-capable content=yes>
		<meta name=apple-mobile-web-app-status-bar-style content=black>
		<meta http-equiv="pragma" content="nocache">
		<link rel="stylesheet" href="/weixinpl/mshop/css/style.css" />
		<link rel="stylesheet" href="/weixinpl/mshop/css/goods/my_collages_record_list_view.css" />
		<link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/order_css/global.css" />
		<script type="text/javascript" src="/weixinpl/mshop/assets/js/jquery.min.js"></script>
		<script src="/weixinpl/mshop/js/r_global_brain.js" type="text/javascript"></script>
		<script src="/weixinpl/mshop/js/global.js" type="text/javascript"></script>
		<script src="/weixinpl/mshop/js/r_pinterest.js" type="text/javascript"></script>
		<script src="/weixinpl/mshop/js/laydate.js" type="text/javascript"></script>
		<link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/css_<?php echo $skin ?>.css" />
	</head>
	<style>
		.tis{
			width: 100%;
			text-align: center;
			color:#999;
			font-size: 18px;
			margin-top: 20px;
			margin-bottom: 10px;
		}
	</style>
	<body>

		<div class="box">
			<input value="<?php echo $start_time; ?>" id="start_time" onclick="laydate({istime: true, format: 'YYYY-MM-DD hh:mm:ss'})"/>
			<label for="start_time">
				<img src="img/icon8.png" />
			</label>
			<span>至</span>
			<input value="<?php echo $end_time; ?>" id="end_time" onclick="laydate({istime: true, format: 'YYYY-MM-DD hh:mm:ss'})"/>
			<label for="end_time">
				<img src="img/icon8.png" />
			</label>
			<button onclick="searchData()">搜索</button>
		</div>

		<div class="box1">
			<div class="cell <?php if( $currtype == 1 ){echo "cellSelected";} ?>" data-type="1">
				<img class="image1" src="img/icon_dingdan_quanbu.png" />
				<img class="image2" src="img/icon_dingdan_quanbu_sel-orange.png" />
				<p>全部</p>
			</div>
			<div class="cell <?php if( $currtype == 2 ){echo "cellSelected";} ?>" data-type="2">
				<img class="image1" src="img/icon_daifukuan.png" />
				<img class="image2" src="img/icon_daifukuan_sel-orange.png" />
				<p>待付款</p>
			</div>
			<div class="cell <?php if( $currtype == 3 ){echo "cellSelected";} ?>" data-type="3">
				<img class="image1" src="img/icon_daifahuo.png" />
				<img class="image2" src="img/icon_daifahuo_sel-orange.png" />
				<p>待发货</p>
			</div>
			<div class="cell <?php if( $currtype == 4 ){echo "cellSelected";} ?>" data-type="4">
				<img class="image1" src="img/icon_daishouhuo.png" />
				<img class="image2" src="img/icon_daishouhuo_sel-orange.png" />
				<p>待收货</p>
			</div>
			<div class="cell <?php if( $currtype == 5 ){echo "cellSelected";} ?>" data-type="5">
				<img class="image1" src="img/icon_daipingjia.png" />
				<img class="image2" src="img/icon_daipingjia_sel-orange.png" />
				<p>待评价</p>
			</div>
			<div class="cell <?php if( $currtype == 6 ){echo "cellSelected";} ?>" data-type="6">
				<img class="image1" src="img/icon_shouhouzhong.png" />
				<img class="image2" src="img/icon_shouhouzhong_sel-orange.png" />
				<p>售后中</p>
			</div>
		</div>
		<div id="pinterestList">
		<?php
               // if($currtype !=7) { //非售后
    			$sql_count = " select count(cot.batchcode) as datacount ";
    			$sql_cond = " FROM collage_crew_order_t AS cot
					LEFT JOIN weixin_commonshop_orders AS wco ON cot.batchcode=wco.batchcode
					LEFT JOIN collage_group_order_t AS cgot ON cgot.id=cot.group_id
					WHERE cot.isvalid=true and cot.customer_id=" . $customer_id . "  and cot.user_id=" . $user_id ." and wco.is_collageActivities>0";

    			switch ($currtype) {
    				case 1:
                            // 所有订单
    				break;
                        case 2: //待付款
                        $sql_cond = $sql_cond . " and wco.status = 0  and wco.paystatus = 0";
                        break;
                        case 3: // 待发货
                        $sql_cond = $sql_cond . " and wco.paystatus=1 and wco.status = 0 and wco.sendstatus = 0 ";
                        break;
                        case 4: //待收货
                        $sql_cond = $sql_cond . " and wco.paystatus=1 and wco.status = 0 and wco.sendstatus = 1";
                        break;
                        case 5: //待评价
                        $sql_cond = $sql_cond . " and (wco.status = 0 or wco.status = 1)  and wco.sendstatus = 2 and wco.is_discuss = 0 ";
                        break;
                        case 6: //售后中
                        $sql_cond = $sql_cond . " and (wco.sendstatus > 2 || wco.aftersale_type > 0)";
                        break;

                    }

                    $sql_count .= $sql_cond;
                $datacounts = 0;
					//echo $sql_count;
                $result_count = _mysql_query($sql_count) or die("Query sql_count failed : ".mysql_error());
                if($row_count = mysql_fetch_object($result_count)){
                	$datacounts = $row_count->datacount;
                }
                if($datacounts == 0){ ?>
                <p class="tis" id="nomany">---暂无更多记录---</p>
		<?php
			}else{
				require('../../../market/web/collageActivities/my_collages_list_model.php');
			}
		?>
		</div>
		<button class="btn" onclick="gotoMyRecord()">我的拼团记录</button>

	</body>

	<script>
		var downFlag = false; // 是否加载全部
		var pageNum = 0, pageSize = 5,isMore = true; // 总笔数
		var dataCounts = <?php echo $datacounts;?>;
		var maxPage = Math.ceil(dataCounts/pageSize);
		var winWidth = $(window).width();
		var winheight = $(window).height();

	    var customer_id_en = '<?php echo $customer_id_en;?>';
		var user_id = <?php echo $user_id;?>;
		user_id_en = '<?php echo passport_encrypt($user_id);?>';
		var is_receipt = '<?php echo $is_receipt;?>';
		var currtype = '<?php echo $currtype;?>';
		var start_time = '<?php echo $start_time;?>';
		var end_time = '<?php echo $end_time;?>';

		$(".cell").click(function(){
			// $(this).siblings().removeClass("cellSelected");
			// $(this).addClass("cellSelected");
			currtype = $(this).data('type');
			var url = "my_collages_list_view.php?customer_id="+customer_id_en+"&currtype="+currtype+"&user_id="+user_id_en;
			if( start_time != '' && end_time!= '' ){
				if( start_time > end_time ){
					showAlertMsg("操作提示","开始时间不得大于结束时间","知道了");
					return;
				}
			}
			if( start_time != '' ){
				url += "&start_time="+start_time;
			}
			if( start_time != '' ){
				url += "&end_time="+end_time;
			}
			window.location.href = url;
		})
		function searchData(){
			start_time = $('#start_time').val();
			end_time = $('#end_time').val();
			var url = "my_collages_list_view.php?customer_id="+customer_id_en+"&currtype="+currtype+"&user_id="+user_id_en;
			if( start_time != '' && end_time!= '' ){
				if( start_time > end_time ){
					showAlertMsg("操作提示","开始时间不得大于结束时间","知道了");
					return;
				}
			}
			if( start_time != '' ){
				url += "&start_time="+start_time;
			}
			if( start_time != '' ){
				url += "&end_time="+end_time;
			}
			window.location.href = url;
		}
			function ajaxSearchData() {
				content = "";

				if (pageNum == maxPage) return;

				$.ajax({
					type: "get",
					url: "my_collages_list_turnpage.php",
					data: "pageNum="+(pageNum+1)+"&currtype="+currtype+"&user_id="+user_id_en+"&start_time"+start_time+"&end_time"+end_time+"",
					success: function(msg){
						$("#pinterestList").append(msg);
					}
				});
				pageNum++;
			}


		window.onscroll = function (event) {  // 返回顶部
			var intY = $(window).scrollTop();
			if (pageNum == maxPage) return;

			var height = document.body.scrollHeight - 100;
			if (intY+winheight-15>height) ajaxSearchData();
		};

		//跳转到供应商页面
		function gotoShop(shopID){
			window.location.href = "/weixinpl/mshop/my_store/my_store.php?supplier_id="+shopID+"&customer_id="+customer_id_en;
		}

		//跳转到首页
		function gotoIndex(){
			window.location.href = "/weixinpl/common_shop/jiushop/index.php?customer_id="+customer_id_en;
		}

		//跳转到订单详情
		function gotoProductOrder(batchcode){
			window.location.href = "/weixinpl/mshop/orderlist_detail.php?customer_id="+customer_id_en+"&user_id="+user_id_en+"&batchcode="+batchcode;
		}

		 function toEvaluation(batchcode){
			window.location.href = "/weixinpl/mshop/orderlist_evaluation.php?batchcode="+batchcode+"&customer_id="+customer_id_en;
		 }

	   function toAftersale(batchcode){
			location.href='/weixinpl/mshop/orderlist_aftersale.php?batchcode='+batchcode+"&customer_id=<?php echo $customer_id_en;?>";
		}

		 //确认收货
		function order_confirm(batchcode,totalprice){
			showConfirmMsg("提示：","警：确认完成后，订单将进行结算，订单不再受理退货，退款，如若确定商品无误，请点击确认，否则取消。","确认","取消",function(){
				$.getJSON("/weixinpl/mshop/orderlist_operation.php",{batchcode:batchcode,totalprice:totalprice,op:"confirm"},function(data){

					showAlertMsg ("提示：",data.msg,"知道了",function(){
						if(is_receipt==1){
							confirmOrder(batchcode,totalprice);
						}
						location.reload();
					});
				});
			});
		}
		//确认完成订单
		function confirmOrder(batchcode,totalprice){
			$.ajax({
				url:"/weixinpl/back_newshops/Order/order/order.class.php",
				dataType:"json",
				type:"post",
				data:{'batchcode':batchcode,'totalprice':totalprice,'op':"confirm","is_receipt":1}
			});
		}
		//点击【查看物流】
		function check_express(expressNum){
			//window.location.href = "http://m.kuaidi100.com/index_all.html?type="+expressNum+"&postid="+expressNum+"#result";
			window.location.href = " //m.kuaidi100.com/result.jsp?nu="+expressNum;
		}
	 //链接到评价页
	 //取消订单
		function order_cancel(batchcode){
			showConfirmMsg("操作提示","取消后不可恢复，是否确认取消订单？","取消","不取消",function(){
				$.getJSON("/weixinpl/mshop/orderlist_operation.php",{batchcode:batchcode,op:"cancel"},function(data){

					showAlertMsg ("提示：",data.msg,"知道了",function(){
						location.reload();
					});
//					showAlertMsg ("提示：","您还没设置支付密码","去设置",function(){
//						location.reload();
//					});
				});
			});
		}

		 function topay(batchcode){
            location.href="/weixinpl/mshop/orderlist_detail.php?batchcode="+batchcode+"&customer_id=<?php echo $customer_id_en;?>&user_id=<?php echo $user_id?>#topay"
        }
		function gotoMyRecord(){
			location.href="my_collages_record_list_view.php?customer_id=<?php echo $customer_id_en;?>";
		}
	</script>

<!--引入侧边栏 start-->
<?php  include_once('../../../weixinpl/mshop/float.php');?>
<!--引入侧边栏 end-->
	<?php require('../../../weixinpl/common/share.php');
	/*判断是否显示底部菜单 start*/
	require_once('../../../weixinpl/common/utility_setting_function.php');
	$fun = "my_collages_record_list_view";
	$is_publish = check_is_publish(2,$fun,$customer_id);
	if($is_publish){
		require_once('../../../weixinpl/mshop/bottom_label.php');
	}
	/*判断是否显示底部菜单 end*/
	?>
</html>
