<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class P_Controller extends CI_Controller {
    protected $zeit;
    function __construct() {
        parent::__construct();
        $this->L_MC_TOKEN = 'T_MC';
        $this->load->driver('cache');
        $this->ip = $this->input->ip_address();
        $this->zeit = date('Y-m-d H:i:s', time());
        header("Access-Control-Allow-Origin: *");
    }
    
    //输出json数据
    public function output_json_return($data = array()) {
        $code = $this->error_->get_error();
        if ($code == null) {
            $this->error_->set_error(Err_Code::ERR_OK);
        }
        if(empty($data)){
           $data = new stdClass();
        }
        $this->json->output(array('c' => $this->error_->get_error(), 'm' => $this->error_->error_msg(),'data' => $data));
    }
    
    //post参数
    public function post_parameter($key) {
        if ($key == '') {
            return false;
        }
        $p = $this->input->post($key, true);
        if (is_array($p)) {
            return $p;
        }
        return trim($p);
    }
    
    //get 参数
    public function get_param($key) {
        if ($key == '') {
            return false;
        }
        $p = $this->input->get($key, true);
        if (is_array($p)) {
            return $p;
        }
        return trim($p);
    }
    
    //不区分get/post 参数
    public function request_param($key) {
        if ($key == '') {
            return false;
        }
        $p = $this->input->get_post($key, true);
        if (is_array($p)) {
            return $p;
        }
        return trim($p);
    }
    
    //通用参数
    function get_public_params(){
        $params = array(
            'uuid'      => (int)$this->request_param('uuid'),
            'channel_id'=> $this->request_param('channel_id'),
            'app_id'    => $this->request_param('app_id'),
            'device_id' => $this->request_param('device_id'),
            'token'     => $this->request_param('token'),
            'method'    => $this->request_param('method'),
            'version'   => $this->request_param('version'),
            'sign'      => $this->request_param('sign'),
        );
        
        if ($params['token'] == "" || $params['uuid'] == "") {
            $this->error_->set_error(Err_Code::ERR_TOKEN_EMPTY);
            $this->output_json_return();
        }
        if( $params['channel_id'] == '' ||  $params['app_id'] == "" || $params['device_id']== "" ||  $params['method'] == "" || $params['sign'] == ""){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验token是否有效
//        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
//            $this->output_json_return();
//        }
        return $params;
    }
    
    //通用参数 未登录情况
    function get_public_params_no_token(){
        $params = array(
            'uuid'      => $this->request_param('uuid'), // 可选
            'channel_id'=> $this->request_param('channel_id'),
            'app_id'    => (int)$this->request_param('app_id'),
            'device_id' => $this->request_param('device_id'),
            'token'     => $this->request_param('token'), // 可选
            'method'    => $this->request_param('method'),
            'version'   => $this->request_param('version'),
            'sign'      => $this->request_param('sign'),
        );
        if($params['channel_id'] == '' || $params['app_id'] == "" || $params['device_id']== "" || $params['method'] == "" || $params['sign'] == ""){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验token是否有效
        if ($params['uuid'] && $params['token']) {
            $params['uuid'] = (int)$params['uuid'];
            if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
                $this->output_json_return();
            }
        }
        return $params;
    }
    
    // 校验用户是否登陆
    function is_login($uuid, $device_id, $token) {
        $expire = $this->passport->get('token_expire');
        $token_info = $this->get_login_token($uuid, $device_id);
        if ($token_info === false) { 
            $this->error_->set_error(Err_Code::ERR_LOGIN_TOKEN_FAIL);
            return false;
        }
        if ($token_info['token'] != $token) {
            $this->error_->set_error(Err_Code::ERR_LOGIN_TOKEN_FAIL);
            return false;
        }
        $diff_time = time() - $token_info['login_ts'];
        if ($diff_time > $expire) { //登录过期
            $this->error_->set_error(Err_Code::ERR_LOGIN_TOKEN_EXPIRE);
            $this->del_appid_login($uuid, $device_id);
            return false;
        }
        return true;
    }
    
    /* 获取uuid在应用登录态的信息 */
    protected function get_login_token($uuid, $device_id) {
        $token_info = $this->cache->memcached->get($this->L_MC_TOKEN.$uuid.'/'.$device_id);
        if ($token_info === false) {
            return false;
        }
        
        return $token_info;
    }
    
    /* 将用户登录信息存放到memcache中,同时记录uuid到cookie */
    function set_token($uuid, $app_id, $device_id) {
        $token_key = $this->passport->get('token_key');
        $expire = $this->passport->get('token_expire');
        $login_ts = time();
        $login_expire_ts = $login_ts + $expire;
        $token = $this->gen_login_token($uuid, $app_id, $device_id,$login_ts);
        $item['device_id'] = $device_id;
        $item['token'] = $token;
        $item['login_ts'] = $login_ts;
        $item['login_expire_ts'] = $login_expire_ts;
        $this->cache->memcached->save($this->L_MC_TOKEN.$uuid.'/'.$device_id, $item, $expire); //设置用户登录信息
        return $token;
    }
    
    /* 登录token生成 */
    protected function gen_login_token($uuid_id, $app_id, $device_id, $login_ts) {
        $token_key = $this->passport->get('token_key');
        return md5($uuid_id . $app_id . $token_key . $login_ts . $device_id);
    }
    
     /* 删除uuid的应用在mc的登录态 */
    protected function del_appid_login($uuid, $device_id) {
        $login_info = $this->get_login_token($uuid, $device_id);
        if ($login_info === false) {
            return true;
        }
        return $this->cache->memcached->delete($this->L_MC_TOKEN.$uuid.'/'.$device_id);
    }
}
