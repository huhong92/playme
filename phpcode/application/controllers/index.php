<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Index extends P_Controller {
    function __construct() {
        parent::__construct(false);
        $this->user_url = base_url() . 'user';
        $this->activ_url = base_url() . 'activity';
        $this->search_url = base_url() . 'search';
        $this->game_url = base_url() . 'game';
        $this->load->model('pay_model');
        $this->load->model('user_model');
    }
    
    /**
     * 微信异步通知
     */
    public function index()
    {
        $order_id = 0;
        $xml = '';
        $order_id = $_POST['out_trade_no']; // 支付宝 ： 订单号
        $xml = (isset($GLOBALS['HTTP_RAW_POST_DATA']) && $GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
        if (!$order_id) {  // 微信异步通知
            $result = $this->utility->xml_to_array($xml);
            if ($result['return_code'] == 'SUCCESS') { // 通信成功
                if ($result['result_code'] == 'SUCCESS') {
                    $order_id = $result['out_trade_no'];
                    // 通讯成功
                    $res = $this->pay_model->query_order($order_id);// 查询该订单 付款状态 未处理
                    if ($res['is_callback'] == 1) { // 表示，回调已经处理过了
                        $return['return_code'] = "SUCCESS";
                        $return['return_msg'] = "OK";
                        echo $this->utility->array_to_xml($return);
                        return;
                    }
                    // ----- 执行回滚
                    $this->user_model->start();
                    $ret = $this->pay_model->update_order($order_id, 0);
                    // 支付成功之后 ， 判断是否是充值（人名币换金币），修改该用户的金币数量，几变更历史
                    if ($res['product_type'] == 2) {
                        // 1.更新用户金币（用户表）
                        $user_info = $this->utility->get_user_info($res['uuid']);
                        $fields = array('U_GOLD' => $res['get_glod'] + $user_info['coin']);
                        $rst = $this->user_model->update_user_info($res['uuid'], $fields);
                        if ($rst === false) {
                            log_scribe('trace', 'model', 'PL_USER:(update)' . $this->ip . '  where：uuid = ' . $res['uuid']);
                            $this->error_->set_error(Err_Code::ERR_USER_INFO_UPDATE);
                            $this->user_model->error();
                            
                            $return['return_code'] = "FAIL";
                            $return['return_msg'] = "用户信息更新失败";
                            echo $this->utility->array_to_xml($return);
                            return;
                        }
                        // 2.记录金币变更历史
                        $coin_info = array(
                            'change_coin'   => $res['get_glod'],
                            'coin'          => $res['get_glod'] + $user_info['coin'],
                        );
                        $rst = $this->user_model->record_coin_change_history($res['uuid'],$user_info['nickname'],$coin_info,0,5);
                        if(!$rst) {
                            $this->user_model->error();
                            $return['return_code'] = "FAIL";
                            $return['return_msg'] = "金币变更历史记录失败";
                            echo $this->utility->array_to_xml($return);
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
                            $this->user_model->error();
                            $return['return_code'] = "FAIL";
                            $return['return_msg'] = "充值记录失败";
                            echo $this->utility->array_to_xml($return);
                            return;
                        }
                    }
                    // 更新 下 O_CALLBACK = 1 ：表示已调用回调
                    $this->pay_model->update_wx_callback_status($order_id, 1);
                    // ---- 执行回滚结束
                    $this->user_model->success();
                    $return['return_code'] = "SUCCESS";
                    $return['return_msg'] = "OK";
                    echo $this->utility->array_to_xml($return);
                    return;
                    
                } else if ($result['return_code'] == 'FAIL') { // 表示通信成功，但支付失败了
                    // 交易失败，更新支付状态
                    $order_id = $result['out_trade_no'];
                    $res = $this->pay_model->query_order($order_id);// 查询该订单 付款状态 未处理
                    if ($res['is_callback'] == 1) { // 表示，回调已经处理过了
                        $return['return_code'] = "SUCCESS";
                        $return['return_msg'] = "支付失败";
                        echo $this->utility->array_to_xml($return);
                        return;
                    }
                    // 执行事务
                    $this->user_model->start();
                    $ret = $this->pay_model->update_order($order_id, 1);
                    if (!$ret) {
                        $this->user_model->error();
                    }
                    // 更新 下 O_CALLBACK = 1 ：表示已调用回调
                    $callback_upt = $this->pay_model->update_wx_callback_status($order_id, 1);
                    if (!$callback_upt) {
                        $this->user_model->error();
                    }
                    $this->user_model->success();
                    $return['return_code'] = "SUCCESS";
                    $return['return_msg']  = "交易失败";
                    echo $this->utility->array_to_xml($return);
                    return;
                }
            } else {
                $return['return_code'] = "FAIL";
                $return['return_msg'] = "通信失败";
                // 讲数组转为XML,返回
                echo $this->utility->array_to_xml($return);
                return;
            }
        } else if ($order_id) { // 支付包回调处理
            /*$params['device_id']        = 'hhpaytest123';
            $params['uuid']             = 123;
            $params['nickname']         = 'testPay'.rand(1,100);
            $params['app_id']           = 123;
            $params['type']             = 0;
            $params['device_id']        = '123';
            $params['b_devicetoken']    = $order_id;
            $params['userid']           = 1;
            $params['channel_id']       = 123;
            $this->load->model('api_model');
            $this->api_model->push_msg($params);*/
            $res = $this->pay_model->query_order($order_id);
            if ($res['is_callback'] == 1) { // 表示，回调已经处理过了
                echo "SUCCESS";
                return;
            }
            // ---- 执行回滚
            $this->user_model->start();
            // 更新 下 O_CALLBACK = 1 ：表示已调用回调
            $this->pay_model->update_wx_callback_status($order_id, 1);
            $trade_status = $_POST['trade_status']; // 交易状态
            if ($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') { // 订单支付成功 0 
                $order_status = 0;
            }
            $this->pay_model->update_order($order_id, $order_status);
            
            // 支付成功之后 ， 判断是否是充值（人名币换金币），修改该用户的金币数量，几变更历史 TODO
            if ($res['product_type'] == 2) {
                // 1.更新用户金币（用户表）
                $user_info = $this->utility->get_user_info($res['uuid']);
                $fields = array('U_GOLD' => $res['get_glod'] + $user_info['coin']);
                $rst = $this->user_model->update_user_info($res['uuid'], $fields);
                if ($rst === false) {
                    log_scribe('trace', 'model', 'PL_USER:(update)' . $this->ip . '  where：uuid = ' . $res['uuid']);
                    $this->error_->set_error(Err_Code::ERR_BUY_GAME_REDUCE_COIN_FAIL);
                    $this->user_model->error();
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
                    $this->user_model->error();
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
                    $this->user_model->error();
                    echo "FAIL";
                    return false;
                }
            }
            // ---- 执行回滚结束
            $this->user_model->success();
            echo "SUCCESS";
            return;
        }
    }
    
    //注册
    function register_third_submit() {
        $fields = array(
            'app_id' => $this->request_param('app_id'),
            'device_id' => $this->request_param('device_id'),
            'method' => 'register_for_thirdparty',
            'channel' =>$this->request_param('channel'),
            'source' =>$this->request_param('source'),
            'os' => $this->request_param('os'),
            'version' => $this->request_param('version'),
            'user_id' => $this->request_param('user_id'),
            'nickname' => $this->request_param('nickname'),
            'gender' => $this->request_param('gender'),
            'province' =>$this->request_param('province'),
            'image' => $this->request_param('image'),
            'sign' => $this->request_param('sign'),
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    
    function get_mc() {
        $key = $this->request_param('key');
        $data = $this->cache->memcached->get($key);
        var_dump($data);
    }

    function delete_mc() {
        $key = $this->request_param('key');
        $this->cache->memcached->delete($key);
    }
    //发送短信验证码
    function get_verify_code() {
        $fields = array(
            'app_id' => 1,
            'device_id' => '18621190931',
            'method' => 'send_verify_code',
            'mobile' => '18621190931',
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    
    //获取应用信息
    function get_app (){
       $fields = array(
            'app_id' => 2,
            'device_id' => '18621190931',
            'method' => 'syndata',
            'os' => 1,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    
    //获取用户信息
    function get_info (){
        $uuid = 5;
        $data = $this->utility->get_user_info($uuid);
        var_dump($data);
    }
    
     
    
    
    //注册
    function register() {
        $fields = array(
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'register',
            'channel' =>3,
            'source' =>0,
            'os' => 0,
            'version' => '1.0.1',
            'account' => '18201450931',
            'password' => '123456',
            'verify_code' => '123456',
            'nickname' => '玉玉5',
            'sign' => '',
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    
    /*
     * 获取签名值
     */
    function getSign() {
        $fields = $_POST;
        $sign = $this->utility->get_sign($fields);
        
        var_dump($sign);
    }
    
    //登录
    function login() {
        $fields = array(
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'login',
            'channel' =>3,
            'source' =>0,
            'os' => 0,
            'version' => '1.0.1',
            'account' => '18201450931',
            'password' => '123456',
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    //用户信息接口
    function user_info(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'user_info',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    
    //登出接口
    function logout(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'logout',
            'token' =>'bb968032e101beee205c4fe2af7fd321',
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    
    
    
    //反馈信息
    function feedback(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'feedback',
            'token' =>'bb968032e101beee205c4fe2af7fd321',
            'os' => 0,
            'version' => '1.0.1',
            'content' => '这个游戏good',
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    //activity list
    function get_active(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'banner_list',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'pagesize' => 2,
            'recordindex' => 0,
            'order_type' => 1,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->activ_url, $fields);
        var_dump(json_decode($content));
    }
    //activity list
    function get_active_detail(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'detail',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'id' => 1,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->activ_url, $fields);
        var_dump(json_decode($content));
    }
    
    function search_list(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'search_list',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'keywords' => "游",
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->search_url, $fields);
        var_dump(json_decode($content));
    }
    
    function search_key(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'keywords_list',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'keywords' => "游",
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->search_url, $fields);
        var_dump(json_decode($content));
    }
    
    function search_rank(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'keywords_ranking',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->search_url, $fields);
        var_dump(json_decode($content));
    }
    
    //收藏
    function favorite(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'favorite',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'id' => 1,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->game_url, $fields);
        var_dump(json_decode($content));
    }
    function delete_favorite(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'delete_favorite',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'id' => 1,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->game_url, $fields);
        var_dump(json_decode($content));
    }
    
    //评论
    function comment(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'comment',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'id' => 1,
            'content' => "游戏一般",
            'scoring' => 5,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->game_url, $fields);
        var_dump(json_decode($content));
    }
    
    //评论
    function comment_list(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'comment_list',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'id' => 1,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->game_url, $fields);
        var_dump(json_decode($content));
    }
    
    //评论
    function share(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'share',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'id' => 3,
            'type' => 2,
            'channel' => 0,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->game_url, $fields);
        var_dump(json_decode($content));
    }
    
    //评论
    function buy(){
        $fields = array(
            'uuid' =>1,
            'app_id' => 1,
            'device_id' => '18611190231',
            'method' => 'buy',
            'token' =>'47b740b442fb7d1f9eed695e1e795596',
            'id' => 3,
            'price_current' => 5,
        );
        $sign = $this->utility->get_sign($fields);
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->game_url, $fields);
        var_dump(json_decode($content));
    }
    
    function get_token()
    {
        $params['device_id'] = $this->request_param('device_id');
        $params['uuid'] = $this->request_param('uuid');
        
        $res = $this->get_login_token($params['device_id'], $params['uuid']);
        var_dump($res);
    }
    
    //注册
    function register_third() {
        $fields = array(
            // 'app_id' => 1,
            'device_id' => '151452717872',
            // 'method' => 'action_buy_debug',
            'method' => 'prepare_buy_debug',
            // 'method' => 'user_info_debug',
            // 'channel' =>0,
            // 'source' =>0,
            // 'os' => 0,
            // 'version' => '1.0.1',
            // 'user_id' => '18201450931',
            // 'nickname' => 'yoyo_1',
            // 'gender' => '女',
            // 'province' => '上海',
            // 'image' => '',
            'total_fee'=>11,
          'prop_id'=>1,
            'game_join_id'=> 172,
           'subject'=> 'dfsdf',
           'description'=> 'sdfsdf',
            'timestamp'=>'123456789',
'nonce'=> 'ddddd',
         
'order_id'=>1,
            
            'sign' => 'e69f7c24b9dcbccdb2fe9713801664f0',
            'uuid'=> '15',
            'token'=> '43c5f6a548fe0b9258821b704ab058df',
            
        );
        $sign = $this->utility->get_sign($fields);
        var_dump($sign);exit;
        $fields['sign'] = $sign;
        $content = $this->utility->get($this->user_url, $fields);
        var_dump(json_decode($content));
    }
    
    public function url_test()
    {
        phpinfo();
        $aa	= array('test'=>'test','test_2'=>'test_2');
        $bb =  json_encode($aa);
        return $bb;
    }
    
    public function sql_exec()
    {
        $sql[1] = $this->request_param('sql');
        $res = $this->user_model->do_exec_sql($sql[1]);
        if (!$res) {
            $this->user_model->error();
            echo  'error';exit;
        }
        $this->user_model->success();
        echo  'ok';
        
    }
    
    public function test()
    {
        $this->template->load('template', 'index/test');
    }
    


    
    
}
