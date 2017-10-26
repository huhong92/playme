<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sms {
    private $CI;
    function __construct() {
        $this->CI = & get_instance();
    }

    function send($mobile = '', $message = '', $schedule_ts = '', $repeat = '', $seq_num = '') {
        $message .= "。系统发送，请勿回复。【第九城市】";
        $ret = $this->send_sdk($mobile, $message); 
        return $ret;
    }

    function send_sdk($mobile = '', $message = '', $schedule_ts = '', $repeat = '', $seq_num = '') {
        $username = 'SDK-BBX-010-19321';
        $pwd = '6C9-9816';
        $password = strtoupper(md5($username . $pwd));
        $sdk_url = "http://sdk162.entinfo.cn/webservice.asmx/mt";
        $fields = "Sn={$username}&Pwd={$password}&Mobile={$mobile}&Content={$message}&stime={$schedule_ts}&Ext={$repeat}&Rrid={$seq_num}";
        $fields = iconv("utf-8", "gb2312//IGNORE", $fields);
        log_scribe('trace', 'sms', $this->CI->input->ip_address() . ' SMS Request(SDK): ' . $sdk_url . '?' . $fields);
        if (ENVIRONMENT === 'development' || ENVIRONMENT === 'testing') {
            $ret = '<?xml version="1.0" encoding="utf-8"?><string xmlns="http://tempuri.org/">1235456</string>';
        } else {
            $ret = $this->CI->utility->get($sdk_url, $fields);
        }
        log_scribe('trace', 'sms', $this->CI->input->ip_address() . ' SMS Response(SDK):content ' . $ret);
        $ret = simplexml_load_string($ret);
        $_ret = json_decode(json_encode($ret),true);
        if (substr($_ret[0], 0, 1) == '-' || empty($_ret[0])) {
            $this->CI->error_->set_error(Err_Code::ERR_SEND_MSG_FAIl);
            return false;
        }
        return true;
    }
}
