<?php
header("Content-type: text/html; charset=utf-8");
require('../../../weixinpl/config.php');
require('../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('order_Form Could not select database');
//头文件
require('../../../weixinpl/common/common_from.php');
require('../../../weixinpl/mshop/select_skin.php');

$name = '';
$weixin_name = '';
$query = "SELECT name,weixin_name FROM weixin_users WHERE customer_id=".$customer_id." AND id=".$user_id." AND isvalid=true";
$result = _mysql_query($query) or die('Query failed:'.mysql_error());
while( $row = mysql_fetch_assoc($result) ){
	$name = $row['name'];
	$weixin_name = $row['weixin_name'];
}
 
$nick_name = empty($weixin_name)? $name:$weixin_name;	//昵称
$nowdate = date('Y-m-d',time());
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
        <meta content="telephone=no" name="format-detection">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
	    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
		<link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/order_css/global.css" /> 
		<link type="text/css" rel="stylesheet" href="/weixinpl/mshop/css/css_<?php echo $skin ?>.css" /> 
		<link href="/weixinpl/mshop/css/collage_activity/reset.css" type="text/css" rel="stylesheet">
		<link href="/weixinpl/mshop/css/collage_activity/style.css" type="text/css" rel="stylesheet">
	</head>
	<body style="background:#f7f7f7">
		<div class="box01">
			<div class="box bbottom">
				<p class="p01">昵&nbsp; &nbsp;&nbsp;称</p>
				<input class="none" value="<?php echo $nick_name;?>" readonly>
				<label class="labelbox" for="chx" onclick="niming(this)"><span class="cl333">匿名</span></label>
				<input class="chx" type="checkbox" id="chx" >
				<input type="hidden" name="is_anonymous" value="0" >
			</div>
			<div class="box">
				<p class="p01">时&nbsp; &nbsp;&nbsp;间</p>
				<input class="none" value="<?php echo $nowdate;?>" readonly>
			</div>			
		</div>
		<div class="box01">
			<div class="mb">
				<div class="box bt">
					<p class="p01 vt">内&nbsp; &nbsp;&nbsp;容</p>
					<textarea class="txt vt" resize="none" placeholder="请输入5到22个字" maxlength="25" name="content"></textarea>
				</div>
				<div class="box bt">
					<p class="p01">姓&nbsp; &nbsp;&nbsp;名</p>
					<input placeholder="输入名字" name="name">
				</div>		
				<div class="box bt">
					<p class="p01">手机号码</p>
					<input placeholder="输入手机号码" name="phone">
				</div>	
			</div>
		</div>		
		<div class="fix confirm_btn">
			提交
		</div>
		<input type="hidden" name="user_id" value="<?php echo $user_id;?>">
		<script type="text/javascript" src="/weixinpl/mshop/js/global.js"></script>
		<script src="/weixinpl/mshop/js/jquery-1.12.1.min.js"></script>
		<!--悬浮按钮-->
		<?php  include_once('../../../weixinpl/mshop/float.php');?>
		<!--悬浮按钮-->
		<script>
			var customer_id_en = '<?php echo $customer_id_en;?>',
				user_id = '<?php echo $user_id;?>',
				isConfirm = false;
		
			function niming(obj){
				var chx=$(".chx");
				var $this=$(obj);
				var $is_anonymous=$('input[name=is_anonymous]');
				if(chx.is(":checked")){
					$this.css({
						"background":"url(/weixinpl/mshop/images/collage_activity/active01.png) no-repeat center center",
						"backgroundSize":"cover"					
					})
					$is_anonymous.val(0);
				}else{
					$this.css({
						"background":"url(/weixinpl/mshop/images/collage_activity/active.png)",
						"backgroundSize":"cover"
					})
					$is_anonymous.val(1);
				}
			}
			
			$('.confirm_btn').click(function(){
				var content = $('textarea[name=content]').val(),
					name = $('input[name=name]').val(),
					phone = $('input[name=phone]').val(),
					is_anonymous = $('input[name=is_anonymous]').val(),
					phoneReg = /^((\+?86)|(\(\+86\)))?1[3|4|5|7|8][0-9]\d{8}$|^(09)\d{8}$/;
					
				if( isConfirm ){
					return;
				}	
					
				isConfirm = true;	
					
				if( content == '' || (/^\s+$/g).test(content) || content == undefined ){
					showAlertMsg('提示','请输入反馈内容！','知道了');
					isConfirm = false;
					return;
				}
				if( name == '' || (/^\s+$/g).test(name) || name == undefined ){
					showAlertMsg('提示','请输入姓名！','知道了');
					isConfirm = false;
					return;
				}
				if( phone == '' || !phoneReg.test(phone) || phone == undefined ){
					showAlertMsg('提示','请输入正确的手机号码！','知道了');
					isConfirm = false;
					return;
				}
				
				$.ajax({
					url: 'save_feedback.php?customer_id='+customer_id_en,
					dataType: 'json',
					type: 'post',
					data: {
						name: name,
						phone: phone,
						content: content,
						is_anonymous: is_anonymous,
						user_id: user_id
					},
					success: function(res){
						if( res.data > 0 ){
							window.location.href = 'feedback_success.php?customer_id='+customer_id_en;
						} else {
							isConfirm = false;
							showAlertMsg('提示',res.errmsg,'知道了');
						}
					},
					error: function(err){
						isConfirm = false;
					}
				})
			})
			
		</script>
	</body>
</html>
