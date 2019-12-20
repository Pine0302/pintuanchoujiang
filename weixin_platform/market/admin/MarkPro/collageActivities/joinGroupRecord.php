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

$group_id = -1;
if( !empty( $_GET['group_id'] ) ){
	$group_id = $configutil->splash_new($_GET["group_id"]);
}
$keyid = -1;
if( !empty( $_GET['keyid'] ) ){
	$keyid = $configutil->splash_new($_GET["keyid"]);
}
//分页---start
$pagenum = 1;
$pagesize = 15;
if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}
$start = ($pagenum-1) * $pagesize;
$end = $pagesize;
 /* 输出数量结束 */


/*获取基本信息---start*/
$a_name 	 = '';//活动名称
$a_status 	 = -1;//活动状态
$o_id 		 = -1;//团ID
$o_status 	 = -1;//团状态
$w_name 	 = '';//团长名称
$weixin_name = '';//团长微信名称
$o_createtime= '';//团创建时间
$refund_status= '';//团退款状态

$query = "SELECT at.name AS a_name,at.status AS a_status,at.type,ot.id,ot.status AS o_status,ot.createtime,ot.refund_status,wu.name AS w_name,wu.weixin_name,ae.type_name
		FROM collage_activities_t AS at
		LEFT JOIN collage_group_order_t AS ot ON at.id=ot.activitie_id
		INNER JOIN weixin_users AS wu ON wu.id=ot.head_id
        LEFT JOIN collage_activities_explain_t AS ae ON ae.type=at.type
		WHERE at.isvalid=true AND at.id=".$keyid." AND ot.isvalid=true AND ot.id=".$group_id."  AND ae.customer_id=".$customer_id." LIMIT 1";
$result = _mysql_query($query) or die('query failed452: ' . mysql_error());
while($row = mysql_fetch_object($result)){
	$o_id 		 = $row->id;
	$type 		 = $row->type;
	$a_name 	 = $row->a_name;
	$a_status 	 = $row->a_status;
	$o_status 	 = $row->o_status;
	$w_name 	 = $row->w_name;
	$weixin_name = $row->weixin_name;
	$o_createtime = $row->createtime;
	$refund_status = $row->refund_status;
    $type_name	 = $row->type_name;
}
$a_status_str = '';//活动状态
switch($a_status){
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
$o_status_str = '';//团状态
switch($o_status){
	case -1:
		if ( strtotime($o_createtime) > strtotime('- 5 mins') ) {
			$o_status_str = '待付款';

		} else {
			$o_status_str = '支付超时';
		}

	break;
	case 1:
		$o_status_str = '进行中';
	break;
	case 2:
		$o_status_str = '拼团失败';
	break;
	case 3:
		$o_status_str = '拼团成功';
	break;
	case 4:
		$o_status_str = '待抽奖';
	break;
	case 5:
		$o_status_str = '成团成功';
	break;
	case 6:
		$o_status_str = '成团失败';
	break;
}
$type_str = '';//活动类型
$type_str = $type_name;

$refund_status_str = '';//团退款状态
switch($refund_status){
	case 1:
		$refund_status_str = '待退款';
	break;
	case 2:
		$refund_status_str = '全部退款';
	break;
	case 3:
		$refund_status_str = '部分退款';
	break;
}
/*获取基本信息---end*/

/*获取本页信息---start*/
$filed = 'ot.is_refund,ot.batchcode,ot.paystyle,ot.totalprice,ot.createtime AS oa_createtime,ot.is_head,ot.paytime,ot.status,mt.pname,mt.prvalues_name,wu.name as wu_name,wu.weixin_name,wu.weixin_headimgurl,wu.id AS user_id,wu.phone AS wu_phone,oa.name AS oa_name,oa.phone AS oa_phone,oa.location_p,oa.location_c,oa.location_a,oa.address,wco.sendstatus,wco.paystatus';
$condition = array('ot.isvalid'=>true,'ot.group_id'=>$group_id,'ot.customer_id'=>$customer_id);
$search_batchcode = '';//搜索订单号
if( !empty( $_GET['search_batchcode'] ) ){
	$search_batchcode = $configutil->splash_new($_GET["search_batchcode"]);
	$condition['ot.batchcode'] = $search_batchcode;
}

$search_name = '';//用户名
if( !empty( $_GET['search_name'] ) ){
	$search_name = $configutil->splash_new($_GET["search_name"]);
	$condition['search_name'] = " AND (wu.name LIKE '%".$search_name."%' OR wu.weixin_name LIKE '%".$search_name."%')";
}

$search_adress_name = '';//收货人
if( !empty( $_GET['search_adress_name'] ) ){
	$search_adress_name = $configutil->splash_new($_GET["search_adress_name"]);
	$condition['oa.name'] = $search_adress_name;
}

$search_phone = '';//搜索电话
if( !empty( $_GET['search_phone'] ) ){
	$search_phone = $configutil->splash_new($_GET["search_phone"]);
	$condition['oa.phone'] = $search_phone;
}

$begintime = '';//搜索开始时间
if( !empty( $_GET['begintime'] ) ){
	$begintime = $configutil->splash_new($_GET["begintime"]);
	$condition['begintime'] = " AND ot.createtime>='".$begintime."'";
}

$endtime = '';//搜索结束时间
if( !empty( $_GET['endtime'] ) ){
	$endtime = $configutil->splash_new($_GET["endtime"]);
	$condition['endtime'] = " AND ot.createtime<='".$endtime."'";
}

$collageActivities = new collageActivities($customer_id);
$dcount = $collageActivities->get_crew_order_mes($condition,'count(1) as dcount');//总数量
$rcount_q = $dcount['data'][0]['dcount'];
$page=ceil($rcount_q/$end);

$condition['ORDER'] = ' order by ot.createtime desc';
$condition['LIMIT'] = " LIMIT ".$start.",".$end;
$list = $collageActivities->get_crew_order_mes($condition,$filed);
/*获取本页信息---start*/

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>参团记录</title>
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
.tips{margin-left:40px;margin-top: 13px;}
.tips span{font-size:15px;margin-left: 10px;}
.redfont{color:red;}
.ellipsis{text-overflow:ellipsis;overflow:hidden;white-space:nowrap;max-width:92px;display:inline-block;vertical-align:top;}
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

      	<div style="margin-left:40px;margin-top:0px;">

			<span style="margin-left:10px;">订单号：</span>
      		<input type="text" name="search_batchcode" id="search_batchcode" value="<?php echo $search_batchcode;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
			<span style="margin-left:10px;">用户名：</span>
      		<input type="text" name="search_name" id="search_name" value="<?php echo $search_name;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
			<span style="margin-left:10px;">收货人：</span>
      		<input type="text" name="search_adress_name" id="search_adress_name" value="<?php echo $search_adress_name;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
			<span style="margin-left:10px;">手机号：</span>
      		<input type="text" name="search_phone" id="search_phone" value="<?php echo $search_phone;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;" onkeyup="clearInt(this)">
			<div class="WSY_position1" style="float:left">
				<ul>
					<li class="WSY_position_date tate001" >
						<p>下单时间：<input class="date_picker" type="text" name="AccTime_E" id="begintime" value="<?php echo $begintime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
						<p style="margin-left:0px;">&nbsp;&nbsp;至&nbsp;&nbsp;<input class="date_picker" type="text" name="AccTime_B" id="endtime" value="<?php echo $endtime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
					</li>
				</ul>
			</div>

			<input type="button" id="my_search" value="搜索" >

		</div>

		<div class="tips">
			<span>活动编号：<span class="redfont"><?php echo $keyid; ?></span></span>
			<span>活动名称：<span class="redfont"><?php echo htmlspecialchars($a_name);  ?></span></span>
			<span>活动状态：<span class="redfont"><?php echo $a_status_str;  ?></span></span>
			<span>活动类型：<span class="redfont"><?php echo $type_str;  ?></span></span>
			<span>团ID：<span class="redfont"><?php echo $group_id;  ?></span>	</span>
			<span>团长：<span class="redfont ellipsis"><?php echo $w_name.'('.$weixin_name.')';  ?></span>	</span>
			<span>团状态：<span class="redfont"><?php echo $o_status_str;  ?></span></span>
			<span>团退款状态：<span class="redfont"><?php echo $refund_status_str;  ?></span></span>
		</div>


             <br class="WSY_clearfloat";>
        </div>
        <!--列表按钮开始-->

        <!--表格开始-->
		<div class="WSY_data" id="type1" style="margin-left: 1.5%;">
		<table class="WSY_t2"  width="97%"  style="border: 1px solid #D8D8D8;border-collapse: collapse;">
			<thead class="WSY_table_header">
				<tr style="border:none">
					<th width="8%" >订单号</th>
					<th width="10%" >用户信息</th>
					<th width="4%">支付方式</th>
					<th width="4%">支付金额</th>
					<th width="6%">支付时间</th>
					<th width="10%">收货信息</th>
					<th width="8%">产品信息</th>
					<th width="6%">下单时间</th>
					<th width="4%">状态</th>
				</tr>
			</thead>
			<tbody>
			<?php
				foreach($list['data'] as $key => $val){
					switch($val['status']){
						 case 1:
							if ( strtotime($val['oa_createtime']) > strtotime('- 5 mins') ) {
								$sendstatusstr = '待付款';
							} else {
								$sendstatusstr = '支付超时';
							}

						 break;
						 case 2:
							 $sendstatusstr = '已付款';
						 break;
						 case 3:
							if( $val['is_refund'] ) {
								$sendstatusstr = '待退款（申请退款）';
							} else {
								$sendstatusstr = '待退款（拼团失败）';
							}

						 break;
						case 4:
							$sendstatusstr = '拼团失败';
						break;
						case 5:
							$sendstatusstr = '拼团成功';
						break;
						case 6:
							$sendstatusstr = '已退款';
						break;
						case 7:
							$sendstatusstr = '成团成功';
						break;
						case 8:
							$sendstatusstr = '成团失败';
						break;
					}
					switch($val['sendstatus']){
					   case 3:
							$sendstatusstr = "申请退货";
						   break;
						case 4:
						   $sendstatusstr = "退货已确认";
						   break;
						case 5:
						   $sendstatusstr = "申请退款";
						   break;
						case 6:
						   $sendstatusstr = "退款完成";
						   break;
					}

                    $order_coefficient = -1;
                    $query1 = "SELECT order_coefficient FROM collage_bbt_order_extend WHERE batchcode='".$val['batchcode']."'";
                    $result1 = _mysql_query($query1) or die('Query1 failed:'.mysql_error());
                    $order_coefficient = mysql_fetch_assoc($result1)['order_coefficient'];

			?>
				<tr style="border:1px solid #D8D8D8">
					<td><?php echo $val['batchcode'];?></td>
					<td><img style="width: 61px;height: 61px;float: left;border-radius: 30px;" src="<?php echo $val['weixin_headimgurl']; ?>"><?php echo $val['user_id'];?><?php if($val['is_head'] == 1){
					?>
					<div style="background-color: red;float:right;height: 25px;width: 25px;border-radius: 20px;"><span style="color: #FFF;font-size: 14px;position: absolute;margin-left: -7px;margin-top: 2px;">团</span></div>
					<?php
					} ?><br><?php echo $val['name'];?>（<?php echo $val['weixin_name'];?>）<br><?php echo $val['wu_phone']; ?>
                    <?php if($order_coefficient>0){?>
                    <br>抱抱团系数：<?php echo $order_coefficient;?>
                    <?php }?>
                    </td>
					<td><?php echo $val['paystyle']==-1?'未支付':$val['paystyle'];?></td>
					<td><?php echo $val['totalprice'];?></td>
					<td><?php echo $val['paytime'];?></td>
					<td><?php echo $val['oa_name'];?><br><?php echo $val['oa_phone'];?><br><?php echo $val['location_p'];?><?php echo $val['location_c'];?><?php echo $val['location_a'];?><?php echo $val['address'];?></td>
					<td><?php echo $val['pname'];?><br><?php echo $val['prvalues_name'];?></td>
					<td><?php echo $val['oa_createtime'];?></td>
					<td><?php echo $sendstatusstr;?></td>
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
			var search_batchcode = $("#search_batchcode").val();
			var search_name = $("#search_name").val();
			var search_adress_name = $("#search_adress_name").val();
			var search_phone = $("#search_phone").val();
			var begintime = $("#begintime").val();
			var endtime = $("#endtime").val();

			$(".WSY_page").createPage({
				pageCount:count,
				current:pagenum,
				backFn:function(p){
				 document.location= "joinGroupRecord.php?customer_id="+customer_id+"&group_id=<?php echo $group_id; ?>&keyid=<?php echo $keyid; ?>&pagenum="+p+"&search_batchcode="+search_batchcode+"&search_name="+search_name+"&search_adress_name="+search_adress_name+"&search_phone="+search_phone+"&begintime="+begintime+"&endtime="+endtime;
			   }
			});

		    var page = <?php echo $page ?>;

		    function jumppage(){
				var a=parseInt($("#WSY_jump_page").val());
				if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
					return false;
				}else{
					document.location= "joinGroupRecord.php?customer_id="+customer_id+"&group_id=<?php echo $group_id; ?>&keyid=<?php echo $keyid; ?>&pagenum="+a+"&search_batchcode="+search_batchcode+"&search_name="+search_name+"&search_adress_name="+search_adress_name+"&search_phone="+search_phone+"&begintime="+begintime+"&endtime="+endtime;
				}
		    }

		$('#my_search').click(function(){
			var search_batchcode = $("#search_batchcode").val();
			var search_name = $("#search_name").val();
			var search_adress_name = $("#search_adress_name").val();
			var search_phone = $("#search_phone").val();
			var begintime = $("#begintime").val();
			var endtime = $("#endtime").val();

			var url = "joinGroupRecord.php?customer_id="+customer_id+"&group_id=<?php echo $group_id; ?>&keyid=<?php echo $keyid; ?>";

			if(endtime<begintime && begintime!='' && endtime!=''){
				alert('开始时间不得大于结束时间');
				return;
			}

			if( search_batchcode != '' ){
				if(search_batchcode > 0){
					url += "&search_batchcode="+search_batchcode;
				}else{
					alert('请输入正确的订单号！');
					return;
				}
			}
			if( search_phone != '' ){
				if(search_phone > 0 ){
					url += "&search_phone="+search_phone;
				}else{
					alert('请输入正确的联系电话！');
					return;
				}
			}

			if(begintime!=''){
				url += "&begintime="+begintime;
			}
			if(endtime!=''){
				url += "&endtime="+endtime;
			}
			if(search_name != ''){
				url += "&search_name="+search_name;
			}
			if(search_adress_name != ''){
				url += "&search_adress_name="+search_adress_name;
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
