<?php
header("Content-type: text/html; charset=utf-8");
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');
//头文件
require('../../../weixinpl/proxy_info.php');
require_once('../../../weixinpl/common/utility.php');
require('../../../weixinpl/common/common_from.php');
require('../../../weixinpl/mshop/select_skin.php');
require_once('../../../weixinpl/function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

$group_id = $configutil->splash_new($_GET["group_id"]);

$share = -1;
if( !empty( $_GET['share'] ) ){
    $share = $configutil->splash_new($_GET["share"]);
}


// define("InviteUrl","//".$http_host."/market/web/collageActivities/activities_detail_view.php?customer_id=".$customer_id_en."&group_id=".$group_id);
define("InviteUrl",Protocol.$http_host."/weixinpl/common_shop/jiushop/forward.php?customer_id=".$customer_id_en."&redirect_url=".urlencode(Protocol.$http_host."/market/web/collageActivities/activities_detail_view.php?group_id=".$group_id)."&exp_user_id=".$user_id);

//获取产品、活动信息
$condition = array(
    'cgot.id' => $group_id,
    'cgot.customer_id' => $customer_id,
    'ae.customer_id' => $customer_id,
    'cgot.isvalid' => true,
    // 'cat.isvalid' => true,
    // 'wcp.isout' => 0,
    'wcp.isvalid' => true,
    'cgpt.isvalid' => true
);
$filed = " cgpt.number AS pnumber,cat.start_time,cat.end_time,cat.user_level,cat.is_since,cat.number AS anumber,wcp.name AS pname,wcp.introduce,wcp.type_ids,wcp.now_price,wcp.default_imgurl,wcp.id as pid,cgot.success_num,cgot.join_num,cgot.endtime,cgot.status,cgot.type,cgot.price,cgpt.open_day,cgpt.stock,cgot.createtime,cgot.head_id,cgot.is_win,ae.type_name,wcp.yundian_id as pro_yundian_id ";

$info = $collageActivities->select_front_group($condition,$filed)['data'][0];

$type_ids = $info['type_ids'];
// $info['start_time'] = date('Y年m月d日',strtotime($info['start_time']));
// $info['end_time'] = date('Y年m月d日',strtotime($info['end_time']));
// var_dump($info);
$group_status = $info['status'];



$type_str = $info['type_name'];
$type_explain = $info['type_name'].'说明';
$head_id = $info['head_id'];//团长user_id
$is_since = $info['is_since'];//是否自购 0-否 1-是
/* switch( $info['type'] ){
	case 1:
		$type_explain = '拼团说明';
	break;
	case 2:
		$type_explain = '抽奖团说明';
	break;
	case 3:
		$type_explain = '秒杀团说明';
	break;
	case 4:
		$type_explain = '超级团说明';
	break;
	case 5:
		$type_explain = '抱抱团说明';
	break;
	case 6:
		$type_explain = '免单团说明';
	break;
} */


//获取参团人员
$condition2 = array(
    'ccot.group_id' => $group_id,
    'ccot.customer_id' => $customer_id,
    'ccot.isvalid' => true,
    'ccot.status' => array(2,3,4,5,6,7,8),
    'wu.isvalid' => true
);
$filed2 = " ccot.user_id,ccot.status,ccot.user_id,ccot.is_head,ccot.paytime,wu.weixin_name,wu.name,wu.province,wu.weixin_headimgurl ";
$now_status = 5;
$group_user = $collageActivities->select_front_crew($condition2,$filed2)['data'];
foreach($group_user as $kg=>$vg){
    if($vg['user_id'] == $user_id){
        $now_status = $vg['status'];
        if($now_status==3){
            $now_status = 6;
        }
    }
}

//获取正在付款的用户
$condition4 = array(
    'csol.group_id' => $group_id,
    'csol.customer_id' => $customer_id,
    'csol.isvalid' => true,
    'wu.isvalid' => true
);
$filed4 = " csol.createtime,wu.weixin_name,wu.name,wu.province,wu.weixin_headimgurl ";
$paying_user = $collageActivities->get_paying_user($condition4,$filed4)['data'];
foreach( $paying_user as $key => $val ){
    $group_user[] = $paying_user[$key];
}

//获取团推荐产品设置
$condition3 = array(
    'caprst.customer_id' => $customer_id,
    'caprst.isvalid' => true
);
$filed3 = " caprst.id,caprst.is_open,caprst.pattern,caprsyst.num,caprsyst.type,caprsyst.style,caprsyst.sort ";
$recommend_set = $collageActivities->getProductRecommendationSet($condition3,$filed3)['content'];

//获取团说明
$get_explain = $collageActivities->getExplain($customer_id)['content'];
$explain = '';
foreach( $get_explain as $k => $v ){
    if( $v['type'] == $info['type'] ){
        $explain = $get_explain[$k];
        break;
    }
}
// var_dump($explain);

//获取团订单产品信息
$query_mes = "SELECT ccot.activitie_id,ccot.group_id,ccopmt.pid,ccopmt.prvalues,ccopmt.price,wcp.is_supply_id
				FROM collage_crew_order_t AS ccot
				LEFT JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccopmt.batchcode=ccot.batchcode
				LEFT JOIN weixin_commonshop_products AS wcp ON ccopmt.pid=wcp.id
				WHERE ccot.customer_id=".$customer_id." AND ccot.group_id=".$group_id." AND ccot.is_head=1 AND ccot.isvalid=true";
$result_mes = _mysql_query($query_mes) or die('Query_mes failed:'.mysql_error());

$product_mes = mysql_fetch_assoc($result_mes);

//参团购买产品数量
$rcount = 1;
if( !empty($product_mes['prvalues']) ){
    $prvalues = explode('_',$product_mes['prvalues']);

    foreach( $prvalues as $k => $v ){
        //如果有批发属性，则按最少批发数量
        $query_pros = "SELECT wholesale_num FROM weixin_commonshop_pros WHERE id=".$v;
        $result_pros = _mysql_query($query_pros) or die('Query_pros failed:'.mysql_error());
        $wholesale_num = mysql_fetch_assoc($result_pros)['wholesale_num'];

        if( $wholesale_num > 0 ){
            $rcount = $wholesale_num;
            break;
        }
    }
}

/* 产品属性开始 */
$propertyids        = "";	//属性id
$query = 'SELECT propertyids FROM weixin_commonshop_products where id=' . $product_mes['pid'] ;
$result = _mysql_query($query) or die('Query failed: ' . mysql_error());
while ($row = mysql_fetch_object($result)) {
    $propertyids = $row->propertyids;
}

$proLst = new ArrayList();

$propertyarr = explode("_",$propertyids);
$pcount = count($propertyarr);
for($i=0;$i<$pcount;$i++){
    $property_id = $propertyarr[$i];
    $proLst->Add($property_id);
}
$default_pids = "";
$proHash = new HashTable();

//var_dump($proLst);

/* 产品属性结束 */

//查询主属性是否有图片
$attr_img_str   = "'0':{'attr':'默认图','img':'".$info['default_imgurl']."'},";
$attr_parent_id = -1;
$attr_img_array = array();
$attr_index     = 0;
$sql_attr_img   = 'select wxcpai.attr_id,wxcp.name,wxcpai.img,wxcp.parent_id from weixin_commonshop_product_attrimg wxcpai inner join weixin_commonshop_pros wxcp on wxcpai.attr_id = wxcp.id where wxcpai.customer_id='.$customer_id.' and wxcpai.pro_id='.$product_mes['pid'].' and wxcpai.status=1';

$result_attr_img = _mysql_query($sql_attr_img) or die('Query failed sql_attr_img: ' . mysql_error());
while ($row_attr_img = mysql_fetch_object($result_attr_img)) {

    $attr_parent_id  = $row_attr_img->parent_id;
    $temp_img        = $row_attr_img->img;
    $temp_attr_id    = $row_attr_img->attr_id;
    $temp_name       = $row_attr_img->name;
    if( !empty($temp_img) && !empty($temp_attr_id) ){ //'0':{'attr':'默认图','img':default_imgurl}
        $attr_index++;
        $attr_img_str .= "'".$attr_index."':{'attr':'".$temp_name."','img':'".$temp_img."'},";
        //$attr_img_str .= $temp_attr_id.'_'.$temp_img.'_'.$temp_name.',';
        $attr_img_array[$temp_attr_id] = array('img'=>$temp_img,'index'=>$attr_index);
    }
}
$attr_img_str = substr($attr_img_str,0,strlen($attr_img_str)-1);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $info['pname'];?></title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta content="telephone=no" name="format-detection">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/assets/css/amazeui.min.css" />
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/order_css/global.css" />
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/css_<?php echo $skin ?>.css" />
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/goods/product_detail.css" />
    <link href="/weixinpl/mshop/css/collage_activity/reset.css" type="text/css" rel="stylesheet">
    <link href="/weixinpl/mshop/css/collage_activity/style.css" type="text/css" rel="stylesheet">
    <!-- 属性图预览 -->
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/ImgPreview.css" />
    <link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/swiper.min.css" />
</head>
<style>
    .share_shadow{
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #000;
        opacity: 0.6;
        z-index: 999;
        text-align: right;
    }
    .share_guide{
        width: 300px;
        margin-top: -70px;
        margin-right: 20px;
    }
    .collage_tip img{
        max-width: 100%;
    }
    .paying_box{
        background-color: #2EAB31 !important;
        display: inline-block;
        padding: 3px;
        color: #fff;
        position: absolute;
        right: 0;
        margin-right: 10px;
        margin-top: -15px;
        border-radius: 3px;
    }
    .cell_icon {
        position: absolute;
        left: 5px;
        top:5px;
        background-image: url(img/cell_icon.png);
        display: block;
        width: 40px;
        height: 20px;
        background-repeat: no-repeat;
        background-size: 40px 20px;
        font-size: 1rem;
        color: #fff;
        line-height: 18px;
        text-align: center;font-size:12px;
        overflow: hidden;box-sizing:border-box;padding:0 2px;
    }
</style>
<script>
    function Hashtable() {
        this._hash = {};
        this._count = 0;
        this.add = function (key, value) {
            if (this._hash.hasOwnProperty(key)) return false;
            else {
                this._hash[key] = value;
                this._count++;
                return true;
            }
        }
        this.remove = function (key) {
            delete this._hash[key];
            this._count--;
        }
        this.count = function () {
            return this._count;
        }
        this.items = function (key) {
            if (this.contains(key)) return this._hash[key];
        }
        this.contains = function (key) {
            return this._hash.hasOwnProperty(key);
        }
        this.clear = function () {
            this._hash = {};
            this._count = 0;
        }
    }

    selproHash = new Hashtable();
</script>
<body style="background:#f8f8f8">
<div class="xiangqing">
    <div class="imgbox02" ><a href="/weixinpl/mshop/product_detail.php?pid=<?php  echo $product_mes['pid'];?>&customer_id=<?php echo $customer_id_en; ?>&is_collage_from=1">
            <img src="
                <?php
            if(empty($info['default_imgurl'])){
                $query6 = "select imgurl from weixin_commonshop_product_imgs where isvalid=true and customer_id='".$customer_id."' and product_id='".$info['pid']."' limit 1";

                $result6 = _mysql_query($query6) or die('query failed6'.mysql_error());
                while($row6 = mysql_fetch_object($result6)){
                    $product_default_imgurl = $row6->imgurl;	//商品封面图
                }
                echo $product_default_imgurl;
            }else{
                echo $info['default_imgurl'];
            }
            ?>" style="height: 111px;">
            <span class="cell_icon">
					<?php
                    echo $info['type_name'];
                    ?>
				</span>
        </a>
    </div>
    <div class="textbox">
        <p class="p01"><a href="/weixinpl/mshop/product_detail.php?pid=<?php  echo $product_mes['pid'];?>&customer_id=<?php echo $customer_id_en; ?>=&is_collage_from=1"><?php echo $info['pname'];?></a></p>
        <?php
        if ( !empty($info['introduce']) ) {
            $introduce = json_decode($info['introduce'], true);
            ?>
            <p class="p02" style="color: <?php echo $introduce[0]['color']?>;"><?php echo $introduce[0]['content'];?></p>
            <?php
        }
        ?>
        <div class="p_array">
            <p class="p04"><span><?php if(OOF_P != 2) echo OOF_S ?></span><?php echo $info['price'];?><span><?php if(OOF_P == 2) echo OOF_S ?></span></p>
            <?php
            if( $info['now_price'] > $info['price'] ){
                ?>
                <p class="p05">单人价<?php if(OOF_P != 2) echo OOF_S ?><?php echo $info['now_price'];?><?php if(OOF_P == 2) echo OOF_S ?></p>
                <?php
            }
            ?>
        </div>
        <p class="p03"><?php echo $info['success_num'];?>人团</p>
        <?php
        $explain['status'] = 1;
        if( !empty($explain) and $explain['status'] == 1 ){
            ?>
            <p class="p06"><?php echo $explain['title'];?></p>
            <?php
        }
        ?>
    </div>
</div>
<?php
if( !empty($explain) and $explain['status'] == 1 ){
    ?>
    <div class="xq_cont" style="display:none;">
        <div class="div01">
            <p class="p01"><span><?php echo $explain['title'];?></span><img src="/weixinpl/mshop/images/collage_activity/tanhao.png"></p>
            <p class="p02">活动时间：<?php echo $info['start_time'];?> - <?php echo $info['end_time'];?></p>
            <img class="ys_jt" src="/weixinpl/mshop/images/collage_activity/xiangshang_icon.png">
        </div>
        <div class="div02 collage_tip">
            <?php echo $explain['content'];?>
        </div>
    </div>
    <?php
}
?>
<div class="ct_cont">
    <p class="p01">参团的人</p>
    <div class="person">
        <?php
        if( !empty($group_user) ){
            $curr_user = 0;
            foreach( $group_user as $k => $v ){
                //只显示8个人
                if ( $curr_user >= 8 ) {
                    break;
                }

                if( empty($v['weixin_headimgurl']) ){
                    $v['weixin_headimgurl'] = '../../common/custom_temp/images/username.png';
                } else {
                    $pos = strpos($v['weixin_headimgurl'],"http://");
                    $pos2 = strpos($v['weixin_headimgurl'],"https://");

                    if( $pos===0 || $pos2===0 ){

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
                <div class="box">
                    <div class="sf">
                        <img src="<?php echo $v['weixin_headimgurl'];?>">
                        <?php
                        if( $v['is_head'] == 1 ){
                            ?>
                            <p class="sf_box">
                                团长
                            </p>
                            <?php
                        } else if( $v['paying'] == 1 ){
                            ?>
                            <p class="sf_box" style="background-color: #2EAB31 !important;">
                                付款...
                            </p>
                            <?php
                        }
                        ?>
                    </div>
                    <p class="text01"><?php echo $v['weixin_name']?$v['weixin_name']:$v['name'];?></p>
                    <p class="text02"><?php echo $v['province'];?></p>
                </div>
                <?php
                $curr_user++;
            }
        }
        ?>
    </div>
    <?php
    $join_num = count($group_user);
    $rest_num = $info['success_num'] - $join_num;
    if( $rest_num > 0 && $group_status == 1 ){
        ?>
        <p class="p02 <?php if($from_type==1){echo 'share-btn';}?>">还差<span><?php echo $rest_num;?></span>人成团，赶紧邀请好友！！</p>
        <?php
    } else if ( $group_status == 3 || $group_status == 4 || $group_status == 5 ) {
        ?>
        <?php if($info['type']==6){ ?>
            <p class="p02">
                <?php
                if($now_status==6){
                    echo " 您的拼团未抽奖成功,已取消";
                }else{
                    echo " 恭喜您，成团成功！";
                }
                ?>
            </p>
        <?php }else{ ?>
            <p class="p02">
                <?php
                if($now_status==6){
                    echo " 您的拼团未抽奖成功,已取消";
                }else{
                    echo " 恭喜您，拼团成功！";
                }
                ?>
            </p>
            <?php
        } }
    if( $group_status == 1 ){
        ?>
        <div class="timebox">
            <p class="txt02">剩余时间：</p>
            <div class="sfm hour">0</div>
            <span>时</span>
            <div class="sfm minute">0</div>
            <span>分</span>
            <div class="sfm second">0</div>
            <span>秒</span>
        </div>
        <?php
    }
    if( !empty($group_user) ){
        ?>
        <div class="show_user" onclick="showmoreuser(this)">
            <p>查看全部参团详情</p>
            <img style="width:7px" src="/weixinpl/mshop/images/collage_activity/dianxiala.png">
        </div>
        <div class="show_tz">
            <?php
            foreach( $group_user as $k => $v ){
                if( empty($v['weixin_headimgurl']) ){
                    $v['weixin_headimgurl'] = '../../common/custom_temp/images/username.png';
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
                <div>
                    <div class="tz_img">
                        <img class="img01" src="<?php echo $v['weixin_headimgurl'];?>">
                    </div>
                    <div class="tz_text">
                        <div class="div01">
                            <?php
                            if( $v['is_head'] == 1 ){
                                ?>
                                <p class="p01">团长 </p>
                                <?php
                            }
                            ?>
                            <p class="p02"><?php echo $v['weixin_name']?$v['weixin_name']:$v['name'];?></p>
                        </div>
                        <div class="div02">
                            <p class="p01"><?php echo $v['paytime']?$v['paytime']:$v['createtime'];?></p>
                            <p class="p02"></p>
                            <?php
                            if( $v['is_head'] == 1 ){
                                ?>
                                <p class="p03">
                                    开团
                                    <?php
                                    if(($v['status'] == 6)||($v['status'==3])){
                                        echo "(已退款)";
                                    }
                                    ?>
                                </p>
                                <?php
                            } else {
                                ?>
                                <p class="p03">
                                    参团
                                    <?php
                                    if($v['status'] == 6){
                                        echo "(已退款)";
                                    }
                                    ?>
                                </p>
                            <?php } ?>
                        </div>
                        <div class="div03">
                            <p class="p01"><?php echo $v['province'];?></p>
                        </div>
                        <?php
                        if( $v['paying'] == 1 ){
                            ?>
                            <div class="paying_box">付款...</div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
            ?>
            <div class="jt">
                <img src="/weixinpl/mshop/images/collage_activity/la.png">
            </div>
        </div>
        <?php
    }
    ?>
</div>
<?php
if( !empty($recommend_set) and $recommend_set['is_open'] == 1 ){
    ?>
    <div class="cn_box">
        <p class="p01">猜你喜欢</p>
        <div class="cnxh">

        </div>
        <div class="btn_array">
            <div class="div01">
                <a onclick="getProduct();">换一组</a>
            </div>
            <div class="div02">
                <a href="product_list_view.php?op=ordinary&customer_id=<?php echo $customer_id_en;?>">查看更多</a>
            </div>
        </div>
    </div>
    <?php
}
?>
<div style="position: fixed;bottom: 0;width: 100%;background-color: #fff;">
    <div class="join_btn join_group" style="display:none;">
        我要参团
    </div>
    <div class="join_btn group_end" style="display:none;">
        已结束
    </div>
</div>
<div style="height: 65px;"></div>
<div class="share_shadow" style="display:<?PHP if( $share > 0 ){ echo 'block'; }else{echo 'none';} ?>;">
    <img class="share_guide" src="/weixinpl/mshop/images/collage_activity/share-guide.png">
</div>

<!-- dialog -->
<div class="am-share shangpin-dialog" >
    <div class="content-base  row1"><div class="dlg-row1-cell0"><img class="am-img-thumbnail am-circle" onclick="closeDialog();" src="/weixinpl/mshop/images/goods_image/2016042704.png" ></div></div>
    <!-- 加入购买 -->
    <div class = "content-base   dialog-content">
        <!-- <img  class="am-img-thumbnail am-circle close-img" src = "./images/goods_image/2016042704.png" > -->
        <div class = "content-base content-row1">
            <div class = "dlg-content-row1-left" id="Preview" value="0">
                <img src = "<?php echo $info['default_imgurl']; ?>" style="vertical-align: middle;">
            </div>
            <div class = "dlg-content-row1-right">
                <div class = "dlg-content-row1-right-top1">
                    <span><?php echo $info['pname']; ?></span>
                </div>
                <div class = "dlg-content-row1-right-top2">
	  					<span>
							<?php if(OOF_P != 2) echo OOF_S ?>
							<span id="now_price">
								<?php echo number_format($info['price'],2); ?>
							</span>
							<?php if(OOF_P == 2) echo OOF_S ?>
						</span>
                </div>
            </div>
        </div>
        <div class="ov-class pros-box">
            <?php
            $query="select id ,name from weixin_commonshop_pros where  parent_id=-1 and isvalid=true and customer_id=".$customer_id;
            $result = _mysql_query($query) or die('Query failed11: ' . mysql_error());
            while ($row = mysql_fetch_object($result)) {
                $prname = $row->name;
                $prid = $row->id;
                $ishasSet_t =false;
                ?>
                <div id="pro_<?php echo $prid;?>" class = "pro_div <?php if($attr_parent_id == $prid ){ echo 'parent_attr_img';}?>">
                    <div class = "big_pro_name">
                        <span><?php echo $prname;?>:&nbsp;&nbsp;</span>
                    </div>
                    <script>var subids = "";</script>
                    <div pos_name="<?php echo $prname;?>" class = "small_pro_div">
                        <?php
                        $if_attr_img = 0;
                        if($attr_parent_id == $prid ){
                            $if_attr_img = 1;
                        }
                        $query2="select id,name from weixin_commonshop_pros where isvalid=true and parent_id=".$prid;
                        $result2 = _mysql_query($query2) or die('Query failed12: ' . mysql_error());
                        $i					= 1;
                        $fir_subid			= -1;
                        $pro_shownameLst 	= "";
                        while ($row2 = mysql_fetch_object($result2)) {
                            $subname	= $row2->name;
                            $subid		= $row2->id;
                            if( $i == 1 ){
                                $fir_subid=$subid;
                            }
                            $has_attr_img = 0;
                            $attr_img_path= '';
                            $temp_index   = 0;
                            if( array_key_exists($subid,$attr_img_array) && $if_attr_img)
                            {
                                $has_attr_img = 1;
                                $attr_img_path= $attr_img_array[$subid]['img'];
                                $temp_index   = $attr_img_array[$subid]['index'];
                            }
                            if($proLst->Contains($subid) and !empty($subname)){
                                $ishasSet	= true;
                                $ishasSet_t = true;
                                if(empty($pro_shownameLst)){
                                    $pro_shownameLst=$subid;
                                }else{
                                    $pro_shownameLst=$pro_shownameLst."_".$subid;
                                }
                                ?>
                                <div pos_id="<?php echo $subid;?>" attr_index="<?php echo $temp_index;?>" class="pos_<?php echo $prid; ?> pos_div pt_pros_div" onclick="chooseDiv(<?php echo $prid; ?>,<?php echo $subid; ?>);" id = "pro_div_<?php echo $prid; ?>_<?php echo $subid; ?>" attr_img="<?php echo $attr_img_path;?>">
                                    <span class="span_pos_<?php echo $prid; ?>" ><?php echo $subname; ?></span>
                                </div>
                                <script>subids = subids+<?php echo $subid; ?>+
                                    "_";</script>
                                <?php
                            }
                        }
                        if(!$ishasSet_t){
                            echo "<script>document.getElementById('pro_".$prid."').style.display='none';</script>";
                        }else{

                            ?>
                            <input type=hidden name="prvalues" id="invalue_<?php echo $prid; ?>" value="" />
                        <?php } ?>
                        <script>
                            if (subids != "") {
                                subids = subids.substring(0, subids.length - 1);
                            }
                            selproHash.add(<?php echo $prid; ?>, subids);
                        </script>
                    </div>
                </div>
                <?php
                if ($ishasSet_t) {
                    if (empty($showpname)) {
                        $showpname = $showpname . $prname;
                    } else {
                        $showpname = $showpname . "," . $prname;
                    }
                    $proHash->insert($prname, $pro_shownameLst);
                    //echo "snamelst=======".$prname."========".$pro_shownameLst;
                }
            }
            if ($default_pids != "") {
                $default_pids = rtrim($default_pids, "_");
            }
            ?>

            <?php
            $id = -1;
            $is_wholesale = 0;
            $wholesale_parentid = "";
            $wholesale_childid = "";
            $query = "SELECT id,wholesale_parentid,wholesale_childid FROM weixin_commonshop_product_extend WHERE isvalid=true AND customer_id=$customer_id AND pid=".$product_mes['pid']." LIMIT 1";
            //echo $query;
            $result= _mysql_query($query) or die('Query failed 1309: ' . mysql_error());
            while( $row = mysql_fetch_object($result) ){
                $id 				= $row->id;
                $wholesale_parentid = $row->wholesale_parentid;
                $wholesale_childid 	= $row->wholesale_childid;
            }
            if($id > 0){
                $is_wholesale = 1;//判断是否拥有批发属性
                $parent_id = -1;
                $parent_name = "";
                $sql = "SELECT id,name FROM weixin_commonshop_pros WHERE isvalid=true AND id=$wholesale_parentid AND parent_id=-1 AND is_wholesale=1 LIMIT 1";
                $res = _mysql_query($sql) or die('Query failed 1320: ' . mysql_error());
                while( $row_wholesale = mysql_fetch_object($res) ){
                    $parent_id = $row_wholesale->id;
                    $parent_name = $row_wholesale->name;
                }

                ?>
                <div id="pro_<?php echo $parent_id;?>" class = "pro_div">
                    <div class = "big_pro_name">
                        <span><?php echo $parent_name;?>:&nbsp;&nbsp;</span>
                    </div>
                    <div pos_name="<?php echo $parent_name;?>" class="small_pro_div">
                        <?php

                        $wholesale_arr = array();
                        $wholesale_arr = explode("_",$wholesale_childid);
                        for($i=0;$i<count($wholesale_arr);$i++){

                            $child_id = -1;
                            $child_name = "";
                            $wholesale_num = 1;
                            $query_child = "SELECT id,name,wholesale_num FROM weixin_commonshop_pros WHERE isvalid=true AND parent_id=$parent_id AND id=$wholesale_arr[$i]";
                            $result_child= _mysql_query($query_child) or die('Query failed 1335: ' . mysql_error());
                            while( $info_child = mysql_fetch_object($result_child) ){
                                $child_id = $info_child->id;
                                $child_name = $info_child->name;
                                $wholesale_num = $info_child->wholesale_num;

                                ?>
                                <div pos_id="<?php echo $child_id;?>" class="pos_<?php echo $parent_id; ?> pos_div wholesale_div" onclick="chooseDiv(<?php echo $parent_id; ?>,<?php echo $child_id; ?>);" id = "pro_div_<?php echo $parent_id; ?>_<?php echo $child_id; ?>" pos_num="<?php echo $wholesale_num ;?>" >
                                    <span class="span_pos_<?php echo $parent_id; ?>" ><?php echo $child_name;?></span>
                                </div>

                                <!-- <script>subids = subids+<?php echo $child_id; ?>+"_";</script> -->
                                <input type=hidden name="prvalues" id="invalue_<?php echo $parent_id; ?>" value="" />
                            <?php }}?>

                    </div>
                    <input type="hidden" name="wholesale_num" id="wholesale_num" value="1">
                </div>
            <?php }?>

        </div>
        <div id="numDiv" class = "content-base content-row4">
            <span class = "dlg-content-row4-span">数量:&nbsp;&nbsp;</span>
            <div class = "num_div">
                <div class = "minus button buttonclick" onclick="minusNum();" ><span>-</span></div>
                <div class = "count_div">
                    <!-- <span id = "mount_count">3</span> -->
                    <input onblur="modify();" type="text" value="1" id="mount_count" autocomplete="off" onkeyup="clearNoNum(this)" onafterpaste="clearNoNum(this)">
                </div>
                <div class = "add button buttonclick" onclick="addNum();"><span>+</span></div>
            </div>
            <div id="stock_div">
                库存:
                <span id="stock">
					<?php echo $info['stock']; ?>
					</span>
            </div>
        </div>
        <!-- 加入购买 -->

        <div class = "div-clear"></div>


        <div class = "content-button" id = "div_buyNow" >
            <button id="collage_buyNow" type="button" onclick="Join_Group();" class="am-btn am-btn-danger">立即参团</button>
        </div>
    </div>
    <!-- dialog -->
</div>

<!-- dialog1 -->
<script src="/weixinpl/mshop/js/jquery-1.12.1.min.js"></script>
<script type="text/javascript" src="/weixinpl/mshop/js/global.js"></script>
<script type="text/javascript" src="/weixinpl/mshop/js/ImgPreview.js"></script>
<script type="text/javascript" src="/weixinpl/mshop/js/swiper.min.js"></script>

<!--悬浮按钮-->
<?php  include_once('../../../weixinpl/mshop/float.php');?>
<!--悬浮按钮-->
<script>
    var recommend_set = eval(<?php echo json_encode($recommend_set);?>);
    var customer_id_en = '<?php echo $customer_id_en;?>';
    var type_ids = '<?php echo $type_ids;?>';
    var product_mes = eval(<?php echo json_encode($product_mes);?>);
    var group_status = '<?php echo $group_status;?>';
    var now_status = '<?php echo $now_status;?>';
    var is_win = '<?php echo $info['is_win'];?>';
    var rcount = <?php echo $rcount;?>;
    var user_id = '<?php echo $user_id;?>';
    var head_id = '<?php echo $head_id;?>';
    var is_since = '<?php echo $is_since;?>';
    var share_url = '<?php echo InviteUrl;?>';
    var title = '<?php echo $info['pname'].'【'.$type_str.'】';?>';
    var desc = '<?php echo mysql_escape_string($info['introduce']);?>';
    var imgUrl = '<?php echo Protocol;?>'+window.location.host+'<?php echo $info['default_imgurl'];?>';
    var is_wholesale = "<?php echo $is_wholesale; ?>";//是否拥有批发属性
    var default_pids = "<?php echo $default_pids; ?>";//产品属性
    var share_type = 4;
    var timeInterVal = '';
    var rest_num = <?php echo $rest_num;?>;
    var attr_img_str 		= "{<?php echo $attr_img_str;  ?>}";	//主属性图片
    var attr_parent_id 		= '<?php echo $attr_parent_id;?>';	//主属性父id
    var pro_yundian_id      = '<?php echo $info['pro_yundian_id'];?>';//云店产品ID
    //主属性图片加载
    console.log(attr_img_str);
    console.log(attr_parent_id);
    $(function(){
        switch( group_status ){
            case '1':
                time();
                timeInterVal = setInterval(time,1000);
                break;
            case '2':
            case '6':
                $('.group_end').show();
                showAlertBox(2, group_status);
                break;
            case '3':
            case '4':
            case '5':
                showAlertBox(1, group_status);
                break;
        }


        getProduct();

        /*var imgW = $('.imgbox02 img').eq(0).width();
        $('.imgbox02 img').eq(0).height(imgW);*/

        var clone = $('.parent_attr_img').clone();
        $('.parent_attr_img').remove();
        $('.pros-box').prepend(clone);

        //属性点击事件
        $(".parent_attr_img").find(".pt_pros_div").click(function(){
            var attr_img = $(this).attr('attr_img');
            var attr_id  = $(this).attr('attr_index');
            if(attr_img != ''){
                $('#Preview').find('img').attr('src',attr_img);
                $('#Preview').attr('value',attr_id);
            }else{
                $('#Preview').find('img').attr('src',imgUrl);
                $('#Preview').attr('value',0);
            }
        });
    })


    //拼团说明
    $('.p06').click(function(){
        $('.xq_cont').toggle(300);
    })
    $(".ys_jt").click(function(){
        $('.xq_cont').fadeOut(300);
    })
    /*点击更多*/
    function showmoreuser(obj){
        var $obj=$(obj);
        var $src=$obj.find("img").attr("src");

        if($src.match("dianxiala")){
            $(".show_tz").show();
            $src=$src.replace("dianxiala","dianshangla")
            $("img",$obj).attr("src",$src);
        }else{
            $(".show_tz").hide();
            $src=$src.replace("dianshangla","dianxiala")
            $("img",$obj).attr("src",$src);
        }

    }

    //分享
    $(".share-btn").click(function(){
        $(".share_shadow").show();
    })
    $(".share_shadow").click(function(){
        $(".share_shadow").hide();
    })

    /*倒数时间*/
    var endTime='<?php if($info['endtime']!="0000-00-00 00:00:00"){ echo strtotime($info['endtime']); }else{
        echo strtotime($info['end_time']);
    }?>';
    var $hour=$(".hour");
    var $minu=$(".minute");
    var $second=$(".second");
    var num =0;

    var num_time = new Date($.ajax({async: false}).getResponseHeader("Date")).getTime();
    function time(){
        num_time = num_time + 1000;
        // $.ajax({type:"HEAD",url:'<?php echo Protocol.$http_host ?>/weixinpl/mshop/ajax_get_servertime.php',async:false,complete:function(x){ nowTime = new Date(x.getResponseHeader("Date")).getTime().toString().substring(0,10);}})//获取服务器时间

        // nowTime = new Date($.ajax({async: false}).getResponseHeader("Date")).getTime();
        nowTime = num_time/1000;

        var moveTime=endTime-nowTime;
        if(moveTime>0){
            // console.log(moveTime)
            var hh=parseInt(moveTime/60/60);
            var mm=parseInt(moveTime/60)%60;
            var ss=parseInt(moveTime)%60;
            $hour.html(hh)
            $minu.html(mm)
            $second.html(ss)
            if( rest_num == 0 ){
                $('.join_group').off('click');
                $('.join_group').css('background-color','#ccc');
            }
            /*                     if(is_since==0){//没开自购
                                    //if(user_id!=head_id){//不是团长
                                        $('.join_group').show();
                                    //}
                                        $('.join_group').off('click');
                                    $('.join_group').css('background-color','#ccc');
                                }else{//开自购 */
            $('.join_group').show();
            /*      } */

            $('.group_end').hide();

        }else{
            $(".txt02").html("已结束");
            clearInterval(timeInterVal);
            $hour.html(0)
            $minu.html(0)
            $second.html(0)
            $('.join_group').hide();
            $('.group_end').show();
        }

        if( nowTime == 0 ){	//没有网络的情况下无法获取当前时间
            $(".txt02").html("您的网络异常");
            // clearInterval(timeInterVal);
            $hour.html(0)
            $minu.html(0)
            $second.html(0)
            $('.join_group').hide();
            $('.group_end').show();
        }
    }


    //提示框
    function showAlertBox(type, group_status){
        var html = '';
        if( type == 1 ){
            if ( group_status == 5 ) {
                if(now_status==6){
                    var group_status_str = '拼团未抽中！';
                }else{
                    var group_status_str = '成团已成功！'
                }
            } else {
                if(now_status==6){
                    var group_status_str = '拼团未抽中！';
                }else{
                    var group_status_str = '拼团已成功！'
                }

            }
            html += '<div class="success_pt">';
            html += '	<div class="black_page"></div>';
            html += '	<div class="cont_page">';
            html += '		<div class="div01">';
            html += '			<img src="/weixinpl/mshop/images/collage_activity/alert01.png">';
            html += '			<p class="p01">'+group_status_str+'</p>';
            html += '		</div>';
            html += '		<div class="div02">';
            html += '			<div class="btn01">';
            html += '				<p class="p02" onclick="window.location.href=\'product_list_view.php?customer_id='+customer_id_en+'&op=popularity\'">更多拼团</p>';
            html += '			</div>';
            html += '			<div class="btn02">';
            html += '				<p class="p03" onclick="window.location.href=\'product_list_view.php?customer_id='+customer_id_en+'&op=ordinary\'">我要开团</p>';
            html += '			</div>';
            html += '			<img class="close" src="/weixinpl/mshop/images/collage_activity/close_alert.png">';
            html += '		</div>';
            html += '	</div>';
            html += '</div>';
        } else {
            if ( group_status == 6 ) {
                var group_status_str = '成团失败！';
            } else {
                if(is_win == 2){
                    var group_status_str = '很遗憾，没能中奖！';
                }else{
                    var group_status_str = '你来晚了，拼团超时，失败了！';
                }
            }
            html += '<div class="success_pt">';
            html += '	<div class="black_page"></div>';
            html += '	<div class="cont_page">';
            html += '		<div class="div01">';
            html += '			<img src="/weixinpl/mshop/images/search_none.png">';
            html += '			<p class="p01" style="color:grey;">'+group_status_str+'</p>';
            html += '		</div>';
            html += '		<div class="div02">';
            html += '			<div class="btn01">';
            html += '				<p class="p02" onclick="window.location.href=\'product_list_view.php?customer_id='+customer_id_en+'&op=popularity\'">更多拼团</p>';
            html += '			</div>';
            html += '			<div class="btn02">';
            html += '				<p class="p03" onclick="window.location.href=\'product_list_view.php?customer_id='+customer_id_en+'&op=ordinary\'">我要开团</p>';
            html += '			</div>';
            html += '			<img class="close" src="/weixinpl/mshop/images/collage_activity/close_alert.png">';
            html += '		</div>';
            html += '	</div>';
            html += '</div>';
        }

        $('body').append(html);

        $(".black_page").click(function(){
            $(".success_pt").remove();
        })
        $(".close").click(function(){
            $(".success_pt").remove();
        })
    }

    //获取团推荐产品
    function getProduct(){
        $.ajax({
            url: 'get_recommendation_product.php?customer_id='+customer_id_en,
            dataType: 'json',
            type: 'post',
            data: {
                recommend_set : recommend_set,
                type_ids : type_ids
            },
            success: function(res){
                if( res.length > 0 ){
                    var html = '';
                    for( i in res ){
                        html += '<a class="to-product-detail" href="/weixinpl/mshop/product_detail.php?pid='+res[i]['pid']+'&customer_id='+customer_id_en+'&is_collage_from=1">';
                        html += '	<div class="cnbox">';
                        html += '		<img src="'+res[i]['default_imgurl']+'">';
                        html += '		<p class="cn01">'+res[i]['pname']+'</p>';
                        html += '		<p class="cn02"><?php if(OOF_P != 2) echo OOF_S ?>'+res[i]['price']+'<?php if(OOF_P == 2) echo OOF_S ?></p>';
                        html += '	</div>';
                        html += '</a>';
                    }
                    $('.to-product-detail').remove();
                    $('.cnxh').append(html);
                } else {
                    $('.cn_box').hide();
                }
            }
        })
    }
    //显示属性
    $('.join_group').click(function(){
        $(".shangpin-dialog #div_buyNow").show();

        $(".am-share").addClass("am-modal-active");
        //$("#share").hide();
        $("body").append('<div class="sharebg"></div>');
        $(".sharebg").addClass("sharebg-active");
        $(".shangpin-dialog").show();
        //$(".content").css({"height":w_heigjt-160+"px","overflow":"hidden"});
        $(".sharebg-active").click(function(){
            $(".am-share").removeClass("am-modal-active");
            setTimeout(function(){
                $(".sharebg").removeClass("sharebg-active");
                $(".sharebg").remove();
                $(".shangpin-dialog").hide();
                //$(".content").css({"height":"","overflow":""});
                $("#share").show();
            },300);
        });

    })


    //参团
    function Join_Group(){
        if(!checkUserLogin()) {
            return;
        }

        var call_value = check_pos();//判断是否选择了属性
        if( call_value ){
            return;
        }

        //获取数量
        var rcount = $('#mount_count').val();
        //----获取选择的属性ID
        var sel_pro_str = '';
        $.each($('.pos_div'),function(){
            self = $(this);
            if(self.hasClass('active')){
                //console.log(self);
                var sel_pros_id = self.attr('pos_id');
                sel_pro_str += sel_pros_id+'_';
            }

        });
        sel_pro_str = sel_pro_str.substr(0,sel_pro_str.length-1)
        //console.log(sel_pro_str);
        //----获取选择的属性ID

        $.ajax({
            url: '/weixinpl/mshop/check_collage.php?customer_id='+customer_id_en,
            dataType: 'json',
            type: 'post',
            data: {
                user_id : user_id,
                pid : product_mes['pid'],
                num : rcount,
                activitie_id : product_mes['activitie_id'],
                group_id : product_mes['group_id']
            },
            success: function(res){
                if( res.code == 0 ){
                    var post_object = [];
                    //产品ID
                    var post_data1 = new Array(1);
                    post_data1['key'] = 'pid';
                    post_data1['val'] = product_mes['pid'];
                    //选择的属性
                    var post_data2 = new Array(1);
                    post_data2['key'] = 'sel_pros';
                    post_data2['val'] = sel_pro_str;

                    var post_data3 = new Array(1);
                    post_data3['key'] = 'fromtype';
                    post_data3['val'] = 1;
                    //数量
                    var post_data4 = new Array(1);
                    post_data4['key'] = 'rcount';
                    post_data4['val'] = rcount;
                    //供应商ID
                    var post_data5 = new Array(1);
                    post_data5['key'] = 'supply_id';
                    post_data5['val'] = product_mes['is_supply_id'];
                    //是否符合首次推广奖励
                    var post_data6 = new Array(1);
                    post_data6['key'] = 'check_first_extend';
                    post_data6['val'] = 0;
                    //商城直播的房间id
                    var post_data7 = new Array(1);
                    post_data7['key'] = 'live_room_id';
                    post_data7['val'] = -1;
                    //是否走拼团路线
                    var post_data8 = new Array(1);
                    post_data8['key'] = 'is_collage_product';
                    post_data8['val'] = 1+'_2_0_'+product_mes['price']+'_'+product_mes['activitie_id']+'_'+product_mes['group_id'];	//是否走拼团路线，拼团标识_单独购买或团购_单独购买价格_团购价_活动id_团id
                    post_object.push(post_data1,post_data2,post_data3,post_data4,post_data5,post_data6,post_data7,post_data8);
                    //是否云店产品
                    var post_data9 = new Array(1);
                    post_data9['key'] = 'pro_yundian_id';
                    post_data9['val'] = pro_yundian_id;
                    post_object.push(post_data1,post_data2,post_data3,post_data4,post_data5,post_data6,post_data7,post_data8,post_data9);
                    Turn_Post(post_object);
                } else {
                    showAlertMsg("提示",res.msg,"知道了");
                }
            }
        })

    }

    /*POST提交数据*/
    function Turn_Post(object,strurl){
        //object:需要创建post数据一对数组 [key:val]

        /* 将GET方法改为POST ----start---*/
        var strurl = "/weixinpl/mshop/order_form.php?customer_id="+customer_id_en+"&minate=1";

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
    /*POST提交数据*/

    /*属性图预览*/
    var Preview_data = eval('(' + attr_img_str + ')');

    $('#Preview').Preview({
        data:Preview_data,/*数据*/
        index: $('#Preview').attr('value')/*选择的属性key*/
    });

    function chooseDiv(prid,subid){

        var n_pridsubid=prid+"_"+subid;
        var classname = $("#pro_div_"+n_pridsubid).attr("class");

        var ind = classname.indexOf("active");
        if(classname.indexOf("active")!=-1){
            $("#pro_div_"+n_pridsubid).removeClass("active");
            $("#invalue_"+prid).attr("value","");
            subid = "";
        }else{
            $(".pos_"+prid).removeClass("active");
            $("#pro_div_"+n_pridsubid).addClass("active");
            $("#invalue_"+prid).attr("value",subid);
        }
        var removeid = "";
        //console.log("1:"+selproHash);
        if (selproHash.contains(prid)) {
            removeid = selproHash.items(prid);
        }
        var removeids = removeid.split("_");
        var str = "";
        defaultpids = default_pids.split("_");
        var dlen = defaultpids.length;
        var isadd = false;

        for (var i = 0; i < dlen; i++) {
            var did = defaultpids[i];
            if (!did) {
                continue;
            }
            var isin = false;
            for (var j = 0; j < removeids.length; j++) {
                var rid = removeids[j];
                if (rid == did) {
                    isin = true;
                }
            }
            if (subid > did) {
                if (!isin) {
                    str = str + did + "_";
                }
                if (i == dlen - 1) {
                    if (!isadd) {
                        if (subid != "") {
                            str = str + subid + "_";
                            isadd = true;
                        }
                    }
                }

            } else {
                if (!isadd) {
                    if (subid != "") {
                        str = str + subid + "_";
                    }
                    isadd = true;
                }
                if (!isin) {
                    str = str + did + "_";
                }
            }
        }
        if (str != "") {
            str = str.substring(0, str.length - 1);
            default_pids = str;
        } else {
            if (subid != "") {
                str = subid;
                default_pids = default_pids + str;
            } else {
                default_pids = "";
            }

        }
//------- 批发属性处理 ------//
        //注意！以下代码只在该产品存在批发属性才受影响！

        if(is_wholesale==1){//判断是否拥有批发属性
            //alert(1);
            //$("#wholesale_num").val($("#pro_div_"+n_pridsubid).attr("pos_num"));
            //$("#mount_count").val($("#pro_div_"+n_pridsubid).attr("pos_num"));
            //得到普通属性
            var pt_str = "";
            $('.pt_pros_div').each(function(){
                if($(this).hasClass('active')){
                    pt_str += "_"+$(this).attr('pos_id');//得到一串属性组合的字符串
                }
            })
            //得到批发属性
            var pf_id = "";
            $(".wholesale_div").each(function(){	//得到那个批发属性被选中
                if($(this).hasClass('active')){
                    pf_id = $(this).attr('pos_id');
                }
            })
            if(pf_id!="" && pt_str!=""){
                str = pf_id+pt_str;//再把选中的批发属性拼接到正确位置 1_2_3_4
            }else{
                str = pf_id;
            }

            strarr = str.split("_").sort(sortNumber);
            str = strarr.join("_");


        }
//------- 批发属性处理 ------//
        //console.log("2:"+str);
    }
    function sortNumber(a,b)
    {
        return a - b;
    }
    /*属性选择结束*/

    /*数量加减开始*/
    function addNum(){

        var mount_count = $("#mount_count").val();

        if(mount_count==999){
            return
        }
        var storenum = $("#stock").html();
        storenum = parseInt(storenum,10);

        if(parseInt(mount_count,10)>=storenum){
            return;
        }
        mount_count ++;

        //批发属性
        if(is_wholesale==1){
            var wholesale_num = $("#wholesale_num").val();

            if( mount_count > wholesale_num ){
                $(".minus").css({'background-color':'#fff'});
            }
        }

        $("#mount_count").val(mount_count);
    }

    function minusNum(){
        var mount_count = $("#mount_count").val();
        if(mount_count==1){
            return
        }
        if(mount_count>1){
            mount_count --;
            //批发属性
            if(is_wholesale==1){
                var wholesale_num = $("#wholesale_num").val();
                if( mount_count < wholesale_num ){
                    $(".minus").css({'background-color':'#ccc'});
                    return;
                }
            }
            $("#mount_count").val(mount_count);
        }
    }
    function modify() {
        var a = parseInt($("#mount_count").val(), 10);
        if ("" == $("#mount_count").val()) {
            $("#mount_count").val(1);
            return
        }
        if (!isNaN(a)) {
            if (1 > a || a > 999) {
                $("#mount_count").val(1);
                return
            } else {
                $("#mount_count").val(a);
                return
            }
        } else {
            $("#mount_count").val(1);
        }
    }
    /*数量加减结束*/

    /*购物数量固定格式开始*/
    function clearNoNum(obj)
    {
        //先把非数字的都替换掉，除了数字
        obj.value = obj.value.replace(/[^\d]/g,"");
        if(obj.value>999){
            obj.value = 999;
        }
        if( is_collage_product && groupBuyType == 2 ){
            //拼团不做限购限制
        } else if( obj.value >= limit_num && islimit==1 ){
            showXiangouMsg("当前商品限购数量为"+limit_num,"知道了");
            $(obj).val(limit_num);
            return
        }
    }
    /*购物数量固定格式结束*/


    /*有属性判断有没有选择属性开始*/
    function check_pos(){
        var call_value	= false;
        var pos 		= $(".pos_div").parent(".small_pro_div");//查有多少个属性父级
        for( i = 0; i < pos.length ; i++ ){
            var active = pos.eq(i).find(".active").length;
            if( active < 1 ){
                var pos_name = pos.eq(i).attr("pos_name");
                //alert('请选择'+pos_name);
                alertAutoClose("请选择"+pos_name,"知道了");
                call_value = true;
                return call_value;
            }
        }
        return call_value;
    }
    /*有属性判断有没有选择属性结束*/

    /* 批发属性选择 */
    $(".wholesale_div").on('click',function(){
        if($(".wholesale_div").hasClass('no-storenum')){ //属性库存置灰不可点击,不改变购买数量
            return;
        }
        var wholesale_num = $(this).attr('pos_num');
        $("#wholesale_num").val(wholesale_num);
        $("#mount_count").val(Math.round(wholesale_num));

        $(".minus").css({'background-color':'#ccc'});

    })

</script>
<?php require('../../../weixinpl/common/share.php'); ?>
<!--引入侧边栏 start-->
<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/common/utility_setting_function.php');
$fun = 'group_goods_detail';
$nav_is_publish = check_nav_is_publish($fun,$customer_id);
$is_publish = check_is_publish(2,$fun,$customer_id);
include_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/mshop/float.php');
//		/*判断是否显示底部菜单 start*/
//		if($is_publish){
//			require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/mshop/bottom_label.php');
//		}
//		/*判断是否显示底部菜单 end*/
?>
<!--引入侧边栏 end-->
<script src="/weixinpl/mshop/js/CheckUserLogin.js"></script><!--检验用户是否已登录-->
</body>
</html>
