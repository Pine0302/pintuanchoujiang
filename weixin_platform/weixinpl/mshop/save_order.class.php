<?php
require_once ROOT_DIR . 'weixinpl/common/common_ext.php';
// require_once ROOT_DIR . 'weixinpl/proxy_info.php';
require_once ROOT_DIR . 'mp/database.php';
// require_once ROOT_DIR . 'weixinpl/common/utility_fun.php';
require_once ROOT_DIR . 'weixinpl/common/utility_shop_pay.php';
// require_once ROOT_DIR . 'weixinpl/mshop/order_newform_function.php';
// require_once ROOT_DIR . 'wsy_pay/web/function/handle_order_function.php';
require_once ROOT_DIR . 'weixinpl/function_model/collageActivities.php';    //拼团
require_once ROOT_DIR . 'mshop/admin/model/slyder_adventures.php';      	//大转盘
require_once ROOT_DIR . 'weixinpl/common/utility_4m.php';
require_once ROOT_DIR . 'mshop/web/model/restricted_purchase.php';          //限购活动
require_once ROOT_DIR . 'mshop/web/controller/orderFormExtend.class.php';
require_once ROOT_DIR . 'weixinpl/mshop/create_shop_order.class.php';
require_once ROOT_DIR . 'weixinpl/mshop/tax_function.php';

class save_order
{
    public $user_id;
    public $customer_id;
    
    private $orderparam;
    private $shop_setting;
    private $user_data;
    //数据库操作类
    protected $db;
    
    protected $configutil;
    protected $shopMessage;
    protected $slyder_adventures;
    protected $model_integral;
    protected $shop_4m;
    protected $model_selfbuy_reward;
    protected $collageActivities;
    protected $create_shop_order;
    
    //当前时间戳（任何时间判断都以该时间为准）
    private $current_time;
    
    public function __construct($customer_id, $user_id) {
        $this->user_id      = $user_id;
        $this->customer_id  = $customer_id;
        $this->current_time = time();
        
        $this->db                       = DB::getInstance();
        $this->configutil               = new ConfigUtility();
        $this->shopMessage              = new shopMessage_Utlity();
        $this->model_slyder_adventures  = new model_slyder_adventures();
        $this->model_integral           = new model_integral();
        $this->shop_4m                  = new Utiliy_4m_new();
        $this->model_selfbuy_reward     = new model_selfbuy_reward();
        $this->collageActivities        = new collageActivities($this->customer_id);
        $this->shop_pay                 = new shop_pay($this->customer_id);
        $this->create_shop_order        = new create_shop_order($this->customer_id, $this->user_id, $this->current_time);
        
    }
    
    //主函数
    public function main() {
        //清除下单页session
        $this->clear_order_session();
        
        //获取订单参数
        $this->orderparam = $this->get_orderparam();
        // extract($orderparam);
        
        //获取商城设置
        $this->shop_setting = $this->get_shop_setting();
        // extract($shop_setting);
        
        //获取用户数据
        $this->user_data = $this->get_user_data();
        // extract($user_data);
        
        //订单业务逻辑
        $this->create_shop_order->create_shop_order($this->orderparam, $this->shop_setting, $this->user_data);
    }
    
    //获取订单参数
    private function get_orderparam() {
        $return = array(
            'from_pc'               => i2post('from_pc', 0),            //pc下单标识
            'order_data'            => i2post('json_data', ''),         //下单也订单数据
            'sum_shop_reward_en'    => i2post('sum_shop_reward_en', ''),//店铺奖励抵扣   	md5加密的数据作检验使用
            'sum_self_reward_en'    => i2post('sum_self_reward_en', ''),//3级奖励抵扣		md5加密的数据作检验使用
            'sendstyle'             => i2post('sendstyle', -1),         //发货方式
            'pay_immed'             => i2post('pay_immed', -1),         //1立即购买，2找人代付
            'paystyle'              => i2post('pay_type', -1),          //支付方式
            'industry_type'         => i2post('industry_type', 'shop'), //行业类型
            'paystyle1'             => i2post('paystyle1', ''),         //新支付方式名字
            'o_shop_id_ex'          => i2post('o_shop_id', ''),         //订货系统门店id
            'or_shop_type_ex'       => i2post('or_shop_type', ''),      //订货系统门店类型：1:总店；2:子门店；只有当o_shop_id大于1的时候才有意义
            'or_code_ex'            => i2post('or_code', ''),           //订货系统关联物码
            'user_open_curr'        => i2post('user_open_curr', ''),    //购买者是否开启购物币加入支付开关
            'user_currency'         => json_decode(stripslashes(html_entity_decode(i2post('user_currency', ''))), true),    //购买者使用的购物币
            'is_select_card'        => i2post('is_select_card', 0),     //会员卡使用开关
            'card_member_id'        => passport_decrypt((string)i2post('select_card_id', -1)),//会员卡id
            'sendtime'              => i2post('sendtime', ''),          //送货时间
            'is_payother'           => i2post('is_payother', 0),        //是否代付状态
            'payother_desc'         => '',                              //代付描述
            'diy_area_id'           => passport_decrypt((string)i2post('diy_area_id', -1)), //区域模式 - 自定义区域编号
            'check_first_extend'    => i2post('check_first_extend', 0), //是否符合首次推广奖励订单
            'delivery_time_start'   => '',                              //预配送时间
            'delivery_time_end'     => '',                              //预配送时间
            'is_collage_product_info' => i2post('is_collage_product_info', ''), //是否走拼团路线，拼团标识_单独购买或团购_单独购买价格_团购价_活动id_团id
            'is_collage_product'    => 0,                               //拼团标识
            'group_buy_type'        => 0,                               //1：单独购买，2：团购
            'activitie_id'          => -1,                              //拼团活动id
            'group_id'              => -1,                              //团id
            'is_head'               => 2,                               //拼团活动角色，1团长，2参团人
            'head_id'               => -1,                              //团长id
            'fromuser_app'          => $_SESSION["fromuser_app_" . $this->customer_id], //app运营商的粉丝标识
            'is_delivery_order'     => $_POST['delivery_arr'] ? addslashes($_POST['delivery_arr']) : 0,       //货到付款
            'aid'                   => passport_decrypt((string)i2post('aid', '')), //收获地址id

            'yundian_id'            => i2post('yundian_id', ''),        //当前下单的云店ID
            'brand_return'           => i2post('brand_return', ''),        //是否复购
            'brand_price'            => i2post('brand_price', ''),        //复购价
            'bs_relate_prod_id'      => i2post('bs_relate_prod_id', ''),        //关联产品id
        );

        
        if(!$return['from_pc']) {
            $return['order_data'] = json_decode($return['order_data'], true);  //json转数组
        }
        // var_dump($return['order_data']);
        //代付
        if($return['is_payother']) {
            $return['payother_desc'] = i2post('payother_desc', '蛋蛋的忧伤，钱不够了，你能不能帮我先垫付下');     //代付描述
        }
        
        //预配送
        if( !empty(i2post('delivery_time', '')) ){
            $delivery_time_arr = explode('_', i2post('delivery_time', ''));
            $return['delivery_time_start'] = $delivery_time_arr[0];
            $return['delivery_time_end'] = $delivery_time_arr[1];
        }
        
        //拼团
        if( !empty($return['is_collage_product_info']) ){
            $collage_product = explode('_', $return['is_collage_product_info']);
            $return['is_collage_product'] = $collage_product[0];
            $return['group_buy_type']     = $collage_product[1];
            $return['group_price']        = 0;
            $return['activitie_id']       = $collage_product[4];
            $return['group_id']           = $collage_product[5];
            if($return['group_id'] < 0) {
                $return['is_head'] = 1;
                $return['head_id'] = $this->user_id;
            }
        }
        
        return $return;
    }
    
    //获取商城设置
    public function get_shop_setting() {
        $return = array(
            'isOpenCurrency'        => 0,           //购物币开关
            'custom'                => '购物币',    //购物币自定义名称
            'user_own_currency'     => 0,           //用户拥有的购物币数量
            'is_charitable'         => 0,           //慈善公益开关
            'charitable_propotion'  => 0,           //慈善公益最低分配率
            'integration_price'     => 1,           //捐赠多少钱得1积分
            'issell'                => false,       //false:没开启分佣 true:开启分佣
            'exp_name'              => '推广员',    //推广员自定义名称
            'distr_type'            => 1,           //1:下单锁定 2:第一次关注锁定
            'is_identity'           => 0,           //是否开启身份证验证
            'init_reward'           => 0,           //推广比例
            'reward_type'           => 2,           //返佣类型 1:积分 2:金额
            'issell_model'          => 1,           //1:关闭复购;2:开启复购
            'shop_card_id'          => -1,          //商家设定的会员卡id
            'is_cost_limit'         => 0,           //是否开启购买限制
            'sell_discount'         => 0,           //商家设定的产品折扣率
            'per_cost_limit'        => 9999,        //每人每天不高于的总额
            'is_number_limit'       => 0,           //是否开启购买数量限制
            'is_weight_limit'       => 0,           //是否开启重量限制
            'per_weight_limit'      => 999,         //每人每天不高于的KG
            'per_identity_num'      => 999,         //每个身份证号每天可下单数量
            'per_number_limit'      => 999,         //每人每天不多于多少件产品
            'shop_is_Pinformation'  => 0,           //必填信息开关:1、开；0、关
            'recovery_time'         => 30,          //订单支付失效时间（分）
            'yundian_recovery_time' => 30,  //云店自营订单支付失效时间（分）       //仅云店自营订单使用
            'is_memberBuyMessage'   => 1,           //下级会员购物消息开关:1、开；0、关
            'is_buyContentMessage'  => 1,           //下级会员购物消息消息（关闭购物内容）开关:1、开；0、关
            'is_receipt'            => 0,           //收货结算开关
            'is_opencalcmode'       => 0,           //是否开启计算模式
            'calcmode'              => 0,           //计算模式  1：比值模式  2：扣除模式
            'calcobj'               => '1_2',       //计算对象  1：优惠券  2：购物币  多个则使用下划线“_”隔开
            'is_distribution'       => 0,           //是否开启代理商功能
            'consume_score'         => 0,           //消费1元返多少会员卡积分
            'identity_order'        => 0,           //0普通订单，1身份证订单
            'isvp_switch'           => 0,           //vp值开关，0关，1开
            'is_open_aftersale'     => "1_1_1"      //订单售后开关 退款_退货_换货           
        );
        
        //是否开启4m模式
        $return['is_4m'] = $this->shop_4m->is_4M_new($this->customer_id);
        
        //购物币设置
        $sql = "SELECT isOpenCurrency, custom ,isOpen FROM weixin_commonshop_currency WHERE customer_id='{$this->customer_id}'";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }
        
        //慈善公益
        $sql = "SELECT is_charitable, charitable_propotion, integration_price FROM charitable_set_t WHERE customer_id='{$this->customer_id}' AND isvalid=TRUE";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }
        
        //商城购物设置
        $sql = "SELECT issell, exp_name, distr_type, is_identity, init_reward, reward_type, issell_model, shop_card_id, is_cost_limit, sell_discount, per_cost_limit, is_number_limit, is_weight_limit, per_weight_limit, per_identity_num, per_number_limit FROM weixin_commonshops WHERE customer_id='{$this->customer_id}' AND isvalid=TRUE";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }
        
        //商城设置
        $sql = "SELECT is_Pinformation AS shop_is_Pinformation, recovery_time, is_memberBuyMessage, is_buyContentMessage, is_receipt, is_opencalcmode, calcmode, calcobj FROM weixin_commonshops_extend WHERE customer_id='{$this->customer_id}' AND isvalid=TRUE";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }
        
        //vp值开关
        $sql = "SELECT isvp_switch FROM weixin_commonshop_vp_bases WHERE isvalid=TRUE AND customer_id='{$this->customer_id}'";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }
        
        //拼团订单支付有效时间只有5分钟
        if($this->orderparam['is_collage_product'] && $this->orderparam['group_buy_type'] == 2) {   
            $return['recovery_time'] = $this->current_time + 5 * 60;
        } else {
            $return['recovery_time'] = $this->current_time + $return['recovery_time'] * 60;
        }
        $return['recovery_time'] = date("Y-m-d H:i:s", $return['recovery_time']);

        //判断云店开关是否开启
        $sql_yundian = "select yundian_onoff,invalid_onoff,invalid_time from ".WSY_REBATE.".weixin_yundian_setting where customer_id='{$this->customer_id}' and isvalid = true ";
        $res         = $this->db->getRow($sql_yundian);

        //若开启，自营商品时间为云店基本设置中订单失效时间
        if($res['yundian_onoff'] == true && $res['invalid_onoff'] == true && $res['invalid_time'] > 0)
        {
            $return['yundian_recovery_time'] = $this->current_time + $res['invalid_time'] * 60;
            $return['yundian_recovery_time'] = date("Y-m-d H:i:s", $return['yundian_recovery_time']);
        }

        
        //是否开启代理商功能
        $sql = "SELECT COUNT(1) AS is_disrcount FROM customer_funs cf INNER JOIN columns c WHERE cf.customer_id='{$this->customer_id}' AND cf.isvalid=TRUE AND cf.column_id=c.id AND c.sys_name='商城代理模式' AND c.isvalid=TRUE";
        $is_disrcount = $this->db->getOne($sql);
        
        if($is_disrcount > 0) {
           $return['is_distribution'] = 1;
        }
        
        //会员卡设置
        $sql = "SELECT consume_score FROM weixin_cards WHERE id='{$return['shop_card_id']}' AND customer_id='{$this->customer_id}' AND isvalid=TRUE";
        $return['consume_score'] = $this->db->getOne($sql);
        
        return $return;
    }
    
    //获取用户数据
    public function get_user_data() {
        $return = array(
            'weixin_name'           => '',                          //微信名
            'parent_id'             => -1,                          //上级id
            'promoter_id'           => -1,                          //是否推广员
            'promoter_isvalid'      => 0,                           //推广员身份是否有效，1：有效，0：无效
            'term_of_validity'      => '3000-01-01 00:00:00',       //推广员有效期
            'is_promoter_permanent' => 0,                           //是否需要续费推广员，1是，0否
            'is_consume'            => 0,                           //店铺身份等级
            'Plevel'                => 0,                           //推广员身份等级
            'exp_user_id'           => -1,                          //推广员身份等级
            'agent_id'              => -1,                          //代理商id
            'agentcont_type'        => 0,                           //是否代理商结算，1是，0否
            'card_discount'         => 0,                           //会员卡折扣
            'identity_today_num'    => 0,                           //身份证号码当天已下单数量
            'address_info'          => array(),                     //收货地址信息
            'identity_info'         => array(),                     //身份证信息
        );
        
        $sql = "SELECT name,weixin_name, parent_id, weixin_fromuser FROM weixin_users WHERE id='{$this->user_id}' AND customer_id='{$this->customer_id}' AND isvalid=TRUE";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
            
            if($key == 'parent_id') {
                $return['exp_user_id'] = $val;
            }
            if($key == 'weixin_name')
            {
                $return['weixin_name'] = $res['name'].'('.$val.')';
            }
        }
        
        $sql = "SELECT id AS promoter_id, term_of_validity, is_consume, commision_level AS Plevel FROM promoters WHERE user_id='{$this->user_id}' AND isvalid=TRUE AND status=1";
        $res = $this->db->getRow($sql);
        foreach($res as $key => $val) {
            $return[$key] = $val;
        }
        
        if($return['promoter_id'] > 0) {
            //推广员是否有效
            if(strtotime($return['term_of_validity']) >= $this->current_time) {
                $return['promoter_isvalid'] = 1;
            }
            
            //推广员有效期是否永久，非永久则可以续期
            if(strtotime($return['term_of_validity']) < strtotime('3000-01-01 00:00:00')) {
                $return['is_promoter_permanent'] = 1;
            }
            
            //开启复购，订单的上级是自己
            if($this->shop_setting['issell_model'] == 2) {
                $return['exp_user_id'] = $this->user_id;
            }
        }
        
        //购物币
        $sum_currency = 0;
        foreach($this->orderparam['user_currency'] as $key => $val) {
            $sum_currency += $val[1];
        }
        
        //判断是否有足够购物币
        if($sum_currency > 0) {
            $sql = "SELECT currency FROM weixin_commonshop_user_currency WHERE user_id='{$this->user_id}' AND isvalid=TRUE";
            $user_currency = $this->db->getOne($sql);
            
            if(empty($user_currency) || $user_currency < $sum_currency) {
                $json["status"] = 10004;
                $json["msg"] = "{$this->shop_setting['custom']}不足！";
                $jsons = json_encode($json);
                die($jsons);
            }
        }
        
        //查找代理商id
        if($this->shop_setting['is_distribution']) {
            $return['agent_id'] = $this->shopMessage->searchParentId($this->user_id, $this->customer_id);
        }
        
        //用户会员卡信息
        if($this->orderparam['is_select_card'] > 0 && $this->orderparam['card_member_id'] > 0) {
            $sql = "SELECT IFNULL(wcl.discount, 0) AS discount FROM weixin_card_members wcm LEFT JOIN weixin_card_levels wcl ON wcm.level_id=wcl.id WHERE wcm.id='{$this->orderparam['card_member_id']}' AND wcl.isvalid=TRUE";
            $return['card_discount'] = $this->db->getOne($sql);
        }
        
        //用户收货地址和身份证信息
        if($this->orderparam['aid'] > 0) {
            $sql = "SELECT name, phone, address, location_p, location_c, location_a, identity FROM weixin_commonshop_addresses WHERE id='{$this->orderparam['aid']}' AND user_id='{$this->user_id}' AND isvalid=TRUE";
            $res = $this->db->getRow($sql);
            foreach($res as $key => $val) {
                $return['address_info'][$key] = mysql_real_escape_string($val);
            }
            
            $sql = "SELECT identity, identityimgt, identityimgf FROM weixin_commonshop_identity_info WHERE user_id='{$this->user_id}' AND name='{$return['address_info']['name']}' AND isvalid=TRUE";
            $res = $this->db->getRow($sql);
            foreach($res as $key => $val) {
                $return['identity_info'][$key] = mysql_real_escape_string($val);
            }
        }
        
        //身份证号码当天已下单数量
        if($this->shop_setting['is_identity']) {
            $this->shop_setting['identity_order'] = 1;
            $sql = "SELECT COUNT(1) AS wcount FROM weixin_commonshop_orders WHERE identity='{$return['identity_info']['identity']}' AND customer_id='{$this->customer_id}' AND identity_order=1 AND isvalid=TRUE AND status != -1 AND createtime>='" . date('Y-m-d', $this->current_time) . " 00:00:00' AND createtime<='" . date('Y-m-d', $this->current_time) . " 23:59:59' GROUP BY batchcode";
            $return['identity_today_num'] = $this->db->getOne($sql);
        }
        
        //开启重量限制
        if($this->shop_setting['is_weight_limit']) {
            //身份证号码当天已购买产品重量
            if($this->shop_setting['is_identity']) {
                $sql = "SELECT SUM(weight) AS iweight FROM weixin_commonshop_orders WHERE isvalid=true AND identity_order=1 AND status!=-1 AND identity='{$return['identity_info']['identity']}' AND createtime>='" . date('Y-m-d', $this->current_time) . " 00:00:00' AND createtime<='" . date('Y-m-d', $this->current_time) . " 23:59:59'";
                $return['identity_today_weight'] = $this->db->getOne($sql);
            }
            
            //用户当天已购买产品重量
            $sql = "SELECT SUM(weight) AS uweight FROM weixin_commonshop_orders WHERE isvalid=true AND status!=-1 AND user_id='{$this->user_id}' AND createtime>='" . date('Y-m-d', $this->current_time) . " 00:00:00' AND createtime<='" . date('Y-m-d', $this->current_time) . " 23:59:59'";
            $return['user_today_weight'] = $this->db->getOne($sql);
            
        }
        
        //开启购买金额限制
        if($this->shop_setting['is_cost_limit']) {
            if($this->shop_setting['is_identity']) {
                //身份证号码当天已下单总额
				$sql = "SELECT SUM(totalprice) AS identity_today_totalprice, SUM(expressPrice) AS identity_today_expressPrice FROM ( SELECT SUM(orders.totalprice) AS totalprice, MAX(express.price) AS expressPrice FROM weixin_commonshop_orders AS orders LEFT JOIN weixin_commonshop_order_express_prices AS express ON orders.batchcode=express.batchcode WHERE orders.isvalid=true AND orders.status!=-1 AND orders.identity_order=1 AND orders.identity='{$return['identity_info']['identity']}' AND orders.createtime>='" . date('Y-m-d', $this->current_time) . " 00:00:00' AND orders.createtime<='" . date('Y-m-d', $this->current_time) . " 23:59:59' GROUP BY orders.batchcode ) a";
				$res = $this->db->getRow($sql);
                foreach($res as $key => $val) {
                    $return[$key] = $val;
                }
			}
            
            //用户当天已下单总额
            $sql = "SELECT SUM(totalprice) AS user_today_totalprice, SUM(expressPrice) AS user_today_expressPrice FROM ( SELECT SUM(orders.totalprice) AS totalprice, MAX(express.price) AS expressPrice FROM weixin_commonshop_orders AS orders LEFT JOIN weixin_commonshop_order_express_prices AS express ON orders.batchcode=express.batchcode WHERE orders.isvalid=true AND orders.status!=-1 AND orders.user_id='{$this->user_id}' AND orders.createtime>='" . date('Y-m-d', $this->current_time) . " 00:00:00' AND orders.createtime<='" . date('Y-m-d', $this->current_time) . " 23:59:59' GROUP BY orders.batchcode ) a";
			$res = $this->db->getRow($sql);
            foreach($res as $key => $val) {
                $return[$key] = $val;
            }
        }
        
        //开启产品数量限制
        if($this->shop_setting['is_number_limit']) {
            if($this->shop_setting['is_identity']) {
                //身份证号码当天已购买产品数量
				$sql = "SELECT SUM(rcount) as identity_today_prod_num FROM weixin_commonshop_orders WHERE isvalid=true AND identity_order=1 AND status!=-1 AND  identity='{$return['identity_info']['identity']}' AND createtime>='" . date('Y-m-d', $this->current_time) . " 00:00:00' AND createtime<='" . date('Y-m-d', $this->current_time) . " 23:59:59'";
				$return['identity_today_prod_num'] = $this->db->getOne($sql);
			}
            
            //用户当天已购买产品数量
            $sql = "SELECT SUM(rcount) as user_today_prod_num FROM weixin_commonshop_orders WHERE isvalid=true AND status!=-1 AND  user_id='{$this->user_id}' AND createtime>='" . date('Y-m-d', $this->current_time) . " 00:00:00' AND createtime<='" . date('Y-m-d', $this->current_time) . " 23:59:59'";
            $return['user_today_prod_num'] = $this->db->getOne($sql);
        }
        
        return $return;
    }
    
    //保存订单前清除确认订单页面的session
    private function clear_order_session() {
        $_SESSION['bug_post_data_'.$this->user_id]      = '';			//清除购物车数据
        $_SESSION['sendtime_id_'.$this->user_id]        = '';			//清除送货时间
        $_SESSION['rtn_sendtime_array_'.$this->user_id] = '';
        $_SESSION['a_type_'.$this->user_id]             = -1;           //清除选择地址的session
        $_SESSION['diy_area_id_'.$this->user_id]        = '';			//清除自定义区域
        $_SESSION['rtn_diy_area_array_'.$this->user_id] = '';
        $_SESSION['check_first_extend_'.$this->user_id] = '';           //首次推广奖励
        $_SESSION['is_virtual_'.$this->user_id]         = '';           //是否虚拟产品
        $_SESSION['is_collage_product_'.$this->user_id] = '';           //拼团产品标识
        $_SESSION['delivery_time_'.$this->user_id]      = '';           //预配送时间
        $_SESSION['o_shop_id_'.$this->user_id]          = '';           //订货系统门店id
        $_SESSION['exchange_'.$this->user_id]           = '';
        $_SESSION['yundian_id_' . $this->user_id]       = '';
        $_SESSION['bs_relate_prod_id_' . $this->user_id]  = '';
        $_SESSION['brand_price_' . $this->user_id]           = '';
        $_SESSION['brand_return_' . $this->user_id]           = '';
        
        /*sz郑培强添加用于众筹活动 砍价活动*/
        $_SESSION['form_bargain_sz']                    = '';
        $_SESSION['form_crowdfund_sz']                  = '';
        $_SESSION['form_bargain_sz_data']               = '';
        $_SESSION['form_crowdfund_sz_data']             = '';
        /*sz郑培强添加用于众筹活动 砍价活动*/
    }
}