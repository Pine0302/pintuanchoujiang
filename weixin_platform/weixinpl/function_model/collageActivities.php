<?php
opcache_reset();
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/currency.php");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/common/utility_shop.php");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/shop/shop.php");
require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/function/handle_shop_order.php");
require_once($_SERVER['DOCUMENT_ROOT']."/weixinpl/function_model/moneybag.php");

class collageActivities{

    private $customer_id;	//商家id

    public function __construct($customer_id){
        $this->customer_id = $customer_id;
    }

    /*
     * 获取团产品推荐设置
     * @param  array  $condition  搜索条件字符串
     * @param  string  $filed  查找字段字符串
     */
    public function getProductRecommendationSet($condition=array(),$filed='*'){
        $list['code'] = 0;
        $list['content'] = '';

        $condition_new = '';
        foreach( $condition as $k => $v ){
            $condition_new .= " AND ".$k."=".$v;
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }

        $query = "SELECT ".$filed."
					FROM collage_activities_product_recommendation_set_t AS caprst
					LEFT JOIN collage_activities_product_recommendation_systemset_t AS caprsyst ON caprst.id=caprsyst.recommendation_id
					WHERE ".$condition_new;
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['content'] = $error;
            return $list;
        }
        $list['content'] = mysql_fetch_assoc($result);

        return $list;
    }

    /*
     * 获取团推荐产品自定义模式
     * @param  array  $condition  搜索条件字符串
     * @param  string  $filed  查找字段字符串
     */
    public function get_recommendation_product($condition=array(),$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '';
        $list['data'] = [];
        $condition_limit = '';
        $condition_order_by = '';
        $query_product = "SELECT ".$filed."
					FROM collage_activities_recommendation_product_t AS carpt
					LEFT JOIN collage_group_products_t AS cgpt ON carpt.pid=cgpt.pid
					LEFT JOIN collage_activities_t AS cat ON cgpt.activitie_id=cat.id
					LEFT JOIN weixin_commonshop_products AS wcp ON carpt.pid=wcp.id
					WHERE ";
        foreach( $condition as $k => $v ){
            if( $k == 'carpt.pid' ){
                $condition_new .= "AND ".$k." ".$v." ";
                continue;
            }
            if( $k == 'limit' ){
                $condition_limit = $v;
                continue;
            }
            if( $k == 'order_by' ){
                $condition_order_by = $v;
                continue;
            }
            $condition_new .= "AND ".$k."=".$v." ";
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,3,-1);
        }
        if( $condition_order_by != '' ){
            $condition_new .= $condition_order_by;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }
        $query_product .= $condition_new;
        $result_product = _mysql_query($query_product);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while( $row_product = mysql_fetch_assoc($result_product) ){
                $list['data'][] = $row_product;
            }
        }

        return $list;
    }
    /*
     * 获取团推荐产品系统模式
     * @param  array  $condition  搜索条件字符串
     * @param  string  $filed  查找字段字符串
     */
    public function get_recommendation_product_system($condition=array(),$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '';
        $list['data'] = [];
        $condition_order_by = '';
        $condition_order_by2 = '';
        $condition_limit = '';
        $condition_new = '';

        foreach( $condition as $k => $v ){
            //按活动时间最新时间
            if( $k == 'order_by' ){
                $condition_order_by = $v;
                continue;
            }
            //按活动开团数量
            if( $k == 'order_by2' ){
                $condition_order_by2 = $v;
                continue;
            }
            //团类型
            if( $k == 'cat.type' ){
                $condition_type = " AND (";
                $condition_types = '';
                foreach( $v as $val ){
                    if( $val != '' ){
                        $condition_types .= " OR ".$k."=".$val;
                    }
                }
                $condition_types = substr($condition_types,3);
                if( $condition_types == '' ){
                    $condition_type = "";
                } else {
                    $condition_type .= $condition_types.") ";
                }
                $condition_new .= $condition_type;
                continue;
            }
            //按产品分类相似度
            if( $k == 'wcp.type_ids' ){
                $condition_type_id = " AND (";
                $condition_type_ids = '';
                foreach( $v as $val ){
                    if( $val != '' ){
                        $condition_type_ids .= " OR LOCATE(',".$val.",', ".$k.")>0 ";
                    }
                }
                $condition_type_ids = substr($condition_type_ids,3);
                if( $condition_type_ids == '' ){
                    $condition_type_id = "";
                } else {
                    $condition_type_id .= $condition_type_ids.") ";
                }
                $condition_new .= $condition_type_id;
                continue;
            }
            //按活动时间
            if( $k == 'cat.start_time_cp' ){
                /* $condition_new .= " AND UNIX_TIMESTAMP(cat.start_time)>=".strtotime(date('Y-m-d',time()));
                $condition_new .= " AND UNIX_TIMESTAMP(cat.start_time)<=".strtotime(date('Y-m-d',strtotime('+1 day'))); */
                continue;
            }
            //显示数量
            if( $k == 'limit' ){
                $condition_limit = $v;
                continue;
            }
            $condition_new .= " AND ".$k."=".$v;
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order_by != '' ){
            $condition_new .= $condition_order_by;
        }
        if( $condition_order_by2 != '' ){
            $condition_new .= " GROUP BY cgpt.id ".$condition_order_by2;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query_product = "SELECT ".$filed."
					FROM collage_group_products_t cgpt
					INNER JOIN collage_activities_t AS cat ON cgpt.activitie_id=cat.id
					INNER JOIN weixin_commonshop_products AS wcp ON cgpt.pid=wcp.id
					LEFT JOIN collage_group_order_t AS cgot ON cgpt.activitie_id=cgot.activitie_id
                    LEFT JOIN collage_activities_explain_t AS ae ON ae.type=cat.type
					WHERE ".$condition_new;

        $result_product = _mysql_query($query_product);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while( $row_product = mysql_fetch_assoc($result_product) ){
                $list['data'][] = $row_product;
            }
        }

        return $list;
    }

    /*
     * 保存团产品推荐设置
     * @param  array  $addArray  保存数据数组
     */
    public function addProductRecommendationSet($addArray){
        $system_set_id = -1;
        $query = "UPDATE collage_activities_product_recommendation_set_t SET pattern=".$addArray['pattern']." WHERE id=".$addArray['recommendation_id'];
        _mysql_query($query) or die('Query failed:'.mysql_error());

        if( $addArray['pattern'] == 1 ){
            if( $addArray['system_set_id'] > 0 ){
                $query = "UPDATE collage_activities_product_recommendation_systemset_t SET num=".$addArray['num'].",type='".$addArray['type']."',style='".$addArray['style']."',sort='".$addArray['sort']."' WHERE id=".$addArray['system_set_id'];
            } else {
                $query = "INSERT INTO collage_activities_product_recommendation_systemset_t(
											recommendation_id,
											num,
											type,
											style,
											sort
										) VALUES (
											".$addArray['recommendation_id'].",
											".$addArray['num'].",
											'".$addArray['type']."',
											".$addArray['style'].",
											".$addArray['sort']."
										)";
            }
            _mysql_query($query) or die('Query failed:'.mysql_error());
            if( $addArray['system_set_id'] > 0 ){
                $system_set_id = $addArray['system_set_id'];
            } else {
                $system_set_id = mysql_insert_id();
            }
        }
        return $system_set_id;
    }

    /**
     * [执行sql获取活动说明-二维数组]
     * @param  int   $customer_id  商家编号
     * @return [type]      [description]
     */
    public function getExplain($customer_id){

        require_once($_SERVER['DOCUMENT_ROOT']."/market/admin/MarkPro/collageActivities/config.php");
        $list['code'] = 40006;
        $list['content'] = '';
        $query = 'SELECT id,title,createtime,status,content,type,sort,type_name,isshow FROM collage_activities_explain_t where isvalid=true and customer_id='.$customer_id.' order by sort asc,id asc';
        $result = _mysql_query($query) or die('getExplain query failed: ' . mysql_error());


        while($row = mysql_fetch_assoc($result)){
            $res[]=$row;
        }
        $res_new = [];
        foreach($res as $key => $value){
            if($value['type']==0){
                if($value['title']=='拼团说明'){
                    $type = 1;
                }elseif($value['title']=='抽奖团说明'){
                    $type = 2;
                }elseif($value['title']=='秒杀团说明'){
                    $type = 3;
                }elseif($value['title']=='超级团说明'){
                    $type = 4;
                }elseif($value['title']=='抱抱团说明'){
                    $type = 5;
                }elseif($value['title']=='免单团说明'){
                    $type = 6;
                }elseif($value['title']=='新抽奖团说明'){
                    $type = 7;
                }
                $query = "UPDATE collage_activities_explain_t SET type=".$type." WHERE id=".$value['id'];
                _mysql_query($query) or die('updateExplain query failed: ' . mysql_error());
                $res[$key]['type'] = $type;
            }
            if(empty($value['type_name'])){
                if($value['title']=='拼团说明'){
                    $type_name = "普通团";
                }elseif($value['title']=='抽奖团说明'){
                    $type_name = "抽奖团";
                }elseif($value['title']=='秒杀团说明'){
                    $type_name = "秒杀团";
                }elseif($value['title']=='超级团说明'){
                    $type_name = "超级团";
                }elseif($value['title']=='抱抱团说明'){
                    $type_name = "抱抱团";
                }elseif($value['title']=='免单团说明'){
                    $type_name = "免单团";
                }elseif($value['title']=='新抽奖团说明'){
                    $type_name = "新抽奖团";
                }
                $query = "UPDATE collage_activities_explain_t SET type_name='".$type_name."' WHERE id=".$value['id'];
                _mysql_query($query) or die('updateExplain2 query failed: ' . mysql_error());
                $res[$key]['type_name'] = $type_name;
            }
            if ( $value['type'] > 0 ) {$type = $value['type'];}
            $res_new[$type] = $res[$key];
        }
        $sql[1]['title'] = '拼团说明';
        $sql[2]['title'] = '抽奖团说明';
        $sql[3]['title'] = '秒杀团说明';
        $sql[4]['title'] = '超级团说明';
        $sql[5]['title'] = '抱抱团说明';
        $sql[6]['title'] = '免单团说明';
        $sql[6]['title'] = '新抽奖团说明';

        $sql[1]['type'] = 1;
        $sql[2]['type'] = 2;
        $sql[3]['type'] = 3;
        $sql[4]['type'] = 4;
        $sql[5]['type'] = 5;
        $sql[6]['type'] = 6;
        $sql[6]['type'] = 7;

        $sql[1]['type_name'] = "普通团";
        $sql[2]['type_name'] = "抽奖团";
        $sql[3]['type_name'] = "秒杀团";
        $sql[4]['type_name'] = "超级团";
        $sql[5]['type_name'] = "抱抱团";
        $sql[6]['type_name'] = "免单团";
        $sql[6]['type_name'] = "新抽奖团";

        $sql['content'] = '<p><span style="color: rgb(51, 51, 51); font-family: "Arial Normal", Arial; font-size: 13px;">1.活动规则</span><br style="color: rgb(51, 51, 51); font-family: "Arial Normal", Arial; font-size: 13px;"><span style="color: rgb(51, 51, 51); font-family: "Arial Normal", Arial; font-size: 13px;">1.1、有活动的开始和截止的时间。</span><br style="color: rgb(51, 51, 51); font-family: "Arial Normal", Arial; font-size: 13px;"><span style="color: rgb(51, 51, 51); font-family: "Arial Normal", Arial; font-size: 13px;">1.2、可以设置几人成团，例如10人成团：首先购买的为团长，团长转发的邀请组团的链接发出去，别人可以参加这个团，其他人看到这个链接转发出去，一样可以邀请人过来参加这个团。到达10人后便是组团成功。</span><br style="color: rgb(51, 51, 51); font-family: "Arial Normal", Arial; font-size: 13px;"><span style="color: rgb(51, 51, 51); font-family: "Arial Normal", Arial; font-size: 13px;">1.3、可以设置抽奖，在组团成功的团中，设置好一定比例的中奖率或中奖团数（到底以团为算还是以人为算），中奖者发放中奖物资，其余的人全额退款。</span></p>';

        if ( isOpenBBT ) {
            $explainLen = 7;
        } else {
            $explainLen = 6;
        }

        for( $i=1;$i<=7;$i++ ){
            if ( !isOpenBBT && $i == 5 ) {
                continue;
            }
            if(empty( $res_new[$i] )){
                $query = "INSERT INTO collage_activities_explain_t(customer_id,isvalid,createtime,title,content,status,type,sort,type_name) VALUES(".$customer_id.",true,now(),'".$sql[$i]['title']."','".$sql['content']."',2,".$sql[$i]['type'].",0,'".$sql[$i]['type_name']."')";
                _mysql_query($query) or die('Explain INSERT failed: ' . mysql_error());
                $list['content'][] = array('id'=>mysql_insert_id(),'title'=>$sql[$i]['title'],'createtime'=>date('Y-m-d H:i:s',time()),'status'=>2,'type'=>$sql[$i]['type'],'sort'=>0,'type_name'=>$sql[$i]['type_name']);
            }else{
                $list['content'][] = $res_new[$i];
            }
        }
        $list['code'] = $explainLen == count($list['content'])?0:40006;

        return $list;
    }

    /**
     * [执行sql获取活动说明-单个数据]
     * @param  int   $keyid  说明ID
     * @return [type]      [description]
     */
    public function getOneExplain($keyid){
        $query = 'SELECT title,content FROM collage_activities_explain_t where isvalid=true and id='.$keyid.'';
        $result = _mysql_query($query) or die('getExplain query failed: ' . mysql_error());
        return $list = mysql_fetch_assoc($result);
    }

    /**
     * [获取团类型]
     * @param  int   $customer_id
     * @return [type]      [description]
     */
    public function getTypes($customer_id){
        $query = 'SELECT type,type_name FROM collage_activities_explain_t where isvalid=true and customer_id='.$customer_id.' order by type';
        $result = _mysql_query($query) or die('getTypes query failed: ' . mysql_error());
        while($row=mysql_fetch_array($result)){
            $list[] = $row;
        }
        return $list;
    }

    /**
     * [获取团类型]
     * @param  int   $customer_id
     * @return [type]      [description]
     */
    public function getTypes2($customer_id,$no_baobao = false){
        $no_baobao = $no_baobao ? " AND type != 5 " : "";
        $query = 'SELECT type,type_name FROM collage_activities_explain_t where isvalid=true and isshow=1 and customer_id='.$customer_id." " . $no_baobao .'  order by sort asc,id asc';
        $result = _mysql_query($query) or die('getTypes query failed: ' . mysql_error());
        while($row=mysql_fetch_assoc($result)){
            $list[] = $row;
        }
        return $list;
    }

    /**
     * [执行sql添加说明]
     * @param  int   $customer_id  商家编号
     * @param  varchar   $content  保存的内容
     * @param  int   $explain_id  说明id
     * @return [type]      [description]
     */
    public function addExplain($customer_id,$content,$explain_id){
        $query = "UPDATE collage_activities_explain_t SET content='".$content."' WHERE id=".$explain_id." AND customer_id=".$customer_id."";
        _mysql_query($query) or die('addExplain query failed: ' . mysql_error());
        $list['code'] = 0;
        $list['content'] = array('id'=>$explain_id,'content'=>$content);
        return $list;
    }

    /**
     * [执行sql获取拼团推荐数据]
     * @param  int   $customer_id  商家编号
     * @return [type]      [description]
     */
    public function getGroupRecommendation($customer_id){

        $list = array();
        $query = "SELECT id,is_open,num,type,sort_type,sort FROM collage_activities_group_recommendation_t where customer_id=".$customer_id." LIMIT 1";
        $result = _mysql_query($query) or die('getGroupRecommendation query failed: ' . mysql_error());
        return $list = mysql_fetch_assoc($result);
    }

    /**
     * [执行sql获取拼团推荐数据]
     * @param  int   $customer_id  商家编号
     * @param  Array   $addArray  修改数据数组
     * @return [type]      [description]
     */
    public function addGroupRecommendation($customer_id,$addArray,$keyid){

        $list['code'] = 40006;
        $list['content'] = '保存失败';

        if( !empty( $addArray ) ){

            $add_str = '';
            foreach( $addArray as $key => $val ){
                $add_str .= $key."='".$val."',";
            }
            $add_str = trim($add_str,',');
            if( 0 < $keyid ){
                $query = "UPDATE collage_activities_group_recommendation_t SET
						".$add_str."
						WHERE id=".$keyid." AND customer_id=".$customer_id." ";
            }else{
                $query = "INSERT INTO collage_activities_group_recommendation_t(
						customer_id,
						createtime,
						is_open,
						num,
						type,
						sort_type,
						sort
						) VALUES(
						".$customer_id.",
						now(),
						1,
						0,
						'',
						'',
						''
						)";
            }
            _mysql_query($query) or die('addGroupRecommendation query failed: ' . mysql_error());

            $list['code'] = 0;
            $list['content'] = '保存成功';
        }

        return $list;
    }

    /**
     * [执行sql获取产品活动列表数据]
     * @param  int   $customer_id  商家编号
     * @param  String   $activitie_id  活动ID
     * @param  String   $condition  查找条件
     * @param  String   $filed  查找字段字符串
     * @return [type]      [description]
     */
    public function get_activities_product($condition=array(),$filed='*'){

        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        $condition_order = '';

        foreach( $condition as $k => $v ){
            if( $k == 'cp.name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'at.name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'pt.stock' ){
                $condition_new .= " AND pt.stock > 0";
                continue;
            }
            if( $k == 'at.end_time' ){
                $condition_new .= " AND at.end_time >= '".$v."'";
                continue;
            }
            if( $k == 'at.start_time' ){
                $condition_new .= " AND at.start_time <= '".$v."'";
                continue;
            }
            if( $k == 'search_type1' ){
                $condition_new .= " AND cp.type_ids LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'search_type2' ){
                $type_str=explode(',', $v);
                $condition_new .= " AND (";
                foreach ($type_str as $type_key => $type_one) { //$type_one为二级分类，添加三级和四级分类的查询
                    $sql_typeids = 'SELECT id FROM weixin_commonshop_types WHERE parent_id="'.$type_one.'"';
                    $result_typeids = _mysql_query($sql_typeids);
                    $type_one_3 = ''; //三级
                    $type_one_4 = ''; //四级
                    while ($row_typeids = mysql_fetch_object($result_typeids)) {
                        $pid = $row_typeids->id;
                        if($pid){
                            $type_one_3 .= ','.$pid;
                        }
                    }
                    if($type_one_3 != ''){
                        $type_one_3_q = substr($type_one_3,1);//三级分类
                        $type_one_3_arr = explode(',',$type_one_3_q);
                        foreach ($type_one_3_arr as $kp => $vp){
                            $sql_typeids = 'SELECT id FROM weixin_commonshop_types WHERE parent_id="'.$vp.'"';
                            $result_typeids = _mysql_query($sql_typeids);
                            while ($row_typeids = mysql_fetch_object($result_typeids)) {
                                $pid = $row_typeids->id;
                                if($pid){
                                    $type_one_4 .= ','.$pid;
                                }
                            }
                        }
                    }
                    $type_one_all_str = $type_one.$type_one_3.$type_one_4;//二级以下
					$type_one_all = explode(',',$type_one_all_str);
                    foreach ($type_one_all as $kk => $vv){
                        if($type_key == 0 && $kk == 0){
                            $condition_new .=" cp.type_ids LIKE '%,".$vv.",%'" ;
                        }else{
                            $condition_new .=" or cp.type_ids LIKE '%,".$vv.",%'" ;
                        }
                    }
                }
                $condition_new .= " ) ";
                continue;
            }
            if ($k == "no_baobao" && $v == true){
                $condition_new .= " AND at.type != 5 ";
                continue;
            }
            if ($k == "is_mini_mshop" && $v == true){
                $condition_new .= " AND cp.is_mini_mshop = true ";
                continue;
            }

            if( $k == 'LIMIT' ){
                $condition_limit .= $v;
                continue;
            }

            if( $k == 'ORDER' ){
                $condition_order .= $v;
                continue;
            }

            $condition_new .= " AND ".$k."=".$v;
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order != '' ){
            $condition_new .= $condition_order;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query = "SELECT ".$filed."
			FROM collage_group_products_t AS pt
			INNER JOIN collage_activities_t AS at ON at.id=pt.activitie_id
			INNER JOIN weixin_commonshop_products AS cp ON cp.id=pt.pid
            LEFT JOIN collage_activities_explain_t AS ae ON ae.type=at.type
            INNER JOIN collage_group_products_t AS CGPT ON cp.id = CGPT.pid AND at.id = CGPT.activitie_id
			 WHERE ".$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();

        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error ." | ". $query;
        } else {
            while($row=mysql_fetch_assoc($result)){
                $list['data'][] = $row;
            }
        }

        return $list;

    }
    /**
     * [执行sql获取产品活动列表数据]
     * @param  int   $customer_id  商家编号
     * @param  String   $activitie_id  活动ID
     * @param  String   $condition  查找条件
     * @param  String   $filed  查找字段字符串
     * @return [type]      [description]
     */
    public function get_group_order($condition='',$filed='*'){

        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        $condition_order = '';

        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' && $k != 'ORDER' && $k != 'begintime' && $k != 'endtime' && $k != 'search_head_name' && $k != 'mp.name' && $k != 'ot.status' && $k != 'pay_timeout' ){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }
            if ( $k == 'pay_timeout' ) {
                $condition_new .= $v;
                continue;
            }

            if( $k == 'ot.status' ){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }

            if( $k == 'LIMIT' ){
                $condition_limit .= $v;
                continue;
            }
            if( $k == 'endtime' ){
                $condition_new .= $v;
                continue;
            }
            if( $k == 'search_head_name' ){
                $condition_new .= " AND (wu.name LIKE '%".$v."%' OR wu.weixin_name LIKE '%".$v."%')";
                continue;
            }
            if( $k == 'mp.name' ){
                $condition_new .= " AND mp.name LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'begintime' ){
                $condition_new .= $v;
                continue;
            }

            if( $k == 'ORDER' ){
                $condition_order .= $v;
                continue;
            }

        }

        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order != '' ){
            $condition_new .= $condition_order;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query = "SELECT ".$filed."
				FROM collage_activities_t AS at
				LEFT JOIN collage_group_order_t AS ot ON at.id=ot.activitie_id
				INNER JOIN weixin_users AS wu ON wu.id=ot.head_id
				INNER JOIN weixin_commonshop_products AS mp ON mp.id=ot.pid
			 WHERE ".$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();

        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row=mysql_fetch_array($result)){
                $list['data'][] = $row;
            }
        }

        return $list;

    }

    /**
     * [执行sql获取团订单列表数据]
     * @param  int   $customer_id  商家编号
     * @param  String   $activitie_id  活动ID
     * @param  String   $condition  查找条件
     * @param  String   $filed  查找字段字符串
     * @return [type]      [description]
     */
    public function get_crew_order_mes($condition='',$filed='*'){


        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        $condition_order = '';

        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' && $k != 'ORDER' && $k != 'begintime' && $k != 'endtime' && $k != 'search_name' && $k != 'oa.name' ){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }

            if( $k == 'LIMIT' ){
                $condition_limit .= $v;
                continue;
            }
            if( $k == 'search_name' ){
                $condition_new .= $v;
                continue;
            }
            if( $k == 'oa.name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."'%";
                continue;
            }
            if( $k == 'endtime' ){
                $condition_new .= $v;
                continue;
            }
            if( $k == 'begintime' ){
                $condition_new .= $v;
                continue;
            }

            if( $k == 'ORDER' ){
                $condition_order .= $v;
                continue;
            }

        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order != '' ){
            $condition_new .= $condition_order;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query = "SELECT ".$filed."
				FROM collage_crew_order_t AS ot
				LEFT JOIN collage_crew_order_pro_mes_t AS mt ON ot.batchcode=mt.batchcode
				LEFT JOIN weixin_users AS wu ON ot.user_id=wu.id
				LEFT JOIN weixin_commonshop_order_addresses AS oa ON ot.batchcode=oa.batchcode
				LEFT JOIN weixin_commonshop_orders AS wco ON ot.batchcode=wco.batchcode
			 WHERE ".$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();

        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row=mysql_fetch_array($result)){
                $list['data'][] = $row;
            }
        }
        return $list;

    }

    /**
     * [执行sql获取参与团用户数据]
     * @param  String   $condition  查找条件
     * @param  String   $filed  查找字段字符串
     * @return [type]      [description]
     */
    public function get_activities_user($condition='',$filed='*'){

        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        $condition_order = '';
        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' && $k != 'wu.name' && $k != 'wu.weixin_name' && $k != 'ORDER' ){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }
            if( $k == 'wu.name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'wu.weixin_name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'LIMIT' ){
                $condition_limit .= $v;
                continue;
            }

            if( $k == 'ORDER' ){
                $condition_order .= $v;
                continue;
            }

        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order != '' ){
            $condition_new .= $condition_order;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query = "SELECT ".$filed."
					FROM weixin_users AS wu
					LEFT JOIN weixin_users AS wu2 ON wu2.id=wu.parent_id
					INNER JOIN collage_activities_user_mes_t AS mt ON mt.user_id=wu.id
				 WHERE ".$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();

        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row=mysql_fetch_array($result)){
                $list['data'][] = $row;
            }
        }
        return $list;
    }

    /**
     * [执行sql获取抱抱团参与团用户数据]
     * @param  String   $condition  查找条件
     * @param  String   $filed  查找字段字符串
     * @return [type]      [description]
     */
    public function get_bbt_user($condition='',$filed='*'){

        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        $condition_order = '';
        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' && $k != 'wu.name' && $k != 'wu.weixin_name' && $k != 'ORDER' ){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }
            if( $k == 'wu.name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'wu.weixin_name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'LIMIT' ){
                $condition_limit .= $v;
                continue;
            }

            if( $k == 'ORDER' ){
                $condition_order .= $v;
                continue;
            }

        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order != '' ){
            $condition_new .= $condition_order;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query = "SELECT ".$filed."
					FROM weixin_users AS wu
					LEFT JOIN weixin_users AS wu2 ON wu2.id=wu.parent_id
					INNER JOIN collage_activities_user_mes_t AS mt ON mt.user_id=wu.id
                    LEFT JOIN collage_bbt_order_extend AS cboe ON cboe.user_id=wu.id
                    LEFT JOIN collage_group_order_t AS cgot ON cgot.id=cboe.group_id
				 WHERE ".$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();

        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row=mysql_fetch_array($result)){
                $list['data'][] = $row;
            }
        }
        return $list;
    }

    /*
     * 获取活动信息
     * @param  string  $condition  搜索条件字符串
     * @param  string  $filed  查找字段字符串
     */
    public function getActivitiesMes($condition=null,$filed='*'){

        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        $condition_order = '';
        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' && $k != 'name' && $k != 'ORDER' ){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }
            if( $k == 'name' ){
                $condition_new .= " AND ".$k." LIKE '%".$v."%'";
                continue;
            }
            if( $k == 'LIMIT' ){
                $condition_limit .= $v;
                continue;
            }

            if( $k == 'ORDER' ){
                $condition_order .= $v;
                continue;
            }

        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order != '' ){
            $condition_new .= $condition_order;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query = "SELECT ".$filed." FROM collage_activities_t WHERE ".$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();

        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row=mysql_fetch_array($result)){
                $list['data'][] = $row;
            }
        }
        return $list;
    }

    /*
     * 获取活动关联的产品
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function getActivitiesProduct($condition=array(),$filed='*'){
        foreach( $condition as $k => $v ){
            $condition_new .= "AND ".$k."=".$v." ";
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,3,-1);
        }
        $query_product = "SELECT ".$filed." FROM collage_group_products_t AS cgpt
						LEFT JOIN weixin_commonshop_products AS wcp ON cgpt.pid=wcp.id
						WHERE ".$condition_new;
        $result_product = _mysql_query($query_product) or die('Query_product failed:'.mysql_error());
        while( $row_product = mysql_fetch_assoc($result_product) ){
            $data[] = $row_product;
        }
        return $data;
    }

    /*
     * 保存活动
     * @param  array  $addArray  保存信息数组
     * @param  string  $activitie_id  活动id
     */
    public function change_group_products_t($addArray,$activitie_id=null){
        $list['code'] = 0;
        $list['content'] = '保存成功';

        $activity 		= $addArray['activity'];
        $product_info 	= $addArray['product_info'];
        $del_product 	= $addArray['delPidStr'];
        $add_product 	= $addArray['addPidArr'];

        //保存活动基本信息
        if( $activitie_id > 0 ){
            $update_sql = '';
            foreach( $activity as $k => $v ){
                if( $k == 'id' ){
                    continue;
                }
                $update_sql .= $k."='".$v."',";
            }
            $update_sql = substr($update_sql,0,-1);
            $query = "UPDATE collage_activities_t SET ".$update_sql." WHERE id=".$activitie_id;
        } else {
            $filed = '';
            $val = '';
            foreach( $activity as $k => $v ){
                if( $k == 'id' ){
                    continue;
                }
                $filed .= $k.",";
                if( $k == 'createtime' || $k == 'isvalid' ){
                    $val .= "".$v.",";
                } else {
                    $val .= "'".$v."',";
                }
            }
            $filed = substr($filed,0,-1);
            $val = substr($val,0,-1);
            $query = "INSERT INTO collage_activities_t(".$filed.") VALUES (".$val.")";
        }
        _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['content'] = '保存失败';
            return $list;
        }

        if( $activitie_id < 0 ){
            $activitie_id = mysql_insert_id();
        }

        //移除活动关联产品
        if( !empty($del_product) && $activitie_id > 0 ){
            $query_del = "UPDATE collage_group_products_t SET isvalid=false WHERE pid in (".$del_product.") AND activitie_id=".$activitie_id;
            _mysql_query($query_del);
            $error = mysql_error();
            if( $error ){
                $list['code'] = 40006;
                $list['content'] = '保存产品失败';
                return $list;
            }
        }

        //添加活动关联产品
        if( !empty($add_product) && $activitie_id > 0 ){
            $query_add = "INSERT INTO collage_group_products_t (
									activitie_id,
									createtime,
									isvalid,
									pid,
									status,
									price,
									stock,
									number,
									total_open,
									total_success,
									total_fail,
									total_conduct,
									open_day,
									sort
								) VALUES ";
            foreach( $add_product as $v ){
                $query_add_val .= "(
									".$activitie_id.",
									now(),
									true,
									".$v.",
									1,
									0,
									0,
									-1,
									0,
									0,
									0,
									0,
									0,
									0
									),";
            }
            $query_add_val = substr($query_add_val,0,-1);
            $query_add .= $query_add_val;
            _mysql_query($query_add);
            $error = mysql_error();
            if( $error ){
                $list['code'] = 40006;
                $list['content'] = '保存产品失败';
                return $list;
            }
        }

        //修改活动产品信息
        if( !empty($product_info) && $activitie_id > 0 ){
            foreach( $product_info as $v ){
                $v = explode('_',$v);
                $query_update = "UPDATE collage_group_products_t SET price='".$v[1]."',stock='".$v[2]."',number='".$v[3]."' ,open_day='".$v[4]."'  ,sort='".$v[5]."',success_num='".$v[6]."',alone_onoff='".$v[7]."' WHERE activitie_id=".$activitie_id." AND pid=".$v[0]." AND isvalid=true";
                _mysql_query($query_update);
            }
            $error = mysql_error();
            if( $error ){
                $list['code'] = 40006;
                $list['content'] = '保存产品失败';
                return $list;
            }
        }

        return $list;
    }

    /*
     * 更新活动产品数据
     * @param  array  $conditions  搜索条件数组
     * @param  string  $values  更新数据数组
     */
    public function update_group_products($conditions=null,$values=null){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';

        $condition_new = "";
        $value_new = "";

        foreach( $conditions as $k => $v ){
            $condition_new .= ' AND '.$k.'='.$v;
        }
        $condition_new = substr($condition_new,4);

        foreach( $values as $k => $v ){
            $value_new .= $k.'='.$v.',';
        }
        $value_new = substr($value_new,0,-1);

        $query = "UPDATE collage_group_products_t SET ".$value_new." WHERE ".$condition_new;
        _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        }

        return $list;
    }

    /*
     * 获取团员订单列表
     * @param  string  $condition  搜索条件字符串
     * @param  string  $filed  查找字段字符串
     */
    public function get_crew_order($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['content'] = '获取订单成功';

        $query = "SELECT ".$filed." FROM collage_crew_order_t AS ccot LEFT JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccot.batchcode=ccopmt.batchcode LEFT JOIN weixin_commonshop_order_addresses AS wcoa ON ccot.batchcode=wcoa.batchcode LEFT JOIN weixin_users AS wu ON ccot.user_id=wu.id INNER JOIN collage_group_order_t AS cgot ON ccot.group_id=cgot.id left join weixin_commonshop_orders as wco on wco.batchcode=ccot.batchcode WHERE ".$condition;

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['content'] = '获取订单失败';
            return $list;
        }
        while( $row = mysql_fetch_assoc($result) ){
            $list['batchcode'][] = $row;
        }

        return $list;
    }
    /*
     * 新获取团员订单列表（定时任务）
     * @param  string  $condition  搜索条件字符串
     * @param  string  $filed  查找字段字符串
     */
    public function new_get_crew_order($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['content'] = '获取订单成功';

        $query = "SELECT ".$filed." FROM collage_crew_order_t AS ccot LEFT JOIN collage_activities_t AS cat on cat.id = ccot.activitie_id LEFT JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccot.batchcode=ccopmt.batchcode LEFT JOIN weixin_commonshop_order_addresses AS wcoa ON ccot.batchcode=wcoa.batchcode LEFT JOIN weixin_users AS wu ON ccot.user_id=wu.id INNER JOIN collage_group_order_t AS cgot ON ccot.group_id=cgot.id left join weixin_commonshop_orders as wco on wco.batchcode=ccot.batchcode WHERE ".$condition;

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['content'] = '获取订单失败';
            return $list;
        }
        while( $row = mysql_fetch_assoc($result) ){
            $list['batchcode'][] = $row;
        }

        return $list;
    }

    /*
     * 获取拼团类型信息
     * @param  string  $condition  搜索条件字符串
     * @param  string  $filed  查找字段字符串
     */
    public function get_group_type($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['content'] = '获取团类型成功';

        $query = "SELECT ".$filed." FROM collage_activities_explain_t WHERE ".$condition;

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['content'] = '获取团类型失败';
            return $list;
        }
        while( $row = mysql_fetch_assoc($result) ){
            $list['data'][] = $row;
        }

        return $list;
    }

    /*
     * 获取产品，活动信息
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function select_front_group($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        foreach( $condition as $k => $v ){
            $condition_new .= " AND ".$k."=".$v;
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }

        $query = "SELECT ".$filed." FROM collage_group_order_t AS cgot
						LEFT JOIN collage_activities_t AS cat ON cgot.activitie_id=cat.id
						LEFT JOIN collage_group_products_t AS cgpt ON cgot.pid=cgpt.pid  AND cgot.activitie_id=cgpt.activitie_id
						LEFT JOIN weixin_commonshop_products AS wcp ON cgot.pid=wcp.id
                        LEFT JOIN collage_activities_explain_t AS ae ON ae.type=cgot.type
						WHERE ".$condition_new;
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error . " | " . $query;
        } else {
            $list['data'][] = mysql_fetch_assoc($result);
        }
        return $list;
    }

    /*
	 * 获取产品，活动信息
	 * @param  array  $condition  搜索条件字符串
	 * @param  string  $filed  查找字段字符串
	 */
    public function select_front_group2($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['content'] = '成功';

        $query = "SELECT ".$filed." FROM collage_group_order_t AS cgot
                    LEFT JOIN collage_activities_t AS cat ON cgot.activitie_id=cat.id
                    LEFT JOIN collage_group_products_t AS cgpt ON cgot.pid=cgpt.pid  AND cgot.activitie_id=cgpt.activitie_id
                    LEFT JOIN weixin_commonshop_products AS wcp ON cgot.pid=wcp.id
                    WHERE ".$condition;

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['content'] = $error;
            return $list;
        } else {
            $list['data'][] = mysql_fetch_assoc($result);
        }

        return $list;
    }

    /*
	 * 获取团订单信息
	 * @param  array  $condition  搜索条件字符串
	 * @param  string  $filed  查找字段字符串
	 */
    public function select_count_crew_order($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['content'] = '成功';

        $query = "SELECT ".$filed." FROM collage_crew_order_t AS ccot
                    LEFT JOIN collage_group_order_t AS cgot ON cgot.id=ccot.group_id
                    WHERE ".$condition;

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['content'] = $error;
            return $list;
        } else {
            $list['data'][] = mysql_fetch_assoc($result);
        }

        return $list;
    }

    /*
     * 参团人员信息
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function select_front_crew($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        foreach( $condition as $k => $v ){
            if( $k == 'ccot.status' ){
                $condition_new .= " AND (".$k."=".$v[0]." OR ".$k."=".$v[1]." OR (".$k."=".$v[2]." AND UNIX_TIMESTAMP(ccot.paytime) > 0) OR ".$k."=".$v[3]." OR ".$k."=".$v[4]." OR ".$k."=".$v[5].")";
                continue;
            }
            $condition_new .= " AND ".$k."=".$v;
        }
        if( $condition_new != '' ){
            $condition_new .= " AND ccot.is_refund=0";
            $condition_new = substr($condition_new,4);
        }

        $query = "SELECT ".$filed." FROM collage_crew_order_t AS ccot
					LEFT JOIN weixin_users AS wu ON ccot.user_id=wu.id
					WHERE ".$condition_new." AND UNIX_TIMESTAMP(paytime)>0";

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while( $info = mysql_fetch_assoc($result) ){
                $list['data'][] = $info;
            }
        }
        return $list;
    }

    /*
    * 参团人员信息_new
    * @param  array  $condition  搜索条件数组
    * @param  string  $filed  查找字段字符串
    */
    public function select_front_crew_new($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        foreach( $condition as $k => $v ){
            if( $k == 'ccot.status' ){
                $condition_new .= " AND (".$k."=".$v[0]." OR ".$k."=".$v[1]." OR (".$k."=".$v[2]." AND UNIX_TIMESTAMP(ccot.paytime) > 0) OR ".$k."=".$v[3]." OR ".$k."=".$v[4]." OR ".$k."=".$v[5].")";
                continue;
            }
            $condition_new .= " AND ".$k."=".$v;
        }
        if( $condition_new != '' ){
            $condition_new .= " AND ccot.is_refund=0";
            $condition_new = substr($condition_new,4);
        }

        $query = "SELECT ".$filed." FROM collage_crew_order_t AS ccot
					LEFT JOIN weixin_users AS wu ON ccot.user_id=wu.id
					WHERE ".$condition_new." AND UNIX_TIMESTAMP(paytime)>0";

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while( $info = mysql_fetch_assoc($result) ){
                $list['data'][] = $info;
            }
        }
        return $list;
    }

    /*
     * 获取推荐的团
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function get_group_recommendation($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_order_by = '';
        $condition_limit = '';
        foreach( $condition as $k => $v ){
            if( $k == 'cgot.type' ){
                $condition_type = " AND (";
                $condition_types = '';
                foreach( $v as $val ){
                    if( $val != '' ){
                        $condition_types .= " OR ".$k."=".$val;
                    }
                }
                $condition_types = substr($condition_types,3);
                if( $condition_types == '' ){
                    $condition_type = "";
                } else {
                    $condition_type .= $condition_types." ) ";
                }
                $condition_new .= $condition_type;
                continue;
            }
            if( $k == 'cgot.endtime' ){
                $condition_new .= " AND ".$v;
                continue;
            }
            if( $k == 'cat.end_time' ){
				$condition_new .= " AND ".$v;
				continue;
			}
            if( $k == 'order_by' ){
                $condition_order_by = $v;
                continue;
            }
            if( $k == 'limit' ){
                $condition_limit = $v;
                continue;
            }
            $condition_new .= " AND ".$k."=".$v;
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        if( $condition_order_by != '' ){
            $condition_new .= $condition_order_by;
        }
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }

        $query = "SELECT ".$filed." FROM collage_group_order_t AS cgot
						LEFT JOIN weixin_users AS wu ON cgot.head_id=wu.id
                        LEFT JOIN collage_activities_t AS cat ON cgot.activitie_id=cat.id
						WHERE ".$condition_new;
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while( $info = mysql_fetch_assoc($result) ){
                $list['data'][] = $info;
            }
        }
        return $list;
    }

    /*
     * 获取我的拼团订单数据
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function get_user_crew_order($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' && $k != 'start_time' && $k != 'end_time' && $k != 'status' && $k != 'cot.paystyle'  && $k != 'cot.status1' && $k != 'cot.status2' && $k != 'status2' && $k != 'paystyle' && $k != 'cot.status_in' && $k !='wco.status_1' && $k !='cso.recovery_time'){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }
            if( $k == 'LIMIT' ){
                $condition_limit .= $k.' '.$v;
                continue;
            }
            if( $k == 'start_time' ){
                $condition_new .= " AND cot.createtime>='".$v."'";
                continue;
            }
            if( $k == 'end_time' ){
                $condition_new .= " AND cot.createtime<='".$v."'";
                continue;
            }
            if( $k == 'status' ){
                $condition_new .= " AND (got.status!=-1 OR ( got.status =-1 AND cso.is_collageActivities = 1 ))";
                continue;
            }
            if( $k == 'status2' ){
                $condition_new .= " AND got.status in (3,4,5,6)";
                continue;
            }
            if( $k == 'cot.status1' ){
                $condition_new .= " AND cot.status!=6 AND cot.status!=3";
                continue;
            }
            if( $k == 'cot.status2' ){
                $condition_new .= " AND cot.status!=6";
                continue;
            }if( $k == 'cot.status_in' ){
                $condition_new .= " AND cot.status in (".$v.")";
                continue;
            }
            if( $k == 'cot.paystyle' ){
                $condition_new .= " AND cot.paystyle!=-1";
                continue;
            }
            if( $k == 'wco.status_1'){
                $condition_new .= " AND wco.status != -1";
                continue;
            }if( $k == 'cso.recovery_time'){
                $condition_new .= " AND cso.recovery_time > now() ";
                continue;
            }
            if( $k == 'paystyle' ){
                $condition_new .= " AND (got.status!=2 or (cot.paystyle!=-1 and got.status=2))";
                continue;
            }

        }

        $condition_new .= " AND (wco.sendstatus !=6)";       //已经退款了的团不显示在我的拼团记录

        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        $condition_new .= ' ORDER BY cot.createtime DESC ';
        if( $condition_limit != '' ){
            $condition_new .= $condition_limit;
        }
        $list['condition_new']=$condition_new;

        $query = "SELECT ".$filed." FROM collage_crew_order_t AS cot
					INNER JOIN collage_group_order_t AS got ON got.id=cot.group_id
					INNER JOIN weixin_commonshop_products AS cp ON cp.id=got.pid
					INNER JOIN weixin_commonshop_orders  AS wco ON cot.batchcode = wco.batchcode
					LEFT JOIN weixin_users AS wu ON wu.id=got.head_id
                    LEFT JOIN collage_activities_explain_t AS ae ON ae.type=got.type
                    LEFT JOIN stockrecovery_t AS cso ON cot.batchcode = cso.batchcode
					WHERE ".$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row=mysql_fetch_array($result)){
                $list['data'][] = $row;
            }
        }

        return $list;
    }

    /*
     * 获取产品，活动信息
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function get_front_group($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';
        $condition_limit = '';
        $condition_order = '';
        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' && $k != 'cat.end_time' && $k != 'cat.start_time' && $k != 'cgpt.stock' && $k != 'ORDER' && $k != 'wcp.name' && $k != 'no_baobao' && $k != 'is_mini_mshop'){
                $condition_new .= " AND ".$k."=".$v;
                continue;
            }
            if( $k == 'wcp.name' ){
                $condition_new .= ' AND wcp.name '.$v;
                continue;
            }
            if( $k == 'cat.end_time' ){
                $condition_new .= "  AND cat.end_time>='".$v."'";
                continue;
            }
            if( $k == 'cat.start_time' ){
                $condition_new .= "  AND cat.start_time<='".$v."'";
                continue;
            }
            if( $k == 'cgpt.stock' ){
                $condition_new .= "  AND cgpt.stock>0";
                continue;
            }
            if ($k == "no_baobao"){
                $condition_new .= " AND cat.type != 5 ";
                continue;
            }
            if ($k == "is_mini_mshop"){
                $condition_new .= " AND wcp.is_mini_mshop = true ";
                continue;
            }
            if( $k == 'LIMIT' ){
                $condition_limit .= "  ".$k." ".$v;
                continue;
            }
            if( $k == 'ORDER' ){
                $condition_order .= $v;
                continue;
            }
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }

        $query = "SELECT ".$filed." FROM collage_group_order_t AS cgot
						LEFT JOIN collage_activities_t AS cat ON cgot.activitie_id=cat.id
						LEFT JOIN collage_activities_explain_t AS ae ON ae.type=cat.type
						LEFT JOIN collage_group_products_t AS cgpt ON cgot.pid=cgpt.pid  AND cgot.activitie_id=cgpt.activitie_id
						LEFT JOIN weixin_commonshop_products AS wcp ON cgot.pid=wcp.id
						WHERE ".$condition_new;

        if( $condition_order != '' ){
            $query .= $condition_order;
        }else{
            $query .= ' order by cat.createtime DESC';
        }
        if( $condition_limit != '' ){
            $query .= $condition_limit;
        }
        /*echo "<pre>";
        print_r($query);exit;*/

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row =mysql_fetch_assoc($result) ){

                $list['data'][] = $row;
            }

        }
        return $list;
    }

    /*
     * 获取团购产品，活动分类信息
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function get_front_group_type($customer_id){
        $type_query='SELECT id,name,parent_id From weixin_commonshop_types where isvalid = 1 AND is_shelves =1 AND parent_id = -1 AND customer_id='.$customer_id;
        $type_result = _mysql_query($type_query);
        $error       = mysql_error();
        $first_type  = array();
        $first_type_id_str='';
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row =mysql_fetch_array($type_result) ){
                $first_type_id_str .=$row['id'].',';
                $list['first_type'][] = array('name'=>$row['name'],'id'=>$row['id']);
            }
        }
        if($first_type_id_str!= ''){
            $first_type_id_str= substr($first_type_id_str,0,strlen($first_type_id_str)-1);
            $sec_type_query   ='SELECT id,name,parent_id From weixin_commonshop_types where isvalid = 1 AND is_shelves =1 AND parent_id in ('.$first_type_id_str.') AND customer_id='.$customer_id;
            $sec_type_result  = _mysql_query($sec_type_query);
            $error            = mysql_error();
            if( $error ){
                $list['code'] = 40006;
                $list['errmsg'] = $error;
            }
            while($sec_row    =mysql_fetch_array($sec_type_result) ){
                foreach ($list['first_type'] as $key => $one) {
                    if($sec_row['parent_id'] == $one['id']){
                        $list['first_type'][$key]['son'][]=array('son_id'=>$sec_row['id'],'son_name'=>$sec_row['name']);
                        $list['first_type'][$key]['son_str'].=$sec_row['id'].',';
                    }
                }

            }
        }
        return $list;

    }


    /*
     * 随机抽奖
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function select_raffle_list($condition=null,$filed='*'){
        $list['code'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $query = "SELECT ".$filed."
			FROM collage_group_order_t AS ot
			INNER JOIN collage_activities_t AS at  ON at.id=ot.activitie_id
			LEFT JOIN weixin_users AS wu ON wu.id=ot.head_id WHERE";

        $condition_new = '';
        foreach( $condition as $k => $v ){
            if( $k != 'LIMIT' ){
                $condition_new .= " AND ".$k."=".$v;
            }
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        $query = $query.$condition_new;

        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while($row =mysql_fetch_array($result) ){
                $data[] = $row;
            }
        }
        $arr_rand = array();
        if( count($data) > $condition['LIMIT'] ){
            while(count($arr_rand)<$condition['LIMIT']){
                $arr_rand[]=rand(0,count($data)-1);
                $arr_rand=array_unique($arr_rand);
            }
            foreach($arr_rand as $key => $val){
                $list['data'][] = $data[$val];
            }
        }else{
            $list['data'] = $data;
        }
        return $list;

    }

    /*
     * 确认中奖名单
     * @param  array  $condition  搜索条件数组
     * @param  array  $values  更新数据数组
     */
    public function update_group_order($condition=null,$values=null){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';

        $condition_new = '';
        foreach($condition as $key => $val){
            if($key != 'id'){
                $condition_new .= ' AND '.$key.'='.$val;
                continue;
            }

            if($key == 'id'){
                $condition_new .= ' AND '.$key.$val;
                continue;
            }
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        $value_new = '';
        foreach($values as $key => $val){
            $value_new .= $key.'='.$val.',';
        }
        if( $value_new != '' ){
            $value_new = substr($value_new,0,strlen($value_new)-1);
        }
        $query = "UPDATE collage_group_order_t SET ".$value_new." WHERE ".$condition_new."";
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        }
        return $list;
    }
    /*
     * 修改订单状态
     * @param  array  $condition  搜索条件数组
     * @param  array  $values  更新数据数组
     */
    public function update_crew_order($condition=null,$values=null){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';

        $condition_new = '';
        foreach($condition as $key => $val){
            if($key == 'group_id'){
                $condition_new .= ' AND '.$key.$val;
                continue;
            }

            if($key == 'batchcode'){
                $condition_new .= ' AND '.$key.$val;
                continue;
            }

            if($key != 'group_id'){
                $condition_new .= ' AND '.$key.'='.$val;
                continue;
            }
        }
        if( $condition_new != '' ){
            $condition_new = substr($condition_new,4);
        }
        $value_new = '';
        foreach($values as $key => $val){
            $value_new .= $key.'='.$val.',';
        }
        if( $value_new != '' ){
            $value_new = substr($value_new,0,strlen($value_new)-1);
        }
        $query = "UPDATE collage_crew_order_t SET ".$value_new." WHERE ".$condition_new."";
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['code'] = 40006;
            $list['errmsg'] = $error;
        }
        return $list;
    }

    /*
     * 插入拼团开团订单
     * @param  array  $values  保存数据数组
     */
    public function insert_group_order($values){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = '';

        $filed = '';
        $value = '';

        foreach( $values as $k => $v ){
            $filed .= $k.',';
            // if($k=='createtime' || $k=='endtime'){
               $v = trim($v,"'");//部分代码调用时会加'，拼接前先过滤一次
               $value .= "'".$v."',"; 
            // }else{
            //    $value .= $v.",";
            // }
        }

        $filed = substr($filed,0,-1);
        $value = substr($value,0,-1);

        $query = "INSERT INTO collage_group_order_t (".$filed.") VALUES (".$value.")";
        _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        } else {
            $list['data'] = mysql_insert_id();
        }

        return $list;
    }

    /*
     * 插入拼团团员订单
     * @param  array  $values  保存数据数组
     */
    public function insert_crew_order($values){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = '';

        $filed = '';
        $value = '';

        foreach( $values as $k => $v ){
            $filed .= $k.',';
            if($k=='createtime'){
               $value .= "'".$v."',"; 
            }else{
               $value .= $v.",";
            }
        }

        $filed = substr($filed,0,-1);
        $value = substr($value,0,-1);

        $query = "INSERT INTO collage_crew_order_t (".$filed.") VALUES (".$value.")";
        _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        } else {
            $list['data'] = mysql_insert_id();
        }

        return $list;
    }

    /*
     * 插入拼团订单产品信息
     * @param  array  $values  保存数据数组
     */
    public function insert_pro_mes($values){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = '';

        $filed = '';
        $value = '';

        foreach( $values as $k => $v ){
            $filed .= $k.',';
            $v = trim($v,"'");//部分代码调用时会加'，拼接前先过滤一次
            $value .= "'".$v."',";
        }

        $filed = substr($filed,0,-1);
        $value = substr($value,0,-1);

        $query = "INSERT INTO collage_crew_order_pro_mes_t (".$filed.") VALUES (".$value.")";
        _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        } else {
            $list['data'] = mysql_insert_id();
        }

        return $list;
    }

    /*
     * 插入拼团用户信息
     * @param  array  $values  保存数据数组
     */
    public function select_activities_user($condition=array(),$filed='*'){
        $list['errcode'] = 0;
        $list['errmsg'] = '';
        $list['data'] = [];

        $condition_new = '';

        foreach( $condition as $k => $v ){
            $condition_new .= " AND ".$k."=".$v;
        }

        $condition_new = substr($condition_new,4);

        $query = "SELECT ".$filed." FROM collage_activities_user_mes_t WHERE ".$condition_new;
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        } else {
            $list['data'] = mysql_fetch_assoc($result);
        }

        return $list;
    }

    /*
     * 插入拼团用户信息
     * @param  array  $values  保存数据数组
     */
    public function insert_activities_user($values){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = '';

        $filed = '';
        $value = '';

        foreach( $values as $k => $v ){
            $filed .= $k.',';
            $value .= $v.',';
        }

        $filed = substr($filed,0,-1);
        $value = substr($value,0,-1);

        $query = "INSERT INTO collage_activities_user_mes_t (".$filed.") VALUES (".$value.")";
        _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        } else {
            $list['data'] = mysql_insert_id();
        }

        return $list;
    }

    /*
     * 更新用户拼团信息
     * @param  array  $conditions  搜索条件数组
     * @param  array  $values  更新数据数组
     */
    public function update_activities_user($conditions=null,$values=null){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';

        $condition_new = '';
        $value_new = '';

        foreach( $conditions as $k => $v ){
            if( $k == 'user_id_in' ){
                $condition_new .= " AND user_id IN(".$v.") ";
                continue;
            }
            $condition_new .= ' AND '.$k.'='.$v;
        }

        $condition_new = substr($condition_new,4);

        foreach( $values as $k => $v ){
            $value_new .= $k.'='.$v.',';
        }

        $value_new = substr($value_new,0,-1);

        $query = "UPDATE collage_activities_user_mes_t SET ".$value_new." WHERE ".$condition_new;
        _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        }
        return $list;
    }

    /*
     * 获取已支付订单的信息，用于推送消息
     * @param  array  $condition  搜索条件数组
     * @param  string  $filed  查找字段字符串
     */
    public function get_pay_batchcode_info($condition=null,$filed='*'){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';

        foreach( $condition as $k => $v ){
            if( $k == 'group_id_in' ){
                $condition_new .= " AND ccot.group_id IN (".$v.") ";
                continue;
            }
            if( $k == 'status_in' ){
                $condition_new .= " AND ccot.status IN (".$v.") ";
                continue;
            }
            $condition_new .= ' AND '.$k.'='.$v;
        }

        $condition_new = substr($condition_new,4);

        $query = "SELECT ".$filed." FROM collage_crew_order_t AS ccot
					LEFT JOIN collage_group_order_t AS cgot ON ccot.group_id=cgot.id
					LEFT JOIN weixin_users AS wu ON ccot.user_id=wu.id
					LEFT JOIN collage_crew_order_pro_mes_t AS ccopmt ON ccot.batchcode=ccopmt.batchcode
					WHERE ".$condition_new;
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while( $row = mysql_fetch_assoc($result) ){
                $list['data'][] = $row;
            }
        }
        return $list;
    }

    /**
     * 获取付款中的用户
     * @param  array $condition 搜索条件数组
     * @param  string $filed 查找字段字符串
     *
     */
    public function get_paying_user($condition,$filed){
        $list['errcode'] = 0;
        $list['errmsg'] = '成功';
        $list['data'] = [];

        $condition_new = '';

        foreach( $condition as $k => $v ){
            $condition_new .= ' AND '.$k.'='.$v;
        }

        $condition_new = substr($condition_new,4);

        $query = "SELECT ".$filed." FROM collage_save_order_log AS csol
						LEFT JOIN weixin_users AS wu ON csol.user_id=wu.id
						WHERE ".$condition_new;
        $result = _mysql_query($query);
        $error = mysql_error();
        if( $error ){
            $list['errcode'] = 40006;
            $list['errmsg'] = $error;
        } else {
            while( $row = mysql_fetch_assoc($result) ){
                $row['paying'] = 1;
                $list['data'][] = $row;
            }
        }
        return $list;
    }

    public function wlog($content,$type=1){
        error_log(var_export($content,1),3,"/opt/www/weixin_platform/debug.txt");
        error_log(var_export('---',1),3,"/opt/www/weixin_platform/debug.txt");
        if($type!=1){
            exit;
        }

    }
    /**
     * 更改拼团订单状态方法
     * @param  int $customer_id 商家编号
     * @param  int $pay_batchcode   支付订单号
     * @param  int $paytype 支付方式
     */
    public function update_pay_crew_order($customer_id,$pay_batchcode,$paytype){
        $this->wlog('update_pay_crew_order');
        $this->utlity = new shopMessage_Utlity();
        $this->shop = new shop();

        $log_name = LocalBaseURL . "log/collage_order_" . date('Ymd') . ".log";
        $is_send_order = 0;		//是否已派单

        $is_collageActivities = 0;
        $query = "SELECT is_collageActivities,batchcode,is_QR,paystatus FROM weixin_commonshop_orders WHERE customer_id=".$customer_id." AND pay_batchcode='".$pay_batchcode."' AND isvalid=true GROUP BY batchcode";
        $result = _mysql_query($query) or die('Query failed1:'.mysql_error());
        while( $row = mysql_fetch_assoc($result) ){
            $batchcode = $row['batchcode'];
            $is_collageActivities = $row['is_collageActivities'];
            $is_QR = $row['is_QR'];
            $paystatus = $row['paystatus'];
        }

        if( $is_collageActivities == 0 ){	//非拼团订单不执行下面代码
            $res = array(
                'is_collageActivities' => $is_collageActivities,
                'group_status' => false
            );
            return $res;
        }

        $condition = " ccot.customer_id=".$customer_id." AND ccot.batchcode='".$batchcode."' AND ccot.isvalid=true ";
        $filed = " ccot.group_id,ccot.id,ccot.is_head,ccot.group_id,ccot.activitie_id,ccot.user_id,ccopmt.pid,cgot.type ";
        $crew_order = $this->get_crew_order($condition,$filed)['batchcode'][0];

        if( !empty($crew_order) ){
            $group_status = $this->check_group_status($customer_id,$crew_order['group_id']);
            $group_log = $this->check_group_log($crew_order['group_id'],$customer_id);


            if( !$group_status && !$group_log ){
                //如果已经拼团成功，则该订单为待退款状态
                $condition = array(
                    'customer_id' => $customer_id,
                    'id' => $crew_order['id']
                );
                $value = array(
                    'status' => 3,
                    'paytime' => 'now()',
                    'paystyle' => "'".$paytype."'"
                );
                $this->update_crew_order($condition,$value);


                //订单状态改为已取消
                if($batchcode != ""){
                    $condition = array(
                        'batchcode' => "'".$batchcode."'",
                        'customer_id' => $customer_id
                    );

                    /*$log_name=$_SERVER['DOCUMENT_ROOT']."/weixinpl/log/errstatus_".date("Ymd").".log";
                    $log="2098未支付订单更新成-1，data: ".json_encode($condition)."\r\n时间:".date("Y-m-d H:i:s")."\r\n\r\n";
                    file_put_contents($log_name,$log,FILE_APPEND);*/

                    $value = array(
                        'status' => -1
                    );
                    $this->shop->update_order($condition,$value);

                    $condition = array(
                        'batchcode' => "'".$batchcode."'",
                        'customer_id' => $customer_id
                    );
                    $value = array(
                        'status' => -1
                    );
                    $this->shop->update_order_price($condition,$value);
                }

                $res = array(
                    'is_collageActivities' => $is_collageActivities,
                    'group_status' => false
                );
                return $res;
            }

            //更新团员订单状态
            $condition = array(
                'customer_id' => $customer_id,
                'id' => $crew_order['id']
            );
            $value = array(
                'status' => 2,
                'paytime' => 'now()',
                'paystyle' => "'".$paytype."'"
            );
            $this->update_crew_order($condition,$value);

            //删除下单支付记录，释放资格
            $query = "DELETE FROM collage_save_order_log WHERE user_id=".$crew_order['user_id']." AND group_id=".$crew_order['group_id']." AND batchcode='".$batchcode."'";
            _mysql_query($query);

            //获取用户拼团信息
            $condition = array(
                'customer_id' => $customer_id,
                'isvalid' => true,
                'user_id' => $crew_order['user_id']
            );
            $filed = " id ";
            $activity_user_id = $this->select_activities_user($condition,$filed)['data']['id'];

            //不存在数据则插入新数据
            if( empty($activity_user_id) ){
                $value = array(
                    'customer_id' => $customer_id,
                    'isvalid' => true,
                    'createtime' => 'now()',
                    'user_id' => $crew_order['user_id']
                );
                $activity_user_id = $this->insert_activities_user($value)['data'];
            }

            //如果是团长的话则更改团的状态为进行中
            if( $crew_order['is_head'] == 1 ){
                $condition = array(
                    'customer_id' => $customer_id,
                    'id' => ' in ('.$crew_order['group_id'].')'
                );
                $value = array(
                    'status' => 1
                );
                $this->update_group_order($condition,$value);

                //更新拼团活动产品
                $condition = array(
                    'activitie_id' => $crew_order['activitie_id'],
                    'isvalid' => true,
                    'pid' => $crew_order['pid']
                );
                $value = array(
                    'total_open' => 'total_open+1',
                    'total_conduct' => 'total_conduct+1'
                );
                $this->update_group_products($condition,$value);

                //更新用户拼团信息，开团数+1
                $condition = array(
                    'id' => $activity_user_id
                );
                $value = array(
                    'total_open' => 'total_open+1'
                );
                $this->update_activities_user($condition,$value);
            } else {
                //更新用户拼团信息，参团数+1
                $condition = array(
                    'id' => $activity_user_id
                );
                $value = array(
                    'total_partakep' => 'total_partakep+1'
                );
                $this->update_activities_user($condition,$value);
            }

            $price = 0;
            $supply_id = -1;
            $query_order_price = "SELECT price,supply_id FROM weixin_commonshop_order_prices WHERE batchcode='".$batchcode."'";
            $result_order_price = _mysql_query($query_order_price);
            while( $row_order_price = mysql_fetch_object($result_order_price) ){
                $price = $row_order_price -> price;
                $supply_id = $row_order_price -> supply_id;
            }

            //获取供应商openid
            $supply_openid = '';
            if ( $supply_id > 0 ) {
                $query_supply = "SELECT weixin_fromuser FROM weixin_users WHERE id=".$supply_id." AND isvalid=true";
                $result_supply = _mysql_query($query_supply);
                while ( $row_supply = mysql_fetch_object($result_supply) ) {
                    $supply_openid = $row_supply -> weixin_fromuser;
                }
            }

            //参团人数加1和增加团总额
            $condition = array(
                'customer_id' => $customer_id,
                'id' => ' in ('.$crew_order['group_id'].')'
            );
            $value = array(
                'join_num' => 'join_num+1',
                'total_price' => 'total_price+'.$price
            );
            $this->update_group_order($condition,$value);

            //检测参团人数是否达到成团人数
            $condition = array(
                'cgot.customer_id' => $customer_id,
                'ae.customer_id' => $customer_id,
                'ae.isvalid' => true,
                'cgot.id' => $crew_order['group_id'],
                'cgot.isvalid' => true
            );
            $filed = ' cgot.type,cgot.success_num,cgot.join_num,cgot.head_id ';
            $group_info = $this->select_front_group($condition,$filed)['data'][0];
            $group_type = $group_info['type'];
            $group_success_num = $group_info['success_num'];
            $group_join_num = $group_info['join_num'];
            $group_head_id = $group_info['head_id'];

            //抱抱团开团记录系数
            if( $crew_order['is_head'] == 1 && $group_type==5){
                $param = array(
                    'customer_id' => $customer_id,
                    'activitie_id' => $crew_order['activitie_id'],
                    'user_id' => $crew_order['user_id'],
                    'group_id' => $crew_order['group_id']
                );

                $bbt_coefficient_result = $this->cal_cuddle_coefficient($param);
                if($bbt_coefficient_result["status"]>0){
                    $res = array(
                        'is_collageActivities' => $is_collageActivities,
                        'group_status' => false,
                    );
                    return $res;
                }

                //插入开团日志
                $param = array(
                    'customer_id' => $customer_id,
                    'activitie_id' => $crew_order['activitie_id'],
                    'group_type' => 5,//抱抱团
                    'return_type' => 1,//返购物币
                    'group_id' => $crew_order['group_id']
                );

                $open_group_result = $this -> cuddle_group_log($param);
                if($open_group_result["status"]>0){
                    $res = array(
                        'is_collageActivities' => $is_collageActivities,
                        'group_status' => false,
                    );
                    return $res;
                }
            }

            //插入订单记录
            if($group_type == 5){
                $param = array(
                    'customer_id' => $customer_id,
                    'user_id' => $crew_order['user_id'],
                    'batchcode' => "'".$batchcode."'",
                    'price' => $price,
                    'group_id' => $crew_order['group_id']
                );

                $orderlog_result = $this -> cuddle_order_log($param);
                if($open_group_result["status"]>0){
                    $res = array(
                        'is_collageActivities' => $is_collageActivities,
                        'group_status' => false,
                    );
                    return $res;
                }
            }

            //如果是免单团
            if( $group_type == 6 ){
                //将订单修改为有效，即可以即时发货
                if($batchcode != ""){
                    $condition = array(
                        'batchcode' => "'".$batchcode."'",
                        'customer_id' => $customer_id
                    );
                    $value = array(
                        'is_collageActivities' => 1
                    );
                    $this->shop->update_order($condition,$value);
                }

                //如果不是团长，发送消息拼团成功
                if($crew_order['is_head'] == 2){

                    //获取已支付的订单的产品名、用户标识，用于推送消息
                    $condition = array(
                        'ccot.id' => $crew_order['id'],
                        'ccot.status' => 2,
                        'ccot.isvalid' => true
                    );
                    $filed2 = " ccopmt.pname,wu.weixin_fromuser,wu.id ";
                    $pay_bat_info = $this->get_pay_batchcode_info($condition,$filed2)['data'];



                    $content = "亲，您参加的 ".$pay_bat_info[0]['pname']."免单团 拼团成功\r\n".
                        "状态：【拼团成功】\n".
                        "时间：".date( "Y-m-d H:i:s")."";
                    $this->utlity->SendMessage($content,$pay_bat_info[0]['weixin_fromuser'],$customer_id);

                }
                //免单团一旦就购买 即是成功
                $this->free_group_success_add($crew_order['group_id'],$customer_id,$batchcode,$is_QR,$supply_id);
                //免单团直接派单
                // file_put_contents($log_name, "拼团派单-----paystatus=" . $paystatus . "\n\n", FILE_APPEND);
                $new_shop_pay = new new_shop_pay($customer_id);
                $send_order_fun = $new_shop_pay->send_order_fun($customer_id, $pay_batchcode);
                file_put_contents($log_name, "拼团派单-----" . var_export($send_order_fun, true) . "\n\n", FILE_APPEND);
                if ( $send_order_fun ) {
                $is_send_order = 1;
                }
            }
            //达到成团人数
            if( $group_join_num == $group_success_num || $group_log ){

                $this->collage_success_operate($customer_id,$crew_order['group_id']);

                $is_collageActivities = 1;

            }


        }
        $res = array(
            'is_collageActivities' => $is_collageActivities,
            'group_status' => true,
            'group_id' => $crew_order['group_id'],
            'group_type' => $group_type,
            'is_send_order' => $is_send_order
        );
        return $res;
    }

    //新抽奖逻辑
    public function lottery($group_id,$customer_id,$pname=''){
        $this->wlog("lottery_start");
        $utlity= new shopMessage_Utlity();
        $query = "SELECT users.weixin_fromuser,ccot.id,ccot.user_id,ccot.batchcode,ccot.group_id, ccot.activitie_id,clc.lottery_count FROM collage_crew_order_t ccot  left join wsy_user.weixin_users users on users.id = ccot.user_id left join wsy_mark.collage_lottery_count clc on ccot.user_id = clc.user_id WHERE ccot.customer_id=".$customer_id." AND ccot.group_id=".$group_id." AND ccot.isvalid=true and ccot.lottery_status = 2";
        $this->wlog($query);
        $result = _mysql_query($query) or die('Query failed:'.mysql_error());

        //获取指定的获奖用户id
        $default_lottery_user_id = 0;
        $query_default = "select id , lottery_user_id from wsy_mark.collage_group_order_t  where id = ".$group_id;
        $result_default = _mysql_query($query_default) or die("L31 query error : ".mysql_error());
        $default_lottery_user_id = mysql_fetch_assoc($result_default)['lottery_user_id'];

        $activitie_id = 0;
        $users = array();
        while ( $row = mysql_fetch_object($result) ) {
            if(!($row->lottery_count)){
                $row->lottery_count = 0;
            }
            $this->wlog($row->lottery_count);
            $users[] = array(
                'id' => $row -> id,
                'user_id' => $row -> user_id,
                'batchcode' => $row -> batchcode,
                'group_id' => $row -> group_id,
                'activitie_id' => $row -> activitie_id,
                'lottery_count' => $row -> lottery_count,
                'weixin_fromuser' => $row -> weixin_fromuser,
            );
            if($activitie_id==0){
                $activitie_id = $row -> activitie_id;
            }
        }
        $this->wlog($users);
        //选出抽奖最低的
        $min_lottery = $users[0]['lottery_count'];
        foreach($users as $ku=>$vu){
            if($vu['lottery_count'] < $min_lottery){
                $min_lottery = $vu['lottery_count'] ;
            }
        }
        $this->wlog($min_lottery);

        $to_be_choose_id = array();
        foreach ($users as $ku=>$vu){
            if($vu['user_id']==$default_lottery_user_id){
                $to_be_choose_id[] = $vu['id'];
                break;
            }
            if($vu['lottery_count'] == $min_lottery){
                $to_be_choose_id[] = $vu['id'];
            }
        }
        $this->wlog($to_be_choose_id);

        $id_count = count($to_be_choose_id);
        $num_id = rand(1,$id_count);

        $this->wlog('num_id');
        $this->wlog($num_id);

        $choose_id = $to_be_choose_id[$num_id-1];

        $this->wlog('choose_id');
        $this->wlog($choose_id);

        $choose_user = array();
        foreach($users as $ku=>$vu){
            if($vu['id']==$choose_id){
                $choose_user = $vu;
                $content = "恭喜您拼中".$pname."产品，产品正在快速向您飞奔而来！".
                    "时间：".date( "Y-m-d H:i:s")."";
                $utlity->SendMessage($content,$vu['weixin_fromuser'],$customer_id);
            }

        }
        $this->wlog($choose_user);

     /*   $user_num = count($users);
        $num = rand(1,$user_num);*/


        //修改collage_crew_order_t 状态
        $query_set_lottery_status_0 = "update collage_crew_order_t set lottery_status = 0 , status = 3 WHERE customer_id = ".$customer_id." AND group_id = ".$group_id." AND isvalid=true  and id != ".$choose_user['id'];
        $query_set_lottery_status_1 = "update collage_crew_order_t set lottery_status = 1 , status = 5 WHERE id = ".$choose_user['id'];
        _mysql_query($query_set_lottery_status_0);
        _mysql_query($query_set_lottery_status_1);
        $this->wlog($query_set_lottery_status_0);
        $this->wlog($query_set_lottery_status_1);

        //给未中奖的用户发钱
        $this->splitBonus($users,$choose_user,$activitie_id,$customer_id,$pname);
        //给中奖用户添加一次中奖记录
        $this->addLotteryRecode($choose_user,$customer_id);

        $this->wlog("lottery_end");
        return $choose_user;
    }

    //给未中奖的用户分钱
    public function splitBonus($users,$chooseUser,$activitie_id,$customer_id,$pname){
        $this->wlog('splitBonus--begin');
        $query = "SELECT id,luck_split_money FROM collage_activities_t WHERE id=".$activitie_id;
        $result = _mysql_query($query) or die('Query failed:'.mysql_error());
        $luck_split_money = mysql_fetch_assoc($result)['luck_split_money'];
        if($luck_split_money>0){
            $luck_split_money = intval($luck_split_money);
        }
        $this->wlog($luck_split_money);
        $luck_split_money_each = floatval($luck_split_money/(count($users)-1));
        $this->wlog($luck_split_money_each);
        $this->wlog($users);
        $utlity= new shopMessage_Utlity();
        foreach($users as $ku=>$vu){
            if($vu['user_id'] != $chooseUser['user_id']){
                $MoneyBag = new MoneyBag();
                $remark = "拼团成功后抽奖失败奖励零钱";
                $refund_result = $MoneyBag->update_moneybag($customer_id,$vu['user_id'],$luck_split_money_each,$vu['batchcode'],$remark,1,14,0);
               /* $sendMessage_content = "亲，您的零钱钱包 +".$luck_split_money_each."元\r\n".
                    "来源：【拼团成功后抽奖失败】\n".
                    "状态：【零钱到帐】\n".
                    "时间：".date( "Y-m-d H:i:s")."";*/
                $content = "很抱歉，您没有拼中".$pname."，厂家为表示歉意，补贴您".$luck_split_money_each."元，请在零钱中查看！".
                    "时间：".date( "Y-m-d H:i:s")."";
                $utlity->SendMessage($content,$vu['weixin_fromuser'],$customer_id);
            }
        }
        $this->wlog('splitBonus--end');
    }

    //给中奖用户新增中奖记录
    public function addLotteryRecode($chooseUser,$customer_id){
        $this->wlog("addLotteryRecode--begin");
        $query_add_lotter_log = "INSERT INTO wsy_mark.collage_lottery_recode ( `user_id`,`group_id`,`batchcode`,`customer_id`,`createtime`) VALUES (".$chooseUser['user_id'].",".$chooseUser['group_id'].",'".$chooseUser['batchcode']."',".$customer_id.",now())";
     //   $query = "INSERT INTO `collage_lottery_recode` ( `user_id`, `group_id`, `batchcode`, `customer_id`, `createtime` ) VALUES ( 100329, 58, '10032915771526488060', 41, now( ) )";
        $this->wlog($query_add_lotter_log);
        _mysql_query($query_add_lotter_log) or die('Query_order_log failed:'.mysql_error());

        //先查看该用户有没有中奖次数,没有的话,插入数据,有的话,数据+1
        $query_lottery_count_info = "SELECT id,user_id,lottery_count FROM wsy_mark.collage_lottery_count WHERE user_id=".$chooseUser['user_id'];
        $result_lottery_count_info = _mysql_query($query_lottery_count_info) or die('Query failed:'.mysql_error());
        $old_lotter_count = mysql_fetch_assoc($result_lottery_count_info)['lottery_count'];
        if($old_lotter_count){
            $this->wlog("qq");
            $this->wlog($old_lotter_count);
            $query_update_lotter_count = "update wsy_mark.collage_lottery_count set lottery_count = lottery_count+1  WHERE user_id = ".$chooseUser['user_id'];
            _mysql_query($query_update_lotter_count);
        }else{
            $this->wlog("ww");
            $new_lottery_count = 1;
            $query_add_lotter_count = "INSERT INTO wsy_mark.collage_lottery_count ( `user_id`,`lottery_count`,`customer_id`) VALUES (".$chooseUser['user_id'].",".$new_lottery_count.",".$customer_id.")";
            $this->wlog($query_add_lotter_count);
            _mysql_query($query_add_lotter_count);
        }
       // _mysql_query($query);
        $this->wlog("addLotteryRecode--end");
    }


    /**
     * 支付检测团状态
     * @param  int $customer_id 商家编号
     * @param  int $group_id   团id
     */
    public function check_group_status($customer_id,$group_id){
        $query = "SELECT status,success_num,join_num FROM collage_group_order_t WHERE customer_id=".$customer_id." AND id=".$group_id." AND isvalid=true";
        $this->wlog($query);
        $result = _mysql_query($query) or die('Query failed:'.mysql_error());
        $group_info = mysql_fetch_assoc($result);
        $this->wlog($group_info);
        $this->wlog($group_info['status']);
        //团状态非进行和未支付，则返回false
        if( $group_info['status'] != 1 && $group_info['status'] != -1 ){
            return false;
        }

        //参团人数等于成团人数，则返回false
        if($group_info['success_num'] == $group_info['join_num'] ){
            return false;
        }
     //   exit;

        return true;
    }
    /**
     * 拼团待退款订单集合
     * @param  int $customer_id 商家编号
     * @param  int $group_id   团id
     */
    public function check_order_refundable_arr($group_id,$customer_id){
        $query = "SELECT batchcode,paystyle FROM collage_crew_order_t WHERE customer_id=".$customer_id." AND group_id=".$group_id." AND isvalid=true AND is_refund=false AND status=3";
        $result = _mysql_query($query) or die('Query failed:'.mysql_error());
        while ( $row = mysql_fetch_object($result) ) {
            $batchcode_arr[] = array(
                'batchcode' => $row -> batchcode,
                'paystyle'	=> $row -> paystyle
            );
        }
        return $batchcode_arr;
    }

    /**
     * 拼团强制成功未支付人数统计
     * @param  int $customer_id 商家编号
     * @param  int $group_id   团id
     */
    public function select_census($group_id,$customer_id){
        $query = "SELECT count(id) as census FROM collage_save_order_log WHERE customer_id=".$customer_id." AND group_id=".$group_id." AND isvalid=true";
        $result = _mysql_query($query) or die('Query2431 failed:'.mysql_error());
        while ( $row = mysql_fetch_object($result) ) {
            $census = $row -> census;
        }

        return $census;
    }

    /**
     * 拼团待退款人数统计
     * @param  int $customer_id 商家编号
     * @param  int $group_id   团id
     */
    public function check_order_refundable($group_id,$customer_id){
        $query = "SELECT count(id) as rcount FROM collage_crew_order_t WHERE customer_id=".$customer_id." AND group_id=".$group_id." AND isvalid=true AND is_refund=false AND status=3";
        $result = _mysql_query($query) or die('Query failed:'.mysql_error());
        while ( $row = mysql_fetch_object($result) ) {
            $refundable_num = $row -> rcount;
        }

        return $refundable_num;
    }

    /**
     * 拼团退款成功人数统计
     * @param  int $customer_id 商家编号
     * @param  int $group_id   团id
     */
    public function check_order_refund($group_id,$customer_id){
        $query = "SELECT count(id) as fcount FROM collage_crew_order_t WHERE customer_id=".$customer_id." AND group_id=".$group_id." AND isvalid=true AND is_refund=false AND status=6";
        $result = _mysql_query($query) or die('Query failed:'.mysql_error());
        while ( $row = mysql_fetch_object($result) ) {
            $refund_num = $row -> fcount;
        }

        return $refund_num;
    }

    /**
     * 查找团退款操作记录
     * @param  int $customer_id 商家编号
     * @param  int $group_id   团id
     */
    public function check_group_log($group_id,$customer_id){
        $query = "SELECT id FROM collage_group_operation_log WHERE customer_id=".$customer_id." AND group_id=".$group_id." AND isvalid=true AND type=2 limit 1";
        $result = _mysql_query($query) or die('Query failed:'.mysql_error());
        $is_operation = mysql_fetch_assoc($result);
        if(!empty($is_operation)){
            return true;
        }else{
            return false;
        }

    }
    /**
     * 参团支付先检测资格
     * @param  int $customer_id 商家编号
     * @param  int $user_id   用户id
     * @param  int $group_id   团id
     * @param  string $batchcode   订单号
     */
    public function check_qualification($customer_id,$user_id,$group_id,$batchcode){
        _mysql_query('SET AUTOCOMMIT=0');
        _mysql_query('BEGIN');

        $query_group = "SELECT status,success_num,join_num FROM collage_group_order_t WHERE customer_id=".$customer_id." AND id=".$group_id." AND isvalid=true";
        $result_group = _mysql_query($query_group) or die('Query_group failed:'.mysql_error());
        $group_info = mysql_fetch_assoc($result_group);

        if( $group_info['status'] != 1 ){
            $json["status"] = 40006;
            $json["msg"] = '该团不在进行中';
            _mysql_query("COMMIT");
            _mysql_query('SET AUTOCOMMIT=1');
            return $json;
        }

        if( $group_info['success_num'] == $group_info['join_num'] ){
            $json["status"] = 40007;
            $json["msg"] = '该团已拼团成功，请选择其他团参加！';
            _mysql_query("COMMIT");
            _mysql_query('SET AUTOCOMMIT=1');
            return $json;
        }

        $query_order_user = "SELECT id FROM collage_save_order_log WHERE customer_id=".$customer_id." AND group_id=".$group_id." AND user_id=".$user_id." AND isvalid=true AND batchcode='".$batchcode."'";
        $result_order_user = _mysql_query($query_order_user) or die('Query_order_user failed:'.mysql_error());
        $log_id = mysql_fetch_assoc($result_order_user)['id'];

        if( $log_id > 0 ){

        } else {
            $query_order_log = "SELECT count(1) AS ocount FROM collage_save_order_log WHERE customer_id=".$customer_id." AND group_id=".$group_id." AND isvalid=true";
            $result_order_log = _mysql_query($query_order_log) or die('Query_order_log failed:'.mysql_error());
            $ocount = mysql_fetch_assoc($result_order_log)['ocount'];	//正在下单支付的人数

            if( $ocount+$group_info['join_num'] >= $group_info['success_num'] ){	//已支付人数+正在下单支付人数 >= 成团人数，则没有下单资格
                $json["status"] = 40008;
                $json["msg"] = '已经有人抢先下单支付，请稍后再操作！';
                _mysql_query("COMMIT");
                _mysql_query('SET AUTOCOMMIT=1');
                return $json;
            } else {
                $query_order_log_ins = "INSERT INTO collage_save_order_log (
												user_id,
												group_id,
												batchcode,
												customer_id,
												isvalid,
												createtime
											) VALUES (
												".$user_id.",
												".$group_id.",
												'".$batchcode."',
												".$customer_id.",
												true,
												now()
											)";
                _mysql_query($query_order_log_ins) or die('Query_order_log failed:'.mysql_error());
            }
        }

        _mysql_query("COMMIT");
        _mysql_query('SET AUTOCOMMIT=1');
        $json["status"] = 1;
        $json["msg"] = '';
        return $json;

    }

    /*
     *	团批量退款
     *	增加团退款状态：全部退款、部分退款、待退款（不增加字段保存团退款状态）
        团退款状态判断：统计拼团订单表collage_crew_order_t订单数量（非主动申请退款）
                            1、存在待退款订单，但不存在已退款订单，则为待退款
                            2、存在待退款订单和已退款订单，则为部分退款
                            3、存在已退款订单，但不存在待退款订单，则为全部退款
     *	@param	int		customer_id	商家id
     *	@param	int		group_id	团id
     */
    function refund_all($group_id,$group_status,$customer_id,$refund_way){
        //1.拼团失败的团

        //2.找出主动退款的订单

        //3.找出2以外的订单
        $batchcode_arr = $this->check_order_refundable_arr($group_id,$customer_id);

        //4.自动退款
        $param = array();
        $param['group_id'] = $group_id;
        $param['group_status'] = $group_status;
        $param['customer_id'] = $customer_id;
        $param['batchcode_arr'] = $batchcode_arr;
        $param['refund_way'] = $refund_way;

        $refund_result = $this->group_refund($param);

        //返回结果
        return $refund_result;

    }

    /*
     *	团批量退款
     *	增加团退款状态：全部退款、部分退款、待退款（不增加字段保存团退款状态）
        团退款状态判断：统计拼团订单表collage_crew_order_t订单数量（非主动申请退款）
                            1、存在待退款订单，但不存在已退款订单，则为待退款
                            2、存在待退款订单和已退款订单，则为部分退款
                            3、存在已退款订单，但不存在待退款订单，则为全部退款
     *	@param	int		customer_id	商家id
     *	@param	int		group_id	团id
     */
    function group_refund ($param = array()) {
        $result = array(
            'code'	=> 10000,
            'msg'	=> '团批量退款'
        );

        $customer_id 	= $param['customer_id'];
        $group_id 		= $param['group_id'];
        $group_status 		= $param['group_status'];
        $batchcode_arr 		= $param['batchcode_arr'];
        $refund_way 		= $param['refund_way'];


        //1、判断参数是否异常
        if ( empty($customer_id) || $customer_id < 0 ) {
            $result = array(
                'code'	=> 40000,
                'msg'	=> '商家id异常'
            );
            return $result;
        }
        if ( empty($group_id) || $group_id < 0 ) {
            $result = array(
                'code'	=> 40001,
                'msg'	=> '团id异常'
            );
            return $result;
        }

        //2、查询团状态，判断团状态是否拼团失败
        if ( $group_status != 2 ) {
            //非拼团失败
            $result = array(
                'code'	=> 40002,
                'msg'	=> '团状态异常'
            );
            return $result;
        }

        //3、获取拼团失败待退款的订单（非主动申请退款）
        if ( empty($batchcode_arr) ) {	//batchcode_arr订单数组
            $result = array(
                'code'	=> 10001,
                'msg'	=> '该团已全部退款，请勿重复操作'
            );
            return $result;
        }

        $bat_num = count($batchcode_arr);	//订单数量
        $success_num = 0;					//退款成功订单数量

        //4、遍历订单，调用退款接口
        foreach ( $batchcode_arr as $v ) {
            if ( $refund_way == 1 ) {
                switch ( $v['paystyle'] ) {
                    case '零钱支付':
                    case '会员卡余额支付':
                    case '微信支付':
                    case '环迅微信支付':
                    case '环迅快捷支付':
                    case '威富通支付':
                    case '健康钱包支付':
                        $refund_way_new = 1;
                        break;
                    default:
                        $refund_way_new = 2;
                        break;
                }
            } else {
                $refund_way_new = 2;
            }


            $post_data	= array(
                'batchcode' => $v['batchcode'],
                'customer_id' => $customer_id,
                'refund_way' => $refund_way_new
            );

            $post_data = http_build_query($post_data);
            $url = Protocol.$_SERVER['SERVER_NAME'].'/market/admin/MarkPro/collageActivities/order_refund.php?customer_id='.$customer_id;		//调用拼团退款
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);					// 要访问的地址
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1 );
            curl_setopt($ch, CURLOPT_HEADER, 0);					// 显示返回的Header区域内容
            curl_setopt($ch, CURLOPT_NOBODY, 0);					//只取body头
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);		// 对认证证书来源的检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);		// 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');	// 模拟用户使用的浏览器
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);			// 使用自动跳转
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);				// 自动设置Referer
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);		// Post提交的数据包
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);					// 设置超时限制防止死循环
            $curl_error = curl_error($ch);
            $json = curl_exec($ch);

            curl_close($ch);

            $jsons = json_decode($json, true);

            //退款成功则退款成功订单数量加1
            if ( $jsons['status'] == 1 ) {
                $success_num ++;
            }
        }

        //5、判断退款状态
        if ( $bat_num == $success_num ) {
            //退款成功订单数量等于总订单数量，则团退款状态为全部退款
            $result = array(
                'code'	=> 10001,
                'msg' 	=> '全部退款成功'
            );
        } else if ( $success_num > 0 ) {
            //退款成功订单数量大于零但不等于总订单数量，则团退款状态为部分退款
            $result = array(
                'code'	=> 10002,
                'msg' 	=> '部分退款成功'
            );
        } else {
            //总订单数量大于零但退款成功订单数量等于0，则退款失败
            $result = array(
                'code'	=> 40004,
                'msg' 	=> '退款失败'
            );
        }

        //返回结果
        return $result;
    }
    /**
     * 免单团拼团成功后,团长返还零钱
     * @param  int $customer_id 商家编号
     * @param  int $group_id    团id
     * @param  string $success_num  团成功人数
     */
    function free_group_cashback($customer_id,$group_id){
        //查询团长的id
        $condition = array(
            'cgot.customer_id' => $customer_id,
            'ae.customer_id' => $customer_id,
            'ae.isvalid' => true,
            'cgot.id'      => $group_id,
            'cgot.isvalid' => true,
            'cgot.type'    => 6
        );
        $filed = ' cgot.head_id,cgot.price,cgot.join_num ';
        $group_info = $this->select_front_group($condition,$filed)['data'][0];
        $condition = " ccot.customer_id=".$customer_id." AND ccot.group_id=".$group_id." AND ccot.is_head= 1 AND ccot.isvalid=true ";
        $filed = " ccot.batchcode,ccot.rcount ";
        $crew_order = $this->get_crew_order($condition,$filed)['batchcode'][0];

        //退实际支付金额
        $query_supply = "SELECT price FROM weixin_commonshop_order_prices WHERE user_id = ".$group_info['head_id']." AND batchcode = '{$crew_order['batchcode']}' AND isvalid=true";
        $price = 0;
        $result_supply = _mysql_query($query_supply);
        while ( $row_supply = mysql_fetch_object($result_supply) ) 
        {
            $price = $row_supply -> price;
        }
        $re_money   = $price;
        if(!empty($group_info) && !empty($crew_order)){
            $MoneyBag = new MoneyBag();
            $remark = "免单团拼团成功，团长返零钱";
            $refund_result = $MoneyBag->update_moneybag($customer_id,$group_info['head_id'],$re_money,$crew_order['batchcode'],$remark,1,26,0);
            if($refund_result['status'] == 1)
            {
                $this->insert_free_currency_log($customer_id,$group_id,$group_info['head_id'],$re_money,$crew_order['batchcode'],$group_info['join_num']);
            }
        }

    }

    /**
     * 免单团开团即成功
     * @param  int $customer_id 商家编号
     * @param  int $group_id    团id
     * @param  int $batchcode   订单号
     */
    function free_group_success_add($group_id,$customer_id,$batchcode,$is_QR=0,$supply_id=-1){
        //获取该团的用户id和订单号
        $condition = " ccot.group_id=".$group_id." AND ccot.customer_id=".$customer_id." AND ccot.batchcode = ".$batchcode." AND ccot.status=2 AND ccot.isvalid=true ";
        $filed = " ccot.user_id,ccot.batchcode,ccot.status ";
        $batchcode_info = $this->get_crew_order($condition,$filed)['batchcode'][0];
        if( !empty($batchcode_info) ){
            //更新用户拼团信息，成功团数+1
            $condition = array(
                'user_id' => $batchcode_info['user_id'],
                'customer_id' => $customer_id
            );
            $value = array(
                'total_success' => 'total_success+1'
            );
            $this->update_activities_user($condition,$value);
        }


        //如果是供应商产品，则发送消息
        //获取已支付的订单的产品名、用户标识，用于推送消息
        if($supply_id > 0)
        {

            $condition = array(
                'ccot.group_id' => $group_id,
                'ccot.status' => 2,
                'ccot.isvalid' => true
            );
            $filed2 = " ccopmt.pname,wu.weixin_fromuser,wu.id ";
            $pay_bat_info_p = $this->get_pay_batchcode_info($condition,$filed2)['data'];

            //获取供应商openid
            $supply_openid = '';
            $query_supply = "SELECT weixin_fromuser FROM weixin_users WHERE id=".$supply_id." AND isvalid=true";
            $result_supply = _mysql_query($query_supply);
            while ( $row_supply = mysql_fetch_object($result_supply) ) {
                $supply_openid = $row_supply -> weixin_fromuser;
            }

            $content = "亲，".$pay_bat_info_p[0]['pname']."免单团有顾客下单，请及时发货\r\n".
                "时间：".date( "Y-m-d H:i:s")."";
            $this->utlity->SendMessage($content,$supply_openid,$customer_id);
        }

        //如果是二维码产品，则立刻发货
        if($is_QR ==1 ){
            $fromuser = $weixin_fromuser[$user_id];

            //获取已支付的订单的产品名、用户标识，用于推送消息
            $condition = array(
                'ccot.group_id' => $group_id,
                'ccot.status' => 2,
                'ccot.isvalid' => true,
                'ccot.batchcode' => $batchcode
            );
            $filed = " ccopmt.pname,wu.weixin_fromuser,wu.id,ccot.is_head,ccot.user_id ";
            $pay_bat_info = $this->get_pay_batchcode_info($condition,$filed)['data'][0];
            $fromuser=$pay_bat_info['weixin_fromuser'];

            $this->utlity->GetQR($batchcode,$fromuser,$customer_id);

            $descript = "商家已发货";
            $query_log = "INSERT INTO weixin_commonshop_order_logs(batchcode,operation,descript,operation_user,createtime,isvalid) values('".$v."',4,'".$descript."','".$fromuser."',now(),1)";
            _mysql_query($query_log);

            //更改订单状态
            $query_order_up = "UPDATE weixin_commonshop_orders SET sendstatus=1 WHERE customer_id=".$customer_id." AND batchcode='".$batchcode."' AND isvalid=true";
            _mysql_query($query_order_up);

            $query_orderp_up = "UPDATE weixin_commonshop_order_prices SET sendstatus=1 WHERE customer_id=".$customer_id." AND batchcode='".$batchcode."' AND isvalid=true";
            _mysql_query($query_orderp_up);
        }


    }


    /*
     *	计算抱抱团系数（开团才调用）
     *
     */
    function cal_cuddle_coefficient ($param = array()) {
        //1、判断参数是否异常
        $customer_id = $param['customer_id'];
        $activitie_id = $param['activitie_id'];
        $user_id      = $param['user_id'];
        $group_id     = $param['group_id'];
        if($customer_id<0 ||$activitie_id<0 || $user_id<0 || $group_id<0){
            $result["status"] = 40012;
            $result["msg"] = '参数异常';
            return $result;
        }

        //2、获取该活动设置的系数
        $condition = array(
            'customer_id' => $customer_id,
            'isvalid' => true,
            'id' => $activitie_id
        );
        $field = " coefficient ";
        $activity_info = $this -> getActivitiesMes($condition,$field)['data'][0];
        $default_coefficient = $activity_info['coefficient'];

        //3、获取用户在该活动内开团次数
        $condition = " cgot.customer_id=".$customer_id." AND cgot.isvalid=true AND cgot.activitie_id=".$activitie_id." AND cgot.head_id=".$user_id." AND cgot.type=5 AND cgot.status>0";
        $filed = " count(cgot.id) as bbtopentimes";
        $activity_group_info = $this->select_front_group2($condition,$filed)['data'][0];
        $bbtopentimes = $activity_group_info['bbtopentimes'];

        //4、计算当前系数
        if($default_coefficient>0){
            $now_coefficient = $bbtopentimes%$default_coefficient;
            if($now_coefficient==0){
                $now_coefficient = $default_coefficient;
            }
        }else{
            $now_coefficient = 0;
        }

        //5、记录该团的系数
        $condition = array(
            'customer_id' => $customer_id,
            'id' => ' in ('.$group_id.')'
        );
        $value = array(
            'coefficient' => $now_coefficient
        );
        $this->update_group_order($condition,$value);

        //6、返回结果
        $result["status"] = 0;
        $result["msg"] = 'success';
        $result["coefficient"] = $now_coefficient;
        return $result;
    }

    /*
     *	抱抱团-计算返购物币数量
     *
     */
    function cal_back_currency ($param = array()) {
        //1、判断参数是否异常
        $customer_id = $param['customer_id'];
        $activitie_id = $param['activitie_id'];
        $group_id     = $param['group_id'];
        $price        = $param['price'];
        if($customer_id<0 ||$activitie_id<0 || $group_id<0 || $price<0){
            $result["status"] = 40013;
            $result["msg"] = '参数异常';
            return $result;
        }

        //2、获取团系数
        $condition = array(
            'cgot.id' => $group_id,
            'ae.isvalid' => true,
            'ae.customer_id' => $customer_id
        );
        $filed = " cgot.coefficient";
        $activity_group_info = $this->select_front_group($condition,$filed)['data'][0];
        $group_coefficient = $activity_group_info ['coefficient'];

        //3、获取系数对应的返购物币计算方式
        $condition = array(
            'customer_id' => $customer_id,
            'isvalid' => true,
            'id' => $activitie_id
        );
        $field = " coefficient,return_curr ";
        $activity_info = $this -> getActivitiesMes($condition,$field)['data'][0];
        $default_coefficient = $activity_info['coefficient'];
        $return_curr_arr = json_decode($activity_info['return_curr'],true);
        if($default_coefficient>0){
            foreach( $return_curr_arr as $key => $value ){
                if($value['collage_times']==$group_coefficient){
                    $return_type = $value['return_type'];
                    $return_value = $value['return_value'];
                }
            }
        }else{
            $return_type = 0;
            $return_value = 0;
        }

        //4、计算返购物币数量
        if($default_coefficient>0){
            if($return_type==1){
                $return_currency = $return_value*$price/100;
            }else{
                $return_currency = $return_value;
            }
        }else{
            $return_currency = 0;
        }

        //5、获取返购物币的用户
        $condition = " ccot.group_id=".$group_id." AND ccot.customer_id=".$customer_id." AND ccot.status=5 AND ccot.isvalid=true ";
        $filed = " ccot.user_id,ccot.batchcode ";
        $return_users = $this->get_crew_order($condition,$filed)['batchcode'];

        //6、返回结果
        $result["status"] = 0;
        $result["msg"] = 'success';
        $result["default_coefficient"] = $default_coefficient;
        $result["return_currency"] = $return_currency;
        $result["return_users"] = $return_users;
        return $result;
    }

    /**
     * 拼团成功后操作
     * @param  int $customer_id 	商家编号
     * @param  int $group_id    	拼团编号
     * @param  int $is_manual    	是否后台手动拼团成功 1是，0否
     */
    function collage_success_operate($customer_id, $group_id, $is_manual=0){
        $this->wlog("collage_success_operate");
        $utlity= new shopMessage_Utlity();
        $shop = new shop();

        if ( $is_manual ) {
            require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/function/handle_order_function.php");
        }

        $new_shop_pay = new new_shop_pay($customer_id);

        if($customer_id<0 || $group_id<0){
            $result["code"] = 40001;
            $result["msg"] = '参数异常';
            return $result;
        }

        $condition = array(
            'cgot.customer_id' => $customer_id,
            'ae.customer_id' => $customer_id,
            'ae.isvalid' => true,
            'cgot.id' => $group_id,
            'cgot.isvalid' => true
        );
        $filed = ' cgot.type,cgot.activitie_id,cgot.head_id,cgot.price,cgot.pid ';
        $group_info = $this->select_front_group($condition,$filed)['data'][0];


        //获取该团的用户id和订单号
        $condition = " ccot.group_id=".$group_id." AND ccot.customer_id=".$customer_id." AND (ccot.status=2 OR ccot.status=1) AND ccot.isvalid=true AND  ccot.is_head = 1";
        $filed = " ccot.user_id,ccot.batchcode,ccot.status,ccopmt.pname ";
        $batchcode_info = $this->get_crew_order($condition,$filed)['batchcode'][0];


        $price= 0;
        $supply_id = -1;
        $query_order_price = "SELECT price,supply_id FROM weixin_commonshop_order_prices WHERE batchcode='".$batchcode_info['batchcode']."'";
        $result_order_price = _mysql_query($query_order_price);
        while( $row_order_price = mysql_fetch_object($result_order_price) ){
            $price = $row_order_price -> price;
            $supply_id = $row_order_price -> supply_id;
        }

        $is_QR = 0;
        $query_order_qr = "SELECT is_QR FROM weixin_commonshop_orders WHERE batchcode='".$batchcode_info['batchcode']."'";
        $result_order_qr = _mysql_query($query_order_qr);
        while ( $row_order_qr = mysql_fetch_object($result_order_qr) ) {
            $is_QR = $row_order_qr -> is_QR;
        }

        //获取供应商openid
        $supply_openid = '';
        if ( $supply_id > 0 ) {
            $query_supply = "SELECT weixin_fromuser FROM weixin_users WHERE id=".$supply_id." AND isvalid=true";
            $result_supply = _mysql_query($query_supply);
            while ( $row_supply = mysql_fetch_object($result_supply) ) {
                $supply_openid = $row_supply -> weixin_fromuser;
            }
        }

        //非免单团拼团成功操作
        if($group_info['type'] != 6){



            $group_status = 3; //拼团成功

            if( $group_info['type'] == 2 ){
                $group_status = 4;	//待抽奖
            }

            //更新团状态
            $condition = array(
                'customer_id' => $customer_id,
                'id' => ' in ('.$group_id.')'
            );
            $value = array(
                'status' => $group_status
            );
            $this->update_group_order($condition,$value);

            $choose_tuan = array();
            if($group_info['type']==7){
                $choose_tuan = $this->lottery($group_id,$customer_id,$batchcode_info['pname']);
                $this->wlog($choose_tuan);
            }else{
                //更新团员订单状态
                $condition = array(
                    'customer_id' => $customer_id,
                    'group_id' => ' IN ('.$group_id.') ',
                    'isvalid' => true,
                    'activitie_id' => $group_info['activitie_id'],
                    'status' => 2
                );

                $value = array('status' => 5);
                $this->update_crew_order($condition,$value);
            }

            //抱抱团拼团成功返购物币
            if( $group_info['type'] == 5 ){
                $param = array(
                    'customer_id' => $customer_id,
                    'activitie_id' => $group_info['activitie_id'],
                    'group_id' => $group_id,
                    'price' => $price
                );

                $return_currency_result = $this->cal_back_currency($param);
                $currency_function = new Currency();
                $remark = '抱抱团返赠';
                $class = 18;
                if($return_currency_result['status']>0){
                    $res = array(
                        'is_collageActivities' => 1,
                        'group_status' => false,
                    );
                    return $res;
                }else{
                    $return_currency = bcadd($return_currency_result['return_currency'],0,2);
                    if($return_currency_result['default_coefficient']>0 && $return_currency>0){//后台设置的系数大于0才返利，等于零不返利
                        foreach($return_currency_result['return_users'] as $key => $val){
                            if(!empty($val['user_id']) && !empty($val['batchcode'])){
                                //返购物币
                                $currency_function->update_currency($val['user_id'],$customer_id,$return_currency,1,$val['batchcode'],$remark,$class,0);
                                //推送消息
                                $shopmessage = new shopMessage_Utlity();
                                $query2="select custom from weixin_commonshop_currency  where isvalid=true and customer_id=".$customer_id;
                                $result2 = _mysql_query($query2) or die('weixin_commonshop_currency Query failed: ' . mysql_error());
                                while ($row2 = mysql_fetch_object($result2)) {
                                    $custom	= $row2->custom;
                                    break;
                                }
                                $query2="select type_name from collage_activities_explain_t  where isvalid=true and type=5 and customer_id=".$customer_id;
                                $result2 = _mysql_query($query2) or die('collage_activities_explain_t Query failed: ' . mysql_error());
                                while ($row2 = mysql_fetch_object($result2)) {
                                    $type_name	= $row2->type_name;
                                    break;
                                }
                                $query2="select weixin_fromuser from weixin_users  where isvalid=true and id=".$val['user_id'];
                                $result2 = _mysql_query($query2) or die('weixin_users Query failed: ' . mysql_error());
                                while ($row2 = mysql_fetch_object($result2)) {
                                    $fromuser	= $row2->weixin_fromuser;
                                    break;
                                }
                                $content = "您通过".$type_name."活动获得￥".$return_currency.$custom."，请前往个人中心零钱查看 ";
                                $shopmessage->SendMessage($content,$fromuser,$customer_id);
                                //更新返利状态
                                $param = array(
                                    'batchcode' => $val['batchcode']
                                );
                                $this -> update_bbtorder_log($param);
                            }
                        }
                    }
                }
            }

            //更新拼团活动产品
            $condition = array(
                'activitie_id' => $group_info['activitie_id'],
                'isvalid' => true,
                'pid' => $group_info['pid']
            );
            $value = array(
                'total_success' => 'total_success+1',
                'total_conduct' => 'total_conduct-1'
            );
            $this->update_group_products($condition,$value);

            //获取该团的所有用户id和订单号
            $condition = " ccot.group_id=".$group_id." AND ccot.customer_id=".$customer_id." AND (ccot.status=5 OR ccot.status=1) AND ccot.isvalid=true ";
            $filed = " ccot.user_id,ccot.batchcode,ccot.status ";
            $batchcode_info = $this->get_crew_order($condition,$filed)['batchcode'];

            $user_id_arr = [];
            $batchcode_arr = [];
            $nopay_batchcode_arr = [];
            foreach( $batchcode_info as $k => $v ){
                if( $v['status'] == 5 ){	//已支付
                    $user_id_arr[] = $v['user_id'];
                    $batchcode_arr[] = $v['batchcode'];
                } else {	//未支付
                    $nopay_batchcode_arr[] = $v['batchcode'];
                }
            }

             //加单引号才能使用索引
            if(count($user_id_arr) > 0)
            {
                $user_id_str = implode("','",$user_id_arr);
                $user_id_str = "'".$user_id_str."'";
            }   

            if(count($batchcode_arr) > 0)
            {
                $batchcode_str = implode("','",$batchcode_arr);
                $batchcode_str = "'".$batchcode_str."'";
            } 

            if(count($nopay_batchcode_arr) > 0)
            {
                $nopay_batchcode_str = implode("','",$nopay_batchcode_arr);
                $nopay_batchcode_str = "'".$nopay_batchcode_str."'";
            } 

            // $user_id_str         = implode(',',$user_id_arr);
            // $batchcode_str       = implode(',',$batchcode_arr);
            // $nopay_batchcode_str = implode(',',$nopay_batchcode_arr);

            if( !empty($user_id_str) ){

                $condition = array(
                    'user_id_in' => $user_id_str
                );
                $value = array(
                    'total_success' => 'total_success+1'
                );
                $this->update_activities_user($condition,$value);


                //获取已支付的订单的产品名、用户标识，用于推送消息
                $condition = array(
                    'group_id_in' => $group_id,
                    'ccot.status' => 5,
                    'ccot.isvalid' => true
                );
                $filed = " ccopmt.pname,wu.weixin_fromuser,wu.id,ccot.is_head ";
                $pay_bat_info = $this->get_pay_batchcode_info($condition,$filed)['data'];

                //获取团类型名称
                $condition = " customer_id=".$customer_id." AND isvalid=true AND type=".$group_info['type'];
                $filed = " type_name ";
                $grouptype_info = $this->get_group_type($condition,$filed)['data'][0];
                $type_str = $grouptype_info['type_name'];

                $weixin_fromuser = array();
                //推送拼团成功消息
                foreach( $pay_bat_info as $k => $v ){
                    $weixin_fromuser[$v['id']] = $v['weixin_fromuser'];
                    switch( $group_info['type'] ){
                        case 1:
                            if(empty($type_str)){
                                $type_str = " 普通团";
                            }
                            $type_status_str = "拼团成功";
                            break;
                        case 2:
                            if(empty($type_str)){
                                $type_str = " 抽奖团";
                            }
                            $type_status_str = "待抽奖";
                            break;
                        case 3:
                            if(empty($type_str)){
                                $type_str = " 秒杀团";
                            }
                            $type_status_str = "拼团成功";
                            break;
                        case 4:
                            if(empty($type_str)){
                                $type_str = " 超级团";
                            }
                            $type_status_str = "拼团成功";
                            break;
                        case 5:
                            if(empty($type_str)){
                                $type_str = " 抱抱团";
                            }
                            $type_status_str = "拼团成功";
                            break;
                        case 7:
                            if(empty($type_str)){
                                $type_str = " 抽奖团";
                            }
                            $type_status_str = "拼团成功,待抽奖";
                            break;
                    }


                    $content = "亲，您参加的 ".$v['pname'].$type_str." 拼团成功\r\n".
                        "状态：【".$type_status_str."】\n".
                        "时间：".date( "Y-m-d H:i:s")."";
                    $utlity->SendMessage($content,$v['weixin_fromuser'],$customer_id);


                }

                if ( $supply_id > 0 && $group_info['type'] != 2 ) {
                    //抽奖团需要中奖才推送供应商发货提醒消息，免单团每次都需发，所以现在不需发
                    $content = "亲，".$pay_bat_info[0]['pname'].$type_str." 拼团成功，请及时发货\r\n".
                        "时间：".date( "Y-m-d H:i:s")."";
                    $utlity->SendMessage($content,$supply_openid,$customer_id);
                }
            }

            //抽奖团需要在后台抽奖才会改商城订单状态，免单团已经是有效订单
            if( $group_info['type'] != 2  ){
                //商城订单改为拼团有效订单
                if($batchcode_str != ""){
                    $condition = array(
                        'batchcode_in' => $batchcode_str,
                        'customer_id' => $customer_id
                    );
                    $value = array(
                        'is_collageActivities' => 1
                    );
                    $shop->update_order($condition,$value);
                }

            }

            //未支付的订单改为已取消状态
            if ( !$is_manual ) {
                if($nopay_batchcode_str != ""){
                    $condition = array(
                        'batchcode_in' => $nopay_batchcode_str,
                        'customer_id' => $customer_id
                    );

                    /*$log_name=$_SERVER['DOCUMENT_ROOT']."/weixinpl/log/errstatus_".date("Ymd").".log";
                    $log="3464未支付订单更新成-1，data: ".json_encode($condition)."\r\n时间:".date("Y-m-d H:i:s")."\r\n\r\n";
                    file_put_contents($log_name,$log,FILE_APPEND);*/

                    $value = array(
                        'status' => -1
                    );
                    $shop->update_order($condition,$value);
                }

            }

            foreach( $batchcode_arr as $k => $v ){

                //如果是二维码核销产品并且非抽奖团则自动发货,若是免单团，每次下单即发货
                if( $is_QR == 1 && $group_info['type'] != 2 &&  $group_info['type'] != 7){

                    $user_id = $user_id_arr[$k];

                    $fromuser = $weixin_fromuser[$user_id];

                    $utlity->GetQR($v,$fromuser,$customer_id);

                    $descript = "商家已发货";
                    $query_log = "INSERT INTO weixin_commonshop_order_logs(batchcode,operation,descript,operation_user,createtime,isvalid) values('".$v."',4,'".$descript."','".$fromuser."',now(),1)";
                    _mysql_query($query_log);

                    $new_shop_pay->weixin_erweima($v);

                    //更改订单状态
                    $query_order_up = "UPDATE weixin_commonshop_orders SET sendstatus=2,confirm_sendtime=now() WHERE customer_id=".$customer_id." AND batchcode='".$v."' AND isvalid=true";
                    _mysql_query($query_order_up);

                    $query_orderp_up = "UPDATE weixin_commonshop_order_prices SET sendstatus=2,confirm_sendtime=now() WHERE batchcode='".$v."' AND isvalid=true";
                    _mysql_query($query_orderp_up);

                }

                //拼团成功进行派单（不包括抽奖团跟免单团）
                if ( $group_info['type'] != 2 && $group_info['type'] != 6 && $group_info['type'] != 7 ) {
                    //派单
                    $new_shop_pay->send_order_fun($customer_id, '', $v);
                }
                $this->wlog($group_info);
                if($group_info['type'] ==7){
                    if($choose_tuan['batchcode'] == $v){
                        $new_shop_pay->send_order_fun($customer_id, '', $v);
                    }
                }
            }
        }else{
            $group_status = 5;  //成团成功
            $this->free_group_cashback($customer_id,$group_id);

            //更新团状态
            $condition = array(
                'customer_id' => $customer_id,
                'id' => ' in ('.$group_id.')'
            );
            $value = array(
                'status' => $group_status
            );
            $this->update_group_order($condition,$value);

            //更新团员订单状态
            $condition = array(
                'customer_id' => $customer_id,
                'group_id' => ' IN ('.$group_id.') ',
                'isvalid' => true,
                'activitie_id' => $group_info['activitie_id'],
                'status' => 2
            );

            //免单团则是成团成功
            $value = array('status' => 7 );
            $this->update_crew_order($condition,$value);

            //更新拼团活动产品
            $condition = array(
                'activitie_id' => $group_info['activitie_id'],
                'isvalid' => true,
                'pid' => $group_info['pid']
            );
            $value = array(
                'total_success' => 'total_success+1',
                'total_conduct' => 'total_conduct-1'
            );
            $this->update_group_products($condition,$value);


            //获取该团的所有用户id和订单号
            $condition = " ccot.group_id=".$group_id." AND ccot.customer_id=".$customer_id." AND (ccot.status=7 OR ccot.status=1) AND ccot.isvalid=true ";
            $filed = " ccot.user_id,ccot.batchcode,ccot.status ";
            $batchcode_info = $this->get_crew_order($condition,$filed)['batchcode'];

            $nopay_batchcode_arr = [];
            foreach( $batchcode_info as $k => $v ){
                if( $v['status'] != 7 ){	//未支付
                    $nopay_batchcode_arr[] = $v['batchcode'];
                }
            }
            $nopay_batchcode_str = implode(',',$nopay_batchcode_arr);

            //获取成团的订单的产品名、用户标识，用于推送消息
            $condition = array(
                'ccot.group_id' => $group_id,
                'ccot.status' => 7,
                'ccot.isvalid' => true,
                'ccot.is_head' => 1
            );
            $filed = " ccopmt.pname,wu.weixin_fromuser,wu.id ";
            $pay_bat_info = $this->get_pay_batchcode_info($condition,$filed)['data'][0];


            $content = "亲，您开的 ".$pay_bat_info['pname']."免单团 成团成功\r\n".
                "状态：【拼团成功,正返还零钱】\n".
                "时间：".date( "Y-m-d H:i:s")."";
            $utlity->SendMessage($content,$pay_bat_info['weixin_fromuser'],$customer_id);

            if ( !$is_manual ) {
                //未支付的订单改为已取消状态
                if($nopay_batchcode_str != ""){
                    $condition = array(
                        'batchcode_in' => $nopay_batchcode_str,
                        'customer_id' => $customer_id
                    );

                    /*$log_name=$_SERVER['DOCUMENT_ROOT']."/weixinpl/log/errstatus_".date("Ymd").".log";
                    $log="3585未支付订单更新成-1，data: ".json_encode($condition)."\r\n时间:".date("Y-m-d H:i:s")."\r\n\r\n";
                    file_put_contents($log_name,$log,FILE_APPEND);*/

                    $value = array(
                        'status' => -1
                    );
                    $shop->update_order($condition,$value);
                }
            }

        }

        $result["code"] = 10001;
        $result["msg"] = '拼团成功';
        return $result;
    }

    /*
     *	记录抱抱团开团日志（支付回调）
     *
     */
    function cuddle_group_log ($param = array()) {
        //1、判断参数是否异常
        $customer_id  = $param['customer_id'];
        $activitie_id = $param['activitie_id'];
        $group_id     = $param['group_id'];
        $group_type   = $param['group_type'];
        $return_type   = $param['return_type'];

        if($customer_id<0 ||$activitie_id<0 || $group_id<0){
            $result["status"] = 40012;
            $result["msg"] = '参数异常';
            return $result;
        }

        //2、获取活动配置
        $condition = array(
            'customer_id' => $customer_id,
            'isvalid' => true,
            'id' => $activitie_id
        );
        $field = " coefficient,return_curr ";
        $activity_info = $this -> getActivitiesMes($condition,$field)['data'][0];
        $default_coefficient = $activity_info['coefficient'];
        $return_curr_arr = json_decode($activity_info['return_curr'],true);

        //3、获取团系数
        $condition = array(
            'cgot.id' => $group_id,
            'ae.isvalid' => true,
            'ae.customer_id' => $customer_id
        );
        $filed = " cgot.coefficient";
        $activity_group_info = $this->select_front_group($condition,$filed)['data'][0];
        $group_coefficient = $activity_group_info ['coefficient'];
        //4、根据系数获取该团的配置
        if($default_coefficient>0){
            foreach( $return_curr_arr as $key => $value ){
                if($value['collage_times']==$group_coefficient){
                    $group_return_type = $value['return_type'];
                    $group_return_value = $value['return_value'];
                }
            }
        }else{
            $group_return_type = 0;
            $group_return_value = 0;
        }

        //5、插入拼团拓展表
        $query = "INSERT INTO collage_open_group_extend(
						customer_id,
                        isvalid,
						createtime,
                        group_id,
                        group_type,
                        return_type,
                        coefficient
						) VALUES(
						".$customer_id.",
                        true,
						now(),
						".$group_id.",
						".$group_type.",
						".$return_type.",
						".$default_coefficient."
						)";
        _mysql_query($query) or die('add_collage_open_group_extend query failed: ' . mysql_error());

        //5、插入抱抱团循环系数配置表
        $query = "INSERT INTO collage_group_coefficient_conf(
                        isvalid,
						createtime,
                        group_id,
                        coefficient,
                        return_type,
                        return_value
						) VALUES(
                        true,
						now(),
						".$group_id.",
						".$group_coefficient.",
						".$group_return_type.",
						".$group_return_value."
						)";
        _mysql_query($query) or die('collage_group_coefficient_conf query failed: ' . mysql_error());


        //6、返回结果
        $result["status"] = 0;
        $result["msg"] = 'success';
        return $result;
    }

    /*
     *	记录抱抱团订单日志（支付回调）
     *
     */
    function cuddle_order_log ($param = array()) {
        //1、判断参数是否异常
        $customer_id  = $param['customer_id'];
        $group_id     = $param['group_id'];
        $user_id      = $param['user_id'];
        $batchcode    = $param['batchcode'];
        $price   = $param['price'];

        if($customer_id<0 ||$user_id<0 || $group_id<0){
            $result["status"] = 40012;
            $result["msg"] = '参数异常';
            return $result;
        }
        //查找团配置
        $query = 'SELECT return_type,coefficient FROM collage_open_group_extend where isvalid=true and customer_id='.$customer_id.'  and group_id='.$group_id.' limit 1';
        $result0 = _mysql_query($query) or die('collage_open_group_extend2 query failed: ' . mysql_error());
        $return_type = 0;
        $coefficient = 0;
        while($row = mysql_fetch_object($result0)){
            $return_type = $row -> return_type;
            $coefficient = $row -> coefficient;
        }

        //查找团配置
        $query = 'SELECT coefficient,return_type,return_value FROM collage_group_coefficient_conf where isvalid=true and group_id='.$group_id.' limit 1';
        $result0 = _mysql_query($query) or die('collage_group_coefficient_conf2 query failed: ' . mysql_error());
        $order_coefficient = 0;
        $coeff_return_type = 0;
        $coeff_return_value = 0;
        while($row = mysql_fetch_object($result0)){
            $order_coefficient = $row -> coefficient;
            $coeff_return_type = $row -> return_type;
            $coeff_return_value = $row -> return_value;
        }
        //插入记录
        $query = "INSERT INTO collage_bbt_order_extend(
                        isvalid,
						createtime,
                        group_id,
                        user_id,
                        batchcode,
                        coefficient,
                        return_type,
                        order_coefficient,
                        coeff_return_type,
                        coeff_return_value,
                        price,
                        return_status
						) VALUES(
                        true,
						now(),
						".$group_id.",
                        ".$user_id.",
                        ".$batchcode.",
						".$coefficient.",
						".$return_type.",
						".$order_coefficient.",
                        ".$coeff_return_type.",
                        ".$coeff_return_value.",
                        ".$price.",
                        0
						)";
        _mysql_query($query) or die('collage_bbt_order_extend query failed: ' . mysql_error());

        //6、返回结果
        $result["status"] = 0;
        $result["msg"] = 'success';
        return $result;

    }

    /*
     *	更新抱抱团订单日志（支付回调）
     *
     */
    function update_bbtorder_log ($param = array()) {
        $batchcode    = $param['batchcode'];
        $query = "UPDATE collage_bbt_order_extend SET return_status=1 WHERE batchcode='".$batchcode."'";
        _mysql_query($query) or die('collage_bbt_order_extend2 query failed: ' . mysql_error());

    }
    /*
	 *	记录免单团返零钱记录
	 *  @param  int $customer_id 商家编号
	 *  @param  int $group_id    团编号
	 *  @param  int $head_id     团长ID
	 *  @param  int $price       返零钱数
	 *  @param  int $batchcode   订单号
	 *  @param  int $join_num    成功参团人数
	 */
    function  insert_free_currency_log($customer_id,$group_id,$head_id,$price,$batchcode,$join_num){
        $query = "INSERT INTO collage_free_order_extend(
						customer_id,
                        isvalid,
						createtime,
                        group_id,
                        head_id,
                        batchcode,
                        currency,
                        join_num
						) VALUES(
						".$customer_id.",
                        true,
						now(),
						".$group_id.",
						".$head_id.",
						".$batchcode.",
						".$price.",
						".$join_num."
						)";
        _mysql_query($query) or die('add collage_free_group_extend query failed: ' . mysql_error());

        //6、返回结果
        $result["status"] = 1;
        $result["msg"] = 'success';
        return $result;

    }
}
?>