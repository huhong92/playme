<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Signkey {
    private $CI;
    function __construct() {
        $this->CI = & get_instance();
    }
    /*
     * bailu签名生成
     */
    function bailu_sign($params)
    {
        $appkey = $this->CI->passport->get('bailu_key');
        $str  = "";
        if(isset($params['sign']))
            unset($params['sign']);
        if(isset($params['page']))
            unset($params['page']);
        if(isset($params['per']))
            unset($params['per']);
        ksort($params);
        reset($params);
        foreach($params as $key=>$value)
        {
            $str  .=  $key ."=". $value;
        }
        return md5($str.$appkey);      

    }
    
    
}
