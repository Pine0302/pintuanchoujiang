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

$filed = 'pt.id,cp.name as p_name,pt.price,pt.activitie_id,at.name as at_name,at.type,at.status,at.group_size,pt.total_open,pt.total_success,pt.total_fail,pt.total_conduct,pt.pid';
$condition = array(
				'at.customer_id' => $customer_id,
                'ae.customer_id' => $customer_id,
                'ae.isvalid' => true,
				'at.isvalid' => true,
				'pt.isvalid' => true
			);
$search_id = '';//搜索ID
if( !empty( $_GET['search_id'] ) ){
	$search_id = $configutil->splash_new($_GET["search_id"]);
	$condition['at.id'] = $search_id;
}
$search_name = '';//搜索标题
if( !empty( $_GET['search_name'] ) ){
	$search_name = $configutil->splash_new($_GET["search_name"]);
	$condition['at.name'] = $search_name;
}
$search_type = '';//搜索类型
if( !empty( $_GET['search_type'] ) ){
	$search_type = $configutil->splash_new($_GET["search_type"]);
	$condition['at.type'] = $search_type;
}
$search_status = '';//搜索状态
if( !empty( $_GET['search_status'] ) ){
	$search_status = $configutil->splash_new($_GET["search_status"]);
	$condition['at.status'] = $search_status;
}

$dcount = $collageActivities->get_activities_product($condition,'count(1) AS dcount');
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
$condition['LIMIT'] = " LIMIT ".$start.",".$end."";
$condition['ORDER'] = " order by at.createtime desc";
$list = $collageActivities->get_activities_product($condition,$filed);

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
<title>产品活动汇总</title>
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
.operation-btn{padding: 3px 5px;color: #fff;border-radius: 2px;cursor:pointer;}
.WSY_t2 td {
    max-width: 200px;
    overflow: hidden;
}
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
			<span style="margin-left:10px;">活动ID：</span>
      		<input type="text" name="search_id" id="search_id" onkeyup="clearInt(this)" value="<?php echo $search_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<span style="margin-left:10px;">标题：</span>
      		<input type="text" name="search_name" id="search_name" value="<?php echo $search_name;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">


			<span style="margin-left:10px;">活动类型：</span>
			<select id="search_type" name="search_type">
				<option value="0">全部</option>
                <?php
                    $type_list = $collageActivities->getTypes($customer_id);
                    foreach($type_list as $key => $val){
                ?>
                <option value="<?php echo $val['type'];?>" <?php if($search_type==$val['type']){echo 'selected';} ?>><?php echo $val['type_name'];?></option>
                <?php
                    }
                ?>
			</select>

			<span style="margin-left:10px;">活动状态：</span>
			<select id="search_status" name="search_status">
				<option value="0">全部</option>
				<option value="1" <?php if($search_status==1){echo 'selected';} ?>>未发布</option>
				<option value="2" <?php if($search_status==2){echo 'selected';} ?>>进行中</option>
				<option value="3" <?php if($search_status==3){echo 'selected';} ?>>终止</option>
				<option value="4" <?php if($search_status==4){echo 'selected';} ?>>已结束</option>
			</select>


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
					<th width="2%" >产品编码</th>
					<th width="6%">产品名称</th>
					<th width="6%">活动价格</th>
					<th width="6%">活动ID</th>
					<th width="6%">活动主题</th>
					<th width="6%">活动类型</th>
					<th width="6%">活动状态</th>
					<th width="6%">成团人数</th>
					<th width="6%">开团总数</th>
					<th width="6%">成功拼团数</th>
					<th width="6%">失败拼团数</th>
					<th width="6%">进行中的拼团</th>
					<th width="6%">总金额</th>
					<th width="6%">操作</th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach($list['data'] as $key => $val){
					$filed = 'ot.total_price';
					//$condition = 'ot.id='.$val['id'].' AND ot.isvalid=true AND at.id='.$val['activitie_id'];
                    $condition = array(
                        'ot.id' => $val['id'],
                        'ot.isvalid' => true,
                        'at.id' => $val['activitie_id']
                    );
					$total_price = $collageActivities->get_group_order($condition,$filed);
			?>
				<tr style="border:1px solid #D8D8D8">
					<td><?php echo $val['id'];?></td>
					<td><?php echo $val['p_name'];?></td>
					<td><?php echo $val['price'];?></td>
					<td><?php echo $val['activitie_id'];?></td>
					<td><?php echo htmlspecialchars($val['at_name']);?></td>
					<td>
					<?php
                        echo $group_type_arr[$val['type']];
					?>
					</td>
					<td>
					<?php
						switch($val['status']){
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
					<td><?php echo $val['group_size'];?></td>
					<td><?php echo $val['total_open'];?></td>
					<td><?php echo $val['total_success'];?></td>
					<td><?php echo $val['total_fail'];?></td>
					<td><?php echo $val['total_conduct'];?></td>
					<td><?php echo $aprice = empty($total_price[0]['total_price'])?0:$total_price[0]['total_price'];?></td>
					<td class="WSY_remind_main">
						<a href="groupOrder.php?keyid=<?php echo $val['activitie_id']; ?>&search_pro_id=<?php echo $val['pid']; ?>" style="cursor:pointer;">
							<span class="operation-btn WSY-skin-bg">团活动明细</span>
						</a>
					</td>
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
			var search_id = $("#search_id").val();
			var search_name = $("#search_name").val();
			var search_type = $("#search_type").val();
			var search_status = $("#search_status").val();


			$(".WSY_page").createPage({
				pageCount:count,
				current:pagenum,
				backFn:function(p){
				 document.location= "proMes.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pagenum="+p+"&search_id="+search_id+"&search_name="+search_name+"&search_type="+search_type+"&search_status="+search_status;
			   }
			});

		    var page = <?php echo $page ?>;

		    function jumppage(){
				var a=parseInt($("#WSY_jump_page").val());
				if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
					return false;
				}else{
					document.location= "proMes.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pagenum="+a+"&search_id="+search_id+"&search_name="+search_name+"&search_type="+search_type+"&search_status="+search_status;
				}
		    }

			//输入框按回车键触发搜索
			$('.search-box').find('input').on('keydown',function(){
				if( event.keyCode == 13 ){
					$('#my_search').click();
				}
			});

		$('#my_search').click(function(){
			var search_id = $("#search_id").val();
			var search_name = $("#search_name").val();
			var search_type = $("#search_type").val();
			var search_status = $("#search_status").val();

			var url = "proMes.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>";

			if( search_id != '' ){
				if(search_id > 0){
					url += "&search_id="+search_id;
				}else{
					alert('请输入正确的ID！');
					return;
				}
			}
			if(search_name!=''){
				url += "&search_name="+search_name;
			}
			if(search_status > 0){
				url += "&search_status="+search_status;
			}
			if(search_type > 0){
				url += "&search_type="+search_type;
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
