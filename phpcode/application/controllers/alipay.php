<?php
class Alipay extends P_Controller {
    /**
     *支付宝网关地址（新）
     */
    var $alipay_gateway_new = 'https://mapi.alipay.com/gateway.do?';
    var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
    var $alipay_config;
    function __construct() {
        parent::__construct(false);
        $this->load->model('pay_model');
        $this->alipay_config  = $this->passport->get('alipay_config');
    }
    
    function index()
    {
        $this->template->load('template', 'alipay_from');
    }
    
    public function doalipay() {
        $params = $this->get_public_params();
        $params['product_id']   = $this->request_param('product_id');
        $params['number']       = $this->request_param('number');
        $params['pay_method']   = $this->request_param('pay_method'); // 可以是人民币,也可以是美元, 还可以是其他平台的货币
        
        // PLAYME平台 生成订单
        if (!$params['product_id'] ||  $params['pay_method'] === '' || $params['channel_id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if (!$params['number']) {
            $params['number'] = 1;
        }
        if (!$params['os']) {
            $params['os'] = 2; //  给个默认值 2：pc(web端)
        }
        $product_info   = $this->pay_model->product_info($params['product_id']);
        if (!$product_info) {
            $this->output_json_return();
        }
        $glod = 0;
        if ($product_info['pro_type'] == 2) { // 表示:充值（人名币换金币）
            // 计算可以换取的金币数量
            $recharge_idx = $product_info['p_idx'];
            $recharge_info = $this->pay_model->get_recharge_info_by_idx($recharge_idx);
            if (!$recharge_info) {
                $this->output_json_return();
            }
            $glod = $recharge_info['glod'] * $params['number'];
        } else if ($product_info['pro_type'] == 3) { // 表示充值：货币换金币
            
        }
        $this->pay_model->start();
        // 将用户预购商品，插入到订单表中
        if ($recharge_info['rmb']) {
            $product_info['price_current'] = $recharge_info['rmb'];
        }
        $total_price = $product_info['price_current'] * $params['number'];
        $nickname = $this->utility->get_user_info($params['uuid'], 'nickname');
        if (!$nickname) {
            $this->error_->set_error(Err_Code::ERR_USER_INFO_NO_DATA);
            $this->output_json_return();
        }
        $feilds = array(
            'uuid'          => $params['uuid'],
            'nickname'      => $nickname,
            'total_price'   => $total_price,
            'number'        => $params['number'],
            'product_id'    => $params['product_id'],
            'product_type'  => $product_info['pro_type'],
            'glod'          => $glod,
            'pay_method'    => $params['pay_method'],
            'device_type'   => $params['os'],
        );
        if (!$params['fee_type']) {
            $feilds['fee_type'] = 'CNY';
        } else {
            $feilds['fee_type'] = $params['fee_type'];
        }
        $feilds['order_id'] = time().$params['uuid'];
        $res = $this->pay_model->insert_order($feilds); // 返回订单ID
        if (!$res) {
            $this->pay_model->error();
            $this->error_->set_error(Err_Code::ERR_PRODUCT_ORDER_INSERT_FAIL);
            $this->output_json_return();
        }
        $this->pay_model->success();
        $total_fee = round($total_price/100, 2);
        if ((int)$params['pay_method'] === 0) { // 微信支付方式
            
        } else if ((int)$params['pay_method'] === 1) { // 支付宝支付方式
            // 请求参数
            $alipay         = $this->passport->get('alipay');
            $payment_type   = "1"; // 请求类型 必填，不能修改
            $notify_url     = $alipay['notify_url']; // 异步通知url
            $return_url     = $alipay['return_url']; // 同步通知url
            $out_trade_no   = $feilds['order_id']; // 商户订单号
            $subject        = $product_info['name']; // 商户订单名称 必填
            $show_url       = ''; // 商品展示地址
            $body           = ''; // 商户订单描述;

            // 参数组建
            //构造要请求的参数数组，无需改动
            $parameter = array(
                    "service"           => "alipay.wap.create.direct.pay.by.user",
                    "partner"           => trim($this->alipay_config['partner']),
                    "seller_id"         => trim($this->alipay_config['seller_id']),
                    "payment_type"	=> $payment_type,
                    "notify_url"	=> $notify_url,
                    "return_url"	=> $return_url,
                    "out_trade_no"	=> $out_trade_no,
                    "subject"           => $subject,
                    "total_fee"     	=> $total_fee,
                    "show_url"          => $show_url,
                    "body"              => $body,
                    "_input_charset"=> trim(strtolower($this->alipay_config['input_charset']))
            );
            // 建立请求
            $html_text = $this->buildRequestForm($parameter, "get", "确认");
            echo $html_text; // 输出支付表单
        }
        
    }
    
    /**
     * 服务器异步通知处理
     */
    public function notifyurl()
    {
        // ---------------------------测试是否调用支付宝---------------------
        $fields = array(
            'uuid' => 1111,
            'nickname' => '1111',
            'mobile' => '1111',
            'score' => '1',
        );
        $this->load->model('game_model');
        $this->game_model->insert_game_score($fields);
        // --------------------------------------------------------------------
        
        // $verify_result  = $this->verifyNotify();
        $verify_result = 1;
        if ($verify_result) {
            //验证成功
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $out_trade_no   = $_POST['out_trade_no'];      //商户订单号
            $trade_no       = $_POST['trade_no'];          //支付宝交易号
            $trade_status   = $_POST['trade_status'];      //交易状态
            $total_fee      = $_POST['total_fee'];         //交易金额
            $notify_id      = $_POST['notify_id'];         //通知校验ID。
            $notify_time    = $_POST['notify_time'];             //通知的发送时间。格式为yyyy-MM-dd HH:mm:ss。
            $buyer_email    = $_POST['buyer_email'];       //买家支付宝帐号；
//            $parameter = array(
//                "out_trade_no"  => $out_trade_no, //商户订单编号；
//                "trade_no"      => $trade_no,     //支付宝交易号；
//                "total_fee"     => $total_fee,    //交易金额；
//                "trade_status"  => $trade_status, //交易状态
//                "notify_id"     => $notify_id,    //通知校验ID。
//                "notify_time"   => $notify_time,  //通知的发送时间。
//                "buyer_email"   => $buyer_email,  //买家支付宝帐号；
//            );
            $order_id = $out_trade_no;
            if(strtoupper($_POST['trade_status']) == 'TRADE_FINISHED') {
                // 退款交易
                $order_status = 0; // 订单成功 0 
            }else if (strtoupper($_POST['trade_status']) == 'TRADE_SUCCESS') {
                $order_status = 0; // 订单成功 0 
                //判断该笔订单是否在商户网站中已经做过处理
                $res = $this->pay_model->query_order($order_id);
                if ($res['is_callback'] == 1) { // 表示，回调已经处理过了
                    echo "SUCCESS";
                    return;
                }
                // ---- 执行回滚
                $this->pay_model->start();
                // 更新 下 O_CALLBACK = 1 ：表示已调用回调
                $upt_ord = $this->pay_model->update_wx_callback_status($order_id, 1);
                if (!$upt_ord) {
                    $this->pay_model->error();
                    echo "FAIL";
                    return;
                }
                $upt_ord = $this->pay_model->update_order($order_id, $order_status);
                if (!$upt_ord) {
                    $this->pay_model->error();
                    echo "FAIL";
                    return;
                }
                // 支付成功之后 ， 判断是否是充值（人名币换金币），修改该用户的金币数量，几变更历史 TODO
                if ($res['product_type'] == 2) {
                    // 1.更新用户金币（用户表）
                    $user_info = $this->utility->get_user_info($res['uuid']);
                    $fields = array('U_GOLD' => $res['get_glod'] + $user_info['coin']);
                    $rst = $this->user_model->update_user_info($res['uuid'], $fields);
                    if ($rst === false) {
                        $this->error_->set_error(Err_Code::ERR_BUY_GAME_REDUCE_COIN_FAIL);
                        $this->pay_model->error();
                        echo "FAIL";
                        return false;
                    }
                    // 2.记录金币变更历史
                    $coin_info = array(
                        'change_coin'   => $res['get_glod'],
                        'coin'          => $res['get_glod'] + $user_info['coin'],
                    );
                    $rst = $this->user_model->record_coin_change_history($res['uuid'],$user_info['nickname'],$coin_info,0,5);
                    if(!$rst) {
                        $this->pay_model->error();
                        echo "FAIL";
                        return false;
                    }

                    // 3.记录充值记录
                    /*
                    * 记录用户充值记录
                    * $recharge_info = array(
                    *      recharge_num, 充值数量
                    *      recharge_rmb, 充值人名币
                    *      get_glod,     获得的金币
                    *      content       充值包说明信息
                    * )
                    */
                    $recharge_info = array(
                        'recharge_num' => $res['number'],
                        'recharge_rmb' => $res['total_price'],
                        'get_glod'     => $res['get_glod'],
                        'content'      => '充值'.$res['total_price']/$res['number'].'人民币,获得金币'.$res['geet_glod'],
                    );
                    $rst = $this->user_model->record_user_recharge_history($res['uuid'],$user_info['nickname'],$recharge_info);
                    if(!$rst) {
                        $this->pay_model->error();
                        echo "FAIL";
                        return false;
                    }
                }
                // ---- 执行回滚结束
                $this->pay_model->success();
            }
            echo "success";        //请不要修改或删除
        } else {
            //验证失败
            echo "fail";
        }
    }
    
    /**
     * 页面跳转处理方法
     */
    public function returnurl()
    {
        // 测试是否调用支付宝
        $fields = array(
            'uuid' => 2222,
            'nickname' => '2222',
            'mobile' => '2222',
            'score' => '2',
        );
        $this->load->model('game_model');
        $this->game_model->insert_game_score($fields);
        
        // $verify_result = $this->verifyReturn();
        $verify_result = 1;
        if ($verify_result) {
            // 验证成功
            //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表
            $out_trade_no   = $_GET['out_trade_no'];      //商户订单号
            $trade_no       = $_GET['trade_no'];          //支付宝交易号
            $trade_status   = $_GET['trade_status'];      //交易状态
            $total_fee      = $_GET['total_fee'];         //交易金额
            $notify_id      = $_GET['notify_id'];         //通知校验ID。
            $notify_time    = $_GET['notify_time'];       //通知的发送时间。
            $buyer_email    = $_GET['buyer_email'];       //买家支付宝帐号；
            $order_id       = $out_trade_no;
            if (strtoupper($_GET['trade_status']) == 'TRADE_FINISHED' || strtoupper($_GET['trade_status']) == 'TRADE_SUCCESS') {
                // 支付成功
                //判断该笔订单是否在商户网站中已经做过处理
                $res = $this->pay_model->query_order($order_id);
                if ($res['is_callback'] == 1) { // 表示，回调已经处理过了
                    echo "验证成功";
                    return;
                }
                // ---- 执行回滚
                $this->pay_model->start();
                // 更新 下 O_CALLBACK = 1 ：表示已调用回调
                $upt_ord = $this->pay_model->update_wx_callback_status($order_id, 1);
                if (!$upt_ord) {
                    $this->pay_model->error();
                    echo "验证成功";
                    return;
                }
                $order_status = 0;// 订单支付成功 0 
                $upt_ord = $this->pay_model->update_order($order_id, $order_status);
                if (!$upt_ord) {
                    $this->pay_model->error();
                    echo "验证成功";
                    return;
                }
                // 支付成功之后 ， 判断是否是充值（人名币换金币），修改该用户的金币数量，几变更历史 TODO
                if ($res['product_type'] == 2) {
                    // 1.更新用户金币（用户表）
                    $user_info = $this->utility->get_user_info($res['uuid']);
                    $fields = array('U_GOLD' => $res['get_glod'] + $user_info['coin']);
                    $rst = $this->user_model->update_user_info($res['uuid'], $fields);
                    if ($rst === false) {
                        $this->pay_model->error();
                        echo "验证成功";
                        return;
                    }
                    // 2.记录金币变更历史
                    $coin_info = array(
                        'change_coin'   => $res['get_glod'],
                        'coin'          => $res['get_glod'] + $user_info['coin'],
                    );
                    $rst = $this->user_model->record_coin_change_history($res['uuid'],$user_info['nickname'],$coin_info,0,5);
                    if(!$rst) {
                        $this->pay_model->error();
                        echo "验证成功";
                        return;
                    }
                    // 3.记录充值记录
                    /*
                    * 记录用户充值记录
                    * $recharge_info = array(
                    *      recharge_num, 充值数量
                    *      recharge_rmb, 充值人名币
                    *      get_glod,     获得的金币
                    *      content       充值包说明信息
                    * )
                    */
                    $recharge_info = array(
                        'recharge_num' => $res['number'],
                        'recharge_rmb' => $res['total_price'],
                        'get_glod'     => $res['get_glod'],
                        'content'      => '充值'.$res['total_price']/$res['number'].'人民币,获得金币'.$res['geet_glod'],
                    );
                    $rst = $this->user_model->record_user_recharge_history($res['uuid'],$user_info['nickname'],$recharge_info);
                    if(!$rst) {
                        $this->pay_model->error();
                        echo "验证成功";
                        return;
                    }
                }
                // ---- 执行回滚结束
                $this->pay_model->success();
            } else {
                echo "trade_status=".$_GET['trade_status'];
            }
            echo "验证成功<br />";
        } else {
            // 验证失败
            echo "验证失败";
        }
    }
    
    /**
     * 建立请求(组建想支付宝请求的数据)，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
    function buildRequestForm($para_temp, $method, $button_name) {
            //待请求参数数组
            $para = $this->buildRequestPara($para_temp);

            $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->alipay_gateway_new."_input_charset=".trim(strtolower($this->alipay_config['input_charset']))."' method='".$method."'>";
            while (list ($key, $val) = each ($para)) {
        $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
    }

		//submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='".$button_name."'></form>";
		
		$sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
		
		return $sHtml;
	}
    
    /**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    function buildRequestPara($para_temp) {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        $para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
        
        return $para_sort;
    }
    
    /**
    * 除去数组中的空值和签名参数
    * @param $para 签名参数组
    * return 去掉空值与签名参数后的新签名参数组
    */
   function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
                if($key == "sign" || $key == "sign_type" || $key == '_URL_' || $val == "")continue;    //添加了$key == '_URL_' 
                else	$para_filter[$key] = $para[$key];
        }
        return $para_filter;
   }
   
   /**
    * 对数组排序
    * @param $para 排序前的数组
    * return 排序后的数组
    */
   function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
   }
   
    /**
    * 生成签名结果
    * @param $para_sort 已排序要签名的数组
    * return 签名结果字符串
    */
   function buildRequestMysign($para_sort) {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);
        
        $mysign = "";
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "RSA" :
                $mysign = $this->rsaSign($prestr, $this->alipay_config['private_key_path']);
                break;
            case "MD5":
                $mysign = $this->md5Sign($prestr, $this->alipay_config['key']);
                break;
            default :
                $mysign = "";
        }
        return $mysign;
   }
   
   /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }
    
    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key_path 商户私钥文件路径
     * return 签名结果
     */
    function rsaSign($data, $private_key_path) {
        $priKey = file_get_contents($private_key_path);
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
            //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    function md5Sign($prestr, $key) {
        $prestr = $prestr . $key;
        return md5($prestr);
    }
    
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息  (跟踪通知)
     * @return 验证结果
     */
    function verifyNotify(){
        if(empty($_POST)) {//判断POST来的数组是否为空
            return false;
        } else {
            //生成签名结果
            $isSign = $this->getSignVeryfy($_POST, $_POST["sign"]);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'true';
            if (! empty($_POST["notify_id"])) {
                $responseTxt = $this->getResponse($_POST["notify_id"]);
            }
            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match("/true$/i",$responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }
    
    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign) {
         //除去待签名参数数组中的空值和签名参数
         $para_filter = $this->paraFilter($para_temp);
         //对待签名参数数组排序
         $para_sort = $this->argSort($para_filter);
         //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
         $prestr = $this->createLinkstring($para_sort);
         $isSgin = false;
         switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "RSA":
               $isSgin = $this->rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
               break;
            case "MD5":
                $isSgin = $this->md5Verify($prestr, trim($this->alipay_config['key']), $sign);
                break;
            default :
               $isSgin = false;
         }
         return $isSgin;
     }
   
    /**
    * RSA验签
    * @param $data 待签名数据
    * @param $ali_public_key_path 支付宝的公钥文件路径
    * @param $sign 要校对的的签名结果
    * return 验证结果
    */
   function rsaVerify($data, $ali_public_key_path, $sign)  {
       $pubKey = file_get_contents($ali_public_key_path);
       $res = openssl_get_publickey($pubKey);
       $result = (bool)openssl_verify($data, base64_decode($sign), $res);
       openssl_free_key($res);    
       return $result;
   }
   /**
    * 验证签名
    * @param $prestr 需要签名的字符串
    * @param $sign 签名结果
    * @param $key 私钥
    * return 签名结果
    */
   function md5Verify($prestr, $key, $sign) {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);
        if($mysgin == $sign) {
            return true;
        }
        else {
            return false;
        }
   }
    
    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空 
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    function getResponse($notify_id) {
        $transport = strtolower(trim($this->alipay_config['transport']));
        $partner = trim($this->alipay_config['partner']);
        $veryfy_url = '';
        
        //  HTTPS形式消息验证地址
        $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
        // TTP形式消息验证地址
        $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';
        if($transport == 'https') {
            $veryfy_url = $https_verify_url;
        }
        else {
            $veryfy_url = $http_verify_url;
        }
        $veryfy_url     = $veryfy_url."partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt    = $this->utility->getHttpResponseGET($veryfy_url);

        return $responseTxt;
    }
   
   /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    function verifyReturn(){
        if(empty($_GET)) {//判断POST来的数组是否为空
                return false;
        }
        else {
            //生成签名结果
            $isSign = $this->getSignVeryfy($_GET, $_GET["sign"]);
            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'true';
            if (! empty($_GET["notify_id"])) {$responseTxt = $this->getResponse($_GET["notify_id"]);}

            //写日志记录
            //if ($isSign) {
            //	$isSignStr = 'true';
            //}
            //else {
            //	$isSignStr = 'false';
            //}
            //$log_text = "responseTxt=".$responseTxt."\n return_url_log:isSign=".$isSignStr.",";
            //$log_text = $log_text.createLinkString($_GET);
            //logResult($log_text);
            
            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match("/true$/i",$responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }
}
