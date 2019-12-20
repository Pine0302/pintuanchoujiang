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

$keyid = -1;//活动ID
if( !empty( $_GET['keyid'] ) ){
	$keyid = $configutil->splash_new($_GET["keyid"]);
}
//分页---start
$pagenum = 1;
$pagesize = 15;
$begintime="";
$endtime ="";
if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}
$start = ($pagenum-1) * $pagesize;
$end = $pagesize;
 /* 输出数量结束 */


/*查找基本信息---start*/
$filed = 'at.status as a_status,at.name as a_name,at.type,ae.type_name';//查找字段
$condition = array(
				'at.isvalid' => true,
				'pt.isvalid' => true,
				'cp.isvalid' => true,
                'ae.isvalid' => true,
				'at.id' => $keyid,
				'at.customer_id' => $customer_id,
                'ae.customer_id' => $customer_id
			);
$data = $collageActivities->get_activities_product($condition,$filed);

/*查找基本信息---end*/

/*查找本页信息---start*/
$filed = 'at.status as a_status,at.name as a_name,at.type,pt.pid,pt.price,pt.stock,pt.total_open,pt.total_success,pt.total_fail,pt.total_conduct,pt.status as p_status,cp.name as p_name,cp.orgin_price,cp.now_price';//查找字段
$search_pid = '';//搜索产品ID
if( !empty( $_GET['search_pid'] ) ){
	$search_pid = $configutil->splash_new($_GET["search_pid"]);
	$condition['pt.pid'] = $search_pid;
}
$search_pname = '';//搜索产品名字
if( !empty( $_GET['search_pname'] ) ){
	$search_pname = $configutil->splash_new($_GET["search_pname"]);
	$condition['cp.name'] = $search_pname;
}
$search_status = 0;//搜索状态
if( !empty( $_GET['search_status'] ) ){
	$search_status = $configutil->splash_new($_GET["search_status"]);
	$condition['pt.status'] = $search_status;
}

$dcount = $collageActivities->get_activities_product($condition,'count(1) as dcount');//查找总数量
$rcount_q = $dcount['data'][0]['dcount'];
$page=ceil($rcount_q/$end);


$condition['LIMIT'] = ' LIMIT '.$start.",".$end;
$collageActivities = new collageActivities();
$list = $collageActivities->get_activities_product($condition,$filed);

/*查找本页信息---end*/

$a_status_str = '';//活动状态
switch($data['data'][0]['a_status']){
	case 1:
		$a_status_str = '未发布';
	break;
	case 2:
		$a_status_str = '进行中';
	break;
	case 3:
		$a_status_str = '终止';
	break;
	case 4:
		$a_status_str = '已结束';
	break;
}
$type_str = '';//活动类型
$type_str = $data['data'][0]['type_name'];



?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>产品活动列表</title>
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
.tips{margin-left:40px;position: absolute;margin-top: 13px;}
.tips span{font-size:15px;margin-left: 10px;}
.redfont{color:red;display:inline-block;max-width:300px;height:17px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;}
.navbox{z-index: 999;}
.operation-btn{display:inline-block;padding: 3px 5px;color: #fff;border-radius: 2px;cursor:pointer;margin: 2px 0;}

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
			<span style="margin-left:10px;">产品ID：</span>
      		<input type="text" name="search_pid" id="search_pid" onkeyup="clearInt(this)" value="<?php echo $search_pid;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<span style="margin-left:10px;">产品名称：</span>
      		<input type="text" name="search_pname" id="search_pname" value="<?php echo $search_pname;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">


			<span style="margin-left:10px;">状态：</span>
			<select id="search_status" name="search_status">
				<option value="0">全部</option>
				<?php
					switch($data['data'][0]['type']){
						case 1:case 4:case 3:case 5:case 6:
				?>
						<option value="1" <?php if($search_status==1){echo 'selected';} ?>>进行中</option>
						<option value="2" <?php if($search_status==2){echo 'selected';} ?>>无效</option>
						<option value="4" <?php if($search_status==4){echo 'selected';} ?>>已结束</option>
				<?php
						break;
						case 2:
				?>
						<option value="1" <?php if($search_status==1){echo 'selected';} ?>>进行中</option>
						<option value="2" <?php if($search_status==2){echo 'selected';} ?>>无效</option>
						<option value="3" <?php if($search_status==3){echo 'selected';} ?>>待抽奖</option>
						<option value="4" <?php if($search_status==4){echo 'selected';} ?>>已结束</option>
				<?php
						break;
					}
				?>
			</select>


			<input type="button" id="my_search" value="搜索" >

		</div>

		<div class="tips">
			<span>活动ID：<span class="redfont"><?php echo $keyid; ?></span></span>
			<span>活动名称：<span class="redfont"><?php echo htmlspecialchars($data['data'][0]['a_name']);  ?></span></span>
			<span>活动类型：<span class="redfont"><?php echo $type_str;  ?></span></span>
			<span>活动状态：<span class="redfont"><?php echo $a_status_str;  ?></span></span>
		</div>


             <br class="WSY_clearfloat";>
        </div>
        <!--列表按钮开始-->

        <!--表格开始-->
		<div class="WSY_data" id="type1" style="margin-left: 1.5%;">
		<table class="WSY_t2"  width="97%"  style="border: 1px solid #D8D8D8;border-collapse: collapse;">
			<thead class="WSY_table_header">
				<tr style="border:none">
					<th width="2%" >产品ID</th>
					<th width="20%" >产品名称</th>
					<th width="6%">市场价</th>
					<th width="6%">销售价</th>
					<th width="6%">活动价</th>
					<th width="6%">活动库存</th>
					<th width="6%">开团总数</th>
					<th width="6%">成功数</th>
					<th width="6%">失败数</th>
					<th width="6%">进行中数</th>
					<th width="6%">状态</th>
					<th width="10%">操作管理</th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach($list['data'] as $key => $val){
			?>
				<tr style="border:1px solid #D8D8D8">
					<td><?php echo $val['pid'];?></td>
					<td><?php echo $val['p_name'];?></td>
					<td><?php echo $val['orgin_price'];?></td>
					<td><?php echo $val['now_price'];?></td>
					<td><?php echo $val['price'];?></td>
					<td><?php echo $val['stock'];?></td>
					<td><?php echo $val['total_open'];?></td>
					<td><?php echo $val['total_success'];?></td>
					<td><?php echo $val['total_fail'];?></td>
					<td><?php echo $val['total_conduct'];?></td>
					<td id="status">
					<?php
						switch($val['p_status']){
							case 1:
								echo '进行中';
							break;
							case 2:
								echo '无效';
							break;
							case 3:
								echo '待抽奖';
							break;
							case 4:
								echo '已结束';
							break;
						}
					?>
					</td>
					<td class="WSY_remind_main">
						<?php
							switch($val['type']){
								case 1:case 4:case 3:
						?>
							<?php if( 1 == $val['p_status'] ){ ?>
								<span id="a_<?php echo $val['pid']; ?>">
									<a onclick="if(confirm(&#39;终止后，当前产品不现参与该活动，已开团活动不受影响，是否终止？&#39;)){end_pro(<?php echo $val['pid'] ?>)};" style="cursor:pointer;">
									<span class="operation-btn WSY-skin-bg">终止</span>
								</span>
							<?php } ?>
						<?php
								break;
								case 2:
						?>
							<?php if( 3 == $val['p_status'] ){ ?>
								<?php
									$order_t_id = -1;
									$query_ = "SELECT id FROM collage_group_order_t WHERE isvalid=true AND pid=".$val['pid']." AND activitie_id=".$keyid." AND status=4 LIMIT 1";
									$reslut_ = _mysql_query($query_);
									while( $row_ = mysql_fetch_object($reslut_) ){
										$order_t_id = $row_->id;
									}
									if( $order_t_id > 0 ){
								?>
								<a href="luckDrawList.php?keyid=<?php echo $keyid; ?>&pid=<?php echo $val['pid']; ?>&op=lottery" style="cursor:pointer;">
									<span class="operation-btn WSY-skin-bg">抽奖</span>
								</a>
								<?php }
									}elseif( 4 == $val['p_status'] && $val['total_success'] > 0 ){ ?>
								<a href="luckDrawList.php?keyid=<?php echo $keyid; ?>&pid=<?php echo $val['pid']; ?>&op=check_lottery" style="cursor:pointer;">
									<span class="operation-btn WSY-skin-bg">查看中奖团</span>
								</a>
						<?php
								}
								break;
							}
						?>
						<a href="luckDrawList.php?keyid=<?php echo $keyid; ?>&pid=<?php echo $val['pid']; ?>&op=check" style="cursor:pointer;">
							<span class="operation-btn WSY-skin-bg">查看团活动</span>
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
			var search_pid = $("#search_pid").val();
			var search_pname = $("#search_pname").val();
			var search_status = $("#search_status").val();


			$(".WSY_page").createPage({
				pageCount:count,
				current:pagenum,
				backFn:function(p){
				 document.location= "proActivityList.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pagenum="+p+"&search_pid="+search_pid+"&search_pname="+search_pname+"&search_status="+search_status;
			   }
			});

		    var page = <?php echo $page ?>;

		    function jumppage(){
				var a=parseInt($("#WSY_jump_page").val());
				if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
					return false;
				}else{
					document.location= "proActivityList.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pagenum="+a+"&search_pid="+search_pid+"&search_pname="+search_pname+"&search_status="+search_status;
				}
		    }

			//输入框按回车键触发搜索
			$('.search-box').find('input').on('keydown',function(){
				if( event.keyCode == 13 ){
					$('#my_search').click();
				}
			});

		$('#my_search').click(function(){
			var search_pid = $("#search_pid").val();
			var search_pname = $("#search_pname").val();
			var search_status = $("#search_status").val();

			var url = "proActivityList.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>";

			if( search_pid != '' ){
				if(search_pid > 0){
					url += "&search_pid="+search_pid;
				}else{
					alert('请输入正确的产品ID！');
					return;
				}
			}
			if(search_pname!=''){
				url += "&search_pname="+search_pname;
			}
			if(search_status > 0){
				url += "&search_status="+search_status;
			}

			document.location=url;
		});

		function end_pro(pid){
			$.ajax({
				type: 'POST',
				url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
				data:{
					op		: 'end_pro',
					keyid	: <?php echo $keyid; ?>,
					pid		: pid
				},
				dataType: 'json',
				success: function(data){
					$('#status').text('无效');
					$('#a_'+pid).remove();
					alert(data.tips);
				}
			});

		}
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
