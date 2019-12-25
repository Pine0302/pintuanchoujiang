<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../../../../weixinpl/proxy_info.php');
_mysql_query("SET NAMES UTF8");

require_once('../../../../weixinpl/function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

/*搜索条件*/
$condition = " ccot.isvalid=true AND ccot.customer_id=".$customer_id;
$search_aid = '';
if( !empty($_GET['search_aid']) ){
	$search_aid = $configutil->splash_new($_GET["search_aid"]);
	$condition .= " AND ccot.activitie_id=".$search_aid;
}
$search_batchcode = '';
if( !empty($_GET['search_batchcode']) ){
	$search_batchcode = $configutil->splash_new($_GET["search_batchcode"]);
	$condition .= " AND ccot.batchcode='".$search_batchcode."'";
}
$search_gid = '';
if( !empty($_GET['search_gid']) && $_GET['search_gid'] > 0 ){
	$search_gid = $configutil->splash_new($_GET["search_gid"]);
	$condition .= " AND ccot.group_id=".$search_gid;
}
$search_phone = '';
if( !empty($_GET['search_phone']) ){
	$search_phone = $configutil->splash_new($_GET["search_phone"]);
	$condition .= " AND wcoa.phone='".$search_phone."'";
}
$search_status = '';
if( !empty($_GET['search_status']) && $_GET['search_status'] > 0 ){
	$search_status = $configutil->splash_new($_GET["search_status"]);
	if( $search_status == 3 ) {
		$condition .= " AND ccot.status=".$search_status." AND ccot.is_refund=1";
	} else if( $search_status == 7 ) {
		$condition .= " AND ccot.status=3 AND ccot.is_refund=0";
	} else if( $search_status == 1 ){
		$condition .= " AND ccot.status=".$search_status." AND ccot.createtime > '".date('Y-m-d H:i:s',strtotime("- 5 mins"))."'";
	} else if( $search_status == 8 ){
		$condition .= " AND ccot.status=1 AND ccot.createtime < '".date('Y-m-d H:i:s',strtotime("- 5 mins"))."'";
	}else if( $search_status == 9 ){
		$condition .= " AND ccot.status=7 ";
	}else if( $search_status == 10 ){
		$condition .= " AND ccot.status=8 ";
	} else {
		$condition .= " AND ccot.status=".$search_status;
	}

}
$search_type = '';
if( !empty($_GET['search_type']) && $_GET['search_type'] > 0 ){
	$search_type = $configutil->splash_new($_GET["search_type"]);
	$condition .= " AND cgot.type=".$search_type;
	}

$search_addressname = '';
if( !empty($_GET['search_addressname']) ){
	$search_addressname = $configutil->splash_new($_GET["search_addressname"]);
	$condition .= " AND wcoa.name='".$search_addressname."'";
}
$search_username = '';
if( !empty($_GET['search_username']) ){
	$search_username = $configutil->splash_new($_GET["search_username"]);
	$condition .= " AND (wu.name='".$search_username."' OR wu.weixin_name='".$search_username."')";
}
$search_time_type = '';
if( !empty($_GET['search_time_type']) && $_GET['search_time_type'] > 0 ){
	$search_time_type = $configutil->splash_new($_GET["search_time_type"]);
}
$search_start_time = '';
$search_end_time = '';
if( !empty($_GET['search_start_time']) ){
	$search_start_time = $configutil->splash_new($_GET["search_start_time"]);
	switch( $search_time_type ){
		case 1:
			$condition .= " AND UNIX_TIMESTAMP(ccot.createtime)>=".strtotime($search_start_time);
		break;
		case 2:
			$condition .= " AND UNIX_TIMESTAMP(ccot.paytime)>=".strtotime($search_start_time);
		break;
	}
}
if( !empty($_GET['search_end_time']) ){
	$search_end_time = $configutil->splash_new($_GET["search_end_time"]);
	switch( $search_time_type ){
		case 1:
			$condition .= " AND UNIX_TIMESTAMP(ccot.createtime)<=".strtotime($search_end_time);
		break;
		case 2:
			$condition .= " AND UNIX_TIMESTAMP(ccot.paytime)<=".strtotime($search_end_time);
		break;
	}
}
/*搜索条件*/
/*获取的字段*/
$filed = " ccot.is_refund,ccot.user_id,ccot.batchcode,ccot.is_head,wu.name AS uname,ccot.paystyle,ccot.totalprice,ccot.paytime,ccot.activitie_id,ccot.group_id,wcoa.name AS aname,wcoa.phone AS aphone,wu.weixin_name,wu.phone AS uphone,wu.weixin_headimgurl,wcoa.location_p,wcoa.location_c,wcoa.location_a,wcoa.address,ccopmt.pname,ccot.rcount,ccopmt.prvalues_name,ccot.createtime,ccot.status,cgot.type,cgot.lottery_user_id,wco.is_sendorder ";
$filed_count = " count(1) AS bcount ";	//统计数量
/*获取的字段*/
$bcount = $collageActivities -> get_crew_order($condition,$filed_count)['batchcode'][0]['bcount'];
if( $bcount == '' ){
	$bcount = 0;
}

$pagenum = 1;//页码
$pagesize = 20;//每页数据数量

if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}

$start = ($pagenum-1) * $pagesize;
$end = $pagesize;

$condition .= " ORDER BY ccot.createtime DESC LIMIT ".$start.",".$end;
$info = $collageActivities -> get_crew_order($condition,$filed)['batchcode'];
//print_r($info);exit;
$page = ceil($bcount/$end);

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
<title>拼团订单列表</title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link href="../../../common/add/css/global.css" rel="stylesheet" type="text/css">
<link href="../../../common/add/css/main.css" rel="stylesheet" type="text/css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/inside.css" media="all">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../../../common/js/inside.js"></script>
<script type="text/javascript" src="../../../js/tis.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
</head>
<style>
.operation-btn{display:inline-block;padding: 5px 10px;color: #fff;border-radius: 2px;cursor:pointer;text-align: center;margin: 2px 0;}
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

</style>
<body>
</div>
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
				<span>活动ID：</span><input type="text" class="search-box" id="search-aid" value="<?php echo $search_aid;?>" onkeyup="clearInt(this)" />
				<span>订单号：</span><input type="text" class="search-box" id="search-batchcode" value="<?php echo $search_batchcode;?>" onkeyup="clearInt(this)" />
				<span>团ID：</span><input type="text" class="search-box" id="search-gid" value="<?php echo $search_gid;?>" onkeyup="clearInt(this)" />
				<span>手机号：</span><input type="text" class="search-box" id="search-phone" value="<?php echo $search_phone;?>" onkeyup="clearInt(this)" />
				<span>状态：</span>
				<select id="search-status">
					<option value="-1" <?php if($search_status==-1){echo 'selected';}?>>全部</option>
					<option value="1" <?php if($search_status==1){echo 'selected';}?>>待付款</option>
					<option value="8" <?php if($search_status==8){echo 'selected';}?>>支付超时</option>
					<option value="2" <?php if($search_status==2){echo 'selected';}?>>已付款</option>
					<option value="3" <?php if($search_status==3){echo 'selected';}?>>待退款（申请退款）</option>
					<option value="7" <?php if($search_status==7){echo 'selected';}?>>待退款（拼团失败）</option>
					<option value="6" <?php if($search_status==6){echo 'selected';}?>>已退款</option>
					<option value="4" <?php if($search_status==4){echo 'selected';}?>>拼团失败</option>
					<option value="5" <?php if($search_status==5){echo 'selected';}?>>拼团成功</option>
					<option value="9" <?php if($search_status==9){echo 'selected';}?>>成团成功（免单团）</option>
					<option value="10" <?php if($search_status==10){echo 'selected';}?>>成团失败（免单团）</option>
				</select>
			</div>
			<div class="header-left">
				<span>收货人：</span><input type="text" class="search-box" id="search-addressname" value="<?php echo $search_addressname;?>" />
				<span>用户名：</span><input type="text" class="search-box" id="search-username" value="<?php echo $search_username;?>" />
				<select id="search-time-type">
					<option value="1" <?php if($search_time_type==1){echo 'selected';}?>>下单时间</option>
					<option value="2" <?php if($search_time_type==2){echo 'selected';}?>>支付时间</option>
				</select>
				<input type="text" class="search-box" id="start_time" value="<?php echo $search_start_time;?>" onfocus="WdatePicker({dateFmt:'yyyy-M-d HH:mm:ss',maxDate:'#F{$dp.$D(\'end_time\')}'});" readonly />
				至
				<input type="text" class="search-box" id="end_time" value="<?php echo $search_end_time;?>" onfocus="WdatePicker({dateFmt:'yyyy-M-d HH:mm:ss',minDate:'#F{$dp.$D(\'start_time\')}'});" readonly />
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
			</div>
        <ul class="WSY_righticon" style="margin-right:5%;">
			<li style="margin-right: 20px;"><span class="operation-btn WSY-skin-bg" id="search-button">搜索</span></li>
            <li style="margin-right: 20px;"><span class="operation-btn WSY-skin-bg" id="export-button" onclick="exportExcel();">导出</span></li>
        </ul>
    </div>
			<table width="97%" class="WSY_table" id="WSY_t1">
				<thead class="WSY_table_header">
					<th width="22%">订单号</th>
					<th width="18%">用户信息</th>
					<th width="8%">支付方式</th>
					<th width="7%">支付金额</th>
					<th width="10%">支付时间</th>
					<th width="7%">活动ID</th>
					<th width="7%">团ID</th>
					<th width="15%">收货信息</th>
					<th width="18%">产品信息</th>
					<th width="10%">下单时间</th>
					<th width="10%">设定中奖用户id</th>
					<th width="8%">状态</th>
					<th width="12%">操作管理</th>
				</thead>
				<?php
					foreach( $info as $v ){
						$query = "SELECT pay_batchcode FROM weixin_commonshop_order_prices WHERE batchcode='".$v['batchcode']."'";
						$result = _mysql_query($query) or die('Query failed:'.mysql_error());
						$pay_batchcode = mysql_fetch_assoc($result)['pay_batchcode'];

                        $order_coefficient = -1;
                        $query1 = "SELECT order_coefficient FROM collage_bbt_order_extend WHERE batchcode='".$v['batchcode']."'";
						$result1 = _mysql_query($query1) or die('Query1 failed:'.mysql_error());
						$order_coefficient = mysql_fetch_assoc($result1)['order_coefficient'];
				?>
				<tr>
					<td title="<?php echo $v['batchcode'].'（'.$pay_batchcode.'）';?>" style="word-wrap: break-word;"><?php echo $v['batchcode'].'（'.$pay_batchcode.'）';?></td>
					<td>
						<?php
							if( $v['is_head'] == 1 ){
						?>
						<div style="width: 100%;text-align: right;">
							<img src="../../../common/images_V6.0/contenticon/is_head.png" style="position: absolute;width: 30px;margin: 0 -17px;">
						</div>
						<?php
							}
						?>
						<div style="display:inline-block;">
						<?php
							if( empty($v['weixin_headimgurl']) ){
								$v['weixin_headimgurl'] = '../../../common/custom_temp/images/username.png';
							} else {
								$pos = strpos($v['weixin_headimgurl'],"http://");
								$pos2 = strpos($v['weixin_headimgurl'],"https://");

								if($pos===0 || $pos2===0){

								}else{
									$pos1 = strpos($v['weixin_headimgurl'],"../../../");
									$pos2 = strpos($v['weixin_headimgurl'],"../../");
									$pos3 = strpos($v['weixin_headimgurl'],"../");
									if($pos1===0){
										$v['weixin_headimgurl'] = substr($v['weixin_headimgurl'],9);
									}elseif($pos2===0){
										$v['weixin_headimgurl'] = substr($v['weixin_headimgurl'],6);
									}elseif($pos3===0){
										$v['weixin_headimgurl'] = substr($v['weixin_headimgurl'],3);
									}
									$v['weixin_headimgurl'] = BaseURL.$v['weixin_headimgurl'];
								}
							}
						?>
							<img src="<?php echo $v['weixin_headimgurl'];?>" class="user_img" >
						</div>
						<div style="display:inline-block;">
							<span><?php echo $v['user_id'];?></span>
							<span>昵称（<?php echo $v['weixin_name']?$v['weixin_name']:$v['uname'];?>)</span>
							<span><?php echo $v['uphone'];?></span>
                            <?php if($order_coefficient>0){?>
                            <span>抱抱团系数：<?php echo $order_coefficient;?></span>
                            <?php }?>
						</div>
					</td>
					<td style="text-align:center"><?php echo $v['paystyle']==-1?'未支付':$v['paystyle'];?></td>
					<td style="text-align:center;word-wrap: break-word;"><?php echo $v['totalprice'];?></td>
					<td><?php echo $v['paytime'];?></td>
					<td><?php echo $v['activitie_id'];?></td>
					<td><?php echo $v['group_id'];?></td>
					<td>
						<span><?php echo $v['aname'];?></span>
						<span><?php echo $v['aphone'];?></span>
						<span><?php echo $v['location_p'].$v['location_c'].$v['location_a'].$v['address'];?></span>
					</td>
					<td>
						<span><?php echo $v['pname'];?></span>
						<span><?php echo $v['rcount'];?></span>
						<span><?php echo $v['prvalues_name'];?></span>
					</td>
					<td><?php echo $v['createtime'];?></td>
					<td><?php echo $v['lottery_user_id'];?></td>

					<td style="text-align:center">
					<?php
						switch( $v['status'] ){
							case 1:
								if ( strtotime($v['createtime']) > strtotime('- 5 mins') ) {
									echo '待付款';
								} else {
									echo '支付超时';
								}
							break;
							case 2:
								echo '已付款';
							break;
							case 3:
								if( $v['is_refund'] ) {
									echo '待退款（申请退款）';
								} else {
									echo '待退款（拼团失败）';
								}
							break;
							case 4:
								echo '拼团失败';
							break;
							case 5:
								echo '拼团成功';
							break;
							case 6:
								echo '已退款';
							break;
							case 7:
								echo '成团成功';
							break;
							case 8:
								echo '成团失败';
							break;
						}
					?>
					</td>
					<td>
					
					<?php
						switch( $v['status'] ){
							case 3:
					?>
						<span class="operation-btn WSY-skin-bg" onclick="refund('<?php echo $v['batchcode'];?>','<?php echo $v['paystyle'];?>')">退款</span>
					<?php
							break;
							case 2:
                     ?>

                               <a  href="/weixinpl/back_newshops/Users/fans/set_prizer.php?customer_id=<?php echo $customer_id_en; ?>&group_id=<?php echo $v['group_id'];?>&fromw=<?php echo $fromw; ?>&user_id=<?php echo $user_id; ?>&isAgent=<?php echo $isAgent; ?>&pagenum=<?php echo $pagenum; ?>&old_parent_id=<?php echo $parent_id; ?>">
                                    <span class="operation-btn WSY-skin-bg">更改中奖用户id</span>
                                </a>

                     <?php
							case 5:
							case 7:
							case 8:
								$send_type = 0;
								$query_order = "SELECT is_collageActivities FROM weixin_commonshop_orders WHERE batchcode='".$v['batchcode']."' AND isvalid=true";
								$result_order = _mysql_query($query_order) or die('Query_order failed:'.mysql_error());
								$is_collageActivities = mysql_fetch_assoc($result_order)['is_collageActivities'];
								if( $is_collageActivities == 1 ){
									if( $v['is_sendorder'] == 1 ){
										$query_order = "SELECT send_type FROM system_send_order WHERE order_id='".$v['batchcode']."' AND isvalid=true";
										$result_order = _mysql_query($query_order) or die('Query_order failed:'.mysql_error());
										$send_type = mysql_fetch_assoc($result_order)['send_type'];

									}
					?>
						<span class="operation-btn WSY-skin-bg" onclick="showBatchcode('<?php echo $v['batchcode'];?>', <?php echo $send_type;?>)">查看订单</span>
					<?php
								}
							break;

							default:
							break;
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
<div class="shadow"></div>
<!--内容框架结束-->
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script src="../../../js/fenye/jquery.page1.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<script type="text/javascript" src="../../Common/js/Base/basicdesign/ToolTip.js"></script>
<script>
var customer_id = '<?php echo $customer_id;?>';
var customer_id_en = '<?php echo $customer_id_en;?>';
var search_aid = '<?php echo $search_aid;?>';
var search_gid = '<?php echo $search_gid;?>';
var search_batchcode = '<?php echo $search_batchcode;?>';
var search_phone = '<?php echo $search_phone;?>';
var search_status = '<?php echo $search_status;?>';
var search_addressname = '<?php echo $search_addressname;?>';
var search_username = '<?php echo $search_username;?>';
var search_time_type = '<?php echo $search_time_type;?>';
var search_start_time = '<?php echo $search_start_time;?>';
var search_end_time = '<?php echo $search_end_time;?>';
var search_type = '<?php echo $search_type;?>';
var isRefunding = false;

var pagenum = <?php echo $pagenum ?>;
var count =<?php echo $page ?>;//总页数
  	//pageCount：总页数
	//current：当前页
	$(".WSY_page").createPage({
        pageCount:count,
        current:pagenum,
        backFn:function(p){
			var url = "crewOrder.php?pagenum="+p+"&customer_id=<?php echo passport_encrypt((string)$customer_id) ?>";
			if( search_aid != '' && search_aid > 0 ){
				url += '&search_aid='+search_aid;
			}
			if( search_gid != '' && search_gid > 0 ){
				url += '&search_gid='+search_gid;
			}
			if( search_batchcode != '' ){
				url += '&search_batchcode='+search_batchcode;
			}
			if( search_phone != '' ){
				url += '&search_phone='+search_phone;
			}
			if( search_status > 0 ){
				url += '&search_status='+search_status;
			}
			if( search_type > 0 ){
				url += '&search_type='+search_type;
			}
			if( search_addressname != '' ){
				url += '&search_addressname='+search_addressname;
			}
			if( search_username != '' ){
				url += '&search_username='+search_username;
			}
			if( search_time_type != '' ){
				url += '&search_time_type='+search_time_type;
			}
			if( search_start_time != '' ){
				url += '&search_start_time='+search_start_time;
			}
			if( search_end_time != '' ){
				url += '&search_end_time='+search_end_time;
			}
			document.location = url;
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
		var url = "crewOrder.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&pagenum="+a;
		if( search_aid != '' && search_aid > 0 ){
				url += '&search_aid='+search_aid;
			}
			if( search_gid != '' && search_gid > 0 ){
				url += '&search_gid='+search_gid;
			}
			if( search_batchcode != '' ){
				url += '&search_batchcode='+search_batchcode;
			}
			if( search_phone != '' ){
				url += '&search_phone='+search_phone;
			}
			if( search_status > 0 ){
				url += '&search_status='+search_status;
			}
			if( search_type > 0 ){
				url += '&search_type='+search_type;
			}
			if( search_addressname != '' ){
				url += '&search_addressname='+search_addressname;
			}
			if( search_username != '' ){
				url += '&search_username='+search_username;
			}
			if( search_time_type != '' ){
				url += '&search_time_type='+search_time_type;
			}
			if( search_start_time != '' ){
				url += '&search_start_time='+search_start_time;
			}
			if( search_end_time != '' ){
				url += '&search_end_time='+search_end_time;
			}
		document.location = url;
	}
}
</script>
<script>
//导出
function exportExcel(){
	var url='/weixin/plat/app/index.php/Excel/commonshop_excel_crew_order/customer_id/<?php echo passport_decrypt($customer_id); ?>';

	if( search_aid != '' && search_aid > 0 ){
		url += '/search_aid/'+search_aid;
	}
	if( search_gid != '' && search_gid > 0 ){
		url += '/search_gid/'+search_gid;
	}
	if( search_batchcode != '' ){
		url += '/search_batchcode/'+search_batchcode;
	}
	if( search_phone != '' ){
		url += '/search_phone/'+search_phone;
	}
	if( search_status > 0 ){
		url += '/search_status/'+search_status;
	}
	if( search_type > 0 ){
		url += '/search_type/'+search_type;
	}
	if( search_addressname != '' ){
		url += '/search_addressname/'+search_addressname;
	}
	if( search_username != '' ){
		url += '/search_username/'+search_username;
	}
	if( search_time_type != '' ){
		url += '/search_time_type/'+search_time_type;
	}
	if( search_start_time != '' ){
		url += '/search_start_time/'+search_start_time;
	}
	if( search_end_time != '' ){
		url += '/search_end_time/'+search_end_time;
	}
	//alert(url);
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
	var search_aid = $('#search-aid').val();
	var search_gid = $('#search-gid').val();
	var search_batchcode = $('#search-batchcode').val();
	var search_phone= $('#search-phone').val();
	var search_status= $('#search-status').val();
	var search_addressname = $('#search-addressname').val();
	var search_username = $('#search-username').val();
	var search_time_type = $('#search-time-type').val();
	var search_start_time = $('#start_time').val();
	var search_end_time = $('#end_time').val();
	var search_type = $('#search-type').val();

	var url = "crewOrder.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>";
	if( search_aid != '' && search_aid > 0 ){
		url += '&search_aid='+search_aid;
	}
	if( search_gid != '' && search_gid > 0 ){
		url += '&search_gid='+search_gid;
	}
	if( search_batchcode != '' ){
		url += '&search_batchcode='+search_batchcode;
	}
	if( search_phone != '' ){
		url += '&search_phone='+search_phone;
	}
	if( search_status > 0 ){
		url += '&search_status='+search_status;
	}
	if( search_type > 0 ){
		url += '&search_type='+search_type;
	}
	if( search_addressname != '' ){
		url += '&search_addressname='+search_addressname;
	}
	if( search_username != '' ){
		url += '&search_username='+search_username;
	}
	if( search_time_type != '' ){
		url += '&search_time_type='+search_time_type;
	}
	if( search_start_time != '' ){
		url += '&search_start_time='+search_start_time;
	}
	if( search_end_time != '' ){
		url += '&search_end_time='+search_end_time;
	}
	document.location = url;
});
//退款
function refund(batchcode, paystyle){
	showRefundBox(paystyle,'退回方式确认','确定','取消',function(){
		var refund_way = $('input[name=refund_way]:checked').val();
		if( refund_way == undefined ){
			alert('请选择退回方式！');
			return false;
		}

		if( isRefunding ){
			alert('正在退款中，请勿重复操作！');
			return;
		} else {
			isRefunding = true;
		}
		//return alert(batchcode);
		$.ajax({
			url: 'order_refund.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>',
			dataType: 'json',
			type: 'post',
			data: {
				batchcode : batchcode,
				refund_way : refund_way
			},
			success: function(res){
				alert(res.msg);
				window.location.reload();
			}
		})
	})
}
//查看订单
function showBatchcode(batchcode, send_type){
	var url = '';
	switch ( send_type ) {
		case 0:
			url = '../../Order/order/order.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&search_batchcode='+batchcode;
		break;

		case 1:
			url = '<?php echo Protocol;?>'+document.domain+'/weixinpl/back_newshops/Order/order/order.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&search_batchcode='+batchcode+'&from_page=1';
		break;
		case 2:
			url = '<?php echo Protocol;?>'+document.domain+'/weixinpl/back_newshops/Order/order/order.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&search_batchcode='+batchcode+'&from_page=2';
		break;
	}
	window.location.href = url;
}
//退款框
function showRefundBox(paystyle,title,confirm_btn,cancel_btn,callbackfunc){
	$('.shadow').show();
	var tip_img = '';
	var is_can_back = 1;
	$('#toolTipLayer').css('z-index','5555');
	switch( paystyle ) {
		case '微信支付':
		case '零钱支付':
		case '会员卡余额支付':
		case '支付宝支付':
		case '环迅快捷支付':
		case '环迅微信支付':
		case '威富通支付':
		case '健康钱包支付':
		case '优惠抵扣':
		case '区块链积分支付':
			is_can_back = 1;

		break;
		default:
			is_can_back = 0;
			tip_img = '<img src="../../Common/images/Base/help.png" onMouseOver="toolTip(\''+paystyle+'暂不支持原路返回\')" onMouseOut="toolTip()">';
		break;
	}

	var html = '';
	html += '<div class="refund-box">';
	html += '	<div class="refund-box-title">'+title+'</div>';
	html += '	<div class="refund-box-content">';
	html += '		<span>退回方式：</span>';
	html += '		<input type="radio" name="refund_way" id="original" value="1" ';
	if( is_can_back ) {
		html += ' /><label for="original">原路返回</label>';
	} else {
		html += 'disabled /><label for="original">原路返回</label>（'+paystyle+'）'+tip_img;
	}


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