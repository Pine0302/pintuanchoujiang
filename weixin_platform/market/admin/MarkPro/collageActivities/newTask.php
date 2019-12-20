<?php
/*
 * 版本8.3.0 新增需求
 * 功能：a.拼团超过开团天数限制自动退款 
 * 	     b.抽奖团成功进行抽奖，未成功视为拼团失败退款		
 */
/*	header("Content-type: text/html; charset=utf-8");
	require('../../../../weixinpl/config.php');
	require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
	require('../../../../weixinpl/proxy_info.php');
	require_once('../../../../weixinpl/function_model/collageActivities.php');
	require_once('../../../../weixinpl/common/utility_shop.php');
	require_once('../../../../weixinpl/common/utility.php');
	require_once('../../../../market/admin/MarkPro/collageActivities/newTask.php');*/
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/common/utility_shop.php");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/currency.php");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/moneybag.php");
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/healthpay/healthpay_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/healthpay/healthpay_api.class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/healthpay/healthpay_submit.class.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/function_model/collageActivities.php');


require_once($_SERVER['DOCUMENT_ROOT'].'/mshop/admin/model/activity.php');
//----执行开始
$file_path = 'run_new_collageTask_log.txt';
$error_file_path = 'run_new_collageTask_errorlog.txt';

/*$shopTask = new CollageTaskClass();
//$shopTask->run_shop();
//$shopTask->stop_collage_activities_update(475,0);
$shopTask->failed_collage_activities_return();*/
//----执行结束
	class CollageTaskClass
{	
	private $db;

    public function __construct(){
        $this->db = DB::getInstance();
    }
    /*
     * 执行拼团新增定时任务
     */
	public function run_shop(){
		//拼团失败立即退款
		$this->failed_collage_activities_return();
	}

	/*
	 * 手动结束拼团活动改变状态
	 * @param aid 活动ID ; type 0 其他团 2 抽奖团; pid 0 活动结束 大于0 产品单独结束 
	 **/
	public function stop_collage_activities_update($aid = 0 , $type = 0 ,$pid = 0){

		$shopMessage_Utlity = new shopMessage_Utlity();

		$currtime = time();	//当时时间戳

		_mysql_query('SET AUTOCOMMIT=0');
		_mysql_query('BEGIN');
		
		//注：改变状态防止15分钟的定时任务再次改变状态
		//更改关联活动的产品状态

		if( $type != 2 ){
			if( $pid == 0 ){
				//更改关联活动状态为已结束
				$query_act_up = "UPDATE collage_activities_t SET status=4 WHERE id = '".$aid."'";
				_mysql_query($query_act_up) or die('Query_act_up failed:'.mysql_error());

				//更改关联活动的产品状态
				$query_product = "UPDATE collage_group_products_t SET status=4 WHERE activitie_id = '".$aid."' and status not in(2,4) ";

				_mysql_query($query_product) or die('Query_product failed:'.mysql_error());	
			}
			else{
				//更改关联活动的产品状态
				$query_product = "UPDATE collage_group_products_t SET status=4 WHERE activitie_id = '".$aid."' and pid = '".$pid."' and status not in(2,4) ";

				_mysql_query($query_product) or die('Query_product failed:'.mysql_error());	
			} 
		}

		else {
			//抽奖团
			//更改成功团数大于0的产品状态为待抽奖
			$query_product = "UPDATE collage_group_products_t SET status=3 WHERE activitie_id = '".$aid."' AND total_success>0 and status not in(2,4) ";
			if( $pid > 0 ){
				$query_product .= " AND pid =  '".$pid."'";
			}
			_mysql_query($query_product) or die('Query_product failed:'.mysql_error());

			//更改成功团数等于0的产品状态为已结束
			$query_product2 = "UPDATE collage_group_products_t SET status=4 WHERE activitie_id = '".$aid."' AND total_success=0 and status not in(2,4) ";
			if( $pid > 0 ){
				$query_product2 .= " AND pid =  '".$pid."'";
			}
			_mysql_query($query_product2) or die('Query_product2 failed:'.mysql_error());

		}

		//获取失败团的id
		$group_id_arr = [];	      //非免单团进行中或团长未支付的团id数组
		$group_free_id_arr=[];	  //免单团进行中或团长未支付的团id数组
		$group_in_arr = [];		  //进行中的非免单团信息数组
		$group_free_in_arr = [];  //进行中的免单团信息数组
		$query_group = "SELECT id,pid,status,activitie_id,head_id,type FROM collage_group_order_t WHERE isvalid=true  AND success_num>join_num and activitie_id = '".$aid."' and status=1";
		if( $pid > 0 ){
			$query_group .= " AND pid =  '".$pid."'";
		}
		$result_group = _mysql_query($query_group) or die('Query_group failed:'.mysql_error());
		while( $row_group = mysql_fetch_assoc($result_group) ){
			if( $row_group['type'] != 6){
				$group_id_arr[] = $row_group['id'];
				if( $row_group['status'] == 1 ){
					$group_in_arr[] = array(
						'activitie_id' => $row_group['activitie_id'],
						'pid' => $row_group['pid'],
						'head_id' => $row_group['head_id']
					);
				}
			}else{
				$group_free_id_arr[] = $row_group['id'];
				if( $row_group['status'] == 1 ){
					$group_free_in_arr[] = array(
						'activitie_id' => $row_group['activitie_id'],
						'pid' => $row_group['pid'],
						'head_id' => $row_group['head_id']
					);
				}
			}

		}
		$group_id_str 	   = implode(',',$group_id_arr);
		$group_free_id_str = implode(',',$group_free_id_arr);

		$pay_bat_str       = '';//已支付的订单字符串
		//非免单团操作
		if( $group_id_str != '' ){
			//获取已支付的订单的团类型、产品名、用户标识，用于推送消息
			$pay_bat_info = [];
			$query_pay_bat = "SELECT ccot.customer_id,
									 ccot.batchcode,
										cgot.type,
										ccopmt.pname,
										wu.weixin_fromuser
								FROM collage_crew_order_t AS ccot
								LEFT JOIN collage_group_order_t AS cgot ON ccot.group_id=cgot.id
								LEFT JOIN weixin_users AS wu ON ccot.user_id=wu.id
								LEFT JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccot.batchcode=ccopmt.batchcode
								WHERE ccot.group_id IN (".$group_id_str.") AND ccot.status=2 AND ccot.isvalid=true";
			$result_pay_bat = _mysql_query($query_pay_bat) or die('Query_pay_bat failed:'.mysql_error());
			while( $row_pay_bat = mysql_fetch_object($result_pay_bat) ){
				$pay_bat_info[] = array(
					'customer_id' => $row_pay_bat->customer_id,
					'type' => $row_pay_bat->type,
					'pname' => $row_pay_bat->pname,
					'weixin_fromuser' => $row_pay_bat->weixin_fromuser
				);
				$pay_bat_str .= $row_pay_bat->batchcode.',';
			}

			//更改团状态为拼团失败
			$query_group_up = "UPDATE collage_group_order_t SET status=2,refund_status=1 WHERE id IN (".$group_id_str.")";
			_mysql_query($query_group_up) or die('Query_group_up failed:'.mysql_error());

			//更改已支付订单状态为待退款
			$query_crew_order_up = "UPDATE collage_crew_order_t SET status=3 WHERE group_id IN (".$group_id_str.") AND status=2 AND isvalid=true";
			_mysql_query($query_crew_order_up) or die('Query_crew_order_up failed 1:'.mysql_error());

			//更新团产品和用户的统计信息
			foreach( $group_in_arr as $k => $v ){
				$query_group_product = "UPDATE collage_group_products_t SET total_fail=total_fail+1,total_conduct=total_conduct-1 WHERE activitie_id=".$v['activitie_id']." AND pid=".$v['pid']." and status not in(2,4) ";
				_mysql_query($query_group_product) or die('Query_group_product failed:'.mysql_error());

				$query_user_mes = " UPDATE collage_activities_user_mes_t SET total_fail=total_fail+1 WHERE user_id=".$v['head_id']." AND isvalid=true";
				_mysql_query($query_user_mes) or die('Query_user_mes failed:'.mysql_error());
			}

			//推送拼团失败消息
			foreach( $pay_bat_info as $k => $v ){
				switch( $v['type'] ){
					case 1:
						$type_str = " 普通团";
					break;
					case 2:
						$type_str = " 抽奖团";
					break;
					case 3:
						$type_str = " 秒杀团";
					break;
					case 4:
						$type_str = " 超级团";
					break;
				}

				$content = "亲，您参加的 ".$v['pname'].$type_str." 拼团失败\r\n".
							"状态：【拼团失败】\n".
							"时间：".date( "Y-m-d H:i:s")."";
				$shopMessage_Utlity->SendMessage($content,$v['weixin_fromuser'],$v['customer_id']);
			}

			//获取未支付的订单号
			$batchcode_str = '';	//未支付的订单号字符串
			$query_bat = "SELECT batchcode FROM collage_crew_order_t WHERE status=1 AND group_id IN (".$group_id_str.") AND isvalid=true";
			$result_bat = _mysql_query($query_bat) or die('Query_bat failed:'.mysql_error());
			while( $row_bat = mysql_fetch_assoc($result_bat) ){
				$batchcode_str .= $row_bat['batchcode'].',';
			}

			$batchcode_str = substr($batchcode_str,0,-1);

			if( $batchcode_str != '' ){
				//更改未支付的订单状态为拼团失败
				/*$query_crew_order_up = "UPDATE collage_crew_order_t SET status=4 WHERE batchcode IN (".$batchcode_str.")";
				_mysql_query($query_crew_order_up) or die('Query_crew_order_up failed 2:'.mysql_error());*/

				//更改未支付的订单状态为已取消
				$query_order_up = "UPDATE weixin_commonshop_orders SET status=-1 WHERE batchcode IN (".$batchcode_str.")";
				_mysql_query($query_order_up) or die('Query_order_up failed:'.mysql_error());

                /*$log_name=$_SERVER['DOCUMENT_ROOT']."/weixinpl/log/errOrder_".date("Ymd").".log";
                $log="250 batchcode: ".$batchcode_str."\r\n时间:".date("Y-m-d H:i:s")."\r\n\r\n";
                file_put_contents($log_name,$log,FILE_APPEND);*/

				$query_orderp_up = "UPDATE weixin_commonshop_order_prices SET status=-1 WHERE batchcode IN (".$batchcode_str.")";
				_mysql_query($query_orderp_up) or die('Query_orderp_up failed:'.mysql_error());
			}

		}
		//免单团操作
		if($group_free_id_str != ''){
			//更改团状态为成团失败
			$query_free_group_up = "UPDATE collage_group_order_t SET status=6 WHERE id IN (".$group_free_id_str.")";
			_mysql_query($query_free_group_up) or die('Query_group_up failed:'.mysql_error());
			//更改团员状态为成团失败
			$query_free_crew_order_up = "UPDATE collage_crew_order_t SET status=8 WHERE group_id IN (".$group_free_id_str.") AND status=2 AND isvalid=true";
			_mysql_query($query_free_crew_order_up) or die('Query_crew_order_up failed 1:'.mysql_error());

			//更新团产品和用户的统计信息
			foreach( $group_free_in_arr as $k => $v ){
				$query_free_group_product = "UPDATE collage_group_products_t SET total_success=total_success+1,total_conduct=total_conduct-1 WHERE activitie_id=".$v['activitie_id']." AND pid=".$v['pid']." and status not in(2,4) ";
				_mysql_query($query_free_group_product) or die('Query_group_product failed:'.mysql_error());
			}
			//获取未支付的订单号
			$batchcode_str_free = '';	//未支付的订单号字符串
			$query_bat = "SELECT batchcode FROM collage_crew_order_t WHERE status=1 AND group_id IN (".$group_free_id_str.") AND isvalid=true";
			$result_bat = _mysql_query($query_bat) or die('Query_bat failed:'.mysql_error());
			while( $row_bat = mysql_fetch_assoc($result_bat) ){
				$batchcode_str_free .= $row_bat['batchcode'].',';
			}

			$batchcode_str_free = substr($batchcode_str_free,0,-1);

			if( $batchcode_str_free != '' ){

				//更改未支付的订单状态为已取消
				$query_order_up = "UPDATE weixin_commonshop_orders SET status=-1 WHERE batchcode IN (".$batchcode_str_free.")";
				_mysql_query($query_order_up) or die('Query_order_up failed:'.mysql_error());

                /*$log_name=$_SERVER['DOCUMENT_ROOT']."/weixinpl/log/errOrder_".date("Ymd").".log";
                $log="289 batchcode: ".$batchcode_str_free."\r\n时间:".date("Y-m-d H:i:s")."\r\n\r\n";
                file_put_contents($log_name,$log,FILE_APPEND);*/

				$query_orderp_up = "UPDATE weixin_commonshop_order_prices SET status=-1 WHERE batchcode IN (".$batchcode_str_free.")";
				_mysql_query($query_orderp_up) or die('Query_orderp_up failed:'.mysql_error());
			}

			$free_pay_bat_info = [];
			$free_query_pay_bat = "SELECT ccot.customer_id,
										ccot.batchcode,
										cgot.type,
										ccopmt.pname,
										wu.weixin_fromuser
								FROM collage_crew_order_t AS ccot
								LEFT JOIN collage_group_order_t AS cgot ON ccot.group_id=cgot.id
								LEFT JOIN weixin_users AS wu ON ccot.user_id=wu.id
								LEFT JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccot.batchcode=ccopmt.batchcode
								WHERE ccot.group_id IN (".$group_free_id_str.") AND ccot.status=8 AND ccot.isvalid=true AND ccot.is_head=1";
			$free_result_pay_bat = _mysql_query($free_query_pay_bat) or die('Query_pay_bat failed:'.mysql_error());
			while( $free_row_pay_bat = mysql_fetch_object($free_result_pay_bat) ){
				$free_pay_bat_info[] = array(
					'customer_id' => $free_row_pay_bat->customer_id,
					'type' => $free_row_pay_bat->type,
					'pname' => $free_row_pay_bat->pname,
					'weixin_fromuser' => $free_row_pay_bat->weixin_fromuser
				);
				$pay_bat_str .= $free_row_pay_bat->batchcode.',';
			}
			//推送拼团失败消息
			foreach( $free_pay_bat_info as $k => $v ){

				$content = "亲，您发起的 ".$v['pname']."免单团 成团失败\r\n".
							"状态：【成团失败】\n".
							"时间：".date( "Y-m-d H:i:s")."";
				$shopMessage_Utlity->SendMessage($content,$v['weixin_fromuser'],$v['customer_id']);
			}

		}
		_mysql_query("COMMIT");
		_mysql_query('SET AUTOCOMMIT=1');
		if( $pay_bat_str != '' ){
			//加库存
			$this->fight_groups_stockrecovery($pay_bat_str);
			//自动退款
			$this->failed_collage_activities_return($pay_bat_str);
		}

	}

	/*库存回收开始*/
	public function fight_groups_stockrecovery($batchcode_str = ''){

		_mysql_query('SET AUTOCOMMIT=0');
		_mysql_query('BEGIN');

/*		$batchcode_str = '';	//拼团失败的订单号
		//获取失败团的订单号
		$query_bat = "SELECT batchcode FROM collage_crew_order_t WHERE status=4";
		$result_bat = _mysql_query($query_bat) or die('Query_bat failed:'.mysql_error());
		while( $row_bat = mysql_fetch_object($result_bat) ){
			$batchcode_str .= $row_bat->batchcode .',';
		}
*/
		$batchcode_str = substr($batchcode_str,0,-1);

		$condition = "";
		if( $batchcode_str != '' ){
			$condition .= " batchcode IN (".$batchcode_str.")";

			$del_id 	 = -1;	//要删除的id
			$pid 		 = -1;	//商品id
			$stock 		 = 0;	//要加的库存
			$stringtime  = date("Y-m-d H:i:s", time());	//当前时间
			$sql = "SELECT id,pid,stock,batchcode FROM stockrecovery_t WHERE ".$condition." AND is_collageActivities=1";
			$result = _mysql_query($sql) or die("stockrecovery Query error : ".mysql_error());
			while( $row = mysql_fetch_object($result) ){
				$del_id 	 = $row->id ;
				$pid 		 = $row->pid ;
				$stock 		 = $row->stock ;
				$batchcode 	 = $row->batchcode ;

				//查询活动id
				$query_act = "SELECT activitie_id FROM collage_crew_order_t WHERE batchcode='".$batchcode."'";
				$result_act = _mysql_query($query_act) or die('Query_act failed:'.mysql_error());
				$activitie_id = mysql_fetch_assoc($result_act)['activitie_id'];

				if($activitie_id == false) continue;

				//返库存
				$query_add = "UPDATE collage_group_products_t SET stock=stock+".$stock." WHERE activitie_id=".$activitie_id." AND isvalid=true AND pid=".$pid." and status not in(2,4) ";
				_mysql_query($query_add) or die('Query_add failed:'.mysql_error());

				//删除库存回收表记录
				$query = "DELETE FROM stockrecovery_t WHERE id=".$del_id;
				_mysql_query($query) or die('Delete failed: ' . mysql_error());
			}
		}

		_mysql_query("COMMIT");
		_mysql_query('SET AUTOCOMMIT=1');
	}

	/*
	 * 待退款拼团订单批量退款
	 * @param batchcode_str 订单字符串 空：定时任务 ；不为空：指定订单; limit 限制条数
	 */
	public function failed_collage_activities_return($batchcode_str = '',$limit = 0){
		$mysqlmessage = new mysql_queryclass();
		//插入日志表
		if($batchcode_str == ''){
			$mysqlmessage->write_run_script_log('failed_collage_activities_return','','','====执行开始====');
		}
		$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
		mysql_select_db(DB_NAME) or die('Could not select database');
		_mysql_query("SET NAMES UTF8");
		$collageActivities = new collageActivities();
		//$shopMessage_Utlity = new shopMessage_Utlity();


		$currtime = date('Y-m-d H:i:s',time());	//当时时间戳
		//$customer_id_en = passport_encrypt((string)$this->customer_id); 
		//查询拼团失败待退款的订单
		$condition = " ccot.isvalid=true  AND ccot.status=3 AND ccot.is_refund=0 ";
		$batchcode_str = substr($batchcode_str,0,-1);

		if( $batchcode_str != '' ){
			$condition .= ' and ccot.batchcode in ('.$batchcode_str.') ';
		}
		$order_by =  ' order by ccot.paytime desc ';
		$condition .= $order_by;
		if( $limit > 0 && $limit < 50){
			$condition .= ' limit 0,'.$limit;
		}else{
			$condition .= ' limit 0,50';//一次性执行最大条数
		}
		$filed = " ccot.batchcode,ccot.customer_id,ccot.user_id";
		$info = $collageActivities->get_crew_order($condition,$filed)['batchcode'];	
		$order_re = new order_refund();
		$errmsg = '';
		foreach ($info as $k => $v) {
/*			$data['batchcode'] = $v['batchcode'];
			$data['refund_way'] = 1;
			$post_data = http_build_query($data);
			$url = Protocol.$_SERVER["HTTP_HOST"].'/weixinpl/back_newshops/MarkPro/collageActivities/order_refund.php?customer_id='.$v['customer_id'];		//调用拼团微信退款

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);					// 要访问的地址
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($ch, CURLOPT_POST, 1 );
			curl_setopt($ch, CURLOPT_HEADER, 0);					// 显示返回的Header区域内容
			curl_setopt($ch, CURLOPT_NOBODY, 0);					//只取body头
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);		// 对认证证书来源的检查
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);		// 从证书中检查SSL加密算法是否存在
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');	// 模拟用户使用的浏览器
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);			// 使用自动跳转
			curl_setopt($ch, CURLOPT_AUTOREFERER, 1);				// 自动设置Referer
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);		// Post提交的数据包
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);					// 设置超时限制防止死循环
			$curl_error = curl_error($ch);
			$json = curl_exec($ch);

			curl_close($ch);

			$jsons = json_decode($json,true);*/
			$jsons = $order_re -> refund($v['batchcode'],$v['customer_id']);
			//var_dump($jsons);die();
			if($jsons['status'] != 1){
				$errmsg .= '订单号:'.$v['batchcode'].',退款失败,原因:'.$jsons['msg'].'; ';
			}
			//sleep(0.1);
		} 
     	if(empty($errmsg)){
     		$errmsg = '拼团批量退款成功!';
            //echo ("CollageActivities Return error: ".$e->getMessage());
     	}
	    //echo ("CollageActivities Return error: ".$errmsg);
	    //插入日志表
	    if($batchcode_str == ''){
			$mysqlmessage->write_run_script_log('failed_collage_activities_return','','','====执行结束====');
		}	
	    mysql_close($link);
	    return array('errcode'=>0,'errmsg'=>'拼团批量退款成功');
	}
}
class mysql_queryclass{

	/*插入运行日志 开始*/
	public function write_run_script_log($class_function,$key_parameter,$error,$remark){
	//函数说明：用于记录自动运行脚本运行结果
	//@class_function	：日志表记录的：类名+方法名
	//@key_parameter	：日志表记录的：关键参数
	//@result			：日志表记录的：运行的结果：0失败1成功
	//@error			：日志表记录的：错误信息
	//@remark			：日志表记录的：备注

		$result 	= 1;
		$e_error 	= '';
		$r_remark 	= '';
		if(!empty($error)){
			$result 	= 0;
			$e_error 	= $error;
			$e_error 	= mysql_real_escape_string($error);	//转义
		}
		if(!empty($remark)){
			$r_remark = $remark;
		}
		$sql = "insert into run_script_log(class_function,isvalid,createtime,key_parameter,result,error,remark)
		values('".$class_function."',1,now(),'".$key_parameter."',".$result.",'".$e_error."','".$r_remark."')";
		//echo $sql;
		_mysql_query($sql);
	}
	/*插入运行日志 结束*/

	/*mysql执行错误写入日志 开始*/
	public function mysql_query_new($query){
	//函数说明：主要把mysql执行的错误信息记录在日志表里面
		global $class_function,$key_parameter;
		//echo $query.'<br>';
		$remark = 'sql错误！';
		$err_tmp='';
		$result = _mysql_query($query) or $err_tmp = mysql_error();
		if($err_tmp){
			$key_parameter .= '_'.$query;
			$this->write_run_script_log($class_function,$key_parameter,$err_tmp,$remark);
			die('mysql_query_new Query failed: ' . $err_tmp);
		}else{
			return $result;	//返回结果
		}
	}
	/*mysql执行错误写入日志 开始*/
}

class check_run_day{
	//查询当日脚本运行日志，防止重复执行
	public function check_time(){
		global $file_path,$error_file_path;
		$file_log_class = new file_log();
		$last_time = $file_log_class->read_last_filelog($file_path);
		$last_time = date('Y-m-d',strtotime($last_time));
		$now_time = date('Y-m-d');
		if($now_time == $last_time){
			$file_log_class->write_filelog($error_file_path,date('Y-m-d H:i:s').':重复执行脚本！'); //写入错误日志
			exit(); //停止运行脚本
		}
	}
}



class order_refund{
	public function refund($batchcode = '',$customer_id = 0){
		$shopMessage_Utlity = new shopMessage_Utlity();

		$collageActivities = new collageActivities($customer_id);//自定义类

		//$batchcode = $configutil->splash_new($_POST['batchcode']);
		$refund_way = 1;
		//die($batchcode);
		$customer_id_en = passport_encrypt((string)$customer_id); 
		$refund_result = array(
			'status' => 1,
			'batchcode' => $batchcode,
			'msg' => '退款成功'
		);
		_mysql_query('SET AUTOCOMMIT = 0');
		_mysql_query('BEGIN');

		$order_status = 0;
		$group_id = 0;
		//拼团订单自动退款开关
		$auto_refund_open = 0;
		$collage_setting = "SELECT auto_refund_open FROM ".WSY_MARK.".collage_setting WHERE customer_id='".$customer_id."' and isvalid = true";
		$result_collage_setting = _mysql_query($collage_setting) or die('Query_collage_setting failed:'.mysql_error());
		while ($row_collage_setting = mysql_fetch_object($result_collage_setting)) {
		    $auto_refund_open = $row_collage_setting->auto_refund_open;
		}
		if($auto_refund_open == 0){
			$refund_result['status'] = 50006;
			$refund_result['msg'] = '拼团自动退款开关关闭';
			return $refund_result;
		}

		//查询订单状态
		$query_collage_order = "SELECT status,group_id FROM collage_crew_order_t WHERE batchcode='".$batchcode."'";
		$result_collage_order = _mysql_query($query_collage_order) or die('Query_collage_order failed:'.mysql_error());
		while ($row_collage_order = mysql_fetch_object($result_collage_order)) {
		    $order_status = $row_collage_order->status;
		    $group_id = $row_collage_order->group_id;
		}
		//$order_status = mysql_fetch_assoc($result_collage_order)['status'];
		//$group_id = mysql_fetch_assoc($result_collage_order)['group_id'];

		//die("order_status: ".$order_status);
		//如果订单状态非待退款状态，则不执行下面代码
		if( $order_status != 3 ){
			$refund_result['status'] = 40006;
			$refund_result['msg'] = '订单状态异常';
			return $refund_result;
		}
		$price = 0;
		$user_id = 0;
		$paystyle = 0;
		$pay_currency = 0;
		$query = "SELECT user_id,price,paystyle,pay_currency FROM weixin_commonshop_order_prices WHERE batchcode='".$batchcode."' AND isvalid=true";
		$result = _mysql_query($query) or die('Query failed:'.mysql_error());
		while( $row = mysql_fetch_object($result) ){
			$price 			= $row->price;
			$user_id 		= $row->user_id;
			$paystyle 		= $row->paystyle;
			$pay_currency 	= empty($row->pay_currency)?0:$row->pay_currency;
		}
		
		//查询是否有改价
		$query_change_price = "SELECT totalprice FROM weixin_commonshop_changeprices WHERE batchcode='".$batchcode."' AND isvalid=true";
		$result_change_price = _mysql_query($query_change_price) or die('Query_change_price failed:'.mysql_error());
		while( $row_change_price = mysql_fetch_object($result_change_price) ){
			$price = $row_change_price->totalprice;
		}

		$sendMessage_content = [];

		if( $refund_way == 1 ){	//原路返回
			if( $pay_currency > 0 ){	//如果有使用购物币则退回购物币
				$Currency = new Currency();
				$remark = "拼团失败退购物币";
				$refund_result = $Currency->update_currency($user_id,$customer_id,$pay_currency,1,$batchcode,$remark,15,1);

				$query_currency_title = "SELECT custom FROM weixin_commonshop_currency WHERE customer_id=".$customer_id." AND isvalid=true";
				$result_currency_title = _mysql_query($query_currency_title) or die('Query_currency_title failed:'.mysql_error());
				$currency_title = mysql_fetch_assoc($result_currency_title)['custom'];

				$sendMessage_content[] = "亲，您的".$currency_title." +".$pay_currency."\r\n".
										"来源：【拼团失败】\n".
										"状态：【退款到帐】\n".
										"时间：".date( "Y-m-d H:i:s")."";
			}
			$totalprice = 0;
			$totalprice = $price - $pay_currency;	//扣除购物币后实际支付金额
			//return array('price'=>$price,'user_id'=>$user_id,'paystyle'=>$paystyle,'pay_currency'=>$pay_currenc,'totalprice'=>$totalprice,'cs'=>$customer_id_en);
			if( $totalprice > 0 ){	//实际支付金额大于0，则按支付方式原路返回
				switch( $paystyle ){
					case '零钱支付':
						$MoneyBag = new MoneyBag();
						$remark = "拼团失败退零钱";
						$refund_result = $MoneyBag->update_moneybag($customer_id,$user_id,$totalprice,$batchcode,$remark,1,14,0);

						$sendMessage_content[] = "亲，您的零钱钱包 +".$totalprice."元\r\n".
												"来源：【拼团失败】\n".
												"状态：【退款到帐】\n".
												"时间：".date( "Y-m-d H:i:s")."";
					break;
					case '会员卡余额支付':
						$query_card_id = "SELECT card_member_id FROM weixin_commonshop_orders WHERE batchcode='".$batchcode."' AND isvalid=true";
						$result_card_id = _mysql_query($query_card_id) or die('Query_card_id failed:'.mysql_error());
						$card_member_id = mysql_fetch_assoc($result_card_id)['card_member_id'];

						if( $card_member_id > 0 ){
							//查询会员卡剩余金额
							$query_card_money = "SELECT remain_consume FROM weixin_card_member_consumes WHERE isvalid=true AND card_member_id=".$card_member_id." LIMIT 1";
							$result_card_money = _mysql_query($query_card_money) or die('Query_card_money failed:'.mysql_error());
							$remain_consume = mysql_fetch_assoc($result_card_money)['remain_consume'];

							$after_consume = $remain_consume + $totalprice;

							//更新消费金额和剩余金额
							$query_card_money_up = "UPDATE weixin_card_member_consumes SET remain_consume=remain_consume+".$totalprice.",total_consume=total_consume-".$totalprice." WHERE card_member_id=".$card_member_id." AND isvalid=true";
							_mysql_query($query_card_money_up) or die('Query_card_money_up failed:'.mysql_error());

							//插日志
							$remark = '拼团失败，退回'.$totalprice.'到会员卡余额';
							$query_card_log_ins = "INSERT INTO weixin_card_recharge_records (
															card_member_id,
															before_cost,
															after_cost,
															cost,
															isvalid,
															createtime,
															remark,
															new_record
														) VALUES (
															".$card_member_id.",
															".$remain_consume.",
															".$after_consume.",
															".$totalprice.",
															true,
															now(),
															'".$remark."',
															1
														)";
							_mysql_query($query_card_log_ins) or die('Query_card_log_ins failed:'.mysql_error());

							$sendMessage_content[] = "亲，您的会员卡余额 +".$totalprice."元\r\n".
												"来源：【拼团失败】\n".
												"状态：【退款到帐】\n".
												"时间：".date( "Y-m-d H:i:s")."";
						} else {
							$refund_result['status'] = 40006;
							$refund_result['msg'] = '会员卡不存在！';
						}

					break;
					case '微信支付':
						//查询微信交易订单号和总支付金额
						$query_transaction_id = "SELECT wwn.transaction_id,
														wwn.total_fee
													FROM weixin_weipay_notifys AS wwn
													INNER JOIN weixin_commonshop_orders AS wco ON wwn.out_trade_no=wco.pay_batchcode
													WHERE wco.batchcode='".$batchcode."' AND wco.isvalid=true AND wco.customer_id=".$customer_id;
						$result_transaction_id = _mysql_query($query_transaction_id) or die('Query_transaction_id failed:'.mysql_error());
						$weipay_notify = mysql_fetch_assoc($result_transaction_id);
						$transaction_id = $weipay_notify['transaction_id'];
						$total_fee = $weipay_notify['total_fee'] / 100;

						if( $transaction_id != '' ){
							//发送的数据
							$post_data	= array(
								'batchcode' => $batchcode,
								'transaction_id' => $transaction_id,
								'total_fee' => $total_fee,
								'refund_fee' => $totalprice
							);

							$post_data = http_build_query($post_data);
							$url = Protocol.$_SERVER["HTTP_HOST"].'/weixinpl/common_shop/jiushop/refund_collage.php?customer_id='.$customer_id_en;		//调用拼团微信退款

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $url);					// 要访问的地址
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_POST, 1 );
							curl_setopt($ch, CURLOPT_HEADER, 0);					// 显示返回的Header区域内容
							curl_setopt($ch, CURLOPT_NOBODY, 0);					//只取body头
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);		// 对认证证书来源的检查
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);		// 从证书中检查SSL加密算法是否存在
							curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');	// 模拟用户使用的浏览器
							curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);			// 使用自动跳转
							curl_setopt($ch, CURLOPT_AUTOREFERER, 1);				// 自动设置Referer
							curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);		// Post提交的数据包
							curl_setopt($ch, CURLOPT_TIMEOUT, 30);					// 设置超时限制防止死循环
							$curl_error = curl_error($ch);
							$json = curl_exec($ch);

							curl_close($ch);

							$jsons = json_decode($json,true);
							//微信退款失败
							if( $jsons['return_code'] == 'FAIL' || $jsons['result_code'] == 'FAIL' ){
								$refund_result['status'] = 40006;
								$refund_result['msg'] = $jsons['err_code_des'] ? $jsons['err_code_des'] : $jsons['return_msg'];
							} else {
								$sendMessage_content[] = "亲，您的微信零钱 +".$totalprice."元\r\n".
														"来源：【拼团失败】\n".
														"状态：【退款到帐】\n".
														"时间：".date( "Y-m-d H:i:s")."";
							}
						}
						break;
					case '支付宝支付' :
						//查找支付宝支付单号
						$transaction_id = '';
						$query = "select sopl.pay_batchcode,sopl.real_pay_price,sopl.transaction_id from system_order_pay_log AS sopl 
									INNER JOIN weixin_commonshop_orders AS wco ON sopl.pay_batchcode=wco.pay_batchcode
									WHERE wco.batchcode='".$batchcode."' AND wco.isvalid=true AND wco.customer_id=".$customer_id;
													
						$result_query = _mysql_query($query);
							while ($row = mysql_fetch_object($result_query)) {
							$transaction_id = $row->transaction_id;
							$real_pay_price = $row->real_pay_price;
							$pay_batchcode = $row->pay_batchcode;
						}
						$url = Protocol . $_SERVER["HTTP_HOST"] . "/wsy_pay/web/alipay_rsa/wappay/refund.php";
						
						$url = $url."?WIDout_trade_no=".$pay_batchcode."&WIDtrade_no=".$transaction_id."&WIDrefund_amount=".$totalprice."&total_fee=".$real_pay_price."&batchcode=".$batchcode."&customer_id=".$customer_id."&industry_type=pintuan";
						//echo $url;
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
						curl_setopt($ch, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
						if (Protocol == "https://") {
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
						}
						$jsons = curl_exec($ch);
						if (curl_errno($ch)) {
							// echo 'Error'.curl_error($ch);
						}
						curl_close($ch);
						
						$jsons = json_decode($jsons,true);
						
						
						//支付宝退款失败
						if( $jsons['result'] ==  '-1' ){
							$refund_result['status'] = 40006;
							$refund_result['msg'] = $jsons['msg'];
						} else {
							$sendMessage_content[] = "亲，您的支付宝余额 +".$totalprice."元\r\n".
													"来源：【拼团失败】\n".
													"状态：【退款到帐】\n".
													"时间：".date( "Y-m-d H:i:s")."";
						}
						
						break;
					case '环迅快捷支付':
					case '环迅微信支付':
						//查询支付订单号、支付金额、支付时间
						$query_hxpay = "SELECT whn.out_trade_no,
												whn.total_fee,
												whn.createtime
											FROM weixin_hxpay_notifys AS whn
											INNER JOIN weixin_commonshop_orders AS wco ON whn.out_trade_no=wco.pay_batchcode
											WHERE wco.batchcode='".$batchcode."' AND wco.isvalid=true AND wco.customer_id=".$customer_id;
						$result_hxpay = _mysql_query($query_hxpay) or die('Query_hxpay failed:'.mysql_error());
						$hxpay_notify = mysql_fetch_assoc($result_hxpay);
						$out_trade_no = $hxpay_notify['out_trade_no'];	//支付订单号
						$total_fee 	  = $hxpay_notify['total_fee'];		//支付金额
						$paytime 	  = $hxpay_notify['createtime'];	//支付时间

						if( $out_trade_no != '' ){
							//发送的数据
							$post_data	= array(
								'batchcode' 	=> $batchcode,
								'out_trade_no' 	=> $out_trade_no,
								'total_fee' 	=> $total_fee,
								'refund_fee' 	=> $total_fee,
								'paytime' 		=> $paytime
							);

							$post_data = http_build_query($post_data);
							$url = Protocol.$_SERVER["HTTP_HOST"].'/wsy_pay/web/ipspay/order_refund_collage.php?customer_id='.$customer_id_en;		//调用环迅支付退款

							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $url);					// 要访问的地址
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($ch, CURLOPT_POST, 1 );
							curl_setopt($ch, CURLOPT_HEADER, 0);					// 显示返回的Header区域内容
							curl_setopt($ch, CURLOPT_NOBODY, 0);					//只取body头
							curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);		// 对认证证书来源的检查
							curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);		// 从证书中检查SSL加密算法是否存在
							curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');	// 模拟用户使用的浏览器
							curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);			// 使用自动跳转
							curl_setopt($ch, CURLOPT_AUTOREFERER, 1);				// 自动设置Referer
							curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);		// Post提交的数据包
							curl_setopt($ch, CURLOPT_TIMEOUT, 30);					// 设置超时限制防止死循环
							$curl_error = curl_error($ch);
							$json = curl_exec($ch);

							curl_close($ch);

							$jsons = json_decode($json,true);
							//环迅支付退款失败
							if( $jsons['code'] != 1 ){
								$refund_result['status'] = 40006;
								$refund_result['msg'] = $jsons['errmsg'];
							} else {
								$sendMessage_content[] = "亲，您申请的退款已通过 \r\n".
														"金额：".$total_fee."元 \n".
														"来源：【拼团失败】\n".
														"状态：【退款】\n".
														"支付方式：【".$paystyle."】\n".
														"时间：".date( "Y-m-d H:i:s")."";
							}
						}
					break;
					case '威富通支付':
						$wftpay = "select transaction_id,pay_batchcode,real_pay_price,wft_type from system_order_pay_log where batchcode_str='".$batchcode."' and is_changeorder=true";
						$result_wftpay = _mysql_query($wftpay) or die('Query_weipay failed: ' . mysql_error());
						while ($row_result_weipay = mysql_fetch_object($result_wftpay)) {
							$transaction_id = $row_result_weipay->transaction_id;
							$pay_batchcode = $row_result_weipay->pay_batchcode;
							$real_pay_price = $row_result_weipay->real_pay_price;
							$wft_type = $row_result_weipay->wft_type;
						}
						$order_data['out_trade_no'] = $pay_batchcode;
						$order_data['out_refund_no'] = time().rand(1000,9999);
						$order_data['total_fee'] = $real_pay_price*100;
						$order_data['refund_fee'] =  $real_pay_price*100;

						if( $wft_type == 1 ){
							require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/wftpay/wx/request.php");
							$req = new Weipayrequest($customer_id);

						}else{
							require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/wftpay_alipay/request.php");
							$req = new Alipayrequest($customer_id);
						}
						// print_r($order_data);
						// die();
						$result = $req->submitRefund($order_data);
						if( $result['status'] != 1 ){
							$refund_result['status'] = 40006;
							$refund_result['msg'] = '退款失败-'.$result['msg'];
						}else{
							$sendMessage_content[] = "亲，您申请的退款已通过 \r\n".
														"金额：".$real_pay_price."元 \n".
														"来源：【拼团失败】\n".
														"状态：【退款】\n".
														"支付方式：【".$paystyle."】\n".
														"时间：".date( "Y-m-d H:i:s")."";
						}

					break;
					case '健康钱包支付':
						//die("in case 健康钱包支付");
						$healthpay="select createtime,pay_batchcode from weixin_commonshop_orders where isvalid=true and batchcode='".$batchcode."' group by batchcode";
						//die("sql: ".$healthpay);
						$result_healthpay = _mysql_query($healthpay) or die('Query healthpay failed: ' . mysql_error());
						$pay_batchcode = "";
						$orderTime = "";
						while ($row_result_healthpay = mysql_fetch_object($result_healthpay)) {
							$pay_batchcode = $row_result_healthpay->pay_batchcode;
							$orderTime = $row_result_healthpay->createtime;
							break;
						}
						$orderTime = datetimeToNewFormat($orderTime);
						//die($pay_batchcode." : ".$orderTime);
						$api_obj = new HealthpayApi($customer_id);
						$sourceId = $api_obj->sourceId;//sourceId收款平台账号
						$md5Key = $api_obj->md5Key; //收款平台密钥

						$parameter = array(
							"orderId"     => $pay_batchcode,
							"orderTime"   => $orderTime,
						    "sourceId"    => $sourceId
						);

						//die("orderId: ".$pay_batchcode." orderTime: ".$orderTime." sourceId: ".$sourceId." md5Key: ".$md5Key);
						$healthPaySubmit = new HealthPaySubmit();
						$sign = $healthPaySubmit->getSign($parameter, $md5Key); //获取签名
						$parameter["sign"] = $sign;
						//die("sign: ".$sign);
						//发起退款
						$res = $api_obj->do_refund($parameter); //方法里面有记录日志

						//处理退款结果{"retCode":"0000","retMessage":"交易订单退款成功"}已转换成array
						if($res["retCode"] == "0000"){ //退款成功标志
							$sendMessage_content[] = "亲，您申请的退款已通过 \r\n".
													"金额：".$totalprice."元 \n".
													"来源：【拼团失败】\n".
													"状态：【退款】\n".
													"支付方式：【".$paystyle."】\n".
													"时间：".date( "Y-m-d H:i:s")."";
						} else{
							$refund_result['status'] = 40006;
							$refund_result['msg'] = $res['retMessage'];
						}
						break;
					default:
						$refund_result['status'] = 40006;
						$refund_result['msg'] = '仅以下支付方式支持原路退回：会员卡余额支付、微信支付、支付宝支付、环迅快捷支付、环迅微信支付、威富通支付、健康钱包支付';
					break;
				}
			}
		} else {	//全退零钱
			if( $price > 0 ){	//实际支付金额大于0，则退零钱


				if( $pay_currency > 0 ){	//如果有使用购物币则退回购物币
					$Currency = new Currency();
					$remark = "拼团失败退购物币";




					$query_currency_title = "SELECT custom FROM weixin_commonshop_currency WHERE customer_id=".$customer_id." AND isvalid=true";
					$result_currency_title = _mysql_query($query_currency_title) or die('Query_currency_title failed:'.mysql_error());
					$currency_title = mysql_fetch_assoc($result_currency_title)['custom'];

					$sendMessage_content[] = "亲，您的".$currency_title." +".$pay_currency."\r\n".
											"来源：【拼团失败】\n".
											"状态：【退款到帐】\n".
											"时间：".date( "Y-m-d H:i:s")."";
				}

				$totalprice = $price - $pay_currency;	//扣除购物币后实际支付金额
				$price      = $totalprice;

				$MoneyBag = new MoneyBag();
				//先检测有没有零钱钱包，没有则创建钱包
				$MoneyBag->insert_moneybag($user_id,$customer_id);

				$remark = "拼团失败退零钱";
				$refund_result = $MoneyBag->update_moneybag($customer_id,$user_id,$price,$batchcode,$remark,1,14,0);

				$sendMessage_content[] = "亲，您的零钱钱包 +".$price."元\r\n".
										"来源：【拼团失败】\n".
										"状态：【退款到帐】\n".
										"时间：".date( "Y-m-d H:i:s")."";
			}
		}

		if( $refund_result['status'] == 1 ){	//退款成功则改订单状态
			$query_status_up = "UPDATE collage_crew_order_t SET status=6 WHERE batchcode='".$batchcode."'";
			_mysql_query($query_status_up) or die('Query_status_up failed:'.mysql_error());
		    
		    $query = "SELECT cgot.type
						FROM collage_crew_order_t AS ccot
						LEFT JOIN collage_group_order_t AS cgot ON ccot.group_id=cgot.id
						WHERE ccot.batchcode='".$batchcode."' AND ccot.customer_id=".$customer_id." AND ccot.isvalid=true AND cgot.isvalid=true";
		                // echo $query;
			$result = _mysql_query($query) or die('Query failed:'.mysql_error());
		    $type = 1;
			while( $row = mysql_fetch_object($result) ){
				$type = $row -> type;
			}
		    
		    if($type == 6){//团长免单团
		        /* 修改商城订单状态为已退款 */
		        $query_order_up2 = "UPDATE weixin_commonshop_orders SET sendstatus=6 WHERE batchcode='".$batchcode."'";
		        _mysql_query($query_order_up2) or die('Query_order_up2 failed:'.mysql_error());
		    }
			
			$query_order_up = "UPDATE weixin_commonshop_orders SET status=-1 WHERE batchcode='".$batchcode."'";
			_mysql_query($query_order_up) or die('Query_order_up failed:'.mysql_error());

			$query_orderp_up = "UPDATE weixin_commonshop_order_prices SET status=-1 WHERE batchcode='".$batchcode."'";
			_mysql_query($query_orderp_up) or die('Query_orderp_up failed:'.mysql_error());

			$refund_result['msg'] = '退款成功';

		    $batchcode_arr = $collageActivities->check_order_refundable_arr($group_id,$customer_id);
		    if ( empty($batchcode_arr) ) {	//该团已全部退款
		        //更改团退款状态
		        $query_gstatus_up = "UPDATE collage_group_order_t SET refund_status=2 WHERE id='".$group_id."'";
		        _mysql_query($query_gstatus_up) or die('Query_gstatus_up failed:'.mysql_error());
		    }

			$custom = "购物币";
			$query_custom = "SELECT custom FROM weixin_commonshop_currency WHERE isvalid=true AND customer_id=".$customer_id." LIMIT 1";
			$result_custom = _mysql_query($query_custom)or die('Query_custom failed:'.mysql_error());
			while( $row_custom = mysql_fetch_object($result_custom) ){
				$custom = $row_custom -> custom;
			}

			//推送佣金流失消息
			$query_ord_pro = "select remark,reward,user_id,card_member_id,level_name,own_user_name,id_new,type,commission_type,commission_score from weixin_commonshop_order_promoters where isvalid=true and paytype=0 and batchcode='".$batchcode."'";
			$result_ord_pro = _mysql_query($query_ord_pro) or die('Query_ord_pro failed:'.mysql_error());
			while ( $row_ord_pro = mysql_fetch_object($result_ord_pro) ) {
				$id_new 			= $row_ord_pro->id_new;
				$money 				= $row_ord_pro->reward;
				$puser_id 			= $row_ord_pro->user_id;
				$card_member_id 	= $row_ord_pro->card_member_id;
				$level_name 		= $row_ord_pro->level_name;
				$own_user_name 		= $row_ord_pro->own_user_name;
				$protype 			= $row_ord_pro->type;
				$commission_type 	= $row_ord_pro->commission_type;
				$commission_score 	= $row_ord_pro->commission_score;

				if ( $protype == 10 ) {
					$tis = "扣除".$custom."：￥";
					$com = "个";
				} else {
					$tis = "退款扣除：￥";
					$com = "元";
				}

				$remark = "身份：【".$level_name."】\n".
						  "用户：【".$own_user_name."】\n";
				if ( $commission_type == 1 && $money > 0 ) {	//扣除零钱
					$remark .= $tis.$money.$com;
				}

				if ( $commission_type == 2 && $commission_score > 0 ) {	//扣除积分
					$remark .= "退积分扣除：".$commission_score;
				}

				//扣佣金
				$qr_info_id = -1;
				$query_qr_in = "select id from weixin_qr_infos where type=1 and foreign_id=".$puser_id." and user_type=1";

				$result_qr_in = _mysql_query($query_qr_in) or die('Query_qr_in failed: ' . mysql_error());
				while ($row_qr_in = mysql_fetch_object($result_qr_in)) {
					$qr_info_id = $row_qr_in->id;
				}

				if ( $qr_info_id > 0 ) {
					$query_up_qr = "update weixin_qrs set reward_money= reward_money-".$money." where qr_info_id=".$qr_info_id;
					_mysql_query($query_up_qr);
				}

				//更改佣金表状态
				$query_up_pro = "update weixin_commonshop_order_promoters set do_time=now(),paytype=4 where id_new=".$id_new;
				_mysql_query($query_up_pro);

				$query5 = "select weixin_fromuser from weixin_users where id='".$puser_id."' limit 1";
				$result5 = _mysql_query($query5) or die('Query5 failed: ' . mysql_error());
				$parent_fromuser = "";
				while ( $row5 = mysql_fetch_object($result5) ) {
					$parent_fromuser = $row5->weixin_fromuser;
				}

				$remark = addslashes($remark);
				$content = "买家退款\n时间：".date( "Y-m-d H:i:s")."\n".$remark;
				if ( $money > 0 ) {
					$shopMessage_Utlity -> SendMessage($content, $parent_fromuser, $customer_id);
				}

			}

			//推送退款消息
			$query_fromuser = "SELECT weixin_fromuser FROM weixin_users WHERE id='".$user_id."' AND customer_id=".$customer_id." AND isvalid=true";
			$result_fromuser = _mysql_query($query_fromuser) or die('Query_fromuser failed:'.mysql_error());
			$weixin_fromuser = mysql_fetch_assoc($result_fromuser)['weixin_fromuser'];

			foreach ( $sendMessage_content as $v ) {
				$shopMessage_Utlity -> SendMessage($v, $weixin_fromuser, $customer_id);
			}

			$insert_refund_log = "insert into ".WSY_MARK.". collage_refund_order_log (batchcode,customer_id,paystyle,totalprice,currency,isvalid,createtime) values('".$batchcode."',".$customer_id.",'".$paystyle."',".$totalprice.",".$pay_currency.",1,now())";
			_mysql_query($insert_refund_log);


			_mysql_query("COMMIT");
		} else {
			_mysql_query("ROLLBACK");
		}

		return $refund_result;
	}
}
?>