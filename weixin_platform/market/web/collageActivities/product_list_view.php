<?php
header("Content-type: text/html; charset=utf-8"); //svn
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]

$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
require('../../../weixinpl/proxy_info.php');
require('../../../weixinpl/common/common_from.php');
require('../../../weixinpl/function_model/collageActivities.php');
require('../../../weixinpl/mshop/select_skin.php');

$collageActivities = new collageActivities($customer_id);

if( !empty( $_GET['op'] ) ){
	$op = $configutil->splash_new($_GET['op']);
}else{
	echo "<script>alert('非法进入！');</script>";
	exit();
}
if( $op == 'ordinary' || $op == 'ordinary3' || $op == 'ordinary2' ){
	$filed = "cp.name,cp.orgin_price,cp.default_imgurl,pt.price,pt.success_num,at.group_size,at.start_time,at.end_time,at.type,at.coefficient,ae.type_name,pt.id,pt.pid,pt.open_day,pt.sort,CGPT.total_open,CGPT.virtual_open";
	$condition=array(
		'at.customer_id' => $customer_id,
        'ae.customer_id' => $customer_id,
		'at.end_time' => date('Y-m-d H:i:s',time()),
		'ae.isvalid' => true,
		'ae.isshow' => '1',
		'at.isvalid' => true,
		'pt.isvalid' => true,
		'cp.isvalid' => true,
		'CGPT.isvalid' => true,
        'cp.isout' => '0',
		'at.status' => '2',
		'pt.status' => '1',
		'pt.stock' => '0',
		'LIMIT' => ' LIMIT 0,15',
		'ORDER' => ' ORDER BY pt.sort asc,pt.id desc,ae.sort asc '
	);
	$list = $collageActivities->get_activities_product($condition,$filed);
    //$type_data = $collageActivities->getExplain($customer_id);
    $type_data = $collageActivities->getTypes2($customer_id);

    $query = 'SELECT count(id) as tcount FROM collage_activities_explain_t where isvalid=true and isshow=1 and customer_id='.$customer_id;
    $result = _mysql_query($query) or die('getTypes query failed: ' . mysql_error());
    while( $row = mysql_fetch_object($result) ){
		$tcount = $row->tcount;
	}

}elseif( $op == 'popularity' ){
	$filed = "wcp.name,wcp.orgin_price,wcp.default_imgurl,cgpt.price,cgpt.success_num,cat.group_size,cat.end_time,cgot.createtime,cgot.endtime,cgot.success_num as cgot_success_num,cgot.join_num,cat.type,cgot.id,cgot.pid,cgpt.total_open,cgpt.virtual_open,ae.type_name";
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
		'LIMIT' => '0,15'
	);
	$list = $collageActivities->get_front_group($condition,$filed);
}
$type_list=$collageActivities->get_front_group_type($customer_id);
	// var_dump($list);exit;
//var_dump($type_list);
define("InviteUrl",Protocol.$http_host."/weixinpl/common_shop/jiushop/forward.php?customer_id=".$customer_id_en."&redirect_url=".urlencode(Protocol.$http_host."/market/web/collageActivities/product_list_view.php?op=".$op)."&exp_user_id=".$user_id);
//检查用户从哪里进  0:网页 1:微信 2:APP 3:支付宝
$CF = new check_from();
$from_type = $CF->check_where($customer_id);
?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php if( $op == 'ordinary' || $op == 'ordinary2' || $op == 'ordinary3' ){echo '商品专区';}elseif( $op == 'popularity' ){echo '人气拼团';} ?></title>
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
		<link rel="stylesheet" href="/weixinpl/mshop/css/style.css" />
		<link rel="stylesheet" href="/weixinpl/mshop/css/product_list.css" />
		<link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/css_<?php echo $skin ?>.css" />
		<link rel="stylesheet" href="/weixinpl/mshop/css/goods/collage_product_list.css" />
		<script type="text/javascript" src="/weixinpl/mshop/js/jquery-2.1.3.min.js"></script>
	</head>
	<style>

		.tis{
			width: 100%;
			color: #999;
			text-align: center;
			margin-top: 20px;
			font-size: 16px;
			display:none;
		}
		.cellimg {
			<?php if( $op == 'ordinary' ){?>
			height: 105px;
			<?php }else{?>
			width: 100%;
			<?php }?>
		}
		.search{position: fixed;left: 0;top: 0;z-index:999;padding: 10px 2.5%;margin: 0;background-color: #f8f8f8;}
		.icosousuo{position: absolute;top: 17px;left: 50px;width: 18px;height: 18px;}
		.timeImg1{width:20%;vertical-align:middle;margin-right:12%;}

		.type-box{position:fixed;top:52px;left:0;z-index:999;width:100%;background-color:#fff;}
		.type-box .type-left{position:relative;margin-right:60px;width:auto;padding:0 8px;overflow-x:scroll;-webkit-overflow-scrolling : touch;}
		.type-box .type {min-width:320px;margin-bottom:0;text-align:left;overflow:hidden;}
		.type-box .type-width .typeCell{font-size:1.3rem;color:#676869;border-right:0;text-align:center;vertical-align:top;padding:0 .6rem;}
		.type-box .select{width:60px;position:absolute;top:0;right:0;border-left:solid 1px #c3c3c3;font-size:1.3rem;color:#676869;margin-top:8px;text-align:center;}
		.type-box .select img{height:10px;margin-left:2px;}
		.Select-content .skin-color{position:relative;}
		.Select-content .skin-color:after{content:'';background-image:url(../<?php echo $images_skin ?>/goods_image/2016042705.png);background-repeat:no-repeat;background-size:15px;display:block;width:15px;height:15px;position:absolute;bottom:0;right:0;}
		.Loading{text-align:center;display:none;}
		.Loading img{width:2.4rem;}
		.Loading p{font-size:1.2rem;color:#666;}
	</style>
	<body>

		<div class="search">
			<img class="icosousuo" src="img/ico_sousuo.png">
			<input id="icosousuoipt" type="text" placeholder="输入关键字搜索" oninput="oinputfun();" />
			<button class="skin-bg" type="button" onclick="search()">搜索</button>
			<input type="hidden" value="0" id="now_type">
			<input type="hidden" value=""  id="search_type_str">
		</div>
		<?php
			if( $op == 'ordinary' ){//商品专区1
		?>
		<div class="type-box">
        <?php if($tcount>1){ ?>
			<div class="type-left">
				<div class="type type-width" >
					<div class="typeCell" data-type="0"><span class="type_choose skin-bd skin-color">全部</span></div>
                    <?php
                        foreach( $type_data as $key => $val ){
                    ?>
					<div class="typeCell" data-type="<?php echo $val['type'];?>"><span><?php echo $val['type_name'];?></span></div>
                    <?php
                        }
                    ?>
				</div>
			</div>
        <?php }?>
			<div class="select" onclick="showSelect();">
                <span>筛选<img  src="/weixinpl/mshop/images/list_image/tagbg_item5.png"></span>
            </div>
		</div>

		<div style="height:98px"></div>
		<div class="content">
			<?php
				foreach($list['data'] as $key => $val){
			?>
			<div class="cellBox">
				<div class="cell" onclick="toOpenGroup(<?php echo $val['pid'];?>)">
					<div class="pro-img">
						<?php
							if($val['default_imgurl'] == ''){
								//echo '<script>alert('.$val['pid'].');</script>';
								$img_query='SELECT imgurl  from weixin_commonshop_product_imgs where product_id='.$val['pid'].' and customer_id='.$customer_id.' and isvalid = 1 order by id desc ';
								$img_result = _mysql_query($img_query);
								$row = mysql_fetch_assoc($img_result);
								$val['default_imgurl'] = $row['imgurl'];
							}
						?>
						<img class="cellimg" src="<?php echo $val['default_imgurl']; ?>" />
						<span class="cell_icon">
						<?php
                            echo $val['type_name'];
						?>
						</span>
					</div>
					<div class="cellRight">
						<p class="line1"><?php echo $val['name']; ?></p>
						<p class="line2">
                        <?php if($val['type']==5){?>
                    	  <?php echo $val['type_name']?> X<?php echo $val['coefficient']; ?>
                        <?php }?>
                        </p>
                        <?php
                        	$this_length=($val['total_open']/33)*100;
							if($val['total_open'] >= 33){
								$this_length=100;
							}
                        ?>
						<p class="line3">
							<span class="line-left">
								<span class="num" style="width:<?php echo $this_length;?>%;"></span>
							</span>
                            <?php
                                $show_open = $val['total_open'] + $val['virtual_open'];
                                if($show_open>99){
                                    $show_open = 99;
                                }
                            ?>
							<span class="line-right">已开团<?php echo $show_open;?></span>
						</p>
						<div class="line4">
							<div class="line4-left">
								<p class="top">
								<?php
									$group_num = $val['group_size'];
									if($val['success_num'] != -1){
										$group_num = $val['success_num'];
									}
								?>
									<span class="span1"><?php if(OOF_P != 2) echo OOF_S ?><?php echo $val['price']; ?><?php if(OOF_P == 2) echo OOF_S ?></span>
									/<?php echo $group_num; ?>人团
								</p>
								<?php
									$surplus_time = strtotime($val['end_time']) - time() + 1;
									$nd = $surplus_time/(60*60*24);
									$nh = $surplus_time/(60*60)%24;
									$nm = $surplus_time/60 % 60;
									$ns = $surplus_time % 60;
									if( $nh < 10 ){
										$nh = '0'.$nh;
									}
									if( $nm < 10 ){
										$nm = '0'.$nm;
									}
									if( $ns < 10 ){
										$ns = '0'.$ns;
									}
									$start_time = strtotime($val['start_time']);
									$end_time   = strtotime($val['end_time']);
									$now_time   = time();


									if((($end_time - $now_time) / 86400)>=3 || ($now_time < $start_time)){
										$start_day=date('Y/m/d',$start_time);
										$end_day  =date('Y/m/d',$end_time);
										echo '<p class="bottom">'.$start_day.'至'.$end_day.'</p>';
									}else{
										if( $nd > 0 ){
											echo '<p class="bottom countdown"  data-start='.strtotime($val['start_time']).' data-end='.strtotime($val['end_time']).' data-id='.$val['id'].'>倒计时：<font id="time_'.$val['id'].'">'.(int)$nd.'天'.$nh.'：'.$nm.'：'.$ns.'</font></p>';
										}else{
											echo '<p class="bottom countdown"  data-start='.strtotime($val['start_time']).' data-end='.strtotime($val['end_time']).' data-id='.$val['id'].'>倒计时：<font id="time_'.$val['id'].'">'.$nh.'：'.$nm.'：'.$ns.'</font></p>';
										}
									}
								?>
							</div>
							<div class="line4-right">
							<?PHP if($now_time > $start_time){
								echo '<button class="btn1"  >拼团</button>';
							}else{
								echo '<button class="btn1" style="background-color:#848484;color:#ffffff" >拼团</button>';
							}
						?>
							</div>
						</div>
					</div>
				</div>
			</div>
				<?php
				}
			?><div class="tis" <?php if( count( $list['data'] ) < 15 ){echo "style='display:block;'";} ?>>---已无更多团活动---</div>
			</div>
			<?php
			}elseif( $op == 'ordinary3' ){//商品专区3
			?>
			<div class="type-box">
            <?php if($tcount>1){ ?>
				<div class="type-left">
					<div class="type type-width" >
						<div class="typeCell" data-type="0"><span class="type_choose skin-bd skin-color">全部</span></div>
						<?php
                        foreach( $type_data as $key => $val ){
                    ?>
					<div class="typeCell" data-type="<?php echo $val['type'];?>"><span><?php echo $val['type_name'];?></span></div>
                    <?php
                        }
                    ?>
					</div>
				</div>
            <?php } ?>
				<div class="select" onclick="showSelect();">
	                <span>筛选<img  src="/weixinpl/mshop/images/list_image/tagbg_item5.png"></span>
	            </div>
			</div>

			<div style="height:98px"></div>
			<div class="content">
			<?php
				foreach($list['data'] as $key => $val){
			?>
				<div class="bottom-all" onclick="toOpenGroup(<?php echo $val['pid'];?>)">
					<div class="product">
						<div class="pro-img">
							<?php
								if($val['default_imgurl'] == ''){
									$img_query='SELECT imgurl  from weixin_commonshop_product_imgs where product_id='.$val['pid'].' and customer_id='.$customer_id.' and isvalid = 1 order by id desc ';
									$img_result = _mysql_query($img_query);
									$row = mysql_fetch_assoc($img_result);
									$val['default_imgurl'] = $row['imgurl'];
								}
							?>
							<img class="cellimg" src="<?php echo $val['default_imgurl']; ?>"/>
							<span class="cell_icon">
							<?php
                                echo $val['type_name'];
                            ?>
							</span>

							<?php
								$this_length=($val['total_open']/33)*100;
								if($val['total_open'] >= 33){
									$this_length=100;
								}

                                $show_open = $val['total_open'] + $val['virtual_open'];
                                if($show_open>99){
                                    $show_open = 99;
                                }

								if($show_open >= 0 ){
									echo '<div class="product-num"><span style="left:'.$this_length.'%;">已开团'.$show_open.'</span>';
									echo '<p class="state" style="width:'.$this_length.'%;"></p></div>';
								}
								$start_time = strtotime($val['start_time']);
								$end_time   = strtotime($val['end_time']);
								$now_time   = time();
							?>

							<!--  <div class="product-time countdown" data-start=<?php echo strtotime($val['start_time']); ?> data-end=<?php echo strtotime($val['end_time']); ?> data-id=<?php echo $val['id']; ?> id="time_<?php echo $val['id']; ?>">
							<?php
								$surplus_time = strtotime($val['end_time']) - time() + 1;
								$nd = $surplus_time/(60*60*24);
								$nh = $surplus_time/(60*60) % 24;
								$nm = $surplus_time/60 % 60;
								$ns = $surplus_time % 60;
							?>
								距结束：
								<span class="wDay"><?php echo (int)$nd; ?><i>天</i></span><span class="wHour"><?php echo (int)$nh; ?><i>时</i></span><span class="wSec"><?php echo (int)$nm; ?><i>分</i></span><span class="owMin"><?php echo (int)$ns; ?><i>秒</i></span>
								<ul>
									<li><span class="wDay"><?php echo (int)$nd; ?></span><i>天</i></li>
									<li><span class="wHour"><?php echo (int)$nh; ?></span><i>时</i></li>
									<li><span class="wSec"><?php echo (int)$nm; ?></span><i>分</i></li>
									<li><span class="owMin"><?php echo (int)$ns; ?></span><i>秒</i></li>
								</ul>
							</div>-->
						</div>

					</div>
					<div class="product-detail">
						<div class="detailOne">
							<!--<span>品牌</span>-->
							<p><?php echo $val['name']; ?></p>
						</div>
					</div>
					<div class="product-money">
						<span class="mon-one"><i><?php if(OOF_P != 2) echo OOF_S ?></i><?php echo $val['price']; ?><i><?php if(OOF_P == 2) echo OOF_S ?></i></span><span class="mon-two"><i><?php if(OOF_P != 2) echo OOF_S ?></i><?php echo $val['orgin_price']; ?><i><?php if(OOF_P == 2) echo OOF_S ?></i></span>
						<div class="tuan">
						<?php
							$group_num = $val['group_size'];
							if($val['success_num'] != -1){
							$group_num = $val['success_num'];
								}
						?>
							<span class="tuan-one"><?php echo $group_num; ?>人团</span>
							<?php if( $now_time > $start_time ){
								echo '<span class="tuan-two" >立即开团</span>';
							}else{
								echo '<span class="tuan-two" style="background-color:#848484;color:#ffffff">立即开团</span>';
							}?>

						</div>
					</div>
				</div>
			<?php
				}
			?><div class="tis" <?php if( count( $list['data'] ) < 15 ){echo "style='display:block;'";} ?>>---已无更多团活动---</div>
			</div>
			<?php
			}elseif( $op == 'ordinary2' ){	// 商品专区2
			?>
			<div class="type-box">
            <?php if($tcount>1){ ?>
				<div class="type-left">
					<div class="type type-width" >
						<div class="typeCell" data-type="0"><span class="type_choose skin-bd skin-color">全部</span></div>
						<?php
                        foreach( $type_data as $key => $val ){
                    ?>
					<div class="typeCell" data-type="<?php echo $val['type'];?>"><span><?php echo $val['type_name'];?></span></div>
                    <?php
                        }
                    ?>
					</div>
				</div>
            <?php } ?>
				<div class="select" onclick="showSelect();">
	                <span>筛选<img  src="/weixinpl/mshop/images/list_image/tagbg_item5.png"></span>
	            </div>
			</div>

			<div style="height:98px"></div>
			<div class="content" style="font-size:0;padding:0 8px;">
			<?php
				foreach($list['data'] as $key => $val){
			?>
				<div class="list-box">
					<a  onclick="toOpenGroup(<?php echo $val['pid'];?>)">
						<div class="img-box">
							<?php
								if($val['default_imgurl'] == ''){
									$img_query='SELECT imgurl  from weixin_commonshop_product_imgs where product_id='.$val['pid'].' and customer_id='.$customer_id.' and isvalid = 1 order by id desc ';
									$img_result = _mysql_query($img_query);
									$row = mysql_fetch_assoc($img_result);
									$val['default_imgurl'] = $row['imgurl'];
								}
							?>
							<img src="<?php echo $val['default_imgurl']; ?>">
							<span class="cell_icon">
							<?php
                                echo $val['type_name'];

                            ?>
							</span>

							<?PHP
								$this_length=($val['total_open']/33)*100;
								if($val['total_open'] >= 33){
									$this_length=100;
								}

                                $show_open = $val['total_open'] + $val['virtual_open'];
                                if($show_open>99){
                                    $show_open = 99;
                                }

								if($show_open >= 0){
									echo '<div class="product-num"><span style="left:'.$this_length.'%;">已开团'.$show_open.'</span>';
									echo '<p class="state" style="width:'.$this_length.'%;"></p></div>';
								}

							?>

						</div>
						<div class="txt-box">
						<?php
							$group_num = $val['group_size'];
							if($val['success_num'] != -1){
								$group_num = $val['success_num'];
							}
						?>
							<div class="title" style="text-overflow: ellipsis;-webkit-box-orient: vertical;"><?php echo $val['name']; ?></div>
							<div class="price"><?php if(OOF_P != 2) echo OOF_S ?><?php echo $val['price']; ?><?php if(OOF_P == 2) echo OOF_S ?> <span>/<?php echo $group_num; ?>人团</span></div>
							<div class="time" >
							<?php
									$surplus_time = strtotime($val['end_time']) - time() + 1;
									$nd = $surplus_time/(60*60*24);
									$nh = $surplus_time/(60*60)%24;
									$nm = $surplus_time/60 % 60;
									$ns = $surplus_time % 60;
									if( $nh < 10 ){
										$nh = '0'.$nh;
									}
									if( $nm < 10 ){
										$nm = '0'.$nm;
									}
									if( $ns < 10 ){
										$ns = '0'.$ns;
									}
									$start_time = strtotime($val['start_time']);
									$end_time   = strtotime($val['end_time']);
									$now_time   = time();
									//echo "<script>alert(".$now_time.");</script>";

									if((($end_time - $now_time) / 86400)>=3 || ($now_time < $start_time)){
										$start_day=date('Y/m/d',$start_time);
										$end_day  =date('Y/m/d',$end_time);
										echo '<p class="bottom" style="color:#a1a1a1;">'.$start_day.'至'.$end_day.'</p>';
									}else{
										if( $nd > 0 ){
											echo '<p class="bottom countdown" style="color:#a1a1a1;" data-start='.strtotime($val['start_time']).' data-end='.strtotime($val['end_time']).' data-id='.$val['id'].'>倒计时：<font id="time_'.$val['id'].'">'.(int)$nd.'天'.$nh.'：'.$nm.'：'.$ns.'</font></p>';
										}else{
											echo '<p class="bottom countdown" style="color:#a1a1a1;" data-start='.strtotime($val['start_time']).' data-end='.strtotime($val['end_time']).' data-id='.$val['id'].'>倒计时：<font id="time_'.$val['id'].'">'.$nh.'：'.$nm.'：'.$ns.'</font></p>';
										}
									}
								?>
							</div>
							<div class="button" >
							<?PHP if($now_time > $start_time){
								echo '<button >立即拼团</button>';
							}else{
								echo '<button style="background-color:#848484;color:#ffffff">立即拼团</button>';
							}?>
							</div>
							<div class="sign">
                            <?php if($val['type']==5){?>
                            <?php echo $val['type_name']?> X<?php echo $val['coefficient']; ?>
                            <?php }?>
                            </div>
						</div>
					</a>
				</div>
			<?php
				}
			?><div class="tis" <?php if( count( $list['data'] ) < 15 ){echo "style='display:block;'";} ?>>---已无更多团活动---</div>
			</div>
			<?php
			}elseif( $op == 'popularity' ){//人气拼团
			?>
			<div class="type-box">
				<div class="type type-renqi">
					<div class="typeCell" data-type="0"><span class="type_choose skin-bd skin-color">全部</span></div>
					<div class="typeCell" data-type="1"><span>最新</span></div>
					<div class="typeCell" data-type="2"><span>人气</span></div>
					<div class="typeCell" data-type="3"><span>将结束</span></div>
				</div>
			</div>

			<div style="height:98px"></div>
			<div class="content">
			<?php
				foreach($list['data'] as $key => $val){
			?>
				<div class="bottom-all" onclick="toJoinGroup(<?php echo $val['id'];?>)">
					<div class="product">
						<div class="pro-img">
						<?php
							if($val['default_imgurl'] == null ){
								$img_query='SELECT imgurl  from weixin_commonshop_product_imgs where product_id='.$val['pid'].' and customer_id='.$customer_id.' and isvalid = 1 order by id desc ';
								$img_result = _mysql_query($img_query);
								$row = mysql_fetch_assoc($img_result);
								$val['default_imgurl'] = $row['imgurl'];
							}
						?>
							<img class="cellimg" src="<?php echo $val['default_imgurl']; ?>"/>


							<?php

                                if($val['endtime']!='0000-00-00 00:00:00'){
                                    $endtime_str = strtotime($val['endtime']);
                                }else{
                                    $endtime_str = strtotime($val['end_time']);
                                }
							?>
							<div class="product-time countdown" data-start=<?php echo strtotime($val['createtime']); ?> data-end=<?php echo $endtime_str; ?> data-id=<?php echo $val['id']; ?> id="time_<?php echo $val['id']; ?>">
							<?php

                                if($val['endtime']!='0000-00-00 00:00:00'){
                                    $surplus_time = strtotime($val['endtime']) - time() + 1;
                                }else{
                                    $surplus_time = strtotime($val['end_time']) - time() + 1;
                                }
								$nd = $surplus_time/(60*60*24);
								$nh = $surplus_time/(60*60) % 24;
								$nm = $surplus_time/60 % 60;
								$ns = $surplus_time % 60;
							?>
								距结束:
								<ul>
									<li><span class="wDay"><?php echo (int)$nd; ?></span><i>天</i></li>
									<li><span class="wHour"><?php echo (int)$nh; ?></span><i>时</i></li>
									<li><span class="wSec"><?php echo (int)$nm; ?></span><i>分</i></li>
									<li><span class="owMin"><?php echo (int)$ns; ?></span><i>秒</i></li>
								</ul>
							</div>
						</div>

					</div>
					<div class="product-detail">
						<div class="detailOne">
							<!--<span>品牌</span>-->
							<p><?php echo $val['name']; ?></p>
						</div>
					</div>
					<div class="product-money">
						<?php
							$group_num = $val['group_size'];
							if($val['success_num'] != -1){
								$group_num = $val['success_num'];
							}
						?>
						<span class="mon-one"><i><?php if(OOF_P != 2) echo OOF_S ?></i><?php echo $val['price']; ?><i><?php if(OOF_P == 2) echo OOF_S ?></i><i style="font-size:1rem;margin-right:.4rem;">/<?php echo $group_num; ?>人团</i></span><span class="mon-three">差<?php echo $val['cgot_success_num']-$val['join_num']; ?>人</span>
						<div class="tuan" style="height:2rem;line-height:2rem;font-style:italic;color:#ec3356;font-size:1.4rem;padding-right:3px;">
							<?php
                                echo $val['type_name'];
                            ?>
						</div>
					</div>
				</div>
			<?php
				}
			?><div class="tis" <?php if( count( $list['data'] ) < 15 ){echo "style='display:block;'";} ?>>---已无更多团活动---</div>
			</div>
			<?php
			}
			?>
		<div class="Loading">
			<img src="/weixinpl/mshop/images/loading.gif">
			<p>---数据加载中---</p>
		</div>
		<!-- 筛选框 -->
		<div class="Select-box">
			<div class="Select-back">返回</div>
			<div class="Select-header">分类</div>
			<div class="Select-content">
				<ul class="Select-list">
				<?php if($type_list['first_type'] && $type_list['code'] != 40006){
						foreach ($type_list['first_type'] as $key => $one) {
							if($one['son'] != ''){
								if( $key == 0 ){
									echo '<li class="Select-show" >';
									echo '<p>'.$one['name'].'<span><img src="/weixinpl/mshop/images/dyright.png"></span></p>';
									echo '<ol style="display:block">';
									echo '<li onclick="search_type('.$one['id'].',\''.$one['son_str'].'\')" >全部</li>';
								}else{
									echo '<li >';
									echo '<p>'.$one['name'].'<span><img src="/weixinpl/mshop/images/dyright.png"></span></p>';
									echo '<ol style="display:none">';
									echo '<li onclick="search_type('.$one['id'].',\''.$one['son_str'].'\')" >全部</li>';
								}
				?>
									<?php foreach($one['son'] as $key_son =>$son_one){
										echo '<li onclick="search_type('.$son_one['son_id'].',1)">'.$son_one['son_name'].'</li>';
									}?>
									</ol>
								</li>
					  <?php }else{?>
								<li>
									<p onclick="search_type(<?php echo $one['id'];?>,1)" ><?php echo $one['name'];?><span><img src="/weixinpl/mshop/images/dyright.png"></span></p>
								</li>
				<?php
							}
						}
				}
				?>
				</ul>
			</div>
		</div>
		<div class="Select-bg"></div>


	</body>
	<script type="text/javascript" src="/mp/currency_config/config.currency.default.js"></script><!--货币默认设置 -->
	<script type="text/javascript" src="/mp/currency_config/config.currency.js"></script>  <!--货币用户设置-->
	<script src="/weixinpl/mshop/js/r_global_brain.js" type="text/javascript"></script>
	<script src="/weixinpl/mshop/js/r_pinterest.js" type="text/javascript"></script>
	<script src="/weixinpl/mshop/js/goods/collage_product_list.js" type="text/javascript"></script>
	<script>
		var customer_id    = '<?php echo $customer_id; ?>';
		var customer_id_en = '<?php echo $customer_id_en; ?>';
		var runtimes 	   = 0;
		var search_keyword = '';//关键字
		var type           = '';//类型
		var downFlag	   = false;	// 是否加载全部
		var	pageNum 	   = 0;	// 起始页码
		var	isLock 	       = false;// 是否继续加载
		var pagesize       = 15;//每页数据数量
		var op             = '<?php echo $op; ?>';
		var timeInterval   = new Array();//定时器对象数组
		var share_url = '<?php echo InviteUrl;?>';
		var title = '<?php echo $op=='popularity'?'人气拼团':'商品专区';?>';
		var desc = '<?php echo $op=='popularity'?'人气拼团':'商品专区';?>';
		var imgUrl = '<?php echo Protocol.$http_host."/weixinpl/common/images_V6.0/contenticon/is_head.png";?>';
		//var imgUrl = '/weixinpl/common/images_V6.0/contenticon/is_head.png';
		var share_type = 4;
		var type_width = 0;
        var OOF_P = '<?php echo OOF_P;?>';
		var OOF_S = '<?php echo OOF_S;?>';

		$('.type-width .typeCell').each(function(){
			var width = $(this).outerWidth();
			type_width += width;
		});
		$('.type-box .type-width').css('width',type_width+5);

		// $('.bottom-all .pro-img').css('height',$('.bottom-all .pro-img').width()/2);
		$('.list-box .img-box').css('height',$('.list-box .img-box').width()*337/339);

		$('.product-num').each(function(){
			var ps_left     = parseFloat($(this).find('span').css('left')),
				state_width = $(this).find('p').outerWidth(),
				span_width  = $(this).find('span').outerWidth(),
				max_left    = state_width - span_width,
				all_width   = $(this).outerWidth();

			if(ps_left <= span_width/2){console.log('yes')
				$(this).find('span').css({'left':0,'margin-left':0});
				$(this).find('p').css('width',span_width/2);
			}else if(ps_left >= max_left){
				$(this).find('span').css({'left':state_width,'margin-left':-span_width/2});
			}
			if(ps_left + span_width >= all_width){
				$(this).find('span').css({'left':'auto','right':0,'margin-left':0});
			}
		});


		var win_width = $(window).width()*0.9;
		$('.Select-box').animate({'right':-win_width});
		function oinputfun() {
			if($("#icosousuoipt").val()==""){
		 		$(".icosousuo").show();
		 	}else{
		 		$(".icosousuo").hide();
		 	}
		};

		//筛选框事件
		function showSelect(){
			$('.Select-box,.Select-bg').show();
			$('.Select-box').animate({'right':'0'});
		}
		$('.Select-list ol li').on('click',function(){
			$('.Select-list ol li').removeClass('skin-color skin-bd');
			$(this).addClass('skin-color skin-bd');
			var txt = $(this).text();

		})

		$('.Select-list li p').on('click',function(){
			$(this).parent('li').toggleClass('Select-show');
			if($(this).parent('li').hasClass('Select-show')){
				$(this).next('ol').stop().slideDown();
			}else{
				$(this).next('ol').stop().slideUp();
			}
		})

		$('.Select-back,.Select-bg').on('click',function(){
			$('.Select-box').animate({'right':-win_width},function(){
				$('.Select-box,.Select-bg').hide();
			});

		})

	</script>
	<?php require('../../../weixinpl/common/share.php');
	/*判断是否显示底部菜单 start*/
	require_once('../../../weixinpl/common/utility_setting_function.php');
	if($op == "popularity"){
		$fun = "popularity_group";
	}else if($op == "ordinary"){
		$fun = "group_list_area";
	}else if($op == "ordinary2"){
		$fun = "group_tile_area";
	}else if($op == "ordinary3"){
		$fun = "group_bigpic_area";
	}

	$is_publish = check_is_publish(2,$fun,$customer_id);
	if($is_publish){
		require_once('../../../weixinpl/mshop/bottom_label.php');
	}
	/*判断是否显示底部菜单 end*/
	?>
	<!--引入侧边栏 start-->
	<?php
	$nav_is_publish = check_nav_is_publish($fun,$customer_id);
	include_once('../../../weixinpl/mshop/float.php');
	?>
	<!--引入侧边栏 end-->
</html>
