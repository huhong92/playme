<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class User_model extends MY_Model {
    function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //同步app应用
    function app_syndata($params){
        $table = 'pl_appconfig';
        $select = array(
            'IDX AS app_id',
            'A_NAME AS name',
            'A_UPDATEINFO AS content',
            'A_PHONETYPE AS os',
            'A_MINIVER AS min_version',
            'A_VER AS max_version',
            'A_UPDATEURL AS download_url',
            'A_DATAVER AS data_version',
        );
        $condition['IDX'] = $params['app_id'];
        $condition['A_PHONETYPE']   = $params['os'];
        $condition['STATUS'] = 0;
        $data = $this->get_row_array($condition, $select,$table);
        if($data === false || empty($data)) {
            log_scribe('trace','model','app_syndata'. $this->ip .': condition：'.http_build_query($condition));
            $this->CI->error_->set_error(Err_Code::ERR_APP_CONFIG_NO_DATA);
        }
        return $data;
    }
    
    //检查账号是否已经存在
    function chk_user_account($user_id,$login_type,$pwd = '') {
        $select = array('U_USERIDX as uuid');
        $condition['U_ACCOUNTID'] = $user_id;
        $condition['U_TYPE'] = $login_type;
        $condition['STATUS'] = 0;
        if($pwd != ''){
            $condition['U_PASSWORD'] = md5($pwd);
        }
        $data = $this->get_row_array($condition, $select, 'pl_userlogin');
        if($data === false || empty($data)) { 
            return false;
        }
        return $data['uuid'];
    }
    
    //检查账号是否已经存在
    function chk_user_account_by_channelidx($user_id,$channel_id,$pwd = '') {
        $select = array('U_USERIDX as uuid');
        $condition['U_ACCOUNTID']  = $user_id;
        $condition['U_CHANNELIDX'] = $channel_id;
        $condition['STATUS'] = 0;
        if($pwd != ''){
            $condition['U_PASSWORD'] = md5($pwd);
        }
        $data = $this->get_row_array($condition, $select, 'pl_userlogin');
        if($data === false || empty($data)) { 
            return false;
        }
        return $data['uuid'];
    }
    
    //检查昵称是否已经存在
    function chk_user_nickname($nickname) {
        $select = array('IDX as uuid');
        $condition['U_NICKNAME'] = $nickname;
        $condition['STATUS'] = 0;
        $data = $this->get_row_array($condition, $select, 'pl_user');
        if($data === false || empty($data)) { 
            return false;
        }
        return $data['uuid'];
    }
    
    /***第三方登录（用户注册）
     * 插入用户信息表
     */
    function insert_user_info($params){
        if (!$params['gold']) {
            $params['gold'] = 0;
        }
        //创建新的用户
        $data = array(
            'U_NICKNAME'        =>  $params['nickname'],
            'U_ICON'            =>  $params['image'],
            'U_TOTALPOINT'      =>  1000,
            'U_POINT'           =>  1000,
            'U_GOLD'            =>  $params['gold'],
            'U_MOBILEPHONE'     =>  $params['mobile'],
            'U_LOGINSTATUS'     =>  0,
            'U_MESSAGESTATUS'   =>  0,
            'U_SEX'             =>  $params['gender'],
            'U_PROVINCE'        =>  $params['province'],
            'STATUS'            =>  0
        );
        $this->DB->set('U_LASTLOGINTIME', $this->zeit);
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_user', $data);
        if($query === false){
            log_scribe('trace', 'model', 'insert_user_info:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_USER_INFO_FAIL);
            return FALSE;
        }
        $uuid = $this->DB->insert_id();
        return $uuid;
    }
    
    /***第三方登录（用户注册）
     * 插入用户登入表
     */
    function insert_user_login($params){
        $data = array(
            'U_USERIDX'     =>  $params['uuid'],
            'U_TYPE'        =>  $params['login_type'],
            'U_ACCOUNTID'   =>  $params['user_id'],
            'U_PASSWORD'    =>  "",
            'U_ACCOUNTNAME' =>  $params['nickname'],
            'STATUS'        =>  0,
            'U_CHANNELIDX'  =>  $params['channel_id'], //// 渠道ID
            'U_APPID'       =>  $params['app_id'],
        );
        if($params['login_type'] == 3) {
            $data['U_PASSWORD'] = md5($params['password']);
        }
        
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_userlogin', $data);
        if($query === false){
            log_scribe('trace', 'model', 'insert_user_login:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_USER_INFO_FAIL);
            return FALSE;
        }
        return true;
    }
    
    //记录用户登录设备
    function record_user_device($params){
        $table = 'pl_userdevice';
        $sql = "SELECT IDX FROM ".$table." WHERE D_USERIDX = ".$params['uuid'];
        $query = $this->DB->query($sql);
        if (!$params['source']) {
            $params['source'] = 20; //  其他下载源
        }
        if($query->num_rows() > 0) {
            //更新用户设备表
            $this->DB->set('D_TYPE', $params['os']);
            $this->DB->set('D_SOURCE', $params['source']); // 记录app下载来源
            $this->DB->set('D_SN', $params['device_id']);
            $this->DB->set('D_APPID', $params['app_id']);
            $this->DB->set('ROWTIMEUPDATE', $this->zeit);
            $this->DB->where('D_USERIDX', $params['uuid']);
            $query = $this->DB->update($table);
            // 记录数据库错误日志
            if($query === false){
                log_scribe('trace', 'model', 'record_user_device(update):'.$this->ip.' where : D_USERIDX->'.$params['uuid']);
                $this->CI->error_->set_error(Err_Code::ERR_INSERT_USER_DEVICE_FAIL);
                return false;
            }
            return true;
        } else {
            $data = array(
                'D_USERIDX'     =>  $params['uuid'],
                'D_NICKNAME'    =>  $params['nickname'],
                'D_TYPE'        =>  $params['os'],
                'D_SOURCE'      =>  $params['source'], // 记录app下载来源
                'D_SN'          =>  $params['device_id'],
                'D_APPID'       =>  $params['app_id'],
                'STATUS'        =>  0
            );
            $this->DB->set('ROWTIME', $this->zeit);
            $this->DB->set('ROWTIMEUPDATE', $this->zeit);
            $query = $this->DB->insert($table, $data);
            if($query === false){
                log_scribe('trace', 'model', 'record_user_device(insert):'.$this->ip.' where : D_USERIDX->'.$params['uuid']);
                $this->CI->error_->set_error(Err_Code::ERR_INSERT_USER_DEVICE_FAIL);
                return false;
            }
            return true;
        }
    }
    
    //记录登录日志
    function record_user_login_history($params){
        $device_info = 'PHONETYPE:'.$params['os'].',VERSION:'.$params['version'].',DEVICEID:'.$params['device_id'];
        if (!$params['source']) {
            $params['source'] = 20; // 其他
        }
        
        $data = array(
            'L_USERIDX'     =>  $params['uuid'],
            'L_NICKNAME'    =>  $params['nickname'],
            'L_DEVICEINFO'  =>  $device_info,
            'L_TYPE'        =>  $params['login_type'],
            'L_SOURCE'      =>  $params['source'],
            'L_APPID'       =>  $params['app_id'],
            'L_IP'          =>  $this->ip,
            'STATUS'        =>  0
        );
        
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_loginlog', $data);
        if($query === false){
            log_scribe('trace', 'model', 'record_user_login_history:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_USER_LOGINLOG_FAIL);
            return false;
        }
        return true;
    }
    
    //获取用户信息
    function get_user_info_by_uuid($uuid)
    {
        $tb_a = 'pl_user a';
        $tb_b = 'pl_userlogin b';
        $select = array(
            'a.IDX AS uuid',
            'a.U_NICKNAME AS nickname',
            'a.U_ICON AS image',
            'a.U_TOTALPOINT AS total_integral',
            'a.U_POINT AS integral',
            'a.U_GOLD AS coin',
            'a.U_MOBILEPHONE AS mobile',
            'a.U_LOGINSTATUS AS status',
            'a.U_MESSAGESTATUS AS comment_status',
            'a.U_SEX AS gender',
            'a.U_PROVINCE AS province',
            'b.U_TYPE AS login_type',
            'b.U_ACCOUNTID AS user_id',
            'b.U_CHANNELIDX AS channel_id',
            'b.U_APPID AS app_id',
            'UNIX_TIMESTAMP(a.U_LASTLOGINTIME) AS last_login_time',
            'UNIX_TIMESTAMP(a.ROWTIME) AS create_time',
        );
        $join_conditon = "b.U_USERIDX = a.IDX";
        $condition = "a.IDX = ".$uuid ." AND a.STATUS = 0 AND b.STATUS = 0";
        $data = $this->get_composite_row_array($select,$condition,$join_conditon,$tb_a,$tb_b);
        if($data === false || empty($data)){
            $this->CI->error_->set_error(Err_Code::ERR_USER_INFO_NO_DATA);
            return false;
        }
        $mod    = $data['total_integral']%1000;
        $data['grade'] = $this->utility->user_grade($data['total_integral']);
        
        $data['upgrade_integral']   = 1000; // 升级所需的积分值
        if ($data['total_integral'] >= 1000*99) {
            $mod    = 1000;
        }
        $data['current_integral']   = $mod; // 当前等级升级 积分值
        if (!empty($data['image'])) {
            $pos  =  strpos ( $data['image'] ,  "http://" );
            if ($pos === false) {
                $data['image'] = $this->passport->get('game_url').$data['image'];
            }
        }
        return $data;
    }
    
    
    //获取用户信息
    function get_user_info_by_mobile($mobile)
    {
        $tb_a = 'pl_user a';
        $tb_b = 'pl_userlogin b';
        $select = array(
            'a.IDX AS uuid',
            'a.U_NICKNAME AS nickname',
            'a.U_ICON AS image',
            'a.U_TOTALPOINT AS total_integral',
            'a.U_POINT AS integral',
            'a.U_GOLD AS coin',
            'a.U_MOBILEPHONE AS mobile',
            'a.U_LOGINSTATUS AS status',
            'a.U_MESSAGESTATUS AS comment_status',
            'a.U_SEX AS gender',
            'a.U_PROVINCE AS province',
            'b.U_TYPE AS channel',
            'b.U_ACCOUNTID AS user_id',
            'UNIX_TIMESTAMP(a.U_LASTLOGINTIME) AS last_login_time',
            'UNIX_TIMESTAMP(a.ROWTIME) AS create_time',
        );
        $join_conditon = "b.U_USERIDX = a.IDX";
        $condition = "a.U_MOBILEPHONE = '".$mobile ."' AND a.STATUS = 0 AND b.STATUS = 0";
        $data_list = $this->get_composite_row_array($select,$condition,$join_conditon,$tb_a,$tb_b, true); // 通过手机号查询，可能查出，手机号注册和微信绑定手机号，2个账号
        
        if($data_list === false || empty($data_list)){
            return false;
        }
        if (is_array($data_list)) {
            foreach ($data_list as $k=>$v) {
                $data_list[$k]['grade'] = ceil($data_list[$k]['total_integral']/1000);
                if($data_list[$k]['grade'] == 0) {
                    $data_list[$k]['grade'] = 1;
                } elseif ($data_list[$k]['grade'] > 99){
                    $data_list[$k]['grade'] = 99;
                }
                if (!empty($data_list[$k]['image'])) {
                    $pos  =  strpos ( $data_list[$k]['image'] ,  "http://" );
                    if ($pos === false) {
                        $data_list[$k]['image'] = $this->passport->get('game_url').$data_list[$k]['image'];
                    }
                }
            }
        }
        return $data_list;
    }
    
    //用户反馈
    function record_feedback($params){
        $device_info = 'PHONETYPE:'.$params['os'].',VERSION:'.$params['version'].',DEVICEID:'.$params['devide_id'];
        $data = array(
            'F_USERIDX'     =>  $params['uuid'],
            'F_NICKNAME'    =>  $params['nickname'],
            'F_APPINFO'     =>  $device_info,
            'F_INFO'        =>  $params['content'],
            'F_CONTACT'     =>  $params['contact_info'],
            'F_IP'          =>  $this->ip,
            'STATUS'        =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_appfeedback', $data);
        if($query === false){
            log_scribe('trace', 'model', 'record_feedback:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_USER_FEEDBACK_FAIL);
            return false;
        }
        return true;
    }
    
    /*/**修改用户信息
     * $fields = array(
     * U_LASTLOGINTIME => ",//最后登录时间
     * U_TOTALPOINT => ",//总积分
     * U_POINT => ",//可用积分
     * U_GOLD => ",//金币
     *U_NICKNAME => ",//昵称
     * )
     */
    function update_user_info($uuid,$fields = array()){
        if (!empty($fields)) {
            foreach($fields as $key=>$val){
                if ($val) {
                    $this->DB->set($key, $val);
                }
            }
        }
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $this->DB->where('IDX', $uuid);
        $query = $this->DB->update("pl_user");
        if($query === false){
            log_scribe('trace', 'model', 'update_user_info:'.$this->ip.' where : D_USERIDX->'.$uuid .'fields：'.  http_build_query($fields));
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    function update_user_login_info($uuid,$fields = array()){
        if (!empty($fields)) {
            foreach($fields as $key=>$val){
                if ($val) {
                    $this->DB->set($key, $val);
                }
            }
        }
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $this->DB->where('U_USERIDX', $uuid);
        $query = $this->DB->update("pl_userlogin");
        if($query === false){
            log_scribe('trace', 'model', 'update_user_login_info:'.$this->ip.' where : U_USERIDX->'.$uuid .'fields：'.  http_build_query($fields));
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
     // 更新账户密码（手机注册时）
    function update_user_pwd($mobile, $login_type = 3, $pwd = '')
    {
        $condition['U_ACCOUNTID'] = $mobile;
        $condition['U_TYPE'] = $login_type;
        $condition['STATUS'] = 0;
        $this->DB->where($condition);
        $data = array('U_PASSWORD' => $pwd, 'ROWTIMEUPDATE' => $this->zeit);
        $res = $this->DB->update('pl_userlogin', $data);
        
        if($res === false){
            log_scribe('trace', 'model', 'update_user_pwd:'.$this->ip.' where : D_USERIDX->'.$mobile .'fields：'.  http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 用户信息修改记录到 记录表
     */
    public function record_user_info_change_history($uuid, $nickname, $fields)
    {
        $res = $this->DB->insert('pl_usernickchangelog', $fields);
        if($res === false){
            log_scribe('trace', 'model', 'record_user_info:'.$this->ip.' where : uuid->'.$uuid);
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    
    /**记录用户金币变更历史
     * type : 类型0:增加1:减少
     * source:更来源0:玩游戏1:购买游戏2:任务3:下载游戏4:管理员 5:充值 6:购买道具
     * content:变更说明
     * coin_info =array(
     *   'change_coin'   => $price_current,//变更金币数值
          'coin'          => $coin,//变更后可用金币
     * )
     */
    function record_coin_change_history($uuid,$nickname,$coin_info,$type = 0,$source= 0,$content = ''){
        $data = array(
            'G_USERIDX'     =>  $uuid,
            'G_NICKNAME'    =>  $nickname,
            'G_TYPE'        =>  $type,
            'G_SOURCE'      =>  $source,
            'G_GOLD'        =>  $coin_info['change_coin'],
            'G_TOTALGOLD'   =>  $coin_info['coin'],
            'G_INFO'        =>  $content,
            'STATUS'        =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_goldhistory', $data);
        if($query === false){
            log_scribe('trace', 'model', 'record_coin_change_history:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_GOLD_HISTORY_FAIL);
            return false;
        }
        return true;
    }
    
    /*
     * 记录用户充值记录
     * $recharge_info = array(
     *      R_RECHARGETYPE,充值包类型
     *      R_RECHARGENUM, 充值数量
     *      R_RECHARGERMB, 充值人名币
     *      R_GETGLOD,     获得的金币
     *      R_INFO       充值包其他说明信息
     * )
     */
    function record_user_recharge_history($uuid,$nickname, $recharge_info){
        $data = array(
            'R_USERIDX'         =>  $uuid,
            'R_NICKNAME'        =>  $nickname,
            'R_RECHARGENUM'     =>  $recharge_info['recharge_num'],
            'R_RECHARGERMB'     =>  $recharge_info['recharge_rmb'],
            'R_GETGLOD'         =>  $recharge_info['get_glod'],
            'R_INFO'            =>  $recharge_info['content'],
            'STATUS'            =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_rechargehistory', $data);
        if($query === false){
            log_scribe('trace', 'model', 'record_user_recharge_history:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_RECHARGE_HISTORY_FAIL);
            return false;
        }
        return true;
    }
    
    /**记录用户积分变更历史
     * type : 类型0:增加1:减少
     * source:更来源0:玩游戏1:购买游戏2:任务3:下载游戏4:管理员
     * content:变更说明
     * integral_info =array(
     *   'change_integral'   => ,//变更积分数值
          'integral'          => ,//变更后可用积分
     * )
     */
    function record_integral_change_history($uuid,$nickname,$integral_info,$type = 0,$source= 0,$content = ''){
        $data = array(
            'P_USERIDX'     =>  $uuid,
            'P_NICKNAME'    =>  $nickname,
            'P_TYPE'        =>  $type,
            'P_SOURCE'      =>  $source,
            'P_POINT'       =>  $integral_info['change_integral'],
            'P_TOTALPOINT'  =>  $integral_info['integral'],
            'P_INFO'        =>  $content,
            'STATUS'        =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_pointhistory', $data);
        if($query === false){
            log_scribe('trace', 'model', 'record_integral_change_history:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_INTEGRAL_HISTORY_FAIL);
            return false;
        }
        return true;
    }
    
    /**记录用户昵称变更历史
     * content:变更说明
     */
    function record_nickname_change_history($uuid,$nickname,$new_nickname){
        $data = array(
            'U_USERIDX'         =>  $uuid,
            'U_NICKNAME'        =>  $nickname,
            'U_CHANGENICKNAME'  =>  $new_nickname,
            'U_SYNCSTATUS '     =>  0,
            'STATUS'            =>  0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_usernickchangelog', $data);
        if($query === false){
            log_scribe('trace', 'model', 'record_nickname_change_history:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_GOLD_HISTORY_FAIL);
            return false;
        }
        return true;
    }
    
    /**
     * 获取用户source by uuid
     */
    public function get_user_source($uuid)
    {
        if (!$uuid) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            return false;
        }
        $condition = "STATUS = 0 AND D_USERIDX = ".$uuid;
        $select = array("D_SOURCE AS source");
        $table = 'pl_userdevice';
        $res = $this->get_row_array($condition, $select, $table);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return $res['source'];
    }
    
    /**
     * 查看用户最好成绩表
     */
    public function top_score($uuid, $game_id)
    {
        // 3.查看 "用户游戏积分最好成绩"表，判断是更新数据, 还是插入数据
        $sql2 = "SELECT P_GAMESCORE FROM PL_GAMESCOREUSERTOP WHERE STATUS = 0 AND P_USERIDX = " . $uuid . " AND P_GAMEIDX = " . $game_id;
        $best_score = $this->DB->query($sql2);
        
        if ($best_score === false) {
            log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:' . $this->ip . '  where： S_GAMEIDX = ' . $game_id . " AND P_USERIDX = " . $uuid);
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }

        if ($best_score->num_rows() > 0) {
            $score_info = $best_score->row_array();
        }
        return $score_info;
    }
    
    /**
     * 插入用户成绩历史表
     */
    public function insert_score_hist($data1)
    {
        $res = $this->DB->insert('PL_GAMESCOREHISTORY', $data1);
        if ($res === false) {
            log_scribe('trace', 'model', 'PL_GAMESCOREHISTORY:' . $this->ip . '  data：' . http_build_query($data1));
            $this->error_->set_error(Err_Code::ERR_GAME_SCORING_UPDATE_FAIL);
            
            return false;
        }
        return true;
    }
    
    /**
     * 插入用户最好成绩表
     */
    public function insert_score_top($data1)
    {
        // 插入数据
        $insert = $this->DB->insert('PL_GAMESCOREUSERTOP', $data1);

        if ($insert === false) {
            log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:(insert)' . $this->ip . '  data：' . http_build_query($upd_data));
            $this->error_->set_error(Err_Code::ERR_DB);

            return false;
        }
        return true;
    }
    
    public function get_developer_key($developer_id)
    {
        $condition = 'STATUS = 0 AND D_DEVELOPERID = '.$developer_id;
        $table = 'pl_opendeveloper';
        $select = array(
            'D_NAME AS name',
            'D_ID AS id',
            'D_ENTERPRISE AS company_name',
            'D_IMGPOS AS imgpos',
            'D_IMGREV AS imgrev',
            'D_EMAIL AS email',
            'D_PHONE AS phone',
            'D_QQ AS qq',
            'D_WEBSITE AS website',
            'D_DUTIES AS duties',
            'D_AUTHTYPE AS authtype',
            'D_CHANNELID AS channel_id',
            'D_CHANNELKEY AS channel_key',
            'D_DEVELOPERID AS developer_id',
            'D_DEVELOPERKEY AS developer_key',
            'D_AUDIT AS audit',
            'STATUS AS status',
        );
        $res = $this->get_row_array($condition, $select, $table);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_DEVELOPERID_ERR_FAIL);
            return false;
        }
        return $res;
    }
    
    public function do_exec_sql($sql)
    {
        $query =  $this->DB->query($sql);
        return $query;
    }
}
