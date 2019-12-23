<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link =mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../../weixinpl/proxy_info.php');
_mysql_query("SET NAMES UTF8");

require_once('../../../../weixinpl/function_model/collageActivities.php');
require_once('../../../../weixinpl/common/utility_common.php');
$collageActivities = new collageActivities($customer_id);

$keyid = -1;
if( !empty($_GET['keyid']) ){
	$keyid = $configutil->splash_new($_GET['keyid']);
}
$comeFrom = 1;
if( !empty($_GET['comeFrom']) ){
	$comeFrom = $configutil->splash_new($_GET['comeFrom']);
}

$condition = array(
	'customer_id' => $customer_id,
	'isvalid' => true,
	'id' => $keyid
);
$field = " id,status,name,type,start_time,end_time,group_size,user_level,number,createtime,luck_draw_num,luck_split_money,if_curr_pay,coefficient,return_curr,head_times,if_change_pro,if_refund,if_return_pro,ginseng_num,is_since,shopcode_onoff,shopcode_limit,shopcode_precent,coupon_onoff,alone_onoff ";
$activity_info = $collageActivities -> getActivitiesMes($condition,$field)['data'][0];
$user_level = $activity_info['user_level'];
$user_level_arr = explode('_',$user_level);
$activity_type = $activity_info['type'];

if($activity_info['alone_onoff']=='' || $activity_info['alone_onoff']==null){
    $activity_info['alone_onoff'] = 1;
}

if(!empty($activity_info['shopcode_limit'])){
	$shopcode_limit = $activity_info['shopcode_limit'];
}else{
	$shopcode_limit = 3;
}
$return_curr_arr = json_decode($activity_info['return_curr'],true);
//var_dump($activity_info);

//获取关联产品
$activity_product = array();
if( $activity_info['id'] > 0 ){
	$condition2['cgpt.activitie_id'] = $activity_info['id'];
	$condition2['cgpt.isvalid'] = true;
	$condition2['wcp.isvalid'] = true;
	$field2 = " cgpt.createtime,cgpt.pid,cgpt.price,cgpt.stock,cgpt.number,cgpt.total_open,cgpt.open_day,cgpt.sort,cgpt.success_num,cgpt.total_success,cgpt.total_fail,cgpt.total_conduct,cgpt.alone_onoff,wcp.name,wcp.orgin_price,wcp.now_price,wcp.cost_price,wcp.for_price ";
	$activity_product = $collageActivities -> getActivitiesProduct($condition2,$field2);
}

//获取产品分类
$link = new shopLink_Utlity($customer_id);
$link_arr = $link->getSelectLink(array(3),1);
$type_arr = $link_arr['type_arr'];

//判断渠道是否开启股东分红功能---start
$is_disrcount 	= 0;
$is_OpenShareholder = 0;
$query = "SELECT count(1) AS is_disrcount FROM customer_funs cf INNER JOIN columns c WHERE c.isvalid=true AND cf.isvalid=true AND cf.customer_id=".$customer_id." AND c.sys_name='商城股东分红奖励' AND c.id=cf.column_id";
$result = _mysql_query($query) or die('W228 is_OpenShareholder Query failed: ' . mysql_error());
while ( $row = mysql_fetch_object($result) ) {
	$is_disrcount = $row->is_disrcount;
	break;
}
if( $is_disrcount > 0 ){
	$is_OpenShareholder = 1;
}
//判断渠道是否开启股东分红功能---end

$is_shareholder = 0;	//是否开启股东分红奖励
$query = "SELECT is_shareholder FROM weixin_commonshops WHERE isvalid=true AND customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed'.mysql_error());
$row = mysql_fetch_assoc($result);
$is_shareholder = $row['is_shareholder'];

// if( $is_OpenShareholder == 1 && $is_shareholder == 1 ){
	$query = "SELECT a_name,b_name,c_name,d_name FROM weixin_commonshop_shareholder WHERE customer_id=".$customer_id." AND isvalid=true";
	$result = _mysql_query($query) or die('Query failed:'.mysql_error());
	$shareholder_name = mysql_fetch_assoc($result);
// }

$group_type_arr = array();
$query = "SELECT type,type_name FROM collage_activities_explain_t WHERE isvalid=true AND customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed'.mysql_error());
while ( $row = mysql_fetch_object($result) ) {
	$type = $row->type;
    $type_name = $row->type_name;
    $group_type_arr[$type] = $type_name;
}

?>
<html>
<head>
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/css2.css" media="all">
<link href="../../../common/add/css/global.css" rel="stylesheet" type="text/css">
<link href="../../../common/add/css/main.css" rel="stylesheet" type="text/css">
<link href="../../../common/add/css/shop.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<script type="text/javascript" src="../../../js/tis.js"></script>
<script type="text/javascript" src="../../../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<style type="text/css">
a:hover{text-decoration: none;}
.button_blue{cursor: pointer;margin-left: 10px;font-size: 14px;line-height: 30px;background-color: #06a7e1;padding-left: 15px;padding-right: 15px;border-radius: 3px 3px 3px 3px;margin-top:20px;color: #fff;}
.button_blue:hover{background:#0e98c9;}
.name{  margin-top: 10px;height: 30px;line-height: 30px;font-size: 13px;text-align: left;font-weight: bolder;margin-left: 19px;}
.button_box{width: 296px;display: block;text-align: right;}
.button_box .WSY_button{border-radius:2px;border:none;}
.delivery-button{padding: 3px 15px;color: #fff;border-radius: 2px;cursor:pointer;}
.product-box{width:95%;margin-top: 15px;padding: 15px;border: 1px #ccc solid;display:none;}
.header-left{float:left;}
.header-right{float:right;}
.search-box{height: 22px;line-height: 22px;}
.delivery_time{margin: 5px 35px;}
.delivery_time input{margin: 0 20px;}
.delivery_limit_box{margin: 15px 35px;position: relative;}
.delivery_font{font-size: 15px;font-weight: bold;width:140px;text-align:right;display:inline-block;}
.delivery_limit_box input[type=text]{margin: 0 20px;}
.selected-date-content{display: block;margin-left: 30px;margin-top: 10px;}
.page-box1{margin-left:30px;}
.page-box1,.page-box2{margin-top:15px;margin-left: 70px;}
.show-data-num{margin:0 20px;}
.page-box1 input,.page-box2 input{width:30px;text-align:center;}
.show-data-num-btn{margin-right:30px;}
.current-page{margin: 0 20px;}
.go-page-btn{margin-left:15px;}
#to-page-num1,#to-page-num2{margin-left:20px;}
.relation_table th,.list_table th{text-align:center;}
.activity_title{width:100%;border-bottom:1px #DEDBDB solid;padding: 3px 0 3px 20px;margin-top: 15px;}
.activity_title_span{padding: 5px 10px;background-color: #DEDBDB;}
.operation-button{height: 50px;line-height: 50px;text-align: center;}
.back-button,.close-float-table{padding:5px 40px;;background-color:#ADABAB;font-size:15px;color:#fff;margin: 0 10px;cursor:pointer;}
.save-button,.add-selected-product{padding:5px 40px;;background-color:#06a7e1;font-size:15px;color:#fff;margin: 0 10px;cursor:pointer;}
.float-table{position: fixed;top: 0;height: 100%;width: 100%;background-color: #fff;overflow: scroll;z-index: 10;}
.float-table-title-box{text-align: center;background-color: #E0E0E0;height: 40px;line-height: 40px;}
.float-table-title{font-size: 18px;font-weight: bold;}
.selected-table td,.selected-table input{font-size: 12px !important;}
table#WSY_t1 td{word-wrap: break-word;text-align:center;}
.product-name{width:100%;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;}
.curr_return1{
    float: left;
}
.curr_return2{
    float: left;
    margin-left: 50px;
}
.curr_return3{
    float: left;
    width: 20px;
}
.curr_return4{
    float: left;
}
.curr_return5{
    float: left;
    width: 50px;
}
.curr_return6{
    float: left;
}
</style>
</head>
<body>
<div>
    <div class="WSY_content">
		<div class="WSY_columnbox" style="min-height: 100px;">
		<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>
		<div class="activity_title">
			<span class="activity_title_span">拼团活动基本信息</span>
		</div>
	<div class="r_con_wrap">
		<div style="margin-top:20px">
			<label class="delivery_font">主题：</label>
			<input type="text" name="name" value="<?php echo $activity_info['name'];?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?>/>
		</div>
		<div style="margin-top:20px">
			<label class="delivery_font">团类型：</label>
            <?php
                $type_list = $collageActivities->getTypes($customer_id);
                foreach($type_list as $key => $val){
            ?>
                <input type="radio" <?php if($val['type']==3||$val['type']==7){?>style="margin-left:25px;"<?php }?> id="type<?php echo $val['type'];?>" name="type" value="<?php echo $val['type'];?>" <?php if($activity_info['type']==$val['type']){echo 'checked';}?> <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> /><label for="type<?php echo $val['type'];?>"><?php echo $val['type_name'];?></label>
                
                <?php if($activity_info['type']==$val['type']){?> 
                    <script>
                       var choose_type = <?php echo $val['type'];?>;
                    </script>
                <?php }else{ ?>
                    <script>
                       var choose_type = 1;//初始化，防止爆炸
                    </script>
                <?php }?>
                
            <?php if($val['type']==2){?>
                <img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;" id="tips_2" src="../../Common/images/Base/help.png">
            <?php }elseif($val['type']==6){?>
                <img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;margin-right: 5px;" id="free_group" src="../../Common/images/Base/help.png">
            <?php
                }
                }
            ?>
		</div>
		<div class="luck_draw_num" style="margin-top:20px;<?php if($activity_info['type']==2){echo 'display:block;';}else{echo 'display:none;';}?>" >
			<label class="delivery_font">抽奖团名额：</label>
			<input type="number" name="luck_draw_num" value="<?php echo $activity_info['luck_draw_num'];?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> /><span style="color:ff0000;">提示：中奖名额以团为单位，举例设置1，代表会有一个团被抽中</span>
		</div>

        <div class="luck_split_money" style="margin-top:20px;<?php if($activity_info['type']==7){echo 'display:block;';}else{echo 'display:none;';}?>" >
            <label class="delivery_font">抽奖失败平分金额：</label>
            <input type="number" name="luck_split_money" value="<?php echo $activity_info['luck_split_money'];?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> /><span style="color:ff0000;">提示：拼团成功后,抽奖失败者平分此金额</span>
        </div>

		<div style="margin-top:20px">
			<label class="delivery_font">开始时间：</label>
			<input type="text" id="start_time" name="start_time" value="<?php echo $activity_info['start_time'];?>" placeholder="年-月-日" onfocus="WdatePicker({onpicked:function(){check_time_section();},dateFmt:'yyyy-M-d HH:mm:ss',maxDate:'#F{$dp.$D(\'end_time\')}'});"  readonly <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> />
		</div>

		<div style="margin-top:20px">
			<label class="delivery_font">结束时间：</label>
			<input type="text" id="end_time" name="end_time" value="<?php echo $activity_info['end_time'];?>" placeholder="年-月-日" onfocus="WdatePicker({onpicked:function(){check_time_section();},dateFmt:'yyyy-M-d HH:mm:ss',minDate:'#F{$dp.$D(\'start_time\')}'});" readonly <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> /><span style="color:ff0000;display: none;" id="free_single_time_tis" >提示：若团的持续时间小于一天，开团天数将不能设置(开始时间小于现在时间,按现在时间算。)</span>
		</div>
		<div style="margin-top:20px">
			<label class="delivery_font">成团人数：</label>
			<input type="number" name="group_size" id="group_size" value="<?php echo $activity_info['group_size'];?>" <?php if($activity_info['type']==2){echo 'onkeyup="clearInt2(this)" max="5"';}else{echo 'onkeyup="clearInt(this)"';}?>  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> min=1  style="width:150px;" /><span>大于0的正整数</span><span style="color:ff0000;<?php if($activity_info['type']==2){echo 'display:;';}else{echo 'display:none;';}?>" id="group_addwork">&nbsp;&nbsp;最大值不能超过5，即成团人数不能超过5人</span>
		</div>
		<div style="margin-top:20px">
			<label class="delivery_font">用户限制：</label>
			<input type="checkbox" class="user_level" id="user_level1" name="user_level[]" value="1" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if(in_array('1',$user_level_arr)){echo 'checked';}?> /><label for="user_level1">粉丝</label>
			<input type="checkbox" class="user_level" id="user_level2" name="user_level[]" value="2" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if(in_array('2',$user_level_arr)){echo 'checked';}?> /><label for="user_level2">推广员</label>
			<?php
				if( !empty($shareholder_name) ){
			?>
			<input type="checkbox" class="user_level" id="user_level3" name="user_level[]" value="3" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if(in_array('3',$user_level_arr)){echo 'checked';}?> /><label for="user_level3"><?php echo $shareholder_name['d_name'];?></label>
			<input type="checkbox" class="user_level" id="user_level4" name="user_level[]" value="4" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if(in_array('4',$user_level_arr)){echo 'checked';}?> /><label for="user_level4"><?php echo $shareholder_name['c_name'];?></label>
			<input type="checkbox" class="user_level" id="user_level5" name="user_level[]" value="5" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if(in_array('5',$user_level_arr)){echo 'checked';}?> /><label for="user_level5"><?php echo $shareholder_name['b_name'];?></label>
			<input type="checkbox" class="user_level" id="user_level6" name="user_level[]" value="6" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if(in_array('6',$user_level_arr)){echo 'checked';}?> /><label for="user_level6"><?php echo $shareholder_name['a_name'];?></label>
			<?php
				}
			?>
			<img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;" id="user_info" src="../../Common/images/Base/help.png">
		</div>
		<!--div style="margin-top:20px">
			<label class="delivery_font">活动参与次数限制：</label>
			<input type="number" name="number" value="<?php echo $activity_info['number'];?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> /><span>同一用户最多参与活动的次数，-1表示不限制</span>
		</div-->

        <div class="baobaotuan" style="<?php if($activity_info['type']==5){echo 'display:block;';}else{echo 'display:none;';}?>" >
			<div style="margin-top:20px">
                <label class="delivery_font">首次开团支付限制：</label>
                <input type="radio" id="can_not_usecurr" name="head_curr_use" value="1" <?php if($activity_info['if_curr_pay']==1){echo 'checked';}?> <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?>/><label for="can_not_usecurr">不能使用<?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?></label>
                <input type="radio" id="can_usecurr" name="head_curr_use" value="2" <?php if($activity_info['if_curr_pay']==2){echo 'checked';}?> <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?>/><label for="can_usecurr">能使用<?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?></label>
                <img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;" id="first-open" src="../../Common/images/Base/help.png">
                <input type="hidden" id="if_curr_pay" value='<?php echo $activity_info["if_curr_pay"];?>' >
            </div>
            <div style="margin-top:20px">
                <label class="delivery_font">系数设置：</label>
                <input type="text" name="coefficient" onkeyup="input_coefficient(this)" value="<?php echo $activity_info['coefficient'];?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?>/>
                <span style="color:red;">说明：设置5的时候，团长可以在这个活动里面开N次团，但是返赠的倍数是根据同一个活动里开团次数进行叠加来递减赠送，到第6次就重新计算返赠。限制0-50数值内,0则不返赠。</span>
            </div>
            <div class="r_con_wrap" style="min-height:100px;">
                <span style="margin-left:20px;color:red;">说明：比例不限制，可以超过100%，金额显示最多小数点后两位，选择哪种方式，下面系数都根据第一个方式来设置，选择固定金额，下面都是只能设置固定金额</span>
                <table class="WSY_table selected-table2" width="90%" id="WSY_t1">
                    <colgroup>
                        <col width="10%">
                        <col width="40%">
                    </colgroup>
                    <thead class="WSY_table_header">
                        <th>次数</th>
                        <th><?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?>返赠</th>
                    </thead>
                    <thead class="coefficient_content">
                    <?php foreach( $return_curr_arr as $key => $value ){?>
                    <tr>
                        <td class="collage_times"><?php echo $value['collage_times']?></td>
                        <td>
                            <div class="curr_return1">
                            <input type="radio" class="return_curr curr_return3" id="return_curr<?php echo $value['collage_times']?>_1" name="return_curr<?php echo $value['collage_times']?>" value="1" <?php if($value['return_type']==1){echo 'checked';}?> onclick="clearother(1,<?php echo $value['collage_times']?>)" <?php if($value['collage_times']!=1){echo 'disabled';}?> <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> /><label class="curr_return4" for="">产品售价比例计算</label>
                            <input class="pro_ratio curr_return5" type="text" name="pro_ratio<?php echo $value['collage_times']?>" id="pro_ratio<?php echo $value['collage_times']?>" value="<?php if($value['return_type']==1){echo $value['return_value'];}?>" onkeyup="clearFloat(this)" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> /><a class="curr_return6">%</a>
                            </div>
                            <div class="curr_return2">
                            <input type="radio" class="return_curr curr_return3" id="return_curr<?php echo $value['collage_times']?>_2" name="return_curr<?php echo $value['collage_times']?>" value="2" <?php if($value['return_type']==2){echo 'checked';}?> onclick="clearother(2,<?php echo $value['collage_times']?>)" <?php if($value['collage_times']!=1){echo 'disabled';}?> <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?>/><label class="curr_return4" for="">固定金额计算</label>
                            <a class="curr_return6">￥</a><input class="fixed_amount curr_return5" type="text" name="fixed_amount<?php echo $value['collage_times']?>" id="fixed_amount<?php echo $value['collage_times']?>" value="<?php if($value['return_type']==2){echo $value['return_value'];}?>" onkeyup="clearFloat(this)" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> />
                            </div>
                        </td>
                    </tr>
                    <?php }?>
                    </thead>
                </table>
            </div>


		</div>

        <div style="margin-top:20px">
            <label class="delivery_font">参团限制：</label>
            <input type="number" name="ginseng_num" value="<?php if(!empty($activity_info['ginseng_num'])){echo $activity_info['ginseng_num'];}else{echo -1;}?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?>/>
            <img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;" id="ginseng_num_tips" src="../../Common/images/Base/help.png">
        </div>

        <div style="margin-top:20px">
            <label class="delivery_font">开团限制：</label>
            <input type="number" name="head_times" value="<?php if(!empty($activity_info['head_times'])){echo $activity_info['head_times'];}else{echo -1;}?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?>/>
            <img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;" id="head_times_tips" src="../../Common/images/Base/help.png">
        </div>

        <div style="margin-top:20px">
            <label class="delivery_font" style="float: left;">自购设置：</label>
            <div class="WSY_remind_main">
                <dl style="display:block;overflow: hidden;">
                    <dt></dt>
                    <dd style="float: left;">
                       <?php if($activity_info['is_since']==1){ ?>
                            <ul style="background-color: rgb(255, 113, 112);">
                                <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                <li  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="is_since(0)" class="WSY_bot"';}?>  style="left: 0px;"></li>
                                <span <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="is_since(1)" class="WSY_bot2"';}?>  style="display: none; left: 0px;"></span>
                            </ul>
                        <?php }else{ ?>
                            <ul style="background-color: rgb(203, 210, 216);">
                                <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                <li <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="is_since(0)" class="WSY_bot"';}?> style="display: none; left: 30px;"></li>
                                <span  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="is_since(1)" class="WSY_bot2"';}?> style="display: block; left: 30px;"></span>
                            </ul>
                        <?php } ?>
                        <input type="hidden" name="is_since" id="is_since" value="<?php echo $activity_info['is_since']?>" />
                    </dd>
                    <img style="width:12px;position: absolute;margin-top: 5px;margin-left: 5px;" id="is_since_tips" src="../../Common/images/Base/help.png">
                </dl>
            </div>


        </div>
        
		<div style="margin-top:20px;<?php if($activity_type==5) {?> display:none <?php } ?>"  class="isShow_alone_onoff">
		   <label class="delivery_font" style="float: left;">单独购买开关：</label>
			<div class="WSY_remind_main">
                <dl style="display:block;overflow: hidden;">
                    <dt></dt>
                    <dd style="float: left;">
                       <?php if($activity_info['alone_onoff']==1){ ?>
                            <ul style="background-color: rgb(255, 113, 112);">
                                <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                <li  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="alone_onoff1(0)" class="WSY_bot"';}?>  style="left: 0px;"></li>
                                <span <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="alone_onoff1(1)" class="WSY_bot2"';}?>  style="display: none; left: 0px;"></span>
                            </ul>
                        <?php }else{ ?>
                            <ul style="background-color: rgb(203, 210, 216);">
                                <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                <li <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="alone_onoff1(0)" class="WSY_bot"';}?> style="display: none; left: 30px;"></li>
                                <span  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="alone_onoff1(1)" class="WSY_bot2"';}?> style="display: block; left: 30px;"></span>
                            </ul>
                        <?php } ?>
                        <input type="hidden" name="alone_onoff" id="alone_onoff" value="<?php echo $activity_info['alone_onoff']?>" />
                    </dd>

                </dl>
            </div>
		</div>          

		<div style="margin-top:20px;<?php if($activity_type==5) {?> display:none <?php } ?>"  class="isShow_shopcode_onoff">
		   <label class="delivery_font" style="float: left;"><?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?>抵扣：</label>
			<div class="WSY_remind_main">
                <dl style="display:block;overflow: hidden;">
                    <dt></dt>
                    <dd style="float: left;">
                       <?php if($activity_info['shopcode_onoff']==1){ ?>
                            <ul style="background-color: rgb(255, 113, 112);">
                                <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                <li  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="shopcode_onoff(0)" class="WSY_bot"';}?>  style="left: 0px;"></li>
                                <span <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="shopcode_onoff(1)" class="WSY_bot2"';}?>  style="display: none; left: 0px;"></span>
                            </ul>
                        <?php }else{ ?>
                            <ul style="background-color: rgb(203, 210, 216);">
                                <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                <li <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="shopcode_onoff(0)" class="WSY_bot"';}?> style="display: none; left: 30px;"></li>
                                <span  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="shopcode_onoff(1)" class="WSY_bot2"';}?> style="display: block; left: 30px;"></span>
                            </ul>
                        <?php } ?>
                        <input type="hidden" name="shopcode_onoff" id="shopcode_onoff" value="<?php echo $activity_info['shopcode_onoff']?>" />
                    </dd>

                </dl>
            </div>
		</div>      

		<div style="margin-top:20px; <?php if($activity_info['shopcode_onoff']==0 or $activity_type==5) {?> display:none <?php }?>" id="isShow_shopcodeLimit">
		      <label class="delivery_font" style="float: left;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
			  <input type="checkbox" class="shopcode_limit" id="shopcode_limit1" name="shopcode_limit[]" value="1" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if($shopcode_limit == 1 or $shopcode_limit ==3){echo 'checked';}?> /><label for="shopcode_limit1">仅团长</label>
			  <input type="checkbox" class="shopcode_limit" id="shopcode_limit2" name="shopcode_limit[]" value="2" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> <?php if($shopcode_limit == 2 or $shopcode_limit ==3){echo 'checked';}?> /><label for="shopcode_limit2">仅团员</label>
		</div>

		<div style="margin-top:20px; <?php if($activity_info['shopcode_onoff']==0 or $activity_type==5) {?> display:none <?php }?>" id="isShow_shopcodeLimit_precent">
		      <label class="delivery_font"><?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?>抵购比例：</label>
            <input type="text" name="shopcode_precent" value="<?php if(!empty($activity_info['shopcode_precent'])){echo $activity_info['shopcode_precent'];}else{echo '100.00';}?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo 'disabled';}?> autocomplete="off" onkeyup="clearNoNum_two(this)" onafterpaste="clearNoNum_two(this)"  />%
		</div>


		<div style="margin-top:20px;<?php if($activity_type==5) {?> display:none <?php } ?>" class="isShow_coupon_onoff">
		   <label class="delivery_font" style="float: left;">优惠券功能：</label>
		     <div class="WSY_remind_main">
                <dl style="display:block;overflow: hidden;">
                    <dt></dt>
                    <dd style="float: left;">
                       <?php if($activity_info['coupon_onoff']==1){ ?>
                            <ul style="background-color: rgb(255, 113, 112);">
                                <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                <li  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="coupon_onoff(0)" class="WSY_bot"';}?>  style="left: 0px;"></li>
                                <span <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="coupon_onoff(1)" class="WSY_bot2"';}?>  style="display: none; left: 0px;"></span>
                            </ul>
                        <?php }else{ ?>
                            <ul style="background-color: rgb(203, 210, 216);">
                                <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                <li <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="coupon_onoff(0)" class="WSY_bot"';}?> style="display: none; left: 30px;"></li>
                                <span  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="coupon_onoff(1)" class="WSY_bot2"';}?> style="display: block; left: 30px;"></span>
                            </ul>
                        <?php } ?>
                        <input type="hidden" name="coupon_onoff" id="coupon_onoff" value="<?php echo $activity_info['coupon_onoff']?>" />
                    </dd>

                </dl>
             </div>
	    </div>
		<div style="margin-top:20px;">
                <label class="delivery_font" style="float: left;">售后设置：</label>
                <div class="WSY_remind_main">
                    <dl class="WSY_remind_dl02" style="<?php if($activity_info['type']==5){echo 'display:none;';}else{echo 'display:block;';}?>">
                        <dt>退款开关：</dt>
                        <dd>
                           <?php if($activity_info['if_refund']==1){ ?>
                                <ul style="background-color: rgb(255, 113, 112);">
                                    <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                    <li  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_refund(0)" class="WSY_bot"';}?>  style="left: 0px;"></li>
                                    <span <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_refund(1)" class="WSY_bot2"';}?>  style="display: none; left: 0px;"></span>
                                </ul>
                            <?php }else{ ?>
                                <ul style="background-color: rgb(203, 210, 216);">
                                    <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                    <li <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_refund(0)" class="WSY_bot"';}?> style="display: none; left: 30px;"></li>
                                    <span  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_refund(1)" class="WSY_bot2"';}?> style="display: block; left: 30px;"></span>
                                </ul>
                            <?php } ?>
                            <input type="hidden" name="if_refund" id="if_refund" value="<?php echo $activity_info['if_refund']?>" />
                        </dd>
                    </dl>
					<dl class="WSY_remind_dl02" style="margin-left: 140px;<?php if($activity_info['type']==5){echo 'display:none;';}else{echo 'display:block;';}?>">
                        <dt>退货开关：</dt>
                        <dd>
                           <?php if($activity_info['if_return_pro']==1){ ?>
                                <ul style="background-color: rgb(255, 113, 112);">
                                    <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                    <li  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_return_pro(0)" class="WSY_bot"';}?>  style="left: 0px;"></li>
                                    <span <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_return_pro(1)" class="WSY_bot2"';}?>  style="display: none; left: 0px;"></span>
                                </ul>
                            <?php }else{ ?>
                                <ul style="background-color: rgb(203, 210, 216);">
                                    <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                    <li <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_return_pro(0)" class="WSY_bot"';}?> style="display: none; left: 30px;"></li>
                                    <span  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="if_return_pro(1)" class="WSY_bot2"';}?> style="display: block; left: 30px;"></span>
                                </ul>
                            <?php } ?>
                            <input type="hidden" name="if_return_pro" id="if_return_pro" value="<?php echo $activity_info['if_return_pro']?>" />
                        </dd>
                    </dl>

                    <dl class="WSY_remind_dl03" style="margin-left: 140px;">
                        <dt>换货开关：</dt>
                        <dd>
                           <?php if($activity_info['if_change_pro']==1){ ?>
                                <ul style="background-color: rgb(255, 113, 112);">
                                    <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                    <li  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="change_pro(0)" class="WSY_bot"';}?>  style="left: 0px;"></li>
                                    <span <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="change_pro(1)" class="WSY_bot2"';}?>  style="display: none; left: 0px;"></span>
                                </ul>
                            <?php }else{ ?>
                                <ul style="background-color: rgb(203, 210, 216);">
                                    <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                    <li <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="change_pro(0)" class="WSY_bot"';}?> style="display: none; left: 30px;"></li>
                                    <span  <?php if($activity_info['status']!=1 && $activity_info['status']!=''){echo '';}else{echo 'onclick="change_pro(1)" class="WSY_bot2"';}?> style="display: block; left: 30px;"></span>
                                </ul>
                            <?php } ?>
                            <input type="hidden" name="change_pro" id="change_pro" value="<?php echo $activity_info['if_change_pro']?>" />
                        </dd>
                    </dl>
                </div>
		</div>
	</div>
	<div class="activity_title">
		<span class="activity_title_span">团活动产品</span>
		<?php
			if($activity_info['status']==1 || $activity_info['status']==2 || $activity_info['status']==''){
		?>
		<span class="delivery-button WSY-skin-bg add-product-btn" style="float:right;margin-right: 100px;padding:2px 15px;">添加活动产品</span>
		<?php
			}
		?>
	</div>
	<div class="r_con_wrap" style="min-height:100px;">
		<table class="WSY_table selected-table" width="95%" id="WSY_t1">
			<colgroup>
				<col width="6%">
				<col width="18%">
				<col width="8%">
				<col width="8%">
				<col width="8%">
				<col width="8%">
				<col width="10%">
				<col width="8%" class="free_single"  >
				<col width="12%">
				<col width="8%" class="free_single"  >
				<col width="8%" >
				
		  		<col width="9%"> 
                <col width="9%" style="<?php if($activity_type==5) {?> display:none <?php } ?>" class="alone_buy1">
			</colgroup>
			<thead class="WSY_table_header">
				<th>产品ID</th>
				<th>产品名称</th>
				<th>市场价</th>
				<th>销售价</th>
				<th>活动价格</th>
				<th>活动库存</th>
				<th>购买数量(每人每次)</th>
				<th class="free_single"  >开团天数</th>
				<th>创建时间</th>
				<th class="free_single"  >排序</th>
				<th>成团人数</th>
				<?php
					if($activity_info['status']==1 || $activity_info['status']==2 || $activity_info['status']==''){
				?>
				<th>操作管理</th>
				<?php
					}
				?>
                <th style="<?php if($activity_type==5) {?> display:none <?php } ?>" class="alone_buy0">单独购买开关</th>
			</thead>
			<?php
				$pid_arr = [];
				$pid_str = '';
				foreach( $activity_product as $k => $v ){
					$pid_arr[] = (int)$v['pid'];
			?>
			<tr data-pid="<?php echo $v['pid']?>" data-cost_price="<?php echo $v['cost_price'];?>" data-for_price="<?php echo $v['for_price'];?>">
				<td><?php echo $v['pid']?></td>
				<td class="product-name"><?php echo $v['name']?></td>
				<td><?php echo $v['orgin_price']?></td>
				<td><?php echo $v['now_price']?></td>
				<td><input type="text" class="price" name="price" value="<?php echo $v['price']?>" onkeyup="clearFloat(this)" <?php if($activity_info['status']!=1 && $activity_info['status']!=2 && $activity_info['status']!=''){echo 'disabled';}?> /></td>
				<td><input type="text" class="stock" name="stock" value="<?php echo $v['stock']?>" onkeyup="clearInt(this)" <?php if($activity_info['status']!=1 && $activity_info['status']!=2 && $activity_info['status']!=''){echo 'disabled';}?> /></td>
				<td><input type="number" class="pnumber" name="pnumber" value="<?php echo $v['number']?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=2 && $activity_info['status']!=''){echo 'disabled';}?> /></td>
				<td class="free_single" ><input type="number" <?php if($activity_info['status']!=1  && $activity_info['status']!=''){echo 'class="old_duration" disabled';}else{echo 'class="new_duration"';} ?>  value="<?php echo $v['open_day']?>" min=1  onfocus="check_time_section()" /></td>
				<td><?php echo $v['createtime']?></td>
				<td class="free_single" ><input type="number" class="free_single_order" value="<?php echo $v['sort']?>" <?php if($activity_info['status']!=1 && $activity_info['status']!=2 && $activity_info['status']!=''){echo 'disabled';}?> /></td>
				<td><input type="number" class="success_num" value="<?php echo $v['success_num']?>" <?php if($activity_info['status']!=1  && $activity_info['status']!=''){echo 'disabled';}?> /></td>
				<?php
					if($activity_info['status']==1 || $activity_info['status']==2 || $activity_info['status']==''){
				?>
				<td><span class="delivery-button WSY-skin-bg del-btn" onclick="delProduct(<?php echo $v['pid']?>,this)">移除</span></td>
				<?php
					}
				?>
                <td style="margin-top:20px;<?php if($activity_type==5) {?> display:none <?php } ?>" class="alone_buy">
                <div class="shelter" style="width: 7%;height: 40px;ute;position: absolute;filter: alpha(opacity=60);z-index: 1002;
                    opacity:0.5;-moz-opacity:0.5;<?php if($activity_info['alone_onoff']==1){ ?> display: none; <?php } ?>"></div>
                    <div class="WSY_remind_main">
                        <dl style="display:block;overflow: hidden;margin-top: 0;">
                            <dt></dt>
                            <dd style="float: left;">
                               <?php if($v['alone_onoff']==1){ ?>
                                    <ul style="background-color: rgb(255, 113, 112);">
                                        <p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>
                                        <li onclick="p_alone_onoff(<?php echo $v['pid']?>,0)" class="WSY_bot" style="left: 0px;"></li>
                                        <span onclick="p_alone_onoff(<?php echo $v['pid']?>,1)" class="WSY_bot2" style="display: none; left: 0px;"></span>
                                    </ul>
                                <?php }else{ ?>
                                    <ul style="background-color: rgb(203, 210, 216);">
                                        <p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>
                                        <li onclick="p_alone_onoff(<?php echo $v['pid']?>,0)" class="WSY_bot" style="display: none; left: 30px;"></li>
                                        <span onclick="p_alone_onoff(<?php echo $v['pid']?>,1)" class="WSY_bot2" style="display: block; left: 30px;"></span>
                                    </ul>
                                <?php } ?>
                                <input type="hidden" name="p_alone_onoff" class="p_alone_onoff" id="p_alone_onoff_<?php echo $v['pid']?>" value="<?php echo $v['alone_onoff']?>" />
                            </dd>

                        </dl>
                    </div>
                </td>
			</tr>
			<?php
				}
				$pid_str = implode(',',$pid_arr);
			?>
		</table>
	</div>
	<div class="operation-button">
		<span class="back-button">返回</span>
		<?php
			if($activity_info['status']==1 || $activity_info['status']==2 || $activity_info['status']==''){
		?>
		<span class="save-button WSY-skin-bg">保存</span>
		<?php
			}
		?>
	</div>
</div>
</div>

<div style="width:100%;height:20px;">
</div>
</div>
<div class="float-table" style="display:none;">
	<div class="float-table-title-box">
		<span class="float-table-title">选择产品</span>
	</div>
	<div class="float-table-search" style="margin-top: 10px;padding-left: 10px;">
		<span>产品编号：</span><input type="text" id="search-pid" />
		<span>产品名称：</span><input type="text" id="search-pname" />
		<span>合作商ID：</span><input type="text" id="search-supply-id" />
		<span>产品分类：</span>
		<select id="search-ptype">
			<option value="-1">全部</option>
			<?php
				foreach( $type_arr['-1'] as $key => $value ){
					$option_arr = explode('_',$value);
					$option_val = $option_arr[0];
					$option_name = $option_arr[1];
			?>
			<option value="<?php echo $option_val;?>"><?php echo $option_name;?></option>
				<?php
					if( !empty($type_arr[$option_val]) ){
						foreach( $type_arr[$option_val] as $key2 => $value2 ){
							$option_arr = explode('_',$value2);
							$option_val = $option_arr[0];
							$option_name = $option_arr[1];
				?>
			<option value="<?php echo $option_val;?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $option_name;?></option>
				<?php
					if( !empty($type_arr[$option_val]) ){
						foreach( $type_arr[$option_val] as $key3 => $value3 ){
							$option_arr = explode('_',$value3);
							$option_val = $option_arr[0];
							$option_name = $option_arr[1];
				?>
			<option value="<?php echo $option_val;?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $option_name;?></option>
				<?php
					if( !empty($type_arr[$option_val]) ){
						foreach( $type_arr[$option_val] as $key4 => $value4 ){
							$option_arr = explode('_',$value4);
							$option_val = $option_arr[0];
							$option_name = $option_arr[1];
				?>
			<option value="<?php echo $option_val;?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $option_name;?></option>
				<?php
						}
					}
						}
					}
						}
					}
				}
			?>
		</select>
	</div>
	<div style="margin-top: 10px;padding-left: 10px;">
		<span>产品来源：</span>
		<select id="search-pfrom">
			<option value="-1">全部</option>
			<option value="1">平台</option>
			<option value="2">供应商</option>
		</select>
		<span>产品标签：</span>
		<select id="search-ptag">
			<option value="-1">全部</option>
			<option value="1">热卖</option>
			<option value="2">新品</option>
			<option value="3">包邮</option>
			<option value="4">虚拟产品</option>
			<option value="5"><?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?></option>
            <option value="6">小程序</option>
		</select>
		<span class="delivery-button WSY-skin-bg search-button" style="float:right;margin-right: 10%;" onclick="searchProduct()">搜索</span>
	</div>
	<table width="93%" class="WSY_table list_table" id="WSY_t1" >
		<thead class="WSY_table_header">
			<th width="7%"><input type="checkbox" id="select-all" style="vertical-align: middle;" /><label for="select-all">全选</label></th>
			<th width="8%">产品ID</th>
			<th width="15%">名称</th>
			<th width="10%">分类</th>
			<th width="15%">价格</th>
			<th width="7%">销量</th>
			<th width="7%">库存</th>
			<th width="10%">图片</th>
			<th width="10%">标签</th>
			<th width="12%">创建时间</th>
			<th width="10%">操作</th>
		</thead>
	</table>
	<div class="operation-button">
		<span class="close-float-table">返回</span>
		<span class="add-selected-product WSY-skin-bg" onclick="addProduct(2)">批量添加</span>
	</div>
</div>
<script charset="utf-8" src="/weixinpl/common/js/layer/V2_1/layer.js"></script>
<script type="text/javascript" src="/weixinpl/common/js/layer/V2_1/layer.js"></script>
<script>
var customer_id = '<?php echo $customer_id;?>';
var customer_id_en = '<?php echo passport_encrypt((string)$customer_id); ?>';
var keyid = '<?php echo $keyid;?>';
var comeFrom = '<?php echo $comeFrom;?>';
var pidArr = eval('<?php echo json_encode($pid_arr);?>');
var pidStr = '<?php echo $pid_str; ?>';
var delPidArr = new Array();	//移除产品id数组
var delPidStr = '';				//移除产品id字符串
var addPidArr = new Array();	//添加产品id数组
var addPidStr = '';				//添加产品id字符串

var showProductLimitStart = 0,
	showProductLimitEnd = 9,
	showProductCurrentPage = 1,
	showProductEachPageNum = 10,
	showProductTotalPage = 1,
	search_pid = '',
	search_pname = '',
	search_supply_id = '',
	search_ptype = -1,
	search_pfrom = -1,
	search_ptag = -1;

//判断活动是间隔是否少于一天
function check_time_section(){

		start_time=$('#start_time').val();
		end_time  =$('#end_time').val();
		if(start_time != '' && end_time !=''){
			start_time = Date.parse(new Date(start_time));
			start_time = start_time / 1000;
			end_time = Date.parse(new Date(end_time));
			end_time = end_time / 1000;
			now_time = Date.parse(new Date())/1000;
			if(now_time>start_time){
				start_time = now_time;
			}
			if(Math.floor((end_time-start_time)/86400)<1){
				$('#free_single_time_tis').css('display','');
				$('.new_duration').attr("disabled",true);
				$('.new_duration').attr("less_one",'true');
				$('.new_duration').val('-1');
			}else{
				$('#free_single_time_tis').css('display','none');
				$('.new_duration').attr("disabled",false);
				$('.new_duration').attr("less_one",'false');
			}
		}

}
//如果是编辑活动  则判断时间段。
if(keyid > 0){
	check_time_section();
}
$('#free_group').on('mouseenter', function(){
	layer.tips('选择该类型，代表不管是否满人，都会拼团成功，生成订单，只要活动时间到，即使没有成团，也给参团人发货，如果成团了，参团人不仅获得产品，开团人还能退款免单','#free_group');
});
$('#tips_2').on('mouseenter', function(){
	layer.tips('建议抽奖团活动价格不要低于原价30%，尽量不要设置1元抽奖！','#tips_2');
});

$('#first-open').on('mouseenter', function(){
	layer.tips('参团的人只能线上支付，只有开团的人才有机会使用<?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?>抵扣','#first-open');
});

$('#user_info').on('mouseenter', function(){
	layer.tips('领取者身份的规则：每种身份都是独立的，如果只勾选了推广员身份，那么其他身份（粉丝和店铺的4种身份都是不能使用的）；如果你想推广员以上身份的包含推广员都可以领取，那么就需要将推广员和店铺的4种身份全部勾选','#user_info');
});

$('#ginseng_num_tips').on('mouseenter', function(){
	layer.tips('每位用户，同活动时间内，最多参团N次拼团活动，-1不限制','#ginseng_num_tips');
});

$('#head_times_tips').on('mouseenter', function(){
	layer.tips('每位用户，同活动时间内，最多开团N次拼团活动，-1不限制','#head_times_tips');
});

$('#is_since_tips').on('mouseenter', function(){
	layer.tips('开启则可参团自己开的团','#is_since_tips');
});

$('input[name=type]').click(function(){
    console.log("选择不同的团");

	var val = $(this).val();
	type = val; //将当前选择的团类型设为常量
	choose_type = val; //将当前选择的团类型设为常量
    console.log(choose_type);
	var shopcode_onoff = $('input[name=shopcode_onoff]').val();
	//隐藏域记录当前团类型
	$('#now_type').val(val);
	if( val == 2 ){
        $('.luck_split_money').fadeOut();
		$('.luck_draw_num').fadeIn();
		$('#group_size').attr('onkeyup','clearInt2(this)');
		$('#group_size').attr('max','5');
		$('#group_size').val('');
		$('#group_addwork').css('display','');
        $('.baobaotuan').fadeOut();
		$('.WSY_remind_dl02').fadeIn();
		$('.isShow_coupon_onoff').fadeIn();
		$('.isShow_shopcode_onoff').fadeIn();
		$('.isShow_alone_onoff').fadeIn();
		$('.alone_buy0').fadeIn();
		$('.alone_buy').fadeIn();
		$('.alone_buy1').fadeIn();
		if(shopcode_onoff == 1){
			$('#isShow_shopcodeLimit').fadeIn();
			$('#isShow_shopcodeLimit_precent').fadeIn();
		}
	}else if(  val == 5 ){
        $('.luck_split_money').fadeOut();
		$('.luck_draw_num').fadeOut();
		$('#group_size').attr('onkeyup','clearInt(this)');
		$('#group_size').attr('max','');
		$('#group_size').val('');
		$('#group_addwork').css('display','none');
        $('.baobaotuan').fadeIn();
        $('.WSY_remind_dl02').fadeOut();
		$('.isShow_coupon_onoff').fadeOut();
		$('.isShow_shopcode_onoff').fadeOut();
		$('.isShow_alone_onoff').fadeOut();
		$('#isShow_shopcodeLimit').fadeOut();
		$('#isShow_shopcodeLimit_precent').fadeOut();
        $('.alone_buy0').fadeOut();
		$('.alone_buy').fadeOut();
		$('.alone_buy1').fadeOut();
	}else if(  val == 6 ){
        $('.luck_split_money').fadeOut();
		$('.luck_draw_num').fadeOut();
		$('#group_size').attr('onkeyup','clearInt(this)');
		$('#group_size').attr('max','');
		$('#group_size').val('');
		$('#group_addwork').css('display','none');
        $('.baobaotuan').fadeOut();
		$('.WSY_remind_dl02').fadeIn();
		$('.isShow_coupon_onoff').fadeIn();
		$('.isShow_shopcode_onoff').fadeIn();
        $('.isShow_alone_onoff').fadeIn();
        $('.alone_buy0').fadeIn();
		$('.alone_buy').fadeIn();
		$('.alone_buy1').fadeIn();
		if(shopcode_onoff == 1){
			$('#isShow_shopcodeLimit').fadeIn();
			$('#isShow_shopcodeLimit_precent').fadeIn();
		}
		check_time_section();
	}else if(val == 7){
        $('.luck_split_money').fadeIn();
        $('.luck_draw_num').fadeOut();
        $('#group_size').attr('onkeyup','clearInt(this)');
        $('#group_size').attr('max','');
        $('#group_size').val('');
        $('#group_addwork').css('display','none');
        $('.baobaotuan').fadeOut();
        $('.WSY_remind_dl02').fadeIn();
        $('.isShow_coupon_onoff').fadeIn();
        $('.isShow_shopcode_onoff').fadeIn();
        $('.isShow_alone_onoff').fadeIn();
        $('.alone_buy0').fadeIn();
        $('.alone_buy').fadeIn();
        $('.alone_buy1').fadeIn();
        if(shopcode_onoff == 1){
            $('#isShow_shopcodeLimit').fadeIn();
            $('#isShow_shopcodeLimit_precent').fadeIn();
        }
    } else {
        $('.luck_split_money').fadeOut();
		$('.luck_draw_num').fadeOut();
		$('#group_size').attr('onkeyup','clearInt(this)');
		$('#group_size').attr('max','');
		$('#group_size').val('');
		$('#group_addwork').css('display','none');
        $('.baobaotuan').fadeOut();
		$('.WSY_remind_dl02').fadeIn();
		$('.isShow_coupon_onoff').fadeIn();
		$('.isShow_shopcode_onoff').fadeIn();
        $('.isShow_alone_onoff').fadeIn();
        $('.alone_buy0').fadeIn();
		$('.alone_buy').fadeIn();
		$('.alone_buy1').fadeIn();
		if(shopcode_onoff == 1){
			$('#isShow_shopcodeLimit').fadeIn();
			$('#isShow_shopcodeLimit_precent').fadeIn();
		}
	}

});

$("#select-all").click(function() { // 全选/取消全部
	if (this.checked == true) {
		$(".product-info-checkbox").each(function() {
			this.checked = true;
		});
	} else {
		$(".product-info-checkbox").each(function() {
			this.checked = false;
		});
	}
});

//返回
$('.back-button').click(function(){
	if( comeFrom == 1 ){
		window.location.href = 'activityList.php?customer_id='+customer_id_en;
	} else {
		history.go(-1);
	}

});
$('.close-float-table').click(function(){
	$('.float-table').fadeOut();
});
//保存
$('.save-button').click(function(){

});
//移除
function delProduct(pid,obj){
	if( !confirm('产品移除后，不再参与当前活动，是否移除？') ){
		return;
	}
	$(obj).parent().parent().remove();
	if( addPidArr.indexOf(pid) >= 0 ){
		addPidArr.splice(addPidArr.indexOf(pid),1);
		addPidStr = addPidArr.join(',');
	}
	if( delPidArr.indexOf(pid) == -1 ){
		delPidArr.push(pid);
		delPidStr = delPidArr.join(',');

	}
	if( pidArr.indexOf(pid) >= 0 ){
		pidArr.splice(pidArr.indexOf(pid),1);
		pidStr = pidArr.join(',');
	}
}
//添加活动产品
$('.add-product-btn').click(function(){
	get_all_product();
	$('.float-table').fadeIn();
});

//获取产品
function get_all_product(){
	// if( arguments[0] != undefined ){
		// search_name = arguments[0];
	// }
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id_en,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'get_all_product',
			search_pid : search_pid,
			search_pname : search_pname,
			search_supply_id : search_supply_id,
			search_ptype : search_ptype,
			search_pfrom : search_pfrom,
			search_ptag : search_ptag,
			pid_str : pidStr,
			del_pid_str : delPidStr,
			limitstart : showProductLimitStart,
			limitend : showProductEachPageNum,
			is_count : 1,
			is_collage_product : 1
		},
		success: function(data){
			var productLen = data['product'].length,
				html = '',
				html_p = '';

			for( i in data['product'] ){
				var tag = '';

				html +='<tr class="product-list">';
				html +='	<td><input type="checkbox" class="product-info-checkbox" value="'+data['product'][i]['id']+'" /></td>';
				html +='	<td>'+data['product'][i]['id']+'</td>';
				html +='	<td>'+data['product'][i]['name']+'</td>';
				html +='	<td>'+data['product'][i]['type_name']+'</td>';
				html +='	<td><span style="display:block;">原价：'+data['product'][i]['orgin_price']+'</span><span style="display:block;">现价：'+data['product'][i]['now_price']+'</span></td>';
				html +='	<td>'+data['product'][i]['sell_count']+'</td>';
				html +='	<td>'+data['product'][i]['storenum']+'</td>';
				html +='	<td><img src="'+data['product'][i]['default_imgurl']+'" style="width: 100%;"></td>';
				if( data['product'][i]['ishot'] == 1 ){
					tag += '热卖/';
				}
				if( data['product'][i]['isnew'] == 1 ){
					tag += '新品/';
				}
				if( data['product'][i]['is_free_shipping'] == 1 ){
					tag += '包邮/';
				}
				if( data['product'][i]['is_virtual'] == 1 ){
					tag += '虚拟产品/';
				}
				if( data['product'][i]['is_currency'] == 1 ){
					tag += '<?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?>/';
				}
                if( data['product'][i]['is_mini_mshop'] == 1 ){
					tag += '小程序/';
				}
				if( tag != '' ){
					tag = tag.slice(0,-1);
				}
				html +='	<td>'+tag+'</td>'
				html +='	<td>'+data['product'][i]['createtime']+'</td>';
				html +='	<td><span class="delivery-button WSY-skin-bg" style="padding:3px 10px;" onclick="addProduct(1,'+data['product'][i]['id']+')">选择</span></td>';
				html +='</tr>';
			}
			if( productLen > 0){
				//翻页
				showProductTotalPage = Math.ceil(data['count'] / showProductEachPageNum);
				html_p +='<div class="page-box2">';
				html_p +='	<span class="data-num">共计'+data['count']+'条记录</span>';
				// html_p +='	<span class="show-data-num">每页<input type="text" id="show-data-num" width="25" value="'+showProductEachPageNum+'" />条</span>';
				// html_p +='	<span class="delivery-button WSY-skin-bg show-data-num-btn">确定</span> ';
				if( showProductCurrentPage > 1 ){	//当前是第一页不显示上一页
					html_p +='	<span class="delivery-button WSY-skin-bg page-left" onclick="goToLeftPage()">上一页</span> ';
				}
				html_p +='	<span class="current-page">当前第'+showProductCurrentPage+'页，共'+showProductTotalPage+'页</span> ';
				if( showProductCurrentPage < showProductTotalPage ){	//当前是最后一页不显示下一页
					html_p +='	<span class="delivery-button WSY-skin-bg page-right" onclick="goToRightPage()">下一页</span> ';
				}
				html_p +='	<input type="text" id="to-page-num2" width="25" value="'+showProductCurrentPage+'" >页 ';
				html_p +='	<span class="delivery-button WSY-skin-bg go-page-btn" onclick="goToPage()">跳转</span> ';
				html_p +='</div>';
			}

			$('.product-list').remove();
			$('.page-box2').remove();
			$('.list_table').append(html);
			$(".list_table").after(html_p);
		},
		error: function(err){
			alert('获取产品出错！');
		}
	});
}

//输入框按回车键触发搜索
$('body').find('.float-table-search>input').on('keydown',function(){
	if( event.keyCode == 13 ){
		searchProduct();
	}
});

//搜索
function searchProduct(){
	search_pid = $('#search-pid').val();
	search_pname = $('#search-pname').val();
	search_supply_id = $('#search-supply-id').val();
	search_ptype = $('#search-ptype').val();
	search_pfrom = $('#search-pfrom').val();
	search_ptag = $('#search-ptag').val();

	showProductCurrentPage = 1;
	showProductLimitStart = 0;
	showProductLimitEnd = showProductEachPageNum - 1;

	get_all_product();
}
//上一页
function goToLeftPage(){
	showProductCurrentPage --;
	showProductLimitStart -= showProductEachPageNum;
	showProductLimitEnd -= showProductEachPageNum;
	get_all_product();
}
//下一页
function goToRightPage(){
	showProductCurrentPage ++;
	showProductLimitStart += showProductEachPageNum;
	showProductLimitEnd += showProductEachPageNum;
	get_all_product();
}
//跳转
function goToPage(){
	var pageNum = $('#to-page-num2').val();

	if( pageNum < 1 || pageNum > showProductTotalPage || pageNum == showProductCurrentPage ){
		return;
	}
	showProductCurrentPage = pageNum;

	showProductLimitStart = (showProductCurrentPage - 1) * showProductEachPageNum;

	showProductLimitEnd = showProductLimitStart + showProductEachPageNum - 1;

	get_all_product();
}
//添加产品
function addProduct(type){
	var selectedProductId = new Array();
	var willAddproductId = '';
	var now_type = $('#now_type').val();
	if( type == 1 ){
		if( arguments[1] != undefined ){
			selectedProductId[0] = arguments[1];
			if( pidArr.indexOf(selectedProductId[0]) == -1 ){
				pidArr.push(selectedProductId[0]);
				willAddproductId = selectedProductId[0];
				addPidArr.push(selectedProductId[0]);

				// if( delPidArr.indexOf(selectedProductId[0]) >= 0 ){
					// delPidArr.splice(delPidArr.indexOf(selectedProductId[0]),1);
				// }
			}
		}
	} else {
		var selectedProduct = $('.product-info-checkbox:checked');
		if( selectedProduct.length == 0 ){
			alert('请选择产品！');
			return false;
		}
		selectedProduct.each(function(i) {
			selectedProductId[i] = $(this).val();
			if( pidArr.indexOf(selectedProductId[i]) == -1 ){
				pidArr.push(selectedProductId[i]);
				willAddproductId += selectedProductId[i]+',';
				addPidArr.push(selectedProductId[i]);

				// if( delPidArr.indexOf(selectedProductId[i]) >= 0 ){
					// delPidArr.splice(delPidArr.indexOf(selectedProductId[i]),1);
				// }
			}
		});
		if( willAddproductId.length > 0 ){
			willAddproductId = willAddproductId.slice(0,-1);
		}
	}
	pidStr = pidArr.join(',');
	addPidStr = addPidArr.join(',');
	// delPidStr = delPidArr.join(',');
  if( willAddproductId != '' ){
	$.ajax({
		url: 'ajax_handle.php?customer_id='+customer_id_en,
		dataType: 'json',
		type: 'post',
		data: {
			op : 'select_add_activitie_product',
			willAddproductId : willAddproductId
		},
		success: function(res){
			if( res ){
				var html = '';
				for( i in res ){
					html += '<tr data-pid="'+res[i]['id']+'" data-cost_price="'+res[i]['cost_price']+'" data-for_price="'+res[i]['for_price']+'">';
					html += '	<td>'+res[i]['id']+'</td>';
					html += '	<td class="product-name">'+res[i]['name']+'</td>';
					html += '	<td>'+res[i]['orgin_price']+'</td>';
					html += '	<td>'+res[i]['now_price']+'</td>';
					html += '	<td><input type="text" class="price" name="price" value="" onkeyup="clearFloat(this)" /></td>';
					html += '	<td><input type="text" class="stock" name="stock" value="" onkeyup="clearInt(this)" /></td>';
					html += '	<td><input type="number" class="pnumber" name="pnumber" value="" placeholder="-1表示不限制" /></td>';

					html += '	<td class="free_single"><input type="number"  class="new_duration" value=""  onfocus="check_time_section()" placeholder="-1表示公共配置" /></td>';

					html += '	<td></td>';

					html += '	<td class="free_single"><input type="number"   class="free_single_order" value=""  /></td>';
					html += '	<td ><input type="number"   class="success_num" value="" placeholder="-1为公共配置" /></td>';

					html += '	<td><span class="delivery-button WSY-skin-bg del-btn" onclick="delProduct('+res[i]['id']+',this)">移除</span></td>';
                    
                    html += '	<td ';
                    //console.log(choose_type)
                    if(choose_type==5){
                        html += '	style="display:none"';
                    }
                    html += '	<td class="alone_buy">';
                    alone_onoff = $('input[name=alone_onoff]').val();
                    html += '	<div class="shelter" style="width: 7%;height: 40px;ute;position: absolute;filter: alpha(opacity=60);z-index: 1002;  opacity:0.5;-moz-opacity:0.5;';
                    if(alone_onoff==1){
                        html += 'display: none;';
                    }
                    html += '   "></div>';
                    html += '	<div class="WSY_remind_main">';
                    html += '	<dl style="display:block;overflow: hidden;margin-top: 0;">';
                    html += '	<dt></dt>';
                    html += '	<dd style="float: left;">';
                    if(alone_onoff==1){
                        html += '	<ul style="background-color: rgb(255, 113, 112);">';
                        html += '	<p style="color: rgb(255, 255, 255); margin: 0px 0px 0px 22px;">开</p>';
                        html += '	<li onclick="p_alone_onoff('+res[i]['id']+',0)" class="WSY_bot" style="left: 0px;"></li>';
                        html += '	<span onclick="p_alone_onoff('+res[i]['id']+',1)" class="WSY_bot2"  style="display: none; left: 0px;"></span>';
                        html += '	</ul>';
                    }else{
                        html += '	<ul style="background-color: rgb(203, 210, 216);">';
                        html += '	<p style="color: rgb(127, 138, 151); margin: 0px 0px 0px 6px;">关</p>';
                        html += '	<li onclick="p_alone_onoff('+res[i]['id']+',0)" class="WSY_bot" style="display: none; left: 30px;"></li>';
                        html += '	<span onclick="p_alone_onoff('+res[i]['id']+',1)" class="WSY_bot2" style="display: block; left: 30px;"></span>';
                        html += '	</ul>';
                    }
                    html += '	<input type="hidden" name="p_alone_onoff" class="p_alone_onoff" id="p_alone_onoff_'+res[i]['id']+'" value="'+alone_onoff+'" />';
                    html += '	</dd>';
                    html += '	</dl>';
                    html += '	</div>';
                    html += '	</td>';
                    
					html += '</tr>';
				}
				$('.selected-table').append(html);
			}
			$('.float-table').fadeOut();
			check_time_section();
		},
		error: function(err){
			alert(err);
		}
	});
  }
}

$("body").on('click',".shelter",function(){
		alert("请先开启总开关！");
		}
);

$("body").on('click',".WSY_bot",function(){
		$(this).animate({left : '30px'});
		$(this).parent().find(".WSY_bot2").animate({left : '30px'});
		$(this).hide();
		$(this).parent().find(".WSY_bot2").show();
		$(this).parent().find("p").animate({margin : '0 0 0 13px'}, 500);
		
		$(this).parent().find("p").html('关');
		$(this).parent().css({backgroundColor : '#cbd2d8'});
		$(this).parent().find("p").css({color : '#7f8a97'});
		}
);
		
$("body").on('click',".WSY_bot2",function(){
    $(this).parent().find(".WSY_bot").animate({left : '0px'});
    $(this).animate({left : '0px'});
    $(this).parent().find(".WSY_bot").show();
    $(this).hide();
    $(this).parent().find("p").animate({margin : '0 0 0 27px'}, 500);
    
    $(this).parent().find("p").html('开');
    $(this).parent().css({backgroundColor : '#ff7170'});
    $(this).parent().find("p").css({color : '#fff'});
    }
);

//保存
$('.save-button').click(function(){
	check_time_section();
	var name = $('input[name=name]').val();
	if( name == '' || (/^\s+$/g).test(name) || name == undefined ){
		alert('请输入主题！');
		return;
	}
	var type = $('input[name=type]:checked').val();
	var luck_draw_num = 0;
	var luck_split_money = 0;
    var if_curr_pay   = 1;
    var coefficient   = 0;
    var head_times    = -1;
    var if_change_pro = 0;
    var if_refund = 0;
    var if_return_pro = 0;
    var is_since = 0;
	var shopcode_onoff =0;
	var coupon_onoff =0;
    var return_curr   = new Array();
    var is_return2    = 0;
	if( type == '' || type == undefined ){
		alert('请选择团类型！');
		return;
	}
	if( type == 2 ){
		luck_draw_num = $('input[name=luck_draw_num]').val();
		if( luck_draw_num == '' || parseInt(luck_draw_num) < 1 || parseInt(luck_draw_num) == undefined ){
			alert('抽奖团名额必须大于1！');
			return;
		}
	}

    if( type == 7 ){
        luck_split_money = $('input[name=luck_split_money]').val();
        if( luck_split_money == '' || parseInt(luck_split_money) < 0 || parseInt(luck_split_money) == undefined ){
            luck_split_money = 0;
        }
    }

    if( type == 5 ){
        var if_curr_pay = $('input[name=head_curr_use]:checked').val();
		if_refund = 0;//退款开关
        if_return_pro = 0;//退货开关
		if_change_pro = $('input[name=change_pro]').val();//换货开关
        if( if_curr_pay == '' || if_curr_pay == undefined ){
            alert('首次开团支付限制！');
            return;
        }
		coefficient = $('input[name=coefficient]').val();
		if( coefficient == '' || parseInt(coefficient) < 0 || parseInt(coefficient) > 50 || parseInt(coefficient) == undefined ){
			alert('请输入正确的系数！');
			return;
		}


        var $tableTr2 = $('.selected-table2').find('tr');
        //获取购物币返赠配置
        $tableTr2.each(function(i){
            if( i == 0 ){
                return;
            }
            var collage_times = $(this).find('.collage_times').html();
            var return_type = $('input[name=return_curr1]:checked').val();
            if( return_type == '' || return_type == undefined ){
                alert('请选择<?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?>'+collage_times+'返赠类型！');
                is_return2 = 1;
				return false;
            }
            if(return_type==1){
                var return_value = $('input[name=pro_ratio'+collage_times+']').val();
                if( return_value < 0 || return_value == '' ){
                    alert('请输入正确的返赠比例！');
                    is_return2 = 1;
                    return false;
                }
            }else{
                var return_value = $('input[name=fixed_amount'+collage_times+']').val();
                if( return_value < 0 || return_value == '' ){
                    alert('请输入正确的金额！');
                    is_return2 = 1;
                    return false;
                }
            }
            return_curr.push({
                collage_times : collage_times,
                return_type : return_type,
                return_value : return_value,
            });
        });

        if( is_return2 ){
            return_curr = new Array();
            return;
        }

	}else{
		if_change_pro = $('input[name=change_pro]').val();
        if_refund = $('input[name=if_refund]').val();
        if_return_pro = $('input[name=if_return_pro]').val();
	}
	is_since = $('input[name=is_since]').val();
	shopcode_onoff = $('input[name=shopcode_onoff]').val();
	shopcode_precent = $('input[name=shopcode_precent]').val();
	alone_onoff = $('input[name=alone_onoff]').val();
	coupon_onoff = $('input[name=coupon_onoff]').val();
	var start_time = $('#start_time').val();
	var end_time = $('#end_time').val();
	if( start_time == '' ){
		alert('请选择开始时间！');
		return;
	}
	if( end_time == '' ){
		alert('请选择结束时间！');
		return;
	}
	var group_size = $('input[name=group_size]').val();
	if( group_size == '' || parseInt(group_size) < 0 || parseInt(group_size) == undefined ){
		alert('成团人数必须大于1！');
		return;
	}
	var $user_level = $('.user_level:checked'),
		user_level = '';
	if( $user_level == undefined || $user_level.length == 0 ){
		alert('请选择用户等级！');
		return;
	} else {
		$user_level.each(function(){
			user_level += $(this).val()+'_';
		});
		user_level = user_level.slice(0,-1);
	}

	var $shopcode_limit = $('.shopcode_limit:checked'),
	    shopcode_limit = '';

    if( shopcode_onoff ==1 && ($shopcode_limit == undefined || $shopcode_limit.length == 0 && type!=5) ){
		alert('请选择<?php echo defined('PAY_CURRENCY_NAME') ? PAY_CURRENCY_NAME : '购物币'; ?>抵扣使用对象！');
		return;
	}

    if($shopcode_limit.length > 1){
		shopcode_limit = 3;
	}else {
		$shopcode_limit.each(function(){
			shopcode_limit = $(this).val();
		});
	}

/* 	var number = $('input[name=number]').val();
	if( parseInt(number) < 1 || number == '' ){
		if( parseInt(number) != -1 ){
			alert('请输入正确的活动参与次数限制！');
			return;
		}
	} */

    var ginseng_num = $('input[name=ginseng_num]').val();
    if( parseInt(ginseng_num) < 1 || ginseng_num == '' ){
		if( parseInt(ginseng_num) != -1 ){
			alert('请输入正确的参团次数限制！');
			return;
		}
	}

    var head_times = $('input[name=head_times]').val();
    if( parseInt(head_times) < 1 || head_times == '' ){
		if( parseInt(head_times) != -1 ){
			alert('请输入正确的开团次数限制！');
			return;
		}
	}

/*     var ginseng_num = $('input[name=ginseng_num]').val();
    if( ginseng_num == '' || parseInt(ginseng_num) < -1 || parseInt(ginseng_num) == undefined ){
        alert('请输入参团限制！');
        return;
    }

    var head_times = $('input[name=head_times]').val();
    if( head_times == '' || parseInt(head_times) < -1 || parseInt(head_times) == undefined ){
        alert('请输入开团限制！');
        return;
    }	 */

	var $tableTr = $('.selected-table').find('tr');
	var product_info = new Array();
	var is_return = 0;
	//获取活动产品信息
	$tableTr.each(function(i){
		if( i == 0 ){
			return;
		}
		var pid = $(this).data('pid'),
			cost_price = $(this).data('cost_price'),
			for_price = $(this).data('for_price'),
			price = $(this).find('.price').val(),
			stock = $(this).find('.stock').val(),
			pnumber = $(this).find('.pnumber').val(),
			pname = $(this).find('.product-name').html();

		var now_type=$('#now_type').val();
		var free_single_order 	 = $(this).find('.free_single_order').val();
		var success_num 	     = $(this).find('.success_num').val();
        var p_alone_onoff        = $(this).find('.p_alone_onoff').val();
		//判断开团日期
		var free_single_duration = $(this).find('.new_duration').val();
		var old_duration 		 = $(this).find('.old_duration').val();
		is_old_duration = 0;

		//判断是否是 old_天数
		if(free_single_duration == undefined && old_duration != undefined && old_duration !=''){
			is_old_duration = 1;
		}

		if( free_single_order == '' || parseInt(free_single_order) < 1 || parseInt(free_single_order) == undefined ){
			alert('排序必须大于1！');
			is_return = 1;
			return false;
		}
		if( parseInt(free_single_duration) <-1 || free_single_duration =='' || free_single_duration == 0){
			alert('请输入正确的开团天数！');
			is_return = 1;
			return false;
		}
		if( parseInt(success_num) < 1 || success_num == '' ){
			if( parseInt(success_num) != -1 ){
				alert('请输入正确的开团人数');
				is_return = 1;
				return false;
			}
		}
		//如果是新天数，即是没有上线的
		if(is_old_duration == 0){
			if_less_one = $(this).find('.new_duration').attr('less_one');

			start_time_1 = Date.parse(new Date(start_time));
			start_time_1 = start_time_1 / 1000;
			end_time_1 = Date.parse(new Date(end_time));
			end_time_1 = end_time_1 / 1000;
			now_time = Date.parse(new Date())/1000;
			if(now_time>start_time_1){
				start_time_1 = now_time;
			}
			max_day=Math.floor((end_time_1-start_time_1)/86400);
			//alert(if_less_one);
			if(if_less_one == 'true'){
				if(free_single_duration != -1){
					alert("非法数据");
					is_return = 1;
					return false;
				}
			}else{
				if( max_day<free_single_duration){
					alert('开团天数大于活动时间持续时间，现最大天数可设置为'+max_day);
					is_return = 1;
					return false;
				}else if(free_single_duration < 1){
/*					alert('团持续时间大于1天时，开团天数不得小于1');
					is_return = 1;
					return false;*/
				}
			}
		//是已经上线的天数则不进行判断
		}else{
			free_single_duration = old_duration;
		}


		if( price < 0 || price == '' ){
			alert('请输入正确活动价格！');
			is_return = 1;
			return false;
		}

		if( price < for_price ){
			alert('【'+pname+'】'+'活动价格小于成本价！');
			is_return = 1;
			return false;
		}

		if( price < cost_price ){
			alert('【'+pname+'】'+'活动价格小于供货价！');
			is_return = 1;
			return false;
		}

		if( stock < 0 || stock == '' ){
			alert('请输入正确活动库存！');
			is_return = 1;
			return false;
		}

		if( parseInt(pnumber) < 1 || pnumber == '' ){
			if( parseInt(pnumber) != -1 ){
				alert('请输入正确的产品购买数量！');
				is_return = 1;
				return false;
			}
		}

		product_info.push(pid+'_'+price+'_'+stock+'_'+pnumber+'_'+free_single_duration+'_'+free_single_order+'_'+success_num+'_'+p_alone_onoff);
	});

	if( is_return ){
		product_info = new Array();
		return;
	}
	if( product_info.length == 0 ){
		alert('至少添加一件产品！');
		return;
	}

    return_curr_json = JSON.stringify(return_curr);
    console.log(return_curr_json)

	$.ajax({
		url: 'ajax_handle.php?customer_id'+customer_id_en,
		dateType: 'json',
		type: 'post',
		data: {
			op : 'save_activity_info',
			keyid : keyid,
			name : name,
			type : type,
			luck_draw_num : luck_draw_num,
            luck_split_money : luck_split_money,
			start_time : start_time,
			end_time : end_time,
			group_size : group_size,
			user_level : user_level,
			//number : number,
			product_info : product_info,
			delPidStr : delPidStr,
            addPidArr : addPidArr,
			if_curr_pay : if_curr_pay,
            coefficient : coefficient,
            return_curr : return_curr_json,
            head_times : head_times,
            ginseng_num : ginseng_num,
            if_change_pro : if_change_pro,
            if_refund : if_refund,
            if_return_pro : if_return_pro,
            is_since : is_since,
			shopcode_onoff : shopcode_onoff,
			shopcode_limit : shopcode_limit,
			shopcode_precent : shopcode_precent,
			coupon_onoff : coupon_onoff,
			alone_onoff : alone_onoff
		},
		success: function(res){
            res = JSON.parse(res);
			if( res['code'] > 0 ){
				alert(res['content']);
                console.log(res['content'])
			} else {
				 if( comeFrom == 1 ){
					window.location.href = 'activityList.php?customer_id'+customer_id_en;
				} else {
					history.go(-1);
				}
			}
		},
		error: function(err){
			alert(err);
		}
	});
});
//正整数
function clearInt(obj){
	if(obj.value.length==1){obj.value=obj.value.replace(/[^1-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
}
function clearInt2(obj){
	obj.value=obj.value.replace(/[^1-9]/g,'');
	if(obj.value !=''){
		if(obj.value<1){
			obj.value=1;
		}else if(obj.value>5){
			obj.value=5;
		}
	}

}
//两位小数
function clearFloat(obj){
	obj.value = obj.value.replace(/[^\d.]/g,""); //清除"数字"和"."以外的字符
	obj.value = obj.value.replace(/^\./g,""); //验证第一个字符是数字而不是
	obj.value = obj.value.replace(/\.{2,}/g,"."); //只保留第一个. 清除多余的
	obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
	obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3'); //只能输入两个小数
}

function input_coefficient(obj){
	if(obj.value.length==1){obj.value=obj.value.replace(/[^0-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
    var coefficient = obj.value;
    if(coefficient>50){
        coefficient = 50;
        $('input[name=coefficient]').val(coefficient);
    }
    var html='';
    $('.coefficient_content').html('');
    for(i=1;i<=coefficient;i++){
        html +='<tr>';
        html +='<td class="collage_times">'+i+'</td>';
        html +='<td>';
        html +='<div class="curr_return1">';
        html +='<input type="radio" class="curr_return3" id="return_curr'+i+'_1" name="return_curr'+i+'" value="1" onclick="clearother(1,'+i+')" ';
        if(i!=1){
            html +='disabled';
        }
        html +='/><label class="curr_return4" for="">产品售价比例计算</label>';
        html +='<input class="curr_return5" type="text" name="pro_ratio'+i+'" id="pro_ratio'+i+'" value="" onkeyup="clearFloat(this)"/><a class="curr_return6">%</a>';
        html +='</div>';
        html +='<div class="curr_return2">';
        html +='<input type="radio" class="curr_return3" id="return_curr'+i+'_2" name="return_curr'+i+'" value="2"  onclick="clearother(2,'+i+')" '
        if(i!=1){
            html +='disabled';
        }
        html +='/><label class="curr_return4" for="">固定金额计算</label>';
        html +='<a class="curr_return6">￥</a><input class="curr_return5" type="text" name="fixed_amount'+i+'" id="fixed_amount'+i+'" value=""  onkeyup="clearFloat(this)"/>';
        html +='</div>';
        html +='</td>';
        html +='</tr>';
    }
    $('.coefficient_content').append(html);
}

function change_pro(obj){
	$("#change_pro").val(obj);
}
function if_refund(obj){
	$("#if_refund").val(obj);
}

function is_since(obj){
	$("#is_since").val(obj);
}

function shopcode_onoff(obj){
	$("#shopcode_onoff").val(obj);
	hide(obj);
}

function alone_onoff1(obj){
	$("#alone_onoff").val(obj);
    if(obj==1){
        $(".shelter").hide();
    }else if(obj==0){
        $(".shelter").show();
    }
}

function p_alone_onoff(pid,obj){
	$("#p_alone_onoff_"+pid).val(obj);
}

function coupon_onoff(obj){
	$("#coupon_onoff").val(obj);
}

function if_return_pro(obj){
	$("#if_return_pro").val(obj);
}

function clearother(obj1,obj2){
    if(obj1==1){
        $("#fixed_amount"+obj2).val('');
    }else{
        $("#pro_ratio"+obj2).val('');
    }
    var coefficient = $('input[name=coefficient]').val();
    for(i=2;i<=coefficient;i++){
        if(obj1==1){
            $("#fixed_amount"+i).val('');
        }else{
            $("#pro_ratio"+i).val('');
        }
        $('input[name=return_curr'+i+']').val('');
        $('input[name=return_curr'+i+']').removeAttr('checked');
        $("#return_curr"+i+"_"+obj1).prop('checked',true);
        $("#return_curr"+i+"_"+obj1).attr("checked","checked");
    }

}

function hide(obj){
	if(obj==0){
		$('#isShow_shopcodeLimit').hide();
		$('#isShow_shopcodeLimit_precent').hide();
	}else{
		$('#isShow_shopcodeLimit').show();
		$('#isShow_shopcodeLimit_precent').show();
	}
}

function clearNoNum_two(obj)
	{
//先把非数字的都替换掉，除了数字和.
		obj.value = obj.value.replace(/[^\d.]/g,"");
//必须保证第一个为数字而不是.
		obj.value = obj.value.replace(/^\./g,"");
//保证只有出现一个.而没有多个.
		obj.value = obj.value.replace(/\.{2,}/g,".");
//保证.只出现一次，而不能出现两次以上
		obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$",".");
//只能输入两个小数
		obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');

		if(parseFloat(obj.value)>parseFloat(100)){		//当输入购物币比例大于100时，默认变为100
				obj.value = 100;
		}
	}

</script>

</body>
</html>