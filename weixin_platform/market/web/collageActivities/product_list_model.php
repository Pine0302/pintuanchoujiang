<?php   
header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../weixinpl/function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

$op = $configutil->splash_new($_POST['op']);//列表类型

$pageNum = 0;//页数
if(!empty($_POST['pageNum'])){
	$pageNum = $configutil->splash_new($_POST['pageNum']);
}
$pagesize = 15;//每页显示数据数
if(!empty($_POST['pagesize'])){
	$pagesize = $configutil->splash_new($_POST['pagesize']);
}
$start = $pageNum * $pagesize;

if($op == 'ordinary' || $op == 'ordinary3' || $op == 'ordinary2')
{
	$filed = "cp.name,cp.orgin_price,cp.default_imgurl,pt.price,at.group_size,pt.success_num,at.coefficient,at.start_time,at.end_time,at.type,ae.type_name,pt.id,pt.pid,CGPT.total_open,CGPT.virtual_open";
	$condition = array(
		'at.customer_id' => $customer_id,
        'ae.customer_id' => $customer_id,
		'at.end_time' => date('Y-m-d H:i:s',time()),
		'ae.isvalid' => true,
        'ae.isshow' => '1',
		'cp.isvalid' => true,
        'cp.isout' => '0',
		'at.isvalid' => true,
		'pt.isvalid' => true,
		'CGPT.isvalid' => true,
		'at.status' => '2',
		'pt.status' => '1',
		'pt.stock' => '0',
		'ORDER' => ' ORDER BY pt.sort asc,pt.id desc,ae.sort asc '
		);
	$type = 0;//搜索类型
	if(!empty($_POST['type'])){
		$type = $configutil->splash_new($_POST['type']);
		$condition['at.type'] = $type;
	}
	$search_keyword = '';//搜索关键字
	if(!empty($_POST['search_keyword'])){
		$search_keyword = $configutil->splash_new($_POST['search_keyword']);
		$condition['cp.name'] = $search_keyword;
	}
	if(!empty($_POST['search_type']) && $_POST['search_type'] != null ){
		$search_type = $configutil->splash_new($_POST['search_type']);
		$type_array  = explode('_', $search_type);
		if($type_array[1] == 1){
			$condition['search_type1'] = $type_array[0];
		}else{
			$type_id_str = substr($type_array[0],0,strlen($type_array[0])-1);
			$condition['search_type2'] = $type_id_str;
		}
	}
	$condition['LIMIT'] = " LIMIT ".$start.",".$pagesize;
	$list = $collageActivities->get_activities_product($condition,$filed);
	foreach ($list['data'] as $one_key => $one) {
		if($one['default_imgurl'] == ''){
			$img_query='SELECT imgurl  from weixin_commonshop_product_imgs where product_id='.$one['pid'].' and customer_id='.$customer_id.' and isvalid = 1 order by id desc ';
			$img_result = _mysql_query($img_query);
			$row = mysql_fetch_assoc($img_result);
			$list['data'][$one_key]['default_imgurl'] = $row['imgurl'];
		}
        $show_open = $one['total_open'] + $one['virtual_open'];
        if($show_open>99){
            $show_open = 99;
        }
        $list['data'][$one_key]['show_open'] = $show_open;
	}
}elseif ($op == 'popularity') {
	$filed = "wcp.name,wcp.orgin_price,wcp.default_imgurl,cgpt.success_num,cgpt.price,cat.group_size,cat.start_time,cgot.endtime,cat.type,cgot.id,cgot.pid,cgot.success_num as cgot_success_num,cgot.join_num,cgpt.total_open,cgpt.virtual_open,ae.type_name";
	$condition=array(
		'cat.customer_id' => $customer_id,
		'ae.customer_id' => $customer_id,
		'cat.end_time' => date('Y-m-d H:i:s',time()),
		'cat.start_time' => date('Y-m-d H:i:s',time()),
		'cat.isvalid' => true,
		'ae.isvalid' => true,
        'ae.isshow' => '1',
		'wcp.isvalid' => true,
		'cgpt.isvalid' => true,
		'cat.status' => '2',
		'cgot.status' => '1',
		'cgpt.stock' => '0',
	);
	$type = 0;//搜索类型
	if(!empty($_POST['type'])){
		$type = $configutil->splash_new($_POST['type']);
			switch($type){
				case 1://最新
					$condition['ORDER'] = " ORDER BY cgot.createtime DESC";
				break;
				case 2://人气
					$condition['ORDER'] = " ORDER BY (cgot.join_num/cgot.success_num) DESC ,cgot.success_num ASC ";
				break;
				case 3://将结束
					$condition['ORDER'] = " ORDER BY cgot.endtime ASC";
				break;
				default:
				
				break;
			}
	}
	$search_keyword = '';//搜索关键字
	if(!empty($_POST['search_keyword'])){
		$search_keyword = $configutil->splash_new($_POST['search_keyword']);
		$condition['wcp.name'] = " LIKE '%".$search_keyword."%'";
	}
	$condition['LIMIT'] = $start.",".$pagesize;
	$list = $collageActivities->get_front_group($condition,$filed);
	foreach ($list['data'] as $one_key => $one) {
		if($one['default_imgurl'] == null ){
			$img_query='SELECT imgurl  from weixin_commonshop_product_imgs where product_id='.$one['pid'].' and customer_id='.$customer_id.' and isvalid = 1 order by id desc ';
			$img_result = _mysql_query($img_query);
			$row = mysql_fetch_assoc($img_result);
			$list['data'][$one_key]['default_imgurl'] = $row['imgurl'];
		}
        $show_open = $one['total_open'] + $one['virtual_open'];
        if($show_open>99){
            $show_open = 99;
        }
        $list['data'][$one_key]['total_open'] = $show_open;
	}
	
}

echo json_encode($list);
?>