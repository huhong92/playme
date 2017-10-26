<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Social_Model extends MY_Model {

    public function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->error_->set_error(Err_Code::ERR_OK);
    }
    
    /**
     * 获取好有ID
     * return fids_list
     */
    public function friend_list($params)
    {
        $uuid       = $params['uuid'];
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        
        $condition          = "(S_USERID = ".$uuid." OR S_FRIENDID = ".$uuid.") AND S_ISFRIEND = 1 AND status = 0 ORDER BY ROWTIME DESC limit " . $offset . "," . $per_page;
        $count_condition    = "(S_USERID = ".$uuid." OR S_FRIENDID = ".$uuid.") AND S_ISFRIEND = 1 AND status = 0";
        $select             = array('S_USERID AS user_id', 'S_FRIENDID AS friend_id');
        $table              = "pl_socialcontact";
        
        $data['user_idx_list'] = $this->get_row_array($condition, $select, $table, true);
        
        if ($data['user_idx_list'] === false || empty($data['user_idx_list'])) {
            return false;
        }
        
        $this->DB->where($count_condition);
        $count = $this->DB->count_all_results($table);
        $data['pagecount'] = (int)ceil($count / $per_page);
        
        if (!$data['pagecount']) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * 将通讯录的号码，添加到好友表，或者，非好友表（表述关系的）
     */
    public function load_friend($params)
    {
        $uuid       = $params['uuid']; 
        $mobiles    = $params['mobiles']; // 未注册的好友
        $fids       = $params['fids']; // 已注册的好友
        
        if ($mobiles && is_array($mobiles)) {
            foreach ($mobiles as $v) {
                $res = $this->insert_unsocialcontact($uuid , $v);
                if (!$res) {
                    $this->error_->set_error(Err_Code::ERR_FRIEND_LOAD_FAIL);
                    return false;
                }
            }
        }
        
        if ($fids && is_array($fids)) {
            foreach ($fids as $val) {
                $ret = $this->insert_socialcontact($uuid, $val);
                if (!$ret) {
                    $this->error_->set_error(Err_Code::ERR_FRIEND_LOAD_FAIL);
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * 将通讯录号码，插入到非好友关系表中，记录关系
     */
    public function insert_unsocialcontact($uuid , $mobile)
    {
        // 插入pl_unsocialcontact表之前， 检查是否已存在
        $condition = "U_USERID = ".$uuid." AND U_FRIENDMOBILE = '".$mobile."' AND STATUS = 0";
        $select = array('IDX');
        $table = "pl_unsocialcontact";
        $res = $this->get_row_array($condition, $select, $table);
        if ($res) {
            return true;
        }
        
        $data = array(
            'U_USERID'          =>  $uuid,
            'U_FRIENDMOBILE'    =>  $mobile,
            'STATUS'            =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_unsocialcontact', $data);
        if($query === false){
            log_scribe('trace', 'model', 'pl_unsocialcontact:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_LINKMAN_RECORD_FAIL);
            return false;
        }
        return true;
    }
    
    /**
     * 将通讯录号码，插入到好友关系表中，记录关系
     */
    public function insert_socialcontact($uuid , $friend_id)
    {
        // 排除，从通讯录中获取的是自己的手机号
        if ($uuid == $friend_id) {
            return true;
        }
        // 插入到pl_socialcontact表之前， 先检查要插入的数据 是否已存在
        $condition = "(S_USERID = ".$uuid." AND S_FRIENDID = ".$friend_id.") OR (S_USERID = ".$friend_id." AND S_FRIENDID = ".$uuid.") AND  STATUS = 0";
        $select = array('IDX');
        $table = "pl_socialcontact";
        $res = $this->get_row_array($condition, $select, $table);
        if ($res) {
            return true;
        }
        
        $data = array(
            'S_USERID'      =>  $uuid,
            'S_FRIENDID'    =>  $friend_id,
            'S_ISFRIEND'    =>  1,
            'STATUS'        =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_socialcontact', $data);
        if($query === false){
            log_scribe('trace', 'model', 'pl_socialcontact:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_FRIEND_LOAD_FAIL);
            return false;
        }
        return true;
    }
    
}
