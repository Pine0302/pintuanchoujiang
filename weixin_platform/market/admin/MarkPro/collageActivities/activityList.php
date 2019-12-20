<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../../../../weixinpl/proxy_info.php');
_mysql_query("SET NAMES UTF8");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/collageActivities.php");
$collageActivities = new collageActivities($customer_id);

$pagenum = 1;//页码
$pagesize = 20;//每页数据数量

if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}

$start = ($pagenum-1) * $pagesize;
$end = $pagesize;

$query_activity = "SELECT id,name,type,start_time,end_time,group_size,number,head_times,ginseng_num,status,createtime FROM collage_activities_t WHERE customer_id=".$customer_id." AND isvalid=true";
$query_count = "SELECT count(1) as acount FROM collage_activities_t WHERE customer_id=".$customer_id." AND isvalid=true";

$search_id = '';
if( !empty( $_GET['search_id'] ) && $_GET['search_id'] > 0 ){
	$search_id = $configutil->splash_new($_GET["search_id"]);
	$query_activity .= " AND id=".$search_id." ";
	$query_count .= " AND id=".$search_id." ";
}
$search_name = '';
if( !empty( $_GET['search_name'] ) ){
	$search_name = $configutil->splash_new($_GET["search_name"]);
	$query_activity .= " AND name like '%".$search_name."%' ";
	$query_count .= " AND name like '%".$search_name."%' ";
}
$search_type = -1;
if( !empty( $_GET['search_type'] ) && $_GET['search_type'] > 0 ){
	$search_type = $configutil->splash_new($_GET["search_type"]);
	$query_activity .= " AND type=".$search_type." ";
	$query_count .= " AND type=".$search_type." ";
}
$search_status = -1;
if( !empty( $_GET['search_status'] ) && $_GET['search_status'] > 0 ){
	$search_status = $configutil->splash_new($_GET["search_status"]);
	$query_activity .= " AND status=".$search_status." ";
	$query_count .= " AND status=".$search_status." ";
}

$query_activity  .= " ORDER BY createtime DESC LIMIT ".$start.",".$end;

$result_count = _mysql_query($query_count) or die('Query_count failed:'.mysql_error());
$acount = mysql_fetch_assoc($result_count)['acount'];

$page = ceil($acount/$end);

$group_type_arr = array();
$query = "SELECT type,type_name FROM collage_activities_explain_t WHERE isvalid=true AND customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed'.mysql_error());
while ( $row = mysql_fetch_object($result) ) {
	$type = $row->type;
    $type_name = $row->type_name;
    $group_type_arr[$type] = $type_name;
}
?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<title>活动管理列表</title>
	<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
	<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
	<link href="../../../common/add/css/global.css" rel="stylesheet" type="text/css">
	<link href="../../../common/add/css/main.css" rel="stylesheet" type="text/css">
	<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/inside.css" media="all">
	<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="../../../common/js/inside.js"></script>
	<script type="text/javascript" src="../../../js/tis.js"></script>
	<style>
		.operation-btn{display:inline-block;padding:0 15px;color:#fff;border-radius:3px;cursor:pointer;height:30px;line-height:30px;}
		.WSY_list{width:97%;margin-left:18px;}
		.header-left{float:left;}
		.header-left span,.header-left input,.header-left select{vertical-align:middle;}
		a:hover{text-decoration: none!important;}
		table#WSY_t1 td{text-align: center;}
		@media (max-width: 1713px) {
		  .operation-btn { margin:2px 0 0; }
		  .operation-btn:nth-child(3) { margin:2px 0 3px; }
		}
	</style>
</head>
<body>
<!--内容框架开始-->
<div class="WSY_content" id="WSY_content_height">

       <!--列表内容大框开始-->
	<div class="WSY_columnbox">
<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>
    <div class="WSY_data">
    	<div class="WSY_list">
			<div class="header-left">
				<span>活动ID：</span><input type="text" class="search-box" id="search-id" value="<?php echo $search_id;?>" onkeyup="clearInt(this)" />
				<span>标题：</span><input type="text" class="search-box" id="search-name" value="<?php echo $search_name;?>" />
				<span>活动类型：</span>
				<select id="search-type">
					<option value="-1" <?php if($search_type==-1){echo 'selected';}?>>全部</option>
                    <?php
                        $type_list = $collageActivities->getTypes($customer_id);
                        foreach($type_list as $key => $val){
                    ?>
					<option value="<?php echo $val['type'];?>" <?php if($search_type==$val['type']){echo 'selected';}?>><?php echo $val['type_name'];?></option>
                    <?php
                        }
                    ?>
				</select>
				<span>状态：</span>
				<select id="search-status">
					<option value="-1" <?php if($search_status==-1){echo 'selected';}?>>全部</option>
					<option value="1" <?php if($search_status==1){echo 'selected';}?>>未发布</option>
					<option value="2" <?php if($search_status==2){echo 'selected';}?>>进行中</option>
					<option value="3" <?php if($search_status==3){echo 'selected';}?>>终止</option>
					<option value="4" <?php if($search_status==4){echo 'selected';}?>>已结束</option>
				</select>
				<span class="operation-btn WSY-skin-bg" id="search-button">搜索</span>
			</div>
        <ul class="WSY_righticon">
			<li style="cursor:pointer;" onclick="exportExcel();"><a><td valign="bottom" align="right" >导出</td></a></li>
            <li><a href="addActivity.php?customer_id=<?php echo passport_encrypt((string)$customer_id);?>"><td valign="bottom" align="right">添加活动</td></a></li>
        </ul>
    </div>
			<table width="97%" class="WSY_table" id="WSY_t1">
				<thead class="WSY_table_header">
					<th width="7%">活动ID</th>
					<th width="15%">主题</th>
					<th width="8%">类型</th>
					<th width="15%">活动时间</th>
					<th width="8%">成团人数</th>
					<th width="16%">活动次数限制</th>
					<th width="8%">状态</th>
					<th width="10%">创建时间</th>
					<th width="20%">操作管理</th>
				</thead>
				<?php
					$result_activity = _mysql_query($query_activity) or die('Query_activity failed:'.mysql_error());
					while( $row_activity = mysql_fetch_assoc($result_activity) ){
				?>
				<tr>
					<td><?php echo $row_activity['id'];?></td>
					<td><a href="addActivity.php?customer_id=<?php echo passport_encrypt((string)$customer_id);?>&keyid=<?php echo $row_activity['id'];?>"><?php echo htmlspecialchars($row_activity['name']);?></a></td>
					<td>
					<?php
                        echo $group_type_arr[$row_activity['type']];
					?>
					</td>
					<td><?php echo $row_activity['start_time'].'至'.$row_activity['end_time'];?></td>
					<td><?php echo $row_activity['group_size'];?></td>
					<td>
                    参团：
					<?php                    
						if( $row_activity['ginseng_num'] > 0 ){
							echo $row_activity['ginseng_num'];
						} else {
							echo '不限';
						}
					?>；
                    开团：
                    <?php                    
						if( $row_activity['head_times'] > 0 ){
							echo $row_activity['head_times'];
						} else {
							echo '不限';
						}
					?>
					</td>
					<td>
					<?php
						switch( $row_activity['status'] ){
							case 1:
								echo '未发布';
							break;
							case 2:
								echo '进行中';
							break;
							case 3:
								echo '终止';
							break;
							case 4:
								echo '已结束';
							break;
						}
					?>
					</td>
					<td><?php echo $row_activity['createtime'];?></td>
					<td>
					<?php
						if( $row_activity['status'] == 1 ){
					?>
						<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">编辑</span>
						<span class="operation-btn WSY-skin-bg" onclick="release('<?php echo $row_activity['id'];?>')">发布</span>
						<span class="operation-btn WSY-skin-bg" onclick="stop('<?php echo $row_activity['id'];?>',<?php echo $row_activity['type']; ?>)"
						>终止</span>
					<?php
						}
						if( $row_activity['status'] == 2 ){
					?>
						<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">编辑</span>
						<span class="operation-btn WSY-skin-bg" onclick="stop('<?php echo $row_activity['id'];?>',<?php echo $row_activity['type']; ?>)"
						>终止</span>
						<span class="operation-btn WSY-skin-bg" onclick="check('<?php echo $row_activity['id'];?>')">查看产品活动列表</span>
					<?php
						}
						if( $row_activity['status'] == 3 || $row_activity['status'] == 4 ){
					?>
						<span class="operation-btn WSY-skin-bg" onclick="check('<?php echo $row_activity['id'];?>')">查看产品活动列表</span>
					<?php
						}
					?>
					</td>
				</tr>
				<?php }?>
			</table>
    	</div>
        <!--翻页开始-->
        <div class="WSY_page">

        </div>
        <!--翻页结束-->
    </div>
</div>
<!--内容框架结束-->
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script src="../../../js/fenye/jquery.page1.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<script>
var customer_id = '<?php echo $customer_id;?>';
var customer_id_en = '<?php echo $customer_id_en;?>';
var search_id = '<?php echo $search_id;?>';
var search_name = '<?php echo $search_name;?>';
var search_type = <?php echo $search_type;?>;
var search_status = <?php echo $search_status;?>;


  var pagenum = <?php echo $pagenum ?>;
  var count =<?php echo $page ?>;//总页数
  	//pageCount：总页数
	//current：当前页
	$(".WSY_page").createPage({
        pageCount:count,
        current:pagenum,
        backFn:function(p){
			var url = "activityList.php?pagenum="+p+"&customer_id=<?php echo passport_encrypt((string)$customer_id) ?>";
			if( search_id != '' && search_id > 0 ){
				url += '&search_id='+search_id;
			}
			if( search_name != '' ){
				url += '&search_name='+search_name;
			}
			if( search_type > 0 ){
				url += '&search_type='+search_type;
			}
			if( search_status > 0 ){
				url += '&search_status='+search_status;
			}
			document.location= url;
	   }
    });
</script>

<script>
var pagenum = <?php echo $pagenum ?>;
var page = <?php echo $page ?>;
function jumppage(){
	var a=parseInt($("#WSY_jump_page").val());
	if((a<1) || (a==pagenum) || isNaN(a)){
		return false;
	}else{
		var url = "activityList.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&pagenum="+a;
		if( search_id != '' && search_id > 0 ){
			url += '&search_id='+search_id;
		}
		if( search_name != '' ){
			url += '&search_name='+search_name;
		}
		if( search_type > 0 ){
			url += '&search_type='+search_type;
		}
		if( search_status > 0 ){
			url += '&search_status='+search_status;
		}
		document.location= url;
	}
}
</script>
<script>
//导出
function exportExcel(){
	var url='/weixin/plat/app/index.php/Excel/commonshop_excel_activity_list/customer_id/<?php echo passport_decrypt($customer_id); ?>';

	if( search_id != '' && search_id > 0 ){
		url += '/search_id/'+search_id;
	}
	if( search_name != '' ){
		url += '/search_name/'+search_name;
	}
	if( search_type > 0 ){
		url += '/search_type/'+search_type;
	}
	if( search_status > 0 ){
		url += '/search_status/'+search_status;
	}

	document.location = url;
}

//输入框按回车键触发搜索
$('.header-left').find('input').on('keydown',function(){
	if( event.keyCode == 13 ){
		$('#search-button').click();
	}
});

//搜索
$('#search-button').click(function(){
	var search_id = $('#search-id').val();
	var search_name = $('#search-name').val();
	var search_type= $('#search-type').val();
	var search_status = $('#search-status').val();

	var url = "activityList.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>";
	if( search_id != '' && search_id > 0 ){
		url += '&search_id='+search_id;
	}
	if( search_name != '' ){
		url += '&search_name='+search_name;
	}
	if( search_type > 0 ){
		url += '&search_type='+search_type;
	}
	if( search_status > 0 ){
		url += '&search_status='+search_status;
	}
	document.location= url;
});
//编辑
function edit(id){
	window.location.href = "addActivity.php?customer_id="+customer_id_en+"&keyid="+id;
}
//产品活动列表
function check(id){
	window.location.href = "proActivityList.php?customer_id="+customer_id_en+"&keyid="+id;
}
//发布
function release(id){
	if( !confirm('发布成功后，活动正式生效，用户可正式开团，是否发布？') ){
		return;
	}
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'release',
			id : id
		},
		success: function(res){
			if( res > 0 ){
				window.location.reload();
			}
		}
	});
}
//终止
function stop(id,type){
	if( !confirm('终止后，用户不可再发起团活动，已进行中团活动不影响，是否终止？') ){
		return;
	}
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'stop',
			id : id,
			type : type
		},
		success: function(res){
			if( res > 0 ){
				window.location.reload();
			}
		}
	});
}

//正整数
function clearInt(obj){
	if(obj.value.length==1){obj.value=obj.value.replace(/[^1-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
}
</script>
<?php
	mysql_close($link);
?>
</body>
</html>