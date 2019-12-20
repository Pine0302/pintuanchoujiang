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
$pid = -1;//产品ID
if( !empty( $_GET['pid'] ) ){
	$pid = $configutil->splash_new($_GET["pid"]);
}
$op = '';//类型
if( !empty( $_GET['op'] ) ){
	$op = $configutil->splash_new($_GET["op"]);
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

$total_success = 0;//成功团数
$total_fail = 0;//失败团数
$p_name = '';//产品名
$status = -1;//产品状态
$sql = "SELECT pt.total_success,pt.status,pt.total_fail,pt.total_success,cp.name FROM collage_group_products_t AS pt INNER JOIN weixin_commonshop_products AS cp ON pt.pid=cp.id WHERE pt.activitie_id=".$keyid." AND pt.pid=".$pid;

$result = _mysql_query($sql) or die('sql  failed: ' . mysql_error());
while($row = mysql_fetch_object($result)){
	$total_success 	= $row->total_success;
	$total_fail 	= $row->total_fail;
	$p_name 		= $row->name;
	$status 		= $row->status;
	break;
}

/*获取基本信息---start*/
$filed = "ot.type,at.name AS a_name,at.luck_draw_num";
$condition = array('at.isvalid'=>true,'ot.activitie_id'=>$keyid,'ot.isvalid'=>true);
$data = $collageActivities->get_group_order($condition,$filed);

/*获取基本信息---end*/
if( $op == 'check_lottery' ){
	$condition['ot.is_win'] = 1;
}
$condition['ot.pid'] = $pid;
/*获取本页信息---start*/
$filed = "at.name AS a_name,at.group_size,ot.type,ot.id AS group_id,ot.head_id,ot.refund_status,ot.price,ot.join_num,ot.total_price,ot.status AS group_status,ot.pid,ot.createtime,ot.coefficient,wu.name,wu.weixin_name,wu.weixin_headimgurl,mp.name as pname,mp.id as pid";
$search_head_id = '';//搜索团长ID
if( !empty( $_GET['search_head_id'] ) ){
	$search_head_id = $configutil->splash_new($_GET["search_head_id"]);
	$condition['ot.head_id'] = $search_head_id;
}

$search_group_id = '';//搜索团ID
if( !empty( $_GET['search_group_id'] ) ){
	$search_group_id = $configutil->splash_new($_GET["search_group_id"]);
	$condition['ot.id'] = $search_group_id;
}
$search_head_name = '';//搜索团长名称
if( !empty( $_GET['search_head_name'] ) ){
	$search_head_name = $configutil->splash_new($_GET["search_head_name"]);
	$condition['search_head_name'] = $search_head_name;
}

$search_status = '';//搜索团状态
if( !empty( $_GET['search_status'] ) ){
	$search_status = $configutil->splash_new($_GET["search_status"]);
	if ( $search_status == -2 ) {	//支付超时
		$condition['ot.status'] = -1;
		$condition['pay_timeout'] = " AND ot.createtime < '".date('Y-m-d H:i:s',strtotime("+ 5 mins"))."' ";
	} else {
		$condition['ot.status'] = $search_status;
	}
	if ( $search_status == -1 ) {
		$condition['pay_timeout'] = " AND ot.createtime > '".date('Y-m-d H:i:s',strtotime("- 5 mins"))."'";
	}

}

$search_refund_status = '';//搜索团退款状态
if( !empty( $_GET['search_refund_status'] ) ){
	$search_refund_status = $configutil->splash_new($_GET["search_refund_status"]);
	if ( $search_refund_status == 1 ) {
		$condition['ot.refund_status'] = 1;
	} else if( $search_refund_status == 2 ) {
		$condition['ot.refund_status'] = 2;
	} else if( $search_refund_status == 3 ){
		$condition['ot.refund_status'] = 3;
	} else if ( $search_refund_status == -1 ) {

	}

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
$page1=ceil($rcount_q/10);

$condition['ORDER'] = ' order by at.createtime desc';
$condition['LIMIT'] = " LIMIT ".$start.",".$end;
$list = $collageActivities->get_group_order($condition,$filed);
/*获取本页信息---end*/
$status_str = '';//状态
switch($status){
	case 1:
		$status_str = '进行中';
	break;
	case 2:
		$status_str = '无效';
	break;
	case 3:
		$status_str = '待抽奖';
	break;
	case 4:
		$status_str = '已结束';
	break;
}

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
<script charset="utf-8" src="/weixinpl/common/js/layer/V2_1/layer.js"></script>
<style>

table th{color: #FFF;line-height: 30px;text-align: center;font-size: 12px; }
table td{height: 40px;line-height: 20px;font-size: 12px;color: #323232;padding: 0px 1em;text-align: center;border: 1px solid #D8D8D8; }
.display{display:none}
table td img{width: 20px;height: 20px;margin-left: 5px;}
.tips{margin-left:40px;margin-top: 13px;    float: left;}
.tips span{font-size:15px;margin-left: 10px;}
.redfont{color:red;}
.sharebg-active {
    opacity: 1;
    display: none;
}
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
#lottery{visibility: inherit;z-index: 1009;position: fixed;left: 50%;margin-left: -529px;top: 50%;margin-top: -278px;display:none;overflow:auto;}
#choose{visibility: inherit;z-index: 1009;position: fixed;left: 50%;margin-left: -529px;top: 35%;margin-top: -278px;display:none;}
.WSY_page2 .WSY_page_search,.WSY_page2 .WSY_jump ,.WSY_page2 .WSY_pageright {
	display:none;
}
.operation-btn{padding: 3px 5px;color: #fff;border-radius: 2px;cursor:pointer;}
.kuang{margin-top: 36px;border: 1px solid #847272;margin-left:5%;width:90%;}
.kuang_div{width: 19.7%;height: 112px;border: 1px solid #A29B9B;    display: inline-block;}
.kuang_div>img{width: 60px;height: 60px;float: left;border-radius: 30px;margin-top:20px;margin-left: 20px;}
.kuang_div>.kuang_name{style="margin-top:31px;font-size: 14px;"}
.skuang{width: 100%;height: 33px;text-align: center;background-color: #C3C2C2;color: #fff;    float: inherit;    cursor: pointer;    margin-top: 30px;}
.skuang span{position: absolute;margin-top: 4px;font-size: 15px;margin-left: -15px;}
.kuang_name{margin-top:31px;font-size: 14px;}
input.search_btn{width: 120px;
    height: 27px;
    border-radius: 2px;
    color: #fff;
    margin-left: 40px;
    margin-top: 19px;
    cursor: pointer;}
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

			<span style="margin-left:10px;">团ID：</span>
      		<input type="text" name="search_group_id" id="search_group_id" value="<?php echo $search_group_id;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<span style="margin-left:10px;">团长名称：</span>
      		<input type="text" name="search_head_name" id="search_head_name" value="<?php echo $search_head_name;?>" style="width:100px;height:25px;border:1px solid #ccc;border-radius:3px;">

			<span style="margin-left:10px;">状态：</span>
			<select id="search_status" name="search_status">
				<option value="0">全部</option>
				<option value="-1" <?php if($search_status==-1){echo 'selected';} ?>>待付款</option>
				<option value="1" <?php if($search_status==1){echo 'selected';} ?>>进行中</option>
				<option value="2" <?php if($search_status==2){echo 'selected';} ?>>拼团失败</option>
				<option value="3" <?php if($search_status==3){echo 'selected';} ?>>拼团成功</option>
				<option value="4" <?php if($search_status==4){echo 'selected';} ?>>待抽奖</option>
				<option value="5" <?php if($search_status==5){echo 'selected';} ?>>成团成功</option>
				<option value="6" <?php if($search_status==6){echo 'selected';} ?>>成团失败</option>
				<option value="-2" <?php if($search_status==-2){echo 'selected';} ?>>支付超时</option>
			</select>

			<div class="WSY_position1" style="float:left">
				<ul>
					<li class="WSY_position_date tate001" >
						<p>开团时间：<input class="date_picker" type="text" name="AccTime_E" id="begintime" value="<?php echo $begintime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
						<p style="margin-left:0px;">&nbsp;&nbsp;至&nbsp;&nbsp;<input class="date_picker" type="text" name="AccTime_B" id="endtime" value="<?php echo $endtime; ?>" onclick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm'});"></p>
					</li>
				</ul>
			</div>

			<input type="button" id="my_search" value="搜索" >
			<?php if( $op == 'check' ){ ?>
				<input type="button" style="cursor: pointer;" class="search_btn" id="my_excel" value="导出" >
			<?php } ?>
		</div>

		<div  class="tips">
			<span>活动编号：<span class="redfont"><?php echo $keyid; ?></span></span>
			<span>活动名称：<span class="redfont"><?php echo $data['data'][0]['a_name'];  ?></span></span>
			<span>状态：<span class="redfont"><?php echo $status_str;  ?></span></span>
			<span>成功团数：<span class="redfont"><?php echo $total_success;  ?></span>	</span>
			<span>失败团数：<span class="redfont"><?php echo $total_fail;  ?></span></span>

			<span style="margin-left:10px;">团退款状态：</span>
			<select id="search_refund_status" name="search_refund_status">
				<option value="-1">全部</option>
				<option value="1" <?php if($search_refund_status==1){echo 'selected';} ?>>待退款</option>
				<option value="2" <?php if($search_refund_status==2){echo 'selected';} ?>>退款成功</option>
				<option value="3" <?php if($search_refund_status==3){echo 'selected';} ?>>部分退款</option>
			</select>

			<?php if( 2 == $data[0]['type'] ){ ?>
			<br>
			<span>团名额：<span class="redfont"><?php echo $data['data'][0]['luck_draw_num'];  ?></span>	</span>
			<span>产品ID：<span class="redfont"><?php echo $pid;  ?></span>	</span>
			<span>产品名称：<span class="redfont"><?php echo $p_name;  ?></span>	</span>

			<?php } ?>
		</div>
			<?php if( $op == 'lottery' ){ ?>
				<input type="button" class="search_btn" style="float: right;margin: 7px 86px;cursor: pointer;" id="raffle" onclick="go_lottery()" value="抽奖" >
			<?php } ?>

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
					<th width="10%">开团时间</th>
					<th width="5%">退款状态</th>
					<th width="5%">退款成功</th>
					<th width="5%">退款失败</th>
					<th width="10%">操作管理</th>
				</tr>
			</thead>
			<tbody>
			<?php

				foreach($list['data'] as $key => $val){

					switch($val['group_status']){
						case 1:
							$group_status_str = '进行中';
						break;
						case 2:
							$group_status_str = '拼团失败';
						break;
						case 3:
							$group_status_str = '拼团成功';
						break;
						case 4:
							$group_status_str = '待抽奖';
						break;
						case 5:
							$group_status_str = '成团成功';
						break;
						case 6:
							$group_status_str = '成团失败';
						break;
						case -1:
							if( strtotime($val['createtime']) > strtotime('- 5 mins') ){
								$group_status_str = '待付款';
							} else {
								$group_status_str = '支付超时';
							}
						break;
					}

			?>
				<tr style="border:1px solid #D8D8D8">
					<td><?php echo $val['group_id'];?></td>
					<td>
                        <img style="width: 50px;height: 50px;float: left;border-radius: 30px;" src="<?php echo $val['weixin_headimgurl']; ?>"><?php echo $val['name'];?>（<?php echo $val['weixin_name'];?>）
                        <?php if($val['type']==5){?>
                        <br>系数：<?php echo $val['coefficient'];?>
                        <?php }?>
                    </td>
					<td><?php echo $val['group_size'];?></td>
					<td><?php echo $val['price'];?></td>
					<td><?php echo $val['join_num'];?></td>
					<td><?php echo $val['total_price'];?></td>
					<td><?php echo $pid;?></td>
					<td><?php echo $group_status_str;?></td>
					<td><?php echo $val['createtime'];?></td>

					<?php

							if($val['group_status'] == 2){
								?><!--未支付-->

						<?php
								 /*拼团失败*/
									if($data[0]['type'] != 6){//免单团不能退款
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
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $keyid; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">参与记录</span>
													</a>
												<span class="operation-btn WSY-skin-bg" onclick="refund(<?php echo $val['group_id'].",".$val['group_status'] ?>)">退款</span></td>
						<?php				}else if($val['refund_status'] == 2 ){ ?>
												<td><?php echo "全部退款"; ?></td><!--退款状态-->
												<td><?php echo $refund_num; ?></td><!--退款成功-->
												<td><?php echo $refundable_num; ?></td><!--退款失败-->
												<td>
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $keyid; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">参与记录</span>
													</a>
												</td>
						<?php				}else if($val['refund_status'] == 1 ){?>
												<td><?php echo "待退款"; ?></td><!--退款状态-->
												<td>0</td><!--退款成功-->
												<td>0</td><!--退款失败-->
												<td>
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $keyid; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">参与记录</span>
													</a>
												<span class="operation-btn WSY-skin-bg" onclick="refund(<?php echo $val['group_id'].",".$val['group_status'] ?>)">退款</span></td>
						<?php				}else {?>
												<td></td><!--退款状态-->
												<td></td><!--退款成功-->
												<td></td><!--退款失败-->
												<td>
													<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $keyid; ?>" style="cursor:pointer;">
														<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">参与记录</span>
													</a>
												</td>
						<?php					}
									}else{ ?>
										<td></td><!--退款状态-->
										<td></td><!--退款成功-->
										<td></td><!--退款失败-->
										<td>
										    <a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $keyid; ?>" style="cursor:pointer;">
												<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">参与记录</span>
											</a>
										</td>
							<?php   }

							}else{ ?>
								<td></td><!--退款状态-->
								<td></td><!--退款成功-->
								<td></td><!--退款失败-->
								<td>
									<a href="joinGroupRecord.php?group_id=<?php echo $val['group_id']; ?>&keyid=<?php echo $keyid; ?>" style="cursor:pointer;">
										<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $row_activity['id'];?>')">参与记录</span>
									</a>
									<?php if($val['group_status'] == 1 && $val['type'] != 6){?><span class="operation-btn WSY-skin-bg" onclick="finish(<?php echo $val['group_id'];?>)">成功</span><?php } ?></td>
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
		<!-- 抽奖结果表格 -->
		<div id="lottery">
			<div style="position: relative;left: 0px;top: 0px;width: 1058px;height: 556px;">
				<div style="left: 0px;top: 0px;width: 1058px;height: 43px;background: inherit;background-color: rgba(0, 0, 255, 1);text-align:center;">
					<div id="u3951" class="text" style="border-width: 0px;position: absolute;left: 2px;top: 5px;width: 1035px;word-wrap: break-word;">
						<p><span style="font-size: 24px;color: #fff;">抽奖</span></p>
					  </div>
					  <div id="u3953" class="text" style="border-width: 0px;width: 18px;white-space: nowrap;float: right;margin: 9px;cursor: pointer;" onclick="close_lottery()">
						<p><span style="font-size: 24px;color: #fff;">X</span></p>
					  </div>
				</div>
				<div id="lottery_div" style="background-color:#FBFBFB;width: 100%;height: 500px;overflow:auto;">
					<span style="font-size: 15px;margin: 24px;">本次成功拼团数：<?php echo $total_success; ?></span>
					<span style="font-size: 15px;margin: 24px;">本次失败拼团数：<?php echo $total_fail; ?></span>
					<span style="font-size: 15px;margin: 24px;">从成功拼团活动中抽取<?php echo $data[0]['luck_draw_num']; ?>个团名额</span>
					<table class="WSY_t2"  width="97%"  style="border: 1px solid #D8D8D8;border-collapse: collapse;    margin-left: 1.5%;">
					<thead class="WSY_table_header">
						<tr style="border:none">
							<th width="2%" >团ID</th>
							<th width="10%" >团长</th>
							<th width="5%">总金额</th>
							<th width="5%">名额</th>
							<th width="5%">参与人数</th>
							<th width="5%">状态</th>
							<th width="5%">活动时间</th>
							<th width="10%">开团时间</th>
						</tr>
					</thead>
					<tbody id="lottery_data">

					</tbody>

					</table>
					<div style="margin-left:10%;margin-top: 50px;">
						<input type="button" class="search_btn" style="margin: 7px 86px;cursor: pointer;" onclick="go_lottery()"  value="不满意重新抽奖" >
						<input type="button" class="search_btn" style="margin: 7px 86px;cursor: pointer;" onclick="choose_my()" value="不满意自己选择" >
						<input type="button" class="search_btn" style="margin: 7px 86px;cursor: pointer;" onclick="rand_determine()" value="确定中奖名单" >
					</div>
				</div>
			</div>
		</div>
		<!-- 抽奖结果表格 -->



		<!-- 自己选择中奖名单 -->
		<div id="choose">
			<div style="position: relative;left: 0px;top: 200px;width: 1058px;height: 558;">
				<div style="left: 0px;top: 0px;width: 1058px;height: 43px;background: inherit;background-color: rgba(0, 0, 255, 1);text-align:center;">
					<div id="u3951" class="text" style="border-width: 0px;position: absolute;left: 2px;top: 5px;width: 1028px;word-wrap: break-word;">
						<p><span style="font-size: 24px;color: #fff;">选择中奖名单</span></p>
					  </div>
					  <div id="u3953" class="text" style="border-width: 0px;width: 18px;white-space: nowrap;float: right;margin: 9px;cursor: pointer;" onclick="close_lottery()">
						<p><span style="font-size: 24px;color: #fff;">X</span></p>
					  </div>
				</div>
				<div style="background-color:#FBFBFB;width: 100%;height: 450px;overflow: auto;">
					<table class="WSY_t2"  width="97%"  style="border: 1px solid #D8D8D8;border-collapse: collapse;    margin-left: 1.5%;">
					<thead class="WSY_table_header">
						<tr style="border:none">
							<th width="2%" >团ID</th>
							<th width="10%" >团长</th>
							<th width="5%">成团人数</th>
							<th width="5%">价格</th>
							<th width="5%">参与人数-含团长</th>
							<th width="5%">总金额</th>
							<th width="5%">产品编号</th>
							<th width="10%">产品名称</th>
							<th width="5%">状态</th>
							<th width="5%">创建时间</th>
							<th width="5%">操作</th>
						</tr>
					</thead>
					<tbody id="mes">

					</tbody>

					</table>

					<div class="WSY_page2">

					</div>
					<div class="kuang">

					</div>
					<div style="margin-left:10%;margin-top: 50px;">
						<input type="button" class="search_btn" style="margin: 10px 351px;cursor: pointer;" onclick="myself_determine()" value="确定中奖名单" >
					</div>
				</div>
			</div>
		</div>
		<!-- 自己选择中奖名单 -->
		<div class="sharebg sharebg-active"></div>
		<script src="../../../js/fenye/jquery.page1.js"></script>
		<script type="text/javascript">
			var customer_id = '<?php echo $customer_id_en ?>';
			var customer_id1 = '<?php echo $customer_id ?>';
			var pagenum = <?php echo $pagenum ?>;
			var count =<?php echo $page ?>;//总页数
			var count1 =<?php echo $page1 ?>;//总页数
			var page1 = 1;
				//pageCount：总页数
				//current：当前页
			var search_group_id = $("#search_group_id").val();
			var search_head_name = $("#search_head_name").val();
			// var search_head_id = $("#search_head_id").val();
			var search_status = $("#search_status").val();
			var search_refund_status = $("#search_refund_status").val();
			var begintime = $("#begintime").val();
			var endtime = $("#endtime").val();
			var draw_num = '<?php echo $data['data'][0]['luck_draw_num']; ?>';
			var isRefunding = false;


			$(".WSY_page").createPage({
				pageCount:count,
				current:pagenum,
				backFn:function(p){
				 document.location= "luckDrawList.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pid=<?php echo $pid; ?>&op=<?php echo $op; ?>&pagenum="+p+"&search_group_id="+search_group_id+"&search_head_name="+search_head_name+"&search_status="+search_status+"&begintime="+begintime+"&endtime="+endtime+"&search_refund_status="+search_refund_status;
			   }
			});

		    var page = <?php echo $page ?>;

		    function jumppage(){
				var a=parseInt($("#WSY_jump_page").val());
				if((a<1) || (a==pagenum) || (a>page) || isNaN(a)){
					return false;
				}else{
					document.location= "luckDrawList.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pid=<?php echo $pid; ?>&op=<?php echo $op; ?>&pagenum="+a+"&search_group_id="+search_group_id+"&search_head_name="+search_head_name+"&search_status="+search_status+"&begintime="+begintime+"&endtime="+endtime+"&search_refund_status="+search_refund_status;
				}
		    }

		$('#my_excel').click(function(){
			var url='/weixin/plat/app/index.php/Excel/commonshop_excel_luck_draw_list/customer_id/'+customer_id1+'/keyid/<?php echo $keyid; ?>/pid/<?php echo $pid; ?>/op/<?php echo $op; ?>';

			if(search_group_id>0){
				url += "/search_group_id/"+search_group_id;
			}
			// if(search_head_id>0){
				// url += "/search_head_id/"+search_head_id;
			// }
			if(search_status!=0){
				url += "/search_status/"+search_status;
			}
			if(search_refund_status!=-1){
				url += "/search_refund_status/"+search_refund_status;
			}
			if(search_head_name!=''){
				url += "/search_head_name/"+search_head_name;
			}
			if(begintime!=''){
				url += "/begintime/"+begintime;
			}
			if(endtime!=''){
				url += "/endtime/"+endtime;
			}

			document.location = url;
		});

		$('#my_search').click(function(){
			var search_group_id = $("#search_group_id").val();
			var search_head_name = $("#search_head_name").val();
			// var search_head_id = $("#search_head_id").val();
			var search_status = $("#search_status").val();
			var search_refund_status = $("#search_refund_status").val();
			var begintime = $("#begintime").val();
			var endtime = $("#endtime").val();

			var url = "luckDrawList.php?customer_id="+customer_id+"&keyid=<?php echo $keyid; ?>&pid=<?php echo $pid; ?>&op=<?php echo $op; ?>";

			if(endtime<begintime && begintime!='' && endtime!=''){
				alert('开始时间不得大于结束时间');
				return;
			}

			if( search_group_id != '' ){
				if(search_group_id > 0){
					url += "&search_group_id="+search_group_id;
				}else{
					alert('请输入正确的团ID！');
					return;
				}
			}
			/*if( search_head_id != '' ){
				if(search_head_id > 0){
					url += "&search_head_id="+search_head_id;
				}else{
					alert('请输入正确的团长ID！');
					return;
				}
			}*/

			if(begintime!=''){
				url += "&begintime="+begintime;
			}
			if(search_head_name!=''){
				url += "&search_head_name="+search_head_name;
			}
			if(endtime!=''){
				url += "&endtime="+endtime;
			}
			if(search_status!=0){
				url += "&search_status="+search_status;
			}
			if(search_refund_status!=-1){
				url += "&search_refund_status="+search_refund_status;
			}

			document.location=url;
		});

		function go_lottery(){
			rand_lottery();
			$('#lottery').show();
			$('.sharebg').show();
		}

		function rand_lottery(){
			$.ajax({
				type: 'POST',
				url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
				data:{
					op		: 'rand_lottery',
					keyid	: '<?php echo $keyid; ?>',
					pid		: '<?php echo $pid; ?>',
					draw_num: draw_num
				},
				dataType: 'json',
				success: function(e){
					var html = '';
					var list = e.data;
					if( list == null ){
						$('#lottery_div').html('<span style="color: red;font-size: 19px;">该团已抽奖</span>');
					}else{
						$.each(list,function(id,val){
							html += "<tr style='border:1px solid #D8D8D8' data-lottery_id="+val.group_id+">";
							html +=	"<td>"+val.group_id+"</td>";
							html +=	"<td>"+val.name+"("+val.weixin_name+")</td>";
							html +=	"<td>"+val.total_price+"</td>";
							html +=	"<td>"+val.success_num+"</td>";
							html +=	"<td>"+val.join_num+"</td>";
							html +=	"<td>待抽奖</td>";
							html +=	"<td>"+val.start_time+"至"+val.end_time+"</td>";
							html +=	"<td>"+val.createtime+"</td>";
							html +=	"</tr>";
						});
						$('#lottery_data').html(html);
					}
				}
			});
		}

		function close_lottery(){
			$('#lottery').hide();
			$('.sharebg').hide();
			$('#choose').hide();
		}

		function choose_my(){
			$('#lottery').hide();
			$('#choose').show();
				$.ajax({
					type: 'POST',
					url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
					data:{
						op		: 'get_group_mes',
						keyid	: '<?php echo $keyid; ?>',
						page	: page1,
						pid		: '<?php echo $pid; ?>'
					},
					dataType: 'json',
					success: function(result){
						var html = '';
						var e = result.data;
						$.each(e,function(id,val){
							html += '<tr style="border:1px solid #D8D8D8" id="group_'+val.group_id+'" >';
							html += '<td>'+val.group_id+'</td>';
							html += '<td class="user"><img style="width: 50px;height: 50px;float: left;border-radius: 30px;" src="'+val.weixin_headimgurl+'"><span>'+val.name+'</span>（'+val.weixin_name+'）</td>';
							html += '<td>'+val.success_num+'</td>';
							html += '<td>'+val.price+'</td>';
							html += '<td>'+val.join_num+'</td>';
							html += '<td>'+val.total_price+'</td>';
							html += '<td>'+val.pid+'</td>';
							html += '<td>'+val.pname+'</td>';

							if( val.group_status == 1 ){
								html += '<td>拼团中</td>';
							}else if( val.group_status == 2 ){
								html += '<td>拼团失败</td>';
							}else if( val.group_status == 3 ){
								html += '<td>拼团成功</td>';
							}else if( val.group_status == 4 ){
								html += '<td>待抽奖</td>';
							}

							html += '<td>'+val.createtime+'</td>';

							html += '<td><span class="operation-btn WSY-skin-bg" onclick="get_choose('+val.group_id+')">选择</span></td>	';
							html += '</tr>';
						});
						$('#mes').html(html);
						$(".WSY_page2").createPage({
							pageCount:count1,
							current:page1,
							backFn:function(p){
								page1 = p;
								choose_my();
						   }
						});
					}
				});
		}
		function get_choose(group_id){
			var is_pass = false;
			var k = 0;
			$('.kuang_div').each(function(){
				k++;
				if( $(this).data('group_id') == group_id ){
					is_pass = true;
				}
			});
			if(is_pass || k == draw_num){
				return;
			}
			var img = $('#group_'+group_id+' .user img').attr('src');
			var name = $('#group_'+group_id+' .user span').text();
			var html = '';
			html += '<div class="kuang_div" data-group_id='+group_id+'>';
			html += '	<img src="'+img+'">';
			html += '	<div class="kuang_name">'+name+'</div>';
			html += '	<div class="skuang">';
			html += '		<span>删除</span>';
			html += '	</div>';
			html += '</div>';
			$('.kuang').append(html);

			$('.skuang').click(function(){
				$(this).parent('.kuang_div').remove();
			});
		}

		function rand_determine(){
			var lottery_array = new Array();
			$('#lottery_data tr').each(function(){
				lottery_array.push($(this).data('lottery_id'));
			});
			if( lottery_array.length > 0 ){
				submit_lottery(lottery_array);
			}

		}

		function myself_determine(){
			var lottery_array = new Array();
			$('.kuang_div').each(function(){
				lottery_array.push($(this).data('group_id'));
			});
			if( lottery_array.length > 0 ){
				submit_lottery(lottery_array);
			}
		}

		var isSubmiting = 0;
		function submit_lottery(lottery_array){
			if ( isSubmiting ) {
				alert('正在确认中奖中，请勿重复操作！');
				return;
			}
			isSubmiting = 1;
			var lottery_ = JSON.stringify(lottery_array);
				$.ajax({
					type: 'POST',
					url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
					data:{
						op		: 'submit_lottery',
						keyid	: '<?php echo $keyid; ?>',
						pid		: '<?php echo $pid; ?>',
						lottery : lottery_
					},
					dataType: 'json',
					success: function(e){
						alert(e.msg);
						history.go(0);
					}
				});
		}
		//退款
function refund(group_id,group_status){
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
function showRefundBox(title,confirm_btn,cancel_btn,callbackfunc){
	$('.shadow').show();
	var tip_img = '';
	var is_can_back = 1;
	$('#toolTipLayer').css('z-index','5555');
	var html = '';
	html += '<div class="refund-box">';
	html += '	<div class="refund-box-title WSY-skin-bg">'+title+'</div>';
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
	layer.tips('原路返回仅支持：微信支付，环迅支付，会员卡支付，零钱支付！','#tips_2');
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
