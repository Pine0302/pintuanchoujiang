<?php   
header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //µ¼ÈëÎÄ¼þ,»ñÈ¡customer_id_en[¼ÓÃÜµÄcustomer_id]ÒÔ¼°customer_id[ÒÑ½âÃÜ]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../weixinpl/function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

$user_id = -1;
if(!empty($_POST['user_id'])){
	$user_id = $configutil->splash_new($_POST['user_id']);
}

$start = $pageNum * $pagesize;
$filed = "cp.name AS pname,cp.default_imgurl,got.price,got.success_num,wu.weixin_name,wu.name AS uname,got.status,cot.totalprice,cot.id,cot.batchcode,cot.is_refund,cot.status as cstatus,cot.is_head,got.join_num,got.endtime,got.pid,got.type,got.is_win,got.id as group_id,ae.type_name,cso.recovery_time as is_paytime";
$condition = array(
	'got.customer_id' => $customer_id,
    'ae.customer_id' => $customer_id,
    'ae.isvalid' => true,
	'cot.isvalid' => true,
	'status' => '-1',
	'paystyle' => '-1',
	'wco.status_1' => '-1',
	'cot.user_id' => $user_id,
	'cot.is_head' => 2
);
$type = 0;//ËÑË÷ÀàÐÍ
if(!empty($_POST['type'])){
	$type = $configutil->splash_new($_POST['type']);
	if( 1 == $type ){
		$condition['cot.is_head'] = 1;
	}
}
$start_time = '';//ËÑË÷¿ªÊ¼Ê±¼ä
if(!empty($_POST['start_time'])){
	$start_time = $configutil->splash_new($_POST['start_time']);
	$condition['start_time'] = $start_time;
}
$end_time = '';//ËÑË÷½áÊø
if(!empty($_POST['end_time'])){
	$end_time = $configutil->splash_new($_POST['end_time']);
	$end_time = date('Y-m-d',strtotime("$end_time + 1 day"));
	$condition['end_time'] = $end_time;
}
$status = 0;//ËÑË÷×´Ì¬
if(!empty($_POST['status'])){
	$status = $configutil->splash_new($_POST['status']);
	if( $status > 0 ){
		if( 3 == $status ){//
			$condition['got.is_win'] = 1;
		}elseif( 2 == $status ){
			$condition['got.status'] = $status;
			$condition['cot.is_refund'] = 0;
			$condition['cot.status2'] = 6;
		}elseif(  5 == $status  ){  //未支付
			
			$condition['cso.recovery_time']  = '';
			
		}elseif(  4 == $status  ){
			$condition['cot.status_in'] = '5,7,8';
			$condition['status2'] = '(3,4,5,6)';
		}else{
			if( $status == 1 ){
				$condition['cot.status1'] = '6';
			}
			$condition['got.status'] = $status;
		}
	}
}

$pageNum = 0;//Ò³Êý
if(!empty($_POST['pageNum'])){
	$pageNum = $configutil->splash_new($_POST['pageNum']);
}
$pagesize = 15;//Ã¿Ò³ÏÔÊ¾Êý¾ÝÊý
if(!empty($_POST['pagesize'])){
	$pagesize = $configutil->splash_new($_POST['pagesize']);
}
$start = $pageNum * $pagesize;
$condition['LIMIT'] = $start.",".$pagesize;
$list = $collageActivities->get_user_crew_order($condition,$filed);

foreach($list['data'] as $key => $val){
    $coefficient = -1;
    $query1 = "SELECT coefficient FROM collage_bbt_order_extend WHERE batchcode='".$val['batchcode']."'";
    $result1 = _mysql_query($query1) or die('Query1 failed:'.mysql_error());
    $coefficient = mysql_fetch_assoc($result1)['coefficient'];  
    $list['data'][$key]['coefficient'] = $coefficient;
    
    if(empty($val['default_imgurl'])){
        $query6 = "select imgurl from weixin_commonshop_product_imgs where isvalid=true and customer_id=".$customer_id." and product_id=".$val['pid']." limit 1";

        $result6 = _mysql_query($query6) or die('query failed6'.mysql_error());
        while($row6 = mysql_fetch_object($result6)){
            $product_default_imgurl = $row6->imgurl;	//商品封面图
        }
        $list['data'][$key]['default_imgurl'] = $product_default_imgurl;
    }    
}

echo json_encode($list);
?>