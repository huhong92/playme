<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class MY_Model extends CI_Model {

    protected $CI;
    protected $DB;
    protected $zeit;
    static private $DB_INSTANCE = array();

    function __construct($init_db = true) {
        parent::__construct();
        $this->CI = & get_instance();
        $this->zeit = date('Y-m-d H:i:s', time());
        $this->ip = $this->CI->input->ip_address();
        if ($init_db) {
            $this->DB = $this->get_db_instance('playmedb');
        }
    }

    /**
     * 获取表字段的自增sequence
     */
    function get_next_id($table_field) {
        $sql = "get_next_id('{$table_field}')";
        $query = $this->DB->query($sql);
        $res = $query->result_array();

        reset($res[0]);
        return current($res[0]);
    }

    /**
     * 设置缓存
     */
    function set_data($service, $value, $args) {
        $this->CI->set_dbdata($service, $value, $args);
        return $value;
    }

    /**
     * 读取缓存
     */
    function get_data($service = '', $args = '') {
        return $this->CI->get_dbdata($service, $args);
    }

    /**
     * 记录数据库错误日志
     */
    function log_error($source = '', $set_err = true) {
        $err_info = "ERR_SOURCE:" . $source . "  ERR_NUM:" . $this->DB->_error_number() . "   ERR_MSG:" . $this->DB->_error_message();
    }

    /**
     * 获取数据库对象
     */
    private function get_db_instance($database) {
        if (empty(self::$DB_INSTANCE[$database])) {
            self::$DB_INSTANCE[$database] = $this->CI->load->database($database, true);
        }
        return self::$DB_INSTANCE[$database];
    }

    /**
     * 开始事务
     */
    function start($xa = true) {
        $this->DB->trans_start();
    }

    /**
     * 事务回滚并返回报错
     */
    function error() {
        $this->DB->trans_rollback();
        return false;
    }

    /**
     * 事务提交并返回成功
     */
    function success() {
        $this->DB->trans_complete();
        if ($this->DB->trans_status() === false) {
            $this->log_error(__method__);
            return false;
        }
        return true;
    }

    //$batch true=> 多条数据  false=>单条数据
    function get_row_array($condition, $select, $table, $batch = FALSE) {
        
        if ($condition == '' || !is_array($select) || $table == '') {
            
            $this->CI->error_->set_error(Err_Code::ERR_PARA);
            return false;
        }
        
        $this->DB->select($select);
        
        $this->DB->where($condition);
        if(!$batch){
            $this->DB->limit(1);
        }
        $query = $this->DB->get($table);

        // 记录数据库错误日志
        if ($query === false) {
            if(is_array($condition)) $condition = http_build_query ($condition);
            log_scribe('trace','model','get_data_fail'. $this->ip .': condition：'.$condition);
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $rst = array();
        if ($query->num_rows() > 0) {
            $ret = $query->result_array();
            if (!$batch) {
                $rst =  $ret[0];
            } else {
                $rst = $ret;
            }
        }
        
        return $rst;
    }

    //获取排序数据 默认降序
    function get_order_row_array($condition, $select, $table, $order_name, $order_type = 'DESC') {
        if ($condition == '' || !is_array($select) || $table == '') {
            $this->CI->error_->set_error(Err_Code::ERR_PARA);
            return false;
        }
        $this->DB->select($select);
        $this->DB->where($condition);
        $this->DB->order_by($order_name, $order_type);
        $query = $this->DB->get($table);
        // 记录数据库错误日志
        if ($query === false) {
            $this->log_error(__method__);
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $ret = array();
        if ($query->num_rows() > 0) {
            $ret = $query->result_array();
        }
        return $ret;
    }

    //获取信息的总记录数
    function get_data_num($condition, $table) {
        $this->DB->from($table);
        $this->DB->where($condition);
        $this->DB->limit(1);
        $query = $this->DB->count_all_results();
        // 记录数据库错误日志
        if ($query === false) {
            $text = (is_array($condition) && $condition!='') ? http_build_query($condition):$condition;
            log_scribe('trace', 'model', 'get_data_num:'.$this->ip.' where :'.$text);
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return $query;
    }
    
    //left join 语句封装
    function get_composite_row_array($select,$condition,$join_condition,$tb_a,$tb_b,$batch = false){
        if ($condition == '' || !is_array($select) || $join_condition == '' || $tb_a == '' || $tb_b == '') {
            $this->CI->error_->set_error(Err_Code::ERR_PARA);
            return false;
        }
        $select_string = implode(',', $select);
        $_limit = '';
        if($batch === false) {
            $_limit = " LIMIT 1";
        }
        $sql = "SELECT ".$select_string." FROM ".$tb_a." LEFT JOIN ".$tb_b." ON ".$join_condition." WHERE ".$condition.$_limit;
        $query = $this->DB->query($sql);
        // 记录数据库错误日志
        if ($query === false) {
            $this->log_error(__method__);
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $ret = array();
        if ($query->num_rows() > 0) {
            $ret = $query->result_array();
            if($batch === false) {
                $ret = $ret[0];
            }
        }
        return $ret;
    }
    
    /**
     * 批量更新
     * @param type $table
     * @param type $data
     * @param type $field
     * @return type
     */
    public function update_batch($data,$field,$table)
    {
        return $this->DB->update_batch($table, $data, $field);
    }
    
    /**
     * 批量插入
     * @param type $table
     * @param type $pairs
     * @return type
     */
    public function insert_batch($pairs,$table)
    {
        return $this->DB->insert_batch($table,$pairs);
    }

}
