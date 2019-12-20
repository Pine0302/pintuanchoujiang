<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link =mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../../weixinpl/proxy_info.php');
_mysql_query("SET NAMES UTF8");

require_once('../../../../weixinpl/function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

$condition = array('caprst.customer_id'=>$customer_id,'caprst.isvalid'=>true);
$filed = " caprst.id AS set_id,caprst.is_open,caprst.pattern,caprst.createtime,caprsyst.id AS system_set_id,caprsyst.num,caprsyst.type,caprsyst.style,caprsyst.sort ";
$info = $collageActivities->getProductRecommendationSet($condition,$filed);

$setInfo = $info['content'];
if( empty($setInfo) ){
	$query_ins = "INSERT INTO collage_activities_product_recommendation_set_t(
										customer_id,
										createtime,
										isvalid,
										is_open,
										pattern
									) VALUES (
										".$customer_id.",
										now(),
										true,
										2,
										1
									)";
	_mysql_query($query_ins) or die('Query_ins failed:'.mysql_error());
	$setInfo['set_id'] = mysql_insert_id();
	$setInfo['is_open'] = 2;
	$setInfo['pattern'] = 1;
}
$typeArr = [];
if( !empty($setInfo['type']) ){
	$typeArr = explode('_',$setInfo['type']);
}

$pid_arr[] = -1;	//关联产品id数组
//查找关联产品
$query_product = "SELECT pid FROM collage_activities_recommendation_product_t WHERE recommendation_id=".$setInfo['set_id']." AND isvalid=true";
$result_product = _mysql_query($query_product) or die('Query_product failed:'.mysql_error());
while( $row_product = mysql_fetch_object($result_product) ){
	$pid_arr[] = (int)$row_product -> pid;
}
$pid_str = implode(',',$pid_arr);

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
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/css2.css" media="all">
<link href="../../../common/add/css/global.css" rel="stylesheet" type="text/css">
<link href="../../../common/add/css/main.css" rel="stylesheet" type="text/css">
<link href="../../../common/add/css/shop.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<script type="text/javascript" src="../../../js/tis.js"></script>
<script type="text/javascript" src="../../../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<style type="text/css">
a:hover{text-decoration: none;}
.button_blue{cursor: pointer;margin-left: 10px;font-size: 14px;line-height: 30px;background-color: #06a7e1;padding-left: 15px;padding-right: 15px;border-radius: 3px 3px 3px 3px;margin-top:20px;color: #fff;}
.button_blue:hover{background:#0e98c9;}
.name{  margin-top: 10px;height: 30px;line-height: 30px;font-size: 13px;text-align: left;font-weight: bolder;margin-left: 19px;}
.button_box{width: 296px;display: block;text-align: right;}
.button_box .WSY_button{border-radius:2px;border:none;}
.delivery-button{display:inline-block;padding: 3px 15px;color: #fff;border-radius: 2px;cursor:pointer;margin: 2px 0;}
.product-box{width:95%;margin-top: 30px;padding: 15px;border: 1px #ccc solid;display:none;}
.header-left{float:left;}
.header-right{float:right;}
.search-box{height: 22px;line-height: 22px;}
.delivery_time{margin: 5px 35px;}
.delivery_time input{margin: 0 20px;}
.delivery_limit_box{margin: 15px 35px;position: relative;}
.delivery_font{font-size: 14px;font-weight: bold;margin-left: 17px;}
.delivery_limit_box input[type=text]{margin: 0 20px;}
.selected-date-content{display: block;margin-left: 30px;margin-top: 10px;}
.page-box1{margin-left:30px;}
.page-box1,.page-box2{margin-top:15px;}
.show-data-num{margin:0 20px;}
.page-box1 input,.page-box2 input{width:30px;text-align:center;}
.show-data-num-btn{margin-right:30px;}
.current-page{margin: 0 20px;}
.go-page-btn{margin-left:15px;}
#to-page-num1,#to-page-num2{margin-left:20px;}
.relation_table th,.list_table th{text-align:center;}
.navbox{z-index: 1000;}
.WSY_remind_main dl ul {
    display: inline-block;
    vertical-align: middle;
}
.float_left2sapn{display: inline-block;vertical-align: middle;font-size: 14px;margin-left: 17px;}
</style>
</head>
<body>
<div>
    <div class="WSY_content">
		<div class="WSY_columnbox" >
			<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>
	<div class="WSY_remind_main" style="background-color: #fff;padding-bottom: 10px;">
		<dl style="margin-top:0;padding-top:10px;">
			<dd class="float_left2" style="float: left;">
			<span class="float_left2sapn">产品推荐开关：</span>
			<?php
				if( $setInfo['is_open'] == 1 ){
			?>
				<ul style="background-color: rgb(255, 113, 112);">
					<p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
					<li onclick="statusSwitch(2)" class="WSY_bot" style="left: 0px;"></li>
					<span onclick="statusSwitch(1)" class="WSY_bot2" style="display: none; left: 0px;"></span>
				</ul>
			<?php
				} else {
			?>
				<ul style="background-color: rgb(203, 210, 216);">
					<p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
					<li onclick="statusSwitch(2)" class="WSY_bot" style="display: none; left: 30px;"></li>
					<span onclick="statusSwitch(1)" class="WSY_bot2" style="display: block; left: 30px;"></span>
				</ul>
			<?php
				}
			?>
			</dd>
		</dl>
		<div style="clear:both;"></div>
	</div>
	<div id="recommendation-set" class="r_con_wrap" <?php if($setInfo['is_open']==2){echo 'style="display:none;"';}?>>
		<div style="margin-bottom: -20px;">
			<label class="delivery_font">产品推荐模式：</label>
			<input type="radio" id="pattern1" name="pattern" value="1" onclick="patternSwitch(1)" <?php if($setInfo['pattern']==1){echo 'checked';}?> /><label for="pattern1" style="margin-right:10px;">系统推荐模式</label>
			<input type="radio" id="pattern2" name="pattern" value="2" onclick="patternSwitch(2)" <?php if($setInfo['pattern']==2){echo 'checked';}?> /><label for="pattern2">自定义模式</label>
		</div>
	<div id="systemSet" <?php if($setInfo['pattern']==2){echo 'style="display:none;"';}?>>
		<form action="" enctype="multipart/form-data" id="recommendation-form" method="post">
		<div style="margin-top:20px">
			<label class="delivery_font">显示数量：</label>
			<input type="text" name="num" value="<?php echo $setInfo['num'];?>" onkeyup="clearInt(this)" />
			<span>建议显示3个</span>
		</div>
		<div style="margin-top:20px">
			<label class="delivery_font">团类型：</label>
            <?php
                $type_list = $collageActivities->getTypes($customer_id);
                foreach($type_list as $key => $val){
            ?>
            <input type="checkbox" name="type[]" class="type" id="type<?php echo $val['type'];?>" value="<?php echo $val['type'];?>" <?php if(in_array($val['type'],$typeArr)){echo 'checked';}?> />
			<label for="type1"><?php echo $val['type_name'];?></label>
            <?php
                }
            ?>
		</div>
		<div style="margin-top:20px">
			<label class="delivery_font">团产品类型：</label>
			<input type="radio" id="style1" name="style" value="1" <?php if($setInfo['style']==1){echo 'checked';}?> /><label for="style1" style="margin-right:10px;">按产品分类相似度</label>
			<input type="radio" id="style2" name="style" value="2" <?php if($setInfo['style']==2){echo 'checked';}?> /><label for="style2">按所有分类</label>
		</div>
		<div style="margin-top:20px">
			<label class="delivery_font">显示顺序：</label>
			<input type="radio" id="sort1" name="sort" value="1" <?php if($setInfo['sort']==1){echo 'checked';}?> /><label for="sort1" style="margin-right:10px;">按活动时间最新时间</label>
			<input type="radio" id="sort2" name="sort" value="2" <?php if($setInfo['sort']==2){echo 'checked';}?> /><label for="sort2">按活动开团数量</label>
		</div>
		</form>
		<span class="button_box">
			<input type=button class="WSY_button"  value="提交" onclick="submitV();"  style="float:none;margin-top: 49px;"/>
		</span>
	</div>
	<div id="customSet" <?php if($setInfo['pattern']==1){echo 'style="display:none;"';}?>>
		<form action="save_pre_delivery.php?customer_id=<?php echo passport_encrypt((string)$customer_id);?>&supply_id=<?php echo $supply_id;?>&supply_id_en=<?php echo $supply_id_en;?>" enctype="multipart/form-data" id="recommendation-form" method="post">
		<div class="product-table" style="margin-top:20px">
			<span class="delivery-button WSY-skin-bg" id="select-product" style="float:right;margin-right:9%;margin-bottom: 10px;">添加</span>
			<table class="WSY_table relation_table" width="90%" id="WSY_t1">
				<thead class="WSY_table_header">
					<th width="5%"><input type="checkbox" id="select-all-product">全选</th>
					<th width="7%">ID</th>
					<th width="7%">产品编号</th>
					<th width="12%">产品名称</th>
					<th width="10%">产品分类</th>
					<th width="7%">价格</th>
					<th width="7%">销量</th>
					<th width="7%">库存</th>
					<th width="10%">标签</th>
					<th width="7%">状态</th>
					<th width="10%">创建时间</th>
					<th width="15%">操作管理</th>
				</thead>

			</table>
		</div>
		<!-- 选择产品 -->
		<div class="product-box">
			<div class="header">
				<div class="header-left">
					<span>产品ID：</span><input type="text" class="search-box" id="search-pid" onkeyup="clearInt(this)" />
					<span>产品名称：</span><input type="text" class="search-box" id="search-pname" />
					<span>活动ID：</span><input type="text" class="search-box" id="search-aid" onkeyup="clearInt(this)" />
					<span>活动主题：</span><input type="text" class="search-box" id="search-aname" />
					<span>类型：</span>
					<select id="search-atype">
						<option value="-1">全部</option>
                        <?php
                            $type_list = $collageActivities->getTypes($customer_id);
                            foreach($type_list as $key => $val){
                        ?>
                        <option value="<?php echo $val['type'];?>"><?php echo $val['type_name'];?></option>
                        <?php
                            }
                        ?>
					</select>
					<span class="delivery-button WSY-skin-bg" id="search-button">搜索</span>
				</div>
				<div class="clear"></div>
			</div>
			<table class="WSY_table list_table" width="100%" id="WSY_t1" style="margin-left:0;">
				<thead class="WSY_table_header">
					<th width="5%"><input type="checkbox" id="select-all" />全选</th>
					<th width="7%">产品ID</th>
					<th width="20%">产品名称</th>
					<th width="10%">活动价</th>
					<th width="7%">活动ID</th>
					<th width="12%">活动主题</th>
					<th width="10%">类型</th>
					<th width="15%">活动时间</th>
					<th width="7%">成团人数</th>
					<th width="7%">单品次数限制</th>
					<th width="7%">活动次数限制</th>
					<th width="10%">操作管理</th>
				</thead>
			</table>
			<div style="text-align: center;margin-top: 20px;">
				<span class="delivery-button WSY-skin-bg" onclick="come_back()" style="padding: 8px;">返回</span>
				<span class="delivery-button WSY-skin-bg" onclick="confirm_select_product(2)" style="padding: 8px;">批量添加选中</span>
			</div>
		</div>
		</form>
			<span class="button_box">
				<input type=button class="WSY_button"  value="提交" onclick="submitV();"  style="float:none;margin-top: 49px;"/>
			</span>
	</div>
</div>
		<input type=hidden name="product_relation" id="product_relation" value="<?php echo $pid_str;?>" />
		</div>
	</div>

<div style="width:100%;height:20px;">
</div>
</div>
<script>
var customer_id = '<?php echo $customer_id;?>';
var pidArr = eval('<?php echo json_encode($pid_arr);?>');
var pidStr = '<?php echo $pid_str;?>';
var is_open = '<?php echo $setInfo['is_open'];?>';
var recommendation_id = '<?php echo $setInfo['set_id'];?>';
var system_set_id = '<?php echo $setInfo['system_set_id'];?>';
// var pidStrNew = '<?php echo ','.$pid_str.',';?>';

var selectedLimitStart = 0,
	selectedLimitEnd = 14,
	selectedCurrentPage = 1,
	selectedEachPageNum = 15,
	selectedTotalPage = 1;

var showProductLimitStart = 0,
	showProductLimitEnd = 14,
	showProductCurrentPage = 1,
	showProductEachPageNum = 15,
	showProductTotalPage = 1,
	search_pid = '',
	search_pname = '',
	search_aid = '',
	search_aname = '',
	search_atype = '';

$(document).ready(function(){
	if( is_open == 1 ){
		get_product_relation(pidStr);
		get_activity_product();
	}

	$("#select-all").click(function() { // 全选/取消全部
		if (this.checked == true) {
			$(".product-list-checkbox").each(function() {
				this.checked = true;
			});
		} else {
			$(".product-list-checkbox").each(function() {
				this.checked = false;
			});
		}
	});

	$("#select-all-product").click(function() { // 全选/取消全部
		if (this.checked == true) {
			$(".product-info-checkbox").each(function() {
				this.checked = true;
			});
		} else {
			$(".product-info-checkbox").each(function() {
				this.checked = false;
			});
		}
	});
});
//开关
function statusSwitch(status){
	$.ajax({
		url: 'ajax_handle.php?customer_id'+customer_id,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'edit_product_recommendation',
			status : status
		},
		success: function(res){
			if( res ){
				if( status == 1 ){
					get_product_relation(pidStr);
					get_activity_product();
					$('#recommendation-set').show();
				} else {
					$('#recommendation-set').hide();
				}
			}
		},
		error: function(err){
			alert(err);
		}
	});
}
//模式切换
function patternSwitch(val){
	if( val == 1 ){
		$('#systemSet').show();
		$('#customSet').hide();
	} else {
		$('#systemSet').hide();
		$('#customSet').show();
	}
}
//删除
function del_product(pid){
	if( confirm('删除后，推荐产品将不再在相关栏目显示，是否继续删除？') ){
		$.ajax({
			url: 'ajax_handle.php?customer_id'+customer_id,
			dataType: 'json',
			type: 'post',
			data: {
				op : 'del_recommendation_product',
				recommendation_id : recommendation_id,
				pid : pid
			},
			success: function(res){
				// console.log(pidArr);
				// console.log(pid);
				// console.log(pidArr.indexOf(pid));
				if( pidArr.indexOf(pid) >= 0 ){
					pidArr.splice(pidArr.indexOf(pid),1);
					pidStr = pidArr.join(',');
					get_product_relation(pidStr);
				}
			},
			error: function(err){
				alert('删除产品失败！');
			}
		});
	}
}
//下架、发布
function edit_out_status(status,pid){
	if( status == 1 ){
		if( !confirm('推荐产品发布成功后，将会在拼团的推荐产品中显示相关内容，是否发布？') ){
			return;
		}
	} else {
		if( !confirm('下架后，用户将无法查看相关动态信息，是否下架？') ){
			return;
		}
	}

	$.ajax({
		url: 'ajax_handle.php?customer_id'+customer_id,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'edit_recommendation_product_status',
			recommendation_id : recommendation_id,
			pid : pid,
			status : status
		},
		success: function(res){
			if( res ){
				if( status == 1 ){
					$('.release-btn-'+pid).hide();
					$('.out-btn-'+pid).show();
					$('.status-'+pid).text('已发布');
				} else {
					$('.release-btn-'+pid).show();
					$('.out-btn-'+pid).hide();
					$('.status-'+pid).text('未发布');
				}
			}
		},
		error: function(err){
			alert('删除产品失败！');
		}
	});
}

//添加产品
$('body').on('click','#select-product',function(){
	$('.product-table').hide();
	$('.product-box').fadeIn();
	get_activity_product();
});
//返回
function come_back(){
	$('.product-box').hide();
	$('.product-table').fadeIn();
}
//确定关联产品
function confirm_select_product(type){
	var selectedProductId = new Array();
	var productActivityId = new Array();
	if( type == 1 ){
		if( arguments[1] != undefined && arguments[2] != undefined ){
			selectedProductId[0] = arguments[1];
			productActivityId[0] = arguments[2];
			if( pidArr.indexOf(selectedProductId[0]) == -1 ){
				pidArr.push(selectedProductId[0]);
			}
		}
	} else {
		var selectedProduct = $('.product-list-checkbox:checked');
		if( selectedProduct.length == 0 ){
			alert('请选择产品！');
			return false;
		}
		selectedProduct.each(function(i) {
			selectedProductId[i] = $(this).val();
			productActivityId[i] = $(this).data('activitie_id');
			if( pidArr.indexOf(selectedProductId[i]) == -1 ){
				pidArr.push(selectedProductId[i]);
			}
		});
	}
	pidStr = pidArr.join(',');
	$('#product_relation').val(pidStr);
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'add_product_relation',
			pid_arr : selectedProductId,
			activitie_id_arr : productActivityId,
			recommendation_id : recommendation_id
		},
		success: function(res){
			if( res ){
				selectedLimitStart = 0,
				selectedLimitEnd = 14,
				selectedCurrentPage = 1,
				selectedEachPageNum = 15,
				selectedTotalPage = 1;
				selectedLimitStart = 0,
				get_product_relation(pidStr);
				$('.product-box').hide();
				$('.product-table').show();
				$('#select-product').show();
			}
		},
		error: function(err){
			alert(err);
		}
	});

}

//获取团推荐关联产品
function get_product_relation(pid_str){
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id,
		dataType: 'json',
		data: {
			op : 'get_recommendation_product',
			pid_str : pid_str,
			is_count : 1,
			limitstart : selectedLimitStart,
			limitend : selectedEachPageNum
		},
		type: 'post',
		success: function(data){
			var productLen = data['product'].length,
				html = '',
				html_p = '';

			for( i in data['product'] ){
				var tag = '';
				console.log(data['product'][i]);
				html +='<tr class="product-info">';
				html +='	<td><input type="checkbox" class="product-info-checkbox" value="'+data['product'][i]['pid']+'" /></td>';
				html +='	<td>'+data['product'][i]['id']+'</td>';
				html +='	<td>'+data['product'][i]['pid']+'</td>';
				html +='	<td>'+data['product'][i]['pname']+'</td>';
				html +='	<td>'+data['product'][i]['type_name']+'</td>';
				html +='	<td>'+data['product'][i]['price']+'</td>';
				html +='	<td>'+data['product'][i]['sell_count']+'</td>';
				html +='	<td>'+data['product'][i]['stock']+'</td>';
				if( data['product'][i]['ishot'] == 1 ){
					tag += '热卖/';
				}
				if( data['product'][i]['isnew'] == 1 ){
					tag += '新品/';
				}
				if( data['product'][i]['is_free_shipping'] == 1 ){
					tag += '包邮/';
				}
				if( data['product'][i]['is_virtual'] == 1 ){
					tag += '虚拟产品/';
				}
				if( data['product'][i]['is_currency'] == 1 ){
					tag += '购物币/';
				}
				if( tag != '' ){
					tag = tag.slice(0,-1);
				}
				html +='	<td>'+tag+'</td>';
				if( data['product'][i]['is_out'] == 1 ){
					html +='	<td class="status-'+data['product'][i]['pid']+'">已发布</td>';
				} else {
					html +='	<td class="status-'+data['product'][i]['pid']+'">未发布</td>';
				}
				html +='	<td>'+data['product'][i]['createtime']+'</td>';
				html +='	<td><span class="delivery-button WSY-skin-bg del-btn" onclick="del_product('+data['product'][i]['pid']+')">删除</span>';
				if( data['product'][i]['is_out'] == 1 ){
					html +='	<span class="delivery-button WSY-skin-bg out-btn-'+data['product'][i]['pid']+'" onclick="edit_out_status(0,'+data['product'][i]['pid']+')">下架</span><span class="delivery-button WSY-skin-bg release-btn-'+data['product'][i]['pid']+'" style="display:none;" onclick="edit_out_status(1,'+data['product'][i]['pid']+')">发布</span>';
				} else {
					html +='	<span class="delivery-button WSY-skin-bg out-btn-'+data['product'][i]['pid']+'" style="display:none;" onclick="edit_out_status(0,'+data['product'][i]['pid']+')">下架</span><span class="delivery-button WSY-skin-bg release-btn-'+data['product'][i]['pid']+'" onclick="edit_out_status(1,'+data['product'][i]['pid']+')">发布</span>';
				}
				html +='	</td>';
				html +='</tr>';
			}
			if( productLen > 0){
				//翻页
				selectedTotalPage = Math.ceil(data['count'] / selectedEachPageNum);
				html_p +='<div class="page-box1">';
				html_p +='	<span class="data-num">共计'+data['count']+'条记录</span>';
				// html_p +='	<span class="show-data-num">每页<input type="text" id="show-data-num" width="25" value="'+selectedEachPageNum+'" />条</span>';
				// html_p +='	<span class="delivery-button show-data-num-btn">确定</span> ';
				if( selectedCurrentPage > 1 ){	//当前是第一页不显示上一页
					html_p +='	<span class="delivery-button WSY-skin-bg page-left" onclick="goToLeftPage(1)">上一页</span> ';
				}
				html_p +='	<span class="current-page">当前第'+selectedCurrentPage+'页，共'+selectedTotalPage+'页</span> ';
				if( selectedCurrentPage < selectedTotalPage ){	//当前是最后一页不显示下一页
					html_p +='	<span class="delivery-button WSY-skin-bg page-right" onclick="goToRightPage(1)">下一页</span> ';
				}
				html_p +='	<input type="text" id="to-page-num1" width="25" value="'+selectedCurrentPage+'" >页 ';
				html_p +='	<span class="delivery-button WSY-skin-bg go-page-btn" onclick="goToPage(1)">跳转</span> ';
				html_p +='</div>';
			}

			$('.product-info').remove();
			$('.page-box1').remove();
			$('.relation_table').append(html);
			$('.product-table').append(html_p);
		},
		error: function(err){
			alert('获取团推荐关联产品出错！');
		}
	});
}
//获取活动关联产品
function get_activity_product(){
	// if( arguments[0] != undefined ){
		// search_name = arguments[0];
	// }
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'get_all_active_product',
			search_pid : search_pid,
			search_pname : search_pname,
			search_aid : search_aid,
			search_aname : search_aname,
			search_atype : search_atype,
			pid_str : pidStr,
			is_count : 1,
			limitstart : showProductLimitStart,
			limitend : showProductEachPageNum
		},
		success: function(data){
			var productLen = data['product'].length,
				html = '',
				html_p = '',
				checkedNum = 0;

			for( i in data['product'] ){
				html +='<tr class="product-list">';
				html +='	<td><input type="checkbox" class="product-list-checkbox" data-activitie_id="'+data['product'][i]['activitie_id']+'" value="'+data['product'][i]['pid']+'" /></td>';
				html +='	<td>'+data['product'][i]['pid']+'</td>';
				html +='	<td>'+data['product'][i]['pname']+'</td>';
				html +='	<td>'+data['product'][i]['price']+'</td>';
				html +='	<td>'+data['product'][i]['activitie_id']+'</td>';
				html +='	<td>'+data['product'][i]['aname']+'</td>';
				html +='	<td>'+data['product'][i]['type_name'];
				html +='	</td>';
				html +='	<td>'+data['product'][i]['start_time']+'至'+data['product'][i]['end_time']+'</td>';
				html +='	<td>'+data['product'][i]['group_size']+'</td>';
				html +='	<td>'+data['product'][i]['pnumber']+'</td>';
				html +='	<td>'+data['product'][i]['anumber']+'</td>';
				html +='	<td><span class="delivery-button WSY-skin-bg" onclick="confirm_select_product(1,'+data['product'][i]['pid']+','+data['product'][i]['activitie_id']+')">选择</span></td>';
				html +='</tr>';
			}
			if( productLen > 0){
				//翻页
				showProductTotalPage = Math.ceil(data['count'] / showProductEachPageNum);
				html_p +='<div class="page-box2">';
				html_p +='	<span class="data-num">共计'+data['count']+'条记录</span>';
				// html_p +='	<span class="show-data-num">每页<input type="text" id="show-data-num" width="25" value="'+showProductEachPageNum+'" />条</span>';
				// html_p +='	<span class="delivery-button show-data-num-btn">确定</span> ';
				if( showProductCurrentPage > 1 ){	//当前是第一页不显示上一页
					html_p +='	<span class="delivery-button page-left" onclick="goToLeftPage(2)">上一页</span> ';
				}
				html_p +='	<span class="current-page">当前第'+showProductCurrentPage+'页，共'+showProductTotalPage+'页</span> ';
				if( showProductCurrentPage < showProductTotalPage ){	//当前是最后一页不显示下一页
					html_p +='	<span class="delivery-button WSY-skin-bg page-right" onclick="goToRightPage(2)">下一页</span> ';
				}
				html_p +='	<input type="text" id="to-page-num2" width="25" value="'+showProductCurrentPage+'" >页 ';
				html_p +='	<span class="delivery-button WSY-skin-bg go-page-btn" onclick="goToPage(2)">跳转</span> ';
				html_p +='</div>';
			}

			$('.product-list').remove();
			$('.page-box2').remove();
			$('.list_table').append(html);
			$('.product-box').append(html_p);
		},
		error: function(err){
			alert('获取活动关联产品出错！');
		}
	});
}

//输入框按回车键触发搜索
$('.header-left').find('input').on('keydown',function(){
	if( event.keyCode == 13 ){
		$('#search-button').click();
	}
});

//搜索
$('#search-button').click(function(){
	search_pid = $('#search-pid').val();
	search_pname = $('#search-pname').val();
	search_aid = $('#search-aid').val();
	search_aname = $('#search-aname').val();
	search_atype = $('#search-atype').val();

	showProductCurrentPage = 1;
	showProductLimitStart = 0;
	showProductLimitEnd = showProductEachPageNum - 1;

	get_activity_product();
});
//上一页
function goToLeftPage(type){
	if( type == 1 ){
		selectedCurrentPage --;
		selectedLimitStart -= selectedEachPageNum;
		selectedLimitEnd -= selectedEachPageNum;
		get_product_relation(pidStr);
	} else if( type == 2 ){
		showProductCurrentPage --;
		showProductLimitStart -= showProductEachPageNum;
		showProductLimitEnd -= showProductEachPageNum;
		get_activity_product();
	}
}
//下一页
function goToRightPage(type){
	if( type == 1 ){
		selectedCurrentPage ++;
		selectedLimitStart += selectedEachPageNum;
		selectedLimitEnd += selectedEachPageNum;
		get_product_relation(pidStr);
	} else if( type == 2 ){
		showProductCurrentPage ++;
		showProductLimitStart += showProductEachPageNum;
		showProductLimitEnd += showProductEachPageNum;
		get_activity_product();
	}
}
//跳转
function goToPage(type){
	if( type == 1 ){
		var pageNum = $('#to-page-num1').val();

		if( pageNum < 1 || pageNum > selectedTotalPage || pageNum == selectedCurrentPage ){
			return;
		}
		selectedCurrentPage = pageNum;

		selectedLimitStart = (selectedCurrentPage - 1) * selectedEachPageNum;

		selectedLimitEnd = selectedLimitStart + selectedEachPageNum - 1;

		get_product_relation(pidStr);
	} else if( type == 2 ){
		var pageNum = $('#to-page-num2').val();

		if( pageNum < 1 || pageNum > showProductTotalPage || pageNum == showProductCurrentPage ){
			return;
		}
		showProductCurrentPage = pageNum;

		showProductLimitStart = (showProductCurrentPage - 1) * showProductEachPageNum;

		showProductLimitEnd = showProductLimitStart + showProductEachPageNum - 1;

		get_activity_product();
	}
}
//提交
function submitV(){
	var pattern = $('input[name=pattern]:checked').val();
	// if( pattern == 1 ){
		var num = $('input[name=num]').val();
		if(  num == '' ){
			alert('请输入显示数量！');
			return;
		}
		var type = $("input[class=type]:checked");
		var type_str = '';
		type.each(function(i){
			type_str += $(this).val()+'_';
		});
		if( type_str != '' ){
			type_str = type_str.substring(0,type_str.length-1);
		}
		var style = $('input[name=style]:checked').val();
		var sort = $('input[name=sort]:checked').val();
	// }
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'save_product_recommendation',
			recommendation_id : recommendation_id,
			system_set_id : system_set_id,
			pattern : pattern,
			num : num,
			type : type_str,
			style : style,
			sort : sort
		},
		success: function(res){
			if( res > 0 ){
				system_set_id = res;
			}
			alert('保存成功');
			window.location.reload();
		},
		error: function(err){
			alert(err);
		}
	});
}

//正整数
function clearInt(obj){
	if(obj.value.length==1){obj.value=obj.value.replace(/[^1-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
}
</script>

</body>
</html>

