<?php
header("Content-type: text/html; charset=utf-8"); 
session_cache_limiter( "private, must-revalidate" ); 
require('../config.php');
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require_once(LocalBaseURL."function_model/collageActivities.php");	//拼团
$collageActivities = new collageActivities($customer_id);

$user_id = $configutil->splash_new($_POST["user_id"]);
$pid = $configutil->splash_new($_POST["pid"]);
$num = $configutil->splash_new($_POST["num"]);
$activitie_id = $configutil->splash_new($_POST["activitie_id"]);
$group_id = $configutil->splash_new($_POST["group_id"]);
$batchcode = $configutil->splash_new($_POST["batchcode"]);
$comefrom = $configutil->splash_new($_POST["comefrom"]);	//1：下单，2：支付

$res = array('code'=>0,'msg'=>'','data'=>'');
//开团检测活动的有效性，参团检测团的有效性
if( $group_id > 0 ){
	//检测团的有效性（参团）
	$query_group = "SELECT isvalid,status,success_num,join_num,endtime,activitie_id,pid,type,createtime,head_id FROM collage_group_order_t WHERE id=".$group_id;
	$result_group = _mysql_query($query_group) or die('Query_group failed:'.mysql_error());
	$group_info = mysql_fetch_assoc($result_group);
	
    //检测活动的有效性
	$query_activity = "SELECT status,isvalid,start_time,end_time,number,user_level,type,head_times FROM collage_activities_t WHERE id=".$activitie_id;
	$result_activity = _mysql_query($query_activity) or die('Query_activity failed:'.mysql_error());
	$activity_info = mysql_fetch_assoc($result_activity);
    
    if( !$activity_info['isvalid'] ){
		$res['code'] = 40001;
		$res['msg'] = "活动已终止！";
		die(json_encode($res));
	}
    
/*      参团无视活动有没有进行
     if( $activity_info['status'] != 2 ){
		$res['code'] = 40002;
		$res['msg'] = "活动不在进行中！";
		die(json_encode($res));
	} */   	
	if( strtotime($activity_info['start_time']) > time() ){
		$res['code'] = 40003;
		$res['msg'] = "活动还未开始！";
		die(json_encode($res));
	}
	if( strtotime($activity_info['end_time']) < time() ){
		$res['code'] = 40004;
		$res['msg'] = "活动已结束！";
		die(json_encode($res));
	}        
	
	if( !$group_info['isvalid'] ){
		$res['code'] = 40001;
		$res['msg'] = "该团已终止！";
		die(json_encode($res));
	}
	if( $group_info['status'] != 1 ){
		$res['code'] = 40002;
		$res['msg'] = "该团不在进行中！";
		die(json_encode($res));
	}
	if( $group_info['success_num'] <= $group_info['join_num'] ){
		$res['code'] = 40003;
		$res['msg'] = "该团已达到成团人数！";
		die(json_encode($res));
	}
	
	$query_activity = "SELECT user_level,number,ginseng_num,is_since,end_time FROM collage_activities_t WHERE id=".$activitie_id;
	$result_activity = _mysql_query($query_activity) or die('Query_activity failed:'.mysql_error());
	$activity_info = mysql_fetch_assoc($result_activity);
    
    if($group_info['endtime']=='0000-00-00 00:00:00'){
        if( strtotime($activity_info['end_time']) < time() ){
            $res['code'] = 40004;
            $res['msg'] = "该团已结束！";
            die(json_encode($res));
        }         
    }else{
        if( strtotime($group_info['endtime']) < time() ){
            $res['code'] = 40004;
            $res['msg'] = "该团已结束！";
            die(json_encode($res));
        }  
    }    
    
    /* 自购检测 */
    if($activity_info['is_since']==0){//没开启自购
        if($group_info['head_id']==$user_id){//参团用户是团长
            $res['code'] = 40012;
            $res['msg'] = "该活动不能自购！";
            die(json_encode($res));
        }
    }

    /* 检测参团次数 */
    $query_user_number = "SELECT count(1) AS ucount FROM collage_crew_order_t WHERE activitie_id=".$activitie_id." AND user_id=".$user_id." AND ( status=2 OR status=3 OR status=5 OR status=7)  AND is_head=2 AND customer_id=".$customer_id;
    $result_user_number = _mysql_query($query_user_number) or die('Query_user_number failed:'.mysql_error());
    $ucount = mysql_fetch_assoc($result_user_number)['ucount'];

    if( $activity_info['ginseng_num'] > 0 && $activity_info['ginseng_num'] <= $ucount ){
        $res['code'] = 40006;
        $res['msg'] = "您的参团次数已超过限制！";
        die(json_encode($res));
    }    
    
} else {
	//检测活动的有效性
	$query_activity = "SELECT status,isvalid,start_time,end_time,number,user_level,type,head_times,ginseng_num FROM collage_activities_t WHERE id=".$activitie_id;
	$result_activity = _mysql_query($query_activity) or die('Query_activity failed:'.mysql_error());
	$activity_info = mysql_fetch_assoc($result_activity);

	if( !$activity_info['isvalid'] ){
		$res['code'] = 40001;
		$res['msg'] = "活动已终止！";
		die(json_encode($res));
	}
	if( $activity_info['status'] != 2 ){
		$res['code'] = 40002;
		$res['msg'] = "活动不在进行中！";
		die(json_encode($res));
	}
	if( strtotime($activity_info['start_time']) > time() ){
		$res['code'] = 40003;
		$res['msg'] = "活动还未开始！";
		die(json_encode($res));
	}
	if( strtotime($activity_info['end_time']) < time() ){
		$res['code'] = 40004;
		$res['msg'] = "活动已结束！";
		die(json_encode($res));
	}
    //获取用户在该活动内开团次数
	/*$condition = array(
        'cgot.customer_id' => $customer_id,
        'cgot.isvalid' => true,
        'cgot.activitie_id' => $activitie_id,
        'cgot.head_id' => $user_id,
        'cgot.type' => 5
    ); */
    
    /* 检测开团次数 */
    $condition = " cgot.customer_id=".$customer_id." AND cgot.isvalid=true AND cgot.activitie_id=".$activitie_id." AND cgot.head_id=".$user_id." AND cgot.status>0";
    $filed = " count(cgot.id) as opentimes";
    $activity_group_info = $collageActivities->select_front_group2($condition,$filed)['data'][0];
    if( $activity_info['head_times']>0 && $activity_group_info['opentimes']>=$activity_info['head_times']){
        $res['code'] = 40011;
		$res['msg'] = "您的开团次数已超过限制！";
		die(json_encode($res));
    }
}
//检查用户是否有未支付订单
$query_user_unpay = "SELECT count(1) AS unpay FROM collage_crew_order_t as ccot LEFT JOIN stockrecovery_t as st on ccot.batchcode = st.batchcode where ccot.user_id=".$user_id." AND ccot.customer_id=".$customer_id." AND st.recovery_time > now()";
$result_user_unpay = _mysql_query($query_user_unpay) or die('Query_user_number failed:'.mysql_error());
$unpay_count = mysql_fetch_assoc($result_user_unpay)['unpay'];

if( $unpay_count > 0 && $comefrom != 2){	
	$res['code'] = 400010;
	$res['msg']  = "您有待付款拼团订单，请完成付款或取消订单再开团/参团";
	die(json_encode($res));
	
}
	

//检测用户身份等级是否符合活动要求等级
$activity_level = explode('_',$activity_info['user_level']);

$promoter_status = 0;
$promoter_is_consume = 0;
$query = "select id,status,is_consume from promoters where isvalid=true and user_id=".$user_id." and customer_id=".$customer_id."";
$result = _mysql_query($query) or die('W10635 Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $promoter_status     = $row->status;
    $promoter_is_consume = $row->is_consume;
}

switch($promoter_status){
    case 1:
        $promoter_status = 2;
        break;
    default:
        $promoter_status = 1;
        break;
}

switch($promoter_is_consume){
    case 0:
        $promoter_is_consume = $promoter_status;
        break;
    case 1:
        $promoter_is_consume = 3;
        break;
    case 2:
        $promoter_is_consume = 4;
        break;
    case 3:
        $promoter_is_consume = 5;
        break;
    case 4:
        $promoter_is_consume = 6;
        break;
}

if($promoter_is_consume>2 && $promoter_status>1 ){//有股东身份
    $isin = in_array($promoter_is_consume,$activity_level);//判断是否满足后台设置的股东身份
    //不需要再判断后台有无设置粉丝||推广员权限（身份唯一，是股东就不是推广员或粉丝了）
}else{//没股东身份
    $isin = in_array($promoter_status,$activity_level);//判断后台有无设置粉丝||推广员权限
    //不需要再判断后台有无设置粉丝权限（身份唯一，是推广员就不是粉丝了）
}



if( !$isin ){
	$res['code'] = 40005;
	$res['msg'] = "您不符合活动的身份要求！";
	die(json_encode($res));
}

//查询该用户参与该活动的次数
/* $query_user_number = "SELECT count(1) AS ucount FROM collage_crew_order_t WHERE activitie_id=".$activitie_id." AND user_id=".$user_id." AND ( status=2 OR status=3 OR status=5 OR status=7) AND customer_id=".$customer_id;
$result_user_number = _mysql_query($query_user_number) or die('Query_user_number failed:'.mysql_error());
$ucount = mysql_fetch_assoc($result_user_number)['ucount'];

if( $activity_info['number'] > 0 && $activity_info['number'] <= $ucount ){
	$res['code'] = 40006;
	$res['msg'] = "您已达到该活动的参与次数！";
	die(json_encode($res));
} */

//查询活动产品信息
$query_product = "SELECT status,stock,number FROM collage_group_products_t WHERE pid=".$pid." AND activitie_id=".$activitie_id." AND isvalid=true";
$result_product = _mysql_query($query_product) or die('Query_product failed:'.mysql_error());
$product_info = mysql_fetch_assoc($result_product);
if( $product_info['status'] != 1 && $group_id <= 0 ){
	$res['code'] = 40007;
	$res['msg'] = "非有效产品！";
	die(json_encode($res));
}
if( $num > 0 && $num > $product_info['stock'] && $comefrom == 1 ){
	$res['code'] = 40008;
	$res['msg'] = "库存不足！";
	$res['data'][] = $product_info['stock'];
	die(json_encode($res));
}

//查询用户购买该产品是否达到参与次数
/*$query_buy_pnum = "SELECT SUM(ccot.rcount) AS rcount FROM collage_crew_order_t AS ccot INNER JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccot.batchcode=ccopmt.batchcode WHERE ccopmt.pid=".$pid." AND ccot.activitie_id=".$activitie_id." AND ccot.customer_id=".$customer_id." AND ccot.isvalid=true AND (ccot.status=2 OR ccot.status=5 OR ccot.status=7) AND ccot.user_id=".$user_id;
$result_buy_pnum = _mysql_query($query_buy_pnum) or die('Query_buy_pnum failed:'.mysql_error());
$rcount = mysql_fetch_assoc($result_buy_pnum)['rcount'];

$rcount += $num;*/

if( $num > $product_info['number'] && $product_info['number'] != -1 ){
	$res['code'] = 40009;
	$res['msg'] = "您单次购买数量已达上限，此产品单次最多购买".$product_info['number']."件！";
	die(json_encode($res));
}

//参团先检测下单资格
if( $group_id > 0 && $batchcode != '' ){
	$collage_status = $collageActivities->check_qualification($customer_id,$user_id,$group_id,$batchcode);
	
	if( $collage_status['status'] > 1 ){
		$res["status"] = 40010;
		$res["msg"] = $collage_status['msg'];
		die(json_encode($res));
	}
}

echo json_encode($res);
?>