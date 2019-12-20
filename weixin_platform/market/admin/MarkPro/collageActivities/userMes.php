<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../../weixinpl/proxy_info.php');
require('../../../../weixinpl/function_model/collageActivities.php');
_mysql_query("SET NAMES UTF8");

$collageActivities = new collageActivities($customer_id);//自定义类

$filed = 'wu.id,wu.name as user_name,wu.weixin_name as user_weixin_name,wu.phone,wu.weixin_headimgurl,wu.createtime,wu2.name as parent_name,wu2.weixin_name as parent_weixin_name,mt.total_partakep,mt.total_open,mt.total_success,mt.total_fail';

$condition = array('wu.customer_id'=>$customer_id,'wu.isvalid'=>true,'mt.isvalid'=>true);
$search_user_id = '';//搜索ID
if( !empty( $_GET['search_user_id'] ) ){
	$search_user_id = $configutil->splash_new($_GET["search_user_id"]);
	$condition['wu.id'] = $search_user_id;
}
$search_name = '';//搜索名字
if( !empty( $_GET['search_name'] ) ){
	$search_name = $configutil->splash_new($_GET["search_name"]);
	$condition['wu.name'] = $search_name;
}
$search_weixin_name = '';//搜索微信昵称
if( !empty( $_GET['search_weixin_name'] ) ){
	$search_weixin_name = $configutil->splash_new($_GET["search_weixin_name"]);
	$condition['wu.weixin_name'] = $search_weixin_name;
}
$search_phone = '';//搜索电话
if( !empty( $_GET['search_phone'] ) ){
	$search_phone = $configutil->splash_new($_GET["search_phone"]);
	$condition['wu.phone'] = $search_phone;
}

$dcount = $collageActivities->get_activities_user($condition,'count(1) AS dcount');
//分页---start
$pagenum = 1;
$pagesize = 20;
$begintime="";
$endtime ="";
if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}
$start = ($pagenum-1) * $pagesize;
$end = $pagesize;
$rcount_q = $dcount['data'][0]['dcount'];
$page=ceil($rcount_q/$end);

 /* 输出数量结束 */
$condition['ORDER'] = " ORDER BY wu.createtime DESC";
$condition['LIMIT'] = " LIMIT ".$start.",".$end."";
$list = $collageActivities->get_activities_user($condition,$filed);

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>用户参与团数据</title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/inside.css" media="all">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<script src="../../Common/js/Data/js/echarts/echarts.js"></script>
<script type="text/javascript" src="../../Common/js/Data/js/ichartjs/ichart.1.2.min.js"></script>
<script type="text/javascript" src="../../../common/js/inside.js"></script>
<style>

table th{color: #FFF;line-height: 30px;text-align: center;font-size: 12px; }
table td{height: 40px;line-height: 20px;font-size: 12px;color: #323232;padding: 0px 1em;text-align: center;border: 1px solid #D8D8D8; }
.display{display:none}
table td img{width: 20px;height: 20px;margin-left: 5px;}
</style>

</head>

<body id="bod" style="min-height: 580px;">
	<!--内容框架-->
	<div class="WSY_content" style="height: 100%;">

		<!--列表内容大框-->
		<div class="WSY_columnbox">
			<!--列表头部切换开始-->

				<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>

			<!--列表头部切换结束-->
<!--门店列表开始-->
  <div  class="WSY_data">
	 <!--列表按钮开始-->
      <div class="WSY_list" id="WSY_list">

      	<div class="search-box" style="margin-left:40px;margin-top:0px;">
			<span style="margin-left:10px;">用户ID：</span>
      		<input type="text" name="search_user_id" id="search_user_id" onkeyup="clearInt(this)" value="<?php echo $search_user_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<span style="margin-left:10px;">微信昵称：</span>
      		<input type="text" name="search_weixin_name" id="search_weixin_name" value="<?php echo $search_weixin_name;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<span style="margin-left:10px;">真实姓名：</span>
      		<input type="text" name="search_name" id="search_name" value="<?php echo $search_name;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<span style="margin-left:10px;">手机号：</span>
      		<input type="text" name="search_phone" id="search_phone" onkeyup="clearInt(this)" value="<?php echo $search_phone;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<input type="button" id="my_search" value="搜索" >

		</div>
             <br class="WSY_clearfloat";>
        </div>
        <!--列表按钮开始-->

        <!--表格开始-->
		<div class="WSY_data" id="type1" style="margin-left: 1.5%;">
		<table class="WSY_t2"  width="97%"  style="border: 1px solid #D8D8D8;border-collapse: collapse;">
			<thead class="WSY_table_header">
				<tr style="border:none">
					<th width="4%">用户ID</th>
					<th width="10%">用户</th>
					<th width="10%">推荐人</th>
					<th width="10%">注册时间</th>
					<th width="6%">参团总数</th>
					<th width="6%">开团总数</th>
					<th width="6%">成功团数</th>
					<th width="6%">失败团数</th>
					<th width="6%">成功率</th>
					<th width="6%">失败率</th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach($list['data'] as $key => $val){

			?>
				<tr style="border:1px solid #D8D8D8">
					<td><?php echo $val['id'];?></td>
					<td><img style="width: 50px;height: 50px;float: left;border-radius: 30px;" src="<?php echo $val['weixin_headimgurl'];?>"><?php echo $val['user_weixin_name'];?><br><?php echo $val['user_name'];?><br><?php echo $val['phone'];?></td>
					<td><?php echo $val['parent_name'];?><br><?php echo $val['parent_weixin_name'];?></td>
					<td><?php echo $val['createtime'];?></td>
					<td><?php echo $val['total_partakep'];?></td>
					<td><?php echo $val['total_open'];?></td>
					<td><?php echo $val['total_success'];?></td>
					<td><?php echo $val['total_fail'];?></td>
					<td><?php echo round( ( $val['total_success']/$val['total_partakep'] ) * 100,2 );?>%</td>
					<td><?php echo round( ( $val['total_fail']/$val['total_partakep'] ) * 100,2 );?>%</td>
				</tr>
			<?PHP }?>

			</tbody>

			</table>
			<!--翻页开始-->
			<div class="WSY_page">

			</div>
			<!--翻页结束-->
		</div>
		<script src="../../../js/fenye/jquery.page1.js"></script>
		<script type="text/javascript">
			var customer_id = '<?php echo $customer_id_en ?>';
			var customer_id1 = '<?php echo $customer_id ?>';
			var pagenum = <?php echo $pagenum ?>;
			var count =<?php echo $page ?>;//总页数
				//pageCount：总页数
				//current：当前页
			var search_user_id = $("#search_user_id").val();
			var search_name = $("#search_name").val();
			var search_weixin_name = $("#search_weixin_name").val();
			var search_phone = $("#search_phone").val();

			$(".WSY_page").createPage({
				pageCount:count,
				current:pagenum,
				backFn:function(p){
				 document.location= "userMes.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pagenum="+p+"&search_user_id="+search_user_id+"&search_name="+search_name+"&search_weixin_name="+search_weixin_name+"&search_phone="+search_phone;
			   }
			});

		    var page = <?php echo $page ?>;

		    function jumppage(){
				var a=parseInt($("#WSY_jump_page").val());
				if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
					return false;
				}else{
					document.location= "userMes.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pagenum="+a+"&search_user_id="+search_user_id+"&search_name="+search_name+"&search_weixin_name="+search_weixin_name+"&search_phone="+search_phone;
				}
		    }

			//输入框按回车键触发搜索
			$('.search-box').find('input').on('keydown',function(){
				if( event.keyCode == 13 ){
					$('#my_search').click();
				}
			});

		$('#my_search').click(function(){
			var search_user_id = $("#search_user_id").val();
			var search_name = $("#search_name").val();
			var search_weixin_name = $("#search_weixin_name").val();
			var search_phone = $("#search_phone").val();

			var url = "userMes.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>";

			if( search_user_id != '' ){
				if(search_user_id > 0){
					url += "&search_user_id="+search_user_id;
				}else{
					alert('请输入正确的用户ID！');
					return;
				}
			}
			if( search_phone != '' ){
				if(search_phone > 0){
					url += "&search_phone="+search_phone;
				}else{
					alert('请输入正确的电话！');
					return;
				}
			}
			if(search_name!=''){
				url += "&search_name="+search_name;
			}
			if(search_weixin_name!=''){
				url += "&search_weixin_name="+search_weixin_name;
			}
			document.location=url;
		});

		function clearInt(obj){
			if(obj.value.length==1){obj.value=obj.value.replace(/[^1-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
		}
		</script>

	</div>
</div>
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/fenye/fenye.css" media="all">


<?php

mysql_close($link);
?>

</body>
</html>
