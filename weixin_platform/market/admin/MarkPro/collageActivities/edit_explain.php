<?php
 header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER, DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../../../../weixinpl/proxy_info.php');

require('../../../../weixinpl/function_model/collageActivities.php');

$keyid = $configutil->splash_new($_GET["keyid"]);

$collageActivities = new collageActivities($customer_id);
$data = $collageActivities->getOneExplain($keyid);
?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link rel="stylesheet" type="text/css" href="../../../back_newshops/Common/css/Base/basicdesign/base_set.css">
<script type="text/javascript" src="../../../common/js/jquery-2.1.0.min.js"></script>
<script type="text/javascript" src="../../Common/js/Base/basicdesign/layer.js"></script>
<script type="text/javascript" src="../../../common/js_V6.0/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="../../../common/utility.js"></script>

<title>编辑活动说明</title>

<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<style>
.distr_type_div i{margin-top:7px;}
.WSY_remind_dl02 .distr_type_div {height:35px;}
.WSY_remind_dl02 input[type="text"] {float: none; width: 137px;}
.screen{width: 63px;height: 20px;margin-left: 173px;margin-top: 22px;position: absolute;z-index: 1;}
</style>
</head>
<body>
<div class="WSY_content">
	<div class="WSY_columnbox">
		<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>
	<form>
		<div class="WSY_remind_main">

			<dl class="WSY_remind_dl02">
				<div>
					<dt>标题：</dt>
					<dd class="spa">
						<input type="text" value="<?php echo $data['title'];?>" name="title" id="title" style="width:250px;" disabled/>
					</dd>

				</div>
			</dl>
			<dl class="WSY_remind_dl02">
				<dt class="editor edit1" id="edit1" style="background-color:white;">规则说明：</dt>
				<div class="text_box input content" style="width:60%;margin-left: 163px;">
                	<textarea id="editor1" name="content" id="content"><?php echo $data['content']; ?></textarea>
                </div>
			</dl>

		</div>

	</form>
	<div class="submit_div">
			<input type="button" class="WSY_button" value="提交" onclick="return saveData(this);" style="cursor:pointer;">
		</div>
	</div>
</div>
<!--配置ckeditor和ckfinder-->
<script type="text/javascript" src="../../../../weixin/plat/Public/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src="../../../../weixin/plat/Public/ckfinder/ckfinder.js"></script>
<!--编辑器多图片上传引入开始-->
<script type="text/javascript" src="../../../../weixin/plat/Public/js/jquery.dragsort-0.5.2.min.js"></script>
<script type="text/javascript" src="../../../../weixin/plat/Public/swfupload/swfupload/swfupload.js"></script>
<script type="text/javascript" src="../../../../weixin/plat/Public/swfupload/js/swfupload.queue.js"></script>
<script type="text/javascript" src="../../../../weixin/plat/Public/swfupload/js/fileprogress.js"></script>
<script type="text/javascript" src="../../../../weixin/plat/Public/swfupload/js/handlers.js"></script>
<!--编辑器多图片上传引入结束-->
<script type="text/javascript" src="../../../common/js_V6.0/jquery.ui.datepicker.js"></script>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script>
function saveData(){
	var content = $('.cke_wysiwyg_frame').contents().find('body').html()
	if( content == '<p><br></p>' ){
		alert('规则说明不能为空！');
		return;
	}
	$.ajax({
		type: 'POST',
		url: 'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
		data:{
			op			: 'save_activity_explanation',
			keyid		: '<?php echo $keyid ?>',
			content		: content
		},
		dataType: 'json',
		success: function(data){
			if( data.code == 0 ){
				alert('保存成功');
				history.go(-1);
			}else{
				alert('保存失败');
			}
		}
	});
}



CKEDITOR.replace( 'editor1', //提现规则
{
extraAllowedContent: 'img iframe[*]',
filebrowserBrowseUrl : '../../../../weixin/plat/Public/ckfinder/ckfinder.html',
filebrowserImageBrowseUrl : '../../../../weixin/plat/Public/ckfinder/ckfinder.html?Type=Images',
filebrowserFlashBrowseUrl : '../../../../weixin/plat/Public/ckfinder/ckfinder.html?Type=Flash',
filebrowserUploadUrl : '../../../../weixin/plat/Public/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
filebrowserImageUploadUrl : '../../../../weixin/plat/Public/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
filebrowserFlashUploadUrl : '../../../../weixin/plat/Public/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Flash'
});


</script>
</body>
</html>