<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Passport {

    private $CI;
    private $_cache = null;

    function __construct() {
        $this->CI = & get_instance();
    }

    function get($item_name) {
        $this->CI->config->load('passport', true);

        $item = $this->CI->config->item($item_name, 'passport');
        
        return $item;
    }

    function get_config($item_name) {
        $this->CI->config->load('config', true);

        $item = $this->CI->config->item($item_name, 'config');
        return $item;
    }
    
    function get_illegal($item_name)
    {
        $this->CI->config->load('illegal_char', true);
        $item = $this->CI->config->item($item_name, 'illegal_char');
        return $item;
    }
    
    /**
     * 是否是公司内部ip地址
     */
    function is_inner_ip($val) {
        if (empty($val)) {
            return false;
        }
        for ($i = 1; $i <= 64; $i++) {
            $ip_list[] = "211.144.214." . $i;
        }
        for ($i = 1; $i <= 64; $i++) {
            $ip_list[] = "211.144.221." . $i;
        }
        for ($i = 40; $i <= 47; $i++) {
            $ip_list[] = "211.144.216." . $i;
        }
        for ($i = 1, $j = $i + 64; $i <= 64; $i++, $j++) {
            $ip_list[] = "61.152.123." . $j;
        }
        for ($j = 144; $j <= 159; $j++) {
            $ip_list[] = "61.152.123." . $j;
        }
        for ($i = 1, $j = $i + 47; $i <= 15; $i++, $j++) {
            $ip_list[] = "219.142.44." . $j;
        }
        switch (ENVIRONMENT) {
            case 'development':
            case 'testing':
                for ($i = 1; $i <= 255; $i++) {
                    $ip_list[] = "172.18.196." . $i;
                }
                break;
        }
        if (!in_array($val, $ip_list)) {
            return false;
        }
        return true;
    }
}
