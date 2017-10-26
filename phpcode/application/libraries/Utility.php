<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Utility {
    private $CI;
    const MOBILE_CODE_PRE = 'M_';
    function __construct() {
        $this->CI = & get_instance();
        $this->SignKey = array();
    }
    
    /**
     * 签名认证
     * $params   array 生成签名的数组        必须
     * $sign     str   客户端发送的对照签名  必须
     * $platform str   生成指定游戏平台的签名
     */
    function check_sign($params, $sign) {
        if($sign == ""){
            $this->CI->error_->set_error(Err_Code::ERR_PARA);
            return false;
        }
        $new_sign = $this->get_sign($params);var_dump($new_sign);exit;
//        if(ENVIRONMENT != "development"){
//            log_scribe('trace', 'params', 'check_sign:'.$this->CI->input->ip_address().'  sign：'.$sign .' |||   new_sign:'.$new_sign);
//            if($sign != $new_sign){
//                $this->CI->error_->set_error(Err_Code::ERR_PARAM_SIGN);
//                return false;
//            }
//        }
        return true;
    }
    
    /**
     * 签名生成
     */
    function get_sign($params) {
        //除去数组中的空值和签名参数
        while (list($key, $val) = each($params)) {
            if ($key == "sign" || ($val === "") || $key == 'sign_key'){
                continue;
            } else {
                $para[$key] = $params[$key];
            }
        }
        //对数组进行字母排序
        ksort($para);
        reset($para);
        while(list($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        if ($params['sign_key']) {
            $sign_key = $params['sign_key'];
        } else {
            $sign_key = $this->CI->passport->get('sign_key');
        }
        $arg .= "key=".$sign_key;
        return md5($arg);
    }
    
    /**
     * 获取拼接签名参数
     */
    public function get_sign_params($params)
    {
        //除去数组中的空值和签名参数
        while (list($key, $val) = each($params)) {
            if ($key == "sign" || $key == "sign_type" ||  ($val === "")){
                continue;
            } else {
                $para[$key] = $params[$key];
            }
        }
        //对数组进行字母排序
        ksort($para);
        reset($para);
        while(list($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
        return $arg;
    }
    
    /**
     * 获取拼接签名参数，并对字符串做urlencode编码
     */
    public function get_sign_by_urlencode($params)
    {
        //除去数组中的空值和签名参数
        while (list($key, $val) = each($params)) {
            if ($key == "sign" || $key == "sign_type" || ($val === "")){
                continue;
            } else {
                $para[$key] = $params[$key];
            }
        }
        //对数组进行字母排序
        ksort($para);
        reset($para);
        while(list($key, $val) = each($para)) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }
        //去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
        return $arg;
    }
    
    
    /**
     * stringLen
     *
     * @param string $str
     * @param integer $type 1 字母与汉字做为1个字符, 2 汉字做为两个字符
     * @return integer
     */
    public function stringLen($str, $type = 1) {
        $len = mb_strlen($str, 'UTF-8');
        if ($type == 2) {
            for ($i = 0; $i < $len; $i++) {
                $char = mb_substr($str, $i, 1, 'UTF-8');
                if (ord($char) > 128) {
                    $len++;
                }
            }
        }
        return $len;
    }
    
    //检查登录密码格式
    function chk_nickname($val) {
        $len = $this->stringLen($val);
        if($le>10 || $len < 2){
            return false;
        }
        return true;
    }
    
    //检查登录密码格式
    function chk_pwd($val) {
        if (!preg_match('/^[a-zA-Z0-9_]{6,16}$/', $val)) {
            return false;
        }
        return true;
    }
    
    //是否为手机号
    function is_mobile($val) {
        if (!preg_match('/^(13|14|15|18)\d{9}$/', $val)) {
            return false;
        }
        return true;
    }
    
    //获取手机验证码
    function get_mobile_code($mobile, $force = true) {
        $expire = $this->CI->passport->get('mobile_code_expire');
        $length = $this->CI->passport->get('mobile_code_length');
        $mobile_code = $this->CI->cache->memcached->get(self::MOBILE_CODE_PRE . $mobile);
        if (empty($mobile_code) || $force) {
            $mobile_code = $this->gen_rand_str($length, 'numeric');
            $this->CI->cache->memcached->save(self::MOBILE_CODE_PRE . $mobile, $mobile_code, $expire);
        }
       
        return $mobile_code;
    }

    //验证手机验证码
    function verify_mobile_code($mobile, $mobile_code, $unset_on_true = false, $unset_on_false = false) {
        if (!$this->is_mobile($mobile)) {
            $this->CI->error_->set_error(Err_Code::ERR_MOBILE_FORMAT);
            return false;
        }
        $code = $this->CI->cache->memcached->get(self::MOBILE_CODE_PRE . $mobile);
        if (empty($code)) {
            $this->CI->error_->set_error(Err_Code::ERR_MOBILE_VERIFY_CODE_LOSE);
            if ($unset_on_false) {
                $this->CI->cache->memcached->delete(self::MOBILE_CODE_PRE . $mobile);
            }
            return false;
        }
        if ($mobile_code != $code) {
            $this->CI->error_->set_error(Err_Code::ERR_MOBILE_VERIFY_CODE_FAIl);
            if ($unset_on_false) {
                $this->CI->cache->memcached->delete(self::MOBILE_CODE_PRE . $mobile);
            }
            return false;
        }
        if ($unset_on_true) {
            $this->CI->cache->memcached->delete(self::MOBILE_CODE_PRE . $mobile);
        }
        return true;
    }

    //获取随机字符串
    function gen_rand_str($len, $type = null) {
        switch ($type) {
            case 'reduced':
                $chars = array(
                    "a", "c", "d", "e", "f", "g", "h", "i", "j", "k",
                    "m", "n", "p", "r", "s", "t", "u", "v",
                    "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
                    "H", "J", "K", "L", "M", "N", "P", "Q", "R",
                    "S", "T", "U", "V", "W", "X", "Y", "Z", "2",
                    "3", "4", "5", "7", "8",
                );
                break;
            case 'numeric':
                $chars = array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
                break;
            default:
                $chars = array(
                    "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
                    "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
                    "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
                    "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
                    "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
                    "3", "4", "5", "6", "7", "8", "9",
                );
                break;
        }
        $chars_len = count($chars) - 1;
        shuffle($chars);
        $output = "";
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $chars_len)];
        }
        return $output;
    }

    
    //通过节点路径返回字符串的某个节点值
    function get_data_for_xml($res_data, $node) {
        $xml = simplexml_load_string($res_data);
        $result = $xml->xpath($node);

        while (list(, $node) = each($result)) {
            return $node;
        }
    }

    //访问外部地址（POST方式）
    function post($url, $post_data = array()) {
        if (is_array($post_data)) {
            $qry_str = http_build_query($post_data);
        } else {
            $qry_str = $post_data;
        }
        log_scribe('trace', 'proxy_php', 'POST Request: ' . $url . ' post_data' . $qry_str);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '15');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // Set request method to POST
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set query data here with CURLOPT_POSTFIELDS
        curl_setopt($ch, CURLOPT_POSTFIELDS, $qry_str);

        $content = trim(curl_exec($ch));

        log_scribe('trace', 'proxy_php', 'POST Response: ' . $content);
        curl_close($ch);
        return $content;
    }
    //get 方式
    public function get($url, $fields = array()) {
        if (is_array($fields)) {
            $qry_str = http_build_query($fields);
        } else {
            $qry_str = $fields;
        }
        if (trim($qry_str) != '') {
            $url = $url . '?' . $qry_str;
        }
        log_scribe('trace', 'proxy_php', 'GET Request: ' . $url);
        $ch = curl_init();
        // Set query data here with the URL
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '100');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $content = trim(curl_exec($ch));
        curl_close($ch);
        log_scribe('trace', 'proxy_php', 'GET Response: ' . $content);
        return $content;
    }
    
    
    //访问外部地址（HTTPS POST方式）
    function wx_post($url, $post_data = '') {
        log_scribe('trace', 'proxy_php', 'POST Request: ' . $url . ' post_data' . $post_data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, '15');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        // Set request method to POST
        curl_setopt($ch, CURLOPT_POST, 1);

        // Set query data here with CURLOPT_POSTFIELDS
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $content = curl_exec($ch);
        // var_dump( curl_error($ch) );exit;//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        log_scribe('trace', 'proxy_php', 'POST Response: ' . $content);
        log_scribe('trace', 'proxy_php', 'POST Response: ' . curl_error($ch));
        curl_close($ch);
        return $content;
    }
    
    /**
    * 远程获取数据，POST模式
    * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
    * @param $url 指定URL完整路径地址
    * @param $cacert_url 指定当前工作目录绝对路径
    * @param $para 请求的数据
    * @param $input_charset 编码格式。默认值：空值
    * return 远程输出的数据
    */
    function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '') {

        if (trim($input_charset) != '') {
                $url = $url."_input_charset=".$input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl,CURLOPT_POST,true); // post传输数据
        curl_setopt($curl,CURLOPT_POSTFIELDS,$para);// post传输数据
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }
   
    /**
    * 远程获取数据，GET模式
    * 注意：
    * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
    * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
    * @param $url 指定URL完整路径地址
    * @param $cacert_url 指定当前工作目录绝对路径
    * return 远程输出的数据
    */
   function getHttpResponseGET($url,$cacert_url = '') {
           $curl = curl_init($url);
           curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
           curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
           curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
           curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);//严格认证
           // curl_setopt($curl, CURLOPT_CAINFO,$cacert_url);//证书地址
           $responseText = curl_exec($curl);
           //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
           curl_close($curl);

           return $responseText;
   }
   
    /* *
    * 支付宝接口RSA函数
    * 详细：RSA签名、验签、解密
    * 版本：3.3
    * 日期：2012-07-23
    * 说明：
    * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
    * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。
    */

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
    * RSA解密
    * @param $content 需要解密的内容，密文
    * @param $private_key_path 商户私钥文件路径
    * return 解密后内容，明文
    */
   function rsaDecrypt($content, $private_key_path) {
       $priKey = file_get_contents($private_key_path);
       $res = openssl_get_privatekey($priKey);
           //用base64将内容还原成二进制
       $content = base64_decode($content);
           //把需要解密的内容，按128位拆开解密
       $result  = '';
       for($i = 0; $i < strlen($content)/128; $i++  ) {
           $data = substr($content, $i * 128, 128);
           openssl_private_decrypt($data, $decrypt, $res);
           $result .= $decrypt;
       }
       openssl_free_key($res);
       return $result;
   }
   
       
    //获得用户信息
    function get_user_info($uuid, $fields = array()) {
        $this->CI->load->model('user_model');
        $ret = $this->CI->user_model->get_user_info_by_uuid($uuid);
        if ($ret === false) {
            return array();
        }
        if (empty($fields)){
            return $ret;
        }
        if (!is_array($fields)) {
            return $ret[$fields];
        }
        $res = array();
        foreach ($fields as $key) {
            $res[$key] = $ret[$key];
        }
        return $res;
    }
    
    // 获取用户信息， 通过 fid_info
    public function get_user_info_by_friend_id($friend_id)
    {
        $this->CI->load->model('user_model');
        $res = array();
        $ret = $this->CI->user_model->get_user_info_by_uuid($friend_id);
        if ($ret === false) {
            return false;
        }
        
        if (!$ret) {
            return false;
        }
        return $ret;
    }
    
    /**
     * 获取用户id, 通过mobile
     */
    public function get_user_info_by_mobile($mobile, $fields = '')
    {
        $this->CI->load->model('user_model');
        $ret = $this->CI->user_model->get_user_info_by_mobile($mobile);
        if ($ret === false) {
            return array();
        }
        if (empty($fields)){
            return $ret;
        }
        if (!is_array($fields)) {
            return $ret[$fields];
        }
        $res = array();
        foreach ($fields as $key) {
            $res[$key] = $ret[$key];
        }
        return $res;
    }
    
    // 获取非法字符
    public function get_illegal_char()
    {
        $illegal_char = $this->CI->passport->get_illegal('filter_illegal_char');
        $illegal_char = array_unique($illegal_char);
        return $illegal_char;
    }
    
    /**
     * 将数组转为XML格式
     */
    public function array_to_xml($arr)
    {
        //对数组进行字母排序
        ksort($arr);
        reset($arr);
        $xml = '';
        $xml .= '<xml>';
        foreach ($arr as $k=>$v) {
            // $xml .= '<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
            $xml .= '<'.$k.'>'.$v.'</'.$k.'>';
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * 将XML转为数组
     */
    public function xml_to_array($xml)
    {
        // $xml = "<xml><aa><![CDATA[aaa ]]></aa><c><ddd>sdsdsd</ddd><eee>dsfsdf</eee></c><b>eeee</b></xml>";
        $xml_obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xml_arr = json_decode(json_encode($xml_obj),TRUE);
        return $xml_arr;
    }
    
    /**
     * 按钮统计公用方法
     */
    public function button_statistics($type = "", $time)
    {
        if (!$type || !$time) {
            return false;
        }
        $this->CI->load->model('api_model');
        $res = $this->CI->api_model->button_statistics($type, $time);
        if (!$res) {
            return false;
        }
        return true;
    }
     /**
     * 计算玩家等级
     */
    public function user_grade($integral)
    {
        $mod    = $integral%1000;
        $grade = ceil($integral/1000);
        if (!$mod) {
            ++$grade;
        }
        if($grade == 0) {
            $grade = 1;
        } elseif ($grade > 99){
            $grade = 99;
        }
        return $grade;
    }
    
    /**
     * 获取ios版本信息
     */
    public function check_ios_version()
    {
        $this->CI->load->model('game_model');
        $info   = $this->CI->game_model->get_ios_version();
        if (!$info) {
            return false;
        }
        return $info;
    }
}
