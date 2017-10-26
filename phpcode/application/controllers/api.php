<?php

class Api extends P_Controller {
    const PAGE_SIZE = 10;
    function __construct() {
        parent::__construct(false);
        $this->load->model('api_model');
    }
    
    /******
     * 统计被玩pv
     * 统计被打开pv
     * 检测游戏是否可玩
     * url ：游戏url
     * ***** */

    function statistics_pv() {
        log_scribe('trace', 'params', 'statistics_pv:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $url = urldecode($this->request_param('url'));
        if ($url == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        $arr = parse_url($url);
        $queryParts = explode('&', $arr['query']);
        $path = $arr['path'];
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        $share_id   = $params['share_id'];// 分享id
        $game_id    = $params['game_id'];// 制作游戏id
        $id         = $params['id'];// 普通游戏id
        $data['status'] = 1;
        
        $this->load->model('game_model');
        if (isset($share_id)) { // 分享游戏类型（查看分享是什么类型）
            $share_game_info = $this->game_model->get_game_share_info($share_id);
            if ($rst === false) {
                $this->error_->set_error(Err_Code::ERR_STATICTISE_SHARE_IS_NOT_EXIT);
                $this->output_json_return();
            }
            $game_type = $share_game_info['type'];
            $share_play_num = $share_game_info['share_play_num'];
        }
        //校验分享是否存在
        if (isset($game_id) || $game_type == 2) { // 制作游戏类型
            $this->game_model->start();
            //制作游戏 检测游戏是否可玩
            if(isset($share_id)){
                $game_id = $share_game_info['produce_game_id'];
            }
            $produce_game_info = $this->game_model->chk_produce_game_cando($game_id);
            if ($produce_game_info === false) {
                $data['status'] = 0;
                $this->error_->set_error(Err_Code::ERR_OK);
                $this->output_json_return($data);
            }
            //更新 被玩pv 被分享打开pv
            $fields['M_PLAYNUM'] = (int) $produce_game_info['play_num'] + 1;
            //制作游戏被打开次数超过N推送消息
            if($fields['M_PLAYNUM'] >= MAKING_GAME_PLAY_NUM){
                $this->tasklib->send_msg_by_making_playnum($produce_game_info['uuid']);
            }
            if (isset($share_id)) {
                $fields['M_SHAREPLAYNUM'] = (int) $produce_game_info['share_open_num'] + 1;
                $share_fields['T_SHAREPLAYNUM'] = (int) $share_play_num + 1;
                //更新分享被玩pv = 分享被打开pv
                $rst = $this->game_model->update_share_game_info($share_id, $share_fields);
                if ($rst === false) {
                    $this->game_model->error();
                    $this->error_->set_error(Err_Code::ERR_STATICTISE_SHAREPLAY_PV_FAIL);
                    $this->output_json_return();
                }
            }
            $rst = $this->game_model->update_produce_game_info($game_id, $fields);
            if ($rst === false) {
                $this->game_model->error();
                $this->error_->set_error(Err_Code::ERR_STATICTISE_PRODUCE_PV_FAIL);
                $this->output_json_return();
            }
            $this->game_model->success();
            // 统计制作被玩的次数 任务
            $this->game_model->start();
            $this->tasklib->task_making_game_playnum($game_id);
            $this->game_model->success();
        } else { // 普通游戏类型
            $this->game_model->start();
            //免费/金币游戏  检测游戏是否可玩
            if (isset($share_id)) {
                $game_id = $share_game_info['game_id'];
            } else {
                $game_id    = $id;
            }
            $game_info = $this->game_model->chk_game_cando($game_id);
            if ($game_info === false) {
                $data['status'] = 0;
                $this->error_->set_error(Err_Code::ERR_OK);
                $this->output_json_return($data);
            }
            //更新分享pv 被玩pv 被分享打开pv
            $fields['G_PLAYNUM'] = (int) $game_info['play_num'] + 1;
            if (isset($share_id)) {
                 $fields['G_SHAREPLAYNUM'] = (int) $game_info['share_open_num'] + 1;
                 $share_fields['T_SHAREPLAYNUM'] = (int) $share_play_num + 1;
                 //更新分享被玩pv = 分享被打开pv
                 $rst = $this->game_model->update_share_game_info($share_id, $share_fields);
                 if ($rst === false) {
                     $this->game_model->error();
                     $this->error_->set_error(Err_Code::ERR_STATICTISE_SHAREPLAY_PV_FAIL);
                     $this->output_json_return();
                 }
             }
            $rst = $this->game_model->update_game_info($game_info['id'], $fields);
            if ($rst === false) {
                $this->game_model->error();
                $this->error_->set_error(Err_Code::ERR_STATICTISE_GAME_PV_FAIL);
                $this->output_json_return();
            }
             $this->game_model->success();
        }
        if (isset($share_id)) {
            // 如果设置分享share_id，直接跳转到游戏
            $u = explode("?", $url);
            $share_url = $u[0];
            $face = $this->request_param('face');
            if ($face) {
                $share_url = $share_url."?face=".$face;
            }
            
            // var_dump($share_url);exit;http://web.playme.the9.com
            echo "<script type='text/javascript'>window.location.href='".$share_url."'</script>";
        } else {
            $this->output_json_return();
        }
        return;
    }

    /*     * ****
     * 制作游戏的图片及名称
     * share_id :分享id
     * ***** */
    function get_produce_info() {
        log_scribe('trace', 'params', 'get_produce_info:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $share_url = urldecode($this->request_param('url'));
        if ($share_url == "") {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        $arr = parse_url($share_url);
        $queryParts = explode('&', $arr['query']);
        $params = array();
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        $share_id = $params['share_id'];
        $game_id = $params['game_id'];
        if (!isset($share_id) && !isset($game_id)) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        $this->load->model('game_model');
        
        if (isset($share_id)) {
            $data = $this->game_model->get_produce_game_info_by_share_id($share_id);
        } else {
            $data = $this->game_model->get_produce_game_info_by_game_id($game_id);
        }
        
        if ($data === false) {
            $this->error_->set_error(ERR_PRODUCE_GAME_SHAER_NO_EXIT);
            $this->output_json_return();
        }
        $data['pic'] = $this->passport->get('game_url').$data['pic'];
        $this->output_json_return($data);
    }
    
    /**
     * 消息推送
     * @param   int     $uuid       用户唯一标示id 必须
     * @param   int     $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id  设备型号，必须
     * @param   string  $token      登录身份令牌，必须
     * @param   string  $method     produce_game:可制作游戏
     * @param   string  $push_id    推送id
     */
    public function push_msg()
    {
        $params                  = $this->get_public_params();
        $params['type']          = $this->request_param('type');// 设备类型 0:iphone1:android
        $params['b_devicetoken'] = $this->request_param('b_devicetoken');//iphone通知设备标识
        $params['userid']        = $this->request_param('userid'); // 百度通知绑定用户id 
        $params['channel_id']    = $this->request_param('channel_id'); // 百度通知绑定渠道id
        
        if ($params['type'] == '' || $params['b_devicetoken'] == '' || $params['userid'] == '' ||  $params['channel_id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //签名校验
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $params['nickname']   = $this->utility->get_user_info($params['uuid'], 'nickname');
        $this->load->model('api_model');
        $data = $this->api_model->push_msg($params);
        $this->output_json_return($data);
    }
    
    /**
     * 信箱
     */
    public function mailbox()
    {
        $params                 = $this->get_public_params();
        $params['recordindex']  = $this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $params['recordindex'] = (int)$params['recordindex'];
        $params['pagesize']    = (int)$params['pagesize'];
        
        if ($params['recordindex'] == '') {
            $params['recordindex'] = 0;
        }
        
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        $this->load->model('api_model');
        $data = $this->api_model->mailbox($params);
        
        if (empty($data)) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            $this->output_json_return();
            return false;
        }
        $this->output_json_return($data);
    }
    
    /**
     * 更新信箱状态（已读未读）
     */
    public function update_read_status()
    {
        $params         = $this->get_public_params();
        $params['id']   = $this->request_param('id');
        
         //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $id = $params['id'];
        $res = $this->api_model->read_status($id, $params['uuid']);// 已读
        
        if ($res) { // 邮箱已读, 更新失败
            $this->error_->set_error(Err_Code::ERR_UPDATE_MAILBOX_STATUS_FAIL);
            $this->output_json_return(); 
        }
        
        $nickname   = $this->utility->get_user_info($params['uuid'], 'nickname');
        
        $ret        = $this->api_model->update_read_status($id, $params['uuid'], $nickname);
        
        if (!$ret) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_MAILBOX_STATUS_FAIL);
            $this->output_json_return();
        }
        $this->output_json_return();
    }
    
    /**
     * 删除信箱
     */
    public function delete_mailbox()
    {
        $params = $this->get_public_params();
        $params['id'] = $this->request_param('id');
        
         //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $ret = $this->api_model->delete_mailbox($params['id'], $params['uuid']);
        $this->output_json_return();
    }
    
    /**
     * 设置开启，关闭，游客身份
     */
    public function get_visitor_status()
    {
        $params['method']       = $this->request_param('method');
        $params['app_id']       = (int)$this->request_param('app_id');
        $params['device_id']    = $this->request_param('device_id');
        $params['sign']         = $this->request_param('sign');
        $params['channel_id']   = $this->request_param('channel_id');
        $params['version']      = $this->request_param('version');
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $data = $this->api_model->get_visitor_status();
        $this->output_json_return($data);
    }
    
    
    /**
     * 获取游戏列表——精简版
     */
     public function game_simple()
    {
        $game_list = $this->passport->get('game_list');
        $this->output_json_return($game_list);
    }
    
    
}
