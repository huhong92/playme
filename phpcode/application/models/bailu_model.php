<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Bailu_model extends MY_Model {
    public function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->error_->set_error(Err_Code::ERR_OK);
    }
    
    /**
     * 插入订单数据
     */
    public function insert_order($params)
    {
        $data = array(
            'P_USERIDX' => $params['uuid'],
            'P_NICKNAME' => $params['nickname'],
            'P_PROPIDX' => $params['goods_id'],
            'P_TOTALFEE' => $params['money'],
            'P_TOTALGOLD' => $params['gold'],
            'P_GAMEJOINID' => $params['game_id'],
            'P_SUBJECT' => $params['goods_name'],
            'P_DECRIPTION' => $params['goods_name'],
            'P_NOTIFYURL' => $params['pay_url'],
            'P_TIMESTAMP' => $params['time'],
            'P_NONCE' => $params['ext'],
            'P_BUYSTATUS' => 2,
            'STATUS' => 0,
            'ROWTIME' => $this->zeit,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $query = $this->DB->insert('pl_propbuy', $data);
        if($query === false){
            log_scribe('trace', 'model', 'insert_pl_propbuy:'.$this->ip.'  data：'.http_build_query($data));
            $this->CI->error_->set_error(Err_Code::ERR_PROPBUY_INSERT_FAIL);
            return FALSE;
        }
        $prepare_id = $this->DB->insert_id();
        return $prepare_id;
    }
    
    /**
     *返回白鹭游戏ID和playme游戏id对照  playme的id => 白鹭的id
     */
    public function select_bailu_gameid($condition = 'IDX > 0')
    {
        $table      = 'PL_BAILU';
        $select     = array(
            'B_GAMEID AS playme_id',
            'B_BAILUIDX AS bailu_id',
        );
        $id_list = $this->get_row_array($condition.' and STATUS = 0', $select, $table, true);
        if(!$id_list)
        {
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return FALSE;
        }
        else
        {
            foreach($id_list as $k => $v)
            {
                $return_array[$v['playme_id']] = $v['bailu_id'];
            }
            return $return_array;
        }
    }
    
    /*
     * 返回pl_game中的白鹭游戏
     */
    public function  select_bailu_game($order_by  , $offset , $per_page)
    {
        $select     = array(
            'IDX AS id',
            'G_PLAYNUM AS play_num',
            'G_GAMESTAR AS rating',
            'G_SHARENUM AS share_num',
        );
        $condition = 'G_GAMETYPE = 4 AND STATUS = 0 AND G_CLOSE = 0';
        $game_list = $this->get_row_array($condition.' ORDER BY '.$order_by, $select, 'pl_game', true);
        return $game_list;
    }

        /**
     * 向game表中添加数据
     */
    public function add_game($params)
    {
        if (!isset($params['G_FILEDIRECTORY'])) {
            $params['G_FILEDIRECTORY'] = '';
        }
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
        
        $ins = $this->DB->insert('pl_game', $params);
        if($ins)
            echo '成功</br>';
    }
    public function  add_bailu_id($data)
    {
        $ins = $this->DB->insert('pl_bailu', $data);
        if($ins)
            echo '成功</br>';
    }
}

