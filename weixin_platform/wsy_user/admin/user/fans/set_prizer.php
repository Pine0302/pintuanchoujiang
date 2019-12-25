<?php
  header("Content-type: text/html; charset=utf-8"); 
  require('../../../../weixinpl/config.php');
  require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
  require('../../../../weixinpl/back_init.php');   
  $pagenum=1; 
  if(!empty($_GET["pagenum"])){
   $pagenum = $_GET["pagenum"];
  }
  $user_id = $configutil->splash_new($_GET["user_id"]); 
  $isAgent = $configutil->splash_new($_GET["isAgent"]);
  $fromw =$configutil->splash_new($_GET["fromw"]);
  $old_parent_id =$configutil->splash_new($_GET["old_parent_id"]);
  $group_id = $configutil->splash_new($_GET["group_id"]);

  $default_lottery_user_id = 0;
  $query = "select id , lottery_user_id from wsy_mark.collage_group_order_t  where id = ".$group_id;
  $result = _mysql_query($query) or die("L31 query error : ".mysql_error());
  $default_lottery_user_id = mysql_fetch_assoc($result)['lottery_user_id'];



  $link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
  mysql_select_db(DB_NAME) or die('Could not select database');
  _mysql_query("SET NAMES UTF8");
  require('../../../../weixinpl/proxy_info.php');


?>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link rel="stylesheet" type="text/css" href="../../Common/css/Users/promoter/add_qrsell_account.css">
<script type="text/javascript" src="../../../common/js_V6.0/assets/js/jquery.min.js"></script>
<title>修改中奖人</title>
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<style type="text/css">
	.search_btn{
		  width: 80px;
		  height: 24px;
		  background-color: #06a7e1;
		  border: none;
		  font-size: 14px;
		  color: #f9fdff;
		  border-radius: 2px;
		  border: solid 1px #06a7e1;
		  text-align: center;
	}
	.search_input{
		  width: 150px;
		  height: 24px;
		  background: #fefefe;
		  border: 1px solid #bbb;
		  color: #333;
		  border-radius: 3px;
		  padding-left: 5px;
	}
	#choose_parent{
		  line-height: 30px;
	}
	.rdo{
		  margin-left: 20px;
	}
	.WSY_button{
		float:none;
	}
	.headimg{
		  width: 40px;
		  height: 40px;
		  vertical-align: middle;
		  margin-right:10px;
	}
	.graybtn{
		  background-color: #919596;
		  border: solid 1px #919596;
	}
</style>
</head>
<body>
<div class="WSY_content">
	<div class="WSY_columnbox">
	<div class="WSY_column_header">
			<div class="WSY_columnnav">
				<a class="white1">修改中奖人</a>
			</div>
		</div>
		<form action="save_change_prizer.php?customer_id=<?php echo $customer_id_en ?>&pagenum=<?php echo $pagenum; ?>"  id="keywordFrm" method="post">
			<input type="hidden" name="group_id" id= "group_id" value="<?php echo $group_id ;?>" >
			<div class="WSY_remind_main">
				<dl class="WSY_remind_dl02"> 
					<dt>输入用户的id：</dt>
					<dd>
						<input type="text" class="search_input" name="user_id" id="user_id" placeHolder="输入用户编号" value="<?php echo  $default_lottery_user_id ?>"/>
						<input type="hidden" id="btn_search" value="搜索" class="search_btn"/> 
					</dd>
				</dl>
			 <dl class="WSY_remind_dl02" style="display: none"> 
					<dt>选择邀请人：</dt>
					<dd id="choose_parent">
						
					</dd>
				</dl> 
				
		<div class="submit_div" style="text-align:center">
			<input type="button" id="btn_submit" class="WSY_button" value="提交" onclick="submitV();" style="cursor:pointer;">
		</div>
	
	
	</form>
	</div>
</div> 	
</body>
<script>
function submitV(){
	//  document.getElementById("keywordFrm").submit();
	  var user_id = $('#user_id').val();
	  var group_id = $('#group_id').val();
    $.getJSON("save_change_prizer.php",{op:"save",user_id:user_id,group_id:group_id},function(json){
        alert('修改成功!');
        location.href='javascript:history.go(-1)';
    });
}







$(function(){
	$("#btn_search").click(function(){
		var key = $("#searchkey").val();
		var user_id = $("#user_id").val();
		if(user_id == "" || key == ""){
			alert("请先输入用户编号！");
			return;
		}
		$.getJSON("save_change_relation.php",{op:"search",searchkey:key,user_id:user_id},function(json){
			$("#choose_parent").html("");
			if(json.state > 0){
				$("#choose_parent").append(json.msg);
				$("#btn_submit").attr("disabled","disabled");
				$("#btn_submit").addClass("graybtn");
			}else{
				$("#choose_parent").append('<input type="radio" class="rdo" value="'
				+json.user_id+'" id="re_parent_'+json.user_id+'" checked name="re_parent"/> '+
				'<img src="'+json.headimg+'" class="headimg" />'+
				'<label for="re_parent_'+json.user_id+'">'+json.weixin_name+'</label>');
				$("#btn_submit").removeAttr("disabled");
				$("#btn_submit").removeClass("graybtn");
			}
		});
	});
});
</script>
<?php 

mysql_close($link);

?>
</html>