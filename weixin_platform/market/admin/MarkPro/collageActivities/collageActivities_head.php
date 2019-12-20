<style  type="text/css">
body{
	margin:0;
	font-family:'微软雅黑','Times New Roman', Times, serif;
	}
.WSY_columnbox{overflow: auto}
.navi_head{
	height:38px;
    background:#F4F4F4;
    border-bottom:1px solid #D8D8D8;
}
.navi_body{
	height:50px;
	cursor: pointer;
	transition:height ease 0.5s;
}
.headbox li{float: left;width:150px;
	text-align:center;
	font-weight:bold;
	color:#FFF;
	font-size:14px;
	vertical-align:top;}
.headbox li p{height: 38px;line-height: 38px}
.headbox li .navi_title{
	font-size:15px;
	line-height:38px;
	margin-top:0;
    color:#646464;
    font-weight:normal;
}
.navbox{background:#06a7e1;display: none;position: absolute;border-bottom:2px solid #06A7E1;}
.navbox li{float: none;height: 38px;line-height: 38px;background:#FFFFFF;color:#646464;font-weight:normal;}
.navbox li:hover{background:#EBEBEB;}
.navbox li:hover a{border:none;}
.clear{clear:both;}
.navi_title:hover{background:#FFFFFF;}

</style>
<div class="navi_body">
	<div class="navi_head">
		<ul class="headbox">
			<li>
				<p class="navi_title">基础配置</p>
				<ul class="navbox WSY-skin-bd">
					<a href="explain.php?customer_id=<?php echo $customer_id_en; ?>"><li>活动说明</li></a>
					<a href="recommendationProduct.php?customer_id=<?php echo $customer_id_en; ?>"><li>团产品推荐</li></a>
					<a href="groupRecommendation.php?customer_id=<?php echo $customer_id_en; ?>"><li>拼团推荐</li></a>
                    <a href="type.php?customer_id=<?php echo $customer_id_en; ?>"><li>拼团类型</li></a>
                    <a href="refund_setting.php?customer_id=<?php echo $customer_id_en; ?>"><li>退款设置</li></a>
				</ul>
			</li>
			<li>
				<p class="navi_title">活动管理列表</p>
				<ul class="navbox WSY-skin-bd">
					<a href="activityList.php?customer_id=<?php echo $customer_id_en; ?>"><li>活动管理列表</li></a>
					<a href="activityProMes.php?customer_id=<?php echo $customer_id_en; ?>"><li>产品活动管理</li></a>
					<a href="groupOrder.php?customer_id=<?php echo $customer_id_en; ?>"><li>团活动列表</li></a>
				</ul>

			</li>

			<!--<li>
				<p class="navi_title">团活动列表</p>
				<ul class="navbox WSY-skin-bd">
					<a href="groupOrder.php?customer_id=<?php echo $customer_id_en; ?>"><li>团活动列表</li></a>
				</ul>
			</li>-->

			<li>
				<p class="navi_title">拼团订单</p>
				<ul class="navbox WSY-skin-bd">
					<a href="crewOrder.php?customer_id=<?php echo $customer_id_en; ?>"><li>拼团订单</li></a>
				</ul>
			</li>
			<li>
				<p class="navi_title">数据统计</p>
				<ul class="navbox WSY-skin-bd">
					<a href="activityMes.php?customer_id=<?php echo $customer_id_en; ?>"><li>活动数据汇总</li></a>
					<a href="proMes.php?customer_id=<?php echo $customer_id_en; ?>"><li>产品活动汇总</li></a>
					<a href="userMes.php?customer_id=<?php echo $customer_id_en; ?>"><li>用户参团活动汇总</li></a>
					<?php
						require_once($_SERVER['DOCUMENT_ROOT'].'/market/admin/MarkPro/collageActivities/config.php');

						if (isOpenBBT) {	//抱抱团屏蔽了即不显示购物币统计
					?>
                    <a href="currencyMes.php?customer_id=<?php echo $customer_id_en; ?>"><li>购物币统计</li></a>
					<?php
						}
					?>
				</ul>
			</li>
			<!--
			<li>
				<p class="navi_title">反馈列表</p>
				<ul class="navbox WSY-skin-bd">
					<a href="order_refund.php?customer_id=<?php echo $customer_id_en; ?>"><li>反馈列表</li></a>
				</ul>
			</li>
			-->

			<div class="clear"></div>
		</ul>

	</div>
</div>
<script type="text/javascript">
	$(".headbox li").hover(function(){
			$(this).find(".navbox").stop().slideDown(300)
	},function(){
			$(this).find(".navbox").stop().slideUp(300)
	})
</script>

