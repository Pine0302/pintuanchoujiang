<?php
 header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../../../../weixinpl/proxy_info.php');
$head=0;//头部文件0基本设置，1红积分日志

require('../../../../weixinpl/function_model/collageActivities.php');

$auto_refund_open = 0;
$query = "SELECT auto_refund_open FROM ".WSY_MARK.".collage_setting WHERE isvalid=true AND customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed'.mysql_error());
while ( $row = mysql_fetch_object($result) ) {
	$auto_refund_open = $row->auto_refund_open;
}

?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link rel="stylesheet" type="text/css" href="../../Common/css/Base/basicdesign/base_set.css">
<script type="text/javascript" src="../../../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../../Common/js/Base/basicdesign/layer.js"></script>
<script type="text/javascript" src="../../../common/js_V6.0/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="../../../common/utility.js"></script>


<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>

<title>基本设置</title>

<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<style>
.distr_type_div i{margin-top:7px;}
.WSY_remind_dl02 .distr_type_div {height:35px;}
.WSY_remind_dl02 input[type="text"] {float: none; width: 137px;}
.navbox{z-index: 1000;}
.WSY_remind_dl02 dt {
    text-align: left;
    width: 100px;
}
</style>
</head>
<body>
<div class="WSY_content">
	<div class="WSY_columnbox">
		<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>
		<div class="WSY_remind_main" style="margin-left: 20px;">
			<div class="openAll">
				<dl class="WSY_remind_dl02">
					<dt>自动退款开关：</dt>
					<dd>
						<?php if($auto_refund_open==1){ ?>
							<ul style="background-color: rgb(255, 113, 112);margin-top:2px;">
								<p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
								<li onclick="set_is_open(0)" class="WSY_bot" style="left: 0px;"></li>
								<span onclick="set_is_open(1)" class="WSY_bot2" style="display: none; left: 0px;"></span>
							</ul>
						<?php }else{ ?>
							<ul style="background-color: rgb(203, 210, 216);margin-top:2px;">
								<p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
								<li onclick="set_is_open(0)" class="WSY_bot" style="display: none; left: 30px;"></li>
								<span onclick="set_is_open(1)" class="WSY_bot2" style="display: block; left: 30px;"></span>
							</ul>
						<?php } ?>
						<img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;" id="free_group" src="../../Common/images/Base/help.png">
						<input type="hidden" name="is_open" id="is_open" value="<?php echo $auto_refund_open; ?>" />
					</dd>
				</dl>
			</div>
			<div class="condition open">
				<div class="submit_div">
					<input type="button" class="WSY_button" value="提交" onclick="return saveData(this);" style="cursor:pointer;">
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script>
var is_open = $('#is_open').val();
function set_is_open(val){
	is_open = val;
}
function saveData(){


	$.ajax({
		type: 'POST',
		url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
		data:{
			op			        : 'save_setting',
			refund_onoff		: is_open,
		},
		dataType: 'json',
		success: function(data){
			alert(data.content);
		}
	});
}

var tip_index = 0;
$(document).on('mouseenter', '#free_group', function(){
    tip_index = layer.tips('开启后将会把待退款的拼团失败记录全部自动退款，请谨慎操作！', '#free_group', {time: 0});
}).on('mouseleave', '#free_group', function(){
    layer.close(tip_index);
});

//正整数
function clearInt(obj){
	if(obj.value.length==1){obj.value=obj.value.replace(/[^-1-9]/g,'')}else{if(obj.value == -1){}else{obj.value=obj.value.replace(/\D/g,'')}}
}
</script>
</body>
</html>