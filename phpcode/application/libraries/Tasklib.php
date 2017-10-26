<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Tasklib {
    private $CI;
    function __construct() {
        $this->CI = & get_instance();
    }
    
    //完成免费游戏任务
    function task_free_game($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->get_free_task($uuid);
        
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_free_game:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //购买游戏任务
    function task_buy_game($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->get_buy_coin_task($uuid);
        
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_buy_game:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //下载游戏任务
    function task_download_game($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->get_download_task($uuid);
        
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_download_game:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
     //制作游戏任务
    function task_make_game($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->get_making_task($uuid);
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_make_game:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //完善个人信息游戏任务
    function task_full_user_info($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->full_user_info_task($uuid);
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_full_user_info:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //首次分享任务
    function task_share_game($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->get_first_share_task($uuid);
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_share_game:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //首次评论任务
    function task_comment_game($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->get_first_comment_task($uuid);
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_comment_game:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //制作被打开次数任务
    function task_making_game_playnum($game_id){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->get_making_play_num_task($game_id);
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_making_game_playnum:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    //首次登陆7天任务
    function task_login_award($uuid){
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->seven_login_task($uuid);
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_login_award:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    /*
     * 每个APP版本，首次评论APP任务
     */
    function task_first_comment_app($uuid, $app_version)
    {
        $this->CI->load->model('task_model');
        $ret = $this->CI->task_model->task_first_comment_app($uuid, $app_version);
        if($ret === false){
            $error_no = $this->CI->error_->get_error();
            $err_msg= $this->CI->error_->error_msg($error_no);
            log_scribe('trace', 'task', 'task_first_comment_app:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }
    
    
    
    /*************************推送消息****************************/
    //积分900以上推送消息
    function send_msg_by_integral($uuid){
        $this->CI->load->model('api_model');
        $bind_info = $this->CI->api_model->get_user_bind_push($uuid);
        if(!empty($bind_info)){
            $ret = $this->CI->api_model->get_user_push_push_task($uuid,PUSH_NO_MAKE_GAME);
            if($ret === false || empty($ret)){
                $nickname = $this->CI->utility->get_user_info($uuid,'nickname');
               $ret = $this->CI->api_model->inser_user_push_task($uuid,$nickname,PUSH_NO_MAKE_GAME);
                if($ret === false){
                    $error_no = $this->CI->error_->get_error();
                    $err_msg= $this->CI->error_->error_msg($error_no);
                    log_scribe('trace', 'task', 'send_msg_by_integral:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
                }
                //定时发送
                $time_arr = getdate(time());
                $lastest_0_ts = mktime(0,0,0,$time_arr['mon'],$time_arr['mday'],$time_arr['year']);//当天的0点
                $lastest_20_ts = $lastest_0_ts+72000;//当天的20点
                $send_time = date('Y-m-d H:i:s',$lastest_20_ts);
                $ret = $this->CI->api_model->inser_user_send_msg($uuid,$nickname,$bind_info,PUSH_NO_MAKE_GAME,$send_time);
                if($ret === false){
                    $error_no = $this->CI->error_->get_error();
                    $err_msg= $this->CI->error_->error_msg($error_no);
                    log_scribe('trace', 'task', 'send_msg_by_integral:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
                }
            }
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
        return true;
    }
    
    //制作游戏被打开次数
    function send_msg_by_making_playnum($uuid){
        $this->CI->load->model('api_model');
        $bind_info = $this->CI->api_model->get_user_bind_push($uuid);
        if(!empty($bind_info)){
            $ret = $this->CI->api_model->get_user_push_push_task($uuid,PUSH_MAKING_GAME_PLAYNUM);
            if($ret === false || empty($ret)){
                $nickname = $this->CI->utility->get_user_info($uuid,'nickname');
                $ret = $this->CI->api_model->inser_user_push_task($uuid,$nickname,PUSH_MAKING_GAME_PLAYNUM);
                if($ret === false){
                    $error_no = $this->CI->error_->get_error();
                    $err_msg= $this->CI->error_->error_msg($error_no);
                    log_scribe('trace', 'task', 'send_msg_by_making_playnum:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
                }
                $ret = $this->CI->api_model->inser_user_send_msg($uuid,$nickname,$bind_info,PUSH_MAKING_GAME_PLAYNUM);
                if($ret === false){
                    $error_no = $this->CI->error_->get_error();
                    $err_msg= $this->CI->error_->error_msg($error_no);
                    log_scribe('trace', 'task', 'send_msg_by_making_playnum:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
                }
            }
        }
        
        $this->CI->error_->set_error(Err_Code::ERR_OK);
        return true;
    }
    
    //超越最高分
    function send_msg_by_top_score($to_uuid,$to_nickname,$nickname){
        $this->CI->load->model('api_model');
        $bind_info = $this->CI->api_model->get_user_bind_push($to_uuid);
        if(!empty($bind_info)){
            $ret = $this->CI->api_model->inser_user_push_task($to_uuid,$to_nickname,PUSH_TOP_GAME_SCORE);
            if($ret === false){
                $error_no = $this->CI->error_->get_error();
                $err_msg= $this->CI->error_->error_msg($error_no);
                log_scribe('trace', 'task', 'send_msg_by_top_score:'.$this->ip.' to_uuid'.$to_uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
            }
            $ret = $this->CI->api_model->inser_user_send_msg($to_uuid,$to_nickname,$bind_info,PUSH_TOP_GAME_SCORE,0,$nickname);
            if($ret === false){
                $error_no = $this->CI->error_->get_error();
                $err_msg= $this->CI->error_->error_msg($error_no);
                log_scribe('trace', 'task', 'send_msg_by_top_score:'.$this->ip.' to_uuid'.$to_uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
            }
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
        return true;
    }
    
    // 用户昵称，有特殊字符时，推送消息
    function send_msg_by_nickname_special_char($uuid)
    {
        $this->CI->load->model('api_model');
        $bind_info = $this->CI->api_model->get_user_bind_push($uuid);
        if(!empty($bind_info)){
            $nickname = $this->CI->utility->get_user_info($uuid,'nickname');
            $ret = $this->CI->api_model->inser_user_push_task($uuid,$nickname,NICKNAME_SPECIAL_CHAR);
            if($ret === false){
                $error_no = $this->CI->error_->get_error();
                $err_msg= $this->CI->error_->error_msg($error_no);
                log_scribe('trace', 'task', 'send_msg_by_nickname_special_char:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
            }
            $ret = $this->CI->api_model->inser_user_send_msg($uuid,$nickname,$bind_info,NICKNAME_SPECIAL_CHAR);
            if($ret === false){
                $error_no = $this->CI->error_->get_error();
                $err_msg= $this->CI->error_->error_msg($error_no);
                log_scribe('trace', 'task', 'send_msg_by_nickname_special_char:'.$this->ip.' to_uuid'.$to_uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
            }
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
        return true;
    }
    
    // 用户昵称，有屏蔽字，推送消息
    function send_msg_by_nickname_illegal($uuid)
    {
        $this->CI->load->model('api_model');
        $bind_info = $this->CI->api_model->get_user_bind_push($uuid);
        if(!empty($bind_info)){
            $nickname = $this->CI->utility->get_user_info($uuid,'nickname');
            $ret = $this->CI->api_model->inser_user_push_task($uuid,$nickname,NICKNAME_ILLEGAL_CHAR);
            if($ret === false){
                $error_no = $this->CI->error_->get_error();
                $err_msg= $this->CI->error_->error_msg($error_no);
                log_scribe('trace', 'task', 'send_msg_by_nickname_illegal:'.$this->ip.' uuid'.$uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
            }
            $ret = $this->CI->api_model->inser_user_send_msg($uuid,$nickname,$bind_info,NICKNAME_ILLEGAL_CHAR);
            if($ret === false){
                $error_no = $this->CI->error_->get_error();
                $err_msg= $this->CI->error_->error_msg($error_no);
                log_scribe('trace', 'task', 'send_msg_by_nickname_illegal:'.$this->ip.' to_uuid'.$to_uuid .' error_no：'.$error_no .'  error_msg:'.$err_msg);
            }
        }
        $this->CI->error_->set_error(Err_Code::ERR_OK);
        return true;
    }
}
