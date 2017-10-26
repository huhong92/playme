<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Activity_model extends MY_Model {
    function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->CI->error_->set_error(Err_Code::ERR_OK);
        $this->select_array = array(
            'IDX AS id',
            'A_NAME AS name',
            'A_ICON AS icon',
            'A_TOPIMG AS banner',
            'A_PCIMG AS pc_img',
            'A_GAMEID AS game_id',
            'A_GAMETYPE AS game_type',
            'A_INFO AS intro',
            'A_RULE AS rule',
            'A_REWARD AS reward',
            'A_ORDERBY AS order_id',
            'UNIX_TIMESTAMP(A_STARTDATE) AS start_time',
            'UNIX_TIMESTAMP(A_ENDDATETIME) AS end_time',
            'UNIX_TIMESTAMP(ROWTIMEUPDATE) AS create_time',
        );
    }
    //获活动列表
    function get_activity_list($params)
    {
        $table = 'pl_active';
        if($params['order_type'] == 1){
            $_orderby = 'A_ORDERBY ASC';
        } elseif($params['order_type'] == 2) {
            $_orderby = 'A_STARTDATE DESC';
        } else {
            $_orderby = 'ROWTIME DESC';
        }
        $condition = "A_TOP = ".$params['type']." AND STATUS = 0 ORDER BY ".$_orderby." limit ".$params['recordindex'].",".$params['pagesize'];
        $activity_list = $this->get_row_array($condition, $this->select_array, $table, true);
        if($activity_list === false) {
            log_scribe('trace', 'model', 'get_activity_list :'.$this->ip.'  where :'.$condition);
            if($params['type'] == 1){
                $this->CI->error_->set_error(Err_Code::ERR_GET_BANNER_LIST_FAIL);
            } else{
                $this->CI->error_->set_error(Err_Code::ERR_GET_LACTIVETY_LIST_FAIL);
            }
        }
        $count_condition = "A_TOP = ".$params['type']." AND STATUS = 0";
        $pagecount = $this->get_data_num($count_condition, $table);
        if($pagecount === false){
            if($params['type'] == 1){
                $this->CI->error_->set_error(Err_Code::ERR_GET_BANNER_COUNT_FAIL);
            } else{
                $this->CI->error_->set_error(Err_Code::ERR_GET_ACTIVITY_COUNT_FAIL);
            }
            return false;
        }
        $data['list'] = $activity_list;
        $data['pagecount'] = ceil($pagecount/$params['pagesize']);
        return $data;
    }
    
    //获活动详情
    function get_activity_detail($id){
        $table = 'pl_active';
        $condition = "IDX = ".$id." AND STATUS = 0";
        $ret = $this->get_row_array($condition, $this->select_array, $table);
        if($ret === false) {
            log_scribe('trace', 'model', 'get_activity_detail :'.$this->ip.'  where :'.$condition);
            $this->CI->error_->set_error(Err_Code::ERR_LACTIVETY_DETAIL_NO_DATA);
            return false;
        }
        return $ret;
    }
}
