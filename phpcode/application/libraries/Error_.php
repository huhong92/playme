<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Error_ {

    const ERR_OK = '0000';
    private $CI;
    private $_error_code = null;
    private $_error_msg = null;

    function __construct($config = array()) {
        require_once 'inc/error.inc.php';
        require_once 'inc/error_code.php';
        $this->CI = & get_instance();
    }

    //返回调用成功返回的错误码
    function get_success() {
        return self::ERR_OK;
    }

    //返回错误码
    function get_error() {
        return $this->_error_code;
    }

    //设置错误码
    function set_error($error_code) {
        $argv = func_get_args();
        $this->_error_code = $error_code;
        $this->_error_msg = call_user_func_array(array($this, 'error_msg'), $argv);
    }
    
    //返回上次TUX调用是否失败
    function error() {
        if ($this->_error_code != self::ERR_OK) {
            return true;
        }
        return false;
    }

    function show_sdkwap_error($error_msg = null) {
        if ($error_msg == null) {
            $error_msg = $this->error_msg();
        }
        ob_start();
        $buffer = $this->CI->template->load('template', 'error', array('error_msg' => $error_msg), true);
        ob_end_clean();
        echo $buffer;
        exit;
    }

    //获取错误码对应的错误信息
    function error_msg($error_code = null) {
        $argv = func_get_args();
        if ($error_code === null) {
            return $this->_error_msg;
        }
        if (!isset(Err::$arErrCode[$error_code])) {
            $argv[0] = '未定义的错误码：[' . $error_code . ']';
        } else {
            $argv[0] = Err::$arErrCode[$error_code];
        }
        return $argv[0];
    }
    
    //设置错误码
    function set_error_webservice($error_code) {
        $argv = func_get_args();
        $this->_error_code = $error_code;
        $this->_error_msg = call_user_func_array(array($this, 'error_msg_webservice'), $argv);
    }
    
    //获取错误码对应的错误信息(webservice)
    function error_msg_webservice($error_code = null) {
       
        $argv = func_get_args();
        if ($error_code === null) {
            return $this->_error_msg;
        }
       
        if (!isset(Err_WebService::$arErrCode[$error_code])) {
            $argv[0] = '未定义的错误码：[' . $error_code . ']';
        } else {
            $argv[0] = Err_WebService::$arErrCode[$error_code];
        }
        return call_user_func_array('sprintf', $argv);
    }
    
    
    //获取所有错误码列表
    function error_list() {
        return Err::$arErrCode;
    }
}