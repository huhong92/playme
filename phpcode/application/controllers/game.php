<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Game extends P_Controller {
    const PAGE_SIZE = 10;
    function __construct() {
        parent::__construct(false);
        $this->load->model('game_model');
        $this->load->model('bailu_model');
        $this->load->model('user_model');
    }
    
    function index() {
//        $url	= "http://172.18.67.26:82/bailiangames/public/recharge_egret.html?".http_build_query($_GET);
//        echo "<a href='".$url."'>".$url."</a>";EXIT;
        $this->template->load('template', 'index/test');
    }
    
    /**
     * 获取所有列表
     */
    public function allgame()
    {
        $list   = $this->game_model->game_all_list();
        $this->template->load('template', 'game/test',array('list'=>$list));
    }
    
    /**
     * 返回可制作的游戏接口
     * @param   int     $uuid       用户唯一标示id 必须
     * @param   int     $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id  设备型号，必须
     * @param   string  $token      登录身份令牌，必须
     * @param   string  $method     produce_game:可制作游戏
     * @param   string  $sign	     签名，必须
     * @return  json                返回可制作的游戏
     */
    public function produce_game()
    {
        log_scribe('trace', 'params', 'produce_game:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params_no_token();
        $params['recordindex']  = $this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        if ($params['recordindex'] === '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if (!$params['pagesize']) {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        $data = $this->game_model->produce_game($params);
        if (!$data) {
            $this->output_json_return();
        }
        foreach ($data['list'] as $k=>&$v) {
            $v['bg']                = $this->passport->get('game_url').$v['bg'];
            $v['game_directory']    = $this->passport->get('game_url').$v['game_directory'].'play/index.html';
        }
        $this->output_json_return($data);
    }
    
    /**
     * 制作游戏帽子列表
     */
    public function hat_list()
    {
        log_scribe('trace', 'params', 'hat_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params_no_token();
        $params['recordindex']  = (int)$this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        if ($params['recordindex'] === '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        if (!$params['pagesize']) {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        $this->game_model->start();
        $data = $this->game_model->hat_list($params);;
        foreach ($data['list'] as $k=>&$v) {
            $v['pic']['small']  = $this->passport->get('game_url').$v['small'];
            $v['pic']['medium'] = $this->passport->get('game_url').$v['medium'];
            $v['pic']['large']  = $this->passport->get('game_url').$v['large'];
            unset($v['small']);
            unset($v['medium']);
            unset($v['large']);
        }
        if (!$data['list']) {
            $this->game_model->error();
            $this->output_json_return();
        }
        
        $this->game_model->success();
        $this->output_json_return($data);
    }
    
    /**
     * 制作游戏
     * @param   int     $uuid       用户唯一标示id 必须
     * @param   int     $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id  设备型号，必须
     * @param   string  $token      登录身份令牌，必须
     * @param   string  $method     produce_submit:制作游戏提交
     * @param   int     $id         游戏id  
     * @param   string  $name       游戏名称
     * @param   binary  $filename   游戏自拍图片
     * @param   string  $sign	     签名，必须
     * @return  json                提交我制作的游戏
     */
    public function produce_submit()
    {
        log_scribe('trace', 'params', 'produce_submit:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params             = $this->get_public_params();
        $params['id']       = $this->request_param('id');
        $params['name']     = urldecode($this->request_param('name'));
        $params['hat_id']   = $this->request_param('hat_id');
        if ($params['id'] == '' || $params['name'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        // 校验游戏名称，是否是非法字符
        if (in_array($params['name'], $this->utility->get_illegal_char())) {
            $this->error_->set_error(Err_code::ERR_ILLEGAL_CHAR);
            $this->output_json_return();
        }
        
        // 校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        // 校验图片
        $params['filename'] = $_FILES['filename'];
        if (empty($params['filename']['name'])) {
            $this->error_->set_error(Err_Code::ERR_UPLOAD_IMAGE_IS_NULL);
            $this->output_json_return();
            return false;
        }
        $allow_img = array('jpg', 'png', 'jpeg', 'gif');
        $ext = pathinfo($params['filename']['name'], PATHINFO_EXTENSION);
        log_scribe('trace', 'params', 'produce_submit:'.$this->game_model->ip.'  params：'.$params['filename']['name']);
        if (!in_array(strtolower($ext) , $allow_img) || empty($params['filename'])) {
            $this->error_->set_error(Err_Code::ERR_IMAGE_FORMAT_INCORRECT);
            $this->output_json_return();
            return false;
        }
        
        $ftp_config = $this->passport->get('ftp_config');
        try{
            $conn_id      = ftp_connect($ftp_config['ip']) or die("Could not connect to ".$ftp_config['ip']);
            $login_result = ftp_login($conn_id, $ftp_config['ftp_user'],$ftp_config['ftp_pass']);
            if (!$conn_id || !$login_result) {
                $this->error_->set_error(Err_Code::ERR_FTP_CONNECT_FAIL);
                $this->output_json_return();
                return false;
            } else {
                $source_path = $_FILES['filename']['tmp_name'];
                $dest_path   = '/upload/'.date('Ymd', time()).'/'.time().'-'.$params['uuid'].'.'.$ext;
                // 检测远程FTP服务器上上传目录是否存在
                $url = $this->passport->get('game_url').'/upload/'.date('Ymd', time());
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_NOBODY, 1); // 不下载内容，只测试远程ftp
                curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $res = curl_exec($ch);
                if ($res == false) {
                    @ftp_mkdir($conn_id, '/upload/'.date('Ymd', time()));
                }
                $upload      = @ftp_put($conn_id, $dest_path, $source_path, FTP_BINARY);
                ftp_close($conn_id);

                if (!$upload) {
                   $this->error_->set_error(Err_Code::ERR_FILE_UPLOAD_FAIL);
                   $this->output_json_return();
                   return false;
               }

               $params['filename'] = $dest_path;
            }
        } catch (Exception $ex) {
            $this->error_->set_error(Err_Code::ERR_FILE_UPLOAD_FAIL);
            $this->output_json_return();
        }
        
            
        $this->game_model->start();
        $params['hat_id']   = (int)$params['hat_id'];
        $data = $this->game_model->produce_submit($params);
        if(!$data) {
            $this->game_model->error();
            $this->output_json_return();
        }
        $this->game_model->success();
        
        // 制作游戏提交之后， 调用任务
        $this->game_model->start();
        $this->tasklib->task_make_game($params['uuid']);
        $this->game_model->success();
        $this->output_json_return($data);
    }
    
    /**
     * 删除制作的游戏
     * @param   int     $uuid       用户唯一标示id 必须
     * @param   int     $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id  设备型号，必须
     * @param   string  $token      登录身份令牌，必须
     * @param   string  $method     delete_produce:删除制作游戏
     * @param   int     $id         游戏id  
     * @param   string  $sign	     签名，必须
     * @return  json                删除制作的游戏
     */
    public function delete_produce()
    {   
        log_scribe('trace', 'params', 'delete_produce:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params       = $this->get_public_params();
        $params['id'] = (int)$this->request_param('id');
        
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $this->game_model->start();
        $data = $this->game_model->delete_produce($params);
        
        if (!$data) {
            $this->game_model->error();
            $this->output_json_return();
        }
        
        $this->game_model->success();
        $this->output_json_return($data);
    }
    
    /**
     * 制作白鹭游戏列表接口
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      produce_list:制作游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  version	      版本号 可选
     * @param   string  $sign	      签名，必须
     * @return  json                 返回制作游戏列表
     */
    public function bailu_list()
    {
        log_scribe('trace', 'params', 'bailu_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params_no_token();
        $params['recordindex']  = (int)$this->request_param('recordindex');
        $params['pagesize']     = (int)$this->request_param('pagesize');
        $params['order_type']   = $this->request_param('order_type');
        $params['version']      = $this->request_param('version');
        //校验参数
        if ($params['recordindex'] === '' || $params['pagesize'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['order_type'] == '') {
            $params['order_type'] = 0;
        }
        // $data = $this->game_model->hot_list($params);
        //请求白鹭获取列表
        $gameList = $this->utility->get('http://api.open.egret.com/Channel.gameList' , 'app_id='.$this->passport->get('channel'));
        if(!$gameList) 
        {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            $this->output_json_return();
        }
        $gameList = json_decode($gameList ,TRUE);
        $gameList = $gameList['game_list'];
        foreach ($gameList as $k => $v)
        {
            $bailuGameList[$v['gameId']] = $v;
        }
        //获取白鹭游戏在playme上的id     $id_list(pl_game表中id => 白鹭平台商id)
        $id_list = $this->bailu_model->select_bailu_gameid();
        if(!$id_list) $this->output_json_return();
        //排序控制数组
        $order_by     = array(
            0 => 'IDX ',
            1 => 'G_UPTIMEORDERBY DESC',
            2 => 'G_BUYNUM DESC',
            3 => 'G_GAMESTAR DESC',
            4 => 'G_PLAYNUM DESC',
        );
        //查询playme上的白鹭游戏数据(排序用)
        $type4Array = $this->bailu_model->select_bailu_game($order_by[$params['order_type']] , $params['recordindex'] ,$params['pagesize']);
        if(!$type4Array) $this->output_json_return();
        //判断登录状态
        $re_login = TRUE;
        if($params['token'] && ($this->is_login($params['uuid'], $params['device_id'], $params['token'])))
        {
                $re_login = FALSE;
                $keyData['appId']  = $this->passport->get('channel');
                $keyData['time']   = time();
                $keyData['userId'] = $params['uuid'];
                $keyData['sign']   = $this->signkey->bailu_sign($keyData);//验证bailu签名
                $userInfo = $this->user_model->get_user_info_by_uuid($params['uuid']);
                if(!$userInfo) $this->output_json_return();
                $keyData['userName'] = $userInfo['nickname'];
                $keyData['userImg']  = $userInfo['image'];
                $keyData['userSex']  = $userInfo['gender'];
                $qry_str = http_build_query($keyData);
        }
        //在白鹭返回的信息中按照白鹭平台ID提取出相应字段,并序列化数组
        foreach($type4Array as $k => &$v)
        {
            //判断游戏在白鹭平台是否下架
            if($bailuGameList[$id_list[$v['id']]]['name'] === NULL )
            {
                unset($type4Array[$k]);
                continue;
            }
//            $type4Array_order[$i] = $v;
            $v['name'] = $bailuGameList[$id_list[$v['id']]]['name'];
            $v['intro'] = $bailuGameList[$id_list[$v['id']]]['desc'];
            $v['guide'] = $bailuGameList[$id_list[$v['id']]]['shortDesc'];
            $v['category'][0] = $bailuGameList[$id_list[$v['id']]]['type'];
            $v['play_num'] = $bailuGameList[$id_list[$v['id']]]['played'];
            $v['logo'] = $bailuGameList[$id_list[$v['id']]]['icon'];
            if(!$re_login)
            {
                $v['game_directory'] = $bailuGameList[$id_list[$v['id']]]['url']. '?' . $qry_str;
            }
            else
            {
                $v['game_directory'] = $bailuGameList[$id_list[$v['id']]]['url'];
            }
            $v['type'] = 4;
        }
        //以recordindex截取数组
        $type4Array_order = array_slice($type4Array,$params['recordindex'] , $params['pagesize']);
        $data['pagecount']  = (int)ceil(count($type4Array) / $params['pagesize']);
        $data['list']       = $type4Array_order;
        $this->output_json_return($data);
    }
    
    /**
     * 制作游戏列表接口
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      produce_list:制作游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回制作游戏列表
     */
    public function produce_list()
    {
        log_scribe('trace', 'params', 'produce_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        
        $params                = $this->get_public_params();
        $params['recordindex'] = (int)$this->request_param('recordindex');
        $params['pagesize']    = (int)$this->request_param('pagesize');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['order_type']) || empty($params['order_type']) || $params['order_type'] == 1) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        
        $data = $this->game_model->produce_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏列表接口-免费游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      free_list:免费游戏列表
     * @param   int     $recordindex  每页请求开始位置 必须
     * @param   int     $pagesize     没请也请最大长度 必须
     * @param   string  $sign         签名，必须
     * @return  json                  返回免费游戏列表
     */
    public function free_list()
    {
        log_scribe('trace', 'params', 'free_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['recordindex'] = (int)$this->request_param('recordindex');
        $params['pagesize']    = (int)$this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        
        $data = $this->game_model->free_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏列表接口-金币游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      coin_list:金币游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回金币游戏列表
     */
    public function coin_list()
    {
        log_scribe('trace', 'params', 'coin_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['recordindex'] = (int)$this->request_param('recordindex');
        $params['pagesize']    = (int)$this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        if ($params['recordindex'] === '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 统计，点击游戏中心按钮，次数 channel_id :1:ios 2:Android 3:-----10都是预留渠道ID
        if ($params['channel_id'] == '1' || $params['channel_id'] == '2') {
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_GAMECENTER', $time);
        }
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            if ($params['uuid'] && $params['token']) {
                $params['orderby'] = 7;// 默认排序，购买在上，未购买在下，购买按购买时间倒序
            } else {
                $params['orderby'] = 1;// 默认排序，按照游戏上架时间
            }
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->coin_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏列表接口-我玩过的游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      myplay_list:我玩过的游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回我玩过的游戏列表
     */
    public function myplay_list()
    {
        log_scribe('trace', 'params', 'myplay_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        
        $params                = $this->get_public_params();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $params['version']     = $this->request_param('version');
        $order_type            = $this->request_param('order_type');
        
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->myplay_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏列表接口-我收藏的游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      favorite_list:我收藏的游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回我收藏的游戏列表
     */
    public function favorite_list()
    {
        log_scribe('trace', 'params', 'favorite_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->favorite_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏列表接口-我评论的游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      comment_game_list:我评论的游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回我收藏的游戏列表
     */
    public function comment_game_list()
    {
        log_scribe('trace', 'params', 'comment_game_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制 TODO
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->comment_game_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏列表接口-热门游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      hot_list:热门游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    每页请求最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回热门游戏列表
     */
    public function hot_list()
    {
        log_scribe('trace', 'params', 'hot_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $type                  = $this->request_param('type'); // 0：免费游戏、1：收费 3:所有
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');

        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
        if ($type !== false) {
            $params['type'] = $type;
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 统计，点击游戏中心按钮，次数
        if ($params['channel_id'] == '1' || $params['channel_id'] == '2') {
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_GAMECENTER', $time);
        }
        $params['recordindex'] = (int)$params['recordindex'];
        $params['pagesize']    = (int)$params['pagesize'];
        
        if ($params['recordindex'] == '') {
            $params['recordindex'] = 0;
        }
        
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['type']) || $params['type']==='') {
            $params['type'] = 3; // 0：免费游戏、1：收费 3 所有游戏
        }
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 游戏上架排序时间(控制游戏列表排序,倒序) G_UPTIMEORDERBY
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        
        $data = $this->game_model->hot_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 热门排行（游戏总排行榜）
     */
    public function game_orderby()
    {
        log_scribe('trace', 'params', 'game_orderby:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['type']        = $this->request_param("type"); // 游戏类型 0：免费 1：金币
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $params['order_type']  = $this->request_param('order_type');// 0:打开的次数 1：游戏评分  2 购买次数
        $params['version']     = $this->request_param('version');
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['channel_id'] == '1' || $params['channel_id'] == '2') {
            // 统计，点击排行，次数
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_RANKING', $time);
        }
        if (!isset($params['type']) || $params['type'] === '') {
            $params['type'] = 0; // 0：免费游戏、1：收费 2 所有游戏
        } else {
            $params['type'] = $params['type'];
        }
        
        $params['recordindex'] = (int)$params['recordindex'];
        $params['pagesize']    = (int)$params['pagesize'];
        
        if ($params['recordindex'] == '') {
            $params['recordindex'] = 0;
        }
        
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        $order_type = $params['order_type'];
        if (!isset($order_type) || $order_type === '') {
            $params['orderby'] = 4;// 游戏排行  4:打开的次数 3：游戏评分  2 购买次数
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->game_orderby($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏列表接口-新品游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      new_list:新品游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回新品游戏列表
     */
    public function new_list()
    {
        log_scribe('trace', 'params', 'new_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $type                  = $this->request_param('type'); // 0：免费游戏、1：收费 3:所有
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
        if ($type !== false) {
            $params['type'] = $type;
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        if ($params['channel_id'] == '1' || $params['channel_id'] == '2') {
            // 统计，点击游戏中心按钮，次数
            $time = date('Y-m-d 00:00:00', time());
            $this->utility->button_statistics('B_GAMECENTER', $time);
        }
        $params['recordindex'] = (int)$params['recordindex'];
        $params['pagesize']    = (int)$params['pagesize'];
        
        if ($params['recordindex'] == '') {
            $params['recordindex'] = 0;
        }
        
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        if (!isset($params['type']) || $params['type']==='') {
            $params['type'] = 3; // 0：免费游戏、1：收费 3 所有游戏
        }
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        
        $data = $this->game_model->new_list($params);
        $this->output_json_return($data);
    }
    
     /**
     * 分类游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      category_game_list:分类游戏列表
     * @param   int     $category    1：动作……
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回分类游戏列表
     */
    public function category_game_list()
    {
        log_scribe('trace', 'params', 'category_game_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['category']    = $this->request_param('category');
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->category_game_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 下载游戏接口
     */
    public function download()
    {
        log_scribe('trace', 'params', 'download:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params             = $this->get_public_params();
        $params['id']       = (int)$this->request_param('id'); // 游戏id
        $params['version']  = $this->request_param('version');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['nickname'] = $this->utility->get_user_info($params['uuid'],'nickname');
        
        $this->game_model->start();
        $data = $this->game_model->download($params);
        if (!$data) {
            $this->game_model->error();
            $this->output_json_return();
        }
        
        $this->game_model->success();
        // 下载游戏成功之后， 调用任务
        $this->game_model->start();
        $this->tasklib->task_download_game($params['uuid']);
        $this->game_model->success();
        
        $this->output_json_return();
    }
    
    /**
     * 我下载过的游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      download_list:分类游戏列表
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回下载过的游戏列表
     */
    public function download_list()
    {
        log_scribe('trace', 'params', 'download_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->download_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 我购买过的游戏列表
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      buy_list:购买过
     * @param   int     $recordindex 每页请求开始位置 必须
     * @param   int     $pagesize    没请也请最大长度 必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回下载过的游戏列表
     */
    public function buy_list()
    {
        log_scribe('trace', 'params', 'download_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->buy_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 游戏详情接口
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      detail:游戏详情
     * @param   int     $id          游戏id，必须
     * @param   string  $sign	      签名，必须
     * @return  json                 返回游戏详情信息
     */
    public function detail()
    {
        log_scribe('trace', 'params', 'detail:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params       = $this->get_public_params_no_token();
        $params['id'] = (int)$this->request_param('id');
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        if ($params['channel_id']) {
            // 查询该渠道商是否开启游戏列表私人定制
            $channel_info = $this->game_model->get_channel_info($params['channel_id']);
            if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
                $params['custom_game'] = 1;
            }
        }
        $data = $this->game_model->detail($params);

        if($data['type'] == 4)
        {
            $re_login = TRUE;
            if($params['token'])
            {
                if($this->is_login($params['uuid'], $params['device_id'], $params['token']))
                {
                    $re_login = FALSE;
                }
            }
            if($re_login)
            {
                $this->error_->set_error(Err_Code::ERR_TOKEN_EMPTY);
                $this->output_json_return();
            }
            $idArray = $this->bailu_model->select_bailu_gameid('B_GAMEID = '.$params['id']);
            if(!$idArray) $this->output_json_return();
            $gameList = $this->utility->get('http://api.open.egret.com/Channel.gameList' , 'app_id='.$this->passport->get('channel'));
            if(!$gameList) 
            {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                $this->output_json_return();
            }
            $gameList = json_decode($gameList ,TRUE);
            foreach($gameList['game_list'] as $k => $v)
            {
                $playme_id = array_search($v['gameId'], $idArray);
                if($v['gameId'] == $idArray[$playme_id])
                {
                    $keyData['appId'] = $this->passport->get('channel');
                    $keyData['time'] = time();
                    $keyData['userId'] = $params['uuid'];
                    $keyData['sign'] = $this->signkey->bailu_sign($keyData);//验证bailu签名
                    $userInfo = $this->user_model->get_user_info_by_uuid($params['uuid']);
                    if(!$userInfo)
                        $this->output_json_return();
                    $keyData['userName'] = $userInfo['nickname'];
                    $keyData['userImg'] = $userInfo['image'];
                    $keyData['userSex'] = $userInfo['gender'];
                    $qry_str = http_build_query($keyData);
                    $data['game_directory'] = $v['url']. '?' . $qry_str;
                    $data['logo'] = $v['icon'];
                    $bailu_gameid = $v['gameId'];
                }
            }
            foreach($data['screenshots'] as $k => $v)
            {
                $data['screenshots'][$k] = $this->passport->get('game_url').'/netgames/'.$bailu_gameid.'/'.($k + 1).'.png';
            }
        }
        $this->output_json_return($data);
    }
    
    /**
     * 玩家游戏排行接口（前十名，和自己的名次）
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      user_ranking:玩家游戏排行
     * @param   int     $id          游戏id，必须
     * @param   string  $sign	      签名，必须
     * @return  json                 玩家游戏排行信息
     */
    public function user_ranking()
    {
        log_scribe('trace', 'params', 'user_ranking:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params       = $this->get_public_params();
        $params['id'] = $this->request_param('id');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        $data = $this->game_model->user_ranking($params);
        
        if ($data['list']) {
            foreach ($data['list'] as $k=>$v) {
                if ($v['image']) {
                    $pos  =  strpos ($v['image'] ,  "http://" );
                    if ($pos === false) {
                        $data['list'][$k]['image'] = $this->passport->get('game_url').$v['image'];
                    }
                }
                if($v['integral'])
                {
                    $data['list'][$k]['grade'] = $this->utility->user_grade($v['integral']);
                }
            }
        }
        
        if ($data['mine']['image']) {
            $pos  =  strpos ($data['mine']['image'] ,  "http://" );
            if ($pos === false) {
                $data['mine']['image'] = $this->passport->get('game_url').$data['mine']['image'];
            }
        }
        if ($data['mine']['integral']) {
            $data['mine']['grade'] = $this->utility->user_grade($data['mine']['integral']);
        }
        
        $this->output_json_return($data);
    }
    
    /**
     * 上传得分接口
     * @param   int     $uuid          用户唯一标示id 必须
     * @param   int     $app_id        发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id     设备型号，必须
     * @param   string  $token         登录身份令牌，必须
     * @param   string  $method        upload_scoring:上传得分
     * @param   int     $id            游戏id，必须
     * @param   int     $update_status 游戏更新类型
     * @param   float   $scoring       游戏得分
     * @param   string  $sign          签名，必须
     * @return  json                   返回用户信息
     */
    public function upload_scoring()
    {
        log_scribe('trace', 'params', 'upload_scoring:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                  = $this->get_public_params();
        $params['id']            = $this->request_param('id');
        $params['scoring']       = $this->request_param('scoring');
        $params['spend_time']    = $this->request_param('spend_time');// 玩游戏花费时间
        $params['update_status'] = $this->request_param('update_status');
        if ($params['id'] == '' || $params['scoring'] === '' || $params['spend_time'] == '' ) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['update_status'] = (int)$params['update_status'];// 0：在线更新 1：下载游戏更新
        $params['nickname']  = $this->utility->get_user_info($params['uuid'],'nickname');
        
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $this->game_model->start();
        $data = $this->game_model->upload_scoring($params);
        if (!$data) {
            $this->game_model->error();
            $this->output_json_return();
        }
        $this->game_model->success();
        
         // 每完成一个免费游戏之后, 调用一次免费游戏任务
        $this->game_model->start();
        $this->tasklib->task_free_game($params['uuid']);
        $this->game_model->success();
        
        $this->output_json_return();
    }
    
    /**
     *统计游戏游玩次数接口
     * @param   int     $uuid          用户唯一标示id 必须
     * @param   int     $app_id        发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id     设备型号，必须
     * @param   string  $token         登录身份令牌，必须
     * @param   string  $method        upload_scoring:上传得分
     * @param   int     $id            游戏id，必须
     * @param   string  $sign          签名，必须
     * @return  json                   返回用户信息
     */
    public function count_play_num()
    {
        log_scribe('trace', 'params', 'count_play_num:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                  = $this->get_public_params();
        $params['id']            = $this->request_param('id');
        
        if ($params['id'] == '' ) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $this->game_model->start();
        $data = $this->game_model->count_play_num($params);
        if (!$data) {
            $this->game_model->error();
            $this->output_json_return();
        }
        $this->game_model->success();
        $this->output_json_return();
    }
    
    /**
     * 购买游戏接口
     * @param   int     $uuid        用户唯一标示id 必须
     * @param   int     $app_id      发送请求方app的id，用来唯一标识app的id号，必须
     * @param   string  $device_id   设备型号，必须
     * @param   string  $token       登录身份令牌，必须
     * @param   string  $method      购买游戏
     * @param   int     $id          游戏id，必须
     * @param   int     $price_current    游戏当前价格
     * @param   string  $sign	     签名，必须
     * @return  json                返回是否购买成功
     */
    public function buy()
    {
        log_scribe('trace', 'params', 'buy:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                  = $this->get_public_params();
        $params['id']            = $this->request_param('id');
        $params['price_current'] = $this->request_param('price_current');
        if ($params['id'] == '' || !isset($params['price_current'])) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['id'] = (int)$params['id'];
        $params['price_current'] = (int)$params['price_current'];
        $this->game_model->start();
        //获取用户信息
        $user_info = $this->utility->get_user_info($params['uuid']);
        $data['user_info'] = $user_info;
        
        //校验游戏是否购买过
        $rst = $this->game_model->chk_game_is_buy($params['uuid'],$params['id']);
        if($rst){
            $this->error_->set_error(Err_Code::ERR_OK);
            $this->output_json_return($data);
        }
        $params['nickname'] = $user_info['nickname'];
        $params['coin'] = $user_info['coin'];
        
        //校验金币是否够
        if($params['coin'] < $params['price_current']){
            $this->error_->set_error(Err_Code::ERR_GAME_COIN_NOT_ENOUGH);
            $this->output_json_return();
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $rst = $this->game_model->buy($params);
        if(!$rst) {
            $this->game_model->error();
            $this->output_json_return();
        }
        
        $this->game_model->success();
        $data['user_info']['coin'] = $data['user_info']['coin'] - $params['price_current'];
        // 购买的金币游戏之后， 调用任务
        
        $this->game_model->start();
        $this->tasklib->task_buy_game($params['uuid']);
        $this->game_model->success();
        
        $this->output_json_return($data);
    }
    
    /**
     * 分享游戏接口
     * @param  int      $uuid       用户唯一标示id 必须
     * @param  int      $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param  string   $device_id  设备型号，必须
     * @param  string   $token	     登录身份令牌，必须
     * @param  string   $method     share：分享游戏接口
     * @param  int      $id         游戏id，必须
     * @param  int      $type       游戏类型 0:免费游戏1:金币游戏2:制作游戏
     * @param  int      $channel    分享渠道 0：微博 1：微信
     * @param  string   $sign       签名，必须
     * @return json                 
     */
    public function share()
    {
        log_scribe('trace', 'params', 'share:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params            = $this->get_public_params();
        $params['id']      = $this->request_param('id');     //游戏id
        $params['type']    = $this->request_param('type');   // 0:免费游戏1:金币游戏2:制作游戏
        $params['channel'] = $this->request_param('channel');// 0：微博，1：微信
        if ($params['id'] == '' || $params['type'] === '' || $params['channel'] === '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['id'] = (int)$params['id'];
        $params['type'] = (int)$params['type'];
        $params['channel'] = (int)$params['channel'];
        $this->game_model->start();
        $params['nickname'] = $this->utility->get_user_info($params['uuid'],'nickname');
        $share_url = $this->game_model->share($params);
        if(!$share_url){
            $this->game_model->error();
            $this->output_json_return();
        }
        
        $data['url'] = $share_url;
        $this->game_model->success();
        
        // 首次分享之后， 调用任务
        $this->game_model->start();
        $this->tasklib->task_share_game($params['uuid']);
        $this->game_model->success();
        
        $this->output_json_return($data);
    }
    
    /**
     * 游戏评分/评论接口
     * @param  int      $uuid       用户唯一标示id 必须
     * @param  int      $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param  string   $device_id  设备型号，必须
     * @param  string   $token	     登录身份令牌，必须
     * @param  string   $method     comment
     * @param  int      $id         游戏id，必须
     * @param  string   $content    游戏评论，必须
     * @param  int      $scoring    评的分数【1-5】,必须
     * @param  string   $sign       签名，必须
     * @return json                 返回制作游戏列表
     */
    public function comment()
    {
        log_scribe('trace', 'params', 'comment:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params();
        $params['id']          = $this->request_param('id');
        $params['content']     = $_REQUEST['content'];
        $params['content']     = urldecode($params['content']);
        $params['scoring']     = $this->request_param('scoring');

        if ($params['id'] == '' || $params['content'] == '' || $params['scoring'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $params['id'] = (int)$params['id'];
        //评论长度限制
        if ($params['content'] == "" || $this->utility->stringLen($params['content']) > 200) {
            $this->error_->set_error(Err_code::ERR_COMMENT_CONTENT_LEN);
            $this->output_json_return();
        }
        
        //评分参数校验
        if(!in_array($params['scoring'], $this->passport->get('score_array'))){
            $this->error_->set_error(Err_Code::ERR_COMMENT_SCORE_LIMIT);
            $this->output_json_return();
        }
        // 评论内容校验，是否是非法字符
        // 是否含有屏蔽字，有，改成*
        $illegal_char_info = $this->utility->get_illegal_char();
        $params['content'] = preg_replace($illegal_char_info, "*", $params['content'],$limit = -1, $count);
        
        $this->game_model->start();
        $params['nickname'] = $this->utility->get_user_info($params['uuid'],'nickname');
        if (!$params['nickname']) {
            $params['nickname'] = '';
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $rst = $this->game_model->comment($params);
        
        if(!$rst) {
            $this->game_model->error();
            $this->output_json_return();
        }
        $this->game_model->success();
        // 首次评论之后， 调用任务
        $this->game_model->start();
        $this->tasklib->task_comment_game($params['uuid']);
        $this->game_model->success();
        $this->output_json_return();
    }
    
    /**
     * 获取游戏的评论列表接口
     * @param  int      $uuid       用户唯一标示id 必须
     * @param  int      $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param  string   $device_id  设备型号，必须
     * @param  string   $token	     登录身份令牌，必须
     * @param  string   $method     comment_list
     * @param  int      $id         游戏id，必须
     * @param  int      $recordindex 每页请求开始位置，必须 （0开始）
     * @param  int      $pagesize    每页请求最大长度，必须
     * @param  string   $sign       签名，必须
     * @return json                 返回制作游戏列表
     */
    public function comment_list()
    {
        log_scribe('trace', 'params', 'comment_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params_no_token();
        $params['id']           = $this->request_param('id');
        $params['recordindex']  = $this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        $params['version']      = $this->request_param('version');
        
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['recordindex'] = (int)$params['recordindex'];
        $params['pagesize'] = ($params['pagesize'] == "") ? self::PAGE_SIZE:(int)$params['pagesize'];
        $data = $this->game_model->comment_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 收藏游戏接口
     * @param  int      $uuid       用户唯一标示id 必须
     * @param  int      $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param  string   $device_id  设备型号，必须
     * @param  string   $token	     登录身份令牌，必须
     * @param  string   $method     favorite
     * @param  int      $id         游戏id，必须
     * @param  string   $sign       签名，必须
     * @return json                 返回制作游戏列表
     */
    public function favorite()
    {
        log_scribe('trace', 'params', 'favorite:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params         = $this->get_public_params();
        $params['id']   = $this->request_param('id');
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $this->game_model->start();
        //校验游戏是否收藏过
        $f_id = $this->game_model->chk_game_favorite($params['uuid'],$params['id']);
        if($f_id) {
            $this->error_->set_error(Err_code::ERR_GAME_IS_FAVORITE);
            $this->output_json_return();
        }
        $params['nickname'] = $this->utility->get_user_info($params['uuid'],'nickname');
        $params['id']       = (int)$params['id'];
        $rst = $this->game_model->favorite($params);
        if(!$rst) {
            $this->game_model->error();
            $this->output_json_return();
        }
        $this->game_model->success();
        $this->output_json_return();
    }
    
    /**
     * 取消收藏游戏接口
     * @param  int      $uuid       用户唯一标示id 必须
     * @param  int      $app_id     发送请求方app的id，用来唯一标识app的id号，必须
     * @param  string   $device_id  设备型号，必须
     * @param  string   $token	     登录身份令牌，必须
     * @param  string   $method     delete_favorite
     * @param  int      $id         游戏id，必须
     * @param  string   $sign       签名，必须
     * @return json                 返回状态
     */
    public function delete_favorite()
    {
        log_scribe('trace', 'params', 'delete_favorite:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params         = $this->get_public_params();
        $params['id']   = $this->request_param('id');
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $params['id'] = (int)$params['id'];
        $this->game_model->start();
        $rst = $this->game_model->delete_favorite($params);
        if(!$rst) {
            $this->game_model->error();
            $this->output_json_return();
        }
        $this->game_model->success();
        $this->output_json_return();
    }
    
    /**
     * 更新制作游戏上传的图片
     */
    public function update_making_img()
    {
        log_scribe('trace', 'params', 'update_making_img:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        log_scribe('trace', 'params', 'update_making_img:'.$this->game_model->ip.'  params：'.http_build_query($_FILES));
        $params         = $this->get_public_params();
        $params['id']   = (int)$this->request_param('id');
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 校验图片
        $params['filename'] = $_FILES['filename'];
        if (empty($params['filename'])) {
            $this->error_->set_error(Err_Code::ERR_UPLOAD_IMAGE_IS_NULL);
            $this->output_json_return();
            return false;
        }
        $allow_img = array('jpg', 'png', 'jpeg', 'gif');
        $ext = pathinfo($params['filename']['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext) , $allow_img) || empty($params['filename'])) {
            $this->error_->set_error(Err_Code::ERR_IMAGE_FORMAT_INCORRECT);
            $this->output_json_return();
            return false;
        }
        
        $ftp_config = $this->passport->get('ftp_config');
        try{
            $conn_id      = ftp_connect($ftp_config['ip']) or die("Could not connect to ".$ftp_config['ip']);
            $login_result = ftp_login($conn_id, $ftp_config['ftp_user'],$ftp_config['ftp_pass']);

            if (!$conn_id || !$login_result) {
                $this->error_->set_error(Err_Code::ERR_FTP_CONNECT_FAIL);
                $this->output_json_return();
                return false;
            } else {
                $source_path = $_FILES['filename']['tmp_name'];
                $dest_path   = '/upload/'.date('Ymd', time()).'/'.time().'-'.$params['uuid'].'.'.$ext;

                // 检测远程FTP服务器上传目录是否存在
                $url = $this->passport->get('game_url').'/upload/'.date('Ymd', time());

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_NOBODY, 1); // 不下载内容，只测试远程ftp
                curl_setopt($ch, CURLOPT_FAILONERROR, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $res = curl_exec($ch);
                if ($res === false) {
                    @ftp_mkdir($conn_id, '/upload/'.date('Ymd', time()));
                }

                $upload      = @ftp_put($conn_id, $dest_path, $source_path, FTP_BINARY);
                ftp_close($conn_id);

                if (!$upload) {
                   $this->error_->set_error(Err_Code::ERR_FILE_UPLOAD_FAIL);
                   $this->output_json_return();
                   return false;
               }

               $params['filename'] = $dest_path; // 上传成功
            }
        } catch (Exception $ex) {
            $this->error_->set_error(Err_Code::ERR_FILE_UPLOAD_FAIL);
            $this->output_json_return();
        }
        
        $this->game_model->start();
        $res = $this->game_model->update_making_img($params);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_MAKING_IMAGE_FAIL);
            $this->output_json_return();
            return false;
        }
        $this->game_model->success();
        
        $data['pic']    = $this->passport->get('game_url').$params['filename'];
        $this->output_json_return($data);
    }
    
    /**
     * 为固定一个游戏，添加游戏排名
     */
    public function game_score_orderby()
    {
        $params                 = $this->get_public_params();
        $params['recordindex']  = $this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $params['recordindex']  = (int)$params['recordindex'];
        $params['pagesize']     = ($params['pagesize'] == "") ? self::PAGE_SIZE:(int)$params['pagesize'];
        
        $data = $this->game_model->game_score_orderby($params);
        
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            $this->output_json_return();
            return false;
        }
        
        $this->output_json_return($data);
    }
    
    /**
     * 获取过关游戏列表
     */
    public function clearance_list()
    {
        log_scribe('trace', 'params', 'clearance_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params();
        $params['recordindex'] = (int)$this->request_param('recordindex');
        $params['pagesize']    = (int)$this->request_param('pagesize');
        $params['version']     = $this->request_param('version');
        $order_type            = $this->request_param('order_type');
        
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->clearance_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 获取好友的成品室
     */
    public function get_other_user_produce()
    {
        $params                = $this->get_public_params();
        $params['ouser_id']    = $this->request_param('ouser_id');
        $params['recordindex'] = (int)$this->request_param('recordindex');
        $params['pagesize']    = (int)$this->request_param('pagesize');
        
        if (!$params['ouser_id'] || $params['recordindex'] === '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['order_type']) || empty($params['order_type']) || $params['order_type'] == 1) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        $params['uuid'] = $params['ouser_id'];
        $data = $this->game_model->produce_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 获取好友的最近玩过的游戏
     */
    public function get_user_play_game()
    {
        $params                = $this->get_public_params();
        $params['ouser_id']    = $this->request_param('ouser_id');
        $params['recordindex'] = (int)$this->request_param('recordindex');
        $params['pagesize']    = (int)$this->request_param('pagesize');
        if ($params['recordindex'] === '' || $params['ouser_id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        $params['uuid'] = $params['ouser_id'];
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->myplay_list($params);
        $this->output_json_return($data);
    }
    
    /**
     * 专题信息
     */
    public function theme()
    {
        $params = $this->get_public_params_no_token();
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        $data   = $this->passport->get('playe_theme');
        $this->output_json_return($data);
    }
    
    public function theme_list()
    {
        log_scribe('trace', 'params', 'clearance_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params_no_token();
        $params['id']           = $this->request_param('id');
        $params['recordindex']  = (int)$this->request_param('recordindex');
        $params['pagesize']     = (int)$this->request_param('pagesize');
        $order_type             = $this->request_param('order_type');
        if ($params['id'] == '' || $params['recordindex'] === '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        if ($params['pagesize'] == '') {
            $params['pagesize'] = self::PAGE_SIZE;
        }
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->theme_list($params);
        $this->output_json_return($data);
    }
    
    
    // ----------------------------------测试-----------------------------
    
    /**
     * 添加数据（游戏表）
     */
    public function game11()
    { 
        $params['G_NAME'] = $this->request_param('G_NAME'); // 游戏名称
        $params['G_FILEDIRECTORY'] = $this->request_param('G_FILEDIRECTORY');// 游戏存放的目录 如：/1/游戏名称
        $params['G_GAMETYPE'] = $this->request_param('G_GAMETYPE'); // '游戏类型0:免费1:金币 2：过关 3：收费',
        $params['G_GAMEGOLD'] = $this->request_param('G_GAMEGOLD'); // '游戏下载金币',
        $params['G_GAMEGOLDCURRENT'] = $this->request_param('G_GAMEGOLDCURRENT'); // '当前优惠价格',
         
        $params['G_HOT'] = $this->request_param('G_HOT'); // '是否热门游戏0:否1:是',
        $params['G_HOTORDERBY'] = $this->request_param('G_HOTORDERBY');// '热门游戏排序,顺序' 
        $params['G_TEMPLATE'] = $this->request_param('G_TEMPLATE');// 是否包括模板0:否1:是',
        $params['G_MAKINGGAMEPOINT'] = $this->request_param('G_MAKINGGAMEPOINT');// '制作游戏消耗玩家积分',
        $params['G_NEW'] = $this->request_param('G_NEW');// '是否新品游戏0:否1:是', 
        $params['G_GAMECATS'] = $this->request_param('G_GAMECATS');// '游戏所属分类id集合,格式,1,2,3,4,',   动作...
        $params['G_PLATFORMS'] = $this->request_param('G_PLATFORMS');// '游戏所属平台ID集合,格式,1,2,',
        $params['G_SCOREORDERBY'] = $this->request_param('G_SCOREORDERBY'); // '游戏积分排序规则0:顺序1:倒序',
        $params['G_GAMESCOREUNIT'] = $this->request_param('G_GAMESCOREUNIT'); // '游戏积分单位',
        $params['G_GAMESCOREMAX'] = $this->request_param('G_GAMESCOREMAX');// '游戏最好成绩(游戏积分)',
        $params['G_GAMESCOREMAXTIME'] = $this->request_param('G_GAMESCOREMAXTIME');// 游戏最好成绩用时(秒)',
        $params['G_GAMEPOINTGA'] = $this->request_param('G_GAMEPOINTGA');// ga系数 
        $params['G_INFO'] = $this->request_param('G_INFO');// '游戏介绍',
        $params['G_OPERATIONINFO'] =$this->request_param('G_OPERATIONINFO');// 游戏操作说明'
        $params['G_KEYS'] = $this->request_param('G_KEYS');// '游戏关键字,多个以,号分开',
        $params['G_CLOSE'] = (int)$this->request_param('G_CLOSE');// '游戏是否关闭0:否1:是',
        $params['G_IMGS'] = $this->request_param('G_IMGS');// '游戏截图文件名集合,多个以,号分开',
        $params['G_ICON'] = $this->request_param('G_ICON');// 游戏图标,文件名',
        $params['G_BUYNUM'] = (int)$this->request_param('G_BUYNUM');// 购买次数',
        $params['G_PLAYNUM'] = (int)$this->request_param('G_PLAYNUM');// '被玩次数(包括分享打开次数)',
        $params['G_SHARENUM'] = (int)$this->request_param('G_SHARENUM');// '游戏分享次数',
        $params['G_SHAREPLAYNUM'] =(int)$this->request_param('G_SHAREPLAYNUM');// '游戏分享被打开次数',
        $params['G_GAMESTAR'] = (int)$this->request_param('G_GAMESTAR');// '游戏综合评分',
        $params['G_GAMESTARNUM'] = (int)$this->request_param('G_GAMESTARNUM');// '游戏评分次数',
        $params['G_UPTIMEORDERBY'] = $this->request_param('G_UPTIMEORDERBY');// 游戏上架排序时间(控制游戏列表排序,倒序)',
        $params['STATUS'] = (int)$this->request_param('STATUS');// '状态0正常',
        $params['G_VERSION']    = "";
        $params['G_ISDEVELOPER']    = 0;
        $params['G_AUDIT']  = 0;
        
        $params['G_GAMEFILESIZE'] = $this->request_param('G_GAMEFILESIZE'); // '游戏下载文件大小(k)'
        if ($params['G_NAME'] == '' || $params['G_KEYS'] == ''|| $params['G_FILEDIRECTORY'] == '' || $params['G_GAMESCOREUNIT'] == '' || $params['G_GAMESCOREMAX'] == '' || $params['G_GAMESCOREMAXTIME']=='' || $params['G_GAMEPOINTGA']=='' || $params['G_INFO']=='' || $params['G_KEYS']=='' || $params['G_IMGS'] == '' ||$params['G_ICON'] =='' || $params['G_GAMECATS'] == '' || $params['G_PLATFORMS'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
            return false;
        }    
        
        if ($params['G_TEMPLATE'] != 2) {
            $params['G_FILEDIRECTORY'] = '/games'.$params['G_FILEDIRECTORY'];
        }
        
        // 判断远程文件大小
        if (!isset($params['G_GAMEFILESIZE']) || !$params['G_GAMEFILESIZE']) {
            
            $game_path = $this->passport->get('game_url').$params['G_FILEDIRECTORY'].'d.zip';
            log_scribe('trace','params',$game_path);
            $a_array = get_headers($game_path, true);
            $params['G_GAMEFILESIZE'] = $a_array['Content-Length']/1024;
            if (!$params['G_GAMEFILESIZE']) {
                $this->error_->set_error(Err_Code::ERR_GAME_FILE_NOT_FOUND);
                $this->output_json_return();
                return false;
            } else {
                $params['G_GAMEFILESIZE'] = round($params['G_GAMEFILESIZE'], 2);
            }
        }
        
        // start 设置默认值
        if (!isset($params['G_GAMETYPE'])) {
            $params['G_GAMETYPE'] = 0;
        }
        
        if (!isset($params['G_GAMEGOLD'])) {
            $params['G_GAMEGOLD'] = 0;
        }
        
        if (!isset($params['G_GAMEGOLDCURRENT'])) {
            $params['G_GAMEGOLDCURRENT'] = 0;
        }
        
        if (!isset($params['G_GAMETYPE'])) {
            $params['G_GAMETYPE'] = 0;
        }
        
        if (!isset($params['G_HOT'])) {
            $params['G_HOT'] = 1;
        }
        
        if (!isset($params['G_HOTORDERBY'])) {
            $params['G_HOTORDERBY'] = 1;
        }
        
        if (!isset($params['G_TEMPLATE'])) {
            $params['G_TEMPLATE'] = 0;
        }
        
        if (!isset($params['G_MAKINGGAMEPOINT'])) {
            $params['G_MAKINGGAMEPOINT'] = 0;
        }
        
        if (!isset($params['G_NEW'])) {
            $params['G_NEW'] = 0;
        }
        
        
        if (!isset($params['G_SCOREORDERBY'])) {
            $params['G_SCOREORDERBY'] = 0;
        }
        
        if (!isset($params['G_BUYNUM'])) {
            $params['G_BUYNUM'] = 0;
        }
        if (!isset($params['G_PLAYNUM'])) {
            $params['G_PLAYNUM'] = 0;
        }
        if (!isset($params['G_SHARENUM'])) {
            $params['G_SHARENUM'] = 0;
        }
        if (!isset($params['G_SHAREPLAYNUM'])) {
            $params['G_SHAREPLAYNUM'] = 0;
        }
        if (!isset($params['G_GAMESTAR'])) {
            $params['G_GAMESTAR'] = 0;
        }
        if (!isset($params['G_GAMESTARNUM'])) {
            $params['G_GAMESTARNUM'] = 0;
        }
        if (!isset($params['G_UPTIMEORDERBY']) || $params['G_UPTIMEORDERBY'] == "") {
            $params['G_UPTIMEORDERBY'] = date('Y-m-d H:i:s', time());
        }
        if (!isset($params['STATUS'])) {
            $params['STATUS'] = 0;
        }
        
        if (!isset($params['G_OPERATIONINFO'])) {
            $params['G_OPERATIONINFO'] = '';
        }
        
        // 判断该游戏名，是否已经存在表中
        $game = $this->game_model->if_exists_game_name($params['G_NAME']);
        
        if ($game['game_id']) {
            $params['is_existst'] = 1; // 存在游戏，更新
            // 判断该游戏是否是破纪录，如果是，全部更新，否则，不更新最高分和时间参数等
              // 1获取游戏是正序，还是倒序  G_SCOREORDERBY
               if ($game['order_type'] ==  1) { // 倒序（分数越低，破纪录）
                   if ($params['G_GAMESCOREMAX'] < $game['game_max']) { // 破纪录
                       $params['is_top'] = 1;// 破纪录
                   } else {// 没破纪录
                       $params['is_top'] = 0;
                   }
               } else { // 正序
                   if ($params['G_GAMESCOREMAX'] > $game['game_max']) { // 破纪录
                       $params['is_top'] = 1;// 破纪录
                   } else { // 没破纪录
                       $params['is_top'] = 0;
                   }
               }
        } else {
            $params['is_existst'] = 0; // 不存在游戏，插入
        }
        
        // end 设置默认值
        $data = $this->game_model->insert_game($params);
        $this->output_json_return($data);
    }
    
    /**
     * 开发者，商户后台调用
     * 预下订单(1.先直接下单)
     */
    public function prepare_buy() {
        $params = array(
            'uuid'      => (int)$this->request_param('uuid'),
            'device_id' => $this->request_param('device_id'),
            'token'     => $this->request_param('token'),
            'method'    => $this->request_param('method'),
            'sign'      => $this->request_param('sign'),
        );
        
        $params['developer_id']   = $this->request_param('developer_id');
        $params['total_fee']    = $this->request_param('total_fee');
        $params['prop_id']      = $this->request_param('prop_id');
        $params['game_join_id'] = $this->request_param('game_join_id');
        $params['subject']      = $this->request_param('subject');
        $params['description']  = $this->request_param('description');
        $params['timestamp']    = $this->request_param('timestamp');
        $params['nonce']        = $this->request_param('nonce');
        if($params['uuid'] == "" || $params['device_id']== "" ||  $params['token'] == "" || $params['method'] == "" || $params['sign'] == "" || $params['total_fee'] == '' || $params['prop_id'] == '' || $params['game_join_id'] == '' || $params['timestamp'] == '' || $params['nonce'] == '' || $params['sign'] == '' || $params['developer_id'] == ''){
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验token是否有效
        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
            $this->output_json_return();
        }
        // 获取开发者ID对应的KEY
        $this->load->model('user_model');
        $qualificate_info = $this->user_model->get_developer_key($params['developer_id']);
        if (!$qualificate_info) {
            $this->output_json_return();
        }
        $params['sign_key'] = $qualificate_info['developer_key'];
        //2.校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 3.获取货币数量（1元 = 开发商货币），存入购买道具订单表中
        $open_game_info = $this->game_model->get_open_game_info($params['game_join_id']); // 该值表示 1人名币 = X 货币
        if (!$open_game_info) {
            $this->error_->set_error(Err_Code::ERR_JOIN_GAME_RATE_FAIL);
            $this->output_json_return();
        }
        $rate_res = (1/$open_game_info['rate']); // 汇率
        $params['total_gold'] = $params['total_fee']*$rate_res*10; // 最终单位：元
        
        // 4.生成预计购买道具订单
        $params['nickname'] = $this->utility->get_user_info($params['uuid'], 'nickname');
        
        if (!$params['description']) {
            $params['description'] = '';
        }
        $fields = array(
            'P_USERIDX'         =>  $params['uuid'],
            'P_NICKNAME'        =>  $params['nickname'],
            'P_PROPIDX'         =>  $params['prop_id'],
            'P_TOTALFEE'        =>  $params['total_fee'],
            'P_TOTALGOLD'       =>  $params['total_gold'],
            'P_GAMEJOINID'      =>  $params['game_join_id'],
            'P_SUBJECT'         =>  $params['subject'],
            'P_DECRIPTION'      =>  $params['description'],
            'P_NOTIFYURL'       =>  '',
            'P_TIMESTAMP'       =>  $params['timestamp'],
            'P_NONCE'           =>  $params['nonce'],
            'P_BUYSTATUS'       =>  2, // 0:成功, 1:失败, 2:等待结果中(未购买状态)
            'STATUS'            =>  0,
        );
        $order_id = $this->game_model->game_propbuy($fields);
        if (!$order_id) {
            $this->output_json_return();
        }
        
        // 5.返回预计订单是否成功，返回订单信息，以及货币兑换信息
        $ret['order_id']     = $order_id;
        $ret['prop_id']      = $params['prop_id'];
        $ret['total_gold']   = $params['total_gold'];
        $ret['total_fee']    = $params['total_fee'];
        $this->output_json_return($ret);
    }
    
    /**
     * 购买道具,支付接口（2.先判断金币是否足够，3.是否执行购买操作）
     */
    public function action_buy() {
        $params = array(
            'uuid'      => (int)$this->request_param('uuid'),
            'device_id' => $this->request_param('device_id'),
            'token'     => $this->request_param('token'),
            'method'    => $this->request_param('method'),
            'sign'      => $this->request_param('sign'),
        );
        $params['developer_id']   = $this->request_param('developer_id');
        $params['order_id'] = $this->request_param('order_id');
        $params['nonce']    = $this->request_param('nonce');
        
        // 1.校验参数
        if ($params['uuid'] == "" ||  $params['device_id']== "" || $params['token'] == '' || $params['method'] == '' || $params['order_id'] == '' || $params['nonce'] == '' || $params['sign'] == '' || $params['developer_id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验token是否有效
        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
            $this->output_json_return();
        }
        // 获取开发者ID对应的KEY
        $this->load->model('user_model');
        $qualificate_info = $this->user_model->get_developer_key($params['developer_id']);
        if (!$qualificate_info) {
            $this->output_json_return();
        }
        $params['sign_key'] = $qualificate_info['developer_key'];
        //2.校验签名
        $params['sign_key'] = $qualificate_info['developer_key'];
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 3.校验该用户金币，是否跟预订单金币相同，然后判断金币是否足够
        $user_info = $this->utility->get_user_info($params['uuid']);
        // 获取预下订单（购买道具）信息
        $propbuy_info = $this->game_model->get_propbuy_info($params['order_id']);
        if (!$propbuy_info) {
            $this->output_json_return();
        }
        // 校验金币是否够
        if($user_info['coin'] < $propbuy_info['total_gold']){
            $this->error_->set_error(Err_Code::ERR_GAME_COIN_NOT_ENOUGH);
            $this->output_json_return();
        }
        
        // 4.执行购买操作，修改订单状态
        $this->game_model->start();
        $propbuy_upt = $this->game_model->update_propbuy_info($params['order_id'], 0); // 0:成功, 1:失败, 2:等待结果中
        if (!$propbuy_upt) {
            $this->game_model->error();
            $this->error_->set_error(Err_Code::ERR_PROP_BUY_FAIL);
            $this->output_json_return();
        }
        // 5.修改用户信息，金币数量
        $this->load->model('user_model');
        $coin = $user_info['coin'] - $propbuy_info['total_gold'];
        // 减去用户花掉的金币
        $fields = array('U_GOLD' => $coin);
        $rst = $this->user_model->update_user_info($params['uuid'],$fields);
        if ($rst === false) {
            $this->game_model->error();
            $this->error_->set_error(Err_Code::ERR_PROP_BUY_FAIL);
            $this->output_json_return();
        }
        
        // 6.修改金币更改历史记录
        $coin_info = array(
            'change_coin'   => $propbuy_info['total_gold'],
            'coin'          => $coin,
        );
        $rst = $this->user_model->record_coin_change_history($params['uuid'],$user_info['nickname'],$coin_info,1,6);
        if(!$rst) {
            $this->game_model->error();
            $this->error_->set_error(Err_Code::ERR_PROP_BUY_FAIL);
            $this->output_json_return();
        }
        
        // 7.判断游戏是否是，开发商上传游戏  $propbuy_info['game_join_id'];
        $game_info = $this->game_model->get_game_info_by_gameid($propbuy_info['game_join_id']);
        if ((int)$game_info['is_developer'] === 1) {
            // 是开发商上传游戏， 添加购买统计
            $data_inset = array(
                'game_id'   => $propbuy_info['game_join_id'],
                'game_name' => $game_info['name'],
                'prop_id'   => 0,
                'uuid'      => $params['uuid'],
                'nickname'  => $user_info['nickname'],
                'price'     => $game_info['price_current'],
                'STATUS'    => 0,
            );
            $res = $this->game_model->developer_buy_insert($data_inset);
            if (!$res) {
                $this->output_json_return();
            }
        }
        
        $this->game_model->success();
        // 8.调用回调，通知游戏开发商，购买结果。
        $result = array(
            'order_id'      => $params['order_id'],
            'prop_id'       => $propbuy_info['prop_id'],
            'total_fee'     => $propbuy_info['total_fee'],
            'total_gold'    => $propbuy_info['total_gold'],
            'game_join_id'  => $propbuy_info['game_join_id'],
            'buy_status'    => $propbuy_info['buy_status'],
            'subject'       => $propbuy_info['subject'],
            'decription'    => $propbuy_info['decription'],
        );
        $this->output_json_return($result);
    }
    
    /**
     * 单机游戏购买道具
     */
    public function prop_buy() {
        log_scribe('trace', 'params', 'prop_buy:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                 = $this->get_public_params();
        $params['total_fee']    = $this->request_param('total_fee');
        $params['game_join_id'] = $this->request_param('game_join_id');
        $params['subject']      = $this->request_param('subject');
        $params['description']  = $this->request_param('description');
        $params['timestamp']    = $this->request_param('timestamp');
        $params['nonce']        = $this->request_param('nonce');
        // 1.校验参数
        if ($params['uuid'] == "" || $params['channel_id'] == '' ||  $params['app_id'] == "" || $params['device_id']== "" || $params['token'] == '' || $params['method'] == '' || $params['total_fee'] == '' || $params['game_join_id'] == '' || $params['subject'] == '' || $params['timestamp'] == '' || $params['nonce'] == '' || $params['sign'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        // 2.校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 4.判断金币是否足够
        //获取用户信息
        $user_info = $this->utility->get_user_info($params['uuid']);
        if (!$user_info) {
            $this->output_json_return();
        }
        //校验金币是否够
        $params['total_gold'] = $params['total_fee'];
        if($user_info['coin'] < $params['total_gold']){
            $this->error_->set_error(Err_Code::ERR_GAME_COIN_NOT_ENOUGH);
            $this->output_json_return();
        }
        
        // 5.扣除用户金币
        $this->game_model->start();
        $this->load->model('user_model');
        $coin = $user_info['coin'] - $params['total_gold'];
        // 减去用户花掉的金币
        $fields = array('U_GOLD' => $coin);
        $rst = $this->user_model->update_user_info($params['uuid'],$fields);
        if ($rst === false) {
            $this->game_model->error();
            $this->output_json_return();
        }
        
        // 5.修改金币更改历史记录
        $coin_info = array(
            'change_coin'   => $params['total_gold'],
            'coin'          => $coin,
        );
        $rst = $this->user_model->record_coin_change_history($params['uuid'],$user_info['nickname'],$coin_info,1,6);
        if(!$rst) {
            $this->game_model->error();
            $this->output_json_return();
        }
        
        // 4.生成购买订单（单机游戏购买）
        $params['nickname'] = $user_info['nickname'];
        if (!$params['description']) {
            $params['description'] = '';
        }
        $fields = array(
            'P_USERIDX'         =>  $params['uuid'],
            'P_NICKNAME'        =>  $params['nickname'],
            'P_PROPIDX'         =>  0,
            'P_TOTALFEE'        =>  $params['total_fee'],
            'P_TOTALGOLD'       =>  $params['total_gold'],
            'P_GAMEJOINID'      =>  $params['game_join_id'],
            'P_SUBJECT'         =>  $params['subject'],
            'P_DECRIPTION'      =>  $params['description'],
            'P_NOTIFYURL'       =>  '',
            'P_TIMESTAMP'       =>  $params['timestamp'],
            'P_NONCE'           =>  $params['nonce'],
            'P_BUYSTATUS'       =>  0, // 0:成功, 1:失败, 2:等待结果中(未购买状态)
            'STATUS'            =>  0,
        );
        
        $order_id = $this->game_model->game_propbuy($fields);
        if (!$order_id) {
            $this->output_json_return();
        }
        
        // 判断游戏是否是，开发商上传游戏  渠道商统计---暂无
        /*$game_info = $this->game_model->get_game_info_by_gameid($params['game_join_id']);
        if ((int)$game_info['is_developer'] === 1) {
            // 是开发商上传游戏， 添加购买统计
            $data_inset = array(
                'game_id'   => $params['game_join_id'],
                'game_name' => $game_info['name'],
                'prop_id'   => 0,
                'uuid'      => $params['uuid'],
                'nickname'  => $params['nickname'],
                'price'     => $game_info['price_current'],
                'STATUS'    => 0,
            );
            $res = $this->game_model->developer_buy_insert($data_inset);
            if (!$res) {
                $this->output_json_return();
            }
        }*/
        
        $this->game_model->success();
        
        // 5.订单是否成功，返回订单信息
        $ret['order_id']     = $order_id;
        $ret['total_fee']    = $params['total_fee'];
        $this->output_json_return($ret);
    }
    
    /**
     * 精品网游
     */
    public function online_game_list()
    {
        log_scribe('trace', 'params', 'online_game_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $order_type            = $this->request_param('order_type');
        $params['version']     = $this->request_param('version');
        if (isset($order_type)) {
            $params['order_type'] = $order_type;
        }
        
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
        
        if (!isset($params['order_type']) || !$params['order_type']) {
            $params['orderby'] = 1;// 默认按照时间排序
        }  else {
            $params['orderby'] = (int)$params['order_type'];
        }
        // 查询该渠道商是否开启游戏列表私人定制 TODO
        $channel_info = $this->game_model->get_channel_info($params['channel_id']);
        if ($channel_info['custom_game']) { // 0:开启四人定制游戏列表 1：开启
            $params['custom_game'] = 1;
        }
        $data = $this->game_model->hot_list($params);
        /*
        $data = $this->game_model->online_game_list($params);
        foreach ($data['list'] as $k=>$v) {
            $fields = array(
                'uuid'      => $params['uuid'],
                'token'     => $params['token'],
                'device_id' => $params['device_id'],
            );
            if ($v['type'] == 4 && $v['game_url']) { // 开发者接入的网游
                // 给游戏URL授权
                $data['list'][$k]['game_url'] = $this->granted_game_api($v['game_url'], $fields);
                $data['list'][$k]['game_directory'] = $this->granted_game_api($v['game_directory'], $fields);
                $data['list'][$k]['debug_url'] = $this->granted_game_api($v['debug_url'], $fields);
            }
        }
         * */
        $this->output_json_return($data);
    }
    
    /**
     * 热门游戏（小编推荐）(默认是推荐游戏列表)，可能会有特殊处理针对不同渠道商
     */
    public function special_recommend_list()
    {
        log_scribe('trace', 'params', 'special_recomment_list:'.$this->game_model->ip.'  params：'.http_build_query($_REQUEST));
        $params                = $this->get_public_params_no_token();
        $params['recordindex'] = $this->request_param('recordindex');
        $params['pagesize']    = $this->request_param('pagesize');
        $params['version']     = $this->request_param('version');
        if ($params['recordindex'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
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
        
         // 特殊处理
        $data = $this->game_model->special_recomment_list($params);
        if ($data) {
            $this->output_json_return($data);
        } else {
            $this->error_->set_error(Err_Code::ERR_OK);
        }
        
        $data = $this->game_model->recomment_list($params);
        $this->output_json_return($data);
    }
    
    ///------------------开放平台, 开发者 -》游戏DEBUG---------------------///
    /**
     * 开发者，商户后台调用
     * 预下订单(1.先直接下单)
     */
    public function prepare_buy_debug() {
        $params['developer_id'] = $this->request_param('developer_id');
        $params['uuid']         = $this->request_param('uuid');
        $params['token']        = $this->request_param('token'); // debug期间，不校验用户TOKEN，该token为游戏的有效期
        $params['method']       = $this->request_param('method');
        $params['device_id']    = $this->request_param('device_id');
        $params['total_fee']    = $this->request_param('total_fee'); // 开发者货币
        $params['prop_id']      = $this->request_param('prop_id');
        $params['game_join_id'] = $this->request_param('game_join_id');
        $params['subject']      = $this->request_param('subject');
        $params['description']  = $this->request_param('description');
        $params['timestamp']    = $this->request_param('timestamp');
        $params['nonce']        = $this->request_param('nonce');
        $params['sign']         = $this->request_param('sign');
        
        // 1.校验参数
        if ($params['uuid'] == "" || $params['token'] == '' || $params['device_id'] == '' || $params['method'] == '' || $params['total_fee'] == '' || $params['prop_id'] == '' || $params['game_join_id'] == '' || $params['timestamp'] == '' || $params['nonce'] == '' || $params['sign'] == '' || $params['developer_id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        // 2.校验TOKEN  登录TOKEN
        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
            $this->output_json_return();
        }
        // 获取开发者ID对应的KEY
        $this->load->model('user_model');
        $qualificate_info = $this->user_model->get_developer_key($params['developer_id']);
        if (!$qualificate_info) {
            $this->output_json_return();
        }
        $params['sign_key'] = $qualificate_info['developer_key'];
        //3.校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 4.校验开发者  游戏测试时间有效期
        $open_game_info = $this->game_model->get_open_game_info($params['game_join_id']);
        $game_expire  = $this->passport->get('game_token');
        if (($open_game_info['game_debug_time'] + $game_expire) < time()) { // 游戏链接失效
            $this->error_->set_error(Err_Code::ERR_DEVELOPER_DEBUG_GAME_LINK_EXPIRE);
            $this->output_json_return();
        }
        
        // 5.获取货币数量（1元 = 开发商货币），1人名币 = X 货币 存入购买道具订单表中
        if (!$open_game_info['rate']) {
            $this->error_->set_error(Err_Code::ERR_JOIN_GAME_RATE_FAIL);
            $this->output_json_return();
        }
        
        $rate_res = (10/$open_game_info['rate']); // 汇率  （1块人民币=10金币）10金币/开发者货币
        $params['total_gold'] = $params['total_fee']*$rate_res; // 最终单位：金币
        
        // 6.生成预计购买道具订单
        $params['nickname'] = $this->utility->get_user_info($params['uuid'], 'nickname');
        
        if (!$params['description']) {
            $params['description'] = '';
        }
        $fields = array(
            'P_USERIDX'         =>  $params['uuid'],
            'P_NICKNAME'        =>  $params['nickname'],
            'P_PROPIDX'         =>  $params['prop_id'],
            'P_TOTALFEE'        =>  $params['total_fee'],
            'P_TOTALGOLD'       =>  $params['total_gold'],
            'P_GAMEJOINID'      =>  $params['game_join_id'],
            'P_SUBJECT'         =>  $params['subject'],
            'P_DECRIPTION'      =>  $params['description'],
            'P_NOTIFYURL'       =>  '',
            'P_TIMESTAMP'       =>  $params['timestamp'],
            'P_NONCE'           =>  $params['nonce'],
            'P_BUYSTATUS'       =>  2, // 0:成功, 1:失败, 2:等待结果中(未购买状态)
            'STATUS'            =>  0,
        );
        $order_id = $this->game_model->game_propbuy($fields);
        if (!$order_id) {
            $this->output_json_return();
        }
        
        // 7.返回预计订单是否成功，返回订单信息，以及货币兑换信息
        $ret['order_id']     = $order_id;
        $ret['prop_id']      = $params['prop_id'];
        $ret['total_gold']   = $params['total_gold'];
        $ret['total_fee']    = $params['total_fee'];
        $this->output_json_return($ret);
    }
    
    /**
     * 购买道具,支付接口（2.先判断金币是否足够，3.是否执行购买操作）
     */
    public function action_buy_debug() {
        $params['developer_id'] = $this->request_param('developer_id');
        $params['device_id']    = $this->request_param('device_id'); // 测试阶段 可选
        $params['uuid']         = $this->request_param('uuid');
        $params['token']        = $this->request_param('token');
        $params['method']       = $this->request_param('method');
        $params['order_id']     = $this->request_param('order_id');
        $params['nonce']        = $this->request_param('nonce');
        $params['sign']         = $this->request_param('sign');
        
        // 1.校验参数
        if ($params['uuid'] == "" || $params['token'] == '' || $params['method'] == '' || $params['order_id'] == '' || $params['nonce'] == '' || $params['sign'] == '' || $params['developer_id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        // 2.校验TOKEN  登录TOKEN
        if(!$this->is_login($params['uuid'], $params['device_id'], $params['token'])){
            $this->output_json_return();
        }
        // 获取开发者ID对应的KEY
        $this->load->model('user_model');
        $qualificate_info = $this->user_model->get_developer_key($params['developer_id']);
        if (!$qualificate_info) {
            $this->output_json_return();
        }
        $params['sign_key'] = $qualificate_info['developer_key'];
        // 3.校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        // 4.校验该用户金币，是否跟预订单金币相同，然后判断金币是否足够
        $user_info = $this->utility->get_user_info($params['uuid']);
        
        // 获取预下订单（购买道具）信息
        $propbuy_info = $this->game_model->get_propbuy_info($params['order_id']);
        if (!$propbuy_info) {
            $this->output_json_return();
        }
        // 由于是开发者游戏测试
        $propbuy_info['total_gold'] = 0;
        // 校验金币是否够
        if($user_info['coin'] < $propbuy_info['total_gold']){
            $this->error_->set_error(Err_Code::ERR_GAME_COIN_NOT_ENOUGH);
            $this->output_json_return();
        }
        
        // 5.执行购买操作，修改订单状态
        $this->game_model->start();
        $propbuy_upt = $this->game_model->update_propbuy_info($params['order_id'], 0); // 0:成功, 1:失败, 2:等待结果中
        if (!$propbuy_upt) {
            $this->game_model->error();
            $this->error_->set_error(Err_Code::ERR_PROP_BUY_FAIL);
            $this->output_json_return();
        }
        
        // 6.修改用户信息，金币数量
        $this->load->model('user_model');
        $coin = $user_info['coin'] - $propbuy_info['total_gold'];
        // 减去用户花掉的金币
        $fields = array('U_GOLD' => $coin);
        $rst = $this->user_model->update_user_info($params['uuid'],$fields);
        if ($rst === false) {
            $this->game_model->error();
            $this->error_->set_error(Err_Code::ERR_PROP_BUY_FAIL);
            $this->output_json_return();
        }
        
        // 7.修改金币更改历史记录
        $coin_info = array(
            'change_coin'   => $propbuy_info['total_gold'],
            'coin'          => $coin,
        );
        $rst = $this->user_model->record_coin_change_history($params['uuid'],$user_info['nickname'],$coin_info,1,6);
        if(!$rst) {
            $this->game_model->error();
            $this->error_->set_error(Err_Code::ERR_PROP_BUY_FAIL);
            $this->output_json_return();
        }
        $this->game_model->success();
        
        // 8.返回购买结果。
        $propbuy_info = $this->game_model->get_propbuy_info($params['order_id']);
        $result = array(
            'order_id'      => $params['order_id'],
            'prop_id'       => $propbuy_info['prop_id'],
            'total_fee'     => $propbuy_info['total_fee'],
            'total_gold'    => $propbuy_info['total_gold'],
            'game_join_id'  => $propbuy_info['game_join_id'],
            'buy_status'    => $propbuy_info['buy_status'],
            'subject'       => $propbuy_info['subject'],
            'decription'    => $propbuy_info['decription'],
        );
        $this->output_json_return($result);
    }
    
    /**
     * 游戏授权接口,正式接入平台后
     */
    public function granted_game_api($game_url, $params)
    {
        if (strpos($game_url,'?') === false) {
            $game_url = $game_url.'?uuid='.$params['uuid'].'&token='.$params['token'].'&device_id='.$params['device_id'];
        } else {
            $game_url = $game_url.'&uuid='.$params['uuid'].'&token='.$params['token'].'&device_id='.$params['device_id'];
        }
        return $game_url;
    }
    
    /**
     * 正式环境测试 开发者游戏
     */
    function game_url_debug()
    {
        $game_id = $this->request_param('id');
        if (!$game_id) {
            echo '<script>alert("必须传入ID");</script>';
        }
        // 调用用户登录接口
        $url = base_url().'user?method=login&channel_id=1&app_id=1&device_id=test_1&method=login&os=1&version=1.0.1&account=15210047119&password=123456&login_type=3&source=1&sign=26201b63bea86dae8863637c31be0cd3';
        $user_info = json_decode($this->utility->getHttpResponseGET($url), true);
        // 获取该游戏ID的UTL
        $game_info = $this->game_model->get_open_game_info($game_id);
        // 拼接游戏URL
        $para['uuid']       = $user_info['data']['uuid'];
        $para['token']      = $user_info['data']['token'];
        $para['device_id']  = 'test_1';
        
        $data['game_url'] = $this->granted_game_api($game_info['game_url'], $para);
        // 显示页面
        $this->template->load('template', 'test', $data);
    }
    
    function game_url_view()
    {
        $this->template->load('template', 'game_view');
    }
    
    public function record_playnum()
	{
		
	}
}