<?php
/**
 * Created by PhpStorm.
 * User: Wei
 * Date: 2017/2/8
 * Time: 15:36
 */

include_once($_SERVER['DOCUMENT_ROOT'].'/mshop/web/model/integral.php');//积分活动
include_once(LocalBaseURL."function_model/collageActivities.php");//拼团
require_once($_SERVER['DOCUMENT_ROOT'].'/weixinpl/php-emoji/emoji.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/mp/lib/LogOpe.php');//日志文件

class new_shop_pay
{
    /*
    *构造函数
    */
    function __construct($customer_id) {
        $this->common= new shopMessage_Utlity();
        $this->paymentPlan= new payment_Plan();
        $this->Promoter= new Promoter\PromoterUtlity($customer_id);
        $this->moneybag = new MoneyBag();
        $this->collageActivities = new collageActivities($customer_id);
        $this->shop = new shop();
		$this->model_integral = new model_integral();
        $this->log_name = "handle_shop_order";
        //$this->logger= Logger::getRootLogger();//错误日志
    }

    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }


    //日志记录 - 方法版v3
    public function zlog_insert($log_name, $log_content, $isclean = 0){
        $debugInfo = debug_backtrace();
        $log_name .= "_".date("Ymd").".log";
        $zlog_name = "{$_SERVER['DOCUMENT_ROOT']}/weixinpl/log/{$log_name}";//log文件路径
        $time = date("Y-m-d H:i:s");
        $content_info = "DEBUG --- time:{$time} --- LINE:{$debugInfo[0]['line']} --- func:{$debugInfo[1]['function']} --- URL:{$_SERVER['PHP_SELF']} ---\n";
        $log_content = $content_info.$log_content;
        //file_put_contents($zlog_name ,$log_content,$isclean ?: FILE_APPEND);
        _file_put_contents($log_name ,$log_content,$isclean ?: FILE_APPEND);
    }



    /**
     * 支付方法
     * @param  int $paytype   支付方式
     * @param  int $customer_id 商家编号
     * @param  int $user_id   用户id
     * @param  int $entryMode   进入方式 1：直接支付、2：我的订单
     * @param  int $pay_price   支付价钱(总价-购物币)
     * @param  int $batchcode   支付订单号
     * @param  float $payCurrency 订单使用的购物币
     * @param  float $pay_scores 支付积分
     * @param  int $is_QR 票券订单 是否 二维码产品 0:否 1:是
     * @param  int   $is_affair   事务 0:关闭，1开启
     * @param  varchar  $third_batchcode    第三方支付回调订单号，如微信、支付宝等 平台支付为空
     * @param  float  $real_pay_price  区块链实际支付的金额，可能与订单金额不同
     */
    public function payment_Common($paytype,$customer_id,$user_id,$entryMode,$pay_price,$batchcode,$payCurrency,$pay_scores,$is_QR,$is_affair,$third_batchcode="",$real_pay_price=""){
        $log_name = ROOT_DIR."wsy_pay/web/function/new_pay_".date("Ymd").".log";//log文件路径

        $result = array();
        $result["errcode"] = 1;
        $result["errmsg"] = 'success';
        file_put_contents($log_name,date('Y-m-d H:i:s')."------paytype  ------------".$paytype."-------------------\n",FILE_APPEND);
        file_put_contents($log_name,"------pay_price------------".$pay_price."---------------------\n",FILE_APPEND);
        $this->zlog_insert($this->log_name,"payment_Common开始执行\npay_batchcode：$batchcode  paytype：$paytype  pay_price：$pay_price");
        //支付价钱、积分、购物币小于0这不执行下面
        //优惠券金额大于支付金额可出现支付价钱为0情况
        if( $pay_price < 0 and $payCurrency <= 0 and $pay_scores <= 0 ){
            $result["errcode"]    = 11;
            $result["errmsg"]     = 'fail';
            $result["msg"]        = '支付异常';
			$result["batchcode"]  = $batchcode;
            return $result;
        }
        if( $pay_price < 0 or $payCurrency < 0 or $pay_scores < 0 ){
            $result["errcode"]    = 11;
            $result["errmsg"]     = 'fail';
            $result["msg"]        = '数据异常';
			$result["batchcode"]  = $batchcode;
            return $result;
        }

        $result_pay         = "";
        $pay_class          = 3;

       // _tran_start(); //开始事务
        $sql = "select paystatus from weixin_commonshop_order_prices where pay_batchcode='".$batchcode."' and isvalid=true for update "; // TODO: 判断订单状态，以及isvalid
        $result_op = _mysql_query($sql) or die('订单信息查询 Query failed: ' . mysql_error());
        while ($row = mysql_fetch_object($result_op)) {
            $paystatus    	= $row->paystatus;
            if( $paystatus == 1 ){
                $result["errcode"] 	= 11;
                $result["errmsg"] 	= 'error';
                $result["msg"]		= "请勿重复支付订单！";
                $result["batchcode"]= $batchcode;
                _tran_rollback();
                return $result;
            }
        }
        file_put_contents($log_name,"------paystatus------------".$paystatus."---------------------\n",FILE_APPEND);

        if( $pay_price > 0 ){
            switch($paytype){
                case "会员卡余额支付":
                    $pay_class = 2;
                    break;
                case "零钱支付": //
                    $pay_class = 3;
                    break;
                case "微信支付": //
                    $pay_class = 4;
                    break;
                case "支付宝支付": //
                    $pay_class = 5;
                    break;
                case "易宝支付": //
                    $pay_class = 6;
                    break;
                case "找人代付": //
                    $pay_class = 7;
                    break;
                case "京东支付": //
                    $pay_class = 8;
                    break;
                case "paypal支付": //
                    $pay_class = 10;
                    break;
                case "V咖支付": //
                    $pay_class = 11;
                    break;
                case "兴业银行公众号支付":
                    $pay_class = 12;
                    break;
                case "环迅快捷支付":
                    $pay_class = 13;
                    break;
                case "环迅微信支付":
                    $pay_class = 14;
                    break;
                case "威富通支付":
                    $pay_class = 15;
                    break;
                case "健康钱包支付":
                    $pay_class = 16;
                    break;
                case "区块链积分支付":
                    $pay_class = 17;
                    break;
                default:
                    #code...
                    break;
            }
            $result_pay["status"] = 1;
            $result_pay["callBackBatchcode"] = $third_batchcode;


            if ( $result_pay["status"] != 1) {
                _tran_rollback();//事务回滚
                return $result_pay;
            }
            $callBackBatchcode = $result_pay["callBackBatchcode"];
        }elseif($pay_price == 0){
            $paytype == "抵扣支付";
            $pay_class = 9;
            $result_pay["status"] = 1;
            $callBackBatchcode = $user_id.$batchcode;
        }

        file_put_contents($log_name,"插入支付回调\n",FILE_APPEND);
        //插入支付回调
        $this->zlog_insert($this->log_name,"pay_batchcode：$batchcode \npay_Callback($customer_id,$batchcode,$callBackBatchcode,$pay_price,$payCurrency,$pay_scores,$pay_class,$is_affair)");
        $this->paymentPlan->pay_Callback($customer_id,$batchcode,$callBackBatchcode,$pay_price,$payCurrency,$pay_scores,$pay_class,$is_affair);
        $this->zlog_insert($this->log_name,"pay_batchcode：$batchcode \nupdate_order($customer_id,$batchcode,$paytype,1,0)");
        //更改订单状态
        $this->update_order($customer_id,$batchcode,$paytype,1,0,0,$real_pay_price);
        //判断卡密信息
        $this->camilo_info($customer_id,$batchcode);
        //判断区块链日志信息
        $this->block_chain_log($customer_id,$batchcode,$paytype,$real_pay_price);
        //更改明细表的支付状态
        $this->update_block_chain_order($customer_id,$batchcode);
		//更改团订单状态
        $this->zlog_insert($this->log_name,"pay_batchcode：$batchcode \nupdate_pay_crew_order($customer_id,$batchcode,$paytype)");
		$collage_group_status = $this->collageActivities->update_pay_crew_order($customer_id,$batchcode,$paytype);


		//拼团订单逻辑
		if( $collage_group_status['is_collageActivities'] > 0 ){
			if( !$collage_group_status['group_status'] ){
				$result = array(
					'errcode' => 11,
					'errmsg' => 'fail',
					'msg' => '抱歉，您参加的团已经拼团成功了，稍后会退回您支付的金额！',
					'batchcode' => $batchcode
				);
				return $result;
			}
		}
		//_tran_commit();//事务提交

        $fromuser_arr	= $this->common->query_openid($customer_id,$user_id);//获取用户openid
        $fromuser       = $fromuser_arr["openid"];
        $weixin_name	= $fromuser_arr["name"]."(".$fromuser_arr["weixin_name"].")";

        $m_batchcode	= -1;   //订单号
        $m_reward_money	= -1;   //佣金
        $m_currency		= -1;   //返还购物币
        $m_needScore	= -1;   //订单用到的积分

        $sql = "select batchcode,reward_money,currency,needScore,supply_id,pay_currency,or_code from weixin_commonshop_order_prices where pay_batchcode='".$batchcode."'";
        $result_op = _mysql_query($sql) or die('订单信息查询 Query failed: ' . mysql_error());
        while ($row = mysql_fetch_object($result_op)) {
            $m_batchcode    = $row->batchcode;
            $m_reward_money = $row->reward_money;
            $m_currency     = $row->currency;
            $m_needScore    = $row->needScore;
            $m_supply_id    = $row->supply_id;
            $m_pay_currency = $row->pay_currency;
            $m_or_code      = $row->or_code;
            $result["GetMoney"][] = array(
                'm_batchcode'   => $m_batchcode,
                'm_reward_money'=> $m_reward_money,
                'm_currency'    => $m_currency,
                'm_needScore'   => $m_needScore,
                'm_supply_id'   => $m_supply_id,
                'm_pay_currency'=> $m_pay_currency
            );

            //插入操作日志
            $descript = "订单支付 －".$paytype;
            $this->order_operate($m_batchcode,2,$descript,$fromuser);

//            //首次推广奖励  ， //首次推广奖励放到分佣任务时执行
//            $this->common->GetMoney_FirstExtend($customer_id,$m_batchcode);

            if( $is_QR == 1 && $collage_group_status['is_collageActivities'] == 0 ){	//拼团订单：二维码核销产品不自动发货
                $this->common->GetQR($m_batchcode,$fromuser,$customer_id);

                //插入操作日志
                $descript = "商家已发货";
                $this->order_operate($m_batchcode,4,$descript,$fromuser);

                //二维码核销发货操作
                $this->weixin_erweima($m_batchcode);

                //更改订单状态
                $this->update_order($customer_id,$m_batchcode,$paytype,2,2);

                //卡密产品发货修改
                $this->camilo_info($customer_id,$batchcode,2);

            }


            if ($m_or_code) {
                $s_sql = "update ".WSY_DH.".orderingretail_code set owner_id='".$user_id."',owner_identity=3 where code='".$m_or_code."'";
                _mysql_query($s_sql);
                //物码订单更新订单状态
                $s_sql = "update weixin_commonshop_orders set sendstatus=2 where batchcode='".$m_batchcode."'";
                _mysql_query($s_sql);
                $s_sql = "update weixin_commonshop_order_prices set sendstatus=2 where batchcode='".$m_batchcode."'";
                _mysql_query($s_sql);
            }

			/*sz技术郑培强 修改--start*/
			$update_bargain="update ".WSY_SHOP.".kj_order set status=2,is_pay=1,pay_style='".$paytype."' where batchcode='".$m_batchcode."' and isvalid=1";
			_mysql_query($update_bargain) or die('更新砍价订单失败' . mysql_error());
			/*sz技术郑培强 修改--end*/
			/*sz技术郑培强 修改--start*/
			$update_bargain="update ".WSY_SHOP.".cr_order set status=1,is_pay=1,pay_style='".$paytype."' where batchcode='".$m_batchcode."' and isvalid=1";
			_mysql_query($update_bargain) or die('更新砍价订单失败' . mysql_error());
			/*sz技术郑培强 修改--end*/
            //删除库存回收信息
            $sql = "delete FROM stockrecovery_t WHERE batchcode='".$m_batchcode."'";
            _mysql_query($sql) or die('删除库存回收信息 Query failed: ' . mysql_error());
            if( $m_supply_id > 0 && $collage_group_status['is_collageActivities'] == 0 ){	//拼团订单的供应商发货提醒消息放到拼团成功里面推送
                $supply_fromuser_arr = $this->common->query_openid($customer_id,$m_supply_id);
                $supply_fromuser = $supply_fromuser_arr["openid"];
                $content = "亲，您有一笔新订单，请及时发货\n订单：".$m_batchcode."\n顾客：".emoji_html_to_unified($weixin_name)."\n时间：".date( "Y-m-d H:i:s")."";
                //发送客服消息提醒 定时计划推送，屏蔽即时推送
                // $this->common->sendWeixinMessage($customer_id,$supply_fromuser,$content);
                // $this->common->SendMessage($content,$supply_fromuser,$customer_id,1,$m_batchcode,2,$m_supply_id); crm 15172

                //只插入记录，不推送，由定时计划推送 2017-8-7    by lzw
               $query = "INSERT INTO send_weixinmsg_log (
                               customer_id, createtime, type, content, openid
                           ) VALUES (
                               '".$customer_id."', now(), 0, '".mysql_real_escape_string($content)."', '".$supply_fromuser."'
                           )";
               _mysql_query($query) or die('Query insert into send_weixinmsg_log failed:'.mysql_error());
            }
        }
		/*电商直播下单操作---start*/
        $this->zlog_insert($this->log_name,"pay_batchcode：$batchcode \nmb_order($batchcode)");
		$this->mb_order($batchcode);
		/*电商直播下单操作---end*/

        /*云店下单操作---start*/
        $this->yundian_order($customer_id,$batchcode,$weixin_name);
        /*云店下单操作---end*/

		/*兑换积分活动下单操作---start*/
		$parm = [
			'pay_batchcode'=>$batchcode,
			'customer_id'=>$customer_id,
		];
        $this->zlog_insert($this->log_name,"pay_batchcode：$batchcode \npay_success_or_failed");
		$this->model_integral->pay_success_or_failed(1,$parm);

		/*兑换积分活动下单操作---end*/

		/*商城下单后大转盘操作---start*/
		$query_sly = "UPDATE ".WSY_SHOP.".slyder_adventures_chance_extend SET is_pay=1,date='".date('Y-m-d')."' WHERE isvalid=true AND batchcode='".$m_batchcode."' AND customer_id=".$customer_id;
		_mysql_query($query_sly);
		/*商城下单后大转盘操作---end*/

        $result["errcode"] 			    = 1;
        $result["errmsg"]               = 'success';
        $result["msg"] 				    = "支付成功";
        $result["callBackBatchcode"]    = $callBackBatchcode;
        $result["batchcode"]			= $batchcode;
        $result["collage_group_status"] = $collage_group_status;

        //分佣方法参数
        $result['payCurrency']          = $payCurrency;


        //勾选协议成为推广员优化     --18/2/28 by:whl
        //因第三方支付时，购买完成后，修改订单状态可能出现延迟，所以添加于此
        $totalprice = 0;
        $query2     = "select sum(totalprice) as total_money from weixin_commonshop_orders where isvalid=true and customer_id='".$customer_id."' and user_id='".$user_id."'  and paystatus=1  and sendstatus<3 and return_status in(0,3,9)";
        $result2    = _mysql_query($query2) or die('Query failed: ' . mysql_error());
        while ($row2    = mysql_fetch_object($result2)) {
           $totalprice  = $row2->total_money;
        }

        $query      = "select auto_upgrade_money,is_autoupgrade,sell_detail,exp_name,name from weixin_commonshops where isvalid=true and customer_id='".$customer_id."' limit 0,1";
        $result_s   = _mysql_query($query) or die('Query failed: ' . mysql_error());
        $auto_upgrade_money = -1;
        $is_autoupgrade     = -1;
        $sell_detail        = "";
        $exp_name           = "推广员";
        $shop_name          = "微商城";
        while ($row = mysql_fetch_object($result_s)) {
            $auto_upgrade_money = $row->auto_upgrade_money;
            $is_autoupgrade     = $row->is_autoupgrade;
            $sell_detail        = $row->sell_detail;
            $exp_name           = $row->exp_name;
            $shop_name          = $row->name;

            $is_autoupgrade_arr = explode(",",$is_autoupgrade);
        }
        //var_dump($is_autoupgrade_arr[0].'a:'.$totalprice.'b'.$auto_upgrade_money);exit;
        if(  $is_autoupgrade_arr[0]==4 && $totalprice>=$auto_upgrade_money ){
            $promo_id  =-1;         
            $query_p   = "select id from promoters where status=1  and user_id='".$user_id."' and isvalid=true and customer_id='".$customer_id."' limit 0,1";
            $result_p  = _mysql_query($query_p) or die('Query failed: ' . mysql_error());
            while ($row_p   = mysql_fetch_object($result_p)) {
                $promo_id   = $row_p->id;
            }

            if($promo_id < 0 ){     //是否已存在身份
               
                //查询是否有大转盘抽奖次数--start
                $slyder_extend_id   = -1;
                $extend_url         = "";
                $query_chance  = "SELECT id FROM ".WSY_SHOP.".slyder_adventures_chance_extend WHERE isvalid=true AND batchcode='".$m_batchcode."' AND customer_id='".$customer_id."' LIMIT 1";
                $result_chance = _mysql_query($query_chance);
                while( $row_chance = mysql_fetch_object($result_chance) ){
                    $slyder_extend_id = $row_chance->id;
                }
                if($slyder_extend_id > 0){
                    $extend_url .= "&slyder_extend_id=".$slyder_extend_id;
                }
                //查询是否有大转盘抽奖次数--end

                $msg_content = "恭喜你已达".$shop_name."商城的成为".$exp_name."的升级条件；\r\n请点击<a href='".BaseURL."common_shop/jiushop/order_aplay_promote.php?come_in=msg&pay_batchcode=".$batchcode.$extend_url."'>成为".$exp_name."</a>，同意协议即可升级成功！";

                _file_put_contents(ROOT_DIR."wsy_pay/web/function/new_pay_".date("Ymd").".log", "协议成为推广员推送=====msg_content:".$msg_content."\r\n",FILE_APPEND);

            
                $this->common->SendMessage($msg_content, $fromuser, $customer_id);
            }
        }
        $this->zlog_insert($this->log_name,"payment_Common 执行成功 result：".json_encode($result,JSON_UNESCAPED_UNICODE));
        return $result;
    }

    /**
     * 订单操作
     * @param  int $batchcode   支付订单号
     * @param  int $operate   订单操作；0：下单；1：取消；2：支付；3：修改价格；4：发货：5：申请延期；6：确认延期；7：确认收货；8：退货；9：退货审批；10：未发货退款；11：审批退款；12：退款；13：用户退货填单；14：商家确认退货；15：退货完成；16：确认完成；17：订单评价；18：申请维权；19：维权审批；20：维权处理；21：微信退款操作；23：维权扣除供应商款项
     * @param  int $descript   描述
     * @param  int $fromuser   操作人openid
     */
    public function order_operate($batchcode,$operate,$descript,$fromuser){
        $query_log = "insert into weixin_commonshop_order_logs(batchcode,operation,descript,operation_user,createtime,isvalid) values('".$batchcode."',".$operate.",'".$descript."','".$fromuser."',now(),1)";
        _mysql_query($query_log) or die('插入订单操作日志 Query failed: ' . mysql_error());
    }

    /**
     * 更改订单状态方法
     * @param  int $customer_id 商家编号
     * @param  int $batchcode   支付订单号
     * @param  int $paystyle 支付方式
     * @param  int $entryMode   进入方式 1：直接支付、2：我的订单
     * @param  int $state   订单状态 0:支付；1：已发货;2:已收货;3.申请退货；4.已退货;5申请退款；6：已经退款
     * @param  int $is_affair   事务 0:关闭，1开启
     * @param  float $real_pay_price  区块链支付金额
     */
    public function update_order($customer_id,$batchcode,$paystyle,$entryMode,$state,$is_affair = 0,$real_pay_price = ''){

        $sql_o = "update weixin_commonshop_orders set paystatus=1,paystyle='".$paystyle."',paytime=now(),sendstatus=".$state;
        $sql_p = "update weixin_commonshop_order_prices set paystatus=1,paystyle='".$paystyle."',paytime=now(),sendstatus=".$state."";

        if( $state == 1 ){
            $sql_o .= ",confirm_sendtime=now()";
            $sql_p .= ",confirm_sendtime=now()";
        }

        if($paystyle == '区块链积分支付'){
            $sql_o .= ",block_chain_price='".$real_pay_price."'";
            $sql_p .= ",block_chain_price='".$real_pay_price."'";

        }

		if( $entryMode == 1 ){
			$sql_o .= " where customer_id=".$customer_id." and pay_batchcode='" . $batchcode . "' and isvalid=true";
			$sql_p .= " where pay_batchcode='" . $batchcode . "' and isvalid=true";
		} else {
			$sql_o .= " where customer_id=".$customer_id." and batchcode='" . $batchcode . "' and isvalid=true";
			$sql_p .= " where batchcode='" . $batchcode . "' and isvalid=true";
		}


        mysql_update_sql($sql_o,0,$is_affair);
        mysql_update_sql($sql_p,0,$is_affair);

        $result = array();
        $result["errcode"]     = 1;
        $result["errmsg"]      = 'success';
    }

    /**
     * [update_order_counter 更新商城订单计数器]
     * @param  [type] $customer_id [description]
     * @param  [type] $pay_batchcode [支付订单号]
     * @return [type]              [description]
     */
    public function update_order_counter($customer_id,$pay_batchcode){
        //查询商城订单计数器（以防计数器中没有该商家记录）
        $query_count = "select order_count from weixin_commonshop_order_counter where customer_id=".$customer_id." and condition_type=1";
        $res_count = _mysql_query($query_count) or die("XH Counter1 Query failed :".mysql_error());
        $order_count = -1;//默认商城订单数量,不能为0，以示区分
        while($row=mysql_fetch_object($res_count)){
            $order_count = $row-> order_count;
        }

        if($order_count==-1){//如果计数器中没有记录该商家订单总数量
            //通过订单表统计订单数量
            $sql_ordercount="select count(1) as ordercount from weixin_commonshop_orders where customer_id=".$customer_id." and isvalid=true and paystatus=1";
            $res = _mysql_query($sql_ordercount) or die("XH Counter2 Query failed :".mysql_error());
            while($row=mysql_fetch_object($res)){
                $order_count = $row->ordercount;
            }

            //将当前商家订单总量保存在计数器中，以便下次查询使用
            if($order_count==-1){//如果订单表中没有该商家的订单
                $order_count = 0;//默认插入计数器中的订单总量为0
            }
            $insert_ordercount = "insert into weixin_commonshop_order_counter(customer_id,condition_type,order_count) values (".$customer_id.",1,".$order_count.")";
            _mysql_query($insert_ordercount) or die("XH Counter3 Insert failed :".mysql_error());
        }

        /*************更新计数器中订单总量 start*******************/
        //1.查询此次下单订单数量（weixin_commonshop_orders里面生成了几条记录就加几，包括重复batchcode）
        $query_cur_order = "select count(1) as o_count from weixin_commonshop_orders where customer_id=".$customer_id." and pay_batchcode='".$pay_batchcode."' and paystatus=1 and isvalid=true";
        $res_cur_order = _mysql_query($query_cur_order) or die("XH Counter4  Query faield :".mysql_error());
        $cur_order_count = 0;
        while($row=mysql_fetch_object($res_cur_order)){
            $cur_order_count = $row->o_count;
        }
        //2.更新计数器
        $update_sql = "update weixin_commonshop_order_counter set order_count=order_count+".$cur_order_count." where customer_id=".$customer_id." and condition_type=1";
        _mysql_query($update_sql) or die("XH Counter5  Query faield :".mysql_error());
    }

	 /**
     * 电商直播订单操作
     * @param  int $batchcode   支付订单号
     */
	function mb_order($batchcode){
		//查询电商直播订单
		$query_mb = "select user_id,batchcode,totalprice from weixin_commonshop_orders where paystatus=1 and mb_order=1 and pay_batchcode='".$batchcode."'";
		$result_mp = _mysql_query($query_mb) or die('电商直播1 Query failed: ' . mysql_error());
		$user_id = -1;
		$totalprice = 0;
		$batchcode = '';
        while ($row = mysql_fetch_object($result_mp)) {
            $user_id = $row->user_id;
            $batchcode = $row->batchcode;
            $totalprice = $row->totalprice;
			$totalprice = bcadd($totalprice,0,2);//截取保留2位小数

			//查询话题id
			$sql_mb = "select topic_id,account_id,customer_id,type from mb_order where batchcode='".$batchcode."'";
			$result_mp = _mysql_query($sql_mb) or die('电商直播2 Query failed: ' . mysql_error());
			$type = -1;			//订单类型：1直播，2视频
			$topic_id = -1;		//话题id或者资源id
			$account_id = -1;	//主播账号id
			$customer_id = -1;
			while ($row = mysql_fetch_object($result_mp)) {
				$type = $row->type;
				$topic_id = $row->topic_id;
				$account_id = $row->account_id;
				$customer_id = $row->customer_id;

				//查询用户姓名
				$sql_user = "select weixin_name from weixin_users where id=".$user_id."";
				$result_user = _mysql_query($sql_user) or die('电商直播3 Query failed: ' . mysql_error());
				$weixin_name = '';
				while ($row = mysql_fetch_object($result_user)) {
					$weixin_name = $row->weixin_name;
				}
				if( $type == 1 ){		//直播间类型，传输购买信息
					$sql_resource = "update mb_topic_info_count set order_num=order_num+1,total_order_price=total_order_price+".$totalprice." where topic_id=".$topic_id."";
					_mysql_query($sql_resource) or die('sql_resource failed:'.mysql_error());
					/*发送消息到workman开始*/
					require_once($_SERVER['DOCUMENT_ROOT']."/wsy_pay/web/function/WebsocketClient.php");
					$so='127.0.0.1:6721';
					$so_arr=explode(':',$so);
					$s=new WebsocketClient($so_arr[0],$so_arr[1]);
					$send_data=(object)[
						"info"=>$weixin_name.'刚刚下单购买了',
						"state"=>"20",
						'topic_id'=>$topic_id
					];
					$send_data=json_encode($send_data);
					$s->sendData($send_data);
					/*发送消息到workman结束*/
				}elseif( $type == 2 ){	//资源类型，增加资源的数据
					$sql_resource = "update mb_resource_count set order_num=order_num+1,order_price=order_price+".$totalprice." where resource=".$topic_id."";
					_mysql_query($sql_resource) or die('sql_resource failed:'.mysql_error());
				}

				//更新主播数据
				$count_id = -1;
				$query_topic_count = "SELECT id FROM mb_account_count_info WHERE isvalid=true AND account_id=".$account_id." ";
				$result_topic_count = _mysql_query($query_topic_count) or die('Query_topic_count failed:'.mysql_error());
				while( $row_topic_count = mysql_fetch_object($result_topic_count) ){
					$count_id = $row_topic_count->id;
				}
				if( $count_id > 0 ){
					$sql_topic = "UPDATE mb_account_count_info SET count_order=count_order+1,count_ordermoney=count_ordermoney+".$totalprice." WHERE isvalid=true AND account_id=".$account_id;
				}else{
					$sql_topic = "INSERT INTO mb_account_count_info(customer_id,account_id,isvalid,count_fans,count_ordermoney,count_order,count_anchor,count_video) VALUES(".$customer_id.",".$account_id.",true,0,".$totalprice.",1,0,0)";
				}
				_mysql_query($sql_topic) or die('Sql_topic failed:'.mysql_error());
			}
		}
	}

    /**
     * 二维码核销自动发货方法
     * @param  int $m_batchcode 订单号
     */
    public function weixin_erweima($m_batchcode){
        $query_order = "select user_id,agent_id,totalprice,agentcont_type,sendstatus from weixin_commonshop_orders where isvalid=true and batchcode='".$m_batchcode."'";
        $result_order = _mysql_query($query_order) or die('Query_order failed: ' . mysql_error());
        while ($row_order = mysql_fetch_object($result_order)) {
            $user_id        = $row_order->user_id;        //用户ID
            $agent_id       = $row_order->agent_id;     //代理商user_id
            $totalprice     = $row_order->totalprice;    //订单总金额
            $agentcont_type = $row_order->agentcont_type;   //分佣路线
            $sendstatus     = $row_order->sendstatus;   //发送状态


            /* 代理商扣除库存 */
            if($agent_id>0 and $agentcont_type==1 and $sendstatus==0){
                //购买支付后,扣除代理库存余额 和 代理得到的金额 start
                $agent_inventory = 0;
                $query_promote="select agent_inventory from promoters where status=1 and isvalid=true and user_id=".$agent_id;  //查找代理商代理剩余库存金额
                $result_promote = _mysql_query($query_promote) or die('Query_promote failed: ' . mysql_error());
                while ($row_promote = mysql_fetch_object($result_promote)) {
                    $agent_inventory = $row_promote->agent_inventory;
                }
                if($agent_discount==0){
                    $query_apply="select agent_discount from weixin_commonshop_applyagents where status=1 and isvalid=true and user_id=".$agent_id; //查找代理商代理剩余库存金额
                    $result_apply = _mysql_query($query_apply) or die('Query_apply failed: ' . mysql_error());
                    $agent_discount =0;
                    while ($row_apply = mysql_fetch_object($result_apply)) {
                     $agent_discount = $row_apply->agent_discount;
                    }
                }

                $agent_discount =  $agent_discount/100;
                $agent_cost_inventorymoney = $totalprice * $agent_discount; //从代理金额扣除成本价
                $agent_cost_inventorymoney = round($agent_cost_inventorymoney,2);
                $agent_inventory = $agent_inventory - $agent_cost_inventorymoney;

            /*  if($agent_inventory<0){
                    $json["status"] = 10010;
                    $json["line"] = 118;
                    $json["msg"] = "代理商库存不足,无法发货！";
                    $jsons=json_encode($json);
                    mysql_close($link);
                    die($jsons);

                }    */

                $agent_cost_inventorymoney = 0-$agent_cost_inventorymoney;

                $query_Irecord = "insert into weixin_commonshop_agentfee_records(user_id,batchcode,price,detail,type,isvalid,createtime,after_inventory) values(".$agent_id.",'".$m_batchcode."',".$agent_cost_inventorymoney.",'发货(出库)',1,true,now(),".$agent_inventory.")";
                _mysql_query($query_Irecord);        //插入扣除成本价
                $query_Upromote = "update promoters set agent_inventory=".$agent_inventory." where user_id=".$agent_id;
                _mysql_query($query_Upromote);  //更新库存

            }
            /* 代理商扣除库存 End */
        }
    }



    /**
     * 商城派单方法
     * @param  int $pay_batchcode 支付订单号
     * @param  int $batchcode 订单号
     */
    public function send_order_fun($customer_id,$pay_batchcode,$batchcode=""){
        if($pay_batchcode != ""){
            $search_type = "pay_batchcode"; //如果支付订单号不为空，则查询条件为pay_batchcode
            $search_batchcode = $pay_batchcode;
        }else{
            $search_type = "batchcode";
            $search_batchcode = $batchcode;
        }
        //查品牌代理  品牌代理、f2c、订货系统 。 三者不可同时使用,
		//二维码产品不派单 8.18  zpd
        $agentcont_type = 0;
        $is_QR          = 0;
        $sql_agent = "select agentcont_type,is_QR,store_id from weixin_commonshop_orders where ".$search_type." = '" . $search_batchcode . "' and isvalid = true limit 1";
        $result_agent = _mysql_query($sql_agent);
        $store_id = -1;
        if ($row_agent = mysql_fetch_object($result_agent)) {
            $agentcont_type = $row_agent->agentcont_type;
            $is_QR          = $row_agent->is_QR;
            $store_id = $row_agent -> store_id;
        }
        _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "\r\n0.agentcont_type=======" . var_export($agentcont_type, true) . "\r\n", FILE_APPEND);
        _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "0.sql_agent=======" . var_export($sql_agent, true) . "\r\n", FILE_APPEND);
        _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "0.agentcont_type=======" . var_export($agentcont_type, true) . "--is_QR:".$is_QR, FILE_APPEND);

        $this->zlog_insert($this->log_name,"pay_batchcode:$pay_batchcode sql_agent:$sql_agent\n 不是品牌代理商&不是二维码产品的订单可以进行分派 agentcont_type:$agentcont_type is_QR:$is_QR store_id:$store_id");
        if ($agentcont_type != 1 and $is_QR != 1) { //不是品牌代理商&不是二维码产品的订单可以进行分派 zhaojing
            $is_open_ordering = 0;
            $query = "select isopen_proxy from ".WSY_DH.".orderingretail_setting where customer_id=" . $customer_id . " and isvalid=true  limit 0,1";
            $result = _mysql_query($query) or die('Query failed111: ' . mysql_error());
            if ($row = mysql_fetch_object($result)) {
                $is_open_ordering = $row->isopen_proxy;
            }
            _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "1.is_open_ordering=======" . var_export($is_open_ordering, true) . "\r\n", FILE_APPEND);
            $this->zlog_insert($this->log_name,"pay_batchcode:$pay_batchcode is_open_ordering:$is_open_ordering store_id:$store_id");
            if ($is_open_ordering > 0 && $store_id <= 0) { //有选择原供应商门店的话不做派单操作
                //查找商城batchcode
                $query_o = "select batchcode,supply_id,user_id,o_shop_id,or_shop_type from weixin_commonshop_order_prices where isvalid=true  and " . $search_type . "= '" . $search_batchcode . "'";
                $result_o = _mysql_query($query_o) or die('Query failed222: ' . mysql_error());
                while ($row_o = mysql_fetch_object($result_o)) {
                    $order_batchcode = $row_o->batchcode;
                    $supply_id = $row_o->supply_id;
//                    $agent_id = $row_o -> agent_id;
                    $user_id = $row_o -> user_id;
                    $o_shop_id = $row_o -> o_shop_id;
                    $or_shop_type = $row_o -> or_shop_type;

                    _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "2.pay_batchcode  : " . var_export($batchcode, true) . " batchcode  : " . var_export($order_batchcode, true) . " supply_id : " . var_export($supply_id, true) . "\r\n", FILE_APPEND);
                    $this->zlog_insert($this->log_name,"pay_batchcode:$pay_batchcode 即不是供货商也不是代理商的订单就执行派单 supply_id:$supply_id");
//                    if ($supply_id <= 0) { //即不是供货商也不是代理商的订单就执行派单 zhaojing
                        $ch = curl_init();
                        $url = Protocol . $_SERVER['HTTP_HOST'] . "/addons/index.php/ordering_retail/Ordering_Service/send_shop_order?customer_id=" . $customer_id . "&user_id=" . $user_id . "&batchcode=" . $order_batchcode;
                        if($o_shop_id > 0){
                            $url .= "&shop_id=".$o_shop_id."&or_shop_type=".$or_shop_type;
                        }
                        _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "url  : " . $url . "\r\n", FILE_APPEND);
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        if (Protocol == "https://") {
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                        }
                        $output = curl_exec($ch);
                        _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "5.output=======" . var_export($output, true) . "\r\n", FILE_APPEND);
                        curl_close($ch);
//                        $or_result = json_decode($output);
//                    }
                }
            } else {
            $isopen_f2c = 0;
            $query_f2c = "select isopen_f2c from f2c_setting where id=" . $customer_id . " limit 0,1";
            $result_f2c = _mysql_query($query_f2c) or die('Query failed_f2c: ' . mysql_error());
            if ($row = mysql_fetch_object($result_f2c)) {
                $isopen_f2c = $row->isopen_f2c;
            }
            _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "1.isopen_f2c=======" . var_export($isopen_f2c, true) . "\r\n", FILE_APPEND);
            if ($isopen_f2c > 0) {
                //查找商城batchcode
                _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "2.batchcode=======" . var_export($search_batchcode, true) . "\r\n", FILE_APPEND);
                $query_o = "select batchcode,supply_id,user_id from weixin_commonshop_order_prices where isvalid=true  and ".$search_type."= '" . $search_batchcode . "'";
                $result_o = _mysql_query($query_o) or die('Query failed222: ' . mysql_error());
                while ($row_o = mysql_fetch_object($result_o)) {
                    $order_batchcode = $row_o->batchcode;
                    $supply_id = $row_o->supply_id;
                    $user_id   = $row_o->user_id;
                    _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "3.order_batchcode=======" . var_export($order_batchcode, true) . "\r\n", FILE_APPEND);
//                    $agent_id = $row_o -> agent_id;
                    if ($supply_id <= 0) { //即不是供货商也不是代理商的订单就执行派单 zhaojing
                        $ch = curl_init();
                        $send_url = Protocol . $_SERVER['HTTP_HOST'] . "/addons/index.php/f2c/Ordering_Service/send_shop_order?customer_id=" . $customer_id . "&user_id=" . $user_id . "&batchcode=" . $order_batchcode;
                        _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "4.send_url=======" . var_export($send_url, true) . "\r\n", FILE_APPEND);
                        curl_setopt($ch, CURLOPT_URL, $send_url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        if (Protocol == "https://") {
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                        }
                        $output = curl_exec($ch);
                        _file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/weixinpl/log/systen_order_send_" . date('Y-m-d', time()) . ".txt", "5.output=======" . var_export($output, true) . "\r\n", FILE_APPEND);
//                        $or_result = json_decode($output);
                        curl_close($ch);
                    }
                }
            }
            }

        }
    }


    /*
     * 云店自营订单通知
     * @param  int $batchcode 订单号
     */
    public function yundian_order($customer_id,$pay_batchcode,$weixin_name){
        if(!empty($pay_batchcode)) {
            $sql = "select batchcode,totalprice,yundian_id,yundian_self from weixin_commonshop_orders where pay_batchcode='{$pay_batchcode}'";
            $result = _mysql_query($sql) or die('云店 Query failed: ' . mysql_error());
            $yundian_id = -1;
            $yundian_self = 0;
            $totalprice   = 0;
            $batchcode    = '';
            while ($row = mysql_fetch_object($result)) {
                $yundian_id = $row->yundian_id;
                $yundian_self = $row->yundian_self;
                $totalprice = $row->totalprice;
                $batchcode = $row->batchcode;
            }

            if($yundian_id > 0 && $yundian_self == 1){      //该订单是云店自营的订单，给店主发送消息
                $sql = "select user_id from ".WSY_USER.".weixin_yundian_keeper where isvalid=1 and customer_id=".$customer_id." and id=".$yundian_id;
                $result_keeper = _mysql_query($sql) or die('云店 Query failed: ' . mysql_error());

                $user_id = -1;
                while ($row_keeper = mysql_fetch_object($result_keeper)) {
                    $user_id = $row_keeper->user_id;
                }

                if($user_id > 0){
                    $fromuser_arr	= $this->common->query_openid($customer_id,$user_id);//获取云店店主openid
                    $fromuser       = $fromuser_arr["openid"];

                    $msg_content = "您的云店有一笔付款了！\n下单时间：".date( "Y-m-d H:i:s")."\n客户：".emoji_html_to_unified($weixin_name)."\n订单号：".$batchcode."，订单金额：￥".$totalprice."，请赶快处理吧！\n<a href='".Protocol . $_SERVER['HTTP_HOST']."/mshop/web/index.php?m=yundian&a=yundian_order_manager&type=2&yundian=".$yundian_id."'>立即处理</a> ";

                    $this->common->SendMessage($msg_content, $fromuser, $customer_id);
                }
            }
        }

    }


    /**
     * [camilo_info 卡密相关操作方法]
     * @param  int $customer_id [description]
     * @param  int $pay_batchcode [支付订单号]
     * @param  int $type [模式1为支付，2为发货]
     */
    public function camilo_info($customer_id, $pay_batchcode, $type=1) {
        //根据商家id与订单号和付款状态，查询出该订单号对应的产品及感冒的数量
        $sql = "SELECT id, pid, user_id, rcount, batchcode, camilo_ids FROM weixin_commonshop_orders WHERE customer_id='{$customer_id}' AND pay_batchcode='{$pay_batchcode}' AND paystatus=1 LIMIT 1";
        $res = _mysql_query($sql) or die("CA Counter1 Query failed :".__LINE__.mysql_error());
        //记录日志
        $str = '卡密操作';
        //获取订单信息数组
        $orders_res=mysql_fetch_array($res);
        switch ($type) {
            case 1:
                //日志记录
                $str .= "\n支付操作：支付订单：{$pay_batchcode}\n订单数据：".json_encode($orders_res);
                //根据pid（产品id）查询出相应的产品
                $sql = 'SELECT is_virtual, is_camilo FROM '.WSY_PROD.".weixin_commonshop_products WHERE customer_id='{$customer_id}' AND
        id='{$orders_res['pid']}' LIMIT 1";
                $res = _mysql_query($sql) or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                //获取产品信息转数组
                $products_res=mysql_fetch_array($res);
                //日志记录
                $str .= "\n产品信息：".json_encode($products_res);

                //判断是否是虚拟产品且开启了卡密功能
                if ($products_res['is_virtual']==1 && $products_res['is_camilo']==1) {
                    $str .= "\n此产品是虚拟产品且开启了卡密";
                    //开启则查询可用的卡密
                    $sql = 'SELECT id FROM '.WSY_PROD.".weixin_commonshop_camilo WHERE customer_id='{$customer_id}' AND product_id='{$orders_res['pid']}' AND status=1 AND isvalid=1 limit {$orders_res['rcount']}";
                    $res = _mysql_query($sql) or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                    //获取卡密信息转数组
                    $camilo_res = [];
                    while($row=mysql_fetch_object($res)){
                        $camilo_res[] = $row->id;
                    }
                    //记录日志
                    $str .= "\n可用卡密：".json_encode($camilo_res).',数量为:'.count($camilo_res).'产品数量为:'.$orders_res['rcount'];
                    //判断卡密的数量是否满足订单的数量
                    if (count($camilo_res) >= $orders_res['rcount']) {
                        //记录日志
                        $str .= ',数量满足';
                        //满足修改卡密信息
                        $camilo_res_str= implode(',', $camilo_res);
                        $sql = 'UPDATE '.WSY_PROD.".weixin_commonshop_camilo SET batchcode = {$orders_res['batchcode']}, status = 2 WHERE id in({$camilo_res_str})";
                        $res = _mysql_query($sql) or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                        //成功则插入日志
                        if ($res) {
                            $str .= "\n卡密修改成功(已占用)";
                            $time = date('Y-m-d H:i:s', time());
                            //插入卡密记录日志
                            foreach ($camilo_res as $v) {
                                $sql = 'INSERT INTO '.WSY_PROD.".weixin_commonshop_camilo_log(customer_id,camilo_id,createtime,operation,comment) VALUES('{$customer_id}', '{$v}', '{$time}', '支付', '修改状态为（已占用）')";
                                _mysql_query($sql)or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                            }

                            //修改相应的订单信息
                            $sql = "UPDATE weixin_commonshop_orders SET camilo_ids='{$camilo_res_str}' WHERE id='{$orders_res['id']}'";
                            $res = _mysql_query($sql)or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                            if ($res) {
                                $str .= "\n订单修改成功";
                            }
                        }
                    } else {
                        $str .= ",数量不满足，默认卡密库存不足";
                    }
                } else {
                    $str .= "\n此产品不是虚拟产品或没有开启卡密";
                }
                break;
            case 2:
                //日志记录
                $str .= "\n发货操作：支付订单：{$pay_batchcode}\n订单数据：".json_encode($orders_res);
                //根据订单号记录的卡密id查询卡密
                if (empty($orders_res['camilo_ids'])) {
                    $str .= "\n对应的卡密id号为：空，不操作";
                } else {
                    $str .= "\n对应的卡密id号为：{$orders_res['camilo_ids']}";
                    $sql = 'UPDATE '.WSY_PROD.".weixin_commonshop_camilo SET status = 3 WHERE customer_id='{$customer_id}'
                    AND product_id='{$orders_res['pid']}' AND isvalid=1 AND batchcode='{$orders_res['batchcode']}' AND id in({$orders_res['camilo_ids']})";
                    $str .= "\n卡密修改sql:{$sql}";
                    $res = _mysql_query($sql) or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                    if ($res) {
                        $str .= "\n卡密修改成功(已使用)";
                        //查询卡密号
                        $sql = 'SELECT camilo FROM '.WSY_PROD.".weixin_commonshop_camilo WHERE customer_id='{$customer_id}' AND product_id='{$orders_res['pid']}' AND status=3 AND isvalid=1 AND batchcode='{$orders_res['batchcode']}' AND id in({$orders_res['camilo_ids']})";
                        $str .= "\n卡密查询sql:{$sql}";
                        $res = _mysql_query($sql) or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                        //获取卡密信息转数组
                        $shop_camilo = [];
                        while($row=mysql_fetch_object($res)){
                            $shop_camilo[] = $row->camilo;
                        }
                        $shop_camilo_str = '';
                        foreach ($shop_camilo as $v) {
                            $shop_camilo_str .= "\n".stripslashes(htmlspecialchars_decode($v));
                        }
                        $c_id = passport_encrypt($customer_id);
                        $msg_content = "亲，您的订单已发货：\n点击蓝色区域可以跳转订单详情查看\n您的订单<a href='/weixinpl/mshop/orderlist_detail.php?customer_id={$c_id}&batchcode={$orders_res['batchcode']}'>{$orders_res['batchcode']}</a>已发货\n产品卡密是：{$shop_camilo_str}\n可点击订单号查看订单详情";
                        $str .= "\n推送信息:\n{$msg_content}";
                        //查询订单用户的微信id
                        $sql = 'SELECT weixin_fromuser FROM '.WSY_USER.".weixin_users WHERE customer_id='{$customer_id}' AND
        id='{$orders_res['user_id']}' LIMIT 1";
                        $res = _mysql_query($sql) or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                        //获取产品信息转数组
                        $user_res=mysql_fetch_array($res);
                        $str .= "\n用户信息：".json_encode($user_res);
                        //推送消息
                        $this->common->SendMessage($msg_content, $user_res['weixin_fromuser'], $customer_id);
                        $time = date('Y-m-d H:i:s', time());
                        //切割字符串为数组
                        $camilo_ids = explode(',', $orders_res['camilo_ids']);
                        //插入卡密记录日志
                        foreach ($camilo_ids as $v) {
                            $sql = 'INSERT INTO '.WSY_PROD.".weixin_commonshop_camilo_log(customer_id,camilo_id,createtime,operation,comment) VALUES('{$customer_id}', '{$v}', '{$time}', '发货', '修改状态为（已使用）')";
                            _mysql_query($sql)or die("CA Counter1 Query failed :".__LINE__.mysql_error());
                        }
                    }
                }
                break;
            default:
                $str .= "\ntype属性错误";
                break;
        }
        $LogOpe = new LogOpe('handle_shop_order');
        $LogOpe->log_insert($str);
    }
    /*
     * 区块链支付日志
     * @param int $customer_id
     * @pay_batchcode varchar 支付订单号
     * @paytype varchar 支付类型
     * @real_pay_price float 区块链支付的金额 
     */
    public function block_chain_log($customer_id,$pay_batchcode,$paytype,$real_pay_price){
        if($paytype == '区块链积分支付' && !empty($pay_batchcode)){
            if(empty($real_pay_price) || $real_pay_price < 0){
                $real_pay_price = 0;
            }
            $sql = "select batchcode,user_id from weixin_commonshop_orders where pay_batchcode='".$pay_batchcode."'";
            $result = _mysql_query($sql) or die('区块链积分支付 Query failed: ' . mysql_error());
            $user_id   = 0;
            $batchcode    = '';
            while ($row = mysql_fetch_object($result)) {
                $batchcode = $row->batchcode;
                $user_id   = $row->user_id;
            }
            $remark = '【区块链支付】 用户'.$user_id.'使用了'.$real_pay_price.'积分';
            $query_log = "insert into ".WSY_SHOP.".block_chain_log(customer_id,user_id,status,batchcode,reward,remark,createtime) values('".$customer_id."',".$user_id.",2,'".$batchcode."','".$real_pay_price."','".$remark."',now())";
            _mysql_query($query_log) or die('插入订单操作日志 Query failed: ' . mysql_error());

            $update_log = "update ".WSY_SHOP.".block_chain_order_detail set order_status = 1,run_num = 3 where customer_id = '".$customer_id."' and batchcode = '".$batchcode."'";
            _mysql_query($update_log) or die('更新订单日志 Query failed: ' . mysql_error());
        }

    }
    /*
     * 更新区块链积分领取明细
     * @param int $customer_id
     * @pay_batchcode varchar 支付订单号
     */
    public function update_block_chain_order($customer_id,$pay_batchcode){
        $sql = "select batchcode,is_block_chain from weixin_commonshop_order_prices where pay_batchcode='".$pay_batchcode."'";
        $result = _mysql_query($sql) or die('区块链积分支付 Query failed: ' . mysql_error());
        $is_block_chain = 0;
        $batchcode      = '';
        while ($row = mysql_fetch_object($result)) {
            $batchcode = $row->batchcode;
            $is_block_chain   = $row->is_block_chain;
        }
        if($is_block_chain == 1)
        {
            $update_log = "update ".WSY_SHOP.".block_chain_order_detail set order_status = 1 where customer_id = '".$customer_id."' and batchcode = '".$batchcode."'";
            _mysql_query($update_log) or die('更新订单日志 Query failed: ' . mysql_error());
        }
    }
}
?>