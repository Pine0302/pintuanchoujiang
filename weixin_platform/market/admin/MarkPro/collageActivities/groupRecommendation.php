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

$collageActivities = new collageActivities($customer_id);

$data = $collageActivities->getGroupRecommendation($customer_id);

$group_type_arr = array();
$query = "SELECT type,type_name FROM collage_activities_explain_t WHERE isvalid=true AND customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed'.mysql_error());
while ( $row = mysql_fetch_object($result) ) {
	$type = $row->type;
    $type_name = $row->type_name;
    $group_type_arr[$type] = $type_name;
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

<title>基本设置</title>

<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<style>
.distr_type_div i{margin-top:7px;}
.WSY_remind_dl02 .distr_type_div {height:35px;}
.WSY_remind_dl02 input[type="text"] {float: none; width: 137px;}
.navbox{z-index: 1000;}
.open{display:<?php if($data['is_open']==1){ echo "block";}else{ echo "none";}?>}
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
					<dt>拼团推荐开关：</dt>
					<dd>
						<?php if($data['is_open']==1){ ?>
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
						<input type="hidden" name="is_open" id="is_open" value="<?php echo $data['is_open']; ?>" />
					</dd>
				</dl>
			</div>
			<div class="condition open">
				<dl class="WSY_remind_dl02">
					<dt>显示数量：</dt>
					<dd><input type="text" id="num" name="num" value="<?php echo $data['num'];?>" onkeyup="clearInt(this)">&nbsp &nbsp -1表示不限制数量</dd>
				</dl>

				<dl class="WSY_remind_dl02">
					<dt>显示内容：</dt>
					<dd>
                    <?php
                        $type_list = $collageActivities->getTypes($customer_id);
                        foreach($type_list as $key => $val){
                    ?>
                    <input type="checkbox" id="type_<?php echo $val['type'];?>" name="type_<?php echo $val['type'];?>" value="<?php echo $val['type'];?>" <?php if( strpos( $data['type'] , $val['type'] ) !== false ){echo 'checked';} ?>><?php echo $val['type_name'];?>&nbsp &nbsp
                    <?php
                        }
                    ?>
					<span>注意：至少选择一个显示内容</span></dd>
				</dl>


				<dl class="WSY_remind_dl02">
					<dt>排序类型：</dt>
					<dd>
						<input type="radio" name="sort_type" value="1" <?php if ( $data['sort_type'] == 1 || empty($data['sort_type']) ) {echo 'checked';}?>>按所有拼团
						<input type="radio" name="sort_type" value="2" <?php if ( $data['sort_type'] == 2 ) {echo 'checked';}?>>按相关产品的团
					</dd>
				</dl>

				<dl class="WSY_remind_dl02">
					<dt>排序方式：</dt>
					<dd>
						<input type="radio" name="sort" value="1" <?php if($data['sort']==1 || empty($data['sort'])){echo 'checked';} ?>>升序 &nbsp
						<input type="radio" name="sort" value="2" <?php if($data['sort']==2){echo 'checked';} ?>>降序 &nbsp
					</dd>
				</dl>
				<div class="submit_div">
					<input type="button" class="WSY_button" value="提交" onclick="return saveData(this);" style="cursor:pointer;">
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script>
function saveData(){
	var is_open = $('#is_open').val();
	var num = $('#num').val();
	var sort_type = $('input[name="sort_type"]:checked').val();
	var sort = $('input[name="sort"]:checked').val();
	var type = '';
	var i = 1;
	for(i=1;i<=6;i++){
		if($('#type_'+i).prop('checked')){
			type += '_'+$('#type_'+i).val();
		}
	}

	if( is_open == 1 ){
		if( num == '' || isNaN(num) ){
			alert('请输入正确的显示数量！');
			return;
		}
		type = type.substr(1,type.length-1);
		if( type == '' ){
			alert('至少选择一个显示内容！');
			return;
		}
	}

	var addArray = new Array();
	addArray['is_open'] = is_open;
	addArray['num'] = num;
	addArray['type'] = type;
	addArray['sort_type'] = sort_type;
	addArray['sort'] = sort;

	$.ajax({
		type: 'POST',
		url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
		data:{
			op			: 'save_recommendation_activity',
			keyid		: '<?php echo $data['id'] ?>',
			is_open		: is_open,
			num			: num,
			type		: type,
			sort_type	: sort_type,
			sort		: sort
		},
		dataType: 'json',
		success: function(data){
			alert(data.content);
		}
	});
}

function set_is_open(n){
	$('#is_open').val(n);

	$.ajax({
		type: 'POST',
		url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
		data:{
			op		: 'changeGroupStatus',
			keyid	: '<?php echo $data['id'] ?>',
			is_open	: n
		},
		dataType: 'json',
		success: function(data){

		}
	});

	if( 0 == n ){
		$('.open').hide();
	}else if( 1 == n ){
		$('.open').show();
	}
}

//正整数
function clearInt(obj){
	if(obj.value.length==1){obj.value=obj.value.replace(/[^-1-9]/g,'')}else{if(obj.value == -1){}else{obj.value=obj.value.replace(/\D/g,'')}}
}
</script>
</body>
</html>