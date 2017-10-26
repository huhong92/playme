<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login {
	private $CI;
	
	public function __construct() {
		$this->CI =& get_instance();
		//$this->_user = $this->CI->session->userdata('user');
	}
	
	public function is_login() {
		$loginname = get_cookie('lgn');
		$user = $this->CI->session->userdata('user');
		if(!empty($user['loginname']) && $loginname == $user['loginname']) {
			//判断用户是否登录超时
			$current_ts = time();
			if(empty($user['login_ts']) 
			|| $current_ts - $user['login_ts'] > $this->CI->passport->get('login_expire')) {
				$this->CI->session->unset_userdata('user');
				return false;
			}
			$user['login_ts'] = $current_ts;
			$this->CI->session->set_userdata('user', $user);
			return true;
		}
		//$this->CI->session->sess_destroy();
		$this->CI->session->unset_userdata('user');
		return false;
	}
	
	public function process_login($loginname, $uuid = null, $save_cookie = true) {
		if(empty($uuid)){
			$ret = Membersvc::getUuidByName($this->CI->tuxedo->ssoilu(), array('loginname' => $loginname));
			if($this->CI->error_->error()) {
				return false;
			}
			$uuid = $ret['data']['uuid'];			
		}
				
		$user = array(
			'loginname' => $loginname,
			'uuid'      => $uuid,
			'login_ts'  => time(),
		);
		
		if($save_cookie){
			$this->CI->session->set_userdata('user', $user);
			$cookie = array(
				'name'   => 'lgn',
				'value'  => $user['loginname'],
				'expire' => '0',
			);
			set_cookie($cookie);
		}
		
		return array('user' => $user);
	}
	
	public function validate($loginname, $pwd, $site_id = '0000') {
		
		if(function_exists('auth_login')) {
			log_scribe('trace', 'login', $this->CI->input->ip_address().' ['.current_url().'] LOGIN_V2 '.$loginname.', '.$this->CI->utility->mosaic($pwd).', '.$this->CI->input->ip_address().', '.$site_id);
			$ret = auth_login($loginname, $pwd, $this->CI->input->ip_address(), $site_id);
			log_scribe('trace', 'login', $this->CI->input->ip_address().' ['.current_url().'] LOGIN_V2 '.json_encode($ret));
			if($ret == false || $ret['error_code'] != 0) {
				$this->CI->error_->set_login_error($ret['error_code']);
				return false;
			}
			$uuid   = trim($ret['uuid']);
			$status = trim($ret['status']);
			
			if(empty($uuid) || empty($status)){
				$this->CI->error_->set_error('10193');
				return false;
			}
		} else {
			log_scribe('trace', 'login', $this->CI->input->ip_address().' ['.current_url().'] LOGIN_V1 '.json_encode($this->CI->tuxedo->su(Svc::SVCID_LOGIN_PASSPORT)).', '.$loginname.', '.$this->CI->utility->mosaic($pwd));
			$ret = Membersvc::login($this->CI->tuxedo->su(Svc::SVCID_LOGIN_PASSPORT), array('loginname' => $loginname, 'password' => $pwd));
			log_scribe('trace', 'login', $this->CI->input->ip_address().' ['.current_url().'] LOGIN_V1 '.json_encode($ret));
			
			$err_code = $this->CI->error_->get_error();
			if($this->CI->error_->error() && !in_array($err_code, array('4170','4171'))) {
				// 4155：账号被冻结   4207：ip在黑名单，无法登录
				in_array($err_code, array('4155', '4207')) && $this->CI->error_->set_error('16071');
				return false;
			}
			
			// 获取uuid
			$ret = Membersvc::getUuidByName($this->CI->tuxedo->su(Svc::SVCID_NAME2UUID), array('loginname' => $loginname));
			if($this->CI->error_->error()) {
				return false;
			}
			$uuid = trim($ret['data']['uuid']);
			
			// 查询用户状态
			$ret = $this->CI->utility->get_info(array('status'), $uuid);
			if($this->CI->error_->error()) {
				return false;
			}
			$status = trim($ret['status']);
		}
		
		// 获取用户冻结状态配置，判断用户冻结状态
		$ret = $this->CI->passport->is_frozen_account($status);
		if($ret === true) {
			$this->CI->error_->set_error('16071');
			return false;
		}
		
		return array('loginname' => $loginname, 'uuid' => $uuid, 'status' => $status);
	}
	
	public function logout() {
		delete_cookie('lgn');
		$this->CI->session->sess_destroy();
	}
}