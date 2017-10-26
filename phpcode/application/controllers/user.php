<?php

class User extends P_Controller {

    function __construct() {
        parent::__construct(false);
        $this->load->model('user_model');
    }
    
    /**
     * 校验 token是否有效
     * @return type
     */
    public function auth_token()
    {
        $params = array(
            'uuid'      => (int)$this->request_param('uuid'),
            'channel_id'=> $this->request_param('channel_id'),
            'app_id'    => (int)$this->request_param('app_id'),
            'device_id' => $this->request_param('device_id'),
            'token'     => $this->request_param('token'), // 可选
            'method'    => $this->request_param('method'),
            'sign'      => $this->request_param('sign'),
        );
        if($params['channel_id'] == '' || $params['app_id'] == "" || $params['device_id']== "" || $params['method'] == "" || $params['sign'] == "" ||  $params['uuid'] == '' || $params['token'] == ''){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验token是否有效
        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
            $this->output_json_return();
        }
        $this->output_json_return();
    }
    
    
    /*     * ****
     * 客户端数据同步接口（验证app版本）
     * app_id	int	发送请求方app的id，用来唯一标识app的id号，必须
     * device_id	string	设备型号，必须
     * method	string	syndata：验证app版本
     *  os	int	操作系统，0:iphone 1:android必须
     * sign	string	签名，必须
     * ***** */
    function syndata() {
        log_scribe('trace', 'params', 'syndata:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params = array(
            'channel_id'=> $this->request_param('channel_id'),
            'app_id'    => $this->request_param('app_id'),
            'device_id' => $this->request_param('device_id'),
            'method'    => $this->request_param('method'),
            'os'        => $this->request_param('os'),
        );
        $sign = $this->request_param('sign');
        
        if ($params['channel_id'] == "" || $params['app_id'] == "" || $params['device_id'] == "" || $sign == "") {
           log_scribe('trace','model','app_syndata'. $this->ip .'params: '.  http_build_query($params));
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        if (!$this->utility->check_sign($params, $sign)) {
            $this->output_json_return();
        }
        
        $params['app_id']   = (int)$params['app_id'];
        $params['os']       = (int)$params['os'];
        
        $data = $this->user_model->app_syndata($params);
        $this->output_json_return($data);
    }
     
    /*****
     * 第三方用户登陆接口
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * method	string	register_for_thirdparty:第三方登录
     * user_id	string	用户ID(第三方返回的唯一id) ，必须
     * username	string	用户名，必须
     * gender	int	性别 0:女性  1:男性
     * province	string	常居（所在省份）
     * image	string	头像
     * channel	int	渠道 0：微信1：QQ 2：九城，3：应用本身，必须
     * source	int	包下载来源 1：玩我官网 2 百度手机助手 .....
     * os	int	设备类型0:iphone1:android ，必须
     * version	string	当前应用版本号，必须 例如：1.0.1
     * sign	string	签名，必须
     * ***** */

    function register_for_thirdparty() {
        log_scribe('trace', 'params', 'register_for_thirdparty:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params['channel_id']   = $this->request_param('channel_id');
        $params['app_id']       = (int)$this->request_param('app_id');
        $params['device_id']    = $this->request_param('device_id');
        $params['method']       = $this->request_param('method');
        $params['os']           = (int)$this->request_param('os');
        $params['version']      = $this->request_param('version');
        $params['user_id']      = $this->request_param('user_id');
        $params['nickname']     = urldecode($this->request_param('nickname'));
        $params['gender']       = urldecode($this->request_param('gender'));
        $params['province']     = urldecode($this->request_param('province'));
        $params['image']        = urldecode($this->request_param('image'));
        $params['login_type']   = $this->request_param('login_type');
        $params['source']       = $this->request_param('source');
        $params['sign']         = $this->request_param('sign');
        $params['mobile']       = $this->request_param('mobile');
        
        if ($params['channel_id'] == "" || $params['app_id'] == "" || $params['device_id'] == "" || $params['version'] == "" || $params['user_id'] == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        if ($params['nickname'] == "") {
            $this->error_->set_error(Err_Code::ERR_NICKNAME_NOT_NULL);
            $this->output_json_return();
        }
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['source']   = (int)$params['source'];
        //校验用户登录方式
        if(!in_array($params['login_type'], $this->passport->get('login_type'))){
            $this->error_->set_error(Err_Code::ERR_CHANNEL_FAIL);
            $this->output_json_return();
        }
        //校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        //校验手机号
        if ($params['mobile'] != "") {
            if (!$this->utility->is_mobile($params['mobile'])) {
                $this->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
                $this->output_json_return();
            }
        }
        
        preg_match_all('/./u', $params['nickname'],$name_arr);
        foreach($name_arr[0] as $k=>$v) {
            if (preg_match("/[^(\x{4e00}-\x{9fa5}\w)]+$/u", $v, $match)) { // 匹配 非 中文、数字、字母、下划线
                // 含特殊字符
                $name_arr[0][$k] = "_";
                if ($match) {
                    $matchs[] = $match[0];
                }
            }
        }
        
        $params['nickname'] = implode("",$name_arr[0]);
        // 是否含有屏蔽字，有，改成*，并推送消息
        $illegal_char_info  = $this->utility->get_illegal_char();
        $params['nickname'] = preg_replace($illegal_char_info, "*", $params['nickname'],$limit = -1, $count);
        if (mb_strlen($params['nickname'],'UTF-8') > 8) { // 中文也作为utf-8处理，一个中文当一个字符处理
            $name = iconv_substr($params['nickname'],0,5);
            $params['nickname'] = $name."...";
        }
        
        //事务开始
        $this->user_model->start();
        //查询该用户是否已注册
        $_uuid = $this->user_model->chk_user_account($params['user_id'],$params['login_type']);
        if ($_uuid === false) {
            //新注册用户 插入用户表 
            $params['uuid'] = $this->user_model->insert_user_info($params);
            if (!$params['uuid']) {
                $this->user_model->error();
                $this->output_json_return();
            }
            //插入用户登入表
            $rst = $this->user_model->insert_user_login($params);
            if (!$rst) {
                $this->user_model->error();
                $this->output_json_return();
            }
            
            // 首次登入注册，加入推送消息
            if ($matchs) { // 含有特殊字符，推送消息
                $this->tasklib->send_msg_by_nickname_special_char($params['uuid']);
            }

            if ($count) {  // 存在屏蔽字， 推送消息
                 $this->tasklib->send_msg_by_nickname_illegal($params['uuid']);
            }
        } else {
            $params['uuid'] = $_uuid;
            //更新最后登录时间
            // 更新用户信息
            $fields = array(
                'U_NICKNAME'      => $params['nickname'],
                'U_ICON'          => $params['image'],
                'U_SEX'           => $params['gender'],
                'U_PROVINCE'      => $params['province'],
                'U_MOBILEPHONE'   => $params['mobile'],
                'U_LASTLOGINTIME' => $this->zeit,
            );
            $rst = $this->user_model->update_user_info($params['uuid'],$fields);
            if (!$rst) {
                $this->user_model->error();
                $this->output_json_return();
            }
        }
        
        //记录用户登录设备(source: 包下载来源)
        $rst = $this->user_model->record_user_device($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //记录用户登录历史记录
        $rst = $this->user_model->record_user_login_history($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //获取用户信息
        $data = $this->utility->get_user_info($params['uuid']);
        $this->user_model->success();
        
        
        // 完善用户信息之后， 调用任务
        $this->user_model->start();
        if (($params['u_icon'] || $data['image'])  && ($params['nickname'] || $data['nickname']) && ($data['mobile'] || $params['mobile'])) {
            $this->tasklib->task_full_user_info($params['uuid']);
        }
        $this->user_model->success();
        
        //加入登陆任务
        $this->tasklib->task_login_award($params['uuid']);
        
        //获取用户信息
        $expires = time()+$this->passport->get('token_expire');
        $token = $this->set_token($params['uuid'],$params['app_id'],$params['device_id']);
        
        $data['token'] = $token;
        $data['expires'] = $expires;
        $this->output_json_return($data);
    }
    
    /***发送验证码
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * method	string	send_verify_code:第三方登录
     * username	string	手机号，必须
     * sign	string	签名，必须
     */
    function send_verify_code(){
        log_scribe('trace', 'params', 'send_verify_code:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params['channel_id']   = $this->request_param('channel_id');
        $params['app_id']       = (int)$this->request_param('app_id');
        $params['device_id']    = $this->request_param('device_id');
        $params['method']       = $this->request_param('method');
        $params['mobile']       = $this->request_param('mobile');
        $params['version']      = $this->request_param('version');
        $params['sign']         = $this->request_param('sign');
        
        if($params['channel_id'] == "" || $params['app_id'] == "" || $params['device_id'] == "" || $params['mobile'] == ""){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if (!$this->utility->is_mobile($params['mobile'])) {
            $this->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
            $this->output_json_return();
        }
        
        $mobile = $params['mobile'];
        //短时间内只能获取一次验证码
        $_prefix        = $this->passport->get('mobile_code_prefix'); // 手机验证码时间戳前缀 T_
        $_limit_prefix  = $this->passport->get('mobile_limit_prefix'); // 手机验证码前缀 MOBILE_LIMIT_
        $current_ip     = $this->input->ip_address();
        $mobile_limit   = $this->cache->memcached->get($_limit_prefix . $current_ip);
        $mobile_limit2  = $this->cache->memcached->get($_limit_prefix . $mobile);
        (empty($mobile_limit2) || !is_array($mobile_limit2)) && $mobile_limit2 = array();
        $zeit           = time();
        $input_mobile   = $mobile;
        $sun_rise       = strtotime(date('Y-m-d 00:00:00', time()));
        if ($mobile_limit2['last_send_ts'] > $sun_rise) {
            //同一手机号每天最多发N条短信
            $limit = $this->passport->get('mobile_limit_by_mobile');
            if ($mobile_limit2['times'] >= $limit) {
                $this->error_->set_error(Err_Code::ERR_MOBILE_LIMIT_MOBILE);
                $this->output_json_return();
            }
            $mobile_limit2['times'] ++;
            $mobile_limit2['last_send_ts'] = $zeit;
        } else {
            $mobile_limit2['times'] = 1;
            $mobile_limit2['last_send_ts'] = $zeit;
        }

        if ($mobile_limit['last_send_ts'] > $sun_rise) {
            //同一IP对不同手机触发验证信息时间间隔不低于n分钟
//            $limit = $this->passport->get('interval_limit_by_ip');
//            if (!in_array($input_mobile, array_keys($mobile_limit['mobile'])) && time() < strtotime("+{$limit} minute", $mobile_limit['last_send_ts'])) {
//                $this->error_->set_error(Err_Code::ERR_MOBILE_SEND_QUICK);
//                $this->output_json_return();
//            }
            //同一IP每天最多只能对n个不同手机号码进行触发验证码 
//            $limit = $this->passport->get('mobile_limit_by_ip');
//            if (count($mobile_limit['mobile']) >= $limit) {
//                $this->error_->set_error(Err_Code::ERR_MOBILE_LIMIT_IP);
//                $this->output_json_return();
//            }

            if (empty($mobile_limit['mobile'][$input_mobile]) || $mobile_limit['mobile'][$input_mobile]['day_ts'] < $sun_rise) {
                $mobile_limit['mobile'][$input_mobile]['day_ts'] = $zeit;
                $mobile_limit['mobile'][$input_mobile]['day_counter'] = 1;
            } else {
                //同一IP每天对同一手机号码触发验证短信不超过n条
//                if (in_array($input_mobile, array_keys($mobile_limit['mobile']))) {
//                    $limit = $this->passport->get('number_limit_by_mobile_ip');
//                    if ($mobile_limit['mobile'][$input_mobile]['day_counter'] >= $limit) {
//                        $this->error_->set_error(Err_Code::ERR_MOBILE_LIMIT_MOBILE_IP);
//                        $this->output_json_return();
//                    }
//                }
                $mobile_limit['mobile'][$input_mobile]['day_counter'] ++;
            }
            
            $mobile_limit['mobile'][$input_mobile]['send_ts'] = $zeit;
            $mobile_limit['last_send_ts'] = $zeit;
        } else {
            $mobile_limit = array();
            $mobile_limit['mobile'][$input_mobile]['day_ts'] = $zeit;
            $mobile_limit['mobile'][$input_mobile]['day_counter'] = 1;
            $mobile_limit['mobile'][$input_mobile]['send_ts'] = $zeit;
            $mobile_limit['last_send_ts'] = $zeit;
        }

        $timestamp = $this->cache->memcached->get($_prefix . $mobile);
        $period = time() - $timestamp;
        if (empty($timestamp) || $period > $this->passport->get('mobile_code_period')) {
            $this->cache->memcached->save($_prefix . $mobile, time());
            $this->load->library('sms');
            $sms_msg = date('Y') . '年' . date('m') . '月' . date('d') . '日，您申请的手机验证码：' . $this->utility->get_mobile_code($mobile);
            if (!$this->sms->send($mobile, $sms_msg)) {
                $this->output_json_return();
            }
        } else {
            $this->error_->set_error(Err_Code::ERR_MOBILE_CODE_TIMES);
            $err_msg = $this->error_->error_msg(Err_Code::ERR_MOBILE_CODE_TIMES);
            $err_msg = sprintf($err_msg,$this->passport->get('mobile_code_period') - $period);
            $this->json->output(array('c' => $this->error_->get_error(), 'm' => $err_msg));
            
        }
        $this->cache->memcached->save($_limit_prefix . $current_ip, $mobile_limit, 864000);
        $this->cache->memcached->save($_limit_prefix . $mobile, $mobile_limit2, 864000);
        $this->cache->memcached->save($_prefix . $mobile, time(), $this->passport->get('mobile_code_expire')); // 验证码有效时间
        $this->error_->set_error(Err_Code::ERR_OK);
        //test 
        $data = array('verify_code'=>$this->cache->memcached->get('M_'.$mobile));
        $this->output_json_return($data);
    }
    
    /*****
     * 手机注册
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * method	string	register
     * channel	int	渠道 0：微信1：QQ 2：九城，3：应用本身，必须
     * source	int	app包下载源
     * os	int	设备类型0:iphone1:android ，必须
     * version	string	当前应用版本号，必须 例如：1.0.1
     * username	string	手机号，必须
     * password	string	密码
     * nickname	string	用户昵称，(2-10位)必须
     * verify_code	String	验证码
     * sign	string	签名，必须
     * ***** */
    function register() {
        log_scribe('trace', 'params', 'register:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params['channel_id']   = $this->request_param('channel_id');
        $params['app_id']       = (int)$this->request_param('app_id');
        $params['device_id']    = $this->request_param('device_id');
        $params['method']       = $this->request_param('method');
        $params['os']           = (int)$this->request_param('os');
        $params['version']      = $this->request_param('version');
        $params['account']      = $this->request_param('account');
        $params['password']     = $this->request_param('password');
        $params['verify_code']  = $this->request_param('verify_code');
        $params['nickname']     = urldecode($this->request_param('nickname'));
        $params['login_type']   = $this->request_param('login_type'); // 登录方式 0：微信1：QQ 2：九城，3:手机号，必须
        $params['source']       = $this->request_param('source');
        $params['sign']         = $this->request_param('sign');
        $params['gender']       = urldecode($this->request_param('gender'));
        $params['province']     = urldecode($this->request_param('province'));
        $params['image']        = urldecode($this->request_param('image'));
        
        if ($params['channel_id'] == '' || $params['app_id'] == "" || $params['device_id'] == "" || $params['nickname'] == "" || $params['os'] === "" || $params['version'] == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        //校验登录方式
        if(!in_array($params['login_type'], $this->passport->get('login_type'))){
            $this->error_->set_error(Err_Code::ERR_LOGIN_TYPE_FAIL);
            $this->output_json_return();
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['source'] = (int)$params['source'];
        
        //校验手机号
        if ($params['account'] == "") {
            $this->error_->set_error(Err_Code::ERR_MOBILE_NO_DATA);
            $this->output_json_return();
        }
        if (!$this->utility->is_mobile($params['account'])) {
            $this->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
            $this->output_json_return();
        }
        
        //校验密码
        if (!$this->utility->chk_pwd($params['password'])) {
            $this->error_->set_error(Err_Code::ERR_PWD_FORMAT);
            $this->output_json_return();
        }
        //校验验证码
        if ($params['verify_code'] == "") {
            $this->error_->set_error(Err_Code::ERR_VERIFYCODE_NO_DATA);
            $this->output_json_return();
        }
        $ret = $this->utility->verify_mobile_code($params['account'], $params['verify_code'], true);
        if (!$ret) $this->output_json_return();
        
        //校验昵称
        preg_match_all('/./u', $params['nickname'],$name_arr);
        foreach($name_arr[0] as $k=>$v) {
            if (preg_match("/[^(\x{4e00}-\x{9fa5}\w)]+$/u", $v, $match)) { // 匹配中文、数字、字母、下划线
                // 含特殊字符
                $name_arr[0][$k] = "_";
                if ($match) {
                    $matchs[]= $match[0];
                }
            }
        }
        $params['nickname'] = implode("",$name_arr[0]);
        // 是否含有屏蔽字，有，改成*，并推送消息
        $illegal_char_info = $this->utility->get_illegal_char();
        $params['nickname'] = preg_replace($illegal_char_info, "*", $params['nickname'],$limit = -1, $count);
        if (mb_strlen($params['nickname'],'UTF-8') > 8) { // 中文也作为utf-8处理，一个中文当一个字符处理
            $name = iconv_substr($params['nickname'],0,5);
            $params['nickname'] = $name."...";
        }
        
        //事务开始
        $this->user_model->start();
        //查询该用户是否可注册(手机号)
        $_uuid = $this->user_model->chk_user_account($params['account'],$params['login_type']);
        if($_uuid) {
            $this->error_->set_error(Err_Code::ERR_REG_ACOUUNT_IS_EXIT);
            $this->output_json_return();
        }
        
        //新注册用户 插入用户表 
        $params['mobile'] = $params['account'];
        $params['uuid'] = $this->user_model->insert_user_info($params);
        if (!$params['uuid']) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //插入用户登入表
        $params['user_id'] = $params['account'];
        $rst = $this->user_model->insert_user_login($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //记录用户登录设备
        $rst = $this->user_model->record_user_device($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //记录用户登录日志
        $rst = $this->user_model->record_user_login_history($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //获取用户信息
        $data = $this->utility->get_user_info($params['uuid']);
        $this->user_model->success();
        
        //加入登陆任务
        $this->user_model->start();
        $this->tasklib->task_login_award($params['uuid']);
        
        // 完善用户信息之后， 调用任务
        $this->user_model->start();
        if (($params['u_icon'] || $data['image'])  && ($params['nickname'] || $data['nickname']) && ($data['mobile'] || $params['mobile'])) {
            $this->tasklib->task_full_user_info($params['uuid']);
        }
        $this->user_model->success();
        
        // 加入推送消息
        if ($matchs) { // 含有特殊字符，推送消息
            $this->tasklib->send_msg_by_nickname_special_char($params['uuid']);
        }
        if ($count) {  // 存在屏蔽字， 推送消息
             $this->tasklib->send_msg_by_nickname_illegal($params['uuid']);
        }
        $this->user_model->success();
        //获取用户信息
        $expires = time()+$this->passport->get('token_expire');
        $token = $this->set_token($params['uuid'],$params['app_id'],$params['device_id']);
        $data['token'] = $token;
        $data['expires'] = $expires;
        $this->output_json_return($data);
    }
    
    /*****
     * 手机登录
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * method	string	register
     * channel	int	渠道 0：微信1：QQ 2：九城，3：应用本身，必须
     * source	int	APP包下载源
     * os	int	设备类型0:iphone1:android ，必须
     * version	string	当前应用版本号，必须 例如：1.0.1
     * username	string	手机号，必须
     * password	string	密码
     * sign	string	签名，必须
     * ***** */
    function login()
    {
        log_scribe('trace', 'params', 'login:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params['channel_id']   = $this->request_param('channel_id');
        $params['app_id']       = (int)$this->request_param('app_id');
        $params['device_id']    = $this->request_param('device_id');
        $params['method']       = $this->request_param('method');
        $params['os']           = (int)$this->request_param('os');
        $params['version']      = $this->request_param('version');
        $params['account']      = $this->request_param('account');
        $params['password']     = $this->request_param('password');
        $params['login_type']   = $this->request_param('login_type');
        $params['source']       = $this->request_param('source');
        $params['sign']         = $this->request_param('sign');
        
        if ($params['channel_id'] == '' || $params['app_id'] == "" || $params['device_id'] == "" || $params['version'] == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['source'] = (int)$params['source'];
        //校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        //校验登录方式
        if(!in_array($params['login_type'], $this->passport->get('login_type'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        
        //校验手机号
        if ($params['account'] == "") {
            $this->error_->set_error(Err_Code::ERR_MOBILE_NO_DATA);
            $this->output_json_return();
        }
        if (!$this->utility->is_mobile($params['account'])) {
            $this->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
            $this->output_json_return();
        }
        //校验密码格式
        if (!$this->utility->chk_pwd($params['password'])) {
            $this->error_->set_error(Err_Code::ERR_PWD_FORMAT);
            $this->output_json_return();
        }
        //事务开始
        $this->user_model->start();
        //数据库验证手机号、密码
        $_uuid = $this->user_model->chk_user_account($params['account'],$params['login_type'],$params['password']);
        if($_uuid === false) {
            $this->error_->set_error(Err_Code::ERR_LOGIN_ACCOUNT_FAIL);
            $this->output_json_return();
        }
        $params['uuid'] = $_uuid;
        
        //获取用户信息
        $data = $this->utility->get_user_info($params['uuid']);
        if(!$data) {
            $this->output_json_return();
        }
        //更新最后登录时间
        $fields = array( 'U_LASTLOGINTIME' => $this->zeit);
        $rst = $this->user_model->update_user_info($params['uuid'], $fields);
        //记录用户登录设备
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        $rst = $this->user_model->record_user_device($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        $params['nickname'] = $data['nickname'];
        //记录用户登录日志
        $rst = $this->user_model->record_user_login_history($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        $this->user_model->success();
        
        //增加登录任务
        $this->user_model->start();
        $this->tasklib->task_login_award($params['uuid']);
        $this->user_model->success();
        
        $expires = time()+$this->passport->get('token_expire');
        $token = $this->set_token($params['uuid'], $params['app_id'], $params['device_id']);
        $data['token'] = $token;
        $data['expires'] = $expires;
        $this->output_json_return($data);
    }
    
    //校验昵称是否存在
    function chk_user_nickname(){
        log_scribe('trace', 'params', 'chk_user_nickname:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params['nickname']     = urldecode($this->request_param('nickname'));
        $params['sign']         = $this->request_param('sign');
        $params['method']       = $this->request_param('method');
        
        if ($params['nickname'] == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $uuid = $this->user_model->chk_user_nickname($params['nickname']);
        $status = 0;
        if($uuid){
            $status = 1;
        }
        $data['status'] = $status;
        $this->output_json_return($data);
    }
    
    //修改昵称
    function update_user_nickname(){
       //  log_scribe('trace', 'params', 'update_user_nickname:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params             = $this->get_public_params();
        $params['nickname'] = urldecode($this->request_param('nickname'));
        if ($params['nickname'] == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 校验昵称，校验特殊字符
        preg_match_all('/./u', $params['nickname'],$name_arr);
        foreach($name_arr[0] as $k=>$v) {
            if (preg_match("/[^(\x{4e00}-\x{9fa5}\w)]+$/u", $v, $matchs)) { // 匹配中文、数字、字母、下划线
                // 含特殊字符
                $name_arr[0][$k] = "_";
            }
        }
        $params['nickname'] = implode("",$name_arr[0]);
        // 是否含有屏蔽字，有，改成*，并推送消息
        $illegal_char_info = $this->utility->get_illegal_char();
        $params['nickname'] = preg_replace($illegal_char_info, "*", $params['nickname'],$limit = -1, $count);
        // 校验昵称，校验长度
        if (mb_strlen($params['nickname'],'UTF-8') > 8) { // 中文也作为utf-8处理，一个中文当一个字符处理
            $name = iconv_substr($params['nickname'],0,5);
            $params['nickname'] = $name."...";
        }
        
        //获取原来用户的昵称
        $nickname = $this->utility->get_user_info($params['uuid'],'nickname');
        $this->user_model->start();
         //校验昵称
        $nickname_status = $this->user_model->chk_user_nickname($params['nickname']);
        if($nickname_status) {
            $this->error_->set_error(Err_Code::ERR_REG_NICKNAME_IS_EXIT);
            $this->output_json_return();
        }
        $fields = array(
            'U_NICKNAME' => $params['nickname'],
        );
        $rst = $this->user_model->update_user_info($params['uuid'],$fields);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //记录昵称修改历史
        $rst = $this->user_model->record_nickname_change_history($params['uuid'],$nickname,$params['nickname']);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        $this->user_model->success();
        
        //获取用户信息
        $info = $this->utility->get_user_info($params['uuid']);
        $info['token'] = $params['token'];
        $expires = time()+$this->passport->get('token_expire');
        $info['expires'] = $expires;
        
        $data['user_info'] = $info;
        $this->output_json_return($data);
    }
    
    /*     * ***
     * 获取用户信息
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * token	String	登录身份令牌，必须
     * method	string	user_info:用户信息
     * sign	string	签名，必须
     * ***** */
    function user_info() {
        log_scribe('trace', 'params', 'user_info:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params     = $this->get_public_params();
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        // 九成渠道ID = '1'
        if ($params['channel_id'] == '1') {
            // 统计，点击个人信息，次数
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_MYINFO', $time);
        }
        
        //获取用户信息
        $info = $this->utility->get_user_info($params['uuid']);
        if (empty($info)) {
            $this->error_->set_error(Err_Code::ERR_USER_INFO_NO_DATA);
            $this->output_json_return($data);
        }
        $info['token'] = $params['token'];
        $expires = time()+$this->passport->get('token_expire');
        $info['expires'] = $expires;
        $data['user_info'] = $info;
        if ($info['token']) {
            //增加登录任务
            $this->user_model->start();
            $this->tasklib->task_login_award($params['uuid']);
            $this->user_model->success();
        }
        $this->output_json_return($data);
    }

    /*     * ***
     * 注销/切换用户账号
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * token	String	登录身份令牌，必须
     * method	string	logout:用户信息
     * sign	string	签名，必须
     * ***** */
    function logout() {
        log_scribe('trace', 'params', 'logout:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params = $this->get_public_params();
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        //删除mc中token状态
        if(!$this->del_appid_login($params['uuid'], $params['device_id'])){
            $this->error_->set_error(Err_Code::ERR_MC_FAIL);
        } else {
            $this->error_->set_error(Err_Code::ERR_OK);
        }
        $this->output_json_return();
    }
    
    /*     * ***
     * 反馈信息
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * token	String	登录身份令牌，必须
     * method	string	feedback:反馈
     * os	int	操作系统，0:iphone 1:android必须
     * version  string	版本号
     * content	string	反馈内容，必须，最多50个字符，必须
     * sign	string	签名，必须
     * ***** */
    function feedback() {
        log_scribe('trace', 'params', 'feedback:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params();
        $params['os']           = (int)$this->request_param('os');
        $params['version']      = $this->request_param('version');
        $params['content']      = urldecode($this->request_param('content'));
        $params['contact_info'] = $this->request_param('contact_info');
        // 校验参数
        if ($params['version'] == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        if ($params['content'] == "" || $this->utility->stringLen($params['content']) > 200) {
            $this->error_->set_error(Err_code::ERR_FEEDBACK_CONTENT_LEN);
            $this->output_json_return();
        }
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        //校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        
        // 是否含有屏蔽字，有，改成*
        $illegal_char_info = $this->utility->get_illegal_char();
        $params['content'] = preg_replace($illegal_char_info, "*", $params['content'],$limit = -1, $count);
        
        //获取用户昵称
        $params['nickname'] = $this->utility->get_user_info($params['uuid'],'nickname');
        $this->user_model->start();
        //记录反馈信息
        $rst = $this->user_model->record_feedback($params);
        if(!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        $this->user_model->success();
        $this->output_json_return();
    }
       
    /**
     * 完善用户资料
     */
    public function update_user_info(){
        log_scribe('trace', 'params', 'update_user_info:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params();
        $params['nickname']     = $this->request_param('nickname');
        $params['sex']          = $this->request_param('sex');
        $params['mobile']       = $this->request_param('mobile');
        $params['province']     = $this->request_param('province');
        $params['verify_code']  = $this->request_param('verify_code');
              
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        //校验昵称格式
        if ($params['nickname']) {
            preg_match_all('/./u', $params['nickname'],$name_arr);
            foreach($name_arr[0] as $k=>$v) {
                if (preg_match("/[^(\x{4e00}-\x{9fa5}\w)]+$/u", $v, $matchs)) { // 匹配中文、数字、字母、下划线
                    // 含特殊字符，有，改成空格
                    $name_arr[0][$k] = "_";
                }
            }
            $params['nickname'] = implode("",$name_arr[0]);
            // 是否含有屏蔽字，有，改成*
            $illegal_char_info = $this->utility->get_illegal_char();
            $params['nickname'] = preg_replace($illegal_char_info, "*", $params['nickname'],$limit = -1, $count);
            if (mb_strlen($params['nickname'],'UTF-8') > 8) { // 中文也作为utf-8处理，一个中文当一个字符处理
                $name = iconv_substr($params['nickname'],0,5);
                $params['nickname'] = $name."...";
            }
        }
        
        if ($params['mobile']) {
            // 校验该用户个人信息，手机号存在不可修改
            $sys_mobile = $this->utility->get_user_info($params['uuid'],'mobile');
            if ($sys_mobile) {
                $this->error_->set_error(Err_Code::ERR_RE_BIND_MOBILE_FAIL);
                $this->output_json_return();
            }
            // 手机号格式验证
            if (!$this->utility->is_mobile($params['mobile'])) {
                $this->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
                $this->output_json_return();
            }
            // 绑定手机号，发送验证码验证。
            if ($params['verify_code'] == "") {
                $this->error_->set_error(Err_Code::ERR_VERIFYCODE_NO_DATA);
                $this->output_json_return();
            }

            $ret = $this->utility->verify_mobile_code($params['mobile'], $params['verify_code'], true);
            if (!$ret) {
                $this->error_->set_error(Err_Code::ERR_MOBILE_VERIFY_CODE_FAIl);
                $this->output_json_return();
            }
        }
        
        // 校验图片
        $params['filename'] = $_FILES['icon'];
        if ($params['filename']['name']) {
            $allow_img = array('jpg', 'png', 'jpeg', 'gif');
            $ext = pathinfo($params['filename']['name'], PATHINFO_EXTENSION);
            if (!in_array($ext , $allow_img) || empty($params['filename'])) {
                $this->error_->set_error(Err_Code::ERR_IMAGE_FORMAT_INCORRECT);
                $this->output_json_return();
                return false;
            }
            
            $ftp_config = $this->passport->get('ftp_config');
            try{
                $conn_id      = ftp_connect($ftp_config['ip']) or die("Could not connect to ".$ftp_config['ip']);
                $login_result = ftp_login($conn_id, $ftp_config['ftp_user'],$ftp_config['ftp_pass']);

                if (!$conn_id || !$login_result) {
                    $this->error_->set_error(Err_Code::ERR_FTP_CONNECT_FAIL);
                    $this->output_json_return();
                    return false;
                } else {

                    $source_path = $params['filename']['tmp_name'];
                    $dest_path   = '/upload/user_icon/'.time().'-'.$params['uuid'].'.'.$ext;

                    // 检测远程FTP服务器上传目录是否存在
                    $url = $this->passport->get('game_url').'/upload/user_icon';
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_NOBODY, 1); // 不下载内容，只测试远程ftp
                    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $res = curl_exec($ch);
                    if ($res == false) {
                        @ftp_mkdir($conn_id, '/upload/user_icon');
                    }

                    $upload      = @ftp_put($conn_id, $dest_path, $source_path, FTP_BINARY);
                    ftp_close($conn_id);

                    if (!$upload) {
                       $this->error_->set_error(Err_Code::ERR_FILE_UPLOAD_FAIL);
                       $this->output_json_return();
                       return false;
                   }

                   $params['u_icon'] = $dest_path; // 上传成功
                }
            } catch (Exception $ex) {
                $this->error_->set_error(Err_Code::ERR_FILE_UPLOAD_FAIL);
                $this->output_json_return();
            }
        }
        if (!$params['u_icon']) {
            $params['u_icon'] = '';
        }
        
        $fields = array(
            'U_NICKNAME'    => $params['nickname'],
            'U_ICON'        => $params['u_icon'],
            'U_SEX'         => $params['sex'],
            'U_PROVINCE'    => $params['province'],
            'U_MOBILEPHONE' => $params['mobile'],
        );
        
        $this->user_model->start();
        $res = $this->user_model->update_user_info($params['uuid'], $fields);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_USERINFO_FAIL);
            $this->output_json_return();
            return false;
        }
        $this->user_model->success();
        
        $user_info = $this->utility->get_user_info($params['uuid']);
        
        // 完善用户信息之后， 调用任务
        $this->user_model->start();
        if (($params['u_icon'] || $user_info['image'])  && ($params['nickname'] || $user_info['nickname']) && ($user_info['mobile'] || $params['mobile'])) {
            $this->tasklib->task_full_user_info($params['uuid']);
        }
        $this->user_model->success();
        
        //获取用户信息
        $info = $this->utility->get_user_info($params['uuid']);
        $info['token'] = $params['token'];
        $expires = time()+$this->passport->get('token_expire');
        $info['expires'] = $expires;
        
        $data['user_info'] = $info;
        $this->output_json_return($data);
    }
    
    /**
     * 使用验证码登陆系统(手机号注册)
     */
    public function find_pwd()
    {
        log_scribe('trace', 'params', 'find_pwd:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params['channel_id']   = $this->request_param('channel_id');
        $params['app_id']       = (int)$this->request_param('app_id');
        $params['device_id']    = $this->request_param('device_id');
        $params['source']       = $this->request_param('source');
        $params['os']           = $this->request_param('os');
        $params['version']      = $this->request_param('version');
        
        $params['sign']         = $this->request_param('sign');
        $params['method']       = $this->request_param('method');
        $params['mobile']       = $this->request_param('mobile');
        $params['chk_code']     = $this->request_param('chk_code');
        // 校验参数
        if ($params['channel_id'] == '' || $params['app_id'] == '' || $params['device_id'] == '' || $params['mobile'] == '' || $params['version'] == "" || $params['chk_code'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        // 校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        
        if (!$this->utility->is_mobile($params['mobile'])) {
            $this->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
            $this->output_json_return();
        }
        
        // 签名校验
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['login_type'] = 3;
        // 校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        
        // 校验验证码
        $ret = $this->utility->verify_mobile_code($params['mobile'], $params['chk_code']);
        if (!$ret) {
            $this->output_json_return();
        }
        
        // 检测该手机号是否注册过
        $_uuid = $this->user_model->chk_user_account($params['mobile'], $params['login_type']);
        if (!$_uuid) { // 没注册过
            $this->error_->set_error(Err_Code::ACCOUNT_NOT_FOUND);
            $this->output_json_return();
        } else {
            //事务开始
            $this->user_model->start();
            //记录用户登录设备
            $params['uuid'] = $_uuid;
            //获取用户信息
            $data = $this->utility->get_user_info($params['uuid']);
            if(!$data) {
                $this->output_json_return();
            }
            
            //更新最后登录时间
            $fields = array( 'U_LASTLOGINTIME' => $this->zeit);
            $rst = $this->user_model->update_user_info($params['uuid'],$fields); // pl_user
            if (!$rst) {
                $this->user_model->error();
                $this->output_json_return();
            }
            
            //记录用户登录设备
            $rst = $this->user_model->record_user_device($params);
            if (!$rst) {
                $this->user_model->error();
                $this->output_json_return();
            }
            $params['nickname'] = $data['nickname'];
            
            //记录用户登录日志
            $rst = $this->user_model->record_user_login_history($params);
            if (!$rst) {
                $this->user_model->error();
                $this->output_json_return();
            }
            $this->user_model->success();
            
            // 设置登陆的token,保存到MC上
            $expires = time()+$this->passport->get('token_expire');
            $token = $this->set_token($params['uuid'], $params['app_id'], $params['device_id']);
            $data['token'] = $token;
            $data['expires'] = $expires;
            $this->output_json_return($data);
        }
    }
    
    /*
     * 重置密码
     */
    public function update_user_pwd() {
        $params                 = $this->get_public_params();
        $params['mobile']       = $this->request_param('mobile');
        $params['password']     = $this->request_param('password');
        $params['re_password']  = $this->request_param('re_password');
        // 校验参数
        if ($params['mobile'] == '' || $params['password'] == '' || $params['re_password'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        // 校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['password'] = md5($params['password']);
        $params['re_password'] = md5($params['re_password']);
        
        // 渠道 0：微信1：QQ 2：九城，3:手机号
        $params['login_type'] = 3;
        
        // 校验手机号
        if (!$this->utility->is_mobile($params['mobile'])) {
            $this->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
            $this->output_json_return();
        }
        if ($params['password'] !== $params['re_password']) {
            $this->error_->set_error(Err_Code::TIMES_2_PWD_DIFF);
            $this->output_json_return();
        }
       
        // 校验账号是否存在
        $_uuid = $this->user_model->chk_user_account($params['mobile'], $params['login_type']);
        if (!$_uuid || $_uuid != $params['uuid']) {
            $this->error_->set_error(Err_Code::ACCOUNT_NOT_FOUND);
            $this->output_json_return();
        }
        $res = $this->user_model->update_user_pwd($params['mobile'], 3, $params['password']);
        if (!$res) {
            $this->error_->set_error(Err_Code::UPDATE_PWD_FAIL);
            $this->output_json_return();
        }
        
        $this->output_json_return();
    }
    
    /**
     * 获取好友用户的个人基本信息
     */
    public function get_other_user_info()
    {
        $params             = $this->get_public_params();
        $params['ouser_id'] = $this->request_param('ouser_id');
        if (!$params['ouser_id']) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        //获取用户信息
        $data['user_info'] = $this->utility->get_user_info($params['ouser_id']);
        if (!$data['user_info']) {
            $this->error_->set_error(Err_Code::ERR_USER_INFO_NO_DATA);
            $this->output_json_return();
        }
        $this->output_json_return($data);
    }
    
    /**
     * 沙包用户
     */
    public function sandbags_user()
    {
        $this->load->model('game_model');
        $this->zeit = date('Y-m-d H:i:s', time());
        
        $params['method']       = $this->request_param('method');
        $params['nickname']     = urldecode($this->request_param('nickname'));
        $params['image']        = urldecode($this->request_param('image'));
        
        $params['id']           = $this->request_param('game_id');
        $params['score']        = $this->request_param('score');
        $params['spend_time']   = $this->request_param('spend_time');
        
        $params['content']      = $this->request_param('comment');
        $params['scoring']      = $this->request_param('game_comment_score');
        
        
        if ($params['nickname'] == '' || $params['image'] == '' || $params['id'] == '' || $params['score']== '' || $params['spend_time'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        $this->load->model('game_model');
        $game_info = $this->game_model->get_game_info_by_gameid($params['id']);
        
        if (!$game_info) {
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        
        // 1.用户注册
        //新注册用户 插入用户表 
        $params['mobile']   = "";
        $params['gender']   = "";
        $params['province'] = "";
        $params['login_type']  = "3";
        
        $params['uuid'] = $this->user_model->insert_user_info($params);
        if (!$params['uuid']) {
            $this->user_model->error();
            $this->output_json_return();
        }
        
        //插入用户登入表
        $params['user_id'] = $params['mobile'];
        $rst = $this->user_model->insert_user_login($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        
        
        // 插入用户评分评论
        if ($params['content'] && $params['scoring']) {
            $this->game_model->comment($params);
        }
       
        // 最后更新用户状态 status = 10 沙包用户
        $this->user_model->update_user_info($params['uuid'], array('status'=>10));
        
        $this->output_json_return();
    }
    
    /**
     * 游戏最高分
     */
    public function game_info()
    {
        $this->load->model('game_model');
        $game_list = $this->game_model->game_info();
        if (!$game_list) {
            $this->error_->set_error(Err_Code::ERR_GAME_GET_FAIL);
            $this->output_json_return();
        }
        $this->output_json_return($game_list);
    }
    
    
    /**
     * 注册550个测试账号
     */
    public function register_test550()
    {
        $params['gender']       = "";
        $params['province']     = "";
        $params['image']        ="";
        $params['app_id']       = 1;
        $params['device_id']    = 1;
        $params['os'] = 0;
        $params['version']      = "1.0.1";
        $params['password']     = 123456;
        $params['login_type']      = 3;
        $params['source']       = 3;
        $params['app_id']       = (int)$params['app_id'];
        $params['os']           = (int)$params['os'];
        $params['source']       = (int)$params['source'];

        for ($i=0; $i<550; $i++) {
            // 随机手机号  /^(13|14|15|18)\d{9}$/
            $mobile             = "";
            $a                  = 13;
            $b                  = 14;
            $c                  = 15;
            $d                  = 18;
            $aa                 = mt_rand(100000000,999999999);
            $aa_new             = (string)$aa;
            $arr                = array('13','14','15');
            $key                = array_rand($arr);
            $mobile             = $arr[$key];
            $moblie1            = $mobile . $aa_new;
            $params['account']  = $moblie1; // 手机号
            $params['nickname'] = rand(10000,99999);
            //校验手机号
            if ($params['account'] == "") {
                $this->error_->set_error(Err_Code::ERR_MOBILE_NO_DATA);
                $this->output_json_return();
            }
           

            //事务开始
            $this->user_model->start();
            //查询该用户是否可注册(手机号)
            $_uuid = $this->user_model->chk_user_account($params['account'],$params['login_type']);
            if($_uuid) {
                 continue;
            }

            //新注册用户 插入用户表 
            $params['mobile'] = $params['account'];
            $params['uuid'] = $this->user_model->insert_user_info($params);
            if (!$params['uuid']) {
                 continue;
            }
            //插入用户登入表
            $params['user_id'] = $params['account'];
            $rst = $this->user_model->insert_user_login($params);
            if (!$rst) {
                continue;
            }
            
            //记录用户登录设备
            $rst = $this->user_model->record_user_device($params);
            if (!$rst) {
                continue;
            }
            
            $this->user_model->success();
        }
        $this->output_json_return();
    }
    
    /**
     * 游客登录
     */
    public function visitor_login()
    {
        log_scribe('trace', 'params', 'visitor_login:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params['channel_id']   = $this->request_param('channel_id');
        $params['app_id']       = $this->request_param('app_id');
        $params['device_id']    = $this->request_param('device_id');
        $params['method']       = $this->request_param('method');
        $params['os']           = $this->request_param('os');
        $params['version']      = $this->request_param('version');
        $params['login_type']   = $this->request_param('login_type');
        $params['source']       = $this->request_param('source');
        $params['sign']         = $this->request_param('sign');
        // 游客
        $params['image']        = '';
        
        if ($params['app_id'] == "" || $params['device_id'] == "" || $params['os'] === "" || $params['version'] == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['app_id'] = (int)$params['app_id'];
        $params['os'] = (int)$params['os'];
        $params['login_type'] = (int)$params['login_type'];
        $params['source'] = (int)$params['source'];
        //校验渠道
        if($params['login_type'] != 4){
            $this->error_->set_error(Err_Code::ERR_CHANNEL_FAIL);
            $this->output_json_return();
        }
        //校验操作系统
        if(!in_array($params['os'], $this->passport->get('playme_os'))){
            $this->error_->set_error(Err_Code::ERR_OS_FAIL);
            $this->output_json_return();
        }
        
        //事务开始
        $this->user_model->start();
        //1.校验该游客账号是否存在
        $_uuid = $this->user_model->chk_user_account($params['device_id'],$params['login_type']);
        if($_uuid === false) {
            // 账户未注册，重新注册该游客账号，然后登录
            // 插入用户表 
            $params['nickname'] = '游客';
            $params['image']    = '';
            $params['mobile']   = '';
            $params['gender']   = '';
            $params['province'] = '';
            $params['gold']     = 25;
            $params['uuid']     = $this->user_model->insert_user_info($params);
            if (!$params['uuid']) {
                $this->user_model->error();
                $this->output_json_return();
            }
            //插入用户登入表
            $params['user_id'] = $params['device_id'];
            $rst = $this->user_model->insert_user_login($params);
            if (!$rst) {
                $this->user_model->error();
                $this->output_json_return();
            }
            
        } else {
            // 更新最后登录时间，直接登录
            $params['uuid'] = $_uuid;
            //更新最后登录时间
            $fields = array( 'U_LASTLOGINTIME' => $this->zeit);
            $rst = $this->user_model->update_user_info($params['uuid'],$fields);
            if (!$rst) {
                $this->user_model->error();
                $this->output_json_return();
            }
        }
        
        //记录用户登录设备(source: 包下载来源)
        $rst = $this->user_model->record_user_device($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //记录用户登录日志
        $params['nickname'] = '游客';
        $rst = $this->user_model->record_user_login_history($params);
        if (!$rst) {
            $this->user_model->error();
            $this->output_json_return();
        }
        //获取用户信息
        $data = $this->utility->get_user_info($params['uuid']);
        $this->user_model->success();
        
        // 完善用户信息之后， 调用任务
        $this->user_model->start();
        if (($params['u_icon'] || $data['image'])  && ($params['nickname'] || $data['nickname']) && ($data['mobile'] || $params['mobile'])) {
            $this->tasklib->task_full_user_info($params['uuid']);
        }
        $this->user_model->success();
        
        //加入登陆任务
        $this->tasklib->task_login_award($params['uuid']);
        
        //获取用户信息
        $expires = time()+$this->passport->get('token_expire');
        $token = $this->set_token($params['uuid'],$params['app_id'],$params['device_id']);
        $data['token'] = $token;
        $data['expires'] = $expires;
        $this->output_json_return($data); 
    }
    
    
    /**
     * 开发者用户校验（授权）接口  -获取device_id用户信息接口
     */
    public function user_info_grant()
    {
        $params['developer_id']   = $this->request_param('developer_id');
        $params['uuid']     = $this->request_param('uuid');
        $params['token']    = $this->request_param('token');
        $params['method']   = $this->request_param('method');
        $params['sign']     = $this->request_param('sign');
        $params['device_id']= $this->request_param('device_id');
        
        if($params['uuid'] == "" || $params['device_id']== "" ||  $params['token'] == "" || $params['method'] == "" || $params['sign'] == "" || $params['developer_id'] == ''){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验token是否有效
        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
            $this->output_json_return();
        }
        // 获取开发者ID对应的KEY
        $qualificate_info = $this->user_model->get_developer_key($params['developer_id']);
        if (!$qualificate_info) {
            $this->output_json_return();
        }
        $params['sign_key'] = $qualificate_info['developer_key'];
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        // 九成渠道ID = '1'
        if ($params['channel_id'] == '1') {
            // 统计，点击个人信息，次数
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_MYINFO', $time);
        }
        
        //获取用户信息
        $info = $this->utility->get_user_info($params['uuid']);
        if (empty($info)) {
            $this->error_->set_error(Err_Code::ERR_USER_INFO_NO_DATA);
            $this->output_json_return($data);
        }
        $info['token'] = $params['token'];
        $expires = time()+$this->passport->get('token_expire');
        $info['expires'] = $expires;
        $data['user_info'] = $info;
        if ($info['token']) {
            //增加登录任务
            $this->user_model->start();
            $this->tasklib->task_login_award($params['uuid']);
            $this->user_model->success();
        }
        $this->output_json_return($data);
    }
    
    /**
     * 开发者用户校验（授权）接口  -获取用户信息接口
     */
    public function user_info_debug()
    {
        $params['developer_id']   = $this->request_param('developer_id');
        $params['uuid']     = $this->request_param('uuid');
        $params['token']    = $this->request_param('token');
        $params['method']   = $this->request_param('method');
        $params['sign']     = $this->request_param('sign');
        $params['device_id']= $this->request_param('device_id');
        if($params['uuid'] == "" || $params['device_id']== "" ||  $params['token'] == "" || $params['method'] == "" || $params['sign'] == "" || $params['developer_id'] == ''){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验用户token
        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
            $this->output_json_return();
        }
        
        // 获取开发者ID对应的KEY
        $qualificate_info = $this->user_model->get_developer_key($params['developer_id']);
        if (!$qualificate_info) {
            $this->output_json_return();
        }
        $params['sign_key'] = $qualificate_info['developer_key'];
        //检查签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        // 九成渠道ID = '1'
        if ($params['channel_id'] == '1') {
            // 统计，点击个人信息，次数
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_MYINFO', $time);
        }
        
        //获取用户信息
        $info = $this->utility->get_user_info($params['uuid']);
        if (empty($info)) {
            $this->error_->set_error(Err_Code::ERR_USER_INFO_NO_DATA);
            $this->output_json_return($data);
        }
        $info['token'] = $params['token'];
        $expires = time()+$this->passport->get('token_expire');
        $info['expires'] = $expires;
        $data['user_info'] = $info;
        if ($info['token']) {
            //增加登录任务
            $this->user_model->start();
            $this->tasklib->task_login_award($params['uuid']);
            $this->user_model->success();
        }
        $this->output_json_return($data);
    }
    
    /**
     * 保存用户的token值 -- 开发者
     */
    function save_mc_debug()
    {
        $uuid = $this->request_param('uuid');
        $device_id = $this->request_param('device_id');
        
        if (!$uuid || !$device_id) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        $app_id = $device_id;
        
        $this->set_token($uuid, $app_id, $device_id);
        $token = $this->get_login_token($uuid, $device_id);
        if (!$token) {
            $this->error_->set_error(Err_Code::ERR_MC_FAIL);
        }
        $this->output_json_return($token);
    }
   
    
}
