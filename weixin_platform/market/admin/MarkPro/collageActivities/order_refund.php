<?php
//ini_set('display_errors','On');
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/common/utility_shop.php");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/currency.php");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/moneybag.php");
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/healthpay/healthpay_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/healthpay/healthpay_api.class.php");
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/blockchain_pay/refund.php"); //拼团退款接口
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/healthpay/healthpay_submit.class.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/function_model/collageActivities.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/wftpay/wx/request.php");
//require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/wftpay_alipay/request.php");

$shopMessage_Utlity = new shopMessage_Utlity();

$collageActivities = new collageActivities($customer_id);//自定义类

$batchcode = $configutil->splash_new($_POST['batchcode']);
$refund_way = $configutil->splash_new($_POST['refund_way']);
//die($batchcode);

$refund_result = array(
	'status' => 1,
	'batchcode' => $batchcode,
	'msg' => '退款成功'
);

_mysql_query('BEGIN');

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
	die(json_encode($refund_result));
}

$query = "SELECT user_id,price,paystyle,pay_currency,block_chain_price FROM weixin_commonshop_order_prices WHERE batchcode='".$batchcode."' AND isvalid=true";
$result = _mysql_query($query) or die('Query failed:'.mysql_error());
while( $row = mysql_fetch_object($result) ){
	$price 				= $row->price;
	$user_id 			= $row->user_id;
	$paystyle 			= $row->paystyle;
	$pay_currency 		= $row->pay_currency;
	$block_chain_price 	= $row->block_chain_price;
}
//查询是否有改价
$query_change_price = "SELECT totalprice FROM weixin_commonshop_changeprices WHERE batchcode='".$batchcode."' AND isvalid=true";
$result_change_price = _mysql_query($query_change_price) or die('Query_change_price failed:'.mysql_error());
while( $row_change_price = mysql_fetch_object($result_change_price) ){
	$price = $row_change_price->totalprice;
}

//查询区块链积分名称
$query = "select name from ".WSY_SHOP.".block_chain_setting where customer_id='" . $customer_id . "'";
$result = _mysql_query($query) or die('Query failed:'.mysql_error());
while( $row = mysql_fetch_object($result) ){
	$name 			= $row->name;
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

	$totalprice = $price - $pay_currency;	//扣除购物币后实际支付金额

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
			case '区块链积分支付':
				//判断退款积分是否大于0
				if($block_chain_price > 0)
				{
					$refund = new blockchain_refund();
					$remark = "拼团失败退{$name}";
					$refund_result = $refund->refund($customer_id,$batchcode,$block_chain_price);
					//var_dump($refund_result);exit;
					$sendMessage_content[] = "亲，您的{$name} +".$totalprice."\r\n".
											"来源：【拼团失败】\n".
											"状态：【退款到帐】\n".
											"时间：".date( "Y-m-d H:i:s")."";
					if ($refund_result['errcode'] == 0) 
					{
						$refund_result['status'] = 1;
						$timer = date('Y-m-d H:i:s');
						$insert_block_chain_sec = "INSERT INTO  ".WSY_SHOP.".block_chain_log (customer_id,user_id,status,batchcode,reward,remark,createtime) values(".$customer_id.",'".$user_id."',1,".$batchcode.",".$block_chain_price.",'用户拼团失败【区块链】{$name}退还+{$block_chain_price}','{$timer}')";
						_mysql_query($insert_block_chain_sec) or die('Insert_block_chain_sec failed:'.mysql_error());

					}
				}
				else
				{
					$refund_result['status'] = 1;
					$timer = date('Y-m-d H:i:s');
					$insert_block_chain_sec = "INSERT INTO  ".WSY_SHOP.".block_chain_log (customer_id,user_id,status,batchcode,reward,remark,createtime) values(".$customer_id.",'".$user_id."',1,".$batchcode.",".$block_chain_price.",'用户拼团失败【区块链】{$name}退还+{$block_chain_price}','{$timer}')";
					_mysql_query($insert_block_chain_sec) or die('Insert_block_chain_sec failed:'.mysql_error());
					$refund_result['msg'] = '退款成功';
				}
				
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

		$query5 = "select weixin_fromuser from weixin_users where id=".$puser_id." limit 1";
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
	$query_fromuser = "SELECT weixin_fromuser FROM weixin_users WHERE id=".$user_id." AND customer_id=".$customer_id." AND isvalid=true";
	$result_fromuser = _mysql_query($query_fromuser) or die('Query_fromuser failed:'.mysql_error());
	$weixin_fromuser = mysql_fetch_assoc($result_fromuser)['weixin_fromuser'];

	foreach ( $sendMessage_content as $v ) {
		$shopMessage_Utlity -> SendMessage($v, $weixin_fromuser, $customer_id);
	}
	_mysql_query("COMMIT");
} else {
	_mysql_query("ROLLBACK");
	$refund_result['msg'] = '退款失败';
}

echo json_encode($refund_result);
?>