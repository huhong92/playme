<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Bailu extends P_Controller {
    
    function __construct() {
        parent::__construct(false);
        $this->load->model('game_model');
        $this->load->model('bailu_model');
        $this->load->model('user_model');
    }
    /**
     * 打开白鹭游戏
     
    public function open_game()
    {
        $params       = $this->get_public_params();
        $params['id'] = $this->request_param('id');
        // 校验参数
        if ($params['id'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        // 校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        //生成白鹭KEY
        $key_array['appId']  = $this->passport->get('channel');
        $key_array['time']   = time();
        $key_array['userId'] = $params['uuid'];
        $key_array['sign']   = $this->signkey->bailu_sign($key_array);//验证bailu签名
        
        $userInfo = $this->utility->get_user_info($params['uuid']);
        if(!$userInfo)    $this->output_json_return();
        $key_array['userName'] = $userInfo['nickname'];
        $key_array['userImg']  = $userInfo['image'];
        $key_array['userSex']  = $userInfo['gender'];
        $qry_str               = http_build_query($key_array);
        //获取白鹭游戏ID
        $game_id_data = $this->bailu_model->select_bailu_gameid('B_GAMEID = '.$params['id']);
        //获取游戏打开地址
        $gameList = $this->utility->get('http://api.open.egret.com/Channel.gameList' , 'app_id='.$key_array['appId']);
        $gameList = json_decode($gameList ,TRUE);
        foreach($gameList['game_list'] as $k => $v)
        {
            if($v['gameId'] == $game_id_data[$params['id']])
            {
                $game_url = $v['url'];
                break;
            }
        }
        if(!$game_url)
        {
            $this->error_->set_error(Err_Code::ERR_DB);
            $this->output_json_return();
        }
        $url = $game_url . '?' . $qry_str;
        echo "<script>window.location.href='".$url."'</script>";
    }
   */
    
    /*
     * 接收白鹭服务器的支付请求
     */
    public function wakeup_php()
    {
        $params['uuid']         = $this->request_param('userId');
        $params['nickname']     = $this->request_param('userName');
        $params['game_id']      = $this->request_param('gameId');
        $params['goods_id']     = $this->request_param('goodsId');
        $params['goods_name']   = $this->request_param('goodsName');
        $params['money']        = $this->request_param('money'); // 元
        $params['order_id']     = $this->request_param('egretOrderId');
        $params['ext']          = $this->request_param('ext'); // 此参数为透传参数，通知支付结果接口调用的时候原样返回
        $params['game_url']     = $this->request_param('gameUrl');
        $params['time']         = $this->request_param('time');
        $params['sign']         = $this->request_param('sign');
        
        // 校验参数
        if ($params['uuid'] == '' || $params['nickname'] == '' || $params['game_id'] == '' || $params['goods_id'] == '' || 
                $params['goods_name'] === '' || $params['money'] == '' || $params['order_id'] == '' || $params['ext'] == '' || $params['game_url'] == '' || $params['time'] == '' || $params['sign'] == '') {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        // 依据白鹭签名规则，校验签名
        $key_arr = array(
            'appId'         => $this->passport->get('channel'),
            'egretOrderId'  => $params['order_id'],
            'gameId'        => $params['game_id'],
            'goodsId'       => $params['goods_id'],
            'money'         => $params['money'],
            'time'          => $params['time'],
            'userId'        => $params['uuid'],
        );
        $sign = $this->signkey->bailu_sign($key_arr);
        if ($params['sign'] != $sign) {
            // 签名错误
            $this->error_->set_error(Err_Code::ERR_PARAM_SIGN);
            $this->output_json_return();
        }
        //人民币转换playme金币，检查用户金币是否足够
        $gold       = $params['money'] * $this->passport->get('rmb'); // 所需要的金币
        $user_info  = $this->utility->get_user_info($params['uuid']);
        if (!$user_info) {
            $this->error_->set_error(Err_Code::ERR_PARAM_SIGN);
            $this->output_json_return();
        }
        //生成playme pl_propbuy订单
        $params['gold'] = $gold;
        
        //获取游戏支付回调地址
        $gameList = $this->utility->get('http://api.open.egret.com/Channel.gameList' , 'app_id='.$this->passport->get('channel'));
        $gameList = json_decode($gameList ,TRUE);
        foreach($gameList['game_list'] as $k => $v)
        {
            if($v['gameId'] == $params['game_id'])
            {
                $params['pay_url'] = $v['payCallBackUrl'];
                break;
            }
        }
        if(!$params['pay_url'])
        {
            $this->error_->set_error(Err_Code::ERR_BAILU_PAY_CALL_BACK_URL);
            $this->output_json_return();
        }
            
        //转换游戏ID select_bailu_gameid
        $game_id_data = $this->bailu_model->select_bailu_gameid('B_BAILUIDX = '.$params['game_id']);
        $game_id_data = array_keys($game_id_data);
        $params['game_id'] = $game_id_data[0];
        //将订单插入数据库
        $order_id = $this->bailu_model->insert_order($params);
        $url_get = array(
            'uuid'         => (int)$params['uuid'],
            'playme_order' => (int)$order_id,
            'price'        => (int)($params['money'] * $this->passport->get('rmb')), //结合汇率转换playme金币,
            'gold'         => (int)$user_info['coin'], //用户剩余的金币
            'rmb'          => (int)($params['money'] * 100),   //所需人民币 (分)
        );
        $url = $this->passport->get('buy_bailu_item').'?'.json_encode($url_get);
        echo "<script>window.location.href='".$url."'</script>";
        
    }
    
    //购买白鹭道具返回
    public function buy_bailu_item() 
    {
        $params                  = $this->get_public_params();
        $params['playme_order']  = $this->request_param('id');
        $params['price']         = $this->request_param('price');
        $params['sign'] = $this->utility->get_sign($params);
        // 校验参数
        if ($params['playme_order'] == '' || $params['price'] == '') 
        {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        //校验签名
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        //开启数据库事务
        $this->user_model->start();
        //获取订单信息
        $order_info = $this->game_model->get_propbuy_info($params['playme_order']);
        //是否已经成功或失败
        if((!$order_info) || $order_info['buy_status'] != 2)
        {
            $this->error_->set_error(Err_Code::ERR_BAILU_PAY_ORDER_FAILURE);
            $this->output_json_return();
        }  
        
        $user_info  = $this->utility->get_user_info($params['uuid']);      
        //检查用户金币是否足够
        if(($user_info['coin'] - $params['price']) >= 0)
        {
            $coin = $user_info['coin'] - $params['price'];
            $fields = array('U_GOLD' => $coin);
            $rst = $this->user_model->update_user_info($params['uuid'],$fields);
            if ($rst === false) {
                $this->user_model->error();
                $this->error_->set_error(Err_Code::ERR_PROP_BUY_FAIL);
                $this->output_json_return();
            }
            //请求白鹭支付回调
            $bailu_post = array(
                'orderId' => $order_info['id'],
                'userId' => $params['uuid'],
                'money' => $params['price'] / $this->passport->get('rmb'),
                'ext' => $order_info['nonce'],
                'time' => time(),
            );
            $bailu_post['sign'] = $this->signkey->bailu_sign($bailu_post);
            //拼接支付回调url
            $game_id_data = $this->bailu_model->select_bailu_gameid('B_GAMEID = '.$order_info['game_join_id']);
            $call_back_data = $this->utility->post($order_info['notify_url'] , $bailu_post);
            $call_back_data = json_decode($call_back_data , TRUE);
            
            //回调成功则修改订单状态为成功
            if($call_back_data['code'] == 0)
            {
                $this->game_model->update_propbuy_info($params['playme_order'] , 0);
            }
            else
            {
                //返回失败回滚用户金币
                $this->user_model->error();
                $is_fail = 1;
                $this->error_->set_error(Err_Code::ERR_DB);
            }
        }
        else
        {
            $is_fail = 1;
            $this->error_->set_error(Err_Code::ERR_GAME_COIN_NOT_ENOUGH);
        }
        if($is_fail)
        {
            //修改订单状态为失败
            $this->game_model->update_propbuy_info($params['playme_order'] , 1);
            $this->output_json_return();
        }
        $this->user_model->success();
        $this->output_json_return();
    }
    
}

