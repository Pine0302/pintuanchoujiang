<?php
header("Content-type: text/html; charset=utf-8");
require('../../../../weixinpl/config.php');
require('../../../../weixinpl/customer_id_decrypt.php'); //导入文件,获取customer_id_en[加密的customer_id]以及customer_id[已解密]
require('../../../../weixinpl/back_init.php');
$link = mysql_connect(DB_HOST,DB_USER,DB_PWD);
mysql_select_db(DB_NAME) or die('Could not select database');

require('../../../../weixinpl/proxy_info.php');
_mysql_query("SET NAMES UTF8");

require_once('../../../../weixinpl/function_model/collageActivities.php');
$collageActivities = new collageActivities($customer_id);

/*搜索条件*/
$condition = array(
	'at.customer_id' => $customer_id,
    'ae.customer_id' => $customer_id,
    'ae.isvalid' => true,
	'pt.isvalid' => true,
	'at.isvalid' => true,
	'CGPT.isvalid' => true
);
$search_pid = '';
if( !empty($_GET['search_pid']) ){
	$search_pid = $configutil->splash_new($_GET["search_pid"]);
	$condition['pt.pid'] = $search_pid;
}
$search_pname = '';
if( !empty($_GET['search_pname']) ){
	$search_pname = $configutil->splash_new($_GET["search_pname"]);
	$condition['cp.name'] = $search_pname;
}
$search_aid = '';
if( !empty($_GET['search_aid']) ){
	$search_aid = $configutil->splash_new($_GET["search_aid"]);
	$condition['pt.activitie_id'] = $search_aid;
}
$search_aname = '';
if( !empty($_GET['search_aname']) ){
	$search_aname = $configutil->splash_new($_GET["search_aname"]);
	$condition['at.name'] = $search_aname;
}
$search_atype = '';
if( !empty($_GET['search_atype']) ){
	$search_atype = $configutil->splash_new($_GET["search_atype"]);
	$condition['at.type'] = $search_atype;
}
$search_astatus = '';
if( !empty($_GET['search_astatus']) ){
	$search_astatus = $configutil->splash_new($_GET["search_astatus"]);
	$condition['at.status'] = $search_astatus;
}
/*搜索条件*/
/*获取的字段*/
$filed = " pt.id as ptid,pt.activitie_id,pt.pid,pt.price,pt.virtual_open,at.name AS aname,at.type,at.status,cp.name AS pname ";
$filed_count = " count(1) AS pcount ";	//统计数量
/*获取的字段*/
$pcount = $collageActivities -> get_activities_product($condition,$filed_count)['data'][0]['pcount'];
if( $pcount == '' ){
	$pcount = 0;
}

$pagenum = 1;//页码
$pagesize = 20;//每页数据数量

if(!empty($_GET["pagenum"])){
   $pagenum = $configutil->splash_new($_GET["pagenum"]);
}

$start = ($pagenum-1) * $pagesize;
$end = $pagesize;

$condition['ORDER'] = ' ORDER BY pt.activitie_id DESC ';
$condition['LIMIT'] = ' LIMIT '.$start.','.$end;
$info = $collageActivities -> get_activities_product($condition,$filed)['data'];

$page = ceil($pcount/$end);

$group_type_arr = array();
$query = "SELECT type,type_name FROM collage_activities_explain_t WHERE isvalid=true AND customer_id=".$customer_id;
$result = _mysql_query($query) or die('Query failed'.mysql_error());
while ( $row = mysql_fetch_object($result) ) {
	$type = $row->type;
    $type_name = $row->type_name;
    $group_type_arr[$type] = $type_name;
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>产品活动管理</title>
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content.css">
<link rel="stylesheet" type="text/css" href="../../../common/css_V6.0/content<?php echo $theme; ?>.css">
<link href="../../../common/add/css/global.css" rel="stylesheet" type="text/css">
<link href="../../../common/add/css/main.css" rel="stylesheet" type="text/css">
<link type="text/css" rel="stylesheet" rev="stylesheet" href="../../../css/inside.css" media="all">
<script type="text/javascript" src="../../../common/js/jquery-1.7.2.min.js"></script>
<script type="text/javascript" src="../../../common/js/inside.js"></script>
<script type="text/javascript" src="../../../js/tis.js"></script>
<script type="text/javascript" src="../../../js/WdatePicker.js"></script>
</head>
<style>
.operation-btn{padding: 5px 10px;color: #fff;border-radius: 2px;cursor:pointer;text-align: center;display:inline-block;}
.header-left{float:left;margin-left:15px;margin-top: 10px;}
.header-left input{height: 23px;}
.user_img{width: 50px;border-radius: 30px;}
.WSY_table span{display:block;}
.navbox{z-index: 999;}
.refund-box{
	width: 30%;
    text-align: center;
    position: fixed;
    border-radius: 6px;
    margin: 0 35%;
    background-color: #fff;
	z-index: 501;
	top: 35%;
}
.refund-box-title{
	background-color: #06a7e1;
    color: #fff;
    height: 35px;
    line-height: 35px;
}
.refund-box-content{
	height: 60px;
    line-height: 60px;
}
.refund-box-btn{
	height: 50px;
    line-height: 50px;
}
.shadow{
	display:none;
	width: 100%;
    position: fixed;
    height: 100%;
    background-color: #000;
    opacity: 0.4;
    top: 0;
	z-index: 500;
}
table#WSY_t1 tr td:nth-child(6),table#WSY_t1 tr td:nth-child(7){
    text-align: center !important;
}
table#WSY_t1 td{text-align: center;}
</style>
<body>
</div>
<!--内容框架开始-->
<div class="WSY_content" id="WSY_content_height">

       <!--列表内容大框开始-->
	<div class="WSY_columnbox">
    	<?php
			include("../../../../market/admin/MarkPro/collageActivities/collageActivities_head.php");
			?>

    <div class="WSY_data">
    	<div class="WSY_list">
			<div class="header-left">
				<span>产品ID：</span><input type="text" class="search-box" id="search-pid" value="<?php echo $search_pid;?>" onkeyup="clearInt(this)" />
				<span>产品名：</span><input type="text" class="search-box" id="search-pname" value="<?php echo $search_pname;?>" />
				<span>活动ID：</span><input type="text" class="search-box" id="search-aid" value="<?php echo $search_aid;?>" onkeyup="clearInt(this)" />
				<span>活动标题：</span><input type="text" class="search-box" id="search-aname" value="<?php echo $search_aname;?>" />
				<span>活动类型：</span>
				<select id="search-atype">
					<option value="-1" <?php if($search_atype==-1){echo 'selected';}?>>全部</option>
					<?php
                        $type_list = $collageActivities->getTypes($customer_id);
                        foreach($type_list as $key => $val){
                    ?>
					<option value="<?php echo $val['type'];?>" <?php if($search_type==$val['type']){echo 'selected';}?>><?php echo $val['type_name'];?></option>
                    <?php
                        }
                    ?>
				</select>
				<span>活动状态：</span>
				<select id="search-astatus">
					<option value="-1" <?php if($search_astatus==-1){echo 'selected';}?>>全部</option>
					<option value="1" <?php if($search_astatus==1){echo 'selected';}?>>未发布</option>
					<option value="2" <?php if($search_astatus==2){echo 'selected';}?>>进行中</option>
					<option value="3" <?php if($search_astatus==3){echo 'selected';}?>>终止</option>
					<option value="4" <?php if($search_astatus==4){echo 'selected';}?>>已结束</option>
				</select>
				<span class="operation-btn WSY-skin-bg" id="search-button">搜索</span>
                <span class="operation-btn WSY-skin-bg" id="edit-virtual">编辑虚拟开团数</span>
                <span class="operation-btn WSY-skin-bg" id="save-virtual" style="display:none">保存</span>
			</div>
    </div>
			<table width="97%" class="WSY_table" id="WSY_t1">
				<thead class="WSY_table_header">
					<th width="10%">产品编号</th>
					<th width="20%">产品名称</th>
					<th width="10%">活动价格</th>
					<th width="8%">活动ID</th>
					<th width="15%">活动主题</th>
					<th width="10%">活动类型</th>
					<th width="10%">活动状态</th>
					<th width="12%">操作管理</th>
                    <th width="10%">虚拟开团数</th>
				</thead>
				<?php
					foreach( $info as $v ){
				?>
				<tr>
					<td><?php echo $v['pid'];?></td>
					<td><?php echo $v['pname'];?></td>
					<td><?php echo $v['price'];?></td>
					<td><?php echo $v['activitie_id'];?></td>
					<td><?php echo htmlspecialchars($v['aname']);?></td>
					<td>
					<?php
                        echo $group_type_arr[$v['type']];
					?>
					</td>
					<td>
					<?php
						switch( $v['status'] ){
							case 1:
								echo '未发布';
							break;
							case 2:
								echo '进行中';
							break;
							case 3:
								echo '终止';
							break;
							case 4:
								echo '已结束';
							break;
						}
					?>
					</td>
					<td>
					<?php
						switch( $v['status'] ){
							case 1:
							case 2:
					?>
						<span class="operation-btn WSY-skin-bg" onclick="edit('<?php echo $v['activitie_id'];?>')">编辑</span>
					<?php
							break;
                        }
					?>
					</td>
                    <td><input id="<?php echo $v['virtual_open']; ?>" type="text" maxlength="2"  class="WSY_sorting edit_virtual" value="<?php echo $v['virtual_open']; ?>" onblur="changeVirtual(<?php echo $v['ptid'] ?>,this);" onkeyup="clear_space(this),Integer(this);" style="width: 60px;display: inherit;" disabled /></td>

				</tr>
				<?php } ?>
			</table>
    	</div>
        <!--翻页开始-->
        <div class="WSY_page">

        </div>
        <!--翻页结束-->
    </div>
</div>
<div class="shadow"></div>
<!--内容框架结束-->
<script type="text/javascript" src="../../../common/js_V6.0/content.js"></script>
<script src="../../../js/fenye/jquery.page1.js"></script>
<script type="text/javascript" src="../../../common/js/layer/layer.js"></script>
<script>
var customer_id = '<?php echo $customer_id;?>';
var customer_id_en = '<?php echo $customer_id_en;?>';
var search_pid= '<?php echo $search_pid;?>';
var search_pname = '<?php echo $search_pname;?>';
var search_aid = '<?php echo $search_aid;?>';
var search_aname = '<?php echo $search_aname;?>';
var search_atype = '<?php echo $search_atype;?>';
var search_astatus = '<?php echo $search_astatus;?>';

var pagenum = <?php echo $pagenum ?>;
var count =<?php echo $page ?>;//总页数
  	//pageCount：总页数
	//current：当前页
	$(".WSY_page").createPage({
        pageCount:count,
        current:pagenum,
        backFn:function(p){
			var url = "activityProMes.php?pagenum="+p+"&customer_id=<?php echo passport_encrypt((string)$customer_id) ?>";
			if( search_pid != '' && search_pid > 0 ){
				url += '&search_pid='+search_pid;
			}
			if( search_pname != '' ){
				url += '&search_pname='+search_pname;
			}
			if( search_aid != '' && search_aid > 0 ){
				url += '&search_aid='+search_aid;
			}
			if( search_aname != '' ){
				url += '&search_aname='+search_aname;
			}
			if( search_atype > 0 ){
				url += '&search_atype='+search_atype;
			}
			if( search_astatus > 0 ){
				url += '&search_astatus='+search_astatus;
			}
			document.location = url;
	   }
    });
</script>

<script>
var pagenum = <?php echo $pagenum ?>;
var page = <?php echo $page ?>;
function jumppage(){
	var a=parseInt($("#WSY_jump_page").val());
	if((a<1) || (a==pagenum) || isNaN(a)){
		return false;
	}else{
		var url = "activityProMes.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&pagenum="+a;
		if( search_pid != '' && search_pid > 0 ){
			url += '&search_pid='+search_pid;
		}
		if( search_pname != '' ){
			url += '&search_pname='+search_pname;
		}
		if( search_aid != '' && search_aid > 0 ){
			url += '&search_aid='+search_aid;
		}
		if( search_aname != '' ){
			url += '&search_aname='+search_aname;
		}
		if( search_atype > 0 ){
			url += '&search_atype='+search_atype;
		}
		if( search_astatus > 0 ){
			url += '&search_astatus='+search_astatus;
		}
		document.location = url;
	}
}
</script>
<script>
//输入框按回车键触发搜索
$('.header-left').find('input').on('keydown',function(){
	if( event.keyCode == 13 ){
		$('#search-button').click();
	}
});

//搜索
$('#search-button').click(function(){
	var search_pid = $('#search-pid').val();
	var search_pname = $('#search-pname').val();
	var search_aid = $('#search-aid').val();
	var search_aname = $('#search-aname').val();
	var search_atype= $('#search-atype').val();
	var search_astatus= $('#search-astatus').val();

	var url = "activityProMes.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>";
	if( search_pid != '' && search_pid > 0 ){
		url += '&search_pid='+search_pid;
	}
	if( search_pname != '' ){
		url += '&search_pname='+search_pname;
	}
	if( search_aid != '' && search_aid > 0 ){
		url += '&search_aid='+search_aid;
	}
	if( search_aname != '' ){
		url += '&search_aname='+search_aname;
	}
	if( search_atype > 0 ){
		url += '&search_atype='+search_atype;
	}
	if( search_astatus > 0 ){
		url += '&search_astatus='+search_astatus;
	}
	document.location = url;
});

$('#edit-virtual').click(function(){
    $('#save-virtual').show();
    $('.edit_virtual').removeAttr("disabled");
});

$('#save-virtual').click(function(){
    $('#save-virtual').hide();
    $('.edit_virtual').attr("disabled","disabled");
});

//编辑
function edit(activitie_id){
	window.location.href = 'addActivity.php?customer_id=<?php echo passport_encrypt((string)$customer_id) ?>&keyid='+activitie_id+"&comeFrom=2";
}

//正整数
function clearInt(obj){
	if(obj.value.length==1){obj.value=obj.value.replace(/[^1-9]/g,'')}else{obj.value=obj.value.replace(/\D/g,'')}
}

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

function changeVirtual(id,e){

var value=e.value;
var before_val=e.id;
var a=$(e);
//alert(value+'=='+before_val);
if(value == before_val){
    return;
    }else if(!value){
        layer.alert('请输入虚拟开团数', {btnAlign: 'c'});
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
                op		: 'change_virtual_open',
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
</script>
<?php
	mysql_close($link);
?>
</body>
</html>