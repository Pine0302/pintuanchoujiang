<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');
_mysql_query("SET NAMES UTF8");
require('../../../../weixinpl/proxy_info.php');
require('../../../../weixinpl/function_model/collageActivities.php');

$collageActivities = new collageActivities($customer_id);

$data = $collageActivities->getExplain($customer_id);
?>
<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>活动说明</title>
    <link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
    <link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
    <script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
    <style>
        .operation-btn{padding:7px 18px;color:#fff;border-radius:2px;cursor:pointer;}
        table#WSY_t1 td{text-align:center !important;}
        .ajax_deal{position: absolute;}
        .WSY_t4 a img{margin-bottom:-5px;}
        .WSY_righticon{width:97%;margin:15px auto;}
        .WSY_righticon:after{content:'';display:block;width:0;height:0;clear:both;}
    </style>
</head>
<body>
<!--内容框架-->
<div class="WSY_content">
		<!--列表内容大框-->
	<div class="WSY_columnbox">
	<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>
		<!--列表头部切换开始-->
		<!--列表头部切换结束-->
<!--门店列表开始-->
		<div class="WSY_data" style="min-height:180px">
            <ul class="WSY_righticon">
                <li style="float: right;"><a href="type.php?customer_id=<?php echo passport_encrypt((string)$customer_id);?>" style="font-size:14px;display:block;line-height:30px;padding-left:15px;padding-right:15px;border-radius:3px 3px 3px 3px;color:#fff;" class="WSY-skin-bg">保存</a></li>
            </ul>
			<!--表格开始-->
			<table width="97%" class="WSY_table WSY_t2" id="WSY_t1">
				<thead class="WSY_table_header">
					<tr>
						<th width="5%">序号</th>
                        <th width="10%">类型</th>
						<th width="20%">名称</th>
                        <th width="10%">排序</th>
                        <th width="10%">操作</th>
					</tr>
				</thead>
				<form name="form1" method="post">
					<tbody>
						<?php
						 foreach( $data['content'] as $key => $val ){

                               $type_str = '';//团类型

                               if( 1 == $val['type'] ){
								   $type_str = '普通团';
							   }elseif( 2 == $val['type'] ){
								    $type_str = '抽奖团';
							   }elseif( 3 == $val['type'] ){
								    $type_str = '秒杀团';
							   }elseif( 4 == $val['type'] ){
								    $type_str = '超级团';
							   }elseif( 5 == $val['type'] ){
								    $type_str = '抱抱团';
							   }elseif( 6 == $val['type'] ){
								    $type_str = '免单团';
							   }elseif( 7 == $val['type'] ){
                                   $type_str = '新抽奖团';
                               }

						?>
						<tr>
							<td><?php echo $val['id'] ?></td>
                            <td><?php echo $type_str; ?></td>
							<td><input id="<?php echo $val['type_name']; ?>" type="text" class="WSY_sorting" value="<?php echo $val['type_name']; ?>" onkeyup="clear_space(this);" onblur="changeTypename(<?php echo $val['id'] ?>,this)" style="width: 230px;display: inherit;" placeholder="请输入不超过5个汉字"/></td>
                            <td><input id="<?php echo $val['sort']; ?>" type="text" maxlength="2"  class="WSY_sorting" value="<?php echo $val['sort']; ?>" onblur="changeSort(<?php echo $val['id'] ?>,this);" onkeyup="clear_space(this),Integer(this);" style="width: 60px;display: inherit;"/></td>
                            <td>
                                <span class="operation-btn WSY-skin-bg" id="isshow_<?php echo $val['id'] ?>" onclick="isshow(<?php echo $val['id'] ?>)" ><?php if($val['isshow']){ echo "隐藏";}else{ echo "显示";} ?></span>
                                <input type=hidden id="isshow_<?php echo $val['id'] ?>_value" value=<?php echo $val['isshow'] ?> />
                            </td>
						</tr>
					<?php } ?>
					</tbody>
				</form>
			</table>
				<!--表格结束-->
				<div class="blank20"></div>
				<div id="turn_page"></div>
				<!--翻页开始-->
				<div class="WSY_page">

				</div>
				<!--翻页结束-->
		</div> <!--门店列表结束-->
		<?php
			mysql_close($link);
		?>
			<div style="width:100%;height:20px;"></div>
	</div>
</div>
<script>
function clear_space(obj){
    var num = obj.value;
    var reg=/^[^ ]+$/;
    reg.test(num) ? obj.value = num : obj.value = obj.value.replace(/(^\s+)|(\s+$)/g, "");
}

function Integer(obj){// 限制整数
    var num = obj.value;
    var reg=/^\d*$/;
    if(!reg.test(num)){
        isNaN(parseInt(num)) ? obj.value ='' : obj.value=parseInt(num);
    }
}

function changeSort(id,e){

var value=e.value;
var before_val=e.id;
var a=$(e);
//alert(value+'=='+before_val);
if(value == before_val){
    return;
    }else if(!value){
        layer.alert('请输入排序数字', {btnAlign: 'c'});
        return;
    }else if(isNaN(value)){
        // layer.alert('输入错误,排序只能是数字', {btnAlign: 'c'});
        e.value = parseInt(value);
        return;
    }else{
        a.after('<img id="ajax_deal" class="ajax_deal" src="/weixin/plat/Public/img/loading/ajax_small.gif" />');
        $.ajax({
            type: 'POST',
            url:'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
            dataType:'json',
            data:{
                op		: 'change_explain_sort',
                id	: id,
                val 	: value
            },
            success:function(data){
                if(data){
                $('#ajax_deal').attr('src',"/weixin/plat/Public/img/loading/s_success.png");
                setTimeout(function(){
                    $('#ajax_deal').remove();
                    e.value=data;
                    e.id=data;
                },500);

                }else{
                $('#ajax_deal').attr('src',"/weixin/plat/Public/img/loading/s_error.png");
                setTimeout(function(){
                    $('#ajax_deal').remove();

                },500);
                }


            },
            error:function(err){
                $('#ajax_deal').attr('src',"/weixin/plat/Public/img/loading/s_error.png");
                setTimeout(function(){
                    $('#ajax_deal').remove();

                },500);
            }

        })

    }

}
function GetLength(str){  //获取字符串的字节数
    var realLength = 0;
    for (var i = 0; i < str.length; i++)
    {
        charCode = str.charCodeAt(i);
        if (charCode >= 0 && charCode <= 128)
        realLength += 1;
        else
        realLength += 2;
    }
    return realLength;
}

function changeTypename(id,e){

var value=e.value;
var before_val=e.id;
var a=$(e);
var value_len = GetLength(value);
//alert(value+'=='+before_val);
if(value == before_val){
    return;
    }else if(!value){
        layer.alert('请输入名称', {btnAlign: 'c'});
        return;
    }else if(value_len>10){
        // var str = '<div style="text-align:center;"">请输入10个字节以内的名称<br>';
        //     str += '<span style="font-size:12px;color:#999;">(一个汉字2字节，一个数字或字母1字节)</span></div>';
        layer.alert('请请输入5个以内的字符', {btnAlign: 'c'});
        return;
    }else{
        a.after('<img id="ajax_deal" class="ajax_deal" src="/weixin/plat/Public/img/loading/ajax_small.gif" />');
        $.ajax({
            type: 'POST',
            url:'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
            dataType:'json',
            data:{
                op		: 'change_explain_typename',
                id	: id,
                val 	: value
            },
            success:function(data){
                if(data){
                $('#ajax_deal').attr('src',"/weixin/plat/Public/img/loading/s_success.png");
                setTimeout(function(){
                    $('#ajax_deal').remove();
                    e.value=data;
                    e.id=data;
                },500);

                }else{
                $('#ajax_deal').attr('src',"/weixin/plat/Public/img/loading/s_error.png");
                setTimeout(function(){
                    $('#ajax_deal').remove();

                },500);
                }


            },
            error:function(err){
                $('#ajax_deal').attr('src',"/weixin/plat/Public/img/loading/s_error.png");
                setTimeout(function(){
                    $('#ajax_deal').remove();

                },500);
            }

        })

    }

}

function isshow(id){  //控制是否显示类型
    value = $('#isshow_'+id+'_value').val();
    $.ajax({
        type: 'POST',
        url:'ajax_handle.php?customer_id=<?php echo $customer_id_en; ?>',
        dataType:'json',
        data:{
            op		: 'change_isshow',
            id	    : id,
            val 	: value
        },
        success:function(res){
            if(res){
                if(value=="1"){
                    $('#isshow_'+id).html('显示');
                    $('#isshow_'+id+'_value').val('0');
                }else{
                    $('#isshow_'+id).html('隐藏');
                    $('#isshow_'+id+'_value').val('1');
                }

            }else{
                alert('操作失败！');
            }

        },
        error:function(err){

        }

    })
}
</script>
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script type="text/javascript" src="../../Common/js/layer/layer.js"></script>
</body>
</html>
