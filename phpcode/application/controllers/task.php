<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Task extends P_Controller {
    function __construct() {
        parent::__construct(false);
        $this->load->model('task_model');
    }
    
    /**
     * 获取任务列表（统计任务及奖励）
     */
    public function get_task()
    {
        log_scribe('trace', 'params', 'task_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params();
        $params['app_version']  = $this->request_param('app_version');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 统计，点击任务按钮，次数
        if ($params['channel_id'] == '1') {
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_TASK', $time);
        }
        
        $data['task_list'] = $this->task_model->get_task($params);
        $this->load->model('user_model');
        $data['user_info'] = $this->utility->get_user_info($params['uuid']);
        $this->output_json_return($data);    
    }
    
    /**
     * 领取奖励
     * @param   int     $uuid       用户唯一标示id 必须
     * @param   int     $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id  设备型号，必须
     * @param   string  $token      登录身份令牌，必须
     * @param   string  $method     award_task:领取任务奖励
     * @param   int     $task_id    任务id  
     * @param   string  $sign	     签名，必须
     * @return  json               
     */
    function award_task(){
        $params                 = $this->get_public_params();
        $params['task_id']      = $this->request_param('task_id');
        $params['app_version']  = $this->request_param('app_version');
        $params['task_catno']   = $this->request_param('task_catno');
        $uuid                   = $params['uuid'];
        
        if($params['task_id'] == ""){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['task_id'] = (int)$params['task_id'];
        $this->task_model->start();
        if ($params['task_catno'] == 'AppComment') {
            $ret = $this->task_model->get_task_award($params['uuid'],$params['task_id'], $params['app_version']);
        } else {
            $ret = $this->task_model->get_task_award($params['uuid'],$params['task_id']);
        }
        if(!$ret) {
            $this->task_model->error();
            $this->output_json_return();
        }
        $this->task_model->success();
        
        // 领取积分之后，判断用户积分是否大于等于900,推送消息
        $userinfo = $this->utility->get_user_info($uuid);
        if ($userinfo['integral'] >= 900) {
            $this->tasklib->send_msg_by_integral($uuid);
        }
        $this->output_json_return();
    }
    
    /**
     * 评论APP任务接口
     */
    public function app_comment()
    {
        $params                 = $this->get_public_params();
        $params['app_version']  = $this->request_param('app_version');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $this->task_model->start();
        $this->tasklib->task_first_comment_app($params['uuid'], $params['app_version']);
        $this->task_model->success();
        
        $this->output_json_return();
    } 
    
    /**
     * 评论APP评论地址（android）
     */
    public function get_comment_url()
    {
        $params = $this->get_public_params();
        // $params['app_version'] = $this->request_param('app_version');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 根据uuid获取，source
        $this->load->model('user_model');
        $source = $this->user_model->get_user_source($params['uuid']);
        
        $url = $this->task_model->get_comment_url($source);
        $this->output_json_return($url);
    }
}

