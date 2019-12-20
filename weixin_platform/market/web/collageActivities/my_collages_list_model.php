<?php

$pagesize = 5;//每页显示数据数
$start = $pageNum * $pagesize;

$query = "SELECT  wco.supply_id, wco.batchcode,wco.id,wco.pid,wco.rcount,wco.is_payother,wco.paystyle,wco.sendstatus,wco.totalprice,wco.status,wco.sendstyle,wco.remark,wco.createtime,wco.paystatus,wco.address_id,wco.express_id,wco.is_discuss,wco.confirm_receivetime,wco.auto_receivetime,wco.is_delay,wco.expressnum,wco.return_type,wco.return_status,wco.aftersale_state,wco.aftersale_reason,wco.prvalues,wco.prvalues_name,wco.paytime,wco.is_QR,cot.group_id,cgot.status as cstatus 
					FROM collage_crew_order_t AS cot 
					LEFT JOIN weixin_commonshop_orders AS wco ON cot.batchcode=wco.batchcode 
					LEFT JOIN collage_group_order_t AS cgot ON cgot.id=cot.group_id 
					WHERE cot.isvalid=true and cot.customer_id=" . $customer_id . "  and cot.user_id=" . $user_id ." and wco.is_collageActivities>0 ";
					
if( !empty( $start_time ) ){
	$query .= " AND cot.createtime>='".$start_time."'";
}
if( !empty( $end_time ) ){
	$query .= " AND cot.createtime<='".$end_time."'";
}
switch ($currtype) {
	case 1:
			// 所有订单
	break;
		case 2: //待付款

		$query = $query . " and wco.status = 0  and wco.paystatus = 0";
		break;
		case 3: // 待发货
		$query = $query . " and wco.paystatus=1 and wco.status = 0 and wco.sendstatus = 0 ";
		break;
		case 4: //待收货
		$query = $query . " and wco.paystatus=1 and wco.status = 0 and wco.sendstatus = 1";
		break;
		case 5: //待评价
		$query = $query . " and (wco.status = 0 or wco.status = 1)  and wco.sendstatus = 2 and wco.is_discuss = 0 ";
		break;
		case 6: //售后中
		$query = $query . " and (wco.sendstatus > 2 || wco.aftersale_type > 0)";
		break;

	}
	$query .= " limit ".$start.",".$pagesize."";
$pid = -1;
$is_QR = -1;
$rcount = 0;
$status = -1;
$remark = '';
$paytime = '';
$cstatus = -1;
$order_id = -1;
$group_id = -1;
$paystyle = '';
$prvalues = '';
$is_delay = -1;
$supply_id = -1;
$batchcode = '';
$sendstyle = -1;
$paystatus = -1;
$totalprice = 0;
$createtime = '';
$sendstatus = -1;
$expressnum = -1;
$address_id = -1;
$express_id = -1;
$is_discuss = -1;
$is_payother = -1;
$return_type = -1;
$return_status = -1;
$prvalues_name = '';
$pro_totalcount = 0;
$aftersale_state = -1;
$auto_receivetime = '';
$aftersale_reason = '';
$confirm_receivetime = '';

$result = _mysql_query($query) or die('Query OrderList failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
	$order_id = $row->id;
	$rcounts = $row->rcounts;
	$createtime = $row->createtime;
	$pid = $row->pid;
	$paystyle= $row->paystyle;
	$paystatus = $row->paystatus;
	$sendstyle=$row->sendstyle;
	$rcount = $row->rcount;
	$pro_totalprice= $row->totalprice;
	$all_goodsprice=$row->totalprice;
	$batchcode = $row->batchcode;
	$sendstatus = $row->sendstatus;
	$status = $row->status;
	$express_id = $row->express_id;
	$supply_id = $row->supply_id;//供应商ID
	$is_discuss = $row->is_discuss;  //是否评论 0:无 1:评论 2:追加
	$confirm_receivetime = $row->confirm_receivetime;   //收货时间
	$auto_receivetime = $row->auto_receivetime;
	$is_delay = $row->is_delay;
	$return_type = $row->return_type;
	$return_status = $row->return_status;
	$aftersale_state = $row->aftersale_state;
	$aftersale_reason = $row->aftersale_reason;
	$prvalues = $row -> prvalues;
	$prvalues_name = $row -> prvalues_name;
	$cstatus = $row -> cstatus;
	$paytime = $row -> paytime;
	$expressnum = $row -> expressnum;	//快递单号
	$date=0;
	$date=floor((strtotime($now)-strtotime($confirm_receivetime))/86400);    //计算收货时间与现在相差时间
	$is_QR = $row -> is_QR;
	$group_id = $row -> group_id;
	
	 if($supply_id > 0) { //如有存在供应商编号 ，则查询供应商名称
		$sql_supplyname = "select id,brand_supply_name from weixin_commonshop_brand_supplys where isvalid=true and user_id=".$supply_id;
		$result_supply = _mysql_query($sql_supplyname) or die('query sql_supplyname failed3' . mysql_error());
		if ($row_supply = mysql_fetch_object($result_supply)) {		//查询品牌供应商
			$brand_supply_id = $row_supply->id;                
			$shop_show_name  = $row_supply->brand_supply_name;      //店铺名
		}else{
			$sql_supplyname = "select shopName from weixin_commonshop_applysupplys where isvalid = true and user_id=" . $supply_id;
			$result_supply = _mysql_query($sql_supplyname) or die('query sql_supplyname failed3' . mysql_error());
			if ($row_supply = mysql_fetch_object($result_supply)) {		//普通供应商
				$shop_show_name = $row_supply->shopName;                //店铺名
			}
		}
	}else{
		//查询商城名
		$sql_shopname = "select name from weixin_commonshops where isvalid=true and customer_id=".$customer_id;
		$result_shop = _mysql_query($sql_shopname) or die('query sql_shopname failed'.mysql_error());
		if($row_shop = mysql_fetch_object($result_shop)) {
			$shop_show_name = $row_shop->name;					//商家名
		}
	}

	//获取订单行邮税总和
	$total_tax = 0;
	$get_tax_result = get_tax_result($batchcode);
	$total_tax = $get_tax_result[1];
	$total_tax_type = $get_tax_result[0];
	//获取行邮税类型名称
	$tax_name = get_tax_name($total_tax_type);	
	/*行邮税*/	

	$totalprice = 0;
	$sql_changeprice = "select totalprice from weixin_commonshop_changeprices where status=1 and isvalid=1 and batchcode='" . $batchcode . "' order by id desc limit 1";
	$result_cp = _mysql_query($sql_changeprice) or die('Query sql_changeprice failed: ' . mysql_error());
	if ($row_cp = mysql_fetch_object($result_cp)) {
		$totalprice = $row_cp->totalprice;
	} else {
		//查询订单价格表中的记录
		$sql_price = "select price,NoExpPrice,ExpressPrice from weixin_commonshop_order_prices where isvalid=true and batchcode='" . $batchcode . "'";
		$result_price = _mysql_query($sql_price) or die('Query sql_price failed: ' . mysql_error());
		if ($row_price = mysql_fetch_object($result_price)) {
			//获取订单的真实价格（可能是折扣总价）
			$totalprice = $row_price->price;
			$express_price = $row_price->ExpressPrice;
		}
	}	
	
	$currtime = time();		//当前时间
	$recovery_time = '';	//支付失效时间
	$query_time = "select recovery_time from weixin_commonshop_order_prices where isvalid=true and batchcode='".$batchcode."' limit 1";
	$result_time = _mysql_query($query_time) or die('Query_time failed:'.mysql_error());
	while($row_time = mysql_fetch_object($result_time)){
		$recovery_time = $row_time->recovery_time;
	}
	
	 /* 商品属性 */
        $query6 = "select id,name,orgin_price,now_price,is_virtual,default_imgurl from weixin_commonshop_products where  customer_id=".$customer_id." and id=".$pid;
        $result6 = _mysql_query($query6) or die('query failed6'.mysql_error());
        while($row6 = mysql_fetch_object($result6)){
            $product_id = $row6->id;						//商品ID
            $product_name = $row6->name;					//商品名
            $product_orgin_price = $row6->orgin_price;		//商品原价
            $product_now_price = $row6->now_price;			//商品现价
            $product_is_virtual = $row6->is_virtual;		//是否虚拟产品
            $product_default_imgurl = $row6->default_imgurl;//商品封面图
        }
?>
<div class="line">
	<div class="top">
		<img class="shop" src="img/icon_shop.png" />
		<span class="shopName" onclick="<?php if($brand_supply_id>0){echo "gotoShop(".$supply_id.")";}else{echo "gotoIndex()";}?>"><?php echo $shop_show_name; ?></span>
		<img class="arrow" src="img/icon2.png" />
		<?php 
			if($status == -1){
				//$status_str = '待付款';
				$status_str = '已取消';
			}

			if($status>=0 and ($paystatus == 0 and $paystyle!="货到付款") and $sendstatus==0 and $aftersale_state==0){
				$status_str = '待付款';
			}else if(($paystatus==1 or $paystyle=="货到付款") && $status>=0 && $sendstatus==0 && $aftersale_state==0){
				$status_str = '待发货';
			}else if($paystatus==1 && $status >= 0 && $sendstatus == 1 && $aftersale_state==0){
				$status_str = '已付款';
			}else if($status >= 0 && $sendstatus == 2 && $aftersale_state==0){
				if($is_discuss == 0 && $aftersale_state == 0){ 
					$status_str = '待评价';
				}else if($is_discuss == 1 && $aftersale_state == 0 ){
					$status_str = '已评价';
				}
			}else if($aftersale_state > 0 || $sendstatus >=3 ){
				$status_str = '售后中';
			}
			if($sendstatus==6){
				$status_str = "已退款";
			}
			if($sendstatus==4){
				$status_str = "已退货";
			}
			if( $cstatus == 4 ){
				$status_str = '待抽奖';
			} 
		?>
		<span class="state"><?php echo $status_str; ?></span>
	</div>
	<div class="content" onclick="gotoProductOrder('<?php echo $batchcode;?>')">
		<img class="detImg" src="<?php echo $product_default_imgurl; ?>" />
		<div class="detOrder">
			<p class="order1"><?php echo $product_name; ?></p>
			<p class="order2"><?php echo $prvalues_name; ?></p>
		</div>
		<div class="detOrder detOrderLeft">
			<p class="order3">￥<?php echo $product_now_price; ?></p>
			<p class="order4">x<?php echo $rcount; ?></p>
		</div>
	</div>
	<div class="bottom">
		<p>共<?php echo $rcount; ?>件商品  合计￥<span class="t1"><?php echo $totalprice; ?></span><span class="t2">
		<?php if( $express_price > 0 ){ ?>
		(运费￥<?php echo $express_price; ?>)
		<?php }else{ ?>
		(免运费)
		<?php } ?>
		</span></p>
	</div>
	<?php 
//			if($status == -1){
//				echo '<button class="comment" onclick="topay('.$batchcode.')">去付款</button>';
//				echo '<button class="lineBtn" onclick="order_cancel(\''.$batchcode.'\');">取消订单</button>';
//			}
			if($status>=0 and ($paystatus == 0 and $paystyle!="货到付款") and $sendstatus==0 and $aftersale_state==0){
				echo '<button class="comment" onclick="topay(\''.$batchcode.'\')">去付款</button>';
				echo '<button class="lineBtn"  onclick="order_cancel(\''.$batchcode.'\');">取消订单</button>';
			}else if(($paystatus==1 or $paystyle=="货到付款") && $status>=0 && $sendstatus==2 && $aftersale_state==0){
				echo '<button class="lineBtn" onclick="check_express('.$expressnum.')">查看物流</button>';
				echo '<button class="comment" onclick="order_confirm(\''.$batchcode.'\','.$totalprice.')">确认收货</button>';
			}else if($paystatus==1 && $status >= 0 && $sendstatus == 1 && $aftersale_state==0){
				echo '<button class="lineBtn" onclick="toAftersale(\''.$batchcode.'\')">申请退款</button>';
			}else if($status >= 0 && $sendstatus == 1 && $aftersale_state==0){
				if($is_discuss == 0 && $aftersale_state == 0){ 
					echo '<button class="lineBtn" onclick="toAftersale(\''.$batchcode.'\')">申请售后</button>';
					echo '<button class="lineBtn" onclick="toEvaluation(\''.$batchcode.'\');">评价</button>';
				}else if($is_discuss == 1 && $aftersale_state == 0 ){
					echo '<button class="lineBtn">查看评价</button>';
				}
			}
			 
		?>
	
	<div style="clear: both;"></div>
</div>
<?php
}
?>