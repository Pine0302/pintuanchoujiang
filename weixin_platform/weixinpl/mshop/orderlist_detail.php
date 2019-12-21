<?php

header("Content-type: text/html; charset=utf-8");
require('../config.php'); //配置
require('../customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../proxy_info.php');
/*require('../common/jssdk.php');
$jssdk = new JSSDK($customer_id);
$signPackage = $jssdk->GetSignPackage();*/
//头文件----start

//判断是否从PC商城进入
if ($_COOKIE['pcshop'] != ""){
    $pcshop = $_COOKIE['pcshop'];
    setcookie("pcshop",null);
    header('Location:'.$pcshop."/shop/index.php/Home/My/orderList");
}

require('../common/common_from.php');
require('./tax_function.php');			//行邮税方法
require('select_skin.php');
//头文件----end

/*接口支付直接不使用倒计时 start*/
$p_stu = false;					//接口支付标识 false默认未支付 true为已经支付
if(!empty($_GET['p_stu'])){		//微信支付
    $p_stu = true;
}
/*接口支付直接不使用倒计时 end*/

$pay_batchcode = '';	//支付订单号
$batchcode 	   = '';
if(!empty($_GET["user_id"])){
    $user_id=$configutil->splash_new($_GET["user_id"]);
    $user_id_en = $user_id;
    $user_id = passport_decrypt($user_id);

}else{
    if(!empty($_SESSION["user_id_".$customer_id])){
        $user_id=$_SESSION["user_id_".$customer_id];
        $user_id_en =passport_encrypt($user_id);
    }
}
if(!empty($_GET['pay_batchcode'])){
    $pay_batchcode = $configutil->splash_new($_GET['pay_batchcode']);
}else if(!empty($_GET['batchcode'])){
    $batchcode = $configutil->splash_new($_GET['batchcode']);
}else{
    header('Location:errors.php?customer_id='.$customer_id_en.'&msg=缺少订单号&url=orderlist.php&currtype=1');
}

/*云店ID*/
$yundian = -1;
if(!empty($_GET['yundian'])){
    $yundian = $_GET['yundian'];
}
/*来自支付*/
$is_from_pay = -1;
if(!empty($_GET['is_from_pay'])){
    $is_from_pay = $_GET['is_from_pay'];
}


/*** 代付信息 start ***/
$payother_desc_id 	  = -1;	//订单详情页代付信息ID
$payother_desc_id_pay = -1;	//下单页代付信息ID
$pay_user_id 	  	  = -1;	//支付人ID
$note			  	  = "";	//支付者留言
$pay_username 	  	  = "";	//支付者名字
/*** 代付信息 end ***/
$store_id = -1;	//门店id
$is_open_aftersale = "1_1_1";	//是否开启退款_退货_换货，0；关闭；1：开启
if(!empty($batchcode)){
    //if($as_id<0){
    $query = "SELECT id,batchcode,pay_batchcode,paystyle,sendstatus,status,sendstyle,remark,merchant_remark,createtime,paystatus,yundian_id,yundian_self
        ,express_id,is_discuss,confirm_sendtime,confirm_receivetime,supply_id,auto_receivetime,is_delay
        ,return_type,return_status,aftersale_state,aftersale_reason,paytime,expressnum,expressname,allipay_orderid,is_QR,is_receipt,delivery_time_start,delivery_time_end,store_id,is_collageActivities,is_pay_on_delivery,is_sign,is_open_aftersale,camilo_ids,aftersale_type 
         from weixin_commonshop_orders where isvalid=true and batchcode = '".$batchcode."' limit 0,1";

    $result = _mysql_query($query) or die('Query OrderList failed: ' . mysql_error());

    $supply_id		  = -1;		//供应商编号
    $is_delay 		  = 0;		//是否申请延时
    $return_type 	  = -1;		//退货类型
    $return_status 	  = -1;		//退货状态
    $aftersale_state  = 0;		//售后状态
    $aftersale_reason = "";		//申请售后原因
    $expressnum 	  = "";		//快递单号
    $expressname      = "";     //快递名称
    $pay_batchcode2	  = "";		//支付订单号
    $paystatus		  = -1;		//支付状态
    $status		  	  = 0;		//订单状态
    $paystyle		  = '';		//支付方式
    $sendstatus		  = 0;		//发货状态
    $is_collageActivities = 0;		//拼团标识

    while ($row = mysql_fetch_object($result)) {
        $pay_batchcode2		 = $row->pay_batchcode;
        $order_id 			 = $row->id;
        $createtime 		 = $row->createtime;
        $paystyle			 = $row->paystyle;
        $paystatus 			 = $row->paystatus;
        $sendstyle			 = $row->sendstyle;
        $sendstatus 		 = $row->sendstatus;
        $status 			 = $row->status;
        $express_id 		 = $row->express_id;
        $supply_id 			 = $row->supply_id;				//供应商ID
        $is_discuss 		 = $row->is_discuss;  			//是否评论 0:无 1:评论 2:追加
        $confirm_receivetime = $row->confirm_receivetime;   //收货时间
        $auto_receivetime 	 = $row->auto_receivetime;
        $is_delay 			 = $row->is_delay;
        $return_type 		 = $row->return_type;
        $return_status 		 = $row->return_status;
        $aftersale_state 	 = $row->aftersale_state;
        $aftersale_reason 	 = $row->aftersale_reason;
        $paytime 			 = $row->paytime;				//支付时间
        $confirm_sendtime 	 = $row->confirm_sendtime;	//发货时间
        $allipay_orderid 	 = $row->allipay_orderid;		//支付宝支付单号
        $expressnum 	 	 = $row->expressnum;			//快递单号
        $expressname         = $row->expressname;           //快递名称
        $remark 		 	 = $row->remark;				//订单备注
        $merchant_remark 	 = $row->merchant_remark;		//商家备注
        $is_receipted 		 = $row->is_receipt;			//订单状态是否为已经自动收货 0：不 1：是
        $delivery_time_start = $row->delivery_time_start;	//配送时间
        $delivery_time_end 	 = $row->delivery_time_end;		//配送时间
        $store_id 			 = $row->store_id;				//门店id
        $is_collageActivities= $row->is_collageActivities;
        $date=0;
        $date=floor((strtotime($now)-strtotime($confirm_receivetime))/86400);    //计算收货时间与现在相差时间
        $is_QR = $row -> is_QR;
        $is_pay_on_delivery  = $row->is_pay_on_delivery;
        $is_sign             = $row->is_sign;
        $is_open_aftersale   = $row->is_open_aftersale;
        $yundian_id = $row ->yundian_id;
        $yundian_self = $row ->yundian_self;
        $camilo_ids = $row ->camilo_ids;  //关联的卡密id
        $batchcode = $row ->batchcode;   //确保订单号一定有
        $aftersale_type = $row->aftersale_type;
    }
    if($yundian_id > 0 && $yundian_self == 1) {
        $yundian = 1;
    } else {
        $yundian = 0;
    }

    if( $paystatus == 0 ){
        $query_up = "UPDATE weixin_commonshop_orders SET delivery_time_start='',delivery_time_end='' WHERE batchcode='".$batchcode."' AND customer_id=".$customer_id;
        _mysql_query($query_up) or die('Query_up failed:'.mysql_error());
    }
    /*** 查询代付信息 start ***/
    $query_payother = "select id,pay_user_id,pay_username,note from weixin_commonshop_otherpay_descs where isvalid=true and user_id=".$user_id." and batchcode='".$batchcode."' limit 1";
    $result_payother = _mysql_query($query_payother) or die('query_payother failed'.mysql_error());
    while($row_payother = mysql_fetch_object($result_payother)){
        $payother_desc_id = $row_payother->id;
        $pay_user_id 	  = $row_payother->pay_user_id;
        $pay_username 	  = $row_payother->pay_username;
        $note 	  		  = $row_payother->note;
    }
    if($payother_desc_id<0){
        $query_payother = "select id,pay_user_id,pay_username,note from weixin_commonshop_otherpay_descs where isvalid=true and user_id=".$user_id." and batchcode='".$pay_batchcode."' limit 1";  //原本这里用$pay_batchcode2
        $result_payother = _mysql_query($query_payother) or die('query_payother failed'.mysql_error());
        while($row_payother = mysql_fetch_object($result_payother)){
            $payother_desc_id_pay = $row_payother->id;
            $pay_user_id 	  	  = $row_payother->pay_user_id;
            $pay_username 	  	  = $row_payother->pay_username;
            $note 	  		  	  = $row_payother->note;
        }
    }
    /*** 查询代付信息 end ***/

    //支付方式开关
    $is_alipay   	= false;				//支付宝支付开关
    $is_weipay   	= false;				//商城微信支付开关
    $is_tenpay   	= false;				//商城财付通开关
    $is_allinpay 	= false;				//商城通联支付开关
    $isdelivery  	= false;				//商城货到付款开关0关闭1开启
    $is_payChange   = false;				//零钱支付开关
    $is_pay         = false;				//暂不支付开关
    $iscard      	= false;				//商城会员卡支付开关
    $isshop      	= false;				//商城到店支付开关
    $is_payother 	= false;				//是否开启代付
    $is_paypal	 	= false;				//paypal支付
    $isOpenCurrency = false;				//购物币支付开关
    $is_jdpay       = false;				//京东支付开关
    $query = 'SELECT id,is_alipay,is_tenpay,is_payChange,is_pay,is_weipay,is_allinpay,isdelivery,iscard,isshop,is_payother,is_paypal,is_jdpay FROM customers where isvalid=true and id='.$customer_id;
    $defaultpay = "";
    $result = _mysql_query($query) or die('W75 Query failed: ' . mysql_error());
    while ($row = mysql_fetch_object($result)) {
        $is_alipay    = $row->is_alipay;
        $is_tenpay    = $row->is_tenpay;
        $is_weipay    = $row->is_weipay;
        $is_pay       = $row->is_pay;
        $is_payChange = $row->is_payChange;
        $is_allinpay  = $row->is_allinpay;
        $iscard       = $row->iscard;
        $isdelivery   = $row->isdelivery;
        $isshop       = $row->isshop;
        $is_payother  = $row->is_payother;
        $is_paypal    = $row->is_paypal;
        $is_jdpay    = $row->is_jdpay;
        break;
    }

    //拼团订单
    $group_id 		= -1;	//团id
    $activitie_id 	= -1;	//活动id
    $is_head 		= 2;	//是否团长：1是、2否
    if( $is_collageActivities > 0 ){
        $query_collage = "SELECT group_id,activitie_id,is_head FROM collage_crew_order_t WHERE isvalid=true AND customer_id=".$customer_id." AND batchcode='".$batchcode."'";
        $result_collage = _mysql_query($query_collage) or die('Query_collage failed:'.mysql_error());
        while( $row_collage = mysql_fetch_object($result_collage) ){
            $group_id 		= $row_collage->group_id;
            $activitie_id 	= $row_collage->activitie_id;
            $is_head 		= $row_collage->is_head;
        }
        if( $is_head == 1 ){
            $group_id = -1;
        }
    }

}else{
    $batchcode 		 = '';
    $paystatus 		 = -1;
    $paystyle  		 = '';
    $paytime   		 = '';
    $status    		 = 0;
    $sendstatus 	 = 0;
    $aftersale_state = 0;
    $is_QR 			 = 0;
    $is_collageActivities = 0;
    $query_batchcode = "select status,batchcode,paystatus,paystyle,paytime,sendstatus,aftersale_state,is_QR,store_id,is_collageActivities,is_pay_on_delivery,is_sign,camilo_ids from weixin_commonshop_orders where isvalid=true and pay_batchcode = '".$pay_batchcode."' limit 0,1";
    $result_batchcode = _mysql_query($query_batchcode) or die('query_batchcode failed'.mysql_error());
    while($row_batchcode = mysql_fetch_object($result_batchcode)){
        $batchcode 		 = $row_batchcode->batchcode;
        $paystatus 		 = $row_batchcode->paystatus;
        $paystyle  		 = $row_batchcode->paystyle;
        $paytime   		 = $row_batchcode->paytime;
        $status    		 = $row_batchcode->status;
        $sendstatus 	 = $row_batchcode->sendstatus;
        $aftersale_state = $row_batchcode->aftersale_state;
        $is_QR 			 = $row_batchcode->is_QR;
        $store_id 		 = $row_batchcode->store_id;
        $is_collageActivities = $row_batchcode->is_collageActivities;
        $is_pay_on_delivery = $row_batchcode->is_pay_on_delivery;
        $is_sign         = $row->is_sign;
        $camilo_ids      = $row_batchcode->camilo_ids;  //卡密相关
    }

    if( $paystatus == 0 ){
        $query_up = "UPDATE weixin_commonshop_orders SET delivery_time_start='',delivery_time_end='' WHERE pay_batchcode='".$pay_batchcode."'";
        _mysql_query($query_up) or die('Query_up failed:'.mysql_error());
    }
    $query_payother = "select id,pay_user_id,pay_username,note from weixin_commonshop_otherpay_descs where isvalid=true and user_id=".$user_id." and batchcode='".$pay_batchcode."'";
    $result_payother = _mysql_query($query_payother) or die('query_payother failed'.mysql_error());
    while($row_payother = mysql_fetch_object($result_payother)){
        $payother_desc_id = $row_payother->id;
        $pay_user_id 	  = $row_payother->pay_user_id;
        $pay_username 	  = $row_payother->pay_username;
        $note 	  		  = $row_payother->note;
    }
}

//卡密
$camilo_row = [];
if (!empty($camilo_ids)) {
    $sql = 'SELECT camilo FROM '.WSY_PROD.".weixin_commonshop_camilo WHERE customer_id='{$customer_id}' AND status=3 AND batchcode='{$batchcode}' AND id in({$camilo_ids})";
    $row = _mysql_query($sql) or die('CA failed'.__LINE__.mysql_error());
    while($camilo = mysql_fetch_object($row)){
//        $camilo_row[] = "\n".$camilo->camilo."; ";
        $camilo_row[] = $camilo->camilo;
    }
    $camilo_str = '';
    $camilo_count = count($camilo_row);
    foreach($camilo_row as $k=>$v) {
        if ($k == 0) {
            $camilo_find = $v;
        } else {
            $camilo_str .= $v."<br>";
        }
    }
}

if(!$batchcode){
    $url = Protocol . $_SERVER['HTTP_HOST']."/weixinpl/mshop/personal_center.php";
    header("Location: ".$url);
    return ;
}



//判断当前订单表中是否已有下架或失效的商品，有则在付款时提示....此处优化应该判断订单是否为待付款，否则就不需要查询商品是否下架
$product_isnone = 0;
$sql_batchcode_product =  "select pid from weixin_commonshop_orders where isvalid = true and batchcode = '".$batchcode."'";
$res_batchcode_product = _mysql_query($sql_batchcode_product) or die("sql_batchcode_product query error : ".mysql_error());
while($row_batchcode_product = mysql_fetch_object($res_batchcode_product)){
    $products_pid = $row_batchcode_product -> pid;
    $sql_product_isout =  "select name from weixin_commonshop_products where isvalid = true and isout = 0 and id =".$products_pid;
    $res_product_isout = _mysql_query($sql_product_isout) or die("sql_product_isout query error : ".mysql_error());
    if(!($row_product_isout = mysql_fetch_object($res_product_isout))){
        $product_isnone = 1;
    }
}

//当前用户的购物币数量
$user_curr = 0;
$sql_user = "select id,currency from weixin_commonshop_user_currency where isvalid = true and user_id = ".$user_id;
$res_user = _mysql_query($sql_user) or die("sql_user query error : ".mysql_error());
if($row_user = mysql_fetch_object($res_user)){
    $user_curr = $row_user -> currency;
}
$user_curr = round($user_curr,2);
//是否开启使用购物币
$custom = '购物币';	//自定义购物币名称
$sql_cur  = "SELECT isOpen,custom FROM weixin_commonshop_currency WHERE isvalid = true and customer_id=".$customer_id;
$res_cur = _mysql_query($sql_cur) or die("sql_cur failed:".mysql_error());
if ($row_cur = mysql_fetch_object($res_cur) ){
    $isOpenCurrency = $row_cur->isOpen;
    $custom 		= $row_cur->custom;
}

//根据贺卡祝福语，订单状态 判断是否显示 贺卡祝福语入口  tao jin
$blessing_entrance = false;
$sql_blessing = "select is_blessing from weixin_commonshops_extend where isvalid=true and customer_id=".$customer_id." limit 1";
$res_blessing = _mysql_query($sql_blessing) or die("sql_blessing  failed:".mysql_error());
if ($row_blessing = mysql_fetch_object($res_blessing) ){
    $is_blessing = $row_blessing->is_blessing;
}
//查询该订单是否有添加过贺卡
$sql_has_blessing = "select id from weixin_commonshop_order_blessing WHERE isvalid = true and batchcode='{$batchcode}' and customer_id='{$customer_id}' limit 1";
$res_has_blessing = _mysql_query($sql_has_blessing) or die("sql_has_blessing failed:".mysql_error());
if ($row_has_blessing = mysql_fetch_object($res_has_blessing) ){
    $has_blessing = $row_has_blessing->id;
}
if($status>=0 and $paystatus==1 and $is_blessing == 1 ) $blessing_entrance = true;
if($has_blessing==false and $status>0 and $sendstatus>0) $blessing_entrance = false;
/*** 下单时是否使用了优惠券、购物币和会员卡折扣 start***/
/*$pay_currency = 0;	//购物币
$coupon		  = 0;	//优惠券金额
$query_cac = "select currency,coupon from order_currencyandcoupon_t where user_id=".$user_id." and customer_id=".$customer_id." and pay_batchcode='".$batchcode."'";
$result_cac = _mysql_query($query_cac) or die('query_cac failed:'.mysql_error());
while($row_cac = mysql_fetch_object($result_cac)){
    $pay_currency = $row_cac->currency;
    $coupon		  = $row_cac->coupon;
}

$cardDiscount = 0;	//会员卡优惠  0:没有使用，1有使用
$query_discount = "select cardDiscount from weixin_commonshop_order_prices where isvalid=true and batchcode='".$batchcode."'";
$result_discount = _mysql_query($query_discount) or die('query_discount failed:'.mysql_error());
while($row_discount = mysql_fetch_object($result_discount)){
    $cardDiscount = $row_discount->cardDiscount;
}*/
/*** 下单时是否使用了优惠券、购物币和会员卡折扣 end***/


$currtime       = time();   //当前时间
$recovery_time  = '';       //支付失效时间
$cardDiscount 	= 0;		//会员卡优惠  0:没有使用，1有使用
$o_shop_id 	= -1;//订货系统门店id
$o_verification_code = '';//订货系统核销码
$is_sendorder = 0;//是否已派单
$or_shop_type = -1;
$or_code = -1;
$query_time = "select recovery_time,is_sendorder,cardDiscount,o_shop_id,or_shop_type,o_verification_code,or_code from weixin_commonshop_order_prices where isvalid=true and batchcode='".$batchcode."' limit 1";
$result_time = _mysql_query($query_time) or die('Query_time failed:'.mysql_error());
while($row_time = mysql_fetch_object($result_time)){
    $recovery_time  = $row_time->recovery_time;
    $cardDiscount 	= $row_time->cardDiscount;
    $is_sendorder 	= $row_time->is_sendorder;
    $o_shop_id 	   = $row_time->o_shop_id;
    //$or_shop_type   = $row_time->or_shop_type;
    $o_verification_code = $row_time->o_verification_code;
    $or_code         = $row_time->or_code;
}
if(empty($or_code)){//该字段数据库默认值为空，会影响后面收货地址的显示
    $or_code = -1;
}
//查询商家绑定的会员卡------star
$shop_card_id = -1;
$is_ban_use_coupon_currency = 0; //是否禁止同时使用购物币和优惠券
$query = "SELECT shop_card_id,is_ban_use_coupon_currency FROM weixin_commonshops WHERE isvalid=true AND customer_id=".$customer_id." limit 1";
$result= _mysql_query($query);
while($row=mysql_fetch_object($result)){
    $shop_card_id = $row->shop_card_id;    //--------先查出商家现在绑定的是哪张会员卡
    $is_ban_use_coupon_currency = $row->is_ban_use_coupon_currency;
}
if($shop_card_id>0){
    $card_member_id = -1;
    $remain_score  = 0 ; //个人积分余额
    $query = "SELECT id FROM weixin_card_members WHERE isvalid=true AND user_id=".$user_id." AND card_id=".$shop_card_id." LIMIT 1";
    $result= _mysql_query($query);
    while($row=mysql_fetch_object($result)){
        $card_member_id = $row->id;						//----------根据商家绑定会员卡id跟user_id查出会员卡id
        if($card_member_id>0){
            $query = "SELECT remain_score FROM weixin_card_member_scores where isvalid=true AND card_member_id=".$card_member_id." LIMIT 1";
            $result= _mysql_query($query);
            while($row2=mysql_fetch_object($result)){
                $remain_score = round($row2->remain_score,2);		//---------再拿会员卡id查出积分余额
            }
        }
    }
}

//查询商家绑定的会员卡------end

/*行邮税*/
//获取订单行邮税总和
$total_tax = 0;
$get_tax_result = get_tax_result($batchcode);
$total_tax = $get_tax_result[1];
$total_tax_type = $get_tax_result[0];
//获取行邮税类型名称
$tax_name = get_tax_name($total_tax_type);
/*行邮税*/

//查询是否开启订单售后维权----start
$is_orderActivist = -1;//订单售后维权开关 0、关闭 1、开启
$is_receipt		  =  0;//
$sql_order = "select is_orderActivist,is_receipt from weixin_commonshops_extend where isvalid=true and customer_id=".$customer_id;
$result_order = _mysql_query($sql_order) or die('sql_score failed:'.mysql_error());
while($row_order = mysql_fetch_object($result_order)){
    $is_receipt 	  = $row_order->is_receipt;
    $is_orderActivist = $row_order->is_orderActivist;
}

//查询是否开启订单售后维权----end
//查询是否开启积分商品售后维权-----start
$is_integral_aftersale = 0;//订单售后维权开关 0、关闭 1、开启
$integral_as_sql = "select aftersale_onoff,afstore_onoff from ".WSY_SHOP.".integral_setting where cust_id=".$customer_id;
$result_integral_as   = _mysql_query($integral_as_sql) or die('sql_score failed:'.mysql_error());
while($row_integral_as = mysql_fetch_object($result_integral_as)){
    $is_integral_aftersale = $row_integral_as->aftersale_onoff;
    $is_integral_afstore   = $row_integral_as->afstore_onoff;
}
//	var_dump($is_integral_aftersale);
//查询是否开启积分商品售后维权-----end
//查询该订单是否有获得积分，控制售后流程开关显示-----start   2018.1.12
$sql_store_off = "SELECT iol.type,iol.number from ".WSY_SHOP.".integral_order_log iol inner join weixin_commonshop_orders wco on iol.batchcode = wco.batchcode where iol.cust_id = '{$customer_id}' and iol.batchcode = '{$batchcode}' ";
$res_store_off   = _mysql_query($sql_store_off) or die('sql_store failed:'.mysql_error());
while($row_store_off = mysql_fetch_object($res_store_off)){
    $store_type_off = $row_store_off->type;
    $store_number_off = $row_store_off->number;
}
$store_type_off_check = 0;
switch($store_type_off){		//判断订单类型，赋值商城积分售后开关或者门店积分售后开关
    case 1:
    case 2:
        $store_type_off_check = $is_integral_aftersale;
        break;
    case 3:
    case 4:
        $store_type_off_check = $is_integral_afstore;
        break;
}

//查询该订单是否有获得积分，控制售后流程开关显示-----end
//查询该订单是否有获得积分，使用积分-----start
$sql_store = "SELECT iol.type,iol.number from ".WSY_SHOP.".integral_order_log iol inner join weixin_commonshop_orders wco on iol.batchcode = wco.batchcode where iol.cust_id = '{$customer_id}' and iol.batchcode = '{$batchcode}' and ((wco.status = 1 and iol.type = 1) or iol.type=2 or iol.type=4) ";
$res_store   = _mysql_query($sql_store) or die('sql_store failed:'.mysql_error());
while($row_store = mysql_fetch_object($res_store)){
    $store_type = $row_store->type;
    $store_number = $row_store->number;
}
$store_show = 0;
if($store_type>0 && $store_number>0){
    $store_show = 1;
    $store_number=round($store_number,2);
    switch($store_type){
        case 1:
            $store_str1 = '获得商城积分';
            $store_str2 = "+{$store_number}";
            break;
        case 2:
            $store_str1 = '商城积分';
            $store_str2 = "-{$store_number}";
            break;

        case 4:
            $store_str1 = '门店积分';
            $store_str2 = "-{$store_number}";
            break;
    }
}

//查询该订单是否有获得积分，使用积分-----end
//查找全局购物币抵扣比例
$percentage = 1;
$shop_cur_sql = "select percentage from currency_percentage_t where isvalid=true and type=1 and customer_id=".$customer_id." LIMIT 1";
$res_shop_cur = _mysql_query($shop_cur_sql)or die('Query failed percen100'.mysql_error());
while($row_shop_cur = mysql_fetch_object($res_shop_cur)){
    $percentage = $row_shop_cur->percentage;
}
//查找全局购物币抵扣比例END

if( $is_collageActivities > 0 ){
    $cstatus = -1;
    $gstatus = -1;
    $query_collage_order = "SELECT ccot.status AS cstatus,cgot.status AS gstatus,cgot.type AS ctype
							FROM collage_crew_order_t AS ccot
							LEFT JOIN collage_group_order_t AS cgot ON ccot.group_id=cgot.id
							WHERE ccot.batchcode='".$batchcode."' AND ccot.customer_id=".$customer_id." AND ccot.isvalid=true AND cgot.isvalid=true";
    $result_collage_order = _mysql_query($query_collage_order) or die('Query_collage_order failed:'.mysql_error());
    while( $row_collage_order = mysql_fetch_object($result_collage_order) ){
        $cstatus = $row_collage_order -> cstatus;	//订单状态
        $gstatus = $row_collage_order -> gstatus;	//团状态
        $ctype = $row_collage_order -> ctype;	//团类型
    }

}

/* 零钱支付手续费用 create by hzq */
if($pay_batchcode != ""){
    $pay_batchcode2 = $pay_batchcode;
}

$poundage = 0;
$poundage_sql = "select money from moneybag_log where batchcode ='".$batchcode."' and isvalid = true and pay_style=13";//原本这里用$pay_batchcode2
$poundage_result = _mysql_query($poundage_sql) or die('Query poundage_sql failed: ' . mysql_error());
while($poundage_row = mysql_fetch_object($poundage_result)){
    $poundage = $poundage_row->money;
}

// print_r($poundage);die();
/* 零钱支付手续费用 end */

/** 支付密码设置优化 create_by hzq **/
$_SESSION['pass_url_'.$customer_id] = $_SERVER['REQUEST_URI'];
/** 支付密码设置优化 end **/

//查找订单所属活动是否开启售后按钮
$if_change_pro = 1;		//是否开启换货
$if_refund = 1;			//是否开启退款
$if_return_pro = 1;		//是否开启退货
$after_sale_button = 1;
if( !empty($is_open_aftersale) && $is_collageActivities!=3){
    $if_aftersale_arr = explode("_",$is_open_aftersale);
    if($if_aftersale_arr[0] == 0){
        $if_refund = 0;
    }
    if($if_aftersale_arr[1] == 0){
        $if_return_pro = 0;
    }
    if($if_aftersale_arr[2] == 0){
        $if_change_pro = 0;
    }
}
if( $is_collageActivities > 0 ){
    $query_order_refund = "SELECT cat.if_change_pro,cat.if_refund,cat.if_return_pro
							FROM collage_crew_order_t AS ccot
							LEFT JOIN collage_activities_t AS cat ON ccot.activitie_id=cat.id
							WHERE ccot.batchcode='".$batchcode."' AND ccot.customer_id=".$customer_id." AND ccot.isvalid=true AND cat.isvalid=true";
    $result_order_refund = _mysql_query($query_order_refund) or die('query_order_refund failed:'.mysql_error());
    while( $row_order_refund = mysql_fetch_object($result_order_refund) ){
        $if_change_pro = $row_order_refund -> if_change_pro;
        $if_refund = $row_order_refund -> if_refund;
        $if_return_pro = $row_order_refund -> if_return_pro;
    }
}
if($if_change_pro==0 && $if_refund==0 && $if_return_pro==0){
    $after_sale_button = 0;
}
if($if_change_pro==0 && $if_refund==1 && $if_return_pro==0){
    $after_sale_button = 2;
}
//抱抱团返赠购物币金额
$bbt_retuncurr = -1;
$query_recurr = "SELECT coeff_return_type,coeff_return_value,price FROM collage_bbt_order_extend WHERE batchcode='".$batchcode."' AND isvalid=true";
$result_recurr = _mysql_query($query_recurr) or die('query_recurr failed:'.mysql_error());
while( $row_recurr = mysql_fetch_object($result_recurr) ){
    $coeff_return_type = $row_recurr -> coeff_return_type;
    $coeff_return_value = $row_recurr -> coeff_return_value;
    $order_price = $row_recurr -> price;

    if($coeff_return_type==1){
        $bbt_retuncurr = $coeff_return_value*$order_price/100;
        $bbt_retuncurr = bcadd($bbt_retuncurr,0,2);
    }elseif($coeff_return_type==2){
        $bbt_retuncurr = $coeff_return_value;
    }
}


$o_shop_name = '';
$o_shop_branch_name = '';
$sys_order = "SELECT id,or_shop_type FROM system_send_order WHERE isvalid=TRUE AND order_id = '".$batchcode."'";
$result_sys_order = _mysql_query($sys_order);
while($row = mysql_fetch_object($result_sys_order)){
    $or_shop_type = $row->or_shop_type;
    $system_send_id = $row->id;
}

if( $or_shop_type == 2 ){
    $sql_shop_info = "SELECT a.branch_name,b.shop_name FROM ".WSY_DH.".orderingretail_shop_branch a INNER JOIN ".WSY_DH.".orderingretail_shop b ON a.shop_id = b.id WHERE a.id=".$o_shop_id;
    $result_shop_info = _mysql_query($sql_shop_info);
    while($row = mysql_fetch_object($result_shop_info)){
        $o_shop_name = $row->shop_name;
        $o_shop_branch_name = $row->branch_name;
    }
}


/* 大转盘 */
$slyder_extend_id = -1;
if($_GET["slyder_extend_id"] > 0){
    $slyder_extend_id = $configutil->splash_new($_GET["slyder_extend_id"]);
}
$slyder_id        = -1;
$slyder_token     = "";
$slyder_chance    = 0;
$slyder_batchcode = "";
if($paystatus == 1 && $slyder_extend_id >0){
    require_once($_SERVER['DOCUMENT_ROOT'] . '/mshop/admin/model/slyder_adventures.php');
    $model_slyder_adventures = new model_slyder_adventures();
    $result_slyder = $model_slyder_adventures->get_chance_extend_shop_order($slyder_extend_id,$user_id);
    if( $result_slyder["errcode"] == 0 ){
        $slyder_id        = $result_slyder["data"]["slyder_id"];
        $slyder_token     = $result_slyder["data"]["token"];
        $slyder_chance    = $result_slyder["data"]["num"];
        $slyder_batchcode = $result_slyder["data"]["batchcode"];
    }
}
/* 大转盘 */
// var_dump($is_collageActivities);

?>
<!DOCTYPE html>
<html>
<head>
    <title>订单详情</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta content="no" name="apple-touch-fullscreen">
    <meta name="MobileOptimized" content="320"/>
    <meta name="format-detection" content="telephone=no">
    <meta name=apple-mobile-web-app-capable content=yes>
    <meta name=apple-mobile-web-app-status-bar-style content=black>
    <meta http-equiv="pragma" content="nocache">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8">
    <script type="text/javascript" src="./assets/js/jquery.min.js"></script>
    <script type="text/javascript" src="./assets/js/amazeui.js"></script>
    <script type="text/javascript" src="../common/js/jquery-2.1.0.min.js"></script>

    <script src="./js/jquery.ellipsis.js"></script>
    <script src="./js/jquery.ellipsis.unobtrusive.js"></script>
    <script type="text/javascript" src="./js/jquery.zclip.min.js"></script>
    <script type="text/javascript" src="./js/loading.js"></script>
    <link href="./css/mobiscroll.custom-2.6.2.min.css" rel="stylesheet" type="text/css">
    <script src="./js/mobiscroll.custom-2.6.2.min.js" type="text/javascript"></script>
    <link type="text/css" rel="stylesheet" href="./assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="./css/order_css/global.css" />
    <link type="text/css" rel="stylesheet" href="/mshop/web/static/yundian/css/common.css" />
    <link type="text/css" rel="stylesheet" href="/mshop/web/static/yundian/css/cloudStore.css" />
    <script src="/addons/common/js/jquery.qrcode.min.js"></script>

    <link type="text/css" rel="stylesheet" href="./css/css_<?php echo $skin ?>.css" />
</head>

<link rel="stylesheet" href="./css/order_css/style.css" type="text/css" media="all">
<link type="text/css" rel="stylesheet" href="./css/order_css/dingdan.css" />
<link type="text/css" rel="stylesheet" href="./css/order_css/dingdan_detail.css"/>
<style>
    .recovery_time{
        color: #fff;
    }
    .wait_tip{
        float: left;
    }
    .left_time_top{
        font-size: 16px;
        margin-top: 15px;
        margin-left: 25px;
    }
    .left_time_bottom{
        font-size: 13px;
        margin-left: 25px;
        margin-bottom: 15px;
    }
    .order_close{
        float: left;
        height: 90px;
        line-height: 90px;
        margin-left: 40px;
        font-size: 16px;
    }
    .recovery_time_img{
        float: right;
        margin-right: 20px;
        height: 90px;
        line-height: 90px;
    }
    .recovery_time_img img{
        vertical-align: middle;
    }


    .sk-fading-circle {
        width: 40px;
        height: 40px;
        position: fixed;
        top:0;
        left:50%;
        top:50%;
        margin-left:-20px;
        margin-top:-20px
    }

    //ld 点击效果
      .button{
          -webkit-transition-duration: 0.4s; /* Safari */
          transition-duration: 0.4s;
      }

    .buttonclick:hover{
        box-shadow:  0 0 5px 0 rgba(0,0,0,0.24);
    }
    .btn-shui{height: 16px; background: #fff;border:1px solid #ff7109; color: #ff7109;border-radius: 2px;font-size: 12px;padding: 0; pointer-events: none;}
    .test5 {
        display: inline-block;
        height:0;
        width:20px;
        color:#fff;
        line-height: 0;
        border-color:#ff7109 #fff transparent transparent;
        border-style:solid solid dashed dashed;
        border-width:14px 4px 0 0 ;
    }
    .test5 span{display: block;margin-top: -6px;color: #fff;font-weight: bold;  line-height: 0px;height: 0px;font-size: 12px;}
    .new_popup-menu-row{
        width: 100%;
        text-align: left;
        background-color: #fff;
        padding: 13px;
    }
    .new_popup-menu-row img {
        display: inline-block;
        width: 23px;
        margin-left: 20px;
        vertical-align: middle;
    }
    .newzhifup{font-size: 12px;}
    .newdiv{
        font-size: 15px;
        margin-left: 15px;
        display: inline-block;
        vertical-align: middle;
        color: #1c1f20;
        max-width: 76%;
    }
    .select-delivery-time{
        line-height: 25px !important;
        border: 1px #C1C0C0 solid;
        border-radius: 5px;
        padding: 3px !important;
        font-size: 12px !important;
    }
    .dlvb{
        z-index: 10001;
        background-color: #fff;
        position: fixed;
        top: 25%;
        left: 15%;
        width: 70%;
        border-radius: 3px;
    }
    .dlvb-title{
        text-align: center;
        padding: 7px 0;
        font-size: 18px;
        color: #31b6e7;
        border-bottom: 2px solid #31b6e7;
    }
    .dlvb-content-box{
        max-height: 190px;
        overflow-y: scroll;
        min-height: 100px;
    }
    .dlvb-btn{
        border-top: 1px solid #dbdbdb;
        height: 36px;
        line-height: 36px;
        padding: 0;
        margin: 0;
        font-weight: normal;
        text-shadow: none;
        -webkit-border-radius: 0;
        -webkit-box-shadow: none;
        cursor: pointer;
        overflow: hidden;
        font-size: 14px;
    }
    .dlvb-confirm{
        border-right: 1px solid #dbdbdb;
        display: inline-block;
        width: 49%;
        position: relative;
        z-index: 5;
        text-align: center;
        color: #000;
    }
    .dlvb-cancel{
        display: inline-block;
        width: 49%;
        position: relative;
        z-index: 5;
        text-align: center;
        color: #000;
    }
    .dlvb-content{
        text-align: center;
    }
    .dlvb-content span{
        display: inline-block;
        margin: 8px;
        height: 30px;
        font-size: 14px;
        width: 170px;
        line-height: 25px;
        border-radius: 11px;
        border: 2px #ccc solid;
    }
    .dlvb-selected span{
        display: inline-block;
        margin: 8px;
        height: 30px;
        font-size: 14px;
        width: 170px;
        line-height: 25px;
        border-radius: 11px;
        border: 2px #31b6e7 solid;
        color: #31b6e7;
    }
    .box1{width: 90%;padding: 10px 5%;background-color: #fff;margin-top: 10px;}
    .boxCell{width: 95%;padding: 5px 2.5%;background-color: #eeeeee;margin-bottom: 10px;}
    .boxCell:last-child{margin-bottom: 0;}
    .boxCell span{color: #f4212b;}
    .boxCell p{color: #707070;margin-top: 5px;}

    .box2{width: 100%;background-color: #fff;margin-top: 10px;border-top: 1px solid#d1d1d1;padding: 10px 0;text-align: center;}
    .box2 button{display: inline-block;width: 30%;margin-right: 2%;background-color: #fff;border: 1px solid#D1D1D1;padding: 10px 0;color: #1c1f20;font-size: 15px;}
    .box2 button:last-child{margin-right: 0;}
    .box{margin:30px 15px 0 15px;}
    .guanbicha{position: absolute;left: 14%;font-size: 22px;}
    .movetime{height: auto;}
    .cell_icon{position:absolute;top:19px;left:13px;background-image:url(collageActivities/img/cell_icon.png);display:block;width:40px;height:20px;background-repeat:no-repeat;background-size:40px 20px;font-size:.8rem;color:#fff;line-height:18px;text-align:center;font-style:normal;overflow:hidden;}
    .greet-car{
        position: fixed;
        bottom: 4.5rem;
        right: 4.5rem;
        z-index: 999;
    }
    .greet-car img{
        display: block;
        /*width: 0.96rem;*/
        /*height: 1.06rem;*/
        /*width: 1.92rem;*/
        /*height: 2.12rem;*/
        width: 48px;
        height: 53px;
    }
    .greet-car p{
        font-size: 0.24rem;
        line-height: 0.44rem;
        color: #4c4c4c;
        text-align: center;
    }
    .content-line{
        height: auto;
        line-height: 20px;
    }

    .to-nav{
        padding: 0 10px;
        background-color: #fd7d24;
        color: #FFF;
        float: right;
        margin:0 7px;
        border-radius: 3px;
    }
    .big-2x{
        -webkit-transform:scale(2.5);
        transform:scale(2.5);
        margin:0  0 0 -40px !important;
        position: relative;
        left: 50%;
    }
    .tips-list{font-size:0;margin:1px 0 0 0;}
    .tips-list .bg-red{background-color:red;}
    .tips-list .tips{display:inline-block;width:14px;height:14px;font-size:10px;line-height:14px;text-align:center;color:#fff;margin:0 3px 0 0;}

    .pay-success{text-align:center;text-indent:0;}
    .pay-success .img{width:7rem;margin:10px 0 0 0;}
    .pay-success .title{font-size:16px;color:#494949;margin:5px 0 0 0;}
    .pay-success .small{font-size:14px;color:#888;}
    .pay-success .small span{font-size:14px;margin:0 3px;}
</style>

<?php require_once('../common/input_password.php');?>
<!--输入支付密码框-->
<div id="gyuji" style="position:fixed;top:0;z-index:2900;left:0;background:#000000;opacity:0.5;width:100%;height:100%;display:none;"></div>
<div class="am-share" id="pass_w" style="width:100%;position: fixed;top:50px;z-index: 3000;display:none;height:201px">
    <div class="box">
        <span class="guanbicha">x</span>
        <h1>输入支付密码</h1>
        <label for="ipt">
            <ul id="p_pwd" pay_type="card">
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
                <li></li>
            </ul>
        </label>
        <input type="text" id="ipt" maxlength="6">
        <!--     <div style="width:100%;text-align: right;padding:10px;"> <a onclick='xiugai_pass();'>密码管理</a></div>
            <a class="commtBtn" onclick="commitBtn();" style="display:none;">确认</a> -->
    </div>
</div>
<body class="mainBody" data-ctrl=true>
<?php if($blessing_entrance){ ?>
    <a href="/mshop/web/index.php?m=order_blessing&a=index&customer_id=<?php echo $customer_id_en; ?>&batchcode=<?php echo $batchcode; ?>">
        <div class="greet-car">
            <img src="/mshop/web/static/images/greet_car_icon.png"/>
            <!--        <p>贺卡</p>-->
        </div>
    </a>
<?php } ?>
<!-- 	<header data-am-widget="header" class="am-header am-header-default">
		<div class="am-header-left am-header-nav" onclick="history.go(-1)">
			<img class="am-header-icon-custom icon_back" src="./images/center/nav_bar_back.png"/><span>返回</span>
		</div>
	    <h1 class="am-header-title topTitle">订单详情</h1>
	</header>
    <div class="topDiv"></div> --><!-- 暂时隐藏头部导航栏 -->

<!-- 基本地区-开始 -->
<div class="mainArea">
    <div class="order-details">
        <!--        <div class="details-status">-->
        <!--            --><?php
        //            if($status>=0 and $paystatus == 0 and $paystyle!="货到付款" and $sendstatus==0 and $aftersale_state==0 and $p_stu==false){
        //                //未支付
        //                ?>
        <!--                --><?php //if(strtotime($recovery_time)>$currtime){ ?>
        <!--                    <div>-->
        <!--                        <p>等待买家付款</p>-->
        <!--                        <p class="font-size-12">剩<span class="times"></span>自动关闭</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}else{ ?>
        <!--                    <div>-->
        <!--                        <p>订单已失效</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //} ?>
        <!---->
        <!--            --><?php //
        //                }else{
        //                    $check = 1;
        //            ?><!-- -->
        <!--                --><?php //if($status == -1){ ?>
        <!--                    <div>-->
        <!--                        <p>订单已取消</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif(($paystatus==1 or $paystyle=="货到付款") && $status>=0 && $sendstatus==0 && $aftersale_state==0){ ?>
        <!--                    <div>-->
        <!--                        <p>等待卖家发货</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($paystatus==1 && $status >= 0 && $sendstatus == 1 && $aftersale_state==0){ ?>
        <!--                    <div>-->
        <!--                        <p>卖家已发货</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($check == 1 && (($sendstatus==2 && $status==1) || $sendstatus==4 || $sendstatus==6 || $aftersale_state==4 || $return_status==4)){ ?>
        <!--                    <div>-->
        <!--                        <p>交易完成</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($aftersale_type == 1 && $aftersale_state == 1 && $sendstatus == 5){ ?>
        <!--                    <div>-->
        <!--                        <p>退款申请中</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($aftersale_type == 1 && $aftersale_state == 2 && $sendstatus == 6){ ?>
        <!--                    <div>-->
        <!--                        <p>退款完成</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($aftersale_type == 2 && $aftersale_state == 1 && $sendstatus == 3){ ?>
        <!--                    <div>-->
        <!--                        <p>退货申请中</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($aftersale_type == 2 && $aftersale_state == 2 && $sendstatus == 3 && $return_status == 2){ ?>
        <!--                    <div>-->
        <!--                        <p>已确认,等待退货</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($aftersale_type == 2 && $aftersale_state == 2 && $sendstatus == 3 && $return_status == 5){ ?>
        <!--                    <div>-->
        <!--                        <p>退货（买家已退货）</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($aftersale_type == 2 && $aftersale_state == 2 && $sendstatus == 3 && $return_status == 6){ ?>
        <!--                    <div>-->
        <!--                        <p>退货已收货,等待退款</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($aftersale_type == 2 && $aftersale_state == 2 && $sendstatus == 4 && $return_status == 2){ ?>
        <!--                    <div>-->
        <!--                        <p>退货完成</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($sendstatus == 3 && $return_type == 0 && $aftersale_type == 1 && $aftersale_state == 1){ ?>
        <!--                    <div>-->
        <!--                        <p>退货(仅退款)申请中</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($sendstatus == 3 && $return_type == 0 && $aftersale_type == 1 && $aftersale_state == 2){ ?>
        <!--                    <div>-->
        <!--                        <p>退货(仅退款)已同意,待退款</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($sendstatus == 4 && $return_type == 0 && $aftersale_type == 1 && $aftersale_state == 2){ ?>
        <!--                    <div>-->
        <!--                        <p>退货(仅退款)已完成</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($sendstatus == 3 && $return_type == 2 && $aftersale_type == 3 && $aftersale_state == 1){ ?>
        <!--                    <div>-->
        <!--                        <p>换货申请中</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($sendstatus == 3 && $return_type == 2 && $aftersale_type == 3 && $aftersale_state == 2 && $return_status == 2){ ?>
        <!--                    <div>-->
        <!--                        <p>已同意换货,请买家退货</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}elseif($sendstatus == 3 && $return_type == 2 && $aftersale_type == 3 && $aftersale_state == 2 && $return_status == 5){ ?>
        <!--                    <div>-->
        <!--                        <p>买家已退货,等待换货</p>-->
        <!--                    </div>-->
        <!--                    <img src="/mshop/web/static/yundian/img/order_details_3.png"/>-->
        <!--                --><?php //}else{} ?>
        <!--            --><?php //} ?>
        <!--        </div>-->
        <!--待付款-->
        <!--状态图标全部切出，后缀从1到7-->

        <!--			<!-- 订单状态 未支付订单不显示订单状态-->
        <?php if($status>=0 and $paystatus == 0 and $paystyle!="货到付款" and $sendstatus==0 and $aftersale_state==0 and $p_stu==false ){?>
        <div class="divOrderState" style="background-color: #fd7d23;padding:0;">
            <div class="recovery_time">
                <?php if(strtotime($recovery_time)>$currtime){?>
                    <div class="wait_tip">
                        <div class="left_time_top">等待买家付款</div>
                        <div class="left_time_bottom"><span class="times"></span>后支付失效</div>
                    </div>
                    <div class="order_close" style="display:none;">订单已失效</div>
                    <div class="recovery_time_img">
                        <img class="left_time_img" src=".\images\order_image\recovery_time_pay.png">
                        <img class="order_close_img" src=".\images\order_image\recovery_time_close.png" style="display:none;">
                    </div>
                <?php }else{?>
                    <div class="order_close">订单已失效</div>
                    <div class="recovery_time_img">
                        <img class="order_close_img" src=".\images\order_image\recovery_time_close.png">
                    </div>
                <?php }?>
            </div>
            <div style="clear:both;height:0;"></div>
            <?php }else{?>
            <div class="divOrderState">
                <div class="orderState">订单状态</div>
                <div class="line_gray"></div>
                <div id="middle-tab">
                    <div class="area-one comment-mark sel ">
                        <img class="btn_round_status" src="./<?php echo $images_skin?>/order_image/icon_check_orange.png">
                        <div>已提交</div>
                    </div>
                    <?php
                    $check = 1;
                    $status_str = '';
                    if($status == -1){
                        $status_str = '待付款';
                    }

                    if($status>=0 and ($paystatus == 0 and $paystyle!="货到付款") and $sendstatus==0 and $aftersale_state==0){
                        $check = 1;
                        $status_str = '待付款';
                    }else if(($paystatus==1 or $paystyle=="货到付款") && $status>=0 && $sendstatus==0 && $aftersale_state==0){
                        $check = 1;
                        $status_str = '待发货';
                    }else if($paystatus==1 && $status >= 0 && $sendstatus == 1 && $aftersale_state==0){
                        $check = 1;
                        $status_str = '待收货';
                    }else if($status >= 0 && $sendstatus == 2 && $aftersale_state==0){
                        $check = 1;
                        $status_str = '已收货';
                    }else if($aftersale_state > 0 ){
                        $check = 1;
                        $status_str = '售后中';
                    }else if($sendstatus >=3 and $sendstatus<5){
                        $check = 1;
                        $status_str = '退货中';
                    }else if($sendstatus>=5){
                        $check = 1;
                        $status_str = '退款中';
                    }


                    if(!empty($pay_batchcode)){
                        if($paystatus==1 or $paystyle=="货到付款"){
                            $check = 1;
                            $status_str = '待发货';
                            if($sendstatus==2){
                                $status_str = '已收货';
                            } else if($sendstatus==1){
                                $status_str = '待收货';
                            }
                        }else{
                            $check = 1;
                            $status_str = '待付款';
                        }
                    }
                    if( $cstatus == 3 ){
                        $status_str = '待退款';
                    }
                    ?>
                    <div class="area-one comment-mark <?php if(1==$check){echo 'sel';}?>">
                        <div class="lineGray"></div>
                        <?php if(1==$check){?>
                            <img class="btn_round_status" src="./<?php echo $images_skin?>/order_image/icon_check_orange.png">
                        <?php }else{?>
                            <img class="btn_round_status" src="./images/order_image/icon_time_gray.png">
                        <?php }?>
                        <div><?php echo $status_str;?></div>
                    </div>
                    <?php
                    //if(1 == $check && (($as_id>0 && $status==7) || ($as_id<0 && $status==1 && $sendstatus==2))){
                    if($check == 1 && (($sendstatus==2 && $status==1) || $sendstatus==4 || $sendstatus==6 || $aftersale_state==4 || $return_status==4)){
                        ?>
                        <div class="area-one comment-mark sel">
                            <div class="lineGray"></div>
                            <img class="btn_round_status" src="./<?php echo $images_skin?>/order_image/icon_check_orange.png">
                            <div>已完成</div>
                        </div>
                        <?php
                    }else if($status == -1){
                        ?>
                        <div class="area-one comment-mark sel">
                            <div class="lineGray"></div>
                            <img class="btn_round_status" src="./<?php echo $images_skin?>/order_image/icon_check_orange.png">
                            <div>已取消</div>
                        </div>
                        <?php
                    }else{
                        ?>
                        <div class="area-one comment-mark">
                            <div class="lineGray"></div>
                            <img class="btn_round_status" src="./images/order_image/icon_time_gray.png">
                            <div>待完成</div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php }?>
            </div>
            <?php
            $name = '佚名';
            $query2 = "select address,name,phone,location_p,location_c,location_a from weixin_commonshop_order_addresses where batchcode='".$batchcode."'";
            // echo $query2;
            $result2 = _mysql_query($query2) or die('query failed2'.mysql_error());
            while($row2 = mysql_fetch_object($result2)){
                $address 	= $row2->address;		//详细地址
                $name 		= $row2->name;			//收货人姓名
                $phone 		= $row2->phone;			//收货人联系电话
                $location_p = $row2->location_p;	//省份
                $location_c = $row2->location_c;	//市区
                $location_a = $row2->location_a;	//街道/镇区
            }

            if ($or_code <= 0) {
                ?>
                <!-- 收货人信息 -->
                <div class="div_receiver">
                    <div class="div_pos">
                        <img src="./images/order_image/icon_position.png">
                    </div>
                    <div class="div_right">
                        <div class="frame_top">
                            <span class="name" style="width:auto;max-width:25%;margin-right:3%;">收货人&nbsp;:&nbsp;</span>

                            <span class="name" style="width: auto;max-width:29%;margin-right: 3%; text-overflow:ellipsis; white-space:nowrap;"><?php echo $name;?></span>
                            <span class="phone_right"><?php echo $phone;?></span>
                        </div>
                        <div class="frame_bottom">
                            <span>地址&nbsp;:&nbsp;</span><span><?php echo $location_p.$location_c.$location_a.$address;?></span>
                        </div>
                    </div>
                    <div style="clear:both;"></div>
                </div>
            <?php } ?>
            <?php if(!empty($pay_batchcode)){?>
                <div style="height:10px;background-color:#eee;"></div>
                <?php
            }
            if(!empty($pay_batchcode)){
                $query_batchcode = "select batchcode,supply_id,createtime,delivery_time_start,sendstyle,delivery_time_end,yundian_id,yundian_self from weixin_commonshop_orders where isvalid=true and customer_id=".$customer_id." and pay_batchcode='".$pay_batchcode."' group by batchcode";
            }else{


                $query_batchcode = "select batchcode,supply_id,createtime,delivery_time_start,sendstyle,delivery_time_end,yundian_id,yundian_self from weixin_commonshop_orders where isvalid=true and customer_id=".$customer_id." and batchcode='".$batchcode."' group by batchcode";
            }

            $supply_id	 = -1;	//供应商ID
            $createtime	 = '';	//创建时间
            $pid_str 	 = '';	//产品ID字符串
            $prcount_str = '';	//产品数量字符串
            $product_count = 0;	//产品总数
            $delivery_time_start = '';	//配送时间
            $delivery_time_end = '';	//配送时间
            $sendstyle = '';	//配送方式
            $result_batchcode = _mysql_query($query_batchcode) or die('query_batchcode faile：'.mysql_error());
            while($row_batchcode = mysql_fetch_object($result_batchcode)){

            $supply_id  = $row_batchcode->supply_id;
            $createtime = $row_batchcode->createtime;
            $batchcode  = $row_batchcode->batchcode;
            $delivery_time_start  = $row_batchcode->delivery_time_start;
            $delivery_time_end  = $row_batchcode->delivery_time_end;
            $sendstyle  = $row_batchcode->sendstyle;

            $yundian_id = $row_batchcode->yundian_id;
            $yundian_self = $row_batchcode->yundian_self;

            $shop_show_name  = ""; //显示的商城名
            $brand_supply_id = -1; //品牌供应商ID
            if($supply_id>0){

                $sql_supplyname = "select id,brand_supply_name from weixin_commonshop_brand_supplys where isvalid=true and user_id=".$supply_id;
                $result_supply = _mysql_query($sql_supplyname) or die('query sql_supplyname failed3' . mysql_error());
                if ($row_supply = mysql_fetch_object($result_supply)) {
                    $brand_supply_id = $row_supply->id;
                    $shop_show_name  = $row_supply->brand_supply_name;                //店铺名
                }else{
                    $sql_supplyname = "select shopName from weixin_commonshop_applysupplys where isvalid = true and user_id=" . $supply_id;
                    $result_supply = _mysql_query($sql_supplyname) or die('query sql_supplyname failed3' . mysql_error());
                    if ($row_supply = mysql_fetch_object($result_supply)) {
                        $shop_show_name = $row_supply->shopName;                //店铺名
                    }
                }
            }else{
                if($yundian_id > 0 && $yundian_self ==1){   //判断是否是云店的自营订单，输出店铺名
                    // $sql_shopname = 'select realname,contact_name from '.WSY_USER.'.weixin_yundian_keeper where customer_id='.$customer_id.' and id ='.$yundian_id.' ';
                    // $result_shop = _mysql_query($sql_shopname) or die('query sql_shopname failed'.mysql_error());
                    // if($row_shop = mysql_fetch_object($result_shop)) {
                    //     $realname = $row_shop->realname;
                    //     $contact_name = $row_shop->contact_name;
                    // }

                    // if(!empty($contact_name)){
                    //     $shop_show_name = $contact_name."的店铺";
                    // }else{
                    //     $shop_show_name = $realname."的店铺";
                    // }

                    $sql_shopname = 'SELECT wyk.realname,wyk.contact_name,wyk.kepper_img,wu.weixin_name,wyk.store_name FROM '.WSY_USER.'.weixin_yundian_keeper as wyk left join '.WSY_USER.'.weixin_users as wu on wyk.user_id = wu.id where wyk.customer_id='.$customer_id.' and wyk.id ='.$yundian_id;
                    $result_shop = _mysql_query($sql_shopname) or die('Query failed1: ' . mysql_error());
                    while ($row_shop = mysql_fetch_object($result_shop)) {
                        $realname = $row_shop->realname;
                        $contact_name = $row_shop->contact_name;
                        $weixin_name = $row_shop->weixin_name;
                        $store_name = $row_shop->store_name;
                        $brand_logo = $row_shop->kepper_img;
                    }
                    if($store_name==NULL)
                    {
                        if(!empty($weixin_name)){
                            $shop_show_name = $weixin_name."的店铺";
                        }else if(!empty($contact_name)){
                            $shop_show_name = $contact_name."的店铺";
                        }else{
                            $shop_show_name = $realname."的店铺";
                        }
                    }
                    else
                    {
                        $shop_show_name = $store_name;
                    }

                }else{

                    //查询商城名
                    $sql_shopname = "select name from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
                    $result_shop = _mysql_query($sql_shopname) or die('query sql_shopname failed'.mysql_error());
                    if($row_shop = mysql_fetch_object($result_shop)) {
                        $shop_show_name = $row_shop->name;					//商家名
                    }
                }
            }
            ?>
            <!-- 订单的商品目录信息 -->
            <ul class="ui_order_goods" style="<?php if(!empty($pay_batchcode)){echo 'margin:0;border-top:0;';}?>">
                <div class="shopHead">
                    <ul class="am-cf am-avg-sm-1" style="z-index:111;">
                        <li class="tab_right_top" style="margin:0px;">
                            <img class="itemPhotoCheck shopall shopCheck" src="./images/order_image/icon_shop.png">
                            <span onclick="<?php if($brand_supply_id>0){echo "gotoShop(".$supply_id.")";}else{echo "gotoIndex()";}?>" class="am-navbar-label">
                            <span class="shopName"><?php echo $shop_show_name;?><?php if($or_shop_type==2){echo '/'.$o_shop_name.'/'.$o_shop_branch_name;}?></span>
                        </span>
                            <img class="img_shop_right" onclick="<?php if($brand_supply_id>0){echo "gotoShop(".$supply_id.")";}else{echo "gotoIndex()";}?>" src="./images/order_image/btn_right.png">
                        </li>
                    </ul>
                </div>
                <?php


                $sum_curr = 0;//可抵扣购物币
                $query3 = "select pid,rcount,prvalues,prvalues_name,is_exchange,totalprice from weixin_commonshop_orders where isvalid=true and customer_id=".$customer_id." and batchcode='".$batchcode."'";
                $result3 = _mysql_query($query3) or die('query failed3'.mysql_error());
                while($row3 = mysql_fetch_object($result3)){
                    $pid 			= $row3->pid;				//商品ID
                    $rcounts 		= $row3->rcount;			//商品数量
                    $prvalues 		= $row3->prvalues;			//商品属性
                    $prvalues_name 	= $row3->prvalues_name;		//商品属性字符串
                    $pro_totalprice = $row3->totalprice;

                    $is_exchange = $row3->is_exchange;       //是否为换购产品

                    $product_count++;

                    $pid_str = $pid_str.$pid.',';	//产品ID拼接成字符串
                    $prcount_str = $prcount_str.$rcounts.',';	//产品数量拼接成字符串

                    $prvstr = "";
                    if( !empty($prvalues) and !empty($prvalues_name) ){
                        $prvstr = $prvalues_name;
                    } else if( !empty($prvalues) ){
                        $prvarr= explode("_",$prvalues);
                        for($i=0;$i<count($prvarr);$i++){
                            $prvid = $prvarr[$i];
                            if($prvid>0){
                                $parent_id = -1;
                                $prname    = '';

                                $query4 = "select name,parent_id from weixin_commonshop_pros where  id=".$prvid;
                                $result4 = _mysql_query($query4) or die('query failed4'.mysql_error());
                                while($row4 = mysql_fetch_object($result4)){
                                    $parent_id = $row4->parent_id;	//是否子属性
                                    $prname    = $row4->name;		//属性名
                                }
                                $p_prname = '';

                                $query5 = "select name from weixin_commonshop_pros where  id=".$parent_id;
                                $result5 = _mysql_query($query5) or die('query failed5'.mysql_error());
                                while($row5 = mysql_fetch_object($result5)){
                                    $p_prname = $row5->name;		//属性名
                                    $prvstr   = $prvstr.$p_prname.":".$prname."  ";
                                }
                            }
                        }
                    }


                    $query6 = "select id,name,is_virtual,default_imgurl,is_QR,QR_isforever,QR_starttime,QR_endtime from weixin_commonshop_products where customer_id=".$customer_id." and id=";

                    $query6 .= $pid;

                    $result6 = _mysql_query($query6) or die('query failed6'.mysql_error());
                    while($row6 = mysql_fetch_object($result6)){
                        $product_id 			= $row6->id;				//商品ID
                        $product_name 			= $row6->name;				//商品名
                        $product_is_virtual 	= $row6->is_virtual;		//是否虚拟产品
                        $product_default_imgurl = $row6->default_imgurl;	//商品封面图
                        $product_yundian_id = $row6->yundian_id;        //商品云店id
                        /*郑培强*/
                        $is_QR_z                = $row6->is_QR;
                        $QR_isforever           = $row6->QR_isforever;
                        $QR_starttime           = $row6->QR_starttime;
                        $QR_endtime             = $row6->QR_endtime;
                        /*郑培强*/
                    }

                    if(empty($product_default_imgurl)){
                        $query6 = "select imgurl from weixin_commonshop_product_imgs where isvalid=true and customer_id=".$customer_id." and product_id=".$pid." limit 1";

                        $result6 = _mysql_query($query6) or die('query failed6'.mysql_error());
                        while($row6 = mysql_fetch_object($result6)){
                            $product_default_imgurl = $row6->imgurl;	//商品封面图
                        }
                    }

                    /*计算单种产品的税金 start*/
                    $product_tax = 0;
                    $product_tax = get_product_tax($batchcode,$pid)[1];
                    /*计算单种产品的税金 end*/


                    /*-----------------计算产品可抵扣购物币数量----------------*/
                    $currency_percentage = -1;
                    $query = "SELECT currency_percentage from commonshop_product_discount_t where isvalid=true and pid=".$pid;
                    $result = _mysql_query($query) or die('Query_product_currency failed: ' . mysql_error());
                    while ($row = mysql_fetch_object($result)) {
                        $currency_percentage = $row->currency_percentage;
                    }

                    if($currency_percentage<0){//-1使用全局配置
                        $currency_percentage = $percentage;
                    }
                    $product_curr = $pro_totalprice*$currency_percentage;

                    $sum_curr +=  $product_curr;
                    //$sum_curr = round($sum_curr,2);
                    $sum_curr = bcadd($sum_curr,0,2);
                    /*-----------------计算产品可抵扣购物币数量----------------*/
                    ?>
                    <!-- 第一个商品 -->

                    <li class="itemWrapper item_goods button buttonclick" onclick="gotoProductDetail('<?php echo $pid;?>')">
                        <div class="itemMainDiv" <?php if($is_collageActivities==1 && $aftersale_state == 0){echo 'style="padding: 15px 10px 15px 10px;"';}?>>
                            <?php
                            if( $is_collageActivities == 1 || $is_collageActivities == 2) {
                                $collage_type = 1;		//团类型
                                $collage_type_img = '';	//团类型标签
                                $query_collage = "SELECT b.type FROM collage_crew_order_t AS a INNER JOIN collage_group_order_t AS b ON a.group_id=b.id WHERE a.batchcode='".$batchcode."'";
                                $result_collage = _mysql_query($query_collage) or die('Query_collage failed:'.mysql_error());
                                while( $row_collage = mysql_fetch_assoc($result_collage) ) {
                                    $collage_type = $row_collage['type'];
                                }
                                $query_activity1 = "SELECT type_name FROM collage_activities_explain_t WHERE customer_id=".$customer_id." and type=".$collage_type;
                                $result_activity1 = _mysql_query($query_activity1) or die('Query_activity1 failed:'.mysql_error());
                                $collage_type_img = mysql_fetch_assoc($result_activity1)['type_name'];
                                ?>
                                <i class="cell_icon"><?php echo $collage_type_img; ?></i>
                                <?php
                            }
                            ?>
                            <img class="itemPhoto" src="<?php echo $product_default_imgurl;?>">
                            <div class="contentLiDiv">
                                <div class="itemProName">
                                    <span class="goodsName"><?php echo $product_name;?></span>
                                    <span class="goodsPrice">
									<?php if(OOF_P != 2) echo OOF_S ?>
                                    <?php echo ceil(($pro_totalprice/$rcounts)*100)/100; ?>
                                    <?php if(OOF_P == 2) echo OOF_S ?>

									</span>
                                </div>
                                <!--<span class="itemProContent goodsContent"></span>-->
                                <div class="itemProContent goodsSize"><?php echo $prvstr;?><span>x <?php echo $rcounts;?></span></div>

                                <?php if($is_exchange){?>
                                    <div class="tips-list"><span class="tips bg-red">赠</span></div>
                                <?php } ?>
                                <?php
                                $delivery_id 	= -1;	//预配送时间设置id
                                $delivery_name 	= '配送时间';	//预配送活动名称
                                /* 查询是否预配送产品 */
                                $query_delivery = "SELECT p.delivery_id, a.delivery_name FROM weixin_commonshop_pre_delivery_product_relation p INNER JOIN weixin_commonshop_pre_delivery a ON p.delivery_id=a.id WHERE p.pid=".$pid." AND p.isvalid=TRUE AND p.customer_id=".$customer_id." AND a.isvalid=TRUE";
                                $result_delivery = _mysql_query($query_delivery) or die('Query_delivery failed:'.mysql_error());
                                while( $row_delivery = mysql_fetch_object($result_delivery) ){
                                    $delivery_id = $row_delivery -> delivery_id;
                                    $delivery_name = $row_delivery -> delivery_name;
                                }
                                /* 查询是否预配送产品 */
                                if ( $paystatus == 1 && strtotime($delivery_time_start) > 0 && strtotime($delivery_time_end) > 0 ) {
                                    $delivery_time_end_new = explode(' ', $delivery_time_end);
                                    $delivery_time_end_new1 = $delivery_time_end_new[1];
                                    ?>
                                    <div class="itemProContent movetime" style="margin-top:-55px;"><?php echo $delivery_time_start.'-'.$delivery_time_end_new1;?></div>
                                    <?php
                                }
                                $as_tip = '';
                                if( $aftersale_state == 1){
                                    $as_tip = "已申请售后，等待商家确认...";
                                }else if($aftersale_state == 2){
                                    $as_tip = "商家已同意售后申请，正在处理中...";
                                }else if($aftersale_state == 3){
                                    $as_tip = "商家已驳回售后申请，原因:".$aftersale_reason;
                                }else if($aftersale_state == 4){
                                    $as_tip = "售后已处理完成";
                                }
                                if($return_status == 2){
                                    if($return_type == 0){
                                        $as_tip = "商家已同意申请，等待退款中...";
                                    }else if($return_type == 1){
                                        $as_tip = "已同意退货申请";
                                    }else if($return_type == 2){
                                        $as_tip = "已同意换货申请";
                                    }

                                }else if($sendstatus == 3 && $return_status == 0){
                                    if($return_type == 0){
                                        $as_tip = "已申请退货(仅退款),等待商家确认中...";
                                    }else if($return_type == 1){
                                        $as_tip = "已申请退货,等待商家确认中...";
                                    }else if($return_type == 2){
                                        $as_tip = "已申请退货(换货),等待商家确认中...";
                                    }
                                }else if($return_status == 5){
                                    $as_tip = "已退货，等待商家收货";
                                }else if($return_status == 6){
                                    $as_tip = "商家已收货";
                                }else if($return_status == 4){
                                    $as_tip = "已确认退货";
                                }

                                if($sendstatus==6){
                                    $as_tip = "已退款完成";
                                }
                                if($sendstatus==4){
                                    $as_tip = "已退货完成";
                                    if($return_type == 0)$as_tip = "已退款完成";
                                }
                                if(!empty($as_tip)){
                                    ?>

                                    <div class="goodsRedRect" style="float:right;">
                                        <?php echo $as_tip;?>
                                    </div>
                                <?php	}?>
                                <!--行邮税-->
                                <?php if($total_tax_type>1){?>
                                    <div class="tax" code="10008">
                                        <button class="btn-shui"><div class="test5">
                                                <span>税</span></div>
                                            <div style="display:inline-block;padding-right:3px;"><?php echo $tax_name;?>：
                                                <?php if(OOF_P != 2) echo OOF_S ?>
                                                <?php echo $product_tax;?>
                                                <?php if(OOF_P == 2) echo OOF_S ?>
                                            </div>
                                        </button>
                                        <span style="font-size:12px;"></span>
                                    </div>
                                <?php }?>
                                <!--行邮税-->

                            </div>
                            <div style="clear:both"></div>
                        </div>

                    </li>
                <?php }?>
                <?php	if($sendstatus==2 and empty($pay_batchcode) and $aftersale_state == 0 and $is_orderActivist==1 and $is_receipted != 1 and $is_collageActivities == 1 and $after_sale_button == 1) { ?>
                    <!--拼团订单的售后按钮-->
                    <div style="height:1px;float:right;">
                        <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite2" style="width: auto;color: #f37b1d;background-color: #fff;font-size: 14px;line-height: 24px;height: 28px;position: relative;right: 0;top: -32px;">申请售后</span>
                    </div>
                    <?php
                }
                ?>
                <div class="line_white"></div>
                <?php if($sendstatus == 1 && empty($pay_batchcode)){  //已发货?>
                    <div style="height:40px;text-align:center;">
                        <?php 	if($is_delay == 0 && $product_yundian_id ==-1){?>

                            <span onclick="order_delay('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite3 skin-color">延时收货</span>
                        <?php 	}	?>
                        <?php if(($is_pay_on_delivery != 1 || $is_sign == 1) && $is_orderActivist==1 && ($aftersale_state ==0 || $aftersale_state ==3)){
                            if ( $after_sale_button > 0 || ($after_sale_button > 0 && $is_integral_aftersale ==1 && $is_collageActivities==3)) { //CRM16950 ?>

                                <?php if($yundian_id > 0 && $yundian_self ==1) { //HMJ-180503-v384 ?>
                                    <span onclick="toAftersale_yundian_returnorexchange('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite3 skin-color">申请退货</span>
                                <?php } else { ?>
                                    <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite3 skin-color">申请退货</span>
                                <?php } ?>


                            <?php 	}
                        }	?>
                    </div>
                <?php	}
                $origin_price  	= 0;
                $totalprice  	= 0;
                $NoExpPrice   	= 0;
                $ExpressPrice 	= 0;
                $CouponPrice  	= 0;
                $needScore    	= 0;
                $pay_currency 	= 0;
                $card_discount 	= 1;


                //查询订单价格表中的记录
                $sql_price = "select origin_price,price,NoExpPrice,ExpressPrice,CouponPrice,needScore,pay_currency,card_discount,shareholder,O_8reward,tax_money,is_suning_order from weixin_commonshop_order_prices where isvalid=true and batchcode='".$batchcode."'";
                $result_price = _mysql_query($sql_price) or die('Query sql_price failed: ' . mysql_error());
                if ($row_price = mysql_fetch_object($result_price)) {
                    //获取订单的真实价格（可能是折扣总价）
                    $origin_price  = round($row_price->origin_price,2);		//原订单的订单总额(包括运费+行邮税)（不计算优惠）
                    $totalprice    = $row_price->price;				//实付金额
                    $NoExpPrice    = $row_price->NoExpPrice;		//不加运费
                    $ExpressPrice  = $row_price->ExpressPrice;		//运费
                    $CouponPrice   = $row_price->CouponPrice;		//优惠券金额
                    $needScore     = round($row_price->needScore,2);//订单需要积分
                    $pay_currency  = $row_price->pay_currency;		//购物币数量
                    $card_discount = $row_price->card_discount;		//会员卡折扣
                    $O_8reward 	   = $row_price->O_8reward;		//'自购8级分销抵扣',
                    $shareholder   = $row_price->shareholder;		//'自购股东抵扣',
                    $tax_money     = $row_price->tax_money;		//'税费',
                    $is_suning_order     = $row_price->is_suning_order;		//
                }

                if($paystyle=="找人代付"){//找人代付没有购物币
                    $pay_currency = 0;
                }

                /*if($paystatus == 1 || $paystyle == '货到付款'){//没付款不算
                    $price = $totalprice - $pay_currency;
                }else{
                    $price = $totalprice;
                }*/
                $price = $totalprice - $pay_currency;

                $origin_price = round($origin_price - $ExpressPrice,2);
                $sql_changeprice = "select totalprice,id from weixin_commonshop_changeprices where status=1 and isvalid=1 and batchcode='".$batchcode."' order by id desc limit 1";
                $result_cp = _mysql_query($sql_changeprice) or die('Query sql_changeprice failed: ' . mysql_error());

                $change_id    = 0;
                $change_price = 0;

                if ($row_cp = mysql_fetch_object($result_cp)) {
                    $price        = $row_cp->totalprice;
                    $change_id    = $row_cp->id;
                    $change_price = $row_cp->totalprice;

                }
                $card_price = sprintf('%.2f',$origin_price - ($origin_price * $card_discount));//会员卡折扣费用


                $sql_order_status = "select order_status from ".WSY_SHOP.".suning_orders where isvalid=true and batchcode='".$batchcode."'";
                $result_order_status = _mysql_query($sql_order_status) or die('Query sql_order_status failed: ' . mysql_error());
                if ($row_order_status = mysql_fetch_object($result_order_status)) {
                    $order_status    = $row_order_status->order_status;
                }
                ?>
                <div class="horizLineGray"></div>
                <?php
                if( $delivery_id > 0 && $paystatus == 0 && $pay_batchcode == '' && strtotime($recovery_time)>$currtime ){
                    ?>
                    <div class="itemWrapper itemOrderInfo" style="padding-bottom: 5px;height: auto;">
                        <span id="delivery_time_start" style="display: none;"></span>
                        <span id="delivery_time_end" style="display: none;"></span>
                        <span class="text_left_13"><?php echo $delivery_name;?></span>
                        <span class="text_left_13 select_delivery_span_r">
					</span>
                        <span class="text_right_13"><span class="select-delivery-time">选择时间</span><input type="text" id="select-delivery-time" style="display:none;"></span>
                        <div style="clear:both;"></div>
                    </div>
                    <?php
                }
                ?>
                <div class="itemWrapper itemOrderInfo">
                    <span class="text_left_13">商品总价</span>
                    <span class="text_right_13"><?php if(OOF_P != 2) echo OOF_S ?><?php echo $origin_price; ?><?php if(OOF_P == 2) echo OOF_S ?></span>
                </div>
                <?php if( $card_discount < 1 && $card_discount > 0 && $card_price > 0 ){?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">会员卡折扣</span>
                        <span class="text_right_13">-<?php if(OOF_P != 2) echo OOF_S ?><?php echo $card_price; ?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php } ?>
                <?php if( $CouponPrice > 0 ){?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">使用优惠券</span>
                        <span class="text_right_13">-<?php if(OOF_P != 2) echo OOF_S ?><?php echo $CouponPrice; ?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php } ?>
                <?php if( $O_8reward > 0 ){?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">复购推广优惠</span>
                        <span class="text_right_13">-<?php if(OOF_P != 2) echo OOF_S ?><?php echo bcadd($O_8reward,0,2); ?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php } ?>
                <?php if( $shareholder > 0 ){?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">复购店铺优惠</span>
                        <span class="text_right_13">-<?php if(OOF_P != 2) echo OOF_S ?><?php echo bcadd($shareholder,0,2); ?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php } ?>
                <?php if ($or_code == -1) { ?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">运费</span>
                        <span class="text_right_13"><?php if(OOF_P != 2) echo OOF_S ?><?php if($ExpressPrice>0){echo $ExpressPrice;}else{echo '0';}?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php } ?>
                <?php if(($pay_currency > 0) ||  ($paystyle == "货到付款" and $paystatus == 0 and $pay_currency > 0)){?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">使用<?php echo defined('PAY_CURRENCY_NAME')? PAY_CURRENCY_NAME: '购物币'; ?></span>
                        <span class="text_right_13">-<?php if(OOF_P != 2) echo OOF_S ?><?php echo $pay_currency; ?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php } ?>
                <?php if( $needScore > 0 ){?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">使用积分</span>
                        <span class="text_right_13"><?php echo '-'.$needScore; ?></span>
                    </div>
                <?php } ?>

                <?php if($bbt_retuncurr>0){ ?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">返赠<?php echo $custom;?></span>
                        <span class="text_right_13">+<?php if(OOF_P != 2) echo OOF_S ?><?php echo $bbt_retuncurr;?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php }?>
                <?php if( $poundage > 0 ){?>
                    <div class="itemWrapper itemOrderInfo">
                        <span class="text_left_13">手续费</span>
                        <span class="text_right_13"><?php if(OOF_P != 2) echo OOF_S ?><?php echo number_format($poundage, 2, '.', ''); ?><?php if(OOF_P == 2) echo OOF_S ?></span>
                    </div>
                <?php }?>
                <div class="itemOrderMoney">
                    <span class="itemLeft">实付款</span>
                    <span class="itemRight"><?php if(OOF_P != 2) echo OOF_S ?><?php echo number_format(round($price+$poundage,2), 2, '.', '');?><?php if(OOF_P == 2) echo OOF_S ?></span>

                </div>
                <?php if( $store_show > 0 ){?>
                    <div class="itemOrderMoney">
                        <span class="itemLeft"><?php echo $store_str1; ?>:<?php echo $store_str2; ?></span>
                    </div>
                <?php } ?>
                <?php
                //查询订单是否是微米订单 区块链微米
                $query_money = "select block_chain_status,block_chain_reward,block_chain_valid_time,is_block_chain from weixin_commonshop_order_prices where isvalid=true and batchcode='".$batchcode."' AND status = 1 AND is_block_chain = true limit 1";
                $result_data = _mysql_query($query_money) or die('Query_time failed:'.mysql_error());
                while($order_money = mysql_fetch_object($result_data)){
                    $block_chain_status  = $order_money->block_chain_status; //领取状态
                    $block_chain_reward   = $order_money->block_chain_reward; //领取多少积分
                    $block_chain_valid_time   = $order_money->block_chain_valid_time;//过期时间
                    $is_block_chain = $order_money->is_block_chain;//过期时间
                }
                $order_timer = date("Y-m-d H:i:s");
                if ($is_block_chain && $block_chain_status == 2 && $aftersale_type <= 0 && $sendstatus == 2)
                {
                    //查询区块链名称
                    $query_name = "select name from ".WSY_SHOP.".block_chain_setting where customer_id='" . $customer_id . "'";
                    $result_data = _mysql_query($query_name) or die('Query_time failed:'.mysql_error());
                    while($order_money = mysql_fetch_object($result_data)){
                        $block_chain_name  = $order_money->name; //区块链名称
                        if (empty($block_chain_name))
                        {
                            $block_chain_name = '区块链积分';
                        }
                    }
                    ?>
                    <div class="itemOrderMoney">
                        <span class="itemLeft">获得<?php echo $block_chain_name; ?>:+<?php echo $block_chain_reward; ?></span>
                    </div>

                <?php } ?>
                <div class="horizLineGray"></div>

                <?php } ?>
            </ul>
            <?php	//	}
            $shopcode_onoff = 0;		//购物币抵购开关
            $shopcode_limit = 1;		//拼团限制：1-仅团长 2-仅团员 3-团长和团员
            $shopcode_precent = 100;	//拼团购物币抵购比例
            $is_head2 = 0;//未支付拼团订单专用，+2不要影响$is_head的数据
            if( $is_collageActivities ==1 || $is_collageActivities ==2 ){
                $query1 = "SELECT at.shopcode_onoff,at.shopcode_limit,at.shopcode_precent,ot.is_head FROM collage_activities_t AS at INNER JOIN collage_crew_order_t AS ot ON at.id=ot.activitie_id WHERE ot.isvalid=true AND ot.batchcode='".$batchcode."' AND ot.status=1 LIMIT 1";
                $result1 = _mysql_query($query1);
                while($row1 = mysql_fetch_object($result1)){
                    $shopcode_onoff = $row1->shopcode_onoff;
                    $shopcode_limit = $row1->shopcode_limit;
                    $shopcode_precent = $row1->shopcode_precent;
                    $is_head2 = $row1->is_head;
                }
            }
            //加上拼团的开关判断
            if(((( $is_collageActivities ==1 || $is_collageActivities ==2 ) &&( $shopcode_onoff==1 && ($shopcode_limit==3 || ($shopcode_limit==1 && $is_head2==1)|| ($shopcode_limit==2 && $is_head2==2)))) || ( $is_collageActivities==0 || $is_collageActivities==3) ) and $status == 0 and $paystatus == 0 and $paystyle != "货到付款" and $sendstatus == 0 and empty($pay_batchcode) and $isOpenCurrency == 1 and strtotime($recovery_time)>$currtime){

                ?>

                <!--购物币（不能重新使用购物币）-->

                <!--<div id="currency_div" class="itembutton" style="margin-top: -24px;margin-bottom: 30px;">
			  <div class="top">
                <?php
                if($sum_curr>($price-$ExpressPrice-$tax_money)){//不算运费
                    $sum_curr = ($price-$ExpressPrice-$tax_money);
                }

                if($sum_curr < 0) $sum_curr = 0;

                // echo $sum_curr;
                ?>

                <!--总复购金额-->
                <script type="text/javascript">
                    var all_shareholder = '<?php echo $O_8reward+$shareholder;?>';
                </script>

                <?php 		//拼团购物币抵购
                if( $is_collageActivities ==1 || $is_collageActivities ==2 ){
                    if($shopcode_onoff==1 && ($shopcode_limit==3 || ($shopcode_limit==1 && $group_id<0)|| ($shopcode_limit==2 && $group_id>0))){
                        $collage_currency = floor($origin_price*$shopcode_precent*0.01*100)/100;
                        // if($sum_curr > $collage_currency){
                        $sum_curr = $collage_currency;
                        // }
                    }
                }

                ?>


                <!--<span>共有<?php echo $user_curr.$custom;?></span> (可抵扣：<span style="color:red;display: inline;padding: 0px;margin: 0px"><?php echo $sum_curr;?></span></span>)
				<input type="checkbox" id="checkbox_c1" class="chk_3" >
				<label for="checkbox_c1" open_val="0" class="open_curr">
					  <div class="slide_body"></div>
					  <div class="slide_block"></div>
				</label>
			  </div>
			  <div class="currency" style="display:none">
				  <div class="line"></div>
				  <div class="bottom">

					<input class="user_currency" type="number" max="<?php echo $user_curr;?>" placeholder="请输入抵用<?php echo $custom;?>数量">
				  </div>
			  </div>
			</div>-->
                <!--购物币-->
            <?php	}?>
            <!-- 订单编号，各种时间信息 -->
            <div class="infoWrapper" style="padding-bottom:10px;<?php if(!empty($pay_batchcode)){echo 'border-bottom:0;margin-top:0;';}?>">
                <?php
                if($paystyle =="通联分期支付"){
                    $tonglian_sql = "select real_pay_price,allinpay_nper from system_order_pay_log where pay_batchcode='".$pay_batchcode2."'";
                    $res_tonglian = _mysql_query($tonglian_sql)or die('Query failed tonglian_sql 1'.mysql_error());
                    while($row_tonglian= mysql_fetch_object($res_tonglian)){
                        $real_pay_price = $row_tonglian->real_pay_price;
                        $allinpay_nper = $row_tonglian->allinpay_nper;
                    }

                    ?>
                    <span class="text_gray_13">分期详情：￥<?php echo bcdiv($real_pay_price,$allinpay_nper,2);echo "　X　";echo $allinpay_nper."期"?></span></br>
                    <?php
                }
                ?>

                <?php if((!empty($pay_batchcode) || !empty($pay_batchcode2)) && $paystyle !=''){?>
                    <span class="content-line">订单号：<?php echo $batchcode;?></span>
                    <span id="batchcode" class="content-line">支付订单号：<?php if($pay_batchcode){echo $pay_batchcode;}else{echo $pay_batchcode2;} ?>
                    <div id="copy_btn" style="margin-bottom :20px;" class="button buttonclick" data-clipboard-action="copy" data-clipboard-target="#batchcode" data-clipboard-text="<?php if(!empty($pay_batchcode)){echo $pay_batchcode;}else{echo $batchcode;}?>">复制</div>
					</span>
                    <span class="content-line">支付方式：<?php echo $paystyle;?></span>
                <?php }else{ ?>
                    <span id="batchcode" class="content-line">订单号：<?php echo $batchcode;?>
                    <div id="copy_btn" style="margin-bottom :20px;" class="button buttonclick" data-clipboard-action="copy" data-clipboard-target="#batchcode" data-clipboard-text="<?php if(!empty($batchcode)){echo $batchcode;}else{echo $pay_batchcode;}?>">复制</div>
					</span>
                <?php } ?>



                <?php if( $o_shop_id > 0 && $sendstyle == '门店自提' && $paystatus == 1 ){
                    //查询门店信息
                    if($or_shop_type==2) {  //2时为子门店
                        $o_shop = "select branch_name,apply_phone,province,city,area,address,location from ".WSY_DH.".orderingretail_shop_branch where id=" . $o_shop_id;
                        $result_o_shop = _mysql_query($o_shop) or die('Query o_shop failed: ' . mysql_error());
                        if ($row_o_shop = mysql_fetch_object($result_o_shop)) {

                            $shop_name = $row_o_shop->branch_name;
                            $shop_tel = $row_o_shop->apply_phone;
                            $addr_prov = $row_o_shop->province;
                            $addr_city = $row_o_shop->city;
                            $addr_area = $row_o_shop->area;
                            $address = $row_o_shop->address;
                            $location = $row_o_shop->location;
                        }
                    }else{
                        $o_shop = "select shop_name,shop_tel,addr_prov,addr_city,addr_area,address from ".WSY_DH.".orderingretail_shop where id=" . $o_shop_id;
                        $result_o_shop = _mysql_query($o_shop) or die('Query o_shop failed: ' . mysql_error());
                        if ($row_o_shop = mysql_fetch_object($result_o_shop)) {

                            $shop_name = $row_o_shop->shop_name;
                            $shop_tel = $row_o_shop->shop_tel;
                            $addr_prov = $row_o_shop->addr_prov;
                            $addr_city = $row_o_shop->addr_city;
                            $addr_area = $row_o_shop->addr_area;
                            $address = $row_o_shop->address;
                        }
                    }

                    ?>
                    <?php if($system_send_id>0){ ?>
                        <?php if($is_sendorder == 1){?>
                        <span class="content-line">核销码：<?php echo $o_verification_code;?> <span style="float:right;margin-right:10px;"><?php if( $sendstatus != 2){echo '未核销';}else{echo '已核销';}?></span></span>
                        <div id="o_verification_code" style="width: 80px;text-align: center;background: white;opacity: 0.8;padding:10px 0 10px 0;float:none;margin-left:90px;" class="QR_width_nei" onclick="big_img()"></div>
                        <script type="text/javascript">
                            var href = "<?php echo $o_verification_code;?>";
                            $("#o_verification_code").qrcode({
                                render: "canvas", //table方式
                                width: 60, //宽度
                                height:60, //高度
                                text: href //任意内容
                            });
                        </script>
                    <?php }?>
                        <span class="content-line">配送方式：<?php echo $sendstyle;?></span>
                        <span class="content-line">已选门店：<?php echo $shop_name;?> <a <?php if($or_shop_type==1){ ?>href="/addons/index.php/ordering_retail/Shop/shop_list_map?customer_id=<?php echo $customer_id;?>&shop_id=<?php echo $o_shop_id;?>" <?php }else{ ?>href="/addons/index.php/ordering_retail/Shop/shop_branch_navigation?customer_id=<?php echo $customer_id;?>&shop_branch_id=<?php echo $o_shop_id;?>"<?php } ?>  class="to-nav">导航</a></span>
                        <span class="content-line">门店地址：<?php echo $addr_prov.$addr_city.$addr_area.$address;?></span>
                        <span class="content-line">门店电话：<?php echo $shop_tel;?></span>

                    <?php }} ?>
                <?php if(!empty($allipay_orderid)){?>
                    <span class="content-line">支付宝交易号：<?php echo $allipay_orderid;?></span>
                    <?php
                }
                if(strtotime($as_createtime)>0){
                    ?>
                    <span class="content-line">申请售后时间：<?php echo $as_createtime;?></span>
                    <?php
                }else if(strtotime($createtime)>0){
                    ?>
                    <span class="content-line">创建时间：<?php echo $createtime;?></span>
                <?php	}
                if($status == 0 and $paystatus == 0 and $paystyle != "货到付款" and $sendstatus == 0){
                    ?>
                    <span class="content-line">支付失效时间：<?php echo $recovery_time;?></span>
                    <?php
                }
                if(strtotime($paytime) > 0){
                    ?>
                    <span class="content-line">付款时间：<?php echo $paytime;?></span>
                    <?php
                }
                if(strtotime($confirm_sendtime) > 0){
                    ?>
                    <span class="content-line">发货时间：<?php echo $confirm_sendtime;?></span>
                    <?php
                }
                if(strtotime($confirm_receivetime) > 0){
                    ?>
                    <span class="content-line">成交时间：<?php echo $confirm_receivetime;?></span>
                    <?php
                }
                if(strtotime($checktime) > 0){
                    ?>
                    <span class="content-line">商家处理时间：<?php echo $checktime;?></span>
                    <?php
                }
                if($sendstatus == 1){
                    ?>
                    <span class="content-line">自动收货时间：<?php echo $auto_receivetime;?></span>
                    <?php
                }
                ?>
                <?php
                if( $pay_user_id > 0 ){
                    ?>
                    <span class="content-line">代付人：<?php echo $pay_username;?></span>
                    <span class="content-line">代付留言：<?php echo $note;?></span>
                    <?php
                }
                ?>
                <?php if (!empty($camilo_row)) {?>
                    <span class="content-line">关联卡密：<?php echo $camilo_find;?></span>
                    <?php if ($camilo_count > 1) {?>
                        <span class="content-line" id="WSY_camilo_str" style="display: none;"><?php echo $camilo_str;?></span>
                        <span class="content-line" id="WSY_camilo" info="1" style="font-size: 25px;margin-left:45%">︾</span>
                    <?php } ?>
                <?php }?>
            </div>

            <!-- 二维码核销 -->
            <?php if($is_QR && $sendstatus >0){

                $qr_img = "";
                $encrypcode = "";

                $query_qr = "select qr,encrypcode from weixin_commonshop_order_qr where batchcode = '".$batchcode."'";
                $result_qr = _mysql_query($query_qr) or die("query_qr Query_qr error : ".mysql_error());
                $qr_img = mysql_result($result_qr,0,0);
                $encrypcode = mysql_result($result_qr,0,1);
                ?>
                <!--郑培强-->
                <div class="QR_class" style="position: relative;" >
                    <?php
                    if($status==1){
                        echo '<div style="position: absolute;top: 0px;height: 264px;background: white;text-align: center;opacity:0.3;" class="QR_width_wai" ></div>';
                        echo '<div style="position: absolute;top: 32px;height: 200px;width: 200px;background: white;text-align: center;opacity:0.7;" class="QR_width"></div>';
                        echo '<div style="position: absolute;top: 70px;height: 120px;font-size: 22px;width: 120px;text-align: center;background: white;border-radius: 50%;opacity: 0.8;" class="QR_width_nei"><img src="images/QR_status_2.png" ></div>';
                    }else{
                        $QR_now=time();
                        if($QR_isforever==1){
                            $QR_start=strtotime($QR_starttime);
                            $QR_end=strtotime($QR_endtime);
                            if($QR_now<$QR_start){
                                echo '<div style="position: absolute;top: 0px;height: 264px;background: white;text-align: center;opacity:0.3;" class="QR_width_wai" ></div>';
                                echo '<div style="position: absolute;top: 32px;height: 200px;width: 200px;background: white;text-align: center;opacity:0.7;" class="QR_width" ></div>';
                                echo '<div style="position: absolute;top: 70px;height: 120px;font-size: 22px;width: 120px;text-align: center;background: white;border-radius: 50%;opacity: 0.8;" class="QR_width_nei"><img src="images/QR_status_3.png" ></div>';
                            }else{
                                if($QR_end<$QR_now){
                                    echo '<div style="position: absolute;top: 0px;height: 264px;background: white;text-align: center;opacity:0.3;" class="QR_width_wai" ></div>';
                                    echo '<div style="position: absolute;top: 32px;height: 200px;width: 200px;background: white;text-align: center;opacity:0.7;" class="QR_width" ></div>';
                                    echo '<div style="position: absolute;top: 70px;height: 120px;font-size: 22px;width: 120px;text-align: center;background: white;border-radius: 50%;opacity: 0.8;" class="QR_width_nei"><img src="images/QR_status_1.png" ></div>';
                                }
                            }
                        }else if($QR_isforever==2){
                            $QR_end=strtotime($paytime)+strtotime($QR_endtime)-strtotime($QR_starttime)+1;
                            if($QR_end<$QR_now){
                                echo '<div style="position: absolute;top: 0px;height: 264px;background: white;text-align: center;opacity:0.3;" class="QR_width_wai" ></div>';
                                echo '<div style="position: absolute;top: 32px;height: 200px;width: 200px;background: white;text-align: center;opacity:0.7;" class="QR_width" ></div>';
                                echo '<div style="position: absolute;top: 70px;height: 120px;font-size: 22px;width: 120px;text-align: center;background: white;border-radius: 50%;opacity: 0.8;" class="QR_width_nei"><img src="images/QR_status_1.png" ></div>';
                            }
                        }
                    }
                    ?>
                    <script>
                        var QR_width=($(window).width()-200)/2
                        var QR_width_nei=($(window).width()-120)/2
                        $(".QR_width").css("left",QR_width+"px")
                        $(".QR_width_wai").css("width",$(window).width()+"px")
                        $(".QR_width_nei").css("left",QR_width_nei+"px")
                    </script>
                    <a href="../common_shop/jiushop/qr_deliver.php?customer_id=<?php echo $customer_id_en; ?>&batchcode=<?php echo $batchcode;?>&user_id=<?php echo passport_encrypt($user_id);?>&type=1">
                        <img class="tpl-stuff-img" style="margin:0 auto;display:block;" src="<?php echo $new_baseurl."../".$qr_img; ?>"></a>
                    <div style="text-align:center;margin-top:-30px;">
                        <div style="color:gray;opacity:0.6;" >[点击二维码，分享给好友]</div>
                        <?php
                        if($status==1){
                            echo '<div style="color:gray;opacity:0.6;text-decoration:line-through" >核销码：'.$encrypcode.'</div>';
                        }else{
                            $QR_now=time();
                            if($QR_isforever==1){
                                $QR_start=strtotime($QR_starttime);
                                $QR_end=strtotime($QR_endtime);
                                if($QR_now<$QR_start){
                                    echo '<div style="color:gray;opacity:0.6;text-decoration:line-through" >核销码：'.$encrypcode.'</div>';
                                }else{
                                    if($QR_end<$QR_now){
                                        echo '<div style="color:gray;opacity:0.6;text-decoration:line-through" >核销码：'.$encrypcode.'</div>';
                                    }else{
                                        echo '<div >核销码：'.$encrypcode.'</div>';
                                    }
                                }
                            }else if($QR_isforever==2){
                                $QR_end=strtotime($paytime)+strtotime($QR_endtime)-strtotime($QR_starttime)+1;
                                if($QR_end<$QR_now){
                                    echo '<div style="color:gray;opacity:0.6;text-decoration:line-through" >核销码：'.$encrypcode.'</div>';
                                }else{
                                    echo '<div >核销码：'.$encrypcode.'</div>';
                                }
                            }else{
                                echo '<div >核销码：'.$encrypcode.'</div>';
                            }
                        }
                        ?>
                        <!--<div >核销码：</div>-->
                        <div style="margin-top:0px;color:gray;opacity:0.6;line-height:15px;">有效时间：
                            <?php if($QR_isforever==1){
                                //echo substr($QR_starttime,0,10).'—'.substr($QR_endtime,0,10).'可使用';
                                echo $QR_starttime.'至<br/>'.$QR_endtime.'可使用';
                            }else if($QR_isforever==2){
                                $QR_ending=strtotime($paytime)+strtotime($QR_endtime)-strtotime($QR_starttime)+1;
                                //echo date("Y-m-d H:i:s",$QR_ending).'前可使用';
                                echo substr(date("Y-m-d H:i:s",$QR_ending),0,10).'前可使用';
                            }else{
                                echo "永久有效";
                            }?></div>
                    </div>
                </div>
                <!--郑培强-->
                <div class="detail">
                    <div class="mall-order-detail-stuff-list-item-con-name black-font blod-font tpl-stuff-name" style="padding-top: 12px;"><?php if($status==0){echo '<a style="color:#20941e;">(未提货)</a>';}else if($status==1){echo '<a style="color:#f30022;">(已提货)</a>';}else{echo '<a style="color:#5a5a5a;">(已取消)</a>';} ?>提货码:<?php echo $encrypcode; ?></div>
                </div>
            <?php if($_POST["search_class"] == 2) {
            var_dump('zzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzzz');
            ?>
                <script>
                    $.ajax({
                        url: "../back_newshops/Order/order.class.php",
                        type:"POST",
                        data:{'batchcode':batchcode,'op':"send"},
                        dataType:"json",
                        success: function(res){

                        },
                        error:function(res){

                            layer.close(index_layer);
                            layer.alert("网络错误请检查网络");
                        }
                    });
                </script>
            <?php }?>
            <?php }?>
            <!-- 留言，回复信息 -->
            <?php
            if(!empty($remark) && empty($pay_batchcode)){
                ?>
                <div class="comment-frame">
                    <span class="content-line2" style="color:red;">买家留言:</span>
                    <span class="content-line2"><?php echo $remark;?></span>
                </div>
                <?php
                if(!empty($merchant_remark)){
                    ?>
                    <div class="comment-frame">
                        <span class="content-line2" style="color:red;">商家回复:</span>
                        <span class="content-line2"><?php echo $merchant_remark;?></span>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
    <!-- 基本地区-终结 -->
    <div class="copy_tip">
        <span>已复制</span>
    </div>
    <!-- 下面的按钮地区 - 开始 -->
    <div class="white-list">
        <div style="width:100%;">
            <ul class="am-navbar-nav am-cf am-avg-sm-1">
                <li class="tab_right_top" style="margin:0px;">
                    <?php
                    if( $is_collageActivities > 0 ){


                        $query1 = "SELECT ot.group_id FROM collage_activities_t AS at INNER JOIN collage_crew_order_t AS ot ON at.id=ot.activitie_id WHERE ot.isvalid=true AND ot.batchcode='".$batchcode."' AND ot.status>1 LIMIT 1";
                        $result1 = _mysql_query($query1);
                        while($row1 = mysql_fetch_object($result1)){
                            $group_id = $row1->group_id;

                            ?>
                            <span onclick="gotoCollage(<?php echo $group_id; ?>)" class="am-navbar-label btnWhite4" style="width:auto;">拼团详情</span>
                            <?php
                        }

                        if( $is_head == 2 && $cstatus == 2 && $gstatus == 1 ){
                            ?>
                            <?php if($ctype==6 && $sendstatus==0){ }else{?>
                                <?php if($is_collageActivities < 0 ){ ?> <!-- 拼团未完成订单，不可在这里退款 -->
                                    <?php if($is_suning_order==0){ ?>
                                        <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">申请退款</span>
                                    <?php }else if($is_suning_order==1){?>
                                        <?php if(!$order_status){ ?>
                                            <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">申请退款</span>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>

                            <?php
                        }
                    }?>
                    <?php
                    $hour=floor(($currtime-strtotime($paytime))/3600);
                    ?>
                    <?php

                    if($return_status == 2 && ($return_type == 1 || $return_type == 2)) { //同意退货后，仅退款不需要填写退货单号

                        if($yundian != 1) {
                            ?>
                            <span onclick="order_return('<?php echo $batchcode;?>')"

                                  class="am-navbar-label btnWhite4" style="width:auto;">填写退货单</span>
                        <?php } else { ?>

                            <span onclick="order_return_yundian('<?php echo $batchcode;?>')"

                                  class="am-navbar-label btnWhite4" style="width:auto;">填写退货单</span>

                        <?php }
                    }
                    //
                    if($status == 0 && ($paystatus == 1 || $paystyle=="货到付款") && $sendstatus == 0 && $hour >=12 && $is_collageActivities != 2 && $or_shop_type != 2 && $o_verification_code == '' && $product_yundian_id ==-1){ //未确认，已支付||货到付款，未发货
                        //离支付时间已超过12小时则可以提醒发货
                        ?>

                        <span onclick="orderRemind('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">提醒发货</span>
                        <?php
                    }
                    if($status == 0 && $paystatus == 1 && $sendstatus == 0 && $is_orderActivist==1 && ($is_collageActivities == 0 || $is_collageActivities == 3) && $if_refund == 1 ){ ?>

                        <?php if($yundian_id > 0 && $yundian_self ==1) { //HMJ-180503-v384 ?>
                            <span onclick="toAftersale_yundian_refund('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">申请退款</span>
                        <?php } else { ?>

                            <?php if(!empty($store_number_off)){ //判断订单是否与积分有关，售后的按钮显示由商城的售后开关和积分系统的售后开关控制?>
                                <?php if($store_type_off_check == 1 ){  ?>
                                    <?php if($is_suning_order==0){ ?>
                                        <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">申请退款</span>
                                    <?php }else if($is_suning_order==1){?>
                                        <?php if(!$order_status){ ?>
                                            <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">申请退款</span>
                                        <?php } ?>
                                    <?php } ?>
                                <?php }else{}  ?>
                            <?php }else{  ?>
                                <?php if($is_suning_order==0){ ?>
                                    <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">申请退款</span>
                                <?php }else if($is_suning_order==1){?>
                                    <?php if(!$order_status){ ?>
                                        <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">申请退款</span>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>

                        <?php } ?>

                        <?php
                    }

                    if(($paystatus == 1 or $paystyle=="货到付款") && $sendstatus > 0 && $is_QR == 0){ //已支付||货到付款,不在未发货状态
                        ?>

                        <span onclick="check_express('<?php echo $expressnum;?>','<?php echo $expressname;?>','<?php echo $batchcode;?>')" class="am-navbar-label btnWhite4" style="width:auto;">查看物流</span>
                        <?php
                        if($sendstatus == 1){  //已发货
                            ?>

                            <span onclick="order_confirm('<?php echo $batchcode;?>','<?php echo $totalprice;?>','<?php echo $is_receipted;?>')" class="am-navbar-label btnWhite2" style="width:auto;">确认收货</span>
                            <?php
                        }
                    }
                    ?>

                    <?php
                    if($sendstatus == 2){ //发货状态为 ： 已收货
                        if($is_QR)  //如果是核销订单则不判断收货自动结算开关
                        {
                            if($aftersale_state == 0 and $is_orderActivist==1 and ($is_collageActivities == 0 || $is_collageActivities == 3) and $after_sale_button == 1) {
                                ?>

                                <?php if($yundian_id > 0 && $yundian_self ==1) { //HMJ-180503-v384 ?>
                                    <span onclick="toAftersale_yundian_returnorexchange('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite2" style="width:auto;">申请售后</span>
                                <?php } else { ?>

                                    <?php if(!empty($store_number_off)){ //判断订单是否与积分有关，售后的按钮显示由商城的售后开关和积分系统的售后开关控制?>
                                        <?php if($store_type_off_check == 1){  ?>
                                            <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite2" style="width:auto;">申请售后</span>
                                        <?php }else{}  ?>
                                    <?php }else{  ?>
                                        <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite2" style="width:auto;">申请售后</span>
                                    <?php } ?>

                                <?php } ?>

                                <?php
                            }
                        }else{
                            if($aftersale_state == 0 and $is_orderActivist==1 and $is_receipted != 1 and ($is_collageActivities == 0 || $is_collageActivities == 3) and $after_sale_button == 1) {
                                ?>

                                <?php if($yundian_id > 0 && $yundian_self ==1) { //HMJ-180503-v384 ?>
                                    <span onclick="toAftersale_yundian_returnorexchange('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite2" style="width:auto;">申请售后</span>
                                <?php } else { ?>

                                    <?php if(!empty($store_number_off)){ //判断订单是否与积分有关，售后的按钮显示由商城的售后开关和积分系统的售后开关控制?>
                                        <?php if($store_type_off_check == 1){  ?>
                                            <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite2" style="width:auto;">申请售后</span>
                                        <?php }else{}  ?>
                                    <?php }else{  ?>
                                        <span onclick="toAftersale('<?php echo $batchcode;?>')" class="am-navbar-label btnWhite2" style="width:auto;">申请售后</span>
                                    <?php } ?>

                                <?php } ?>

                                <?php
                            }
                        }
                        if($is_discuss == 0 && $aftersale_state == 0 && $yundian_self == 0){  //未评价
                            ?>

                            <span onclick="toEvaluation('<?php echo $batchcode;?>');" class="am-navbar-label btnWhite2" style="width:76px;text-align:center;">评价</span>
                        <?php }else if($is_discuss == 1 && $aftersale_state == 0 ){ //已评 ?>

                            <span onclick="toEvaluation('<?php echo $batchcode;?>');" class="am-navbar-label btnWhite2" style="width:auto;">追加评价</span>
                        <?php }?>
                        <?php
                        //领取区块链积分
                        //查询区块链名称
                        $query_name = "select name from ".WSY_SHOP.".block_chain_setting where customer_id='" . $customer_id . "'";
                        $result_data = _mysql_query($query_name) or die('Query_time failed:'.mysql_error());
                        while($order_money = mysql_fetch_object($result_data)){
                            $block_chain_name  = $order_money->name; //区块链名称
                            if (empty($block_chain_name))
                            {
                                $block_chain_name = '区块链积分';
                            }
                        }
                        if ($block_chain_status != 2 && strtotime($block_chain_valid_time) >= time() && $aftersale_type <= 0 && $sendstatus == 2 && $block_chain_reward > 0) {?>
                            <span onclick="receive_micron(this,'<?php echo $batchcode;?>');" class="am-navbar-label btnWhite2" style="width:auto;">领取<?php echo $block_chain_name; ?></span>
                        <?php } ?>
                        <?php
                    }
                    ?>
                    <?php
                    if($status == 0 and $paystatus == 0 and $sendstatus == 0){ //未确认，未付款状态
                        ?>

                        <span onclick="order_cancel('<?php echo $batchcode;?>');" class="am-navbar-label btnWhite4" style="width:auto;">取消订单</span>
                        <?php


                        if($paystyle != "货到付款" and strtotime($recovery_time)>$currtime){ //货到付款的不需要支付按钮
                            // 测试
                            // if($paystyle != "货到付款"){ //货到付款的不需要支付按钮
                            if($is_payother && $pay_currency==0 && $CouponPrice==0 && $cardDiscount==0 && $needScore==0 && $from_type==1){	//是否开启代付
                                ?>
                                <!--
							<span id="payother_on" onclick="payother('<?php echo $batchcode;?>','<?php echo $payother_desc_id;?>')" class="am-navbar-label btnWhite2" style="width:auto;">找人代付</span>
							<span id="payother_off" class="am-navbar-label btnWhite2" style="display:none;color:grey;border:1px solid grey;width:auto;">找人代付</span>
							-->
                            <?php }?>


                            <span id="newtopay" name="newtopay" onclick="new_order_pay('<?php echo $batchcode;?>','<?php echo $price+$poundage;?>')" class="am-navbar-label btnWhite2">付款</span>
                            <?php
                        }
                    }
                    ?>
                </li>
            </ul>
        </div>
    </div>
    <div class="pay_desc" style="display:none;">
        <span style="font-size:16px;display:block;margin-top:10px;">对你的好友说：</span>
        <textarea class="pay_desc_text" rows="6" cols="32" style="margin-top:10px;resize:none;" placeholder="蛋蛋的忧伤，钱不够了，你能不能帮我先垫付下"></textarea>
        <div class="pay_desc_btn">确定</div>
    </div>
    <div class="shadow" style="display:none;"></div>
    <!-- 支付方式 begin -->
    <!-- <div class=" popup-memu" id = "new_zhifuPannel"></div> --><!-- 新支付方式  -->

    <div class="new-am-dimmer new-am-active am-dimmer am-active" data-am-dimmer="" style="display: none;"></div>
    <div class=" popup-memu" id = "zhifuPannel" style="display: none">
        <div class="list-one popup-menu-title">
            <span class="sub">选择支付方式</span>
        </div>

        <?php if($from_type == 1){
            if( $is_weipay ) {		//目前支持微信端的微信支付，app微信支付暂不支持?>
                <div class="line"></div>
                <div class = "popup-menu-row" data-value="微信支付">

                    <img src="images/np-1.png">
                    <span class="font">微信支付</span>
                </div>

            <?php 	}
        }else{
            if( $is_alipay ) {?>
                <div class="line"></div>
                <div class = "popup-menu-row" data-value="支付宝支付">
                    <img src="images/np-4.png">
                    <span class="font">支付宝支付</span>
                </div>
            <?php 	}
        }
        ?>
        <?php if( $iscard && $card_member_id>0 ) {?>

            <!--<div class="line"></div>
            <div class = "popup-menu-row" data-value="会员卡余额支付">

                <img src="images/np-2.png">
                <span class="font">会员卡余额支付</span>

            </div>-->
        <?php }?>

        <?php if($is_payChange){ ?>
            <!--<div class="line"></div>
            <div class = "popup-menu-row" data-value="零钱支付">

                <img src="images/np-3.png">
                <span class="font">钱包零钱支付</span>
            </div>-->
        <?php } ?>
        <!--<?php if( $is_allinpay ) {?>
		<div class="line"></div>
        <div class = "popup-menu-row" data-value="通联支付">

           <img src="images/np-8.png">
           <span class="font">通联支付</span>
       </div>
	   <?php };?>
	   <?php if( $isshop ) {?>
	   <div class="line"></div>
       <div class = "popup-menu-row" data-value="货到付款">

            <img src="images/np-5.png">
           <span class="font">货到付款</span>
       </div>
	   <?php };?>
	   <?php if( $is_alipay ) {?>
       <div class="line"></div>
       <div class = "popup-menu-row" data-value="支付宝支付">

            <img src="images/np-4.png">
           <span class="font">支付宝支付</span>
       </div>
	   <?php };?>
      <div class="line"></div>
       <div class = "popup-menu-row" data-value="找人代付">

            <img src="images/np-6.png">
           <span class="font">找人代付<span class = "font-small">(指定一位好友帮忙支付)</span></span>
       </div>
	   <?php if( $is_paypal ) {?>
       <div class="line"></div>
        <div class = "popup-menu-row" data-value="PayPal支付">

            <img src="images/np-7.png">
           <span class="font">PayPal</span>
       </div>
	   <?php };?>-->
        <?php if( $is_jdpay ) {?>
            <div class="line"></div>
            <div class = "popup-menu-row" data-value="京东支付">
                <img src="images/np-10.png">
                <span class="font">京东支付</span>
            </div>
        <?php };?>
    </div>
    <!-- 支付方式 end -->
    <div class="am-dimmer am-active" data-am-dimmer="" style="display: none;"></div>
    <?php
    if( $is_collageActivities > 0 ){
        $query1 = "SELECT ot.group_id FROM collage_activities_t AS at INNER JOIN collage_crew_order_t AS ot ON at.id=ot.activitie_id WHERE ot.isvalid=true AND ot.batchcode='".$batchcode."' AND ot.status>1 LIMIT 1";
        $result1 = _mysql_query($query1);
        while($row1 = mysql_fetch_object($result1)){
            $group_id = $row1->group_id;
            ?>
            <div class="white-list">
                <div style="width:100%;">
                    <ul class="am-navbar-nav am-cf am-avg-sm-1">
                        <li class="tab_right_top" style="margin:0px;">
                            <span onclick="gotoCollage(<?php echo $group_id; ?>)" class="am-navbar-label btnWhite4" style="width:auto;">拼团详情</span>
                        </li>
                    </ul>
                </div>
            </div>
            <?php
        }
    }

    ?>
    <!-- 下面的按钮地区 - 终结 -->


</body>
<!--自购奖励抵扣-->
<script type="text/javascript" src="./js/goods/selfbug.js"></script>
<script type="text/javascript" src="./js/global.js?a=<?php echo rand(); ?>"></script>
<script>

</script>
<script type="text/javascript">
    var product_isnone = '<?php echo $product_isnone;?>';

    var customer_id_en = '<?php echo $customer_id_en;?>';
    var user_id		   = '<?php echo $user_id;?>';
    var user_id_en     = '<?php echo $user_id_en;?>';

    var user_id_en	   = '<?php echo passport_encrypt($user_id);?>';
    var batchcode	   = '<?php echo $batchcode;?>';
    var pay_batchcode2 ='<?php echo $pay_batchcode2; ?>';
    var check		   = true;

    var custom		   = '<?php echo $custom;?>';
    var recovery_time  = '<?php echo strtotime($recovery_time);?>';
    var currtime	   = '<?php echo $currtime;?>';
    var paystatus	   = '<?php echo $paystatus;?>';
    var paystyle	   = '<?php echo $paystyle;?>';
    var needScore	   = '<?php echo $needScore;?>';
    var remain_score   = '<?php echo $remain_score;?>';
    var pid_str   	   = '<?php echo $pid_str;?>';
    var prcount_str    = '<?php echo $prcount_str;?>';
    var is_receipt 	   = '<?php echo $is_receipt;?>';
    var totalprice 	   = '<?php echo $totalprice;?>';
    var change_id      = '<?php echo $change_id; ?>';
    var change_price   = '<?php echo $change_price; ?>';
    var CouponPrice    = '<?php echo $CouponPrice;?>';
    var delivery_id    = '<?php echo $delivery_id;?>';
    var is_collage_order = <?php echo $is_collageActivities>0&&$is_collageActivities<3?1:0;?>; //1有效拼团 2无效拼团  3积分
    var pid    			= '<?php echo $pid;?>';
    var rcount    		= '<?php echo $rcounts;?>';
    var group_id    	= '<?php echo $group_id;?>';
    var activitie_id    = '<?php echo $activitie_id;?>';

    var is_collageActivities = '<?php echo $is_collageActivities;?>';
    var origin_price   = '<?php echo $origin_price;?>';       //仅仅产品的总价，用于计算拼团购物币抵购
    var shopcode_onoff  = '<?php echo $shopcode_onoff;?>';
    var shopcode_limit  = '<?php echo $shopcode_limit;?>';
    var shopcode_precent = '<?php echo $shopcode_precent;?>';    //拼团购物币抵购比例

    var yundian         = '<?php echo $yundian_id; ?>';        //云店ID
    var yundian_id      = '<?php echo $yundian_id; ?>';        //云店ID
    var is_from_pay     = '<?php echo $is_from_pay; ?>';

    // 返回上一页操作
    $(function() {
        if(is_from_pay == 1){
            if(yundian_id > 0){
                var url = '/weixinpl/mshop/orderlist.php?customer_id='+customer_id_en+'&currtype=1&yundian='+yundian_id+'&is_from_pay'+is_from_pay;
            }else{
                var url = '/weixinpl/mshop/orderlist.php?customer_id='+customer_id_en+'&currtype=1&is_from_pay='+is_from_pay;
            }
            if (window.history && window.history.pushState) {
                window.addEventListener('load', function() {   //CRM16969，防止苹果手机自动触发返回；无法重现，不知道效果  
                    setTimeout(function() {
                        $(window).on('popstate', function () {
                            window.location.href = url;
                            /*		　　window.history.pushState('forward', null, '#');
                             　　window.history.forward('#');*/  //2018-3-13 HJW屏蔽 苹果手机无法返回
                        });
                    }, 0);
                })
            }
            // window.location.href = url;
            //CRM17421，屏蔽下面两行，↓IE连商城首页都打不开，还必须个毛线啊↓↓↓↓↓↓↓↓
            if(!isIE() && is_collage_order <= 0){
                window.history.pushState('forward', null, '#'); //在IE中必须得有这两行
                window.history.forward('#');
            }

            //加上这条，小程序支付完后点返回不会跳启动页
            window.history.pushState(null,null,'/weixinpl/mshop/orderlist.php?customer_id='+customer_id_en+'&currtype=1&is_from_pay='+is_from_pay);
        }
    })
    function isIE() { //ie?
        if (!!window.ActiveXObject || "ActiveXObject" in window)
            return true;
        else
            return false;
    }

    var is_pay_on_delivery = '<?php echo $is_pay_on_delivery;?>';
    var text_v    		= '请选择配送时间';
    var CouponPrice     = '<?php echo $CouponPrice;?>';
    var is_ban_use_coupon_currency = '<?php echo $is_ban_use_coupon_currency;?>';
    var o_shop_id = '<?php echo $o_shop_id;?>';
    var or_shop_type = '<?php echo $or_shop_type;?>';
    var is_allow_pay = true;
    //判断从哪个端口进入
    <?php
    $page_port="";
    switch ($from_type)
    {
        case 1:
            $page_port='wx';//微信端
            break;
        case 2:
            $page_port='app';
            break;
        case 0:
            $page_port='h5';
            break;
    }
    ?>
    var page_port= '<?php echo $page_port;?>';
    var pid_str_len = pid_str.length;
    pid_str = pid_str.substring(0,pid_str_len-1);	//去最后一个逗号

    var prcount_str_len = prcount_str.length;
    prcount_str = prcount_str.substring(0,prcount_str_len-1);	//去最后一个逗号
    //退货填写退货单
    function order_return(batchcode){
        location.href='orderlist_return.php?customer_id='+customer_id_en+"&batchcode="+batchcode+"&user_id="+user_id_en;
    }
    //退货填写退货单
    function order_return_yundian(batchcode){
        location.href='../../../mshop/web/index?m=yundian&a=return_address_user_in&batchcode='+batchcode+"&customer_id=<?php echo $customer_id_en;?>&yundian=<?php echo $yundian; ?>";
    }


    //支付成功,获得大转盘抽奖次数 start
    var slyder_id       = <?php echo $slyder_id; ?>;
    var slyder_chance   = <?php echo $slyder_chance; ?>;
    var slyder_token    = "<?php echo $slyder_token; ?>";
    var slyder_batchcode= "<?php echo $slyder_batchcode; ?>";
    if( (slyder_chance > 0 || slyder_chance == -1) && slyder_batchcode == batchcode ){
        if( slyder_chance == -1 ){
            slyder_chance = "无限";
        }
        var success_html = '<div class="pay-success">';
        success_html += '<img src="/weixinpl/mshop/images/pay_success.png" class="img"/>';
        success_html += '<p class="title">支付成功</p>';
        success_html += '<p class="small">恭喜你获得<span class="skin-color">'+slyder_chance+'</span>次参与抽奖</p>';
        success_html += '</div>';
        showAlertMsg("",success_html,"确定",go_to_slyder_adventures);
    }
    function go_to_slyder_adventures(){
        var sly_url = '/mshop/web/index.php?m=slyder_adventures&a=index&customer_id='+customer_id_en+'&slyder_id='+slyder_id+"&token="+slyder_token;
        document.location.href = sly_url;
        var history_return_page = '/weixinpl/mshop/orderlist.php?customer_id='+customer_id_en+'&currtype=1';
        history.replaceState({}, '',history_return_page);
    }
    //支付成功,获得大转盘抽奖次数 end


    //新付款开始
    function alert_warning(alert_str){
        showAlertMsg("提示",alert_str,"知道了"); //弹出警告
    }
    //付款
    function new_order_pay(batchcode,totalprice){
        if(product_isnone == 1){
            alert('有商品已经下架，无法购买！');
            return ;
        }

        if(CouponPrice>0){
            $.ajax({
                url: 'coupon_expired.class.php?customer_id='+customer_id_en,
                type: 'post',
                dataType: 'json',
                data: {'user_id':user_id,'batchcode':batchcode},
                success:function(result){
                    console.log(result);
                    if(result.status<0){
                        if(result['status']==-2){
                            showAlertMsg("提示","订单中的优惠券已失效!","知道了");
                            setTimeout(function(){history.go(0);},1000);
                            return false;
                            return;
                        }

                    }
                },
                error:function(data){
                    alert('Ajax error');
                }
            });
        }
        //判断门店库存是否足够
        if(o_shop_id>0 &&or_shop_type>0){
            $.ajax({
                url: '/addons/index.php/ordering_retail/Ordering_Service/check_store_count?customer_id='+customer_id_en,
                type: 'post',
                dataType: 'json',
                async: false,
                data: {'user_id':user_id,'o_shop_id':o_shop_id,'or_shop_type':or_shop_type,'batchcode':batchcode},
                success:function(result){
                    console.log(result);
                    if(result['errcode']!=1000){
                        showAlertMsg("提示",result['errmsg'],"知道了");
                        is_allow_pay = false;
                        setTimeout(function(){history.go(0);},1000);
                        return false;
                        return;
                    }
                },
                error:function(data){
                    alert('Ajax error');
                    return false;
                }
            });
        }


        $.ajax({
            url: 'limitbuy_class.php',
            type: 'post',
            dataType: 'json',
            data: {'customer_id':customer_id_en,'pid_str':pid_str,'user_id':user_id,'pidcount_str':prcount_str,'type':'2'},
            success:function(result){
                if(result.status == -1){
                    showXiangouMsg(result.errmsg);
                    return;
                }
                if(result.limit == 1){
                    showXiangouMsg('付款失败，【'+result.p_name+'】限购，已超过可购买数量，请重新下单，谢谢！','知道了');
                    return;
                }

                if(parseFloat(needScore) > parseFloat(remain_score)){
                    showAlertMsg("操作提示","您的积分不够！","知道了");
                    return;
                }

                // if( totalprice == 0 &&  needScore > 0 ){	//积分支付
                // 	var iptCurrency = $(".user_currency").val();
                // 	var open_curr = $(".open_curr").attr('open_val');
                // 	showConfirmMsg("操作提示","确定使用"+needScore+"积分支付？","确定","取消",function(){
                // 		$.ajax({
                // 			type: "post",
                // 			url: "new_pay_order.php?customer_id=<?php echo $customer_id_en; ?>",
                // 			async: false,
                // 			dataType: 'json',
                // 			data: {'pay_type':"deductible",'batchcode':batchcode,'industry_type':'shop','price':totalprice,'user_id':user_id,'pay_currency':iptCurrency},
                // 			success: function(data){
                // 				if( data.status == 1 ){
                // 					showAlertMsg("提示",data.msg,"知道了");
                // 					setTimeout(function(){location.href="orderlist_detail.php?customer_id="+customer_id_en+"&pay_batchcode="+data.batchcode;},2000);
                // 				}else{
                // 					showAlertMsg("提示",data.msg,"知道了");
                // 				}
                // 			}
                // 		});
                // });
                // 	return;
                // }

                /*自购奖励抵扣 start*/
                check_promoters(user_id,customer_id_en,batchcode);
                check = selfbuy; //重新赋值状态

                /*自购奖励抵扣 end*/

                if(check){
                    check = false;
                    var iptCurrency = $(".user_currency").val();
                    var open_curr = $(".open_curr").attr('open_val');
                    if(iptCurrency != "" && open_curr == 1){
                        if(parseFloat(iptCurrency) > parseFloat(totalprice)){
                            showAlertMsg("操作提示","最多只能使用"+totalprice+"个"+custom,"知道了");
                            check = true;
                            return;
                        }
                        if(parseFloat(iptCurrency) == parseFloat(totalprice)){ //全部使用购物币支付
                            showConfirmMsg("操作提示","是否确定全部使用"+custom+"支付？","支付","取消",function(){
                                check = false;
                                var price=parseFloat(totalprice)-parseFloat(iptCurrency);
                                $.ajax({
                                    type: "post",
                                    url: "new_pay_order.php?customer_id=<?php echo $customer_id_en; ?>",
                                    async: false,
                                    dataType: 'json',
                                    data: {'pay_type':"deductible",'batchcode':batchcode,'industry_type':'shop','price':price,'user_id':user_id,'pay_currency':iptCurrency},
                                    success: function(data){
                                        if( data.status == 1 ){

                                            showAlertMsg("提示",data.msg,"知道了");

                                            setTimeout(function(){location.href="orderlist_detail.php?customer_id="+customer_id_en+"&pay_batchcode="+data.batchcode;},2000);
                                        }else{
                                            showAlertMsg("提示",data.msg,"知道了");
                                        }
                                    }
                                });
                                return;
                            });
                            check = true;
                            return;
                        }
                    }
                    check = true;
                    // 	$.ajax({
                    // 		url: "ajax_show_payway.php",
                    // 		dataType: 'json',
                    // 		type: 'post',
                    // 		data:{'customer_id':customer_id_en,'industry_type':'shop',"page_port":page_port},
                    // 		success:function(result){
                    // 			var content="";
                    // 			if(result.errcode==0){
                    // 			content+='<div class="list-one popup-menu-title">';
                    // 			content+='<span class="sub">选择支付方式</span></div>';
                    // 			for(i=0;i<result.datalist.length;i++){
                    // 			if(result.datalist[i].pay_type!='nopay'){
                    //             content+='<div class="line"></div>';
                    //             content+='<div class = "new_popup-menu-row" data-value="'+result.datalist[i].pay_type+'" onclick="new_popup(this);">';
                    //             content+='<img src="'+result.datalist[i].icon+'">';
                    //             content+='<div class="newdiv"><p class="newfont">'+result.datalist[i].pay_name+'</p>';
                    //             content+='<p class="newzhifup">'+result.datalist[i].description+'</p></div>';
                    //             content+='</div>';
                    //             }
                    // 			}
                    // 			content+='</div>';
                    // 		}
                    // 		$("#new_zhifuPannel").html(content);
                    // 		$(".new-am-dimmer").show();
                    // 		$("#new_zhifuPannel").fadeIn();
                    // 	}
                    // });
                    $.ajax({
                        url: 'limitbuy_class.php',
                        type: 'post',
                        async: false,
                        dataType: 'json',
                        data: {'customer_id':customer_id_en,'pid_str':pid_str,'user_id':user_id,'pidcount_str':prcount_str,'type':'2'},
                        success:function(result){
                            if(result.status == -1){
                                showXiangouMsg(result.errmsg);
                                return;
                            }
                            if(result.limit == 1){
                                showXiangouMsg('付款失败，【'+result.p_name+'】限购，已超过可购买数量，请重新下单，谢谢！','知道了');
                                return;
                            }
                        }
                    });
                    //更新订单购物币
                    var use_currency="<?php echo $pay_currency; ?>";
                    /*if(open_curr == 1){//开关开了再说
                     if(iptCurrency != "" && iptCurrency>=0){
                     var use_currency=iptCurrency;

                     }else{
                     var use_currency="<?php echo $pay_currency; ?>";
                     }
                     }else{//开关都没开肯定没用到购物币啊
                     var use_currency=0;
                     }*/

                    //预配送时间
                    if ( delivery_id > 0 ) {
                        var delivery_time_start = $('#delivery_time_start').text(),
                            delivery_time_end = $('#delivery_time_end').text();

                        if ( delivery_time_start == '' || delivery_time_end == '' ) {
                            showAlertMsg("提示", "请选择时间", "知道了");
                            return;
                        }
                    }

                    /*$.ajax({
                     type: "post",
                     url: "../../wsy_pay/web/function/update_currency.php?customer_id=<?php echo $customer_id_en; ?>",
                     async: false,
                     dataType: 'json',
                     data: {'customer_id':customer_id_en,'batchcode':batchcode,'user_id':user_id,'user_currency':use_currency,'industry_type':'shop'},
                     success: function(data){
                     if( data.status > 1 ){
                     showAlertMsg("操作提示",data.msg,"知道了");
                     return;
                     }else{*/
                    //以数组形式传入订单号
                    var order_batchcode= new Array();
                    order_batchcode[0] = {"batchcode":batchcode};
//								order_batchcode['batchcode']  = new Array();
//								order_batchcode['batchcode'][0] =batchcode;
                    // var price=parseFloat(totalprice)-parseFloat(use_currency);

                    var price=parseFloat(totalprice);

                    if(change_id > 0)
                    {
                        price = change_price;
                    }
//
//								showPayType(customer_id_en , "shop" , page_port , order_batchcode ,price,user_id,pay_batchcode2);

                    var post_data = new Array(1);
                    post_data['industry_type'] = "shop";//行业类型
                    post_data['batchcode_arr'] = order_batchcode;//订单号集合
                    post_data['price'] = price;//支付金额
                    post_data['user_id'] = user_id;
                    post_data['pay_batchcode'] = pay_batchcode2;//支付订单号



                    //判断是否支持找人代付
                    var is_payother = 1;
                    var is_payother_msg ="";
                    if(parseFloat(needScore) > 0){
                        is_payother = 0;
                        is_payother_msg = '找人代付不支付积分产品';
                    }
                    var pay_currency = <?php echo $pay_currency; ?>;
                    var card_price = <?php echo $card_price; ?>;
                    if (parseFloat(CouponPrice) > 0 || parseFloat(pay_currency) > 0 || parseFloat(card_price) > 0)
                    {
                        is_payother = 0;
                        if (custom == '' || custom == undefined)
                        {
                            custom = "优惠抵扣";
                        }
                        is_payother_msg = '找人代付不能使用'+custom;
                    }
                    var open_curr = $(".open_curr").attr('open_val');
                    if(open_curr == 1){
                        is_payother = 0;
                        is_payother_msg = '找人代付不能使用'+custom;
                    }
                    post_data['is_payother'] = is_payother;
                    post_data['is_payother_msg'] = is_payother_msg;

                    post_data['yundian'] = yundian;


                    var json = {};
                    for( i in post_data ){
                        json[i] = post_data[i];
                    }
                    var jsons = JSON.stringify(json);

                    var post_data1 = new Array(1);
                    post_data1['key'] = 'post_data';
                    post_data1['val'] =  jsons;
                    var post_object = [];
                    if(is_allow_pay==true) {
                        post_object.push(post_data1);
                        Turnpay_Post(post_object);
                    }
                    /*}
                     }
                     });*/
                }
            }
        });
    }

    /*POST提交数据*/
    function Turnpay_Post(object,strurl){
        //object:需要创建post数据一对数组 [key:val]

        /* 将GET方法改为POST ----start---*/
        var strurl = "choose_paytype.php?customer_id="+customer_id_en;

        var objform = document.createElement('form');
        document.body.appendChild(objform);


        $.each(object,function(i,value){
            //console.log(value);
            var obj_p = document.createElement("input");
            obj_p.type = "hidden";
            objform.appendChild(obj_p);
            obj_p.value = value['val'];
            obj_p.name = value['key'];
        });

        objform.action = strurl;
        objform.method = "POST"
        objform.submit();
        /* 将GET方法改为POST ----end---*/
    }

    //新找人代付开始
    //	function anotherpay(batchcode){
    //		$.ajax({
    //			url: 'limitbuy_class.php',
    //			type: 'post',
    //			dataType: 'json',
    //			data: {'customer_id':customer_id_en,'pid_str':pid_str,'user_id':user_id,'pidcount_str':prcount_str,'type':'2'},
    //			success:function(result){
    //				if(result.status == -1){
    //					showXiangouMsg(result.errmsg);
    //					return;
    //				}
    //				if(result.limit == 1){
    //					showXiangouMsg('付款失败，【'+result.p_name+'】限购，已超过可购买数量，请重新下单，谢谢！','知道了');
    //					return;
    //				}
    //				if(parseFloat(needScore) > 0){
    //					showAlertMsg("操作提示","积分产品不支持找人代付！","知道了");
    //					return;
    //				}
    //				if(check){
    //					var open_curr = $(".open_curr").attr('open_val');
    //					if(open_curr == 1){
    //						showAlertMsg ("提示：","找人代付不能使用"+custom,"知道了");
    //						return;
    //					}
    //						$('.pay_desc').show();
    //						$('.shadow').show();
    //						$('.pay_desc_btn').click(function(){
    //							if(check){
    //								check = false;
    //								var pay_desc = $('.pay_desc_text').val();
    //								if(pay_desc == '' || (/^\s+$/g).test(pay_desc)){
    //									pay_desc = '蛋蛋的忧伤，钱不够了，你能不能帮我先垫付下';
    //								}
    //								$.ajax({
    //									url: 'save_order_payother.php?customer_id='+customer_id_en,
    //									data:{
    //										batchcode:batchcode,
    //										user_id:user_id,
    //										pay_desc:pay_desc
    //									},
    //									dataType: 'json',
    //									type: 'post',
    //									success:function(res){
    //										var url = "payother_new.php?pay_batchcode="+batchcode+"&customer_id="+customer_id_en+"&payother_desc="+pay_desc;
    //                						document.location = url;
    //										check = true;
    //									},
    //									error:function(er){
    //										check = true;
    //									}
    //								});
    //							}
    //						})
    //
    //				}
    //			}
    //		});
    //	}

    //        function new_popup(obj){
    //     $(".am-dimmer").hide();
    // 	$("#new_zhifuPannel").hide();
    // 	// alert($(obj).data('value'));return;
    // 	var pay_status = true;
    // 	var pay_type = $(obj).data('value');
    // 	var currency = $(".user_currency").val();
    // 	if(currency==""){
    // 		currency=0;
    // 	}
    // 	var open_curr = $(".open_curr").attr('open_val');

    // 	$.ajax({
    // 		url: 'limitbuy_class.php',
    // 		type: 'post',
    // 		dataType: 'json',
    // 		data: {'customer_id':customer_id_en,'pid_str':pid_str,'user_id':user_id,'pidcount_str':prcount_str,'type':'2'},
    // 		success:function(result){
    // 			if(result.status == -1){
    // 				showXiangouMsg(result.errmsg);
    // 				return;
    // 			}
    // 			if(result.limit == 1){
    // 				showXiangouMsg('付款失败，【'+result.p_name+'】限购，已超过可购买数量，请重新下单，谢谢！','知道了');
    // 				return;
    // 			}
    // 			/*混合支付生成购物币订单*/
    // 			if( pay_type != '找人代付' && currency > 0 && open_curr == 1 ){
    // 				pay_status = false;
    // 				$.ajax({
    // 					type: "get",
    // 					url: "orderlist_operation.php",
    // 					async: false,
    // 					dataType: 'json',
    // 					data: "op=order_currency&batchcode="+batchcode+"&currency="+currency+"&customer_id="+customer_id_en,
    // 					success: function(data){
    // 						if( data.status > 1 ){
    // 							showAlertMsg("操作提示",data.msg,"知道了");
    // 							return;
    // 						}
    // 						pay_status = true;
    // 					}
    // 				});
    // 			}
    // 			if(pay_status){
    // 				if( pay_type =='weipay'){
    // 					var	url="./WeChatPay/weipay_new.php?order_id="+batchcode+'&customer_id='+customer_id_en+'&industry_type=shop';
    // 					location.href=url;
    // 				}else if(pay_type == '找人代付'){
    // 				 // var payother_desc_id = result.payother_desc_id;
    // 				 // var url = "payother.php?payother_desc_id="+payother_desc_id+"&customer_id="+customer_id_en;
    // 				}else if(pay_type == '支付宝支付'){
    // 					var	url="./alipay/alipayapi.php?order_id="+batchcode+'&customer_id=<?php echo $customer_id;?>';
    // 				}else if(pay_type == '京东支付'){
    // 					var	url="../jdPay/action/ClientOrder.php?order_id="+batchcode+"&order_type=sign";
    // 				}else if(pay_type=='card' || pay_type=='moneybag'){

    // 					var price=parseFloat(totalprice)-parseFloat(currency);
    // 				$.ajax({
    // 					type: "post",
    // 					url: "new_pay_order.php?customer_id=<?php echo $customer_id_en; ?>",
    // 					async: false,
    // 					dataType: 'json',
    // 					data: {'pay_type':pay_type,'batchcode':batchcode,'industry_type':'shop','price':price,'user_id':user_id},
    // 					success: function(data){
    // 						if( data.status == 1 ){
    // 							showAlertMsg("提示",data.msg,"知道了");
    // 							setTimeout(function(){location.href="orderlist_detail.php?customer_id="+customer_id_en+"&pay_batchcode="+data.batchcode;},2000);
    // 						}else if( data.status == 10012){
    // 									showAlertMsg("提示","会员卡余额不足,请充值后再支付","知道了");
    // 								}else if(data.status == 10011){
    // 									showAlertMsg("提示","钱包余额不足,请充值后再支付","知道了");
    // 								}
    // 					}
    // 				});
    // 				}
    // 				//location.href=url;
    // 			}

    // 		}
    // 	});

    // }

    //新付款结束




    //付款
    function order_pay(batchcode,totalprice){

        if(CouponPrice>0){
            $.ajax({
                url: 'coupon_expired.class.php?customer_id='+customer_id_en,
                type: 'post',
                dataType: 'json',
                data: {'user_id':user_id,'batchcode':batchcode},
                success:function(result){
                    console.log(result);
                    if(result.status<0){
                        if(result['status']==-2){
                            showAlertMsg("提示","订单中的优惠券已失效!","知道了");
                            setTimeout(function(){history.go(0);},1000);
                            return false;
                            return;
                        }

                    }
                },
                error:function(data){
                    alert('Ajax error');
                }
            });
        }

        $.ajax({
            url: 'limitbuy_class.php',
            type: 'post',
            dataType: 'json',
            data: {'customer_id':customer_id_en,'pid_str':pid_str,'user_id':user_id,'pidcount_str':prcount_str,'type':'2'},
            success:function(result){
                if(result.status == -1){
                    showXiangouMsg(result.errmsg);
                    return;
                }
                if(result.limit == 1){
                    showXiangouMsg('付款失败，【'+result.p_name+'】限购，已超过可购买数量，请重新下单，谢谢！','知道了');
                    return;
                }

                if(parseFloat(needScore) > parseFloat(remain_score)){
                    showAlertMsg("操作提示","您的积分不够！","知道了");
                    return;
                }

                if( totalprice == 0 &&  needScore > 0 ){	//积分支付
                    showConfirmMsg("操作提示","确定使用"+needScore+"积分支付？","确定","取消",function(){
                        $.ajax({
                            type: 'get',
                            url: 'orderlist_operation.php',
                            dataType: 'json',
                            data: {
                                'op':'pay_score',
                                'batchcode':batchcode,
                                'customer_id':customer_id_en
                            },
                            success: function(data){
                                if( data.errcode > 1 ){
                                    showAlertMsg("操作提示",data.msg,"知道了");
                                }
                                if( data.errcode == 1 ){
                                    showAlertMsg("操作提示",data.msg,"知道了",function(){
                                        location.href="orderlist.php?customer_id="+customer_id_en+"&currtype=3&user_id="+user_id_en;
                                    });
                                }
                            }
                        });
                    });
                    return;
                }

                if(check){
                    check = false;
                    var iptCurrency = $(".user_currency").val();
                    var open_curr = $(".open_curr").attr('open_val');
                    if(iptCurrency != "" && open_curr == 1){
                        if(parseFloat(iptCurrency) > parseFloat(totalprice)){
                            showAlertMsg("操作提示","最多只能使用"+totalprice+"个"+custom,"知道了");
                            check = true;
                            return;
                        }
                        if(parseFloat(iptCurrency) == parseFloat(totalprice)){ //全部使用购物币支付
                            showConfirmMsg("操作提示","是否确定全部使用"+custom+"支付？","支付","取消",function(){
                                check = false;
                                $.ajax({
                                    type: "get",
                                    url: "orderlist_operation.php",
                                    dataType: 'json',
                                    data: "op=pay_currency&batchcode="+batchcode+"&customer_id="+customer_id_en+"&pay_currency="+iptCurrency,
                                    success: function(data){
                                        showAlertMsg("操作提示",data.msg,"知道了",function(){
                                            if(data.errcode == 1) {
                                                location.href="orderlist.php?customer_id="+customer_id_en+"&currtype=3&user_id="+user_id_en;
                                            }
                                        });
                                    }
                                });
                                return;
                            });
                            check = true;
                            return;
                        }
                    }
                    check = true;
                    togglePan();
                }
            }
        });
    }

    $('.popup-menu-row').click(function(){
        var pay_status = true;
        var pay_type = $(this).data("value");
        var currency = $(".user_currency").val();
        var open_curr = $(".open_curr").attr('open_val');

        $.ajax({
            url: 'limitbuy_class.php',
            type: 'post',
            dataType: 'json',
            data: {'customer_id':customer_id_en,'pid_str':pid_str,'user_id':user_id,'pidcount_str':prcount_str,'type':'2'},
            success:function(result){
                if(result.status == -1){
                    showXiangouMsg(result.errmsg);
                    return;
                }
                if(result.limit == 1){
                    showXiangouMsg('付款失败，【'+result.p_name+'】限购，已超过可购买数量，请重新下单，谢谢！','知道了');
                    return;
                }
                /*混合支付生成购物币订单*/
                if( pay_type != '找人代付' && currency > 0 && open_curr == 1 ){
                    pay_status = false;
                    $.ajax({
                        type: "get",
                        url: "orderlist_operation.php",
                        async: false,
                        dataType: 'json',
                        data: "op=order_currency&batchcode="+batchcode+"&currency="+currency+"&customer_id="+customer_id_en,
                        success: function(data){
                            if( data.status > 1 ){
                                showAlertMsg("操作提示",data.msg,"知道了");
                                return;
                            }
                            pay_status = true;
                        }
                    });
                }
                if(pay_status){
                    if( pay_type =='微信支付'){
                        var	url="./WeChatPay/weipay_single.php?order_id="+batchcode;
                    }else if(pay_type == '找人代付'){
                        // var payother_desc_id = result.payother_desc_id;
                        // var url = "payother.php?payother_desc_id="+payother_desc_id+"&customer_id="+customer_id_en;
                    }else if(pay_type == '支付宝支付'){
                        var	url="./alipay/alipayapi.php?order_id="+batchcode+'&customer_id=<?php echo $customer_id;?>';
                    }else if(pay_type == '京东支付'){
                        var	url="../jdPay/action/ClientOrder.php?order_id="+batchcode+"&order_type=sign";
                    }
                    location.href=url;
                }

            }
        });
    });

    //跳转到供应商页面
    function gotoShop(shopID){
        window.location.href = "my_store/my_store.php?supplier_id="+shopID+"&customer_id="+customer_id_en;
    }

    //跳转到首页
    function gotoIndex(){
        if(yundian_id > 0){
            window.location.href = "../common_shop/jiushop/index.php?customer_id="+customer_id_en+"&yundian="+yundian_id;
        }else{
            window.location.href = "../common_shop/jiushop/index.php?customer_id="+customer_id_en;
        }
    }

    //跳转到产品详情页
    function gotoProductDetail(pid){

        if( is_collage_order ){
            window.location.href = "product_detail.php?pid="+pid+"&customer_id="+customer_id_en+"&is_collage_from=1";
        } else {
            if(yundian_id != '' && yundian_id > 0 ){
                window.location.href = "product_detail.php?pid="+pid+"&customer_id="+customer_id_en+"&yundian="+yundian_id;
            }else{
                window.location.href = "product_detail.php?pid="+pid+"&customer_id="+customer_id_en;
            }
        }
    }

    //收回选择支付div和蒙版
    function togglePan(){
        $(".am-dimmer").toggle();
        $("#zhifuPannel").slideToggle();
    }
    // $('.am-dimmer.am-active').click(function(){togglePan();});
    $('.am-dimmer.am-active').click(function(){
        $(".am-dimmer").hide();
        $("#zhifuPannel").fadeOut();
    });

    //新支付收回选择支付div和蒙版
    $('.new-am-dimmer.new-am-active').click(function(){
        $(".am-dimmer").hide();
        $("#new_zhifuPannel").fadeOut();
    });

    //点击【提醒发货】
    function orderRemind(batchcode){
        if(check){
            check = false;
            $.getJSON("orderlist_operation.php",{batchcode:batchcode,op:"remind",user_id:user_id_en,customer_id:customer_id_en},function(data){
                showAlertMsg ("提示：",data.msg,"知道了");
                check = true;
            });
        }
    }

    //申请延时收货
    function order_delay(batchcode){
        showConfirmMsg("操作提示","只能延迟一次，是否确定申请延迟收货？","申请","取消",function(){
            $.getJSON("orderlist_operation.php",{batchcode:batchcode,op:"delay"},function(data){

                showAlertMsg ("提示：",data.msg,"知道了",function(){
                    location.reload();
                });
            });
        });
    }

    //链接到评价页
    function toEvaluation(batchcode){
        window.location.href = "orderlist_evaluation.php?batchcode="+batchcode+"&customer_id="+customer_id_en+"&user_id="+user_id_en;
    }

    //申请售后
    function toAftersale(batchcode){
        //location.href='orderlist_aftersale.php?batchcode='+batchcode+"&pid="+pid+"&customer_id="+customer_id_en+"&prvalues="+prvalues;
        location.href='orderlist_aftersale.php?batchcode='+batchcode+"&customer_id="+customer_id_en+"&user_id="+user_id_en;
    }

    //HMJ-18-05-03 --云店申请退款
    function toAftersale_yundian_refund(batchcode){
        location.href='../../../mshop/web/index?m=yundian&a=application_for_refund_in&batchcode='+batchcode+"&customer_id=<?php echo $customer_id_en;?>&yundian=<?php echo $yundian; ?>";
    }

    //HMJ-18-05-03 --云店申请售后
    function toAftersale_yundian_returnorexchange(batchcode){
        location.href='../../../mshop/web/index?m=yundian&a=application_for_return_in&batchcode='+batchcode+"&customer_id=<?php echo $customer_id_en;?>&yundian=<?php echo $yundian; ?>";
    }


    //取消订单
    function order_cancel(batchcode){

        if(check){
            showConfirmMsg("提示：","取消后不可恢复，是否确认取消订单？","取消","不取消",function(){
                $.getJSON("orderlist_operation.php",{batchcode:batchcode,op:"cancel"},function(data){
                    showAlertMsg ("提示：",data.msg,"知道了",function(){
                        location.reload();
                    });
                });
            });
        }
    }
    //确认收货
    function order_confirm(batchcode,totalprice,is_receipt){
        showConfirmMsg("提示：","警：确认完成后，订单将进行结算，订单不再受理退货，退款，如若确定商品无误，请点击确认，否则取消。","确认","取消",function(){
            $.getJSON("orderlist_operation.php",{batchcode:batchcode,totalprice:totalprice,op:"confirm"},function(data){
                setTimeout(function(){
                    showAlertMsg("操作提示",data.msg,"知道了");
                    if(is_receipt==1){
                        confirmOrder(batchcode,totalprice);
                    }
                    $(".sharebg-active,.share_btn, .cancel, .close_button").click(function(){
                        location.reload();
                    })
                },300);
            });
        });
    }
    //确认完成订单
    function confirmOrder(batchcode,totalprice){
        $.ajax({
            url:"../back_newshops/Order/order/order.class.php",
            dataType:"json",
            type:"post",
            data:{'batchcode':batchcode,'totalprice':totalprice,'op':"confirm","is_receipt":1}
        });
    }

    //点击【查看物流】
    function check_express(expressNum,expressname,batchcode){
        window.location.href = "/weixinpl/back_newshops/Distribution/settings/kuaidi_head.php?is_web=1&customer_id="+customer_id_en+"&batchcode="+batchcode+"&postid="+expressNum+"&type="+expressname;
        //window.location.href = "http://m.kuaidi100.com/index_all.html?type="+expressname+"&postid="+expressNum+"#result";
        // window.location.href = " https://m.kuaidi100.com/result.jsp?type="+expressname+"&nu="+expressNum;
    }

    //找人代付
    function payother(batchcode,payother_desc_id){

        $.ajax({
            url: 'limitbuy_class.php',
            type: 'post',
            dataType: 'json',
            data: {'customer_id':customer_id_en,'pid_str':pid_str,'user_id':user_id,'pidcount_str':prcount_str,'type':'2'},
            success:function(result){
                if(result.status == -1){
                    showXiangouMsg(result.errmsg);
                    return;
                }
                if(result.limit == 1){
                    showXiangouMsg('付款失败，【'+result.p_name+'】限购，已超过可购买数量，请重新下单，谢谢！','知道了');
                    return;
                }
                if(parseFloat(needScore) > 0){
                    showAlertMsg("操作提示","积分产品不支持找人代付！","知道了");
                    return;
                }
                if(check){
                    var open_curr = $(".open_curr").attr('open_val');
                    if(open_curr == 1){
                        showAlertMsg ("提示：","找人代付不能使用"+custom,"知道了");
                        return;
                    }
                    if(payother_desc_id>0){
                        window.location.href = "payother.php?customer_id="+customer_id_en+"&payother_desc_id="+payother_desc_id;
                    }else{
                        $('.pay_desc').show();
                        $('.shadow').show();
                        $('.pay_desc_btn').click(function(){
                            if(check){
                                check = false;
                                var pay_desc = $('.pay_desc_text').val();
                                if(pay_desc == '' || (/^\s+$/g).test(pay_desc)){
                                    pay_desc = '蛋蛋的忧伤，钱不够了，你能不能帮我先垫付下';
                                }
                                $.ajax({
                                    url: 'save_order_payother.php?customer_id='+customer_id_en,
                                    data:{
                                        batchcode:batchcode,
                                        user_id:user_id,
                                        pay_desc:pay_desc
                                    },
                                    dataType: 'json',
                                    type: 'post',
                                    success:function(res){
                                        window.location.href = "payother.php?customer_id="+customer_id_en+"&payother_desc_id="+res+"&batchcode="+batchcode;
                                        check = true;
                                    },
                                    error:function(er){
                                        check = true;
                                    }
                                });
                            }
                        })
                    }
                }
            }
        });
    }

    $('.shadow').click(function(){
        $('.dlvb').remove();
        $('.pay_desc').hide();
        $('.shadow').hide();
    })

    //打开按钮
    function btn_on(obj){
        //console.log('btn_on');
        //console.log(obj);
        var slide_body = obj.find('.slide_body');
        var slide_block = obj.find('.slide_block');

        slide_block.css({
            left:27+"px",
            boxshadow:"0 1px 2px rgba(0,0,0,0.05), inset 0px 1px 3px rgba(0,0,0,0.1)"
        });
        slide_body.css({
            background:"#fd832f",
            boxShadow:"0 0 1px #fd832f"
        });
        obj.attr('open_val',1);
        $('#payother_on').hide();
        $('#payother_off').show();
    }

    //点击购物币事件

    $(".open_curr").click(function(){
        var open_val = $(this).attr('open_val');
        if(open_val ==0 ){
            if(CouponPrice > 0 && is_ban_use_coupon_currency == 1){
                alertAutoClose('<?php echo defined('PAY_CURRENCY_NAME')? PAY_CURRENCY_NAME: '购物币'; ?>和优惠券不能同时使用',2000);
            }else{
                btn_on($(this));
                $(this).parent().siblings('.currency').show();
                var cur = $('.user_currency').val();
                var cur_remain = <?php echo $user_curr;?>;  //购物币余额
                var max_cur = <?php echo $sum_curr;?>;  //可抵用购物币

                console.log(max_cur);

                //拼团购物币抵购
                var collage_currency = '';
                if( is_collageActivities ==1 || is_collageActivities ==2 ){
                    if(shopcode_onoff==1 && (shopcode_limit==3 || (shopcode_limit==1 && group_id<0)|| (shopcode_limit==2 && group_id>0))){
                        collage_currency = Math.floor(origin_price*shopcode_precent*0.01*100)/100;
                        // if(max_cur > collage_currency){
                        max_cur = collage_currency - all_shareholder;
                        // }
                    }
                }
                console.log(collage_currency);
                console.log(max_cur);

                if(cur_remain>max_cur){
                    max_cur = max_cur;
                }else{
                    max_cur = cur_remain;
                }
                $('.user_currency').val(max_cur);
                check_has_password();
            }
        }else{
            btn_off($(this));
            $(this).parent().siblings('.currency').hide();
        }
    });

    function close_currency_btn(){
        $(".open_curr").click();
    }

    //监听购物币输入框值的变化
    $('.user_currency').bind('input propertychange',function(){
        var cur = $('.user_currency').val();
        var cur_remain = <?php echo $user_curr;?>;  //购物币余额
        var real_pay = <?php echo $totalprice; ?>;  //支付金额
        var max_cur = <?php echo $sum_curr;?>;  //可抵用购物币

        //拼团购物币抵购
        var collage_currency = '';
        if( is_collageActivities ==1 || is_collageActivities ==2 ){
            if(shopcode_onoff==1 && (shopcode_limit==3 || (shopcode_limit==1 && group_id<0)|| (shopcode_limit==2 && group_id>0))){
                collage_currency = Math.floor(origin_price*shopcode_precent*0.01*100)/100;
                // if(max_cur > collage_currency){
                max_cur = collage_currency - all_shareholder;
                // }
            }
        }

        if(cur_remain>max_cur){
            max_cur = max_cur;
        }else{
            max_cur = cur_remain;
        }
        if(!isNaN(cur)){
            if(cur>max_cur){
                $('.user_currency').val(max_cur);
            }
        }
    });

    //关闭按钮
    function btn_off(obj){
        //console.log('btn_off');
        //console.log(obj);

        var slide_body = obj.find('.slide_body');
        var slide_block = obj.find('.slide_block');
        slide_block.css({
            left:0,
            boxshadow:"none"
        });
        slide_body.css({
            background:"none",
            boxShadow:"inset 0 0 0 0 #eee, 0 0 1px rgba(0,0,0,0.4)"
        });
        obj.attr('open_val',0);
        $('#payother_on').show();
        $('#payother_off').hide();
    }

    var startTime ;
    $.ajax({type:"HEAD",url:'/weixinpl/mshop/ajax_get_servertime.php',complete:function(x){ startTime = new Date(x.getResponseHeader("Date")).getTime();}})//获取服务器时间
    var count=0;
    document.addEventListener("visibilitychange", function (e) { //解决锁屏导致倒计时不准确的bug
        $.ajax({type:"HEAD",url:'/weixinpl/mshop/ajax_get_servertime.php',complete:function(x){ startTime = new Date(x.getResponseHeader("Date")).getTime();}})//获取服务器时间
        count=0;
    }, false);
    if(paystatus==0 && paystyle!='货到付款' && is_pay_on_delivery != 1){
        if(recovery_time>currtime){
            timeid = setInterval('times('+recovery_time+')',1000);
        }
    }

    //支付失效倒计时
    function times(recovery_time){
        count++;
        var now_time=startTime+count*1000;

        now_time=now_time.toString().substring(0,10);
        if(now_time==0){
            $('.left_time_top').text('您的网络异常');
            $('.left_time_bottom').hide();
        }
        else{
            $('.left_time_top').text('等待买家付款');
            $('.left_time_bottom').show();

            timestamp = recovery_time-now_time;	//时间差
            console.log(now_time);
            var day = 24 * 60 * 60;
            var hour = 60 * 60;
            var minute = 60;
            var second = 1;
            var days = 0;
            var hours = 0;
            var minutes = 0;
            var seconds = 0;

            if(timestamp >= day){
                days = parseInt(timestamp/day);
                timestamp = timestamp - day * days;
            }
            if(timestamp >= hour){
                hours = parseInt(timestamp/hour);
                timestamp = timestamp - hour * hours;
            }
            if(timestamp >= minute){
                minutes = parseInt(timestamp/minute);
                timestamp = timestamp - minute * minutes;
            }
            if(timestamp >= second){
                seconds = parseInt(timestamp/second);
                timestamp = timestamp - second * seconds;
            }
            var html = '';
            if(days==0 && hours==0 && minutes==0){
                html = seconds+'秒';
            }else if(days==0 && hours==0){
                html = minutes+'分'+seconds+'秒';
            }else if(days==0){
                html = hours+'小时'+minutes+'分'+seconds+'秒';
            }else{
                html = days+'天'+hours+'小时'+minutes+'分'+seconds+'秒';
            }

            $(".times").html(html);		//刷新时间
            if(days==0 && hours==0 && minutes==0 && seconds==0){
                $('.wait_tip').hide();
                $('.left_time_img').hide();
                $('.order_close').show();
                $('.order_close_img').show();
                $('#payother_on').hide();
                $('#payother_off').hide();
                $('#topay').hide();
                $('#newtopay').hide();
                $('#currency_div').hide();
                $('.select-delivery-time').hide();
                clearInterval(timeid);
                if(timestamp>=0)
                {
                    window.location.reload();
                }

            }
        }
    }
</script>
<script>
    /*配送时间*/
    $(function(){
        $('#select-delivery-time').scroller($.extend({preset : 'date'},{
            theme: 'android-ics light',
            mode: 'scroller',
            display: 'modal',
            lang: 'zh',
            onSelect: function(textVale,inst){
                var delivery_time = new Array();
                //获取配送时间段
                $.ajax({
                    url: 'get_delivery_time.php',
                    dataType: 'json',
                    type: 'post',
                    async: false,
                    data: {
                        'delivery_date' : textVale,
                        'delivery_id' : delivery_id
                    },
                    success: function(res){
                        if( res['status'] == 1 ){
                            delivery_time = res['delivery_time'];
                        }
                    },
                    error: function(err){
                        showAlertMsg("提示",err,"知道了");
                    }
                });
                //配送时间段为空，则重新选择日期
                if( delivery_time == '' ){
                    showAlertMsg('提示',textVale+'不在配送时间内！','知道了',function(){
                        $('#select-delivery-time').click();
                    });
                    return;
                }
                //显示配送时间选择框
                deliveryBox('请选择配送时间',delivery_time,'确定','取消',function(){
                    var deliveryDate = $('#select-delivery-time').val(),
                        deliveryTime = $('.dlvb').find('.dlvb-selected').data('val');

                    if( deliveryDate != '' && deliveryTime != '' ){
                        var delivery_time_start = '',
                            delivery_time_end = '';

                        deliveryTimeNew = deliveryTime.split('至');
                        delivery_time_start = deliveryDate+' '+deliveryTimeNew[0];
                        delivery_time_end = deliveryDate+' '+deliveryTimeNew[1];

                        $.ajax({
                            url: 'save_delivery_time.php?customer_id='+customer_id_en,
                            dataType: 'json',
                            type: 'post',
                            data: {
                                delivery_time_start : delivery_time_start,
                                delivery_time_end : delivery_time_end,
                                batchcode : batchcode
                            },
                            success: function(status){
                                $('.select_delivery_span_r').text('预计'+deliveryDate+' '+deliveryTime+'送达');
                                $('#delivery_time_start').text(delivery_time_start);
                                $('#delivery_time_end').text(delivery_time_end);
                            },
                            error: function(err){
                                showAlertMsg("提示",err,"知道了");
                            }
                        });
                    }
                },function(){
                    $('.select_delivery_span_r').text('');
                });
            },
            onClose:function(textVale,inst){
                $('.select_delivery_span_r').text('');
            }
        }));

        $('.select-delivery-time').click(function(){
            $('#select-delivery-time').click();
        });


    })
    //配送时间选择框
    function deliveryBox(title,data,confirm_btn,cancel_btn,callbackfunc,callbackfunc2){
        var html = '';

        html += '<div class="dlvb">';
        html += '	<div class="dlvb-title">'+title+'</div>';
        html += '	<div class="dlvb-content-box">';
        for( i in data ){
            html += '<div class="dlvb-content" data-val="'+data[i]+'"><span>'+data[i]+'</span></div>';
        }
        html += '	</div>';
        html += '	<div class="dlvb-btn">';
        html += '		<span class="dlvb-confirm">'+confirm_btn+'</span>';
        html += '		<span class="dlvb-cancel">'+cancel_btn+'</span>';
        html += '	</div>';
        html += '</div>';
        $('.shadow').show();
        $('body').append(html);
        $('.dlvb-content').eq(0).addClass('dlvb-selected');
        $('.dlvb-confirm').click(function(){
            if( callbackfunc ){
                callbackfunc();
            }
        });
        $('.dlvb-cancel').click(function(){
            if( callbackfunc2 ){
                callbackfunc2();
            }
        });
        $('.dlvb-confirm,.dlvb-cancel').click(function(){
            $('.dlvb').remove();
            $('.shadow').hide();
        });

        $('.dlvb-content').click(function(){
            $('.dlvb-content').removeClass('dlvb-selected');
            $(this).addClass('dlvb-selected');
        });
    }

    $(function(){

        window.addEventListener("popstate", function(e) {
            if(is_collageActivities > 0)
            {
                window.location.href = '/weixinpl/mshop/collageActivities/my_collages_record_list_view.php?customer_id='+customer_id_en+'&user_id='+user_id_en;
            }
        }, false);

    });
</script>

<!--引入微信分享文件----start-->
<script>
    <!--微信分享页面参数----start-->
    debug=false;

    share_url=''; //分享链接
    title=""; //标题
    desc=""; //分享内容
    imgUrl="";//分享LOGO
    share_type=3;//自定义类型

    <!--微信分享页面参数----end-->
</script>
<?php require('../common/share.php');?>
<!--引入微信分享文件----end-->
<script src="./js/clipboard.min.js"></script>
<script>
    //复制(手机微信复制不了)

    var copy_btn = document.getElementById('copy_btn');
    var clipboard = new Clipboard(copy_btn);

    clipboard.on('success', function(e) {
        e.clearSelection();
        $(".copy_tip").fadeIn(200);
        setTimeout(function(){ $(".copy_tip").fadeOut(200); },1500);
    });

    clipboard.on('error', function(e) {
        showAlertMsg ("提示：","长按订单号进行复制","知道了");
    });

    $(".sharebg,.guanbicha,.gyuji").click(function() {
        $('#ipt').val("");
        $('li').text("");
        $("#pass_w").hide();
        $(".sharebg").hide();
        //$("box").hide();
    });

    $('#ipt').on('input', function (e){
        var numLen = 6;
        var pw = $('#ipt').val();
        //alert(pw);
        var list = $('li');
        for(var i=0; i<numLen; i++){
            //alert(pw[i]);
            if(pw[i]){
                //alert(pw[i]);
                $(list[i]).text('·');
            }else{
                $(list[i]).text('');
            }
        }
    });

    function gotoCollage(group_id){
        window.location.href = "collageActivities/activities_detail_view.php?customer_id="+customer_id_en+"&group_id="+group_id;
    }

    function collageAftersale(batchcode){
        showConfirmMsg("操作提示","确定申请退款？","确定","取消",function(){
            $.ajax({
                type: 'get',
                url: 'orderlist_operation.php',
                dataType: 'json',
                data: {
                    'op':'collageAftersale',
                    'batchcode':batchcode,
                    'customer_id':customer_id_en
                },
                success: function(data){
                    showAlertMsg("操作提示",data.errmsg,"知道了",function(){
                        window.location.reload();
                    });
                }
            });
        });
    }

    function big_img(){
        $("#o_verification_code").toggleClass('big-2x');
    }
    var block_chain_reward = '<?php echo $block_chain_reward; ?>'; //需要领取的积分
    var block_chain_name   = '<?php echo $block_chain_name; ?>';
    //领取微米
    function receive_micron(obj,batchcode)
    {
        $(".sharebg").remove();
        var scroll_top = $(window).height(); //浏览器的可视区域高度
        scroll_top = (scroll_top-100)/2;
        $("body").append('<div class="sharebg" style="opacity:0"></div>');
        $(".sharebg").animate({"opacity":1});
        $(".sharebg").append('<div style="width:100px;height:100%;margin: auto;margin-top:'+scroll_top+'px;"><img src="/mshop/web/view/block_chain/img/timg.gif" style="width:100px;height:100px;"></div>');
        $(".sharebg").addClass("sharebg-active");
        $(obj).attr('onclick',false);
        //判定用户是否绑定
        $.ajax({
            url:'/mshop/web/index.php?m=block_chain&a=whether_to_bind',
            data:{'customer_id':customer_id_en,'user_id':user_id,'batchcode':batchcode,'industry_type':'shop'},
            dataType:'json',
            type:'post',
            complete:function(){},
            success:function(res)
            {
                console.log(res);
                if (res.errcode == 1)
                {
                    window.location.href = '/mshop/web/index.php?m=block_chain&a=binding&user_id='+user_id+'&batchcode='+batchcode;
                    $(obj).attr('onclick','receive_micron(this,"'+batchcode+'")');
                }
                else if(res.errcode == 0)
                {
                    $(obj).attr('onclick','receive_micron(this,"'+batchcode+'")');
                    showAlertMsgChain("提示",block_chain_reward+block_chain_name+'领取成功',"知道了");
                    return;
                }
                else
                {
                    $(obj).attr('onclick','receive_micron(this,"'+batchcode+'")');
                    showAlertMsg("提示",res.errmsg,"知道了");
                    return;
                }
            }
        });
    }

    //弹窗
    function showAlertMsgChain(title,content,cancel_btn){
        $(".sharebg").remove();
        $("body").append('<div class="sharebg" style="opacity:0"></div>');
        $(".sharebg").animate({"opacity":1});
        $(".sharebg").addClass("sharebg-active");
        $("body").append('<div class="am-share alert" style="top:100%"></div>');
        $(".alert").animate({"top":0})
        $(".alert").addClass("am-modal-active");
        var html = "";
        html += '<div class = "close_button">';
        html += '<img src = "/weixinpl/mshop/images/info_image/btn_close.png"  width = "30">';
        html += '</div>';
        html += '<div class = "alert_content">';
        html += '  <div class = "dlg_content1_row1" style="text-align:left;">';
        html += '       <span  style="font-size:15px;">'+title+'</span>';
        html += '    </div>';
        html += '<div class = "dlg_content1_row2">';
        html += '    <span style="font-size: 15px;">'+content+'</span>';
        html += '</div>';
        html += '</div>';
        html += '<div style="width: 100%;height: 50px;background: white;border-bottom-left-radius: 7px;border-bottom-right-radius: 7px;">';
        html += '<div class = "dlg_commit cancel1 skin-bg" style="width: 50%;float: left;border-radius: 5px;border-top-left-radius: 0px;border-bottom-right-radius: 0px;border-top-right-radius: 0px;">';
        html += '    <span>查看</span>';
        html += '</div>';
        html += '<div class = "dlg_commit cancel skin-bg" style="float: right;width: 50%;border-radius: 5px;border-bottom-left-radius: 0px;border-top-right-radius: 0px;border-top-left-radius: 0px;background-color: #f3f3f3!important;">';
        html += '    <span style="color: #1c1f20;">'+cancel_btn+'</span>';
        html += '</div>';
        html += '</div>';
        $(".alert").html(html);
        // dialog cancel_btn按键点击事件  
        $(".sharebg-active,.share_btn, .cancel, .close_button").click(function()
        {
            $(".sharebg").remove();
            $('.alert').remove();
            window.location.reload();
        })
        $('.cancel1').click(function()
        {
            window.location.href = '/mshop/web/index.php?m=block_chain&a=Block_chain_integral&user_id=<?php echo passport_encrypt((string)$user_id) ?>&customer_id=<?php echo $customer_id_en; ?>';
        })
    }

</script>
<script>
    //卡密隐藏与显示
    $("#WSY_camilo").click(function(){
        if ($("#WSY_camilo").attr('info') == 1) {
            $("#WSY_camilo_str").show('slow');
            $("#WSY_camilo").attr('info',2);
        } else {
            $("#WSY_camilo_str").hide('slow');
            $("#WSY_camilo").attr('info',1);
        }

    });
</script>
<!--引入侧边栏 start-->
<?php
require_once('../common/utility_setting_function.php');
// include_once("foot.php");
$fun = "order_detail";
$is_publish = check_is_publish(2,$fun,$customer_id);
$nav_is_publish = check_nav_is_publish($fun,$customer_id);
include_once('float.php');
?>
<!--引入侧边栏 end-->
</body>
</html>