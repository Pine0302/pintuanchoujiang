<?php   
header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require_once('../../../weixinpl/function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

$recommend_set = $_POST["recommend_set"];
$type_ids = $configutil->splash_new($_POST["type_ids"]);

// $result = array('code'=>1,'errmsg'=>'','data'=>'');
$result['code'] = 1;
$result['errmsg'] = '';
$result['data'] = [];

if( !empty($recommend_set) and $recommend_set['is_open'] == 1 ){
    if(empty($recommend_set['num'])){
        $recommend_set['num']=1;
    }
	//获取推荐产品
	if( $recommend_set['pattern'] == 1 ){
		//系统推荐
		$filed = " wcp.name AS pname,wcp.default_imgurl,cgpt.price,cgpt.pid ";
		$condition = array(
			'cat.customer_id' => $customer_id,
            'ae.customer_id' => $customer_id,
            'ae.isvalid' => true,
			'cat.status' => 2,
			'cat.isvalid' => true,
			'cgot.status' => 1,
			'cgpt.status' => 1,
			'cgpt.isvalid' => true,
			'limit' => " LIMIT ".$recommend_set['num']
		);
		
		if( !empty($recommend_set['type']) ){
			$condition['cat.type'] = explode('_',$recommend_set['type']);
		}
		if( $recommend_set['style'] == 1 ){
			$condition['wcp.type_ids'] = explode(',',$type_ids);
		} else {
			$condition['cat.start_time_cp'] = 1;
		}
		if( $recommend_set['sort'] == 1 ){
			$condition['order_by'] = " ORDER BY RAND(), cat.createtime DESC ";
		} else {
			$condition['order_by2'] = " ORDER BY RAND(), gcount DESC ";
			$filed .= ",count(cgot.id) AS gcount ";
		}
		$product = $collageActivities->get_recommendation_product_system($condition,$filed);
		// var_dump($product);
		if( $product['code'] == 1 ){
			$result['data'] = $product['data'];
		} else {
			$result['code'] == 40006;
			$result['errmsg'] == $product['errmsg'];
		}
	} else {
		//自定义
		$filed2 = " cgpt.pid,cgpt.price,wcp.name AS pname,wcp.default_imgurl ";
		$condition2 = array(
			'cgpt.isvalid'=>true,
			'cgpt.status'=>1,
			'cat.isvalid'=>true,
			'wcp.isvalid'=>true,
			'carpt.recommendation_id'=>$recommend_set['id'],
			'carpt.is_out'=>true,
			'carpt.isvalid'=>true,
			'limit'=>' limit 3 ',
			'order_by'=>' ORDER BY RAND() '
		);
		$product = $collageActivities->get_recommendation_product($condition2,$filed2);
		// var_dump($product);
		if( $product['code'] == 1 ){
			$result['data'] = $product['data'];
		} else {
			$result['code'] == 40006;
			$result['errmsg'] == $product['errmsg'];
		}
	}
} else {
	$result['code'] == 40006;
	$result['errmsg'] == '商家没开启团推荐产品';
}
echo json_encode($product['data']);
?>