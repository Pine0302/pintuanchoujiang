<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../../../../weixinpl/proxy_info.php');
require('../../../../weixinpl/function_model/collageActivities.php');

$collageActivities = new collageActivities($customer_id);

$data = $collageActivities->getExplain($customer_id);
?>
<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>活动说明</title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<style>
.operation-btn{padding: 7px 18px;color: #fff;border-radius: 2px;cursor:pointer;}
table#WSY_t1 td {
    text-align: center !important;
}
</style>
</head>
<body>
<style>
.WSY_t4 a img {
    margin-bottom: -5px;
}
</style>
	<!--内容框架-->
<div class="WSY_content">
		<!--列表内容大框-->
	<div class="WSY_columnbox">
	<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>
		<!--列表头部切换开始-->
		<!--列表头部切换结束-->
<!--门店列表开始-->
		<div class="WSY_data" style="min-height:180px">
			<!--表格开始-->
			<table width="97%" class="WSY_table WSY_t2" id="WSY_t1">
				<thead class="WSY_table_header">
					<tr>
						<th width="5%">序号</th>
						<th width="10%">标题</th>
						<th width="10%">创建时间</th>
						<th width="10%">状态</th>
						<th width="10%">操作</th>
					</tr>
				</thead>
				<form name="form1" method="post">
					<tbody>
						<?php
						 foreach( $data['content'] as $key => $val ){

							   $status_str = '';//发布状态
							   $status_tips = '';//操作提示
							   $status_op = '';//操作语
							   if( 1 == $val['status'] ){
								   $status_str = '已发布';
								   $status_tips = '下架后，用户将无法查看相关动态信息，是否下架？';
								   $status_op = '下架';
							   }elseif( 2 == $val['status'] ){
								    $status_str = '未发布';
									$status_tips = '信息发布后，将会在官方动态中显示相关内容，是否发布？';
									$status_op = '发布';
							   }
						?>
						<tr>
							<td><?php echo $val['id'] ?></td>
							<td><?php echo $val['title']; ?></td>
							<td><?php echo $val['createtime']; ?></td>
							<td id="status_str_<?php echo $val['id'] ?>"><?php echo $status_str; ?></td>
							<td>
								<a href="edit_explain.php?keyid=<?php echo $val['id'] ?>&customer_id=<?php echo passport_encrypt((string)$customer_id) ?>" style="cursor:pointer;">
								<span class="operation-btn WSY-skin-bg">编辑</span>
								</a>
								<span id="a_<?php echo $val['id'] ?>">
									<a onclick="if(confirm(&#39;<?php echo $status_tips; ?>&#39;)){change_status(<?php echo $val['id'] ?>,<?php echo $val['status'] ?>)};" style="cursor:pointer;">
									<span class="operation-btn WSY-skin-bg"><?php echo $status_op; ?></span>
								</span>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</form>
			</table>
				<!--表格结束-->
				<div class="blank20"></div>
				<div id="turn_page"></div>
				<!--翻页开始-->
				<div class="WSY_page">

				</div>
				<!--翻页结束-->
		</div> <!--门店列表结束-->
		<?php
			mysql_close($link);
		?>
			<div style="width:100%;height:20px;"></div>
	</div>
</div>
<script>
function change_status(keyid,status){
	$.ajax({
		type: 'POST',
		url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
		data:{
			op		: 'explain',
			keyid	: keyid,
			status 	: status
		},
		dataType: 'json',
		success: function(data){
			if( 1 == data.code ){
				if( 1 == data.before_statu ){
					var statu_srt = '已发布';
					var a = '<a id="a_'+keyid+'" onclick="if(confirm(&#39;下架后，用户将无法查看相关动态信息，是否下架？&#39;)){change_status('+keyid+','+data.before_statu+')};"  style="cursor:pointer;" ><span class="operation-btn WSY-skin-bg">下架</span></a>';
				}else if( 2 == data.before_statu ){
					var statu_srt = '未发布';
					var a = '<a id="a_'+keyid+'" onclick="if(confirm(&#39;信息发布后，将会在官方动态中显示相关内容，是否发布？&#39;)){change_status('+keyid+','+data.before_statu+')};"  style="cursor:pointer;"><span class="operation-btn WSY-skin-bg">发布</span></a>';
				}
				$('#status_str_'+keyid).text(statu_srt);
				$('#a_'+keyid).html(a);
			}else if( 10001 == data.code ){
				alert(data.tips);
			}
		}
	});
}
</script>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
</body>
</html>
