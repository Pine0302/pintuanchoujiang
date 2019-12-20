<?php

class create_shop_order
{
    public $customer_id;
    public $customer_id_en;
    public $user_id;
    public $pay_batchcode;
    public $current_time;
    public $current_datetime;

    //数据库操作类
    protected $db;

    protected $configutil;
    protected $shopMessage;
    protected $slyder_adventures;
    protected $model_integral;
    protected $shop_4m;
    protected $model_selfbuy_reward;
    protected $collageActivities;
    protected $shop_pay;
    protected $model_restricted_purchase;
    protected $orderFormExtend;
    protected $qr_Utlity;
    protected $HyOrder_reward = null; //订单奖励

    public function __construct($customer_id, $user_id, $current_time) {
        $this->customer_id      = $customer_id;
        $this->customer_id_en   = passport_encrypt($customer_id);
        $this->user_id          = $user_id;
        $this->current_time     = $current_time;
        $this->current_datetime = date('Y-m-d H:i:s', $current_time);

        $this->db                       = DB::getInstance();
        $this->configutil               = new ConfigUtility();
        $this->shopMessage              = new shopMessage_Utlity();
        $this->model_slyder_adventures  = new model_slyder_adventures();
        $this->model_integral           = new model_integral();
        $this->shop_4m                  = new Utiliy_4m_new($this->customer_id);
        $this->model_selfbuy_reward     = new model_selfbuy_reward();
        //初始化佣金抵扣
        $this->model_selfbuy_reward->selfbuycal_new_first($this->customer_id);
        $this->collageActivities        = new collageActivities($this->customer_id);
        $this->shop_pay                 = new shop_pay($this->customer_id);
        $this->model_restricted_purchase= new model_restricted_purchase();
        $this->orderFormExtend          = new orderFormExtend($this->customer_id, $this->user_id, 1);
        $this->qr_Utlity                = new qr_Utlity();

        if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/mshop/web/controller/HyOrder_reward.php') ){
            require_once($_SERVER['DOCUMENT_ROOT'] . '/mshop/web/controller/HyOrder_reward.php');
            $this->HyOrder_reward = new control_HyOrder_reward($this->customer_id);
        }

    }

    public function create_shop_order($orderparam, $shop_setting, $user_data) {
        extract($orderparam);
        extract($shop_setting);
        extract($user_data);
        if($this->user_id <= 0 || $this->user_id == NULL || $this->user_id == '')
        {
            $json["status"] = 10004;
            $json["msg"]    = "用户id异常，请重新登录";
            $json["user_id"] = $this->user_id;
            $jsons = json_encode($json);
            die($jsons);
        }
        //开始事务
        $this->db->tran_begin();

        //生成支付订单号
        $this->create_pay_batchcode();

        $allPerNeedScore    = 0;        //总所需积分
        $allProdWeight      = 0;        //总产品重量
        $allProdNum         = 0;        //总产品数量
        $allTotalprice      = 0;        //总产品金额
        $allShopTotalprice  = 0;        //总订单金额
        $sum_currency       = 0;        //使用购物币数量
        $batchcode_arr      = array();  //订单号数组
        $allProdName        = array();  //所有产品名称
        $allRewardMoney     = 0;        //总佣金

        // var_dump($orderparam);

        if(empty($yundian_id)){
            $yundian_id     = -1;
        }

        //先遍历一次供应商订单，查询下单的云店环境    ，-1表示在平台下的
        // foreach($order_data as $order_num => $supply_order) {
        //     $product_info_arr = $supply_order[1];         //订单产品信息
        //     foreach($product_info_arr as $product_num => $product_data) {
        //         if($product_data[12] != -1){
        //             $yundian_id = $product_data[12];
        //         }
        //     }
        // }

        //遍历供应商订单
        foreach($order_data as $order_num => $supply_order) {       //  supply_order=>商品信息
            $opi_array      = array();                  //产品运费信息数组
            $supply_id      = $supply_order[0];         //供应商id
            $product_info   = $supply_order[1];         //订单产品信息
            $remark         = $supply_order[2];         //订单备注
            // $identity       = $supply_order[3];         //身份证号码
            $store_id       = $supply_order[4] ? : -1;  //门店id     
            $store_name     = $supply_order[5];         //门店名字
            $invoice_head   = $supply_order[6];         //发票抬头
            $userCoupon_id  = $supply_order[7];         //用户优惠券id
            $bargain_data   = $supply_order[9];         //众筹活动数据
            $crowdfund_data = $supply_order[10];        //砍价活动数据
            $o_store_id     = $supply_order[11] ? : -1; //订货系统门店id
            $o_branch_id    = $supply_order[12] ? : -1; //订货系统子门店id
            $send_way       = $supply_order[13] ? : 1;  //快递/自提  1快递 2自提

            //订单保存信息
            $reward_money           = 0;    //订单佣金
            $order_back_currency    = 0;    //返购物币数量
            $is_delivery_order      = $is_delivery_order ? $is_delivery_order : 0;    //是否货到付款订单
            $is_identity_order      = 0;    //是否身份证订单
            $is_integral_order      = 0;    //是否是兑换/积分产品订单
            $sum_give_integral      = 0;    //总赠送积分
            $sum_cost_integral      = 0;    //总兑换积分
            $is_restricted_purchase = 0;    //是否限购订单
            $restricted_purchase_id = -1;   //限购活动编号
            $is_collageActivities   = 0;    //0:不是拼团订单、1：拼团有效订单、2：拼团无效订单 3积分兑换订单
            $is_collageActivities_stock = 0;//库存回收表拼团订单标识，0:不是拼团订单、1：拼团订单
            $origin_totalprice      = 0;    //原订单的订单总额(包括运费+行邮税)（不计算优惠:会员卡折扣、优惠劵、购物币）
            $AgentCostMoney         = 0;    //订单代理商结算金额
            $SupplyCostMoney        = 0;    //订单总供应商供货价
            $SupplyForMoney         = 0;    //订单总供应商成本价
            $shopPerNeedScore       = 0;    //订单所需积分
            $total_vpscore          = 0;    //订单返vp值
            $cardReward_score       = 0;    //订单返会员卡积分
            $total_charitable       = 0;    //订单慈善公益金额
            $noExpPrice             = 0;    //订单总价（不加运费） 优惠后（会员卡折扣、优惠劵）
            $shopTotalprice         = 0;    //订单总价（+运费） 实付订单总金额(包括运费+行邮税) 优惠后（会员卡折扣、优惠劵）
            $tax_num                = 0;    //行邮税产品个数
            $total_expressprice     = 0;    //订单运费
            $is_suning_order        = true; //是否苏宁订单

            $yundian_reward         = 0;    //云店的分佣比例
            $yun_commission         = 0;    //平台对云店自营产品的抽成比例

            $supply_yundian_id      = empty($supply_order[1][0][12])?-1:$supply_order[1][0][12];//当前店铺的云店ID   -1:平台或供应商商品  大于0：云店自营产品
            $recovery_time_show     = '';
            $is_block_chain         = 0;    //是否是区块链订单
            $block_chain_status     = 0;    //是否领取了区块链积分
            $block_chain_reward     = 0;    //区块链积分
            $block_chain_valid_time = '';   //区块链积分有效期 
            $sum_block_chain_reward = 0;    //总区块链积分
            $temp_revenue = array();        //行邮税数组
            $yundian_setting = array();
            if($supply_yundian_id != -1 && $supply_yundian_id > 0 && !empty($supply_yundian_id)){
                $yundian_setting = $this->find_yundian_setting($supply_yundian_id);

                if($yundian_setting['yundian_identity']['self_reware'] < 0){
                    $yun_commission = 1; //平台对云店自营产品的抽成比例
                }else{
                    $yun_commission = 1 - $yundian_setting['yundian_identity']['self_reware']; //平台对云店自营产品的抽成比例
                }
                
                $yundian_self_reware = $yundian_setting['yundian_identity']['self_reware']; //平台对云店自营产品的抽成比例
                $yundian_shop_reware = $yundian_setting['yundian_identity']['profit_shop']; //店主对平台产品的身份奖励比例
            }

            //订单失效时间
            $recovery_time_show = $recovery_time;
            if($supply_yundian_id != -1 && $supply_yundian_id > 0 && !empty($supply_yundian_id)){
                $recovery_time_show = $yundian_recovery_time;       //云店自营产品的失效时间
            }

            //订单号
            if($supply_id > 0) {
                $batchcode = $this->pay_batchcode . $supply_id;
            } else {
                $batchcode = $this->pay_batchcode . '0';
            }

            $batchcode_arr[$order_num]['batchcode'] = $batchcode;

            //发票抬头
            $this->invoice_head($invoice_head, $batchcode);

            //订货系统门店
            $or_shop_type = 1;  //扫码购物
            if(!empty($or_shop_type_ex) && !empty($o_shop_id_ex)) {
                if($or_shop_type_ex == 1) {
                    $o_store_id = $o_shop_id_ex;
                } elseif ($or_shop_type_ex == 2) {
                    $o_branch_id = $o_shop_id_ex;
                }
            }
            if($o_store_id > 0 || $o_branch_id > 0) {
                if($o_store_id > 0) {
                    $o_shop_id = $o_store_id;
                    $or_shop_type = 1;
                } else {
                    $o_shop_id = $o_branch_id;
                    $or_shop_type = 2;
                    $send_way = 2; //子门店只能自提
                }
            }

            //发货方式
            $sendstyle = "快递";
            if($store_id > 0) {
                $sendstyle = "自提";
            }

            if($o_shop_id > 0 && $send_way == 2) {
                $sendstyle = "门店自提";
            }

            $c_product_info = count($product_info);
            $c_add          = 1;

            //积分赠送活动，如果最后一个不是赠送产品导致不赠送
            foreach($product_info as $product_num => $product_data) 
            {
                $act_type_arr[] = $product_data[8];     //活动类型数组
            }


            //遍历产品信息
            foreach($product_info as $product_num => $product_data) {
                    $prod_id            = $product_data[0];     //产品id
                $prod_prosid        = $product_data[1];     //产品属性
                $prod_num           = $product_data[2];     //产品数量
                $prod_num = (int)$prod_num;
                if( $prod_num < 1){
                    $prod_num           = 1;
                }
                $information        = $product_data[4];     //必填信息
                $mb_topic           = $product_data[6];     //电商直播标识
                $check_first_extend = $product_data[7] ? : $check_first_extend;//首次推广奖励
                $act_type           = $product_data[8];     //活动类型
                $act_id             = $product_data[9];     //活动id
                $exchange_act_id    = $product_data[10] ? : -1;//换购活动id
                $exchange_id    = $product_data[10] ? : -1;//换购活动id
                $is_exchange_goods  = $product_data[11];    //判断是否为换购产品  0：不是；1： 是
                // $send_way           = $product_data[13] ? : 1;//快递/自提  1快递 2自提

                $pro_yundian_id     = $product_data[12] ? : -1;//-1:平台或供应商商品  大于0：云店自营产品

                $yundian_self = 0;          //是否为云店的自营订单
                if($pro_yundian_id == $yundian_id && $yundian_id != -1){
                    $yundian_self = 1;
                }   

                //订单产品信息
                $totalprice = 0;    //单件产品金额

                //获取产品信息
                $prod_info = $this->get_product_info($prod_id);
                // var_dump($prod_info);
                extract($prod_info);

                $allProdName[] = $prod_name;

                
                //满赠活动
                if($is_exchange_goods  && $product_yundian_id == -1) {
                    $act_type   = '';
                    $act_id     = '';

                    $exchange_arr = array(
                        'pid'               => $prod_id,
                        'prod_name'         => $prod_name,
                        'prod_num'          => $prod_num,
                        'exchange_act_id'   => $exchange_act_id
                    );
                    //判断库存和获取产品活动价格
                    $exchange_info = $this->exchange_act($exchange_arr);
                    extract($exchange_info);
                }

                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //判断活动产品是否已下架
                if(($isout || !$isvalid) && $act_id > 0 && $act_type > 0) {
                    $json["status"] = 10010;
                    $json["msg"]    = $prod_name . "已下架";
                    die(json_encode($json));
                }
                }

                //获取属性产品
                $prod_pros_id = -1;
                if($is_exchange_goods && $product_yundian_id == -1) {
                }else{
                    if(!empty($prod_prosid)) {
                        $prod_pros_info = $this->get_product_pros_info($prod_id, $prod_prosid);
                        extract($prod_pros_info);

                        //判断活动产品是否已下架
                        // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/AAABBB.TXT','AAA:'.json_encode($prod_pros_info).'--BBB:'.$prod_id.'---CCC:'.$prod_prosid.'--DDD:'.$act_type.'--EEE:'.$act_id.PHP_EOL,FILE_APPEND);    
                        
                        // if(!empty($prod_pros_id) && $act_id > 0 && $act_type > 0) {
                        //     $json["status"] = 10011;
                        //     $json["msg"]    = $prod_name . "已下架";
                        //     die(json_encode($json));
                        // }
                        if(empty($prod_pros_id) && $act_id > 0 && $act_type > 0) {
                            $json["status"] = 10011;
                            $json["msg"]    = $prod_name . "已下架";
                            die(json_encode($json));
                        }
                    }
                }

                //获取属性字符串
                $prod_pros_name = $this->get_pros_str($prod_prosid);
 
                //是否苏宁订单
                if($skuid < 0) {
                    $is_suning_order = false;
                }

                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //虚拟产品不需要邮费和身份证验证
                if($is_virtual) {
                    $is_free_shipping = 1;
                    $prod_is_identity = 0;
                }

                //身份证订单
                if($is_identity && $prod_is_identity) {
                    $is_identity_order = 1;
                }

                //返购物币
                if($is_currency) {
                    $order_back_currency += $back_currency * $prod_num;
                }
                }

                //是否支持货到付款
                $is_delivery_order = $this->check_delivery($is_delivery_order, $prod_id);

                //电商直播订单
                $is_mb_order = $this->mb_order($mb_topic, $batchcode);
                //var_dump($pro_is_block_chain);exit;

                //是否区块链积分产品
                if($pro_is_block_chain == 1){
                    $block_chain_info = $this->block_chain_info($now_price,$block_chain_type,$block_chain_bfb,$block_chain_money);
                    extract($block_chain_info);
                    $sum_block_chain_reward += $block_chain_reward* $prod_num;
                }
                //不符合首次推广奖励
                if(1 != $is_first_extend) {
                    $check_first_extend = 0;
                }

                //活动产品
                $product_act_arr = array(
                    'pid'               => $prod_id,
                    'prod_prosid'       => $prod_prosid,
                    'act_type'          => $act_type,
                    'act_id'            => $act_id,
                    'batchcode'         => $batchcode,
                    'recovery_time'     => $recovery_time_show,
                    'now_price'         => &$now_price,
                    'totalprice'        => &$totalprice,
                    'bargain_data'      => $bargain_data,
                    'crowdfund_data'    => $crowdfund_data,
                    'is_collage_product'=> $is_collage_product,
                    'group_buy_type'    => $group_buy_type,
                    'activitie_id'      => $activitie_id,
                    'prod_num'          => $prod_num
                );
                
                if($product_num == ($c_product_info-1)) //当便利最后一次
                {   
                    $last_pro_act = 0;
                    foreach ($act_type_arr as $a => $b) 
                    {
                        if($b == 21) $last_pro_act = 1; break;
                    }
                }    

                $pro_info_ar      = array($c_product_info,$c_add,$last_pro_act); //用于消费返积分，防止多次插入
                $product_act_info = $this->product_act($product_act_arr,$prod_num,$pro_info_ar);
                $c_add++;

                extract($product_act_info);

                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //赠送积分
                if($consume_integral > 0) {
                    $sum_give_integral += $consume_integral;
                }

                //兑换积分
                if($cost_integral > 0) {
                    $sum_cost_integral += $cost_integral;
                }
                }

                //检验产品库存
                $prod_strorenum_arr = array(
                    'pid'           => $prod_id,
                    'prod_pros_id'      => $prod_pros_id,
                    'prod_prosid'       => $prod_prosid,
                    'prod_num'          => $prod_num,
                    'storenum'          => $storenum,
                    'act_type'          => $act_type,
                    'act_id'            => $act_id,
                    'o_store_id'        => $o_store_id,
                    'o_branch_id'       => $o_branch_id,
                    'or_shop_type_ex'   => $or_shop_type_ex,
                    'is_exchange_goods' => $is_exchange_goods,
                    'is_collageActivities' => $is_collageActivities,
                    'is_recovery_storenum' => $is_recovery_storenum,
                    'activitie_id'      => $activitie_id,
                    'is_4m'             => $is_4m,
                    'create_type'       => $create_type,
                    'recovery_time'     => $recovery_time_show,
                    'batchcode'         => $batchcode
                );
                $this->check_prod_storenum($prod_strorenum_arr);

                //续费产品
                if($is_promoter_permanent) {
                    $this->promoter_permanent($batchcode, $prod_id, $prod_num);
                }

                //是否品牌订阅复购价
                if ($brand_return==1) {
                    $now_price = $brand_price;
                }
                $perNeedScore       = $need_score;
                $totalprice         = $now_price * $prod_num;       //产品总额
                $origin_totalprice  += $totalprice;
                $tax_totalprice     = $totalprice;                  //不打折的产品总额
                $perNeedScore       = (float)$perNeedScore * (float)$prod_num;    //产品所需积分
                $shopPerNeedScore   += $perNeedScore;
                $allPerNeedScore    += $perNeedScore;
                $allProdWeight      += $weight * $prod_num;
                $allProdNum         += $prod_num;
                $allTotalprice      += $totalprice;

                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                /*开启重量限制开始*/
                if($is_weight_limit){
                    $errorMsg = '';
                    $user_today_weight += $weight;
                    if($is_identity && $prod_is_identity) {
                        $identity_today_weight += $weight;
                        if($identity_today_weight > $per_weight_limit) {
                            $errorMsg = "每个身份证号每天最多只可购买总重:".$per_weight_limit."KG的商品";
                        }
                    } else {
                        if($user_today_weight > $per_weight_limit) {
                            $errorMsg = "每个用户每天最多只可购买总重:".$per_weight_limit."KG的商品";
                        }
                    }

                    if($errorMsg != '') {
                        $json["status"] = 10007;
                        $json["msg"] = $errorMsg;
                        $jsons=json_encode($json);
                        die($jsons);
                    }
                }
                /*开启重量限制结束*/

                /*开启购买金额限制开始*/
                if($is_cost_limit){
                    $errorMsg = '';
                    $user_today_totalprice += $totalprice;
                    if($is_identity && $prod_is_identity) {
                        $identity_today_totalprice += $totalprice;
                        if($identity_today_totalprice > $per_cost_limit) {
                            $errorMsg = "每个身份证号每天最多只可购买总额:".$per_cost_limit."元的商品";
                        }
                    } else {
                        if($user_today_totalprice > $per_cost_limit) {
                            $errorMsg = "每个用户每天最多只可购买总额:".$per_cost_limit."元的商品";
                        }
                    }

                    if($errorMsg != '') {
                        $json["status"] = 10008;
                        $json["msg"] = $errorMsg;
                        $jsons=json_encode($json);
                        die($jsons);
                    }
                }
                /*开启购买金额限制结束*/

                /*开启买产品数量限制开始*/
                if($is_number_limit){
                    $errorMsg = '';
                    $user_today_prod_num += $prod_num;
                    if($is_identity && $prod_is_identity) {
                        $identity_today_prod_num += $prod_num;
                        if($identity_today_prod_num > $per_number_limit) {
                            $errorMsg = "每个身份证号每天最多只可购买:".$per_number_limit."件商品";
                        }
                    } else {
                        if($user_today_prod_num > $per_number_limit) {
                            $errorMsg = "每个用户每天最多只可购买:".$per_number_limit."件商品";
                        }
                    }

                    if($errorMsg != '') {
                        $json["status"] = 10009;
                        $json["msg"] = $errorMsg;
                        $jsons=json_encode($json);
                        die($jsons);
                    }
                }
                /*开启买产品数量限制结束*/

                if(1 == $isvp) {
                    $vp_score       = $vp_score * $prod_num;        //单个产品总vp值
                    $total_vpscore  += $vp_score;                   //总vp值
                }
                }

                $reward_score = 0;
                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //计算会员卡返多少积分
                if($is_exchange_goods != 1) {   //换购产品不返积分
                    $reward_score       = $totalprice * $consume_score;
                   // $cardReward_score   += $reward_score;
                } else {
                    $reward_score = 0;
                }
                }

                $charitable = 0;
                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //计算慈善公益
                if($is_charitable) {
                    if($donation_rate < $charitable_propotion) {
                        $donation_rate = $charitable_propotion;
                    }
                    $charitable = $totalprice * $donation_rate;
                    $charitable = round($charitable, 2);
                    $total_charitable += $charitable;
                } else {
                    $charitable = 0;
                }
                }

                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //代理商
                if($agent_id > 0) {
                    //如果产品的代理折扣没有设置，则用代理商原来的折扣
                    if($agent_discount == 0) {
                        $sql = "SELECT agent_discount FROM weixin_commonshop_applyagents WHERE status=1 AND isvalid=TRUE AND user_id='{$agent_id}'";
                        $agent_discount = $this->db->getOne($sql) ? : 0;

                    }
                    $agent_discount = $agent_discount / 100;

                    $AgentCostMoney = $AgentCostMoney + $now_price * $prod_num * $agent_discount;

                    $agentcont_type = 1;
                }
                }

                //计算供应商成本价和供货价
                $Supply_OnlyCostMoney = 0; //供应商的单个产品总成本价
                $forMoney_only        = 0; //单个产品总成本价
                //$SupplyForMoney       = 0;
                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                if($supply_id > 0) {
                    $Supply_OnlyCostMoney   = $cost_price * $prod_num;      //供应商的单个产品总供货价
                    $SupplyCostMoney        += $Supply_OnlyCostMoney;       //供应商的总供货价
                }
                $forMoney_only  = $for_price * $prod_num;                   //单个产品总成本价
                if($forMoney_only < $Supply_OnlyCostMoney) {
                    $forMoney_only  = $Supply_OnlyCostMoney;
                }
                $SupplyForMoney += $forMoney_only;
                }

                if($product_yundian_id == -1 || empty($product_yundian_id)){                 //云店自营产品不插入供应商ID
                    $supply_id_order_show = $supply_id;
                }else{
                    $supply_id_order_show = -1;
                }

                //是否品牌订阅复购价
                if ($brand_return==1) {
                    $shopactivity_mark = 3;
                }
                // $exchange_id    = $_SESSION['exchange_id_'.$this->user_id];

                if ($_SESSION['exp_user_id_' . $this->customer_id] == '')  //添加分享产品用户ID
                {
                    $share_user_id = '-1';
                }
                else if ($_SESSION['exp_user_id_' . $this->customer_id] > 0)
                {
                    $share_user_id = $_SESSION['exp_user_id_' . $this->customer_id];
                }
                else
                {
                    $share_user_id = '-1';
                }

                //保存产品订单
                $save_prod_order = array(
                    'fromuser_app'          => $fromuser_app,
                    'user_id'               => $this->user_id,
                    'pid'                   => $prod_id,
                    'pname'                 => $prod_name,
                    'rcount'                => $prod_num,
                    'totalprice'            => $tax_totalprice,
                    'remark'                => $remark,
                    'isvalid'               => TRUE,
                    'createtime'            => $this->current_datetime,
                    'status'                => 0,
                    'customer_id'           => $this->customer_id,
                    'paystyle'              => '',
                    'sendstyle'             => $sendstyle,
                    'prvalues'              => $prod_prosid,
                    'prvalues_name'         => $prod_pros_name,
                    'sendtime'              => $sendtime,
                    'express_id'            => -1,
                    'batchcode'             => $batchcode,
                    'pay_batchcode'         => $this->pay_batchcode,
                    'expressname'           => '',
                    'card_member_id'        => $card_member_id,
                    'address_id'            => -1,
                    'exp_user_id'           => $exp_user_id,
                    'need_score'            => $perNeedScore,
                    'supply_id'             => $supply_id_order_show,
                    'agent_id'              => $agent_id,
                    'agentcont_type'        => $agentcont_type,
                    'identity'              => $identity_info['identity'],
                    'weight'                => $weight,
                    'is_QR'                 => $is_QR,
                    'is_payother'           => false,
                    'store_id'              => $store_id,
                    'store_name'            => $store_name,
                    'AgentCostMoney'        => $AgentCostMoney,
                    'SupplyCostMoney'       => $Supply_OnlyCostMoney,
                    'baseCostMoney'         => $forMoney_only,
                    'identity_order'        => $identity_order,
                    'reward_score'          => $reward_score,
                    'charitable'            => $charitable,
                    'is_first_extend'       => $check_first_extend,
                    'extend_money'          => $extend_money,
                    'mb_order'              => $is_mb_order,
                    'delivery_time_start'   => $delivery_time_start,
                    'delivery_time_end'     => $delivery_time_end,
                    'is_collageActivities'  => $is_collageActivities,
                    'is_pay_on_delivery'    => $is_delivery_order,
                    'train_parameter'       => '',
                    'train_callback'        => false,
                    'is_receipt'            => $is_receipt,
                    'is_open_aftersale'     => $is_open_aftersale,
                    'cost_integral'         => $cost_integral,
                    'give_integral'         => $consume_integral,
                    'shopactivity_mark'     => $shopactivity_mark,
                    'is_restricted_purchase' => $is_restricted_purchase,
                    'restricted_purchase_id' => $restricted_purchase_id,
                    'is_exchange_goods'     => $is_exchange_goods,
                    'exchange_id'           => $exchange_id,
                    'exchange_act_id'       => $exchange_id,
                    'yundian_id'            => $yundian_id,
                    'yundian_self'          => $yundian_self,
                    'shop_cost_price'       => $cost_price,
                    'share_user_id'         =>$share_user_id
                );
                if ($brand_return!="" && $brand_return!=1 && $brand_return!=0) {
                    $save_prod_order['bs_relate_prod_id'] = $bs_relate_prod_id;//第一次购买标记
                    //$save_prod_order['shopactivity_mark'] = 3;
                }
                //是否品牌订阅复购价
                if ($brand_return==1) {
                    $cheack_auth = $this->cheack_brand_auth($bs_relate_prod_id,$prod_num,$prod_id);
                    if ($cheack_auth['status']!=1) {
                    die(json_encode($cheack_auth));
                    }
                    $save_prod_order['bs_relate_prod_id'] = $bs_relate_prod_id;
                }


                //判断是否开启了卡密功能
//                if($is_virtual==1&&$is_camilo==1) {
//                    //开启则添加订单卡密信息
//                    //查询这间商品的可用卡密
//                    $camilo_id_sql = 'SELECT id FROM '.WSY_PROD.".weixin_commonshop_camilo WHERE customer_id='{$this->customer_id}' AND product_id='{$prod_id}' AND status=1 AND isvalid=1 limit {$prod_num}";
//                    $camilo_id = implode(',', array_column(json_decode(json_encode($this->db->getAll($camilo_id_sql)), true), 'id'));
//                    $camilo_save_sql = 'UPDATE '.WSY_PROD.".weixin_commonshop_camilo SET batchcode = {$batchcode}, status = 2 WHERE id in({$camilo_id})";
//                    $camilo_save_sql_res = _mysql_query($camilo_save_sql) or die(' Query failed: ' . mysql_error());
//                    //记录日志
//                    if ($camilo_save_sql_res) {
//                        $camilo_save_log_time = date('Y-m-d H:i:s', time());
//                        //插入卡密记录日志
//                        $camilo_save_log_id = explode(',', $camilo_id);
//                        foreach ($camilo_save_log_id as $v) {
//                            $camilo_save_log_sql = 'INSERT INTO '.WSY_PROD.".weixin_commonshop_camilo_log(customer_id,camilo_id,createtime,operation,comment) VALUES('{$this->customer_id}', '{$v}', '{$camilo_save_log_time}', '修改', '修改卡密，修改状态为2（已占用），记录订单号：{$batchcode}，修改时间：{$camilo_save_log_time}')";
//                            _mysql_query($camilo_save_log_sql)or die('L445 : Query failed321: ' . mysql_error());
//                        }
//                        //为生成的订单添加占用的卡密id
//                        $save_prod_order['camilo_ids'] = $camilo_id;
//                    }
//                }

                $this->db->autoExecute('weixin_commonshop_orders', $save_prod_order, 'insert');
                
                //835需求，开启复购记录用户购买时的身份，分佣时按照购买的身份进行
                if($issell_model == 2) {
                    //保存购买时的身份
                    $promoter_id = -1;
                    $commision_level = 1;//推广员奖励等级
                    $is_consume = 0;
                    $u_commision_level = 0;
                    $u_is_consume = 0;
                    $term_of_validity = '3000-01-01 00:00:00';//推广员有效期
                    $query_promoters = "SELECT
                                    id,
                                    commision_level,
                                    is_consume,
                                    term_of_validity
                                FROM
                                    promoters
                                WHERE
                                    STATUS = 1
                                AND isvalid = TRUE
                                AND user_id = '".$this->user_id."'
                                LIMIT 0,1";
                    $result_promoters = _mysql_query($query_promoters) or errorResult($result, 15002);
                    while ($row_pro = mysql_fetch_object($result_promoters)) {
                        $promoter_id = $row_pro->id;
                        $commision_level = $row_pro->commision_level;
                        $is_consume = $row_pro->is_consume;
                        $term_of_validity = $row_pro->term_of_validity;
                    }
                    
                    if ($promoter_id > 0) {
                        $term_of_validity = new DateTime($term_of_validity);
                        if ($term_of_validity->format("U") >= time()) {
                            $u_commision_level = $commision_level;
                            $u_is_consume = $is_consume;
                        }else{
                            //推广员身份已过期
                            $u_commision_level = 0;
                            $u_is_consume = 0;
                        }
                    }else{
                        //不是推广员
                        $u_commision_level = 0;
                        $u_is_consume = 0;
                    }
                    
                    $save_user_level = array(
                        'isvalid'               => TRUE,
                        'createtime'            => $this->current_datetime,
                        'batchcode'             => $batchcode,
                        'commision_level'       => $u_commision_level,
                        'is_consume'            => $u_is_consume,
                    );
                    
                    $this->db->autoExecute('weixin_commonshop_order_userlevel', $save_user_level, 'insert');
                }

                //品牌订阅首次购买需更改权限表
                if ($brand_return>1) {
                    $brand_activity_id = $brand_return-1;
                    $select_authorize_id = "SELECT id FROM ".WSY_MARK.".brandsubscribe_authorize WHERE customer_id='{$this->customer_id}' and user_id='{$this->user_id}' and isvalid=true and activity_id='{$brand_activity_id}'";
                    $authorize_id = $this->db->getOne($select_authorize_id);
                    $first_buy = array(
                        'customer_id' => $this->customer_id, 
                        'user_id' => $this->user_id, 
                        'activity_id' => $brand_activity_id, 
                        'authorize_id' => $authorize_id, 
                        'bs_relate_prod_id' => $bs_relate_prod_id, 
                        'createtime' => $this->current_datetime, 
                        'isvalid' => true, 
                        );
                   $this->db->autoExecute(WSY_MARK.'.brandsubscribe_first_buy',$first_buy, 'insert');
                }

                //会员卡折扣(赠送产品不进行打折)
                $card_discount_t = 0;
                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                if($card_member_id > 0 && $is_select_card > 0 ){
                    if ($card_discount == 0 and $card_discount == 1) {
                        $card_discount = 100;
                    }

                    if($card_discount > 0) {
                        $card_discount_t = $card_discount / 100;
                        $totalprice = $totalprice * $card_discount_t;   //会员卡折扣打折后总价
                    }
                }
                }

                $totalprice = round($totalprice, 2);
                $noExpPrice += $totalprice;

                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //计算会员卡返多少积分
                if($is_exchange_goods != 1) {   //换购产品不返积分
                    //$reward_score       = $noExpPrice * $consume_score;
                    $cardReward_score   += $totalprice * $consume_score;
                } else {
                    $cardReward_score = 0;
                }
                }
                //计算订单佣金
                if($issell) {
                    if($product_yundian_id == -1 || empty($product_yundian_id)){  
                        //如果产品的总返佣比例有设置，则用产品里面的总分佣比例
                        if($pro_reward == -1) {
                            $pro_reward = $init_reward;
                        }

                        $allcost_price  =  $cost_price * $prod_num;       //总供货价
                        $allfor_price   =  $for_price * $prod_num;        //总成本价

                        if($shopactivity_mark == 2) {   //众筹活动
                            $cost_sell_price    = $crowdfund_orgin_price - $allcost_price;                  //最多能分的佣金， 总价减去总供货价
                            $prod_reward_money  = $pro_reward * ($crowdfund_orgin_price - $allfor_price);   //计算订单返佣总金：（总价-总成本价）*比例
                        } else {
                            $cost_sell_price    = $totalprice - $allcost_price;                 //最多能分的佣金， 总价减去总供货价
                            $prod_reward_money  = $pro_reward * ($totalprice - $allfor_price);  //计算订单返佣总金：（总价-总成本价）*比例
                        }

                        if($prod_reward_money > $cost_sell_price) {
                            $prod_reward_money = $cost_sell_price;                          //如果佣金大于最多能分的佣金，则以最多能分的佣金为准
                        }
                        if($prod_reward_money > 0) {
                            $reward_money += $prod_reward_money;
                            $reward_money = $this->shopMessage->floor_decimals($reward_money, 2);
                        }
                    }else{  //云店自营产品的佣金计算，平台抽成百分比，其他归还给云店店主
                        
                        $yundian_pro_reward =  $prod_num * $now_price * $yun_commission;

                        $yundian_reward += $yundian_pro_reward;
                        $yundian_reward = $this->shopMessage->floor_decimals($yundian_reward, 2);
                    }
                }

                //产品必填信息
                if($is_Pinformation == 1 && $shop_is_Pinformation == 1) {
                    $this->information($batchcode, $information, $prod_id);
                }

                //产品运费
                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                if($is_free_shipping == 0 && $store_id < 0 && empty($or_shop_type_ex) && $send_way != 2) {
                    //获取运费模板
                    $pro_express_temp = $this->orderFormExtend->pro_express_template($freight_id, $express_type, $tax_totalprice, $supply_id, $address_info['location_p'], $address_info['location_c'], $address_info['location_a'], 1);

                    $tem_id = $pro_express_temp[0];     //运费模板
                    $temp_product_express = array($tem_id, $weight, $prod_num, $tax_totalprice, $express_type);
                    array_push($opi_array, $temp_product_express);
                }
                }

                //必须是税收产品，首个产品直接存入数组，其余产品必须与第一个数对比，不同则不计算税收
                if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                if($tax_num == 0) {
                    $tax_compare_tax_type = $tax_type;
                }
                if($tax_type > 1) {
                    if($tax_num == 0 || ($tax_num > 0 && $tax_compare_tax_type == $tax_type)){
                        $pro_data = array(
                            'pid'           =>$prod_id,
                            'rcount'        =>$prod_num,
                            'pro_totalprice'=>$tax_totalprice,
                            'tax_type'      =>$tax_type
                        );

                        array_push($temp_revenue, $pro_data);
                    } else {                                    //当出现不同的税收类型则不计算税收
                        $temp_revenue = 'different_tax_type';
                        break;                                  //停止循环
                    }
                }
                $tax_num++;
                }
            }

            //使用购物币数量
            $order_currency = 0;
            if($supply_yundian_id == -1 || $supply_yundian_id == 0){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            if($isOpen) {
                foreach($user_currency as $key => $currency_values){
                    $curr_shopid = $currency_values[0];
                    if($curr_shopid == $supply_id){
                        $order_currency = $currency_values[1];
                        $sum_currency += $order_currency;
                        break;
                    }
                }
            }
            }

            /* 校验购物币是否合法start */
            /*foreach($user_currency as $key => $val) {
                if($val[0] == $supply_id) {
                    $user_curr = $val[1];
                    break;
                }
            }

            $collage_activity_res = 0;      //判断是否拼团购物币抵购检验
            if($user_open_curr == 1) {
                if($is_collage_product && $group_buy_type == 2) {   //拼团购物币抵购检验
                        $sql = "SELECT shopcode_limit, shopcode_precent, shopcode_onoff FROM collage_activities_t WHERE id='{$activitie_id}'";
                        $res = $this->db->getRow($sql);

                        $shopcode_limit     = $res['shopcode_limit'];
                        $shopcode_precent   = $res['shopcode_precent'];
                        $shopcode_onoff     = $res['shopcode_onoff'];

                    if($shopcode_onoff == 1 && ($is_collageActivities == 1 || $is_collageActivities == 2)) {
                        if($shopcode_limit == 3 || ($shopcode_limit == 1 && $group_id < 0) || ($shopcode_limit == 2 && $group_id > 0)) {
                                $collage_activity_res = 1;
                                $collage_currency = floor($group_price*$shopcode_precent*0.01*100*$rcount)/100;
                                $collage_currency = $collage_currency - $O_8reward - $shareholder;          //减去复购
                                if($collage_currency < $user_curr){
                                    $json["status"] = 10021;
                                    $json["msg"] = "数据异常！";
                                    $jsons=json_encode($json);
                                    die($jsons);
                                }

                            }
                        }
                }
            }
            if($collage_activity_res == 0){
                check_supply_currency($supply_curr,$user_curr);
            }*/
            /* 校验购物币是否合法end */

            /*开启身份证验证开始*/
            if($supply_yundian_id == -1){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            if($is_identity_order) {
                $identity_today_num++;
                if($identity_today_num > $per_identity_num){
                    $json["status"] = 10006;
                    $json["msg"] = "每个身份证号每天最多只可".$per_identity_num."单";
                    die(json_encode($json));
                }
            }
            }
            /*开启身份证验证结束*/

            //冻结购物币
            $this->frozen_currency($batchcode, $order_currency);

            //优惠券金额，只抵扣产品金额，不抵扣运费和行邮税
            $coupon_info = $this->check_coupon($userCoupon_id);
            extract($coupon_info);

            $C_Money = 0;
            if($supply_yundian_id == -1){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            if(!empty($userCoupon_id) && $userCoupon_id > 0) {    //优惠劵ID
                if($coupon_type == 3) {
                    $C_Money = $origin_totalprice;
                } else {
                    if($coupon_money > $origin_totalprice) {
                        $coupon_money = $origin_totalprice;
                    }
                    $C_Money = $coupon_money;
                }

                //记录用户使用优惠劵的价格
                $query_coupon .= "(".$userCoupon_id.",".$C_Money.",'".$order_batchcodeorder_batchcode."',1,now()),";
                $coupon_ins_arr = array(
                    'C_id'          => $userCoupon_id,
                    'price'         => $C_Money,
                    'batchcode'     => $batchcode,
                    'isvalid'       => TRUE,
                    'createtime'    => $this->current_datetime
                );
                $this->db->autoExecute('weixin_commonshop_order_coupons', $coupon_ins_arr, 'insert');

                //更新优惠劵使用状态为已使用
                $coupon_up_arr = array(
                    'is_used' => 1
                );
                $this->db->autoExecute('weixin_commonshop_couponusers', $coupon_up_arr, 'update', "id='{$userCoupon_id}' AND user_id='{$this->user_id}' AND customer_id='{$this->customer_id}'");

            }
            }

            // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/weixinpl/mshop/create_order_test.TXT',$supply_yundian_id."\r\n".PHP_EOL,FILE_APPEND);

            //计算同一个供应商下的同一个运费模板下的产品，累计重量，件数，金额在筛选出快递规则
            if(!empty($opi_array) && ($supply_yundian_id == -1 || $supply_yundian_id =='')) {//云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
                //获取供应商产品的的最优快递
                $rtn_express_tem_arr = $this->orderFormExtend->pro_express_new($opi_array, $address_info['location_p'], $address_info['location_c'], $address_info['location_a'], $supply_id);

                if($rtn_express_tem_arr != 'failed') {
                    // file_put_contents($_SERVER['DOCUMENT_ROOT'].'/weixinpl/mshop/create_order_test.TXT',"aaa"."\r\n".PHP_EOL,FILE_APPEND);
                    //计算单个供应商的所有运费
                    $total_expressprice = $this->shopMessage->New_change_freight_direct($rtn_express_tem_arr, $this->customer_id, $supply_id);
                }
            }

            /******计算每个供应商的行邮税 start*****/

            $tax_money = 0;
            if($supply_yundian_id == -1){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            if($temp_revenue == 'different_tax_type') {     //错误弹出
                $json["status"]     = 10013;
                $json["batchcode"]  = $batchcode;
                $json["msg"]        = "产品含有多种税收，无法提交订单";
                $json["remark"]     = "行邮税";
                die(json_encode($json));

            } elseif (!empty($temp_revenue)) {
                //计算税金
                $tax_money_array = tax_pro_totalpirce($total_expressprice, $origin_totalprice, ($origin_totalprice+$total_expressprice), $temp_revenue, 2, $batchcode);

                //错误弹出
                if($tax_money_array[2] == 24001 || $tax_money_array[2] == 24002) {
                    $json["status"]     = 10014;
                    $json["batchcode"]  = $batchcode;
                    $json["msg"]        = $tax_money_array[3];
                    $json["remark"]     = "行邮税";
                    die(json_encode($json));
                }

                $tax_money      = $tax_money_array[0];
                $order_tax_arr  = $tax_money_array[4];

                //订单行邮税记录
                foreach($order_tax_arr as $key => $val) {
                    $this->db->autoExecute('weixin_commonshop_order_tax', $val, 'insert');
                }
            }
            }

            /******计算每个供应商的行邮税 end*****/
            $shopTotalprice = $noExpPrice + $total_expressprice + $tax_money - $C_Money;
            $origin_totalprice   += $total_expressprice + $tax_money;

            /* 计算模式开启运行 start */
            if ($is_opencalcmode == 1) {
                $origin_price_noexp = $origin_totalprice - $total_expressprice - $tax_money;
                //比值模式
                if ($calcmode == 1) {
                    switch ($calcobj){
                        case 1:
                            $total_c_money = ($origin_price_noexp-$C_Money)/$origin_price_noexp*$reward_money;
                            break;
                        case 2:
                            $total_c_money = ($origin_price_noexp-$order_currency)/$origin_price_noexp*$reward_money;
                            break;
                    }
                    if ($calcobj == '1_2') {
                        $total_c_money = ($origin_price_noexp-$C_Money-$order_currency)/$origin_price_noexp*$reward_money;
                    }
                } else {    //扣除模式
                    switch ($calcobj){
                        case 1:
                            $total_c_money = $reward_money-$C_Money;
                            break;
                        case 2:
                            $total_c_money = $reward_money-$order_currency;
                            break;
                    }
                    if ($calcobj == '1_2') {
                        $total_c_money = $reward_money-$C_Money-$order_currency;
                    }
                }

                if ($total_c_money < 0 ){
                    $total_c_money = 0;
                } else {
                    $total_c_money = $this->shopMessage->floor_decimals($total_c_money, 2);
                }

                $reward_money = $total_c_money;
            }
            /* 计算模式开启运行 end */

            $allRewardMoney += $reward_money;

            /******复购抵扣优惠后的实付金额 start*****/
            $O_8reward = 0;
            $shareholder = 0;
            if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            if($issell == 1 && $issell_model == 2 && empty($or_shop_type_ex)) {
                $self_reward_data = array(
                    'batchcode'     => $batchcode,
                    'Plevel'        => $Plevel,
                    'reward'        => $reward_money,
                    'is_consume'    => $is_consume,
                    'customer_id'   => $this->customer_id,
                    'user_id'       => $this->user_id,
                    'supply_id'     => $supply_id ,
                    'totalprice'    => $shopTotalprice
                );

                $self_reward = $this->model_selfbuy_reward->selfbuycal_new_second($self_reward_data);
                $O_8reward      = $self_reward['data']['O_8reward'];
                $shareholder    = $self_reward['data']['shareholder'] ? : 0;

                $shopTotalprice = $shopTotalprice - $O_8reward - $shareholder;
            }
            }
            /******复购抵扣优惠后的实付金额 start*****/

            if($shopTotalprice < 0){    //防止负数
                $shopTotalprice = 0;
            }

            $allShopTotalprice += $shopTotalprice;

            //参团先检测下单资格
            if($product_yundian_id == -1 || empty($product_yundian_id)){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            if( $is_collage_product && $group_buy_type == 2 && $group_id > 0 ){
                $check_collage_arr = array(
                    'group_id' => $group_id,
                    'batchcode' => $batchcode
                );
                $this->check_qualification($check_collage_arr);
            }
            //生成拼团订单
            $collage_arr = array(
                'is_collage_product'    => $is_collage_product,
                'activitie_id'          => $activitie_id,
                'group_buy_type'        => $group_buy_type,
                'group_id'              => $group_id,
                'pid'                   => $prod_id,
                'now_price'             => $now_price,
                'batchcode'             => $batchcode,
                'is_head'               => $is_head,
                'allShopTotalprice'     => $allShopTotalprice,
                'prod_num'              => $prod_num,
                'prod_name'             => $prod_name,
                'prod_prosid'           => $prod_prosid,
                'prod_pros_name'        => $prod_pros_name
            );
            $this->create_collage_order($collage_arr);
            }

            if($supply_yundian_id == -1){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            /*vp值日志开始*/
            if(0 < $total_vpscore && $isvp_switch){
                $vpscore_arr = array(
                    'customer_id'   => $this->customer_id,
                    'user_id'       => $this->user_id,
                    'type'          => 1,
                    'batchcode'     => $batchcode,
                    'vp'            => $total_vpscore,
                    'status'        => 0,
                    'remark'        => '来源于下单得到VP值',
                    'isvalid'       => TRUE,
                    'createtime'    => $this->current_datetime
                );
                $this->db->autoExecute('weixin_commonshop_vp_logs', $vpscore_arr, 'insert');
            }
            /*vp值日志结束*/
            }

            if($supply_yundian_id == -1){          //云店自营产品不适用抵购，购物币，优惠券等，仅计算原来商品的金额
            /*慈善公益插入日志开始*/
            if($is_charitable) {
                $total_charitable_s = 0;
                if(0 < $integration_price) {
                    $total_charitable_s = $total_charitable / $integration_price; //慈善分
                }

                $charitable_arr = array(
                    'customer_id'   => $this->customer_id,
                    'user_id'       => $this->user_id,
                    'batchcode'     => $batchcode,
                    'reward'        => $total_charitable,
                    'charitable'    => $total_charitable_s,
                    'paytype'       => -1,
                    'type'          => 0,
                    'supply_id'     => $supply_id,
                    'isvalid'       => TRUE,
                    'createtime'    => $this->current_datetime
                );
                $this->db->autoExecute('charitable_log_t', $charitable_arr, 'insert');
            }
            /*慈善公益插入日志结束*/
            }

            //获取自定义区域名称
            $diy_area_name = $this->get_diy_area_name($diy_area_id);

            //保存收货地址
            $address_arr = array(
                'is_identity_order' => $is_identity_order,
                'diy_area_id'       => $diy_area_id,
                'diy_area_name'     => $diy_area_name,
                'batchcode'         => $batchcode,
                'address_info'      => $address_info,
                'identity_info'     => $identity_info
            );
            $this->save_address($address_arr);

            if($supply_yundian_id == -1){ 
                $supply_id_show = $supply_id;
            }else{
                $supply_id_show = -1;
            }
            //保存订单
            $save_order_price = array(
                'user_id'           => $this->user_id,
                'exp_user_id'       => $exp_user_id,
                'paystyle'          => '',
                'batchcode'         => $batchcode,
                'origin_price'      => $origin_totalprice,
                'price'             => $shopTotalprice,
                'NoExpPrice'        => $noExpPrice,
                'ExpressPrice'      => $total_expressprice,
                'CouponPrice'       => $C_Money,
                'reward_money'      => $reward_money,
                'AgentCostMoney'    => $AgentCostMoney,
                'SupplyCostMoney'   => $SupplyCostMoney,
                'SupplyForMoney'    => $SupplyForMoney,
                'total_vpscore'     => $total_vpscore,
                'total_charitable'  => $total_charitable,
                'rcount'            => 1,
                'reward_score'      => $cardReward_score,
                'agent_id'          => $agent_id,
                'supply_id'         => $supply_id_show,
                'pay_batchcode'     => $this->pay_batchcode,
                'needScore'         => $shopPerNeedScore,
                'currency'          => $order_back_currency,
                'cardDiscount'      => $is_select_card,
                'recovery_time'     => $recovery_time_show,
                'tax_money'         => $tax_money,
                'pay_currency'      => $order_currency,
                'card_discount'     => $card_discount_t,
                'give_integral'     => $sum_give_integral,
                'cost_integral'     => $sum_cost_integral,
                'o_shop_id'         => $o_shop_id,
                'O_8reward'         => $O_8reward,
                'shareholder'       => $shareholder,
                'or_shop_type'      => $or_shop_type,
                'or_code'           => $or_code_ex,
                'is_suning_order'   => $is_suning_order,
                'customer_id'       => $this->customer_id,
                'isvalid'           => TRUE,
                'createtime'        => $this->current_datetime,
                'yundian_reward'    => $yundian_reward,
                'yun_commission'    => $yundian_self_reware,
                'is_block_chain'    => $is_block_chain,
                'block_chain_status'=> $block_chain_status,
                'block_chain_reward'=> $sum_block_chain_reward,
                'block_chain_valid_time'=> $block_chain_valid_time
            );
            $this->db->autoExecute('weixin_commonshop_order_prices', $save_order_price, 'insert');

            //下单日志
            $order_log_arr = array(
                'batchcode' => $batchcode,
                'weixin_fromuser' => $weixin_fromuser
            );
            $this->order_log($order_log_arr);

            //区块链订单积分日志
            if($is_block_chain == 1 && $sum_block_chain_reward > 0){
                $block_chain_order_detail = array(
                    'user_id' => $this->user_id,
                    'batchcode' => $batchcode,
                    'reward' => $sum_block_chain_reward,
                    'receivetime' => $block_chain_valid_time,
                    'pay_batchcode' => $this->pay_batchcode
                );
                $this->block_chain_order_detail($block_chain_order_detail);
            }
        
        
        /*-------订单奖励 start-------*/
            /*订单奖励 by hy*/

            /*订单奖励 by hy*/
            if($this->HyOrder_reward){
                $this->HyOrder_reward->busses_add($batchcode,$this->user_id);
            }
            /*-------订单奖励 end-------*/
            

        }

        //复购佣金抵扣日志
        $this->model_selfbuy_reward->selfbuycal_new_third();


        //检验会员卡积分是否足够
        $card_score_arr = array(
            'allPerNeedScore'   => $allPerNeedScore,
            'card_member_id'    => $card_member_id
        );
        $this->check_card_score($card_score_arr);

        //添加顾客表
        $this->add_user_customer();

        /*4M库存同步执行语句 start */
        if($create_type != 3) {
            if($is_4m) {
                $this->shop_4m->update_sql_sync_4M_product_storenum(1);
            }
        }
        /*4M库存同步执行语句 end */

        //订货系统产品库存警戒提示
        $this->check_alart_num($o_store_id, $o_branch_id, $batchcode);

        //下单后锁定   改为支付后锁定关系
        //$this->qr_Utlity->user_is_lock($this->customer_id, $this->user_id);

        //大转盘增加抽奖次数
        $slyder_arr = array(
            'customer_id' => $this->customer_id,
            'batchcode' => $batchcode
        );
        $result_slyder = $this->model_slyder_adventures->add_slyder_adventures_order_chance($slyder_arr);

        /*订货状态 start*/
        if(!empty($or_shop_type_ex)) {
            $or_code_arr = array(
                'is_freeze' => 0
            );
            $this->db->autoExecute('orderingretail_code', $or_code_arr, 'update', "code='{$or_code_ex}'");
        }
        /*订货状态 end*/

        //货到付款逻辑
        if($is_delivery_order == 1) {
            $this->shop_pay->payment_Common2('货到付款', $this->customer_id, $this->user_id, $exp_user_id, 1, $allShopTotalprice, $this->pay_batchcode, $sum_currency, $allPerNeedScore, $is_QR, $_SERVER['HTTP_HOST'], $card_member_id, 1);
        }

        //发送客服消息给上级
        if($exp_user_id > 0 && $exp_user_id != $this->user_id && $allRewardMoney > 0 && $is_memberBuyMessage) {
            $sql = "SELECT weixin_fromuser FROM weixin_users WHERE id='{$exp_user_id}' AND isvalid=TRUE";
            $parent_fromuser = $this->db->getOne($sql);

            $sql = "SELECT id FROM promoters WHERE user_id='{$exp_user_id}' AND status=1 AND isvalid=TRUE";
            $parent_promoter_id = $this->db->getOne($sql);

            if(!empty($parent_fromuser)) {
                $content = "亲, 您的好友下了一笔订单\n\n昵称：".$weixin_name;

                if($is_buyContentMessage){      //是否关闭购物内容
                    $content .= "\n商品：".implode(',', $allProdName);
                }

                $content .= "\n时间：{$this->current_datetime}";

                if(empty($parent_promoter_id)) {
                    $content .= "\n若您成为{$exp_name}";
                }

                if($issell > 0) {   //如果开启了分销
                    $content .= "\n\n支付完成后，您将获得佣金！";
                }

                

                $content = addslashes($content);
                $message_arr = array(
                    'customer_id' => $this->customer_id,
                    'createtime' => $this->current_datetime,
                    'type' => 1,
                    'status' => 0,
                    'send_limit' => 0,
                    'content' => $content,
                    'openid' => $parent_fromuser
                );
                $this->db->autoExecute('send_weixinmsg_log', $message_arr, 'insert');
            }
        }

        $_SESSION['pass_url_'.$this->customer_id] = "/weixinpl/mshop/orderlist_detail.php?customer_id=".$this->customer_id_en."=&batchcode=".$batchcode;

        //提交事务
        $this->db->tran_commit();

        /*积分兑换活动 第三步*/
            if($act_type == 22 || $act_type == 24){
                if($act_type == 22) {
                    $integral_exchange_type = 0;     //商城类型
                }elseif($act_type == 24) {              //门店积分兑换产品
                    $integral_exchange_type = 1;     //门店类型
                }
                $order_deduct_parm = [
                        'customer_id'=>$this->customer_id,
                        'user_id'=>$this->user_id,
                        'pay_batchcode'=>$batchcode,
                        'integral_type'=>$integral_exchange_type
                    ];

                $order_deduct_res = $this->model_integral->order_deduct_stock_integral($order_deduct_parm);
                if($order_deduct_res['errcode']>0){
                        $json["status"] = 10113;
                        $json["msg"]    = $order_deduct_res['errmsg'];
                        $jsons=json_encode($json);
                        die($jsons);
                }
            }
            /*积分兑换活动*/

        //解决货到付款时候，事务没提交进行更新，表被锁
        if($is_delivery_order == 1) 
        {  
           $paytype_onde   = '货到付款'; 
           $batchcode_onde = $this->pay_batchcode;
           $order_update   = "update weixin_commonshop_orders set paystyle='".$paytype_onde."'"." where customer_id=".$this->customer_id." and pay_batchcode='" . $batchcode_onde . "' and isvalid=1";
            _mysql_query($order_update) or die(' Query failed: ' . mysql_error());
        }

        $json["status"]         = 1;
        $json["batchcode"]      = $this->pay_batchcode;
        $json["msg"]            = "提交成功";
        $json["batchcode_arr"]  = $batchcode_arr;
        $json["price"]          = $allShopTotalprice - $sum_currency;
        $json["currency"]       = $sum_currency;
        $json["payondelivery"]  = $is_delivery_order;

        if($is_collage_product == 1 && $group_buy_type == 2) {
            $json["is_collage_product"] = 1;
        }


        //返回下单结果
        die(json_encode($json));
    }

    //生成支付订单号
    public function create_pay_batchcode() {
        /* 订单号随机3位数*/
        $arr_rand = array();
        while(count($arr_rand) < 3){
            $arr_rand[] = rand(0,9);
            $arr_rand = array_unique($arr_rand);
        }
        $str_rand = implode("", $arr_rand);
        /* 订单号随机3位数*/

        /*生成订单号开始*/
        $this->pay_batchcode = $this->user_id . $this->current_time . $str_rand;
        /*生成订单号结束*/

        /*判断订单号是否正常开始*/
        if(!is_numeric($this->pay_batchcode) or $this->pay_batchcode < 0 ){
            $json["status"] = 10003;
            $json["msg"]    = "订单号不正确！";
            $jsons = json_encode($json);
            die($jsons);
        }
        /*判断订单号是否正常结束*/

        return $this->pay_batchcode;
    }

    //获取产品信息
    public function get_product_info($pid) {
        $return = array();

        $sql = "SELECT 
                    name AS prod_name,
                    is_QR,
                    weight,
                    isvalid,
                    storenum,
                    for_price,
                    isout,
                    now_price,
                    need_score,
                    cost_price,
                    pro_reward,
                    is_identity AS prod_is_identity,
                    donation_rate,
                    isvp,
                    vp_score,
                    is_currency,
                    back_currency,
                    agent_discount,
                    is_Pinformation,
                    is_virtual,
                    is_camilo,
                    is_free_shipping,
                    freight_id,
                    express_type,
                    tax_type,
                    is_first_extend,
                    extend_money,
                    create_type,
                    yundian_id AS product_yundian_id,
                    skuid,
                    is_block_chain as pro_is_block_chain,
                    block_chain_type,
                    block_chain_bfb,
                    block_chain_money
                FROM 
                    weixin_commonshop_products
                WHERE 
                    id='{$pid}'";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }

        return $return;
    }

    //获取属性产品
    public function get_product_pros_info($pid, $pros) {
        $return = array(
            'prod_pros_id' => -1
        );

        $sql = "SELECT 
                    id AS prod_pros_id,
                    now_price,
                    cost_price,
                    storenum,
                    need_score,
                    for_price,
                    weight,
                    create_type
                FROM 
                    weixin_commonshop_product_prices
                WHERE 
                    product_id='{$pid}'
                AND proids='{$pros}'";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }

        return $return;
    }

    //获取属性字符串
    public function get_pros_str($pros) {
        $pros_arr = array();
        if(!empty($pros)) {
            $pros = str_replace('_', ',', $pros);

            $sql = "SELECT name, parent_name FROM weixin_commonshop_pros WHERE id IN ({$pros})";
            $res = $this->db->getAll($sql);
            foreach($res as $key => $val) {
                $pros_arr[] = "{$val['parent_name']}:{$val['name']}";
            }
        }

        return $pros_str = implode(' ', $pros_arr);
    }

    //优惠券有效性
    public function check_coupon($coupon_id) {
        $return = array(
            'userCoupon_id' => $coupon_id,
            'coupon_money'  => 0,
            'coupon_type'   => -1
        );

        $sql = "SELECT Money AS coupon_money, deadline AS coupon_deadline, class_type AS coupon_type FROM weixin_commonshop_couponusers WHERE id='{$coupon_id}' AND customer_id='{$this->customer_id}' AND isvalid=TRUE";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }

        //保存订单的时候优惠券过期，不能使用
        if($res && strtotime($res['coupon_deadline']) < $this->current_time) {
            $return['userCoupon_id']    = -1;
            $return['coupon_money']     = 0;
        }

        return $return;
    }

    //满赠活动，判断库存和获取产品活动价格
    public function exchange_act($exchange_arr) {
        $return = array();

        extract($exchange_arr);

        $sql = "SELECT storenum, exchange_price AS now_price FROM weixin_commonshop_exchange_products WHERE pid='{$pid}' AND exchange_id='{$exchange_act_id}' AND isvalid=TRUE";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }

        //判断库存
        if($return['storenum'] < $prod_num) {
            $json['status'] = 10015;
            $json['msg']    = "{$prod_name} 库存不足！";
            die(json_encode($json));
        }

        //扣除库存
        $up_arr = array(
            'storenum' => $return['storenum'] - $prod_num
        );
        $this->db->autoExecute('weixin_commonshop_exchange_products', $up_arr, 'update', "pid='{$pid}' AND exchange_id='{$exchange_act_id}' AND isvalid=TRUE");

        return $return;
    }

    //检验产品库存
    public function check_prod_storenum($prod_arr) {
        extract($prod_arr);

        //订货系统门店或者子门店派单时，查询门店库存是否足够
        $this->check_orderingretail_store($pid, $prod_prosid, $prod_num, $o_shop_id, $o_branch_id, $or_shop_type_ex);

        if($storenum < $prod_num && $is_exchange_goods != 1 && empty($or_shop_type_ex)) {
            $json["status"] = 10005;
            $json["msg"]    = "库存不足！";
            die(json_encode($json));
        }

        //扣除库存
        $return = $this->deduct_storenum($prod_arr);

        if($return['is_recovery_storenum']) {
            //回收库存
            $this->recovery_storenum($prod_arr);
        }

    }

    //订货系统门店或者子门店派单时，查询门店库存是否足够
    public function check_orderingretail_store($pid, $pros, $p_num, $o_shop_id, $o_branch_id, $or_shop_type_ex) {
        if(($o_store_id>0 || $o_branch_id>0) && empty($or_shop_type_ex)) {
            //查询门店
            if($o_store_id > 0) {
                $sql = "SELECT proxy_id FROM " . WSY_DH . ".orderingretail_shop WHERE id='{$o_store_id}'";
                $proxy_id = $this->db->getOne($sql);

                $sql = "SELECT id FROM " . WSY_DH .".orderingretail_proxy_product WHERE proxy_id='{$proxy_id}' AND product_id='{$pid}' AND proids='{$pros}' AND store_count>={$p_num}";
                $store_count_id = $this->db->getOne($sql);
            }

            //查询子门店
            if($o_branch_id > 0) {
                $sql = "SELECT id FROM " . WSY_DH . ".orderingretail_shop_branch_pro WHERE branch_id='{$o_branch_id}' AND product_id='{$pid}' AND provalues='{$pros}' AND store_count>={$p_num}";
                $store_count_id = $this->db->getOne($sql);
            }

            //库存不足;
            if(empty($store_count_id)) {
                $json["o_store_id"] = $o_store_id;
                $json["o_branch_id"] = $o_branch_id;
                $json["pid"] = $pid;
                $json["proids"] = $pros;
                $json["status"] = 10005;
                $json["msg"]    = "门店库存不足！";
                die(json_encode($json));
            }
        }
    }

    //扣除库存
    public function deduct_storenum($prod_arr) {
        extract($prod_arr);
        //var_dump($prod_arr);exit;
        $return = array(
            'is_recovery_storenum' => $is_recovery_storenum     //是否回收库存
        );

        if(($is_recovery_storenum && $is_exchange_goods != 1) || $act_type == 22 || $act_type == 24) {
            if($o_store_id > 0 || $o_branch_id > 0) {    //订货系统门店
                //订货系统门店派单不需要回收库存
                $return['is_recovery_storenum'] = 0;

                //添加门店冻结库存数
                if($o_store_id > 0) {
                    $sql = "SELECT proxy_id FROM " . WSY_DH . ".orderingretail_shop WHERE id='{$o_store_id}'";
                    $proxy_id = $this->db->getOne($sql);

                    //添加总库存
                    $proxy_account_arr = array('sum_repertory' => 'sum_repertory-' . $prod_num);
                    $this->db->autoExecute(WSY_DH . '.orderingretail_proxy_account', $proxy_account_arr, 'update', "proxy_id='{$proxy_id}'");

                }

                //添加子门店冻结库存数
                if($o_branch_id > 0) {
                    $shop_branch_arr = array('total_product' => 'total_product-' . $prod_num);
                    $this->db->autoExecute(WSY_DH . '.orderingretail_shop_branch', $shop_branch_arr, 'update', "id='{$o_branch_id}'");

                }
                //门店冻结库存日记
                if($o_shop_id > 0 && $o_branch_id <= 0) {
                    $sql = "SELECT or_product_id, store_count, virtual_store_count FROM " . WSY_DH . ".orderingretail_proxy_product WHERE proxy_id='{$proxy_id}' AND proids='{$prod_prosid}' AND product_id='{$pid}' AND customer_id='{$this->customer_id}'";
                    $res = $this->db->getRow($sql);
                    if($res) {
                        $log_arr = array(
                            'customer_id'   => $this->customer_id,
                            'batchcode'     => $batchcode,
                            'proxy_id'      => $proxy_id,
                            'or_product_id' => $res['or_product_id'],
                            'product_id'    => $pid,
                            'proids'        => $pros,
                            'createtime'    => $this->current_datetime,
                            'count'         => 0,
                            'before_store'  => $res['store_count'],
                            'after_store'   => $res['store_count'],
                            'before_virtual_store' => $res['virtual_store_count'],
                            'after_virtual_store'  => $res['virtual_store_count'],
                            'store_type'    => $res['virtual_store_count'],
                            'comment'       => "下单",
                            'isvalid'       => true
                        );
                        $this->db->autoExecute(WSY_DH . ".orderingretail_proxy_product_log", $log_arr, 'insert');
                    }
                }

            } elseif ($is_collageActivities == 2) {   //拼团

                //扣除拼团产品库存
                $collage_arr = array('stock' => 'stock-' . $prod_num);
                $this->db->autoExecute('collage_group_products_t', $collage_arr, 'update', "pid='{$pid}' AND activitie_id='{$activitie_id}'");
            } else {

                if($prod_pros_id == -1) {
                    $storenum_arr = array('storenum' => 'storenum-' . $prod_num);
                    $this->db->autoExecute('weixin_commonshop_products', $storenum_arr, 'update', "id='{$pid}'");

                    //4M同步库存
                    if($create_type != 3) {
                        $this->shop_4m->sync_4M_product_storenum($is_4m , 2, 2, $prod_num, $pid, $pros, $prod_pros_id, $create_type);
                    }
                } else {
                    $storenum_arr = array('storenum' => 'storenum-' . $prod_num);
                    $this->db->autoExecute('weixin_commonshop_product_prices', $storenum_arr, 'update', "id='{$prod_pros_id}'");

                    if($create_type != 3) {
                        //4M同步库存
                        $this->shop_4m->sync_4M_product_storenum($is_4m , 1, 2, $prod_num, $pid, $pros, $prod_pros_id, $create_type);

                    }
                }

                //4M库存同步执行语句
                if($create_type != 3) {
                    if($is_4m) {
                        $this->shop_4m->update_sql_sync_4M_product_storenum(1);
                    }
                }
            }
        }

        return $return;
    }

    //回收库存
    public function recovery_storenum($prod_arr) {
        extract($prod_arr);
        if($is_collageActivities == 1 || $is_collageActivities == 2){
            $is_collageActivities_stock = 1;//拼团订单
        }
        $recovery_arr = array(
            'batchcode' => $batchcode,
            'pid' => $pid,
            'pos_id' => $prod_pros_id,
            'stock' => $prod_num,
            'recovery_time' => $recovery_time,
            'customer_id' => $this->customer_id,
            'is_collageActivities' => $is_collageActivities_stock
        );

        $this->db->autoExecute('stockrecovery_t', $recovery_arr, 'insert');
    }

    //续费产品
    public function promoter_permanent($batchcode, $pid, $prod_num) {
        $sql = "SELECT 
                    prp.renewal_id,pr.renew_time
                FROM 
                    promoter_renewal_products AS prp
                INNER JOIN 
                    promoter_renewal AS pr ON prp.renewal_id=pr.id
                WHERE 
                    pr.customer_id='{$this->customer_id}' AND prp.isvalid=true AND prp.product_id='{$pid}' AND pr.isout=0 AND pr.isvalid=true LIMIT 1";
        $res = $this->db->getRow($sql);
        if($res) {
            //生成续费订单
            $order_arr = array(
                'batchcode'     => $batchcode,
                'customer_id'   => $this->customer_id,
                'user_id'       => $this->user_id,
                'status'        => 0,
                'p_id'          => $pid,
                'p_num'         => $prod_num,
                'renewal_id'    => $res['renewal_id'],
                'renew_time'    => $res['renew_time'] * $prod_num,
                'isvalid'       => TRUE,
                'createtime'    => $this->current_datetime
            );

            $this->db->autoExecute('promoter_renewal_orders', $order_arr, 'insert');
        }

    }

    //是否支持货到付款
    public function check_delivery($is_delivery_order, $pid) {

        if($is_delivery_order) {
            $sql = "SELECT id FROM pay_on_delivery_products_t WHERE pid='{$pid}' AND isvalid=TRUE";
            return $this->db->getOne($sql) ? 1 : 0;
        } else {
            return 0;
        }
    }

    //电商直播订单
    public function mb_order($mb_topic, $batchcode) {
        $is_mb_order = 0;

        if(!empty($mb_topic) && $mb_topic != -1) {
            $mb_topic_arr = explode('_', $mb_topic);

            if($mb_topic_arr[0] == 'topicid') {     //直播类型
                $mb_topic_type = 1;

                $sql = "SELECT account_id FROM mb_topic WHERE id='{$mb_topic_arr[1]}' AND isvalid=TRUE";
                $account_id = $this->db->getOne($sql);
            } elseif ($mb_topic_arr[0] == 'resourceid') {   //资源类型
                $mb_topic_type = 2;

                $sql = "SELECT account_id FROM mb_resource WHERE id='{$mb_topic_arr[1]}' AND isvalid=TRUE";
                $account_id = $this->db->getOne($sql);
            } else {
                $account_id = -1;
            }

            //生成电商直播订单
            if($account_id > 0) {
                $mb_order_arr = array(
                    'batchcode'     => $batchcode,
                    'createtime'    => $this->current_datetime,
                    'isvalid'       => TRUE,
                    'type'          => $mb_topic_type,
                    'account_id'    => $account_id,
                    'customer_id'   => $this->customer_id,
                    'topic_id'      => $mb_topic_arr[1]
                );

                $mb_order_id = $this->db->autoExecute('mb_order', $mb_order_arr, 'insert');

                if($mb_order_id > 0){
                    $is_mb_order = 1;
                }
            }
        }

        return $is_mb_order;
    }

    //活动产品
    public function product_act($product_act_arr,$prod_num,$pro_info_ar) {
        //$act_type=$product_act_arr['act_type'];
        $return = array(
            'consume_integral' => 0,
            'cost_integral' => 0,
            'is_recovery_storenum' => 1,
            'is_collageActivities_stock' => 0   //库存回收表拼团订单标识，0:不是拼团订单、1：拼团订单
        );

        $result = array();

        extract($product_act_arr);

        if($act_type == 22) {
            $product_act_arr['integral_exchange_type'] = 0;     //商城类型
        }elseif($act_type == 24) {              //门店积分兑换产品
            $product_act_arr['act_type'] = $return['act_type'] = $act_type = 22;
            $product_act_arr['integral_exchange_type'] = 1;     //门店类型
        }
        
        switch($act_type) {
            case 21:    //积分赠送产品
                $result = $this->integral_give($product_act_arr,$prod_num,$pro_info_ar);
                break;

            case 22:    //积分兑换产品
                $result = $this->integral_exchange($product_act_arr);
                break;

            case 31:    //限购活动产品
                $result = $this->restricted_purchase($product_act_arr);
                break;
        }

        //当遍历最后一次不是增送积分产品时，必须再次调用积分并且插入数据
        if($act_type != 21 && $pro_info_ar[2] == 1)
        {
            $result = $this->integral_give($product_act_arr,$prod_num,$pro_info_ar);
        }
        
        if($act_type == 22) {
            $return['is_collage_product'] = 0;      //不进入拼团流程
            $return['is_collageActivities'] = 3;    //3为积分兑换活动订单
        }

        //砍价活动
        if($bargain_data) {
            $result = $this->bargain_act($bargain_data, $batchcode);
        }

        //众筹活动
        if($crowdfund_data) {
            // var_dump($crowdfund_data);
            // exit();
            $result = $this->crowdfund_act($crowdfund_data, $batchcode);
            $return['crowdfund_orgin_price'] = $now_price;
        }

        //拼团活动
        if($is_collage_product && $group_buy_type == 2) {
            $result = $this->collage_act($product_act_arr);
        }

        $return = array_merge($return, $result);
        return $return;
    }

    //商城积分赠送产品
    public function integral_give($product_act_arr,$prod_num,$pro_info_ar) {
        extract($product_act_arr);

        $return['is_integral_order'] = 1;   //积分赠送订单

        //判断产品是否积分赠送活动产品
        $integral_arr = array(
            'p_id'      => $pid,
            'cust_id'   => $this->customer_id,
            'come_type' => 1
        );

        $product_type = $this->model_integral->check_product_integral_activity($integral_arr);

        if($product_type['act_type'] == 2) {
            $json["status"] = 10114;
            $json["msg"]    = '产品异常，不能购买';
            die(json_encode($json));
        }

        //获取赠送积分数量
        $integral_arr = array(
            'p_id'      => $pid,
            'cust_id'   => $this->customer_id,
            'batchcode' => $batchcode,
            'pros_id'   => $prod_prosid,
            'rcount'    => $prod_num,
            'act_type'  => $product_type['act_type'],
            'act_id'    => $product_type['act_id']
        );
        //当遍历最后一次不是增送积分产品时，必须再次调用积分并且插入数据
        if($act_type == 21)
        {
            $product_pos_get = $this->model_integral->get_pros_integral($integral_arr);
            $return['consume_integral'] = $product_pos_get['consume_integral'];
        }

        //待送积分
        $parm = [
                'customer_id'   =>$this->customer_id,
                'user_id'       =>$this->user_id,
                'pay_batchcode' =>$this->pay_batchcode,
                'integral_type' =>0,
            ];

        if($pro_info_ar[0] == $pro_info_ar[1]) //当购买的产品遍历完后插入
        {
            $res_inter= $this->model_integral->save_order_integral($parm);     
        }


        return $return;
    }

    //积分兑换产品
    public function integral_exchange($product_act_arr) {
        extract($product_act_arr);

        $return['is_integral_order'] = 2;   //积分兑换订单
        $return['is_recovery_storenum'] = 0;

        //判断产品是否积分兑换活动产品
        $integral_arr = array(
            'p_id'      => $pid,
            'cust_id'   => $this->customer_id,
            'come_type' => 2
        );
        $product_type = $this->model_integral->check_product_integral_activity($integral_arr);

        if($product_type['act_type'] != 2) {
            $json["status"] = 10113;
            $json["msg"]    = '兑换活动产品异常，不能购买';
            die(json_encode($json));
        }

        //判断是否有效活动
        $integral_arr = array(
            'act_id'        => $act_id,
            'customer_id'   => $this->customer_id,
            'integral_type' => $integral_exchange_type
        );

        $isvalid_check = $this->model_integral->check_exchange_isvalid($integral_arr);

        if($isvalid_check['errcode'] != 0) {
            $json["status"] = 10110;
            $json["msg"]    = $isvalid_check['errmsg'];
            die(json_encode($json));
        }

        //判断库存
        $integral_arr = array(
            'proId'         => $pid,
            'act_id'        => $act_id,
            'batchcode'     => $batchcode,
            'customer_id'   => $this->customer_id,
            'rcount'        => $prod_num,
            'recovery_time' => $recovery_time
        );

        $stock_res = $this->model_integral->check_stock($integral_arr);

        if($stock_res['errcode'] != 0) {
            $json["status"] = 10111;
            $json["msg"]    = $stock_res['errmsg'];
            $jsons          = json_encode($json);
            die($jsons);
        }

        //获取产品的积分
        $integral_arr = array(
            'pid'           => $pid,
            'act_id'        => $act_id,
            'batchcode'     => $batchcode,
            'customer_id'   => $this->customer_id,
            'num'           => $prod_num,
            'type'          => 2,
            'integral_type' => $integral_exchange_type
        );

        $integral_res = $this->model_integral->cul_integral($integral_arr, $now_price, $totalprice);

        if($integral_res['errcode'] == 101) {
            return $return;
        }

        if($integral_res['errcode'] > 101) {
            $json["status"] = 10112;
            $json["msg"]    = $integral_res['errmsg'];
            die(json_encode($json));
        }

        //重新赋值库存
        $return['storenum'] = $integral_res['data']['data']['stock'];

        //获取积分兑换售后设置
        $sql = "SELECT aftersale_onoff, afstore_onoff FROM " . WSY_SHOP .".integral_setting WHERE cust_id='{$this->customer_id}'";
        $res = $this->db->getRow($sql);
        if($res) {
            $aftersale_onoff    = $res['aftersale_onoff'];
            $afstore_onoff      = $res['afstore_onoff'];
        } else {
            $aftersale_onoff    = 1;
            $afstore_onoff      = 1;
        }

        if($integral_exchange_type == 1) {  //门店
            $return['cost_integral'] = $integral_res['data']['data']['store_integral_t'];
            //门店积分不开启售后
            if($afstore_onoff == 0) {
                $return['is_open_aftersale'] = '0_0_0';
            }
        } else {                      //商城
            $return['cost_integral'] = $integral_res['data']['data']['integral_t'];
            //商城积分不开启售后
            if($aftersale_onoff == 0) {
                $return['is_open_aftersale'] = '0_0_0';
            }
        }
        $return['totalprice'] = $integral_res['totalprice'];
        $return['now_price'] = $integral_res['now_price'];

        return $return;
    }

    //限购活动
    public function restricted_purchase($product_act_arr) {
        $return = array();

        extract($product_act_arr);

        $restricted_arr = array(
            'user_id'       => $this->user_id,
            'customer_id'   => $this->customer_id,
            'buy_num'       => $prod_num,
            'product_id'    => $pid,
            'restricted_id' => $act_id
        );

        //验证活动有效性
        $restricted_result = $this->model_restricted_purchase->checkUserRestrictedPurchase($restricted_arr);

        if($restricted_result['errcode'] == 0) {
            $return['is_restricted_purchase'] = 1;
            $return['restricted_purchase_id'] = $act_id;

            //限购活动售后设置
            $return['now_price']= $restricted_result['data']['activity']['price'];              //修改现价
            $is_refund          = $restricted_result['data']['activity']['is_refund'];          //退款开关 0:关 1:开
            $is_return_good     = $restricted_result['data']['activity']['is_return_good'];     //退货开关 0:关 1:开
            $is_exchange        = $restricted_result['data']['activity']['is_exchange'];        //换货开关 0:关 1:开

            if(!$is_refund) {
                $is_open_aftersale = '0_';
            } else {
                $is_open_aftersale = '1_';
            }

            if(!$is_return_good) {
                $is_open_aftersale .= '0_';
            } else {
                $is_open_aftersale .= '1_';
            }

            if(!$is_exchange) {
                $is_open_aftersale .= '0';
            } else {
                $is_open_aftersale .= '1';
            }

            $return['is_open_aftersale'] = $is_open_aftersale;

        } elseif ($restricted_result['errcode'] == 101) {

        } else {
            $json["status"] = $restricted_result['errcode'];
            $json["msg"]    = $restricted_result['errmsg'];
            die(json_encode($json));
        }

        return $return;
    }

    //砍价活动
    public function bargain_act($bargain_data, $batchcode) {
        $return = array();

        $bargain_data = explode(',', $bargain_data);
        $return['now_price']            = $bargain_data[4];
        $return['shopactivity_mark']    = 1;

        //生成砍价订单
        $bargin_arr = array(
            'batchcode'     => $batchcode,
            'activity_id'   => $bargain_data[0],
            'product_id'    => $bargain_data[1],
            'action_id'     => $bargain_data[2],
            'user_id'       => $this->user_id,
            'money'         => $bargain_data[4],
            'order_time'    => $this->current_datetime,
            'is_pay'        => '0',
            'status'        => '0',
            'isvalid'       => '1',
            'customer_id'   => $this->customer_id
        );
        $this->db->autoExecute(WSY_SHOP . '.kj_order', $bargin_arr, 'insert');

        return $return;
    }

    //众筹活动
    public function crowdfund_act($crowdfund_data, $batchcode) {
        $return = array();

        $crowdfund_data = explode(',', $crowdfund_data);
        $return['now_price']            = $crowdfund_data[4];
        $return['shopactivity_mark']    = 2;

        //生成众筹订单
        $crowdfund_arr = array(
            'batchcode'     => $batchcode,
            'apply_id'      => $crowdfund_data[2],
            'money'         => $crowdfund_data[4],
            'order_time'    => $this->current_datetime,
            'user_id'       => $this->user_id,
            'is_pay'        => '0',
            'status'        => '0',
            'class'         => '1',
            'isvalid'       => '1',
            'customer_id'   => $this->customer_id
        );
        $this->db->autoExecute(WSY_SHOP . '.cr_order', $crowdfund_arr, 'insert');
        
        return $return;
    }

    //拼团活动
    public function collage_act($product_arr) {
        $return = array(
            'is_collageActivities' => 2,
            'is_collageActivities_stock' => 1
        );

        extract($product_arr);

        //查询拼团价格
        $condition = array(
            'cgpt.pid'          => $pid,
            'cgpt.isvalid'      => true,
            'cgpt.status'       => 1,
            'cat.status'        => 2,
            'cat.isvalid'       => true,
            'wcp.isvalid'       => true,
            'ae.isvalid'        => true,
            'ae.customer_id'    => $this->customer_id,
            'cat.customer_id'   => $this->customer_id
        );
        $filed = " cgpt.price ";
        $group_pro = $this->collageActivities->get_recommendation_product_system($condition, $filed);

        if(isset($group_pro['code']) && $group_pro['code'] > 0) {
            $json["status"] = 11100;
            $json["msg"]    = " 拼团产品数据错误！";
            die(json_encode($json));
        }

        $return['now_price'] = $return['group_price'] = $group_pro['data'][0]['price'];

        //拼团之前产品已经被终止拼团活动
        if(empty($return['now_price'])){
            $condition1 = array();
            $condition1 = $condition;
            $condition1['cgpt.status'] = 2;
            unset($condition1['cat.status']);
            $filed = " cgpt.price,cgpt.status ";
            $group_pro = $this->collageActivities->get_recommendation_product_system($condition1, $filed);
            if(isset($group_pro['code']) && $group_pro['code'] > 0) {
                $json["status"] = 11100;
                $json["msg"]    = " 拼团产品数据错误！";
                die(json_encode($json));
            }
            if($group_pro['data'][0]['status'] == 2){
                $return['now_price'] = $return['group_price'] = $group_pro['data'][0]['price'];
            }
            
        }
        //查询拼团产品库存
        $sql = "SELECT stock FROM collage_group_products_t WHERE pid='{$pid}' AND activitie_id='{$activitie_id}' AND isvalid=TRUE";
        $return['storenum'] = $this->db->getOne($sql);

        return $return;
    }

    //订单是否超过商家后台设置的限制


    //必填信息
    public function information($batchcode, $information, $pid) {
        foreach($information as $key => $val) {
            $information_arr = array(
                'pid'               => $pid,
                'createtime'        => $this->current_datetime,
                'isvalid'           => TRUE,
                'batchcode'         => $batchcode,
                'information_head'  => $val[0],
                'information_con'   => mysql_real_escape_string($val[1]),
                'customer_id'       => $this->customer_id
            );

            $this->db->autoExecute('weixin_commonshop_orders_requiredinformation_t', $information_arr, 'insert');
        }
    }

    //检验会员卡积分是否足够
    public function check_card_score($card_score_arr) {
        extract($card_score_arr);

        $sql = "SELECT remain_score FROM weixin_card_member_scores WHERE card_member_id='{$card_member_id}' AND isvalid=TRUE";
        $remain_score = $this->db->getOne($sql) ? : 0;

        if($allPerNeedScore > $remain_score) {
            $json["status"] = 10022;
            $json["msg"]    = "您的会员卡积分不足";
            $json["remark"] = "会员卡积分不足";
            die(json_encode($json));
        }
    }

    //订货系统产品库存警戒提示
    public function check_alart_num($o_store_id, $o_branch_id, $batchcode) {
        //订货系统产品库存警戒提示开始
        if($o_store_id > 0 || $o_branch_id > 0) {
            if($o_store_id > 0) {
                $o_shop_id = $o_store_id;
                $or_shop_type = 1;
            }
            if($o_branch_id > 0) {
                $o_shop_id = $o_branch_id;
                $or_shop_type = 2;
            }
            $url = Protocol . "" . $_SERVER['HTTP_HOST'] . "/addons/index.php/ordering_retail/Postdata/check_alart_num?customer_id=" . $this->customer_id."&o_shop_id=".$o_shop_id."&or_shop_type=".$or_shop_type."&batchcode=".$batchcode;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            if (Protocol == "https://") {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            }
            $output = curl_exec($ch);
        }
        //订货系统产品库存警戒提示结束
    }

    //添加顾客表
    public function add_user_customer() {
        $sql = "SELECT id FROM weixin_commonshop_customers WHERE user_id='{$this->user_id}' AND customer_id='{$this->customer_id}' AND isvalid=TRUE";
        $res = $this->db->getOne($sql);

        if(!$res) {
            $cus_arr = array(
                'user_id' => $this->user_id,
                'customer_id'   => $this->customer_id,
                'isvalid'       => TRUE,
                'createtime'    => $this->current_datetime
            );
            $this->db->autoExecute('weixin_commonshop_customers', $cus_arr, 'insert');
        }
    }

    //生成拼团订单
    public function create_collage_order($order_arr) {
        extract($order_arr);

        /*开团订单开始*/
        if( $is_collage_product && $group_buy_type == 2 && $group_id < 0 ) {
            $sql = "SELECT type, group_size, end_time FROM collage_activities_t WHERE id='{$activitie_id}'";
            $activity_info = $this->db->getRow($sql);
            $this_endting_time1 = $this_endting_time2 = $activity_info['end_time'];

            $sql = "SELECT open_day, success_num FROM collage_group_products_t WHERE activitie_id='{$activitie_id}' AND isvalid=true AND pid='{$pid}'";
            $group_pro_info = $this->db->getRow($sql);
            if($group_pro_info['open_day'] > 0) {
                $this_endting_time2 = date("Y-m-d H:i:s",strtotime( $this->current_datetime . '+' . $group_pro_info['open_day'] . 'day'));
            }

            if(strtotime($this_endting_time1) > strtotime($this_endting_time2)) {
                $this_endting_time = $this_endting_time2;
            } else {
                $this_endting_time = $this_endting_time1;
            }

            $success_num = $activity_info['group_size'];
            if($group_pro_info['success_num'] != -1) {
                $success_num = $group_pro_info['success_num'];
            }

            $values = array(
                'customer_id'   => $this->customer_id,
                'isvalid'       => true,
                'createtime'    => $this->current_datetime,
                'activitie_id'  => $activitie_id,
                'type'          => $activity_info['type'],
                'status'        => -1,
                'is_win'        => 2,
                'head_id'       => $this->user_id,
                'price'         => $now_price,
                'success_num'   => $success_num,
                'join_num'      => 0,
                'total_price'   => 0,
                'pid'           => $pid,
                'endtime'       => $this_endting_time
            );
            $group_order = $this->collageActivities->insert_group_order($values);
            $group_id = $group_order['data'];
        }
        /*开团订单结束*/

        /*团员订单开始*/
        if( $is_collage_product && $group_buy_type == 2 && $group_id > 0 ) {
            //拼团-团员订单
            $values = array(
                'group_id'      => $group_id,
                'batchcode'     => $batchcode,
                'customer_id'   => $this->customer_id,
                'createtime'    => $this->current_datetime,
                'isvalid'       => true,
                'activitie_id'  => $activitie_id,
                'user_id'       => $this->user_id,
                'is_head'       => $is_head,
                'status'        => 1,
                'totalprice'    => $allShopTotalprice,
                'rcount'        => $prod_num
            );
            $crew_order = $this->collageActivities->insert_crew_order($values);

            if( $crew_order['data'] > 0 ) {
                //拼团-团员订单产品信息
                $values = array(
                    'batchcode'     => $batchcode,
                    'pid'           => $pid,
                    'pname'         => $prod_name,
                    'prvalues'      => $prod_prosid,
                    'prvalues_name' => $prod_pros_name,
                    'price'         => $now_price
                );
                $pro_mes = $this->collageActivities->insert_pro_mes($values);
            }
        }
        /*团员订单结束*/
    }

    //发票抬头
    public function invoice_head($invoice_head, $batchcode) {
        if(!empty($invoice_head)) {
            $invoice_arr = array(
                'batchcode'     => $batchcode,
                'invoice_head'  => $invoice_head
            );

            $this->db->autoExecute('order_invoice_t', $invoice_arr, 'insert');
        }
    }

    //参团先检测下单资格
    public function check_qualification($check_collage_arr) {
        extract($check_collage_arr);

        $collage_status = $this->collageActivities->check_qualification($this->customer_id, $this->user_id, $group_id, $batchcode);

        if($collage_status['status'] > 1) {
            // $query_stock = "UPDATE collage_group_products_t SET stock=stock+".$rcount." WHERE pid=".$proId." AND activitie_id=".$activitie_id." AND isvalid=true AND status=1";
            // _mysql_query($query_stock);

            $json["status"] = 40006;
            $json["msg"] = $collage_status['msg'];
            die(json_encode($json));
        }
    }

    //添加下单日志
    public function order_log($order_log_arr) {
        extract($order_log_arr);

        $order_log_arr = array(
            'batchcode' => $batchcode,
            'operation' => 0,
            'descript' => '用户下单',
            'operation_user' => $weixin_fromuser,
            'createtime' => $this->current_datetime,
            'isvalid' => TRUE
        );
        $this->db->autoExecute('weixin_commonshop_order_logs', $order_log_arr, 'insert');
    }

    //获取自定义区域名称
    public function get_diy_area_name($diy_area_id) {
        $diy_area_name = "";

        if($diy_area_id > 0) {
            $sql = "SELECT areaname FROM weixin_commonshop_team_area WHERE id='{$diy_area_id}' AND customer_id='{$this->customer_id}' AND grade=3 AND isvalid=TRUE";
            $diy_area_name = $this->db->getOne($sql);
        }

        return $diy_area_name;
    }

    //保存收货地址
    public function save_address($address_arr) {
        extract($address_arr);
        extract($address_info);
        extract($identity_info);

        $order_address = array(
            'batchcode'     => $batchcode,
            'name'          => $name,
            'phone'         => $phone,
            'address'       => $address,
            'location_p'    => $location_p,
            'location_c'    => $location_c,
            'location_a'    => $location_a,
            'diy_area_id'   => $diy_area_id,
            'diy_area_name' => $diy_area_name,
            'identity'      => '',
            'identityimgt'  => '',
            'identityimgf'  => ''
        );

        //身份证验证订单需要保存身份证信息
        if($is_identity_order) {
            $order_address['identity']      = $identity;
            $order_address['identityimgt']  = $identityimgt;
            $order_address['identityimgf']  = $identityimgf;
        }

        $this->db->autoExecute('weixin_commonshop_order_addresses', $order_address, 'insert');
    }

    //冻结购物币
    public function frozen_currency($batchcode, $currency) {
        //echo "aaaaaa";exit;
        //扣除购物币
        $currency_arr = array(
            'currency' => 'currency-' . $currency
        );
        $this->db->autoExecute('weixin_commonshop_user_currency', $currency_arr, 'update', "user_id='{$this->user_id}' AND customer_id='{$this->customer_id}' AND isvalid");

        //生成冻结购物币日志
        $currency_log_arr = array(
            'batchcode'     => $batchcode,
            'currency'      => $currency,
            'status'        => 1,
            'customer_id'   => $this->customer_id,
            'user_id'       => $this->user_id,
            'isvalid'       => true,
            'createtime'    => $this->current_datetime
        );
        $this->db->autoExecute(WSY_SHOP . '.weixin_commonshop_frozen_currency_log', $currency_log_arr, 'insert');

        if ($currency > 0) {
            //查询当前总购物币
            $sql = "SELECT currency FROM weixin_commonshop_user_currency WHERE customer_id='{$this->customer_id}' AND user_id='".$this->user_id."' AND isvalid=TRUE";
            $currency_num = $this->db->getOne($sql);
            $currency_arr = array(
                'batchcode'     => $batchcode,
                'cost_currency' => $currency,
                'status'        => 0,
                'type'          => 2,
                'customer_id'   => $this->customer_id,
                'user_id'       => $this->user_id,
                'isvalid'       => true,
                'createtime'    => $this->current_datetime,
                'class'         => 1,
                'remark'        => '提单不支付冻结购物币',
                'after_currency'=> $currency_num
            );
            $this->db->autoExecute(WSY_SHOP . '.weixin_commonshop_currency_log', $currency_arr, 'insert');
        }
    }

    //调试测试
    public function debug($data) {
        if($this->user_id == 196282) {
            var_dump($data);
        }
    }

    /*查找云店配置*/
    public function find_yundian_setting($yundian_id){
        $sql_setting = "select yundian_onoff,invalid_onoff,invalid_time from ".WSY_REBATE.".weixin_yundian_setting where customer_id=".$this->customer_id." and isvalid = 1 ";
        $res_setting = $this->db->getRow($sql_setting);

        $sql = "select wyk.user_id,wyk.expire_time,wyk.profit_shop,wyk.shop_reware,wyk.self_reware,wyi.reward from ".WSY_USER.".weixin_yundian_keeper as wyk 
            inner join ".WSY_REBATE.".weixin_yundian_identity as wyi on wyk.tequan_id = wyi.id 
            where wyk.id = '".$yundian_id."' and wyk.customer_id = ".$this->customer_id." and wyi.customer_id = ".$this->customer_id." and wyk.isvalid = true and wyk.status = 1 and wyi.is_identity = 1 and wyi.isvalid = 1
            ";
        $res = $this->db->getRow($sql);
        
        $result = array(
            'yundian_setting' => $res_setting,
            'yundian_identity' => $res
        );

        return $result;
    }

    /*品牌订阅 验证有效性*/
    public function cheack_brand_auth($prod_id,$num,$pid){
        $time = date('Y-m-d H:i:s',time());
        //检查礼包身份是否过期
        $sql = "select p.activity_id,p.total_limit_num,p.day_limit_num,a.id,a.end_time,a.createtime from ".WSY_MARK.".brandsubscribe_relate_prod as p inner join ".WSY_MARK.".brandsubscribe_authorize as a on p.activity_id=a.activity_id where p.isvalid=true and a.isvalid=true and p.id='{$prod_id}' and a.customer_id='{$this->customer_id}' and a.user_id='{$this->user_id}' order by a.createtime desc limit 1";
        $res = $this->db->getRow($sql);
        
        //判断是否购买了礼包权限
        if ($res['id']) {
            //判断礼包权限是否过期
            if ($res['end_time'] <= $time) {
                $return["status"] = 4002;
                $return["msg"]    = "礼包权限已过期，无法购买！";
                return $return;
            }

            //判断当前活动是否已结束
            $activity_sql = "select id from ".WSY_MARK.".brandsubscribe_activity where id='{$res['activity_id']}' and isvalid=true and customer_id='{$this->customer_id}' and status=2 and start_time<='{$time}' and ((end_time >= '{$now}' AND time_type=1) OR time_type=2)";
            $res2 = $this->db->getRow($activity_sql);
            if (!$res2) {
                $return["status"] = 4005;
                $return["msg"]    = "活动已结束！";
                return $return;
            }

            //查询出用户购买的产品数量
            $sql2 = "select sum(rcount) as num from weixin_commonshop_orders where pid='{$pid}' and customer_id='{$this->customer_id}' and status!=-1 and shopactivity_mark=3 and isvalid=true and createtime>='{$res['createtime']}' and user_id='{$this->user_id}'";
            $all_num = $this->db->getOne($sql2);
            $sum_num = $all_num+$num;
            // var_dump($sum_num);

            if ($sum_num > $res['total_limit_num'] && $res['total_limit_num']!=-1) {
                $return["status"] = 4003;
                $return["msg"]    = "购买数量超过限制数量！";
                return $return;
            }

            //查询出当天购买的产品数量
            $time_start = date('Y-m-d 00:00:00',time());
            $time_end = date('Y-m-d H:i:s',time());
            $sql3 = "select sum(rcount) as num from weixin_commonshop_orders where pid='{$pid}' and customer_id='{$this->customer_id}' and status!=-1 and shopactivity_mark=3 and createtime between '{$time_start}' and '{$time_end}' and isvalid=true and createtime>='{$res['createtime']}' and user_id='{$this->user_id}'";
            $day_num = $this->db->getOne($sql3);
            $sum_num2 = $day_num+$num;
            // var_dump($sum_num2);
            //判断是否超过限制购买数量
            if ($sum_num2 > $res['day_limit_num'] && $res['day_limit_num']!=-1) {
                $return["status"] = 4004;
                $return["msg"]    = "购买数量超过当天限制数量！";
                return $return;
            }
            $return["status"] = 1;
            $return["msg"]    = "验证成功！";
            return $return;

        }else{
            $return["status"] = 4001;
            $return["msg"]    = "无礼包权限，无法购买！";
            return $return;
        }

    }
    //区块链产品信息
    public function block_chain_info($now_price,$block_chain_type,$block_chain_bfb,$block_chain_money){
        $is_block_chain         = 0;//是否区块链订单
        $block_chain_status     = 0;//未领取区块链积分
        $block_chain_reward     = 0;//区块链积分
        $block_chain_valid_time = '';//区块链积分有效期
        $query = "select on_off,valid_day,block_chain_type,block_chain_bfb,block_chain_money from ".WSY_SHOP.".block_chain_setting where customer_id='".$this->customer_id."'";
        $result = $this->db->getRow($query);
        if($result['on_off'] == 1){
            $is_block_chain = 1;
        }
        if($block_chain_type == 1 && $block_chain_bfb > 0 && $result['on_off'] == 1){
            //按百分比 产品价格*设置比例(保留4位)
            $block_chain_reward = $this->shopMessage->floor_decimals($now_price*$block_chain_bfb/100,4);
        }else if($block_chain_type == 2 && $block_chain_money > 0 && $result['on_off'] == 1){
            //按固定金额
            $block_chain_reward = $block_chain_money;
        }else if($block_chain_type == 1 && $block_chain_bfb == -1 && $result['on_off'] == 1){
            //产品比例设置-1时，使用全局变量
            if($result['block_chain_type'] == 1){
                $block_chain_reward = $this->shopMessage->floor_decimals($now_price*$result['block_chain_bfb']/100,4);
            }
        }
        if($result['valid_day'] > 0 && $result['on_off'] == 1){
            $block_chain_valid_time = date('Y-m-d H:i:s',strtotime('+'.$result['valid_day'].' day'));
        }else{
            //默认7天
            $block_chain_valid_time = date('Y-m-d H:i:s',strtotime('+7 day'));
        }
        $result = array(
            'is_block_chain'         => $is_block_chain ,
            'block_chain_status'     => $block_chain_status,
            'block_chain_reward'     => $block_chain_reward,
            'block_chain_valid_time' => $block_chain_valid_time
        );
        return $result;
    }
    //添加区块链积分明细
    public function block_chain_order_detail($order_log_arr) {
        extract($order_log_arr);

        $order_log_arr = array(
            'customer_id'=>$this->customer_id,
            'user_id'=>$this->user_id,
            'status'=>0,
            'batchcode' => $batchcode,
            'reward' => $reward,
            'createtime' => date('Y-m-d H:i:s'),
            'receivetime' => $receivetime,
            'pay_batchcode' => $pay_batchcode,
            'order_status'  => 0
        );
        $this->db->autoExecute(WSY_SHOP.'.block_chain_order_detail', $order_log_arr, 'insert');
    }
}






