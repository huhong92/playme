<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Task_Model extends MY_Model {
    function __construct() {
        parent::__construct(true);
        $this->error_->set_error(Err_Code::ERR_OK);
    }
    
    public function get_task($params)
    {
        // 获取任务列表
        $uuid      = $params['uuid'];
        
        // 查询用户没有完成的任务
        // 查找未完成的app comment任务
        $sql3 = "SELECT T_TASKIDX AS task_id FROM pl_taskusercompletion WHERE T_USERIDX = ".$uuid." AND T_TASKCATNO = 'AppComment' AND T_APPVER = '".$params['app_version']."'";
        $query = $this->DB->query($sql3);
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        
        if ($query->num_rows() <= 0) { // 当前版本的 app 评论未完成， 返回未完成的任务
            $sql1 = "SELECT  IDX as task_id,T_TASKCATNO as task_catno,T_TASKCATNAME as task_catname,T_TASKNAME as task_name,T_TASKCOMPLETIONNUM as task_completionnum,T_REPEATNUM as repeat_num,T_GETPOINT as point,T_GETGOLD as coin,T_ORDERBY as orderby FROM pl_tasklist WHERE  STATUS = 0 AND IDX not in ( select  T_TASKIDX from pl_taskusercompletion where T_USERIDX={$uuid})";
        } else {
            $sql1 = "SELECT  IDX as task_id,T_TASKCATNO as task_catno,T_TASKCATNAME as task_catname,T_TASKNAME as task_name,T_TASKCOMPLETIONNUM as task_completionnum,T_REPEATNUM as repeat_num,T_GETPOINT as point,T_GETGOLD as coin,T_ORDERBY as orderby FROM pl_tasklist WHERE STATUS = 0 AND T_TASKCATNO <> 'AppComment' AND IDX not in ( select  T_TASKIDX from pl_taskusercompletion where T_USERIDX={$uuid})";
        }
        
        // 查询用户已经完成的任务，获取领取的任务
        $sql2 = "SELECT T_TASKIDX as task_id,T_TASKCATNO as task_catno,T_TASKCATNAME as task_catname,T_TASKNAME as task_name,T_TASKCOMPLETIONNUM as task_completionnum,T_REPEATNUM as repeat_num,T_GETPOINT as point,T_GETGOLD as coin,T_ORDERBY as orderby,RECEIVE AS receive FROM pl_taskusercompletion WHERE T_USERIDX = ".$uuid; 
        
        $query1 = $this->DB->query($sql1);
        $query2 = $this->DB->query($sql2);
        
        if ($query1 === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        
        if ($query2 === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        
        $task_list = array();
        if ($query1->num_rows() <= 0 && $query2->num_rows() <= 0) { // 数据库查询出错
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        } else if ($query1->num_rows() <= 0) { // 用户所有的任务都完成了
            $simle_task = $query2->result_array();
            $task_list['smile'] = $simle_task;
            $task_list['question'] = array();
        } else if ($query2->num_rows() <= 0) { // 用户一个任务都没有完成
            $question_task = $query1->result_array();
            
            $task_list['smile'] = array();
            $task_list['question'] = $question_task;
        } else { // 用户既有没有完成的任务，又有已完成的任务
            $question_task = $query1->result_array();
            $simle_task = $query2->result_array();
            $task_list['smile'] = $simle_task;
            $task_list['question'] = $question_task;
        }
        if (!$task_list) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $task = array_merge($task_list['question'], $task_list['smile']);
        
        // 判断是否去掉首次登陆的任务
        //获取用户的注册时间
        $current_ts = time()+ 0.1; // 防止注册，和登陆 记录的时间相同,所以有0.1s的误差
        $create_time = $this->utility->get_user_info($uuid,'create_time');
        $day = ceil($current_ts/86400 - $create_time/86400);
        
        if($day > 7){
            // 过滤掉未完成的7天首次登陆任务
            foreach ($task as $k=>$v) {
                if ($v['task_catno'] == 'Login' && !isset($v['receive'])) {
                    unset($task[$k]);
                } else {
                    if (isset($v['receive'])) {
                        $task[$k]['complete'] = 1; // 已完成的任务
                    } else {
                        $task[$k]['complete'] = 0; // 未完成的任务
                        $task[$k]['receive'] = 0; // 未领取奖励
                    }
                }
            }
        } else {
            foreach ($task as $k=>$v) {
                if (isset($v['receive'])) {
                    $task[$k]['complete'] = 1; // 已完成的任务
                } else {
                    $task[$k]['complete'] = 0; // 未完成的任务
                    $task[$k]['receive'] = 0; // 未领取奖励
                }
            }
        }
        $new_task = array();
        $new_task= array_values($task);
        return $new_task;
    }
    
    //领取奖励
    function get_task_award($uuid, $task_id ,$app_version = ''){
        if ($app_version) {
            $task_info = $this->get_task_info_by_app_ver($uuid, $app_version);
        } else {
            $task_info = $this->get_task_info_by_task_id($uuid, $task_id);
        }
        if(empty($task_info) || $task_info === false){
            $this->error_->set_error(Err_Code::ERR_WITHOUT_COMPLETE_TASK);
            return false;
        }
        if($task_info['receive'] == 1){
            $this->error_->set_error(Err_Code::ERR_GET_AWARD_FINISH);
            return false;
        }
        $integral = $task_info['integral'];
        $coin     = $task_info['coin'];
        
        // 2. 修改用户信息表-积分和金币
        $sql5 = "UPDATE PL_USER SET U_TOTALPOINT = U_TOTALPOINT + " . $integral . ", U_POINT = U_POINT + " . $integral . ", U_GOLD = U_GOLD + ".$coin." , ROWTIMEUPDATE = '" . $this->zeit . "' WHERE IDX = " . $uuid;
        $user_update = $this->DB->query($sql5);
        
        if ($user_update === false) {
            log_scribe('trace', 'model', 'PL_USER:(update)' . $this->ip . '  where： uuid = ' . $uuid);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        $userinfo = $this->utility->get_user_info($uuid);
        //3. 记录用户积分变更历史
        if ($integral !==0) {
            $integral_info = array(
                'change_integral' => $integral, // 用户获得积分
                'integral'        => $userinfo['integral'], // 用户可用积分
            );
            $res = $this->user_model->record_integral_change_history($uuid, $userinfo['nickname'], $integral_info, 0, 3);
            
            if (!$res) {
                return false;
            }
        }
        
        // 4.记录金币变更历史
        if ($coin !==0) {
            $coin_info = array(
                'change_coin'   => $coin, // 变更的金币
                'coin'          => $userinfo['coin'], //变更后可用金币
            );
            $res = $this->user_model->record_coin_change_history($uuid, $userinfo['nickname'], $coin_info, 0, 2);
            if (!$res) {
                return false;
            }
        }
        
        // 5.更新完成任务表记录 status = 1
        // 只更新一条数据
        $condition_2 = "T_USERIDX = ".$uuid." AND STATUS = 0 AND RECEIVE = 0 AND T_TASKIDX = ".$task_id;
        $select_2 = array('IDX AS id');
        $table_2 = "pl_taskusercompletion";
        $result = $this->get_row_array($condition_2, $select_2, $table_2);
        if (!$result) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            RETURN FALSE;
        }
        $idx = $result['id']; // 任务完成表，自动id
        $data = array(
            'RECEIVE'        => 1,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $this->DB->where(array('T_USERIDX' => $uuid, 'T_TASKIDX' => $task_id, 'IDX' => $idx));
        $upd = $this->DB->update('pl_taskusercompletion', $data);
        
        if (!$upd) {
            $this->error_->set_error(Err_Code::ERR_GET_AWARD_FAIL);
            return false;
        }
        
        // 6. 判断用户积分是否大于等于900,推送消息
        if ($userinfo['integral'] >= 900) {
            $this->CI->tasklib->send_msg_by_integral($uuid);
        }
        
        return true;     
    }
    
    /**
     * 任务---完成免费游戏任务
     */
    public function get_free_task($uuid)
    {   
        $tb_a = "PL_GAMESCOREUSERTOP AS A";
        $tb_b = "PL_GAME AS B";
        $select = array("count('A.IDX') AS free_num");
        $condition = "B.G_GAMETYPE = 0 AND A.P_USERIDX = ".$uuid." AND A.STATUS = 0 AND B.STATUS = 0";
        $join_condition = "A.P_GAMEIDX = B.IDX";
        $res = $this->get_composite_row_array($select, $condition, $join_condition, $tb_a, $tb_b);
        
        if(!empty($res)){
            $_count = 0;
            if($res['free_num'] == FRRE_GAME_COUNT_5) {
                $_count = FRRE_GAME_COUNT_5;
            } else if ($res['free_num'] == FRRE_GAME_COUNT_10) {
                $_count = FRRE_GAME_COUNT_10;
            } else if ($res['free_num'] == FRRE_GAME_COUNT_50) {
                $_count = FRRE_GAME_COUNT_50;
            } else if ($res['free_num'] == FRRE_GAME_COUNT_100) {
                $_count = FRRE_GAME_COUNT_100;
            }
            
            //查task_id
            $task_type = 'FreeGame';
            $sys_task = $this->get_sys_task_by_type($task_type, $_count);
            
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid,$task_id);
                
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid,$sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            }else {
                $this->error_->set_error(Err_Code::ERR_FREE_TASK_TAIL);
                return false;
            }
        }
        return true;
    }
    
    /**
     * 任务---获取购买金币游戏的任务
     */
    public function get_buy_coin_task($uuid)
    {   
        $table = "PL_GAMEBUY";
        $select = array("count('IDX') AS buy_num");
        $condition = "B_USERIDX = ".$uuid." AND STATUS = 0";
        $res = $this->get_row_array($condition, $select, $table);
        
        if(!empty($res)){
            $_count = 0;
            if($res['buy_num'] == BUY_COIN_GAME_COUNT_1) {
                $_count = BUY_COIN_GAME_COUNT_1;
            } else if ($res['buy_num'] == BUY_COIN_GAME_COUNT_5) {
                $_count = BUY_COIN_GAME_COUNT_5;
            } else if ($res['buy_num'] == BUY_COIN_GAME_COUNT_10) {
                $_count = BUY_COIN_GAME_COUNT_10;
            } else if ($res['buy_num'] == BUY_COIN_GAME_COUNT_20) {
                $_count = BUY_COIN_GAME_COUNT_20;
            } else if ($res['buy_num'] == BUY_COIN_GAME_COUNT_50) {
                $_count = BUY_COIN_GAME_COUNT_50;
            }
            //查task_id
            $task_type = 'PayGame';
            $sys_task = $this->get_sys_task_by_type($task_type, $_count);
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid,$task_id);
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid,$sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            }else {
                $this->error_->set_error(Err_Code::ERR_BUY_COIN_TASK_TAIL);
                return false;
            }
        }
        return true;
    }
   
    /**
     * 任务---获取下载金币游戏任务
     */
    public function get_download_task($uuid)
    {   
        $tb_a = "PL_GAMEDOWNLOAD AS A";
        $tb_b = "PL_GAME AS B";
        $select = array("count('A.IDX') AS download_num");
        $condition = "B.G_GAMETYPE = 1 AND A.D_USERIDX = ".$uuid." AND A.STATUS = 0 AND B.STATUS = 0";
        $join_condition = "A.D_GAMEIDX = B.IDX";
        $res = $this->get_composite_row_array($select, $condition, $join_condition, $tb_a, $tb_b);
        
        if(!empty($res)){
            $_count = 0;
            if($res['download_num'] == DOWN_COIN_GAME_COUNT_1) {
                $_count = DOWN_COIN_GAME_COUNT_1;
            } else if ($res['download_num'] == DOWN_COIN_GAME_COUNT_5) {
                $_count = DOWN_COIN_GAME_COUNT_5;
            } else if ($res['download_num'] == DOWN_COIN_GAME_COUNT_10) {
                $_count = DOWN_COIN_GAME_COUNT_10;
            } else if ($res['download_num'] == DOWN_COIN_GAME_COUNT_20) {
                $_count = DOWN_COIN_GAME_COUNT_20;
            } else if ($res['download_num'] == DOWN_COIN_GAME_COUNT_50) {
                $_count = DOWN_COIN_GAME_COUNT_50;
            }
            //查task_id
            $task_type = 'DownPayGame';
            $sys_task = $this->get_sys_task_by_type($task_type, $_count);
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid,$task_id);
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid,$sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            }else {
                $this->error_->set_error(Err_Code::ERR_DOWNLOAD_COIN_TASK_TAIL);
                return false;
            }
        }
        return true;
    }
    
    /**
     * 任务---获取制作游戏任务
     */
    public function get_making_task($uuid)
    {   
        $table = "PL_MAKING";
        $select = array("count('IDX') AS making_num");
        $condition = "M_USERIDX = ".$uuid." AND STATUS = 0";
        $res = $this->get_row_array($condition, $select, $table);
        
        if(!empty($res)){
            $_count = 0;
            if($res['making_num'] == MAKE_GAME_COUNT_1) {
                $_count = MAKE_GAME_COUNT_1;
            } else if ($res['making_num'] == MAKE_GAME_COUNT_3) {
                $_count = MAKE_GAME_COUNT_3;
            } else if ($res['making_num'] == MAKE_GAME_COUNT_10) {
                $_count = MAKE_GAME_COUNT_10;
            } else if ($res['making_num'] == MAKE_GAME_COUNT_20) {
                $_count = MAKE_GAME_COUNT_20;
            } else if ($res['making_num'] == MAKE_GAME_COUNT_50) {
                $_count = MAKE_GAME_COUNT_50;
            }
            //查task_id
            $task_type = 'MakeGame';
            $sys_task = $this->get_sys_task_by_type($task_type, $_count);
            
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid, $task_id);
                
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid, $sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            } else {
                $this->error_->set_error(Err_Code::ERR_MAKING_TASK_TAIL);
                return false;
            }
        }
        return true;
    }
    
    /**
     * 任务---获取首次分享任务
     */
    public function get_first_share_task($uuid)
    {
        $table = "pl_gameshare";
        $select = array("count('IDX') AS num");
        $condition = "T_USERIDX = ".$uuid." AND STATUS = 0";
        $res = $this->get_row_array($condition, $select, $table);
        
        if ((int)$res['num'] === 1) {
            $task_type = 'ShareGame';
            $sys_task = $this->get_sys_task_by_type($task_type);
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid,$task_id);
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid,$sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            } else {
                $this->error_->set_error(Err_Code::ERR_TASK_IS_NOT_EXIT);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 任务---获取首次评论任务
     */
    public function get_first_comment_task($uuid)
    {
        $table = "pl_gamecomments";
        $select = array("count('IDX') AS num");
        // $condition = "C_USERIDX = ".$uuid." AND C_VER = '".$app_version."' AND STATUS = 0";
        $condition = "C_USERIDX = ".$uuid." AND STATUS = 0";
        $res = $this->get_row_array($condition, $select, $table);
        
        if ((int)$res['num'] === 1) {
            $task_type = 'CommentGame';
            $sys_task = $this->get_sys_task_by_type($task_type);
            
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid,$task_id);
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid, $sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            } else {
                $this->error_->set_error(Err_Code::ERR_TASK_IS_NOT_EXIT);
                return false;
            }
        }
        
        return true;
    }
    /**
     * 任务--完善用户信息，奖励积分
     */
    public function full_user_info_task($uuid) // pl_usernickchangelog --- 优化 2015-05-08 12:05 @auther huhong
    {
            $task_type = 'MyInfo';
            $sys_task = $this->get_sys_task_by_type($task_type);
            
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid, $task_id);
               
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid,$sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            } else {
                $this->error_->set_error(Err_Code::ERR_TASK_IS_NOT_EXIT);
                return false;
            }
        
        return true;
    }
    
    /**
     * 任务---获取单个制作游戏被打开的次数 任务
     */
    public function get_making_play_num_task($game_id)
    {
        $table = "PL_MAKING";
        $select = array("M_PLAYNUM AS play_num", "M_USERIDX AS uuid");
        $condition = "IDX = ".$game_id." AND STATUS = 0";
        $res = $this->get_row_array($condition, $select, $table);
        if(!empty($res)){
            $uuid = $res['uuid'];
            $_count = 0;
            if($res['play_num'] == MAKE_GAME_PLAY_COUNT_10) {
                $_count = MAKE_GAME_PLAY_COUNT_10;
            } else if ($res['play_num'] == MAKE_GAME_PLAY_COUNT_20) {
                $_count = MAKE_GAME_PLAY_COUNT_20;
            } else if ($res['play_num'] == MAKE_GAME_PLAY_COUNT_50) {
                $_count = MAKE_GAME_PLAY_COUNT_50;
            }
            //查task_id
            $task_type = 'MakeGameOpen';
            $sys_task = $this->get_sys_task_by_type($task_type, $_count);
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid,$task_id);
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid,$sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            } else {
                $this->error_->set_error(Err_Code::ERR_TASK_IS_NOT_EXIT);
                return false;
            }
        }
        return true;
    }
    
    /**
     * 用户前7天登陆，记录任务
     */
    public function seven_login_task($uuid)
    {
        //获取用户的注册时间
        $current_ts = time(); 
        $create_time = $this->utility->get_user_info($uuid,'create_time'); // 时间戳格式
        
        $create_time = date("Y-m-d", $create_time); // 2015-03-27
        $create_time = strtotime($create_time); // 时间戳格式
        
        $current_ts = date("Y-m-d", $current_ts); // 2015-03-27
        $current_ts = strtotime($current_ts); // 时间戳格式
        
        $day = $current_ts/86400 - $create_time/86400;
        if (!$day) {
            $day = 0.1;// 防止注册，和登陆 记录的时间相同,所以有0.1s的误差
        }
        $day = ceil($day);
        if($day > 7){
            return true;
        } else if($day > 0 && $day <= 7) {
            $task_type = 'Login';
            $sys_task = $this->get_sys_task_by_type($task_type, $day);
            if(!empty($sys_task)) {
                $task_id = $sys_task['task_id'];
                $task_info = $this->get_task_info_by_task_id($uuid,$task_id);
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid,$sys_task);
                    if ($ret === false) {
                        return false;
                    }
                }
            } else {
                $this->error_->set_error(Err_Code::ERR_TASK_IS_NOT_EXIT);
                return false;
            }
        }
        return true;
    }
    
    /**
     * 每个APP版本，首次评论APP任务
     */
    function task_first_comment_app($uuid, $app_version)
    {
            $task_type = 'AppComment';
            $sys_task = $this->get_sys_task_by_type($task_type);
            if(!empty($sys_task)) {
                $task_info = $this->get_task_info_by_app_ver($uuid, $app_version);
                if(empty($task_info) || $task_info === false){
                    //插入任务完成表
                    $ret = $this->inser_user_task_completion($uuid, $sys_task, $app_version);
                    if ($ret === false) {
                        return false;
                    }
                }
            } else {
                $this->error_->set_error(Err_Code::ERR_TASK_IS_NOT_EXIT);
                return false;
            }
        
        return true;
    }
    
    /**
     * 获取app评论地址（android）
     */
    function get_comment_url($source)
    {
        $table = 'pl_appcommenturl';
        $condition = "STATUS = 0 AND C_SOURCE = ".$source;
        $select = array("C_URL AS url");
        $res = $this->get_row_array($condition, $select, $table);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_GET_APP_COMMENT_URL_FAIL);
            return false;
        }
        
        return $res['url'];
    }
    
    //查询任务对应的配置信息
    function get_sys_task_by_type($task_type,$count = 1){
        $table = 'pl_tasklist';
        $condition = "STATUS = 0 AND T_TASKCATNO = '" . $task_type ."' AND T_TASKCOMPLETIONNUM = ".$count;
        $select = array(
            'IDX AS task_id',
            'T_TASKCATNO AS task_type',
            'T_TASKCATNAME AS task_type_name',
            'T_TASKNAME AS task_name',
            'T_TASKCOMPLETIONNUM AS task_count',
            'T_REPEATNUM AS repeat_num',
            'T_GETPOINT AS integral',
            'T_GETGOLD AS coin',
            'T_ORDERBY AS task_orderby',
        );
        $ret = $this->get_row_array($condition, $select, $table);
        if ($ret === false) {
            return false;
        }
        return $ret;
    }
    
    //查询任务完成对应的信息
    function get_task_info_by_task_id($uuid, $task_id){
        $table = 'pl_taskusercompletion';
        if ($task_id == 8) { // 突破任务
            $condition = "T_TASKIDX = " . $task_id ." AND RECEIVE = 0 AND T_USERIDX = ".$uuid;
        } else {
            $condition = "T_TASKIDX = " . $task_id ." AND T_USERIDX = ".$uuid;
        }
        
        $select = array(
            'IDX AS id',
            'T_USERIDX AS uuid',
            'T_GETPOINT AS integral',
            'T_GETGOLD AS coin',
            'status AS status',
            'RECEIVE AS receive',
        );
        $ret = $this->get_row_array($condition, $select, $table);
        if ($ret === false) {
            return false;
        }
        return $ret;
    }
    
    //查询任务完成对应的信息
    function get_task_info_by_app_ver($uuid, $app_ver, $task_no = 'AppComment'){
        $table = 'pl_taskusercompletion';
        $condition = "T_TASKCATNO = '" . $task_no ."' AND T_USERIDX = ".$uuid." AND T_APPVER = '".$app_ver."'";
        $select = array(
            'IDX AS id',
            'T_USERIDX AS uuid',
            'T_GETPOINT AS integral',
            'T_GETGOLD AS coin',
            'status AS status',
        );
        $ret = $this->get_row_array($condition, $select, $table);
        if ($ret === false) {
            return false;
        }
        return $ret;
    }
    
    //插入任务完成表
    function inser_user_task_completion($uuid,$params, $app_version = ''){
        $nickname = $this->utility->get_user_info($uuid,'nickname');
        if (!$nickname) {
            $nickname = '';
        }
        $data = array(
            'T_USERIDX'             =>  $uuid,
            'T_NICKNAME'            =>  $nickname,
            'T_TASKIDX'             =>  $params['task_id'],
            'T_TASKCATNO'           =>  $params['task_type'],
            'T_TASKCATNAME'         =>  $params['task_type_name'],
            'T_TASKNAME'            =>  $params['task_name'],
            'T_TASKCOMPLETIONNUM'   =>  $params['task_count'],
            'T_REPEATNUM'           =>  $params['repeat_num'],
            'T_GETPOINT'            =>  $params['integral'],
            'T_GETGOLD'             =>  $params['coin'],
            'T_ORDERBY'             =>  $params['task_orderby'],
            'T_APPVER'              =>  $app_version,
            'STATUS'                =>  0,
            'RECEIVE'               =>  0, // 未领取
        );
        
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_taskusercompletion', $data);
        
        if($query === false){
            log_scribe('trace', 'model', 'inser_user_task_completion:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_INSERT_TASK_COMPLETE_FAIL);
            return false;
        }
        
        return true;
    }

}
