<?php
class Cron extends P_Controller{
    
    /**
     * 白鹭网游同步
     */
    public function index()
    {
        $this->load->model('game_model');
        $res    = $this->game_model->bailu_game_sync();// 同步pl_game表
        // $this->game_model->bailu_table_sync();// 同步pl_bailu表
        echo 'SUCCESS';exit;
    }
    
    /**
     * 游戏送审版本维护
     */
    public function game_check()
    {
        $version    = $this->request_param('version');
        $game_ids   = $this->request_param('id');
        if (!$game_ids) {
            echo 'SUCCESS';EXIT;
        }
        $this->load->model('game_model');
        // 获取游戏游戏列表
        $all_list   = $this->game_model->game_all_list();
        if (!$all_list) {
            echo 'SUCCESS';EXIT;
        }
        foreach ($all_list as $k=>$v) {
            if (!in_array($v['id'], $game_ids) && $version == $v['version']) {
                $info['IDX']        = $v['id'];
                $info['G_VERSION']  = '';
                $data[]    = $info;
            }
        }
        // 获取送审版本列表
        $list   = $this->game_model->game_check_list(implode(",", $game_ids));
        if (!$list) {
            echo 'SUCCESS';EXIT;
        }
        foreach ($list as $k=>$v) {
            $info['IDX']        = $v['id'];
            $info['G_VERSION']  = $version;
            $data[]             = $info;            
        }
        $res = $this->game_model->update_check_game($data);
        echo 'SUCCESS';EXIT;
    }
    
    
}
