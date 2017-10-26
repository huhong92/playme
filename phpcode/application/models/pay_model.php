<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Pay_model extends MY_Model {
    function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    // 商品信息by product_id
    public function product_info($product_id)
    {
        $condition = "STATUS = 0 AND IDX = ".$product_id;
        $select = array('P_NAME AS name', 'P_TYPE AS pro_type','P_PRICE AS price', 'P_PRICECURRENT AS price_current','P_IDX AS p_idx');
        $table = "pl_product";
        $product_info = $this->get_row_array($condition, $select, $table);
        if (!$product_info) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        return $product_info;
    }
    
    /**
     * 获取商品ID 通过 game_id/模板id/充值包id(商品表中P_IDX字段)
     */
    public function get_product_id_by_gameid($game_id, $pro_type = 0)
    {
        $condition = "STATUS = 0 AND P_TYPE = ".$pro_type." AND P_IDX = ".$game_id;
        $select = array('IDX AS product_id',);
        $table = "pl_product";
        $product_info = $this->get_row_array($condition, $select, $table);
        
        if (!$product_info) {
            return false;
        }
        return $product_info['product_id'];
    }
    
    /*
     * 商品信息插入订单表
     */
    public function insert_order($params)
    {
        $table = 'pl_order';
        $data = array(
            'O_ORDERIDX'    => $params['order_id'],
            'O_USERID'      => $params['uuid'],
            'O_NICKNAME'    => $params['nickname'],
            'O_TOTALPRICE'  => $params['total_price'],
            'O_PRODUCTNUM'  => $params['number'],
            'O_PRODUCTID'   => $params['product_id'],
            'O_PRODUCTTYPE' => $params['product_type'],
            'O_GETGLOD'     => $params['glod'],
            'O_PAYMETHOD'   => $params['pay_method'],
            'O_FEETYPE'     => $params['fee_type'],
            'O_DEVICETYPE'  => $params['device_type'],
            'O_ORDERSTATUS' => 2,
            'STATUS'        => 0,
            'ROWTIME'       => $this->zeit,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $ins = $this->DB->insert($table, $data);
        if ($ins === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return $this->DB->insert_id();
    }
    
    /**
     * 更新订单表，支付状态
     */
    public function update_order($order_id, $order_status = 0)
    {
        $table = 'pl_order';
        $data = array(
            'O_ORDERSTATUS' => $order_status,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $where = 'O_ORDERIDX = '.$order_id;
        $res = $this->DB->update($table, $data, $where);
        if ($res === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_PRODUCT_ORDER_UPDATE_FAIL);
            return false;
        }
        return true;
    }
    
    /**
     * 更新是否回调的状态
     */
    public function update_wx_callback_status($order_id, $callback_status = 1)
    {
        $table = 'pl_order';
        $data = array(
            'O_CALLBACK' => $callback_status,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $where = 'O_ORDERIDX = '.$order_id;
        $res = $this->DB->update($table, $data, $where);
        if ($res === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if (!$res) {
            return false;
        }
        return true;
    }
    
    /*
     * 订单列表查询
     */
    public function order_list($uuid)
    {
        $table = "pl_order AS A, pl_product AS B";
        $select = array(
            'A.O_ORDERIDX AS order_id',
            'A.O_USERID AS uuid',
            'A.O_NICKNAME AS nickname',
            'A.O_TOTALPRICE AS total_price',
            'A.O_PRODUCTNUM AS number',
            'A.O_PAYMETHOD AS pay_method',
            'A.O_PRODUCTID AS product_id',
            'A.O_PRODUCTTYPE AS product_type',
            'A.O_GETGLOD AS get_glod',
            'A.O_FEETYPE AS fee_type',
            'A.O_CALLBACK AS is_callback',
            'A.O_ORDERSTATUS AS order_status',
            'B.P_NAME AS name',
            'B.P_TYPE AS type',
            'B.P_PRICE AS price',
            'B.P_PRICECURRENT AS price_current',
            'B.P_IDX AS p_idx',
        );
        $condition = "A.O_USERID = ".$uuid." AND A.O_PRODUCTID = B.IDX AND  A.STATUS = 0 AND B.STATUS = 0";
        $data['list'] = $this->get_row_array($condition, $select, $table, true);
        if (!$data['list']) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }
    
    /**
     * 查询订单信息
     */
    public function query_order($order_id, $order_status = '')
    {
        $table = "pl_order";
        if ($order_status === '') {
            $condition = "O_ORDERIDX = ".$order_id. " AND STATUS = 0";
        } else {
            $condition = "O_ORDERIDX = ".$order_id. " AND STATUS = 0 AND O_ORDERSTATUS = ".$order_status;
        }
        $select = array(
            'O_ORDERIDX AS order_id',
            'O_USERID AS uuid',
            'O_NICKNAME AS nickname',
            'O_TOTALPRICE AS total_price',
            'O_PRODUCTNUM AS number',
            'O_PAYMETHOD AS pay_method',
            'O_PRODUCTID AS product_id',
            'O_PRODUCTTYPE AS product_type',
            'O_GETGLOD AS get_glod',
            'O_FEETYPE AS fee_type',
            'O_CALLBACK AS is_callback',
            'O_ORDERSTATUS AS order_status',
        );
        $res = $this->get_row_array($condition, $select, $table);
        if (!$res) {
            return false;
        }
        return $res;
    }
    
    /**
     * 查询订单信息通过商品id product_id
     */
    public function get_order_info_by_productid($product_id, $uuid)
    {
        $table = "pl_order";
        $condition = "O_USERID = ".$uuid. " AND O_PRODUCTID = ".$product_id." AND O_ORDERSTATUS = 0 AND STATUS = 0";
        $select = array(
            'O_ORDERIDX AS order_id',
            'O_USERID AS uuid',
            'O_NICKNAME AS nickname',
            'O_TOTALPRICE AS total_price',
            'O_PRODUCTNUM AS number',
            'O_PAYMETHOD AS pay_method',
            'O_PRODUCTID AS product_id',
            'O_PRODUCTTYPE AS product_type',
            'O_FEETYPE AS fee_type',
            'O_CALLBACK AS is_callback',
            'O_ORDERSTATUS AS order_status',
        );
        $res = $this->get_row_array($condition, $select, $table);
        if (!$res) {
            return false;
        }
        return $res;
    }
    
    /**
     * 查询微信是否调用 回调
     */
    public function wx_is_callback()
    {
        
    }
    
    /**
     * 获取充值包
     */
    public function recharge_package () {
        $table = "pl_recharge";
        $condition = "STATUS = 0";
        $select = array(
            'IDX AS id',
            'R_RMB AS rmb',
            'R_GLOD AS gold',
            'R_REWARD AS reward',
            'R_ICON AS icon',
            'STATUS AS status',
        );
        $res = $this->get_row_array($condition, $select, $table, true);
        if (!$res) {
            $this->CI->error_->set_error(Err_Code::ERR_WITHOUT_RECHARGE_INFO);
            return false;
        }
        $ret = array();
        foreach ($res as $k=>$v) {
            $product_id = $this->get_product_id_by_gameid($v['id'], 2);
            if ($product_id) {
                $v['product_id'] = $product_id;
                
                if ($v['icon']) {
                    $v['icon'] = $this->passport->get('game_url').$v['icon'];
                }
                $ret[$k] = $v;
            }
        }
        
        if (!$ret) {
            $this->CI->error_->set_error(Err_Code::ERR_WITHOUT_RECHARGE_INFO);
            return false;
        }
        
        return $ret;
    }
    
    /**
     * 通过充值idx，获取充值包信息
     */
    public function get_recharge_info_by_idx($idx) {
        $table = "pl_recharge";
        $condition = "STATUS = 0 AND IDX = ".$idx;
        $select = array(
            'R_RMB AS rmb',
            'R_GLOD AS glod',
            'STATUS AS status',
        );
        $res = $this->get_row_array($condition, $select, $table);
        if (!$res) {
            $this->CI->error_->set_error(Err_Code::ERR_WITHOUT_RECHARGE_INFO);
            return false;
        }
        return $res;
    }
    
    /**
     * 生成支付宝sign
     * @param type $params
     */
    public function alipay_sign_by_rsa($params)
    {
        $config = $this->CI->passport->get('alipay_config');
        $sign['_input_charset'] = 'utf-8';
        $sign['out_trade_no']   = $params['out_trade_no'];
        $sign['partner']        = $config['partner'];
        $sign['payment_type']   = 1;
        $sign['notify_url']     = $config['notify_url'];
        $sign['service']        = 'mobile.securitypay.pay';
        $sign['subject']        = $params['subject']?$params['subject']:"充值金币";
        $sign['total_fee']      = $params['total_fee'];
        $sign['seller_id']      = $config['seller_email'];
        $sign['body']           = $params['subject']?$params['subject']:"直接充值金币方式充值";
        $arg  = "";
	while (list ($key, $val) = each ($sign)) {
		$arg.=$key."=".'"'.$val.'"'."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
        $sign_  = urlencode($this->rsaSign($arg,$config['private_key']));
        $sign_type  = '&sign_type="RSA"';
        $sign_new   = $arg.'&sign='.'"'.$sign_.'"'.$sign_type;
        return $sign_new;
    }
    
    function rsaSign($data, $private_key) {
        //以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
        $private_key=str_replace("-----BEGIN RSA PRIVATE KEY-----","",$private_key);
	$private_key=str_replace("-----END RSA PRIVATE KEY-----","",$private_key);
	$private_key=str_replace("\n","",$private_key);

	$private_key="-----BEGIN RSA PRIVATE KEY-----".PHP_EOL .wordwrap($private_key, 64, "\n", true). PHP_EOL."-----END RSA PRIVATE KEY-----";
        $res=openssl_get_privatekey($private_key);
        if($res) {
            openssl_sign($data, $sign,$res);
        } else {
            echo "您的私钥格式不正确!"."<br/>"."The format of your private_key is incorrect!";
            exit();
        }
        openssl_free_key($res);
            //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }
}
