<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Activity extends P_Controller {
    const PAGE_SIZE = 10;
    const ORDER_TYPE = 1;
    function __construct() {
        parent::__construct(false);
        $this->load->model('activity_model');
    }
    
    /** 活动列表
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	String	设备号，必须
     * token	String	登录身份令牌，必须
     * method	string	activity_list:活动列表
     * banner_list：banner列表    
     * recordindex	int	每页请求开始位置，必须 （0开始）
     * pagesize	Int	每页请求最大长度，必须
     * sign	String	签名，必须
     * **/
    function activity_list() {
        log_scribe('trace', 'params', 'activity_list:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params_no_token();
        $params['recordindex']  = $this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        $params['order_type']   = $this->request_param('order_type');
        $params['sign']         = $this->request_param('sign');
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 统计，点击活动按钮，次数 channel_id:九成渠道
        if ($params['channel_id'] == '1') {
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_ACTIVITY', $time);
        }
        $params['recordindex']  = (int)$params['recordindex'];
        $params['pagesize']     = ($params['pagesize'] == "") ? self::PAGE_SIZE:(int)$params['pagesize'];
        $params['order_type']   = ($params['order_type'] == "") ? self::ORDER_TYPE:(int)$params['order_type'];
        $params['type']         = 0;
        $data = $this->_get_activity_list($params);
        $this->output_json_return($data);
    }
    
    /* ** banner列表
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	String	设备号，必须
     * token	String	登录身份令牌，必须
     * method	string	activity_list:活动列表
     * banner_list：banner列表    
     * recordindex	int	每页请求开始位置，必须 （0开始）
     * pagesize	Int	每页请求最大长度，必须
     * sign	String	签名，必须
     * **/
    function banner_list() {
        log_scribe('trace', 'params', 'banner_list:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params_no_token();
        $params['recordindex']  = $this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        $params['order_type']   = $this->request_param('order_type');
        $params['sign']         = $this->request_param('sign');
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['recordindex']  = (int)$params['recordindex'];
        $params['pagesize']     = ($params['pagesize'] == "") ? self::PAGE_SIZE:(int)$params['pagesize'];
        $params['order_type']   = ($params['order_type'] == "") ? self::ORDER_TYPE:(int)$params['order_type'];
        $params['type']         = 1;
        $data = $this->_get_activity_list($params);
        
        $this->output_json_return($data);
    }
    
    //获取列表
    function _get_activity_list($params){
        $data = $this->activity_model->get_activity_list($params);
        if (!empty($data['list']) && is_array($data['list'])) {
            foreach ($data['list'] as $k=>$v) {
                if ($v['icon']) {
                    $data['list'][$k]['icon'] = $this->passport->get('game_url').$v['icon'];
                }
                
                if ($v['banner']) {
                   $data['list'][$k]['banner'] =$this->passport->get('game_url').$v['banner'];
                }
                if ($v['pc_img']) {
                   $data['list'][$k]['pc_img'] =$this->passport->get('game_url').$v['pc_img'];
                }
            }
        }
        
        return $data;
    }
    
    /**活动详情
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * token	string	登录身份令牌，必须
     * method	string	detail:活动详情 
     * id	int	活动的id，必须
     * sign	string	签名，必须
     * **/
    function detail() {
        log_scribe('trace', 'params', 'activity detail:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params         = $this->get_public_params_no_token();
        $params['id']   = $this->request_param('id');
        $params['sign'] = $this->request_param('sign');
        if($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['id'] = (int)$params['id'];
        $data = $this->activity_model->get_activity_detail($params['id']);
        if ($data['icon']) {
            $data['icon'] = $this->passport->get('game_url').$data['icon'];
        }
        if ($data['banner']) {
            $data['banner'] = $this->passport->get('game_url').$data['banner'];
        }
        if ($data['pc_img']) {
            $data['pc_img'] = $this->passport->get('game_url').$data['pc_img'];
        }
        $this->output_json_return($data);
    }
}