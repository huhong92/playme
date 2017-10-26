<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Api_Model extends MY_Model {
    function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->error_->set_error(Err_Code::ERR_OK);
    }
    
    /**
     * 推送消息(绑定推送设备 账号)   已经废弃
     */
    public function push_msg($params)
    {
        $device_id = $params['device_id'];
        $table     = 'PL_BAIDUDEVICEPUSH';
        
        $_device_id = $this->is_pushed($device_id, $params['uuid']); // 判断是否已经推送消息
        if ($_device_id) {
            // 更新推送消息
            $upd_data = array(
                'B_NICKNAME'    => $params['nickname'],
                'B_APPID '      => $params['app_id'],
                'B_TYPE'        => $params['type'],// 设备类型0:iphone1:android
                'B_DEVICETOKEN' => $params['b_devicetoken'],// iphone通知设备标识
                'B_USERID'      => $params['userid'], // 百度通知绑定用户id 
                'B_CHANNELID'   => $params['channel_id'], // 百度通知绑定渠道id
                'ROWTIMEUPDATE' => $this->zeit
            );
            $this->DB->where(array('B_SN'=> $device_id, 'B_USERIDX'=>$params['uuid']));
            $upd = $this->DB->update($table ,$upd_data);
            
            if ($upd === false) {
                log_scribe('trace', 'model', $table.'(update):'.$this->ip.' where : user_id->'.$params['uuid'].' and device_id ->' .$params['device_id']);
                $this->error_->set_error(Err_Code::ERR_DB);
                return false;
            }
            
            return true;
        } else {
            // 插入推送消息
            $ist_data = array(
                'B_USERIDX'     => $params['uuid'],
                'B_NICKNAME'    => $params['nickname'],
                'B_APPID '      => $params['app_id'],
                'B_TYPE'        => $params['type'], // 设备类型 0iphone 1安卓
                'B_SN'         => $params['device_id'], // 设备标示
                'B_DEVICETOKEN' => $params['b_devicetoken'],// iphone通知设备标识
                'B_USERID'      => $params['userid'], // 百度通知绑定用户id 
                'B_CHANNELID'   => $params['channel_id'], // 百度通知绑定渠道id
                'STATUS'        => 0,
                'ROWTIME'       => $this->zeit,
                'ROWTIMEUPDATE' => $this->zeit
            );
            
            $ist = $this->DB->insert($table, $ist_data);
            
            if (!$ist) {
                log_scribe('trace', 'model', $table.'(insert):'.$this->ip);
                $this->error_->set_error(Err_Code::ERR_DB);
                return false;
            }
            
            return true;
        }
    }
    
 
    /**
     * 判断是否推送过消息
     */
    public function is_pushed($device_id, $uuid)
    {
        $table = 'PL_BAIDUDEVICEPUSH';
        $condition = 'B_USERIDX  = '.$uuid.' AND B_SN  = "'.$device_id.'" AND STATUS = 0';
        $select = array('B_SN AS device_id');
        $data = $this->get_row_array($condition, $select, $table);
        
        if($data === false || empty($data)){
            return false;
        }
        return $data[0]['device_id'];
    }
    
    //获取推送消息的绑定关系
    function get_user_bind_push($uuid){
        $table = 'pl_userdevice';
        $condition = 'D_USERIDX  = '.$uuid.' AND STATUS = 0';
        $select = array(
            'D_USERIDX as uuid',
            'D_TYPE as so',
            'D_SN as device_id',
        );
        $data = $this->get_row_array($condition, $select, $table);
        if($data === false || empty($data)){
            return false;
        }
        return $data;
    }
    
    //查询是否有推送任务
    function get_user_push_push_task($uuid,$push_type){
        $table = 'pl_pushusertask';
        $condition = "P_USERIDX = " . $uuid ." AND P_PUSHTASKNO = '".$push_type."'";
        $select = array(
            'IDX AS id',
        );
        $ret = $this->get_row_array($condition, $select, $table);
        if ($ret === false) {
            return false;
        }
        return $ret;
    }
    
    //插入推送任务表(完成表)
    function inser_user_push_task($uuid,$nickname,$push_type){
        $data = array(
            'P_USERIDX'             =>  $uuid,
            'P_NICKNAME'            =>  $nickname,
            'P_PUSHTASKNO'          =>  $push_type,
            'STATUS'                =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_pushusertask', $data);
        if($query === false){
            log_scribe('trace', 'model', 'inser_user_push_task:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::INSERT_ERR_PUSH_TASK_FAIL);
            return false;
        }
        return true;
    }
    
    //插入消息发送表
    function inser_user_send_msg($to_uuid,$to_nickname,$bind_info,$push_type,$send_time = 0,$nickname = ''){
        $send_time_type = 0;
        $send_time_val = $this->zeit;
        if($send_time != 0) {
            $send_time_type = 1;
            $send_time_val = $send_time;
        }
        $msg_info = $this->passport->get($push_type);
        if($push_type == PUSH_NO_MAKE_GAME){
            $msg_info['content'] = str_replace("[integral]",MAKING_GAME_TOTAL_INTEGRAL,$msg_info['content']);
        } else if($push_type == PUSH_MAKING_GAME_PLAYNUM){
            $msg_info['content'] = str_replace("[play_num]",MAKING_GAME_PLAY_NUM,$msg_info['content']);
        }else if($push_type == PUSH_TOP_GAME_SCORE){
            $msg_info['content'] = str_replace("[nickname]",$nickname,$msg_info['content']);
        }
        $data = array(
            'M_TITLE'           =>  $msg_info['title'],
            'M_TXT'             =>  $msg_info['content'],
            'M_SN'              =>  $bind_info['device_id'],
            'M_DEVICETOKEN'     =>  '',
            'M_USERID'          =>  '',
            'M_CHANNELID'       =>  '',
            'M_TOPIC'           => '',
            'M_ALL'             =>  0,
            'M_USERIDX'         =>  $to_uuid,
            'M_NICKNAME'        =>  $to_nickname,
            'M_PHONETYPE'       =>  $bind_info['so'],
            'M_SENDTIME'        =>  $send_time_val,
            'M_SENDTIMETYPE'    =>  $send_time_type,
            'STATUS'            =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        
        $query = $this->DB->insert('pl_messagesend', $data);
        if($query === false){
            log_scribe('trace', 'model', 'inser_user_send_msg:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::INSERT_ERR_SEND_MSG_FAIL);
            return false;
        }
        return true;
    }
    
    /**
     * 信箱   客户端显示信箱时间是  rowtime
     */
    public function mailbox($params)
    {
        $table = "pl_mailbox AS A, pl_mailstatus AS B";
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $hy_mail = 0;
        //  1.获取当前用户注册时间
        $create_time = $this->utility->get_user_info($params['uuid'],'create_time'); // 时间戳        
        //  2.查询当前用户，所有已读，未读的邮件列表（除去被用户删除的了邮件）
        $sql = "SELECT @is_read := 0 as is_read, IDX AS id,M_NAME AS name,M_INFO AS content,M_ICON AS pic ,M_SENDER AS sender,UNIX_TIMESTAMP(M_DATE) AS datetime,UNIX_TIMESTAMP(ROWTIMEUPDATE) AS send_time  FROM pl_mailbox WHERE STATUS = 0 AND UNIX_TIMESTAMP(ROWTIMEUPDATE) >= ".$create_time." AND  IDX NOT IN (SELECT M_MAILID FROM pl_mailstatus WHERE STATUS != 0 AND M_USERID = ".$params['uuid']." ) order by ROWTIMEUPDATE desc";
        $query = $this->DB->query($sql);
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if ($query->num_rows() > 0) {
            $data['list'] = $query->result_array(); 
        }
        
        if (empty($data['list'])) {
            // 获取一条欢迎邮件
            $res = $this->get_hy_mail_info($params['uuid']);
            if ($res) {
                $res['send_time'] = $create_time;
                $data['list'][] = $res;
                $hy_mail = 1;
            }
        } else {
            // 判断当前列表中，是否存在欢迎邮件
            foreach ($data['list'] as $k=>$v) {
                if ((int)$v['id'] === 1) { // 存在欢迎邮件
                    $data['list'][$k]['send_time'] = $create_time;
                    $hy_mail = 1;
                }
            }
        }
        if (!$hy_mail) {
            $res = $this->get_hy_mail_info($params['uuid']);
            if ($res) {
                $res['send_time'] = $create_time;
                $data['list'][] = $res;
            }
        }
        if (empty($data['list']) && !is_array($data['list'])) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        // 3.获取用户 已读 且未删除
        $condition  = "STATUS = 0 AND M_USERID = ".$params['uuid'];
        $select     = array('M_MAILID AS mail_id');
        $t = 'pl_mailstatus';
        $read_mail = $this->get_row_array($condition, $select, $t, true);
        if ($read_mail && is_array($read_mail)) { // 存在已读邮件
            foreach ($read_mail as $k1=>$v1) {
                foreach ($data['list'] as $k=>$v) {
                    if ($v1['mail_id'] == $v['id']) {
                        $data['list'][$k]['is_read'] = 1; // 已读
                    }
                }
            }
        }
        $data['pagecount'] = ceil(count($data['list']) / $per_page);
        $count_all = count(array_slice($data['list'], $offset));
        $data['list'] = array_slice($data['list'], $offset, $per_page);
        
        if ($data['pagecount']<=0) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }
    
    /**
     * 判断信箱是否已读
     */
    public function read_status($id, $uuid)
    {
        $table = "pl_mailstatus";
        $condition = array(
            'STATUS'    => 0,
            'M_USERID'  => $uuid,
            'M_MAILID'  => $id,
        );
        $select = array(
            'M_MAILID AS mail_id',
        );
        $res = $this->get_row_array($condition, $select, $table, false);
        
        if (!$res['mail_id']) {
            return false; // 未读
        }
        return $res['mail_id']; // 已读
    }
    
    /**
     * 更新信箱已读未读状态
     */
    public function update_read_status($mail_id, $uuid, $nickname) // 邮箱id
    {
        if (!$mail_id) {
            return false;
        }
        $table  = "pl_mailstatus";
        $data   = array(
            'M_MAILID'      => $mail_id,
            'M_USERID'      => $uuid,
            'M_NICKNAME'    => $nickname,
            'STATUS'        => 0,
            'ROWTIME'       => $this->zeit,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $res = $this->DB->insert($table, $data);
        
        if ($res === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 删除信箱
     */
    public function delete_mailbox($id,$uuid)
    {
        if (!$id) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            return false;
        }
        
        // 判断信箱是否已删除
        $is_del = $this->is_delete_mailbox($id, $uuid); 
        if ($is_del) { // 已经删除过了
            $this->error_->set_error(Err_Code::ERR_OK);
            return true;
        }
        $ret = $this->cando_delete_mailbox($id, $uuid);
        if (!$ret) {
            $this->error_->set_error(Err_Code::ERR_DELETE_FAIL_BY_NOT_READ); // 未读，不能删除
            return false;
        }
        $table = "pl_mailstatus";
        $data = array(
            'status'        =>1,
            'ROWTIMEUPDATE' =>$this->zeit,
        );
        $condition = array('M_MAILID'=>$id,'M_USERID'=>$uuid);
        $res = $this->DB->update($table, $data, $condition);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_MAILBOX_DELETE_FAIL); // 删除失败
            return false;
        }
        return $res;
    }
    
    /**
     * 判断信箱是否可删除(是否已读)
     */
    public function cando_delete_mailbox($id, $uuid)
    {
        $table = "pl_mailstatus";
        $select = array(
            'IDX AS id',
        );
        $condition = array(
            'STATUS'    => 0,
            'M_MAILID'  => $id,
            'M_USERID'  => $uuid,
        );
        $res = $this->get_row_array($condition, $select, $table, false);
        if (!$res) {
            return false;
        }
        return $res['id'];
    }
    /**
     * 判断信箱是否已经删除
     */
    public function is_delete_mailbox($id, $uuid)
    {
        if (!$id) {
            return false;
        }
        $condition = array(
            'STATUS'    => 1,
            'M_MAILID'  => $id,
            'M_USERID'  => $uuid,
        );
        $table = "pl_mailstatus";
        $select = array('IDX AS id');
        $res = $this->get_row_array($condition, $select, $table, false);
        if (!$res) {
            return false;
        }
        return $res['id']; // 邮箱已经删除
    }
    
    /**
     * 获取欢迎邮箱信息
     */
    public function get_hy_mail_info($uuid)
    {
        $condition = array(
            'M_MAILID'  => 1,
            'M_USERID'  => $uuid,
        );
        $table = "pl_mailstatus";
        $select = array('STATUS AS status');
        $res = $this->get_row_array($condition, $select, $table, false);
        // 获取欢迎信箱信息
        $condition1 = array(
            'IDX'  => 1,
        );
        $table1 = "pl_mailbox";
        $select1 = array('IDX AS id',
            'M_NAME AS name',
            'M_INFO AS content',
            'M_ICON AS pic',
            'M_SENDER AS sender',
            'UNIX_TIMESTAMP(M_DATE) AS datetime',
            'UNIX_TIMESTAMP(ROWTIME) AS send_time',
            );
        $res1 = $this->get_row_array($condition1, $select1, $table1, false);
        
        if (!$res) { // 邮箱未读
            $res1['is_read'] = 0;
        }
        
        if ((int)$res['status'] === 1) { // 欢迎邮件被删除
            return false;
           
        } else if($res['status'] === '0'){ // 欢饮邮箱被删除
            $res1['is_read'] = 1;
        }
        
        return $res1;
    }
    
    /**
     * 获取游客身份状态 0：打开 1：关闭
     */
    public function get_visitor_status()
    {
        $condition = array(
            'STATUS'  => 0,
        );
        $table = "pl_visitorstatus";
        $select = array('S_STATUS AS visitor_status');
        $res = $this->get_row_array($condition, $select, $table, false);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_GET_VISITOR_STATUS_FAIL); // 删除失败
            return false;
        }
        return $res;
    }
    
    /**
     * 按钮统计
     * $type ： B_TASK 任务统计
     *          B_GAMECENTER 游戏中心统计
     *          B_MYINFO 我的信息按钮统计
     *          B_ACTIVITY 活动室统计
     *          B_SEARCH 搜索统计
     *          B_RANKING 排行榜统计
     * $time :  2015-08-03 00:00:00(时间格式：统计访问当天的日期格式)
     */
    public function button_statistics($type, $time)
    {
        $table = 'pl_buttonstatistics';
        $sql = "SELECT IDX, ".$type." FROM ".$table." WHERE B_DATATIME = '".$time."' AND STATUS = 0";
        $query = $this->DB->query($sql);
        if($query->num_rows() > 0) {
            $ret = $query->result_array();
            $idx = $ret[0]['IDX'];
            $type_val = $ret[0][$type];
            $this->DB->set($type, $type_val+1);
            $this->DB->set('ROWTIMEUPDATE', $this->zeit);
            $this->DB->where('IDX', $idx);
            $update = $this->DB->update("pl_buttonstatistics");
            if($update === false){
                log_scribe('trace', 'model', 'update_buttonstatistics:'.$this->ip.' where : IDX->'.$idx.' 更新按钮统计失败');
                return false;
            }
            return true;
        } else {
            // 插入按钮统计表
            $data = array(
                'STATUS'        =>  0,
                'ROWTIME'       =>  $this->zeit,
                'ROWTIMEUPDATE' =>  $this->zeit,
                'B_DATATIME'    =>  $time,
            );
            $data['B_GAMECENTER']   = 0;
            $data['B_MYINFO']   = 0;
            $data['B_ACTIVITY']   = 0;
            $data['B_SEARCH']   = 0;
            $data['B_RANKING']   = 0;
            if ($type == 'B_GAMECENTER') {
                $data['B_TASK']   = 1;
                $data['B_GAMECENTER']   = 1;
            } else if($type == 'B_MYINFO'){
                $data['B_TASK']   = 2;
                $data['B_MYINFO']   = 1;
            } elseif($type == 'B_ACTIVITY') {
                $data['B_TASK']   = 3;
                $data['B_ACTIVITY']   = 1;
            }elseif($type == 'B_SEARCH') {
                $data['B_TASK']   = 4;
                $data['B_SEARCH']   = 1;
            }elseif($type == 'B_RANKING') {
                $data['B_TASK']   = 5;
                $data['B_RANKING']   = 1;
            }
            $query = $this->DB->insert($table, $data);
            if($query === false){
                log_scribe('trace', 'model', 'pl_buttonstatistics(insert):'.$this->ip.' 插入按钮统计失败');
                return false;
            }
            return true;
        }
    }
    
}

