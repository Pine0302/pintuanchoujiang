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

/*$keyid = -1;//活动ID
if( !empty( $_GET['keyid'] ) ){
	$keyid = $configutil->splash_new($_GET["keyid"]);
}*/

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


/*获取本页信息---start*/
$filed = "wu.name as user_name,wu.weixin_name,wu.weixin_headimgurl,ot.id AS group_id,ot.activitie_id,ot.refund_status,at.group_size,at.start_time,at.end_time,at.type,at.name as a_name,ot.success_num,ot.join_num,ot.status,ot.pid,ot.createtime,ot.total_price,ot.coefficient,mp.name AS p_name";
$condition = ' at.isvalid=true AND ot.isvalid=true AND at.customer_id='.$customer_id.'';
$condition = array('at.isvalid'=>true,'ot.isvalid'=>true,'at.customer_id'=>$customer_id,);

$search_group_id = '';//搜索团ID
if( !empty( $_GET['search_group_id'] ) ){
	$search_group_id = $configutil->splash_new($_GET["search_group_id"]);
	$condition['ot.id'] = $search_group_id;
}

$search_head_name = '';//搜索团长名字
if( !empty( $_GET['search_head_name'] ) ){
	$search_head_name = $configutil->splash_new($_GET["search_head_name"]);
	$condition['search_head_name'] = $search_head_name;
}

$search_pname = '';//搜索产品名字
if( !empty( $_GET['search_pname'] ) ){
	$search_pname = $configutil->splash_new($_GET["search_pname"]);
	$condition['mp.name'] = $search_pname;
}

$search_type = '';//搜索团类型
if( !empty( $_GET['search_type'] ) ){
	$search_type = $configutil->splash_new($_GET["search_type"]);
	$condition .= ' AND ot.type='.$search_type;
}

$search_status = '';//搜索团状态
if( !empty( $_GET['search_status'] ) ){
	$search_status = $configutil->splash_new($_GET["search_status"]);
	$condition['ot.status'] = $search_status;
}

$search_activities_id = '';//搜索活动ID
if( !empty( $_GET['search_activities_id'] ) ){
	$search_activities_id = $configutil->splash_new($_GET["search_activities_id"]);
	$condition['ot.activitie_id'] = $search_activities_id;
}

$search_pro_id = '';//搜索产品ID
if( !empty( $_GET['search_pro_id'] ) ){
	$search_pro_id = $configutil->splash_new($_GET["search_pro_id"]);
	$condition['ot.pid'] = $search_pro_id;
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

$dcount = $collageActivities->get_group_order($condition,'count(1) as dcount');//总数量
$rcount_q = $dcount['data'][0]['dcount'];
$page=ceil($rcount_q/$end);

$condition['ORDER'] = ' order by ot.createtime desc';
$condition['LIMIT'] = " LIMIT ".$start.",".$end;
$list = $collageActivities->get_group_order($condition,$filed);
/*获取本页信息---end*/
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>团活动</title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/inside.css" media="all">
<script type="text/javascript" src="../../../js/tis.js"></script>
<script type="text/javascript" src="../../../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script type="text/javascript" src="../../../common/js/inside.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>


<style>

table th{color: #FFF;line-height: 30px;text-align: center;font-size: 12px; }
table td{height: 40px;line-height: 20px;font-size: 12px;color: #323232;padding: 0px 1em;text-align: center;border: 1px solid #D8D8D8; }
.display{display:none}
table td img{width: 20px;height: 20px;margin-left: 5px;}
.tips{margin-left:40px;position: absolute;margin-top: 13px;}
.tips span{font-size:15px;margin-left: 10px;}
.loading{
	width: 100%;
    height: 120%;
    background-color: black;
    position: absolute;
    opacity: 0.5;
    -moz-opacity: 0.5;
     display: none;
    top: 0;
}
.loading img{margin-left: 43%;margin-top: 320px;}
.redfont{color:red;}
.sharebg-active {
    opacity: 1;
    display: none;
}
.sharebg {
    background-color: rgba(0, 0, 0, 0.6);
    bottom: 0;
    height: 100%;
    left: 0;
    position: fixed;
    right: 0;
    top: 0;
    width: 100%;
    z-index: 500;
}
.operation-btn{padding: 3px 5px;color: #fff;border-radius: 2px;cursor:pointer;}
#lottery{visibility: inherit;z-index: 1009;position: fixed;left: 50%;margin-left: -529px;top: 50%;margin-top: -278px;display:none;}
.operation-btn{display:inline-block;padding: 5px 10px;background-color: #06a7e1;color: #fff;border-radius: 2px;cursor:pointer;text-align: center;margin: 2px 0;}
.header-left{float:left;margin-left:15px;margin-top: 10px;}
.header-left input{height: 23px;}
.user_img{width: 50px;border-radius: 30px;}
.WSY_table span{display:block;}
.navbox{z-index: 999;}
.refund-box{
	width: 30%;
    text-align: center;
    position: fixed;
    border-radius: 6px;
    margin: 0 35%;
    background-color: #fff;
	z-index: 501;
	top: 35%;
}
.refund-box-title{
	background-color: #06a7e1;
    color: #fff;
    height: 35px;
    line-height: 35px;
}
.refund-box-content{
	height: 60px;
    line-height: 60px;
}
.refund-box-btn{
	height: 50px;
    /*line-height: 50px;*/
}
.shadow{
	display:none;
	width: 100%;
    position: fixed;
    height: 100%;
    background-color: #000;
    opacity: 0.4;
    top: 0;
	z-index: 500;
}
table#WSY_t1 tr td:nth-child(6),table#WSY_t1 tr td:nth-child(7){
    text-align: center !important;
}
.Group-btn{padding:3px 5px;background-color:#06a7e1;color:#fff;border-radius:2px;cursor:pointer;margin:2px;border:0;}
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
			<div >
				<ul>
					<li class="WSY_position_date tate001" >
						<p>开团时间：<input class="date_picker" type="text" name="AccTime_E" id="begintime" value="<?php echo $begintime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
						<p style="margin-left:0px;">&nbsp;&nbsp;至&nbsp;&nbsp;<input class="date_picker" type="text" name="AccTime_B" id="endtime" value="<?php echo $endtime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
					</li>
				</ul>
			</div>
			<span style="margin-left:10px;">团ID：</span>
      		<input type="text" name="search_group_id" id="search_group_id" onkeyup="clearInt(this)" value="<?php echo $search_group_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
			<span style="margin-left:10px;">团长名称：</span>
      		<input type="text" name="search_head_name" id="search_head_name" value="<?php echo $search_head_name;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
			<!--<span style="margin-left:10px;">活动ID：</span>
      		<input type="text" name="search_activities_id" onkeyup="clearInt(this)" id="search_activities_id" value="<?php echo $search_activities_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
			 <span style="margin-left:10px;">团类型：</span>
			<select id="search_type" name="search_type">
				<option value="0">全部</option>
				<option value="1" <?php if($search_type==1){echo 'selected';} ?>>普通团</option>
				<option value="2" <?php if($search_type==2){echo 'selected';} ?>>抽奖团</option>
				<option value="3" <?php if($search_type==3){echo 'selected';} ?>>秒杀团</option>
				<option value="4" <?php if($search_type==4){echo 'selected';} ?>>超级团</option>
			</select> -->
			<span style="margin-left:10px;">状态：</span>
			<select id="search_status" name="search_status">
				<option value="0">全部</option>
				<option value="1" <?php if($search_status==1){echo 'selected';} ?>>进行中</option>
				<option value="2" <?php if($search_status==2){echo 'selected';} ?>>拼团失败</option>
				<option value="3" <?php if($search_status==3){echo 'selected';} ?>>拼团成功</option>
				<!-- <option value="4" <?php if($search_status==4){echo 'selected';} ?>>待抽奖</option> -->
				<option value="5" <?php if($search_status==5){echo 'selected';} ?>>成团成功</option>
				<option value="6" <?php if($search_status==6){echo 'selected';} ?>>成团失败</option>
			</select>
		<!-- 	<span style="margin-left:10px;">产品ID：</span>
      		<input type="text" name="search_pro_id" onkeyup="clearInt(this)" id="search_pro_id" value="<?php echo $search_pro_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">
			<span style="margin-left:10px;">产品名称：</span>
      		<input type="text" name="search_pname" id="search_pname" value="<?php echo $search_pname;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;"> -->



			<input type="button" id="my_search" style="margin-top:6px;" value="搜索" >

		</div>

             <br class="WSY_clearfloat";>
        </div>
        <!--列表按钮开始-->

        <!--表格开始-->
		<div class="WSY_data" id="type1" style="margin-left: 1.5%;">
		<table class="WSY_t2"  width="97%"  style="border: 1px solid #D8D8D8;border-collapse: collapse;">
			<thead class="WSY_table_header">
				<tr style="border:none">
					<th width="2%" >团ID</th>
					<th width="10%" >团长</th>
					<th width="5%">成团人数</th>
					<th width="5%">价格</th>
					<th width="5%">参与人数</th>
					<th width="5%">团总额</th>
					<th width="5%">产品编号</th>
					<th width="5%">状态</th>
					<th width="5%">开团时间</th>
					<th width="5%">活动编码</th>
					<th width="5%">活动名称</th>
					<th width="5%">退款状态</th>
					<th width="5%">退款成功</th>
					<th width="5%">退款失败</th>
					<th width="10%">操作</th>
				</tr>
			</thead>
			<tbody>
			<?php

				foreach($list['data'] as $key => $val){
					/*获取团产品信息*/
					$filed = 'pt.pid,cp.name,pt.stock,pt.price';
					$condition = array(
                                    'ae.customer_id' => $customer_id,
                                    'ae.isvalid' => true,
									'pt.isvalid' => true,
									'pt.activitie_id' => $val['activitie_id'],
									'cp.id' => $val['pid'],
									'LIMIT' => ' LIMIT 1',
								);
					$data = $collageActivities->get_activities_product($condition,$filed);

			?>
				<tr style="border:1px solid #D8D8D8">
					<td><?php echo $val['group_id'];?></td>
					<td>
                        <img style="width: 50px;height: 50px;float: left;border-radius: 30px;" src="<?php echo $val['weixin_headimgurl']; ?>">
                        <?php echo $val['weixin_name'];?><br>
                        <?php echo $val['user_name'];?>
                        <?php if($val['type']==5){?>
                        <br>系数：<?php echo $val['coefficient'];?>
                        <?php }?>
                    </td>
					<td><?php echo $val['success_num'];?></td> <!--成团人数-->
					<td><?php echo $data['data'][0]['price'];?></td><!--价格-->
					<td><?php echo $val['join_num']; ?></td><!--参与人数-->
					<td><?php echo $val['total_price'];?></td><!--团总额-->
					<td><?php echo $data['data'][0]['pid'];?></td><!--产品编号-->
					<td><?php
							switch($val['status']){
								case -1:
									if( strtotime($val['createtime']) > strtotime('- 5 mins') ){
										echo '待付款';
									} else {
										echo '支付超时';
									}
								break;
								case 1:
									echo '进行中';
								break;
								case 2:
									echo '拼团失败';
								break;
								case 3:
									echo '拼团成功';
								break;
								case 4:
									echo '待抽奖';
								break;
								case 5:
									echo '成团成功';
								break;
								case 6:
									echo '成团失败';
								break;
							}
						?>
					</td><!--状态-->
					<td><?php echo $val['createtime'];?></td><!--开团时间-->
					<td><?php echo $val['activitie_id'];?></td><!--活动编码-->
					<td><a href="proActivityList.php?customer_id=<?php echo $customer_id; ?>&keyid=<?php echo $val['activitie_id']; ?>"><?php echo $val['a_name'];?></td></a><!--活动名称-->

					<?php

							if($val['status'] == 2){
								?><!--未支付-->

						<?php
								 /*拼团失败*/
									if($val['type'] != 6){//免单团不能退款
									/*查找是否操作过团退款*/
										//$is_operation = $collageActivities->check_group_log($val['group_id'],$customer_id);

											$refund_num = 0;
											$refundable_num = 0;
											/*获取退款成功订单数量*/
											$refund_num = $collageActivities->check_order_refund($val['group_id'],$customer_id);
											/*获取退款失败订单数量*/
											$refundable_num = $collageActivities->check_order_refundable($val['group_id'],$customer_id);

											if($val['refund_status'] == 3 ){?>
												<td><?php echo "部分退款"; ?></td><!--退款状态-->
												<td><?php echo $refund_num; ?></td><!--退款成功-->
												<td><?php echo $refundable_num; ?></td><!--退款失败-->
												<td>
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $val['activitie_id']; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $val['activitie_id'];?>')">参与记录</span>
													</a>
												<span class="operation-btn WSY-skin-bg" onclick="refund(<?php echo $val['group_id'].",".$val['status'] ?>)">退款</span></td>
						<?php				}else if($val['refund_status'] == 2 ){ ?>
												<td><?php echo "全部退款"; ?></td><!--退款状态-->
												<td><?php echo $refund_num; ?></td><!--退款成功-->
												<td><?php echo $refundable_num; ?></td><!--退款失败-->
												<td>
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $val['activitie_id']; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $val['activitie_id'];?>')">参与记录</span>
													</a>
												</td>
						<?php				}else if($val['refund_status'] == 1 ){?>
												<td><?php echo "待退款"; ?></td><!--退款状态-->
												<td>0</td><!--退款成功-->
												<td>0</td><!--退款失败-->
												<td>
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $val['activitie_id']; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $val['activitie_id'];?>')">参与记录</span>
													</a>
												<span class="operation-btn WSY-skin-bg" onclick="refund(<?php echo $val['group_id'].",".$val['status'] ?>)">退款</span></td>
						<?php				}else {?>
												<td></td><!--退款状态-->
												<td></td><!--退款成功-->
												<td></td><!--退款失败-->
												<td>
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $val['activitie_id']; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $val['activitie_id'];?>')">参与记录</span>
													</a>
												</td>
						<?php					}
									}else{ ?>
										<td></td><!--退款状态-->
										<td></td><!--退款成功-->
										<td></td><!--退款失败-->
										<td>
										    <a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $val['activitie_id']; ?>" style="cursor:pointer;">
												<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $val['activitie_id'];?>')">参与记录</span>
											</a>
										</td>
							<?php   }

							}else{ ?>
								<td></td><!--退款状态-->
								<td></td><!--退款成功-->
								<td></td><!--退款失败-->
								<td>
									<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $val['activitie_id']; ?>" style="cursor:pointer;">
										<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $val['activitie_id'];?>')">参与记录</span>
									</a>
									<?php if($val['status'] == 1 && $val['type'] != 6){?><span class="operation-btn WSY-skin-bg" onclick="finish(<?php echo $val['group_id'].",".$val['status'] ?>)">成功</span><?php } ?></td>

					<?php	}?>

				</tr>
			<?PHP }?>

			</tbody>

			</table>
			<!--翻页开始-->
			<div class="WSY_page">

			</div>
			<!--翻页结束-->

		</div>
		<div class="loading" id="loading">
				<img src="loading.gif" alt="">
		</div>
		<div class="sharebg sharebg-active"></div>
		<script src="../../../js/fenye/jquery.page1.js"></script>
		<script charset="utf-8" src="/weixinpl/common/js/layer/V2_1/layer.js"></script>
		<script type="text/javascript">
			var customer_id = '<?php echo $customer_id_en ?>';
			var customer_id1 = '<?php echo $customer_id ?>';
			var pagenum = <?php echo $pagenum ?>;
			var count =<?php echo $page ?>;//总页数
				//pageCount：总页数
				//current：当前页
			var search_group_id = '<?php echo $search_group_id;?>';
			var search_head_name = '<?php echo $search_head_name;?>';
			var search_activities_id = '<?php echo $search_activities_id;?>';
			var search_type = '<?php echo $search_type;?>';
			var search_status = '<?php echo $search_status;?>';
			var search_pro_id = '<?php echo $search_pro_id;?>';
			var search_pname = '<?php echo $search_pname;?>';
			var begintime = '<?php echo $begintime;?>';
			var endtime = '<?php echo $endtime;?>';
			var isRefunding = false;

			$(".WSY_page").createPage({
				pageCount:count,
				current:pagenum,
				backFn:function(p){
					var url = "groupOrder.php?customer_id="+customer_id+"&pagenum="+p
					if( search_group_id != '' ){
						url += '&search_group_id='+search_group_id;
					}
					if( search_head_name != '' ){
						url += '&search_head_name='+search_head_name;
					}
					if( search_status >= 0 ){
						url += '&search_status='+search_status;
					}
					if( begintime != '' ){
						url += '&begintime='+begintime;
					}
					if( endtime != '' ){
						url += '&endtime='+endtime;
					}
					if( search_pro_id != '' ){
						url += '&search_pro_id='+search_pro_id;
					}
					document.location= url;
			   }
			});

		    var page = <?php echo $page ?>;

		    function jumppage(){
				var a=parseInt($("#WSY_jump_page").val());
				if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
					return false;
				}else{
					var url = "groupOrder.php?customer_id="+customer_id+"&pagenum="+a
					if( search_group_id != '' ){
						url += '&search_group_id='+search_group_id;
					}
					if( search_head_name != '' ){
						url += '&search_head_name='+search_head_name;
					}
					if( search_status >= 0 ){
						url += '&search_status='+search_status;
					}
					if( begintime != '' ){
						url += '&begintime='+begintime;
					}
					if( endtime != '' ){
						url += '&endtime='+endtime;
					}
					if( search_pro_id != '' ){
						url += '&search_pro_id='+search_pro_id;
					}
					document.location= url;

				}
		    }

			//输入框按回车键触发搜索
			$('.search-box').find('input').on('keydown',function(){
				if( event.keyCode == 13 ){
					$('#my_search').click();
				}
			});

		$('#my_search').click(function(){
			var search_group_id = $("#search_group_id").val();
			var search_head_name = $("#search_head_name").val();
			var search_activities_id = $("#search_activities_id").val();
			var search_type = $("#search_type").val();
			var search_status = $("#search_status").val();
			var search_pro_id = $("#search_pro_id").val();
			var search_pname = $("#search_pname").val();
			var begintime = $("#begintime").val();
			var endtime = $("#endtime").val();

			var url = "groupOrder.php?customer_id="+customer_id;

			if(endtime<begintime && begintime!='' && endtime!=''){
				alert('开始时间不得大于结束时间');
				return;
			}

			if( search_group_id != '' ){
				if(search_group_id > 0 ){
					url += "&search_group_id="+search_group_id;
				}else{
					alert('请输入正确的团ID！');
					return;
				}
			}

			if(search_head_name!=''){
				url += "&search_head_name="+search_head_name;
			}

			if(begintime!=''){
				url += "&begintime="+begintime;
			}
			if(endtime!=''){
				url += "&endtime="+endtime;
			}

			if(search_status>=0){
				url += "&search_status="+search_status;
			}

			document.location=url;
		});

		function clearInt(obj){
			if(obj.value.length==1){obj.value=obj.value.replace(/[^1-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
		}

		//退款
function refund(group_id,group_status, group_type){
	showRefundBox('退回方式确认','确定','取消',function(){
		var refund_way = $('input[name=refund_way]:checked').val();
		if( refund_way == undefined ){
			alert('请选择退回方式！');
			return false;
		}
		$('.shadow').hide();
		$('.refund-box').remove();
		$("#loading").show();
		if( isRefunding ){
			alert('正在退款中，请勿重复操作！');
			return;
		} else {
			isRefunding = true;
		}
		var op = 'refund_all';
		$.ajax({
			url: 'group_operation.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>',
			dataType: 'json',
			type: 'post',
			data: {
				group_id : group_id,
				group_status : group_status,
				op : op,
				refund_way : refund_way

			},
			success: function(res){
				$("#loading").hide();
				alert(res.msg);
				window.location.reload();
			}
		})
	})
}

//退款框
/*function showRefundBox(title,confirm_btn,cancel_btn,callbackfunc){
	$('.shadow').show();
	var tip_img = '';
	var is_can_back = 1;
	$('#toolTipLayer').css('z-index','5555');

	var html = '';
	html += '<div class="refund-box">';
	html += '	<div class="refund-box-title">'+title+'</div>';
	html += '	<div class="refund-box-content">';
	html += '		<span>退回方式：</span>';

	html += '		<input type="radio" name="refund_way" id="moneybag" value="2" /><label for="moneybag">零钱余额</label>';
	html += '	</div>';
	html += '	<div class="refund-box-btn">';
	html += '		<span class="operation-btn WSY-skin-bg close-refund-btn" style="background-color: #ccc;margin: 0 10px;padding: 5px 20px;">'+cancel_btn+'</span>';
	html += '		<span class="operation-btn WSY-skin-bg confirm-refund-btn" style="margin: 0 10px;padding: 5px 20px;">'+confirm_btn+'</span>';
	html += '	</div>';
	html += '</div>';
	$('body').append(html);
	$('.confirm-refund-btn').click(function(){
		if( callbackfunc ){
			if( callbackfunc() ){
				$('.close-refund-btn').click();
			}
		}

	});
	$('.close-refund-btn').click(function(){
		$('.shadow').hide();
		$('.refund-box').remove();
	});
}*/

//退款框
function showRefundBox(title,confirm_btn,cancel_btn,callbackfunc){
	$('.shadow').show();
	var tip_img = '';
	var is_can_back = 1;
	$('#toolTipLayer').css('z-index','5555');
	var html = '';
	html += '<div class="refund-box">';
	html += '	<div class="refund-box-title">'+title+'</div>';
	html += '	<div class="refund-box-content">';
	html += '		<span>退回方式：</span>';
	html += '		<input type="radio" name="refund_way" id="moneybag" value="2" /><label for="moneybag">零钱余额</label>';
	html += '		<input type="radio" name="refund_way" id="original" value="1" /><label for="original">原路返回</label>';
	html += '<img style="width:12px;position: absolute;margin-top: 22px;margin-left: 5px;" id="tips_2" src="../../Common/images/Base/help.png">';
	html += '	</div>';
	html += '	<div class="refund-box-btn">';
	html += '		<span class="operation-btn WSY-skin-bg close-refund-btn" style="background-color: #ccc;margin: 0 10px;padding: 5px 20px;">'+cancel_btn+'</span>';
	html += '		<span class="operation-btn WSY-skin-bg confirm-refund-btn" style="margin: 0 10px;padding: 5px 20px;">'+confirm_btn+'</span>';
	html += '	</div>';
	html += '</div>';
	$('body').append(html);
	$('.confirm-refund-btn').click(function(){
		if( callbackfunc ){
			if( callbackfunc() ){
				$('.close-refund-btn').click();
			}
		}

	});
	$('.close-refund-btn').click(function(){
		$('.shadow').hide();
		$('.refund-box').remove();
	});
	$('#tips_2').on('mouseenter', function(){
	layer.tips('原路返回仅支持：微信支付，环迅支付，威富通支付，会员卡支付，零钱支付！','#tips_2');
	});
}
function finish(group_id,group_status){
	var op = 'census';
	var census = 0;
		$.ajax({
			url: 'group_operation.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>',
			dataType: 'json',
			type: 'post',
			async: false,
			data: {
				group_id : group_id,
				op : op,

			},
			success: function(res){
				census = res;
			}
		})

	if(confirm('该团还有'+census+'人正在支付中，确定拼团成功吗?')) {
		$("#loading").show();
		var op = 'finish';
		$.ajax({
			url: 'group_operation.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>',
			dataType: 'json',
			type: 'post',
			data: {
				group_id : group_id,
				group_status : group_status,
				op : op,

			},
			success: function(res){
				$("#loading").hide();
				alert(res.msg);
				window.location.reload();
			}
		})

	}else{
		return false;
		}
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
