<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Search extends P_Controller {
    const PAGE_SIZE = 10;
    function __construct() {
        parent::__construct(false);
        $this->load->model('game_model');
        $this->load->model('search_model');
    }
    /**搜索接口
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * token	string	登录身份令牌，必须
     * method	string	search_list:游戏列表
     * keywords	string	搜索关键字，必须
     * recordindex	int	每页请求开始位置，必须 （0开始）
     * pagesize	string	每页请求最大长度，必须
     * sign	string	签名，必须
     * **/
    function search_list(){
        log_scribe('trace', 'params', 'search_list:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $params['keywords']    = urldecode($this->request_param('keywords'));
        $params['version']     = $this->request_param('version');
        $params['sign']        = $this->request_param('sign');
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if($params['keywords'] == ""){
            $this->error_->set_error(Err_Code::ERR_SEARCH_KEYWORDS_NONE);
            $this->output_json_return();
        }
        $params['recordindex'] = (int)$params['recordindex'];
        $params['pagesize'] = ($params['pagesize'] == "") ? self::PAGE_SIZE:(int)$params['pagesize'];
        // 判断该渠道商是否定制游戏列表 TODO
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:未开启私人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->search_model->get_search_list($params);
        if(empty($data['list'])) {
            //获取热门推荐
            $this->load->model('search_model');
            $params['orderby'] = 5; // 1: 按照热门游戏排序序号(默认) 2：游戏购买次数 3：游戏评分
            $params['type'] = 2; // 获取游戏表中的所有的热门游戏（包括免费和金币）
            $data = $this->search_model->hotrecommended_list($params);
        }
        
        $this->output_json_return($data);
    }
    /**搜索推荐关键字列表
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * token	string	登录身份令牌，必须
     * method	string	keywords_list:推荐关键字列表
     * keywords	string	关键字
     * sign	string	签名，必须
     * **/
    function keywords_list(){
        log_scribe('trace', 'params', 'keywords_list:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params             = $this->get_public_params_no_token();
        $params['keywords'] = urldecode($this->request_param('keywords'));
        $params['version']  = $this->request_param('version');
        $params['sign']     = $this->request_param('sign');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if($params['keywords'] == ""){
            $this->error_->set_error(Err_Code::ERR_SEARCH_KEYWORDS_NONE);
            $this->output_json_return();
        }
        // 判断该渠道商是否定制游戏列表 TODO
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:未开启私人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $list = $this->search_model->get_keywords_list($params);
        if ($list && is_array($list)) {
            foreach ($list as $v) {
                $data['list'][] = $v['keywords'];
            }
        }
        
        $this->output_json_return($data);
    }
    /**搜索关键字排行
     * uuid	int	用户唯一标识id，必须
     * app_id	int	发送请求方app的id，用来唯一标识app的id号
     * device_id	string	设备号，必须
     * token	string	登录身份令牌，必须
     * method	string	keywords_ranking:分类游戏列表
     * sign	string	签名，必须
     * **/
    function keywords_ranking(){
        log_scribe('trace', 'params', 'keywords_ranking:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params             = $this->get_public_params_no_token();
        $params['version']  = $this->request_param('version');
        $params['sign']     = $this->request_param('sign');
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 统计，点击搜索按钮，次数
        $time = date('Y-m-d 00:00:00', time());
        $this->utility->button_statistics('B_SEARCH', $time);
        
        $data['list'] = $this->search_model->get_keywords_ranking($params);
        $this->output_json_return($data);
    }
    
    
    /**
     * 热门游戏推荐
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      hot_list:热门游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    每页请求最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 热门游戏推荐
     */
    public function hotrecommended_list()
    {
        log_scribe('trace', 'params', 'hotrecommended_list:'.$this->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['type']        = $this->request_param('type'); // 0：免费游戏、1：收费
        $params['recordindex'] = (int)$this->request_param('recordindex');
        $params['pagesize']    = (int)$this->request_param('pagesize');
        $order_type            = $this->request_param('order_type'); 
        $params['version']     = $this->request_param('version');
        
        if (isset($order_type) && $order_type) {
            $params['order_type'] = $order_type;
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['pagesize'] == '') {
            $params['recordindex'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['type']) || $params['type'] === '') {
            $params['type'] = 2; // 0：免费游戏、1：收费 2:所有游戏
        }
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 5;// 默认按照排序序号排序  T_ORDERBY // 1: 按照热门游戏排序序号 2：游戏购买次数 3：游戏评分
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 判断该渠道商是否定制游戏列表 TODO
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:未开启私人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        
        $data = $this->search_model->hotrecommended_list($params);
        $this->output_json_return($data);
    }
}