<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Game_Model extends MY_Model {

    public function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->error_->set_error(Err_Code::ERR_OK);
    }
    
    /**
     * 私有方法：获取游戏列表（包括：免费游戏、金币游戏、我玩过的游戏、我收藏的游戏、我评论过的游戏、热门、新品游戏）
     * param $tab  1:表示pl_game表 2:pl_channelgame
     */
    public function get_game_public($condition, $count_condition, $uuid, $table, $select = 1, $pagesize = 10,  $tab = 1)
    {
        if ($select == 1) {
            $select = array(
                'IDX AS id',
                'G_ICON AS logo',
                'G_NAME AS name',
                'G_INFO AS intro',
                'G_OPERATIONINFO AS guide',
                'G_GAMECATS AS category',
                'G_GAMETYPE AS type',
                'G_GAMEGOLD AS price',
                'G_GAMEGOLDCURRENT AS price_current',
                'G_FILEDIRECTORY AS game_directory',
                'G_SHARENUM AS share_num',
                'G_PLAYNUM AS play_num',
                'G_GAMESTAR AS rating',
                'G_IMGS AS screenshots_str',
                'G_GAMEFILESIZE AS size',
                'G_BUYNUM AS buy_num',
                'UNIX_TIMESTAMP(G_UPTIMEORDERBY) AS create_time',
                'G_VERSION AS g_version',
            );
            if ($tab == 2) {
                $select[] = 'G_CHANNELIDX AS channel_id';
                $select[] = 'G_GAMEIDX AS id';
            }
        } else if ($select == 2) {
            $select = array(
                'B.IDX AS id',
                'B.G_NAME AS name',
                'B.G_ICON AS logo',
                'B.G_INFO AS intro',
                'B.G_OPERATIONINFO AS guide',
                'B.G_GAMECATS AS category',
                'B.G_GAMETYPE AS type',
                'B.G_GAMEGOLD AS price',
                'B.G_GAMEGOLDCURRENT AS price_current',
                'B.G_FILEDIRECTORY AS game_directory',
                'B.G_SHARENUM AS share_num',
                'B.G_PLAYNUM AS play_num',
                'B.G_GAMESTAR AS rating',
                'B.G_IMGS AS screenshots_str',
                'B.G_GAMEFILESIZE AS size',
                'B.G_BUYNUM AS buy_num',
                'UNIX_TIMESTAMP(A.ROWTIME) AS create_time',
                'B.G_VERSION AS g_version',
            );
            if ($tab == 2) {
                $select[] = 'B.G_CHANNELIDX AS channel_id';
                $select[] = 'B.G_GAMEIDX AS id';
            }
        } else {
            $select1 = array(
                'B.IDX AS id',
                'B.G_NAME AS name',
                'B.G_ICON AS logo',
                'B.G_INFO AS intro',
                'B.G_OPERATIONINFO AS guide',
                'B.G_GAMECATS AS category',
                'B.G_GAMETYPE AS type',
                'B.G_GAMEGOLD AS price',
                'B.G_GAMEGOLDCURRENT AS price_current',
                'B.G_FILEDIRECTORY AS game_directory',
                'B.G_SHARENUM AS share_num',
                'B.G_PLAYNUM AS play_num',
                'B.G_GAMESTAR AS rating',
                'B.G_IMGS AS screenshots_str',
                'B.G_GAMEFILESIZE AS size',
                'B.G_BUYNUM AS buy_num',
                'UNIX_TIMESTAMP(A.ROWTIME) AS create_time',
                'B.G_VERSION AS g_version',
            );
            if ($tab == 2) {
                $select1[] = 'B.G_CHANNELIDX AS channel_id';
                $select1[] = 'B.G_GAMEIDX AS id';
            }
            $select = array_merge($select, $select1);
        }
        $data['list'] = $this->get_row_array($condition, $select, $table, true);
        if ($data['list'] === false) {
            log_scribe('trace', 'model', 'select(game)' . $this->ip . ': condition：' .http_build_query($condition));
            $this->error_->set_error(Err_Code::ERR_GAME_GET_FAIL);
            return false;
        }
        if (empty($data['list'])) {
            log_scribe('trace', 'model', 'PL_GAME(select):' . $this->ip . 'where: G_GAMETYPE=>免费游戏');
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        foreach ($data['list'] as $k => $v) {
               // 将category、游戏截屏变成数组
               $data['list'][$k]['category'] = explode(',', trim($v['category'], ','));
               $screenshots_arr = explode(',', trim($v['screenshots_str'], ','));
                foreach ($screenshots_arr as $val) {
                   $data['list'][$k]['screenshots'][] = $this->passport->get('game_url').$v['game_directory'].$val;
                }
                // 拼接游戏完整路径
                if ($v['type'] != 4) {
                    $data['list'][$k]['logo']   = $this->passport->get('game_url').$v['game_directory'].$v['logo'];
                }
                $data['list'][$k]['game_directory']  = $this->passport->get('game_url').$v['game_directory'].'play/index.html';
                $data['list'][$k]['buy_status'] = 0;
        }
        
         // 游戏是否已经购买
        if ($uuid) {
            $buy_sql  = "SELECT B_GAMEIDX as game_id FROM PL_GAMEBUY WHERE B_USERIDX = ".$uuid;
            $buy_query = $this->DB->query($buy_sql);
            if ($buy_query === false) {
                $this->error_->set_error(Err_Code::ERR_DB);
                return false;
            }
            if ($buy_query->num_rows() > 0) {
                $buy_ids = $buy_query->result_array();

                foreach ($data['list'] as $k => $v) {
                    // 添加游戏的购买状态
                    foreach ($buy_ids as $k1 => $v1) {
                        if ($v1['game_id'] == $data['list'][$k]['id']) {
                            $data['list'][$k]['buy_status'] = 1;
                        }
                    }
                }
            }
        }
        
        $this->DB->where($count_condition);
        $count = $this->DB->count_all_results($table);
        $data['pagecount'] = (int)ceil($count / $pagesize);
        
        if ($data['pagecount'] <= 0) {
            log_scribe('trace', 'model', 'PL_GAME(select):' . $this->ip . 'where: G_GAMETYPE=>免费游戏');
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        return $data;
    }
    
    /*
     * 返回可制作的游戏
     */
    public function produce_game($params) {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        $tb_a       = 'PL_GAME a';
        $tb_b       = 'pl_makinggame b';
        $select = array(
            'a.IDX AS id',
            'a.G_FILEDIRECTORY AS game_directory',
            'a.G_NAME AS name',
            'a.G_INFO AS intro',
            'a.G_MAKINGGAMEPOINT AS consume_integral',
            'b.T_MAKINGNUM AS making_num',
            'b.T_PIC AS bg',
            'a.G_VERSION AS g_version',
        );
        
        $join_conditon = "a.IDX = b.T_GAMEIDX";
        $condition = "a.STATUS = 0 AND a.G_CLOSE = 0 AND b.STATUS = 0 LIMIT " . $offset . "," . $per_page;
        $data['list'] = $this->get_composite_row_array($select,$condition,$join_conditon,$tb_a,$tb_b, true);
        if ($data['list'] === false || empty($data['list'])) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        $table  = "PL_GAME a,pl_makinggame b";
        $where  = "a.IDX = b.T_GAMEIDX AND a.STATUS = 0 AND a.G_CLOSE = 0 AND b.STATUS = 0";
        $this->DB->from($table);
        $this->DB->where($where);
        $pagecount = $this->DB->count_all_results();
        $data['pagecount'] = (int)ceil($pagecount / $per_page);
        
        if ($data['pagecount'] <= 0) {
            log_scribe('trace', 'model', 'PL_MAKING(select):' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        return $data;
    }
    
    /**
     * 帽子列表
     * @param type $params
     * @return boolean
     */
    public function hat_list($params)
    {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $produce_table = 'pl_makinghat';
        
        if ($params['orderby'] == 1) {
            $orderby = 'ROWTIME';
        } else {
            $orderby = 'IDX';
        }
        
        $condition = "STATUS = 0 ORDER BY " . $orderby . " ASC LIMIT " . $offset . "," . $per_page;
        $count_condition = "STATUS = 0";
        $select = array(
            'IDX AS id',
            'M_SMALLPIC AS small',
            'M_MEDIUMPIC AS medium',
            'M_LARGEPIC AS large',
        );
        
        $data['list'] = $this->get_row_array($condition, $select, $produce_table, true);
                                      
        if ($data['list'] === false) {
            log_scribe('trace', 'model', 'app_syndata' . $this->ip . ': condition：' . http_build_query($condition));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if (empty($data['list'])) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        $this->DB->from($produce_table);
        $this->DB->where($count_condition);
        $pagecount = $this->DB->count_all_results();
        $data['pagecount'] = (int)ceil($pagecount / $per_page);
        
        if ($data['pagecount'] <= 0) {
            log_scribe('trace', 'model', 'PL_MAKING(select):' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }
    
    /**
     * 帽子info
     * @param type $params
     * @return boolean
     */
    public function hat_info($hat_id)
    {
        $produce_table  = 'pl_makinghat';
        $condition      = "IDX = ".$hat_id." AND STATUS = 0";
        $select = array(
            'M_SMALLPIC AS small',
            'M_MEDIUMPIC as medium',
            'M_LARGEPIC as large',
        );
        
        $hat_info = $this->get_row_array($condition, $select, $produce_table, FALSE);                          
        if ($hat_info === false) {
            log_scribe('trace', 'model', 'hat_info' . $this->ip . ': condition：' . http_build_query($condition));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if (empty($hat_info)) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $hat_info;
    }

    /**
     * 提交用户制作的游戏
     */
    public function produce_submit($params) {
        $game_id    = $params['id'];
        $game_table = 'PL_GAME';
        $makinggame = 'pl_makinggame';
        //校验游戏是否可制作
        $sql = "SELECT A.G_NAME, A.G_ICON, A.G_MAKINGGAMEPOINT, B.T_MAKINGNUM,B.IDX AS makinggameid FROM " . $game_table . " AS A, " . $makinggame . " AS B WHERE A.IDX = B.T_GAMEIDX AND A.IDX = ".$game_id;
        $produce_query = $this->DB->query($sql);
        if ($produce_query === false) {
            log_scribe('trace', 'model', $table . '(insert)' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_GAME_GET_FAIL);
            return false;
        }
        if ($produce_query->num_rows() > 0) {
            $produce_info = $produce_query->row_array(); 
        } else {
            log_scribe('trace', 'model', $game_table . '(select)' . $this->ip . ' where : IDX->' . $params['id']);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 判断用户积分是否够制作游戏
        $userinfo = $this->utility->get_user_info($params['uuid']);
        if ($userinfo['integral'] < $produce_info['G_MAKINGGAMEPOINT']) {
            log_scribe('trace', 'model', 'uuid = '.$params['uuid'].'积分不足');
            $this->error_->set_error(Err_Code::ERR_GAME_POINT_NOT_ENOUGH);
            return false;
        }
        $makingame_info = $this->makinggame_info($game_id);
        $data = array(
            'M_USERIDX'          => $params['uuid'],
            'M_NICKNAME'         => $userinfo['nickname'],
            'M_MAKINGNAME'       => $params['name'],
            'M_PHOTOURL'         => $params['filename'],
            'M_BG'               => $makingame_info['bg'],
            'M_HATID'            => $params['hat_id'],
            'M_MAKINGGAMEID'     => $produce_info['makinggameid'],
            'M_GAMEIDX'          => $params['id'],
            'M_GAMENAME'         => $produce_info['G_NAME'],
            'M_PLAYNUM'          => 0,
            'M_SHARENUM'         => 0,
            'M_SHAREPLAYNUM'     => 0,
            'STATUS'             => 0,
            'ROWTIME'            => $this->zeit,
            'ROWTIMEUPDATE'      => $this->zeit
        );
        
        $making_table = 'PL_MAKING';
        $making_query = $this->DB->insert($making_table, $data);
        
        if ($making_query === false) {
            log_scribe('trace', 'model', $making_table . '(insert)' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_MAKING_GAME_FAIL);
            return false;
        }
        $making_id = $this->DB->insert_id();
        $making_info = $this->making_info($making_id);
        // 游戏制作成功,减去用户消耗的积分
        $user_table = 'PL_USER';
        $condition  = array(
            'IDX'    => $params['uuid'],
            'STATUS' => 0
        );
        $sql = "UPDATE PL_USER SET U_POINT = U_POINT-".$produce_info['G_MAKINGGAMEPOINT']. " WHERE IDX = ".$params['uuid']." AND STATUS = 0";
        $query = $this->DB->query($sql);
        
        if (!$query) {
            log_scribe('trace', 'model', $making_table . '(insert)' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_USER_INFO_UPDATE);
            
            return false;
        }
        $this->load->model('user_model');
        // 插入用户积分变更表
        $integral_info = array(
            'change_integral' => $produce_info['G_MAKINGGAMEPOINT'],
            'integral'        => $userinfo['integral']-$produce_info['G_MAKINGGAMEPOINT'],
        );
        $res = $this->user_model->record_integral_change_history($params['uuid'], $userinfo['nickname'], $integral_info, 1, 1);
        if (!$res) {
            $this->error_->set_error(Err_Code::ERR_INSERT_INTEGRAL_HISTORY_FAIL);
            return false;
        }
        
        // 修改可制作游戏列表，制作次数
        $sql = "UPDATE pl_makinggame SET T_MAKINGNUM = T_MAKINGNUM+1 WHERE T_GAMEIDX = ".$params['id']." AND STATUS = 0";
        $query = $this->DB->query($sql);
        if (!$query) {
            log_scribe('trace', 'model', $making_table . '(insert)' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_MAKING_GAME_FAIL);
            
            return false;
        }
        
        //返回用户信息
        $userinfo['integral'] = $userinfo['integral']-$produce_info['G_MAKINGGAMEPOINT'];
        $data = array(
            'userinfo' => $userinfo,
            'produce_info' => $making_info,
        );
        
        return $data;
    }
    
    /**
     * 获取制作游戏的信息
     */
    public function making_info($id)
    {
        if (!$id) {
            return false;
        }
        $table = 'PL_MAKING AS A, PL_GAME AS B';
        $condition = "A.STATUS = 0 AND A.M_GAMEIDX = B.IDX AND A.IDX = ".$id;
        $select = array(
            'A.IDX AS id',
            'B.IDX AS game_id',
            'B.G_NAME AS name',
            'B.G_ICON AS logo',
            'B.G_FILEDIRECTORY AS game_directory',
            'B.G_TEMPLATE AS template',
            'B.G_INFO AS intro',
            'A.M_PHOTOURL AS pic',
            'A.M_BG AS bg',
            'A.M_HATID AS hat_id',
            'A.M_SHARENUM AS share_num',
            'A.M_PLAYNUM AS play_num',
            'UNIX_TIMESTAMP(A.ROWTIME) AS create_time'
        );
        $res = $this->get_row_array($condition, $select, $table, false);
        if ($res === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        
        if ($res['template'] == 2) { // game_directory = /2/3/
            $making = '/making'.$res['game_directory'];
        } else { // game_directory = /1/2/3/(/games/...)
            $replacement  =  '/making';
            $pattern  =  '/^\/games/i';
            $making = preg_replace($pattern, $replacement, $res['game_directory']);
        }
        // 获取制作游戏 bg(底图)  face（脸图） temp(帽子莫版图)
        // $res['temp']    = array();
        if ($res['hat_id']) {
            $hat_info       = $this->hat_info($res['hat_id']);
            if ($hat_info) {
                 $res['temp']['small']  = $this->passport->get('game_url').$hat_info['small'];
                 $res['temp']['medium'] = $this->passport->get('game_url').$hat_info['medium'];
                 $res['temp']['large']  = $this->passport->get('game_url').$hat_info['large'];
            }
        }
        
        if ($res['pic']) {
            $res['pic'] = $this->passport->get('game_url').$res['pic'];
        }
        if ($res['bg']) {
            $res['bg'] = $this->passport->get('game_url').$res['bg'];
        }
        if ($res['game_directory']) {
            $res['game_directory'] = $this->passport->get('game_url').$making.'play/index.html?face='.$res['pic']."&temp=".$res['temp']['large'];
        }
        if ($res['logo']) {
            $res['logo'] = $this->passport->get('game_url').$making.$res['logo'];
        }
        
        return $res;
    }
    
    /**
     * 获取制作模板信息
     */
    public function makinggame_info($game_id)
    {
        $produce_table  = 'pl_makinggame';
        $condition      = "T_GAMEIDX = ".$game_id." AND STATUS = 0";
        $select = array(
            'T_GAMEIDX AS game_id',
            'T_GAMENAME AS game_name',
            'T_MADE AS bg',
            'T_MAKINGNUM AS making_num',
        );
        
        $makinggame_info = $this->get_row_array($condition, $select, $produce_table, FALSE);                          
        if ($makinggame_info === false) {
            log_scribe('trace', 'model', 'makinggame_info' . $this->ip . ': condition：' . http_build_query($condition));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if (empty($makinggame_info)) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $makinggame_info;
    }

    /**
     * 删除制作的游戏
     */
    public function delete_produce($params) {
        $produce_id = $params['id'];
        $table = 'PL_MAKING';

        $where = array('IDX' => $produce_id);
        $delete = $this->DB->delete($table, $where);

        if ($delete === false) {
            log_scribe('trace', 'model', 'PL_MAKING(delete):' . $this->ip . "where: 'IDX' => " . $produce_id);
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }

    /**
     * 制作游戏列表
     */
    public function produce_list($params) {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $produce_table = 'PL_MAKING AS A, PL_GAME AS B';
        
        if ($params['orderby'] == 1) {
            $orderby = 'A.ROWTIME';
        } else {
            $orderby = 'A.IDX';
        }
        
        $condition = "A.STATUS = 0 AND A.M_GAMEIDX = B.IDX AND A.M_USERIDX = ".$params['uuid']." ORDER BY " . $orderby . " DESC LIMIT " . $offset . "," . $per_page;
        $count_condition = "A.STATUS = 0 AND A.M_GAMEIDX = B.IDX AND A.M_USERIDX = ".$params['uuid'];
        $select = array(
            'A.IDX AS id',
            'B.G_NAME AS name',
            'B.G_ICON AS logo',
            'B.G_FILEDIRECTORY AS game_directory',
            'B.G_TEMPLATE AS template',
            'B.G_INFO AS intro',
            'B.G_MAKINGGAMEPOINT AS consume_integral',
            'A.M_PHOTOURL AS pic',
            'A.M_BG AS bg',
            'A.M_HATID AS hat_id',
            'A.M_SHARENUM AS share_num',
            'A.M_PLAYNUM AS play_num',
            'UNIX_TIMESTAMP(A.ROWTIME) AS create_time'
        );
        
        $data['list'] = $this->get_row_array($condition, $select, $produce_table, true);
                                      
        if ($data['list'] === false) {
            log_scribe('trace', 'model', 'app_syndata' . $this->ip . ': condition：' . http_build_query($condition));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if (!$data['list']) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        $this->DB->from($produce_table);
        $this->DB->where($count_condition);
        $pagecount = $this->DB->count_all_results();
        $data['pagecount'] = (int)ceil($pagecount / $per_page);
        
        if ($data['pagecount'] <= 0) {
            log_scribe('trace', 'model', 'PL_MAKING(select):' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        foreach ($data['list'] as $k=>&$v) {
            if ($v['template'] == 2) { // game_directory = /2/3/
                $making = '/making'.$v['game_directory'];
            } else { // game_directory = /1/2/3/(/games/...)
                $replacement  =  '/making';
                $pattern  =  '/^\/games/i';
                $making = preg_replace($pattern, $replacement, $v['game_directory']);
            }
            // 拼接图片地址
            if ($v['hat_id']) {
                $hat  = $this->hat_info($v['hat_id']);
                $v['temp']['small']  = $this->CI->passport->get('game_url').$hat['small'];
                $v['temp']['medium'] = $this->CI->passport->get('game_url').$hat['medium'];
                $v['temp']['large']  = $this->CI->passport->get('game_url').$hat['large'];
            }
            
            if ($v['bg']) {
                $v['bg'] = $this->CI->passport->get('game_url').$v['bg'];
            }
            if ($v['pic']) {
                $v['pic'] = $this->CI->passport->get('game_url').$v['pic'];
            }
            $v['logo'] = $this->passport->get('game_url').$making.$v['logo'];
            if ($v['game_directory']) {
                $v['game_directory'] = $this->passport->get('game_url').$making.'play/index.html?face='.$v['pic']."&temp=".$v['temp']['large'];
            }
        }
        return $data;
    }

    /**
     * 免费游戏列表
     */
    public function free_list($params) {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        $uuid       = $params['uuid'];
        if ($params['custom_game']) {
            $game_table = 'PL_CHANNELGAME';
            $tab        = 2;
        } else {
            $game_table = 'PL_GAME';
            $tab        = 1;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'G_UPTIMEORDERBY';// 按照游戏上架时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'G_BUYNUM';
        } else {
            $orderby = 'G_GAMESTAR';
        }
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub    = "STATUS = 0 AND G_GAMETYPE = 0 AND G_TEMPLATE != 2 AND  G_CLOSE = 0"; 
        if ($params['custom_game']) {
            $condition          = $condition_pub." AND G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub." AND G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub;
        }
        // 判断是否存在version， 拼接condition条件  
        if (!$params['version']) {
            $condition = $condition." LIMIT ".$offset.",".$per_page;
        }
        // 获取免费游戏列表
        $data = $this->get_game_public($condition, $count_condition,$uuid, $game_table, $select = 1, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        
        return $data;
    }
    
    /**
     * 金币游戏列表
     */
    public function coin_list($params) {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        $uuid       = $params['uuid'];
        if ($params['orderby'] == 1) {
            $orderby = 'A.G_UPTIMEORDERBY DESC';// 按照游戏上架时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'A.G_BUYNUM DESC ';
        } else if ($params['orderby'] == 3){
            $orderby = 'A.G_GAMESTAR DESC ';
        } else {
            $orderby1 = 'B.ROWTIME DESC'; // 默认已购买排上，然后按照游戏上架上架时间倒序
            $orderby2 = "A.G_UPTIMEORDERBY DESC";
        }
        if (!$orderby1) {
            $orderby1 = $orderby;
        }
        if (!$orderby2) {
            $orderby2 = $orderby;
        }
        
        // 获取金币游戏列表
        // 拼接sql语句
        if ($uuid) {
            if ($params['custom_game']) { // pl_game --> pl_channelgame
                // 用户已购买的金币游戏
                $select     =  'A.G_VERSION AS g_version, A.G_CHANNELIDX AS channel_id,A.G_GAMEIDX AS id,A.G_NAME AS name, A.G_ICON AS logo, A.G_INFO AS intro,A.G_OPERATIONINFO AS guide,A.G_GAMECATS AS category,A.G_GAMETYPE AS type, A.G_GAMEGOLD AS price,A.G_GAMEGOLDCURRENT AS price_current,A.G_FILEDIRECTORY AS game_directory,A.G_SHARENUM AS share_num,A.G_PLAYNUM AS play_num,A.G_GAMESTAR AS rating, A.G_IMGS AS screenshots_str, A.G_GAMEFILESIZE AS size,A.G_BUYNUM AS buy_num, @buy_status := 1 as buy_status';
                $sql        = "SELECT ".$select." FROM pl_channelgame AS A JOIN pl_gamebuy AS B ON A.G_GAMEIDX = B.B_GAMEIDX WHERE A.STATUS = 0 AND A.G_GAMETYPE = 1 AND A.G_CLOSE = 0 AND B.STATUS = 0 AND A.G_TEMPLATE != 2 AND B.B_USERIDX = ".$uuid."  ORDER BY ".$orderby1;
                // 用户未购买的金币游戏
                $select2    =  'A.G_VERSION AS g_version, A.G_CHANNELIDX AS channel_id, A.G_GAMEIDX AS id, A.G_NAME AS name, A.G_ICON AS logo, A.G_INFO AS intro,A.G_OPERATIONINFO AS guide,A.G_GAMECATS AS category,A.G_GAMETYPE AS type, A.G_GAMEGOLD AS price,A.G_GAMEGOLDCURRENT AS price_current,A.G_FILEDIRECTORY AS game_directory,A.G_SHARENUM AS share_num,A.G_PLAYNUM AS play_num,A.G_GAMESTAR AS rating, A.G_IMGS AS screenshots_str, A.G_GAMEFILESIZE AS size,A.G_BUYNUM AS buy_num, @buy_status := 0 as buy_status, UNIX_TIMESTAMP(A.G_UPTIMEORDERBY) AS create_time';
                $sql2       = "SELECT ".$select2." FROM pl_channelgame as A WHERE G_GAMEIDX NOT IN (SELECT  B_GAMEIDX FROM pl_gamebuy WHERE B_USERIDX = ".$uuid." AND STATUS = 0) AND  A.STATUS = 0 AND A.G_GAMETYPE = 1 AND A.G_CLOSE = 0 AND A.G_TEMPLATE != 2 ORDER BY ".$orderby2;
            } else {
                $select     =  'A.G_VERSION AS g_version,A.IDX AS id,A.G_NAME AS name, A.G_ICON AS logo, A.G_INFO AS intro,A.G_OPERATIONINFO AS guide,A.G_GAMECATS AS category,A.G_GAMETYPE AS type, A.G_GAMEGOLD AS price,A.G_GAMEGOLDCURRENT AS price_current,A.G_FILEDIRECTORY AS game_directory,A.G_SHARENUM AS share_num,A.G_PLAYNUM AS play_num,A.G_GAMESTAR AS rating, A.G_IMGS AS screenshots_str, A.G_GAMEFILESIZE AS size,A.G_BUYNUM AS buy_num, @buy_status := 1 as buy_status';
                $select2    =  'A.G_VERSION AS g_version, A.IDX AS id,A.G_NAME AS name, A.G_ICON AS logo, A.G_INFO AS intro,A.G_OPERATIONINFO AS guide,A.G_GAMECATS AS category,A.G_GAMETYPE AS type, A.G_GAMEGOLD AS price,A.G_GAMEGOLDCURRENT AS price_current,A.G_FILEDIRECTORY AS game_directory,A.G_SHARENUM AS share_num,A.G_PLAYNUM AS play_num,A.G_GAMESTAR AS rating, A.G_IMGS AS screenshots_str, A.G_GAMEFILESIZE AS size,A.G_BUYNUM AS buy_num, @buy_status := 0 as buy_status, UNIX_TIMESTAMP(A.G_UPTIMEORDERBY) AS create_time';
                $sql        = "SELECT ".$select." FROM pl_game AS A JOIN pl_gamebuy AS B ON A.IDX = B.B_GAMEIDX WHERE A.STATUS = 0 AND A.G_GAMETYPE = 1 AND A.G_CLOSE = 0 AND B.STATUS = 0 AND A.G_TEMPLATE != 2 AND B.B_USERIDX = ".$uuid."  ORDER BY ".$orderby1;
                $sql2       = "SELECT ".$select2." FROM pl_game as A WHERE IDX NOT IN (SELECT  B_GAMEIDX FROM pl_gamebuy WHERE B_USERIDX = ".$uuid." AND STATUS = 0) AND  A.STATUS = 0 AND A.G_GAMETYPE = 1 AND A.G_CLOSE = 0 AND A.G_TEMPLATE != 2 ORDER BY ".$orderby2;
            }
        } else {
            if ($params['custom_game']) {
                $select     =  'A.G_VERSION AS g_version, A.G_CHANNELIDX AS channel_id,A.G_GAMEIDX AS id,A.G_NAME AS name, A.G_ICON AS logo, A.G_INFO AS intro,A.G_OPERATIONINFO AS guide,A.G_GAMECATS AS category,A.G_GAMETYPE AS type, A.G_GAMEGOLD AS price,A.G_GAMEGOLDCURRENT AS price_current,A.G_FILEDIRECTORY AS game_directory,A.G_SHARENUM AS share_num,A.G_PLAYNUM AS play_num,A.G_GAMESTAR AS rating, A.G_IMGS AS screenshots_str, A.G_GAMEFILESIZE AS size,A.G_BUYNUM AS buy_num, @buy_status := 0 as buy_status';
                $sql        = "SELECT ".$select." FROM pl_channelgame AS A WHERE A.STATUS = 0 AND A.G_GAMETYPE = 1 AND A.G_CLOSE = 0 AND A.G_TEMPLATE != 2 AND ORDER BY ".$orderby1;
            } else {
                $select     =  'A.G_VERSION AS g_version,A.IDX AS id,A.G_NAME AS name, A.G_ICON AS logo, A.G_INFO AS intro,A.G_OPERATIONINFO AS guide,A.G_GAMECATS AS category,A.G_GAMETYPE AS type, A.G_GAMEGOLD AS price,A.G_GAMEGOLDCURRENT AS price_current,A.G_FILEDIRECTORY AS game_directory,A.G_SHARENUM AS share_num,A.G_PLAYNUM AS play_num,A.G_GAMESTAR AS rating, A.G_IMGS AS screenshots_str, A.G_GAMEFILESIZE AS size,A.G_BUYNUM AS buy_num, @buy_status := 0 as buy_status';
                $sql        = "SELECT ".$select." FROM pl_game AS A WHERE A.STATUS = 0 AND A.G_GAMETYPE = 1 AND A.G_CLOSE = 0 AND A.G_TEMPLATE != 2  ORDER BY ".$orderby1;
            }
        }
        
        $query = $this->DB->query($sql);
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        if ($query->num_rows() < 0) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        $data1 = $query->result_array();
        if ($sql2) {
            $query2 = $this->DB->query($sql2);
            if ($query2 === false) {
                $this->error_->set_error(Err_Code::ERR_DB);
                return false;
            }
            if ($query2->num_rows() < 0) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data2 = $query2->result_array();
            $data1 = array_merge($data1,$data2);
        }
        
        if (empty($data1)) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data1 as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data4[] = $v;
                    }
                }
            }
            if (empty($data4)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data1 = $data4;
        }
        
        $data['pagecount'] = (int)ceil(count($data1) / $per_page);
        $data['list'] = array_slice($data1, $offset, $per_page);
        foreach ($data['list'] as $k => $v) {
            // 将category、游戏截屏变成数组
            $data['list'][$k]['category'] = explode(',', trim($v['category'], ','));
            $screenshots_arr = explode(',', trim($v['screenshots_str'], ','));
            foreach ($screenshots_arr as $val) {
                $data['list'][$k]['screenshots'][] = $this->passport->get('game_url').$v['game_directory'].$val;
            }
            // 拼接游戏完整路径
            $data['list'][$k]['logo']           = $this->passport->get('game_url').$v['game_directory'].$v['logo'];
            $data['list'][$k]['game_directory'] = $this->passport->get('game_url').$v['game_directory'].'play/index.html';
        }
        return $data;
    }
    
    /**
     * 我玩过的游戏列表
     */
    public function myplay_list($params) {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $uuid     = $params['uuid'];
        if ($params['custom_game']) {
            $table  = 'PL_GAMESCOREUSERTOP AS A, pl_channelgame AS B';
            $tab    = 2;
        } else {
            $table  = 'PL_GAMESCOREUSERTOP AS A, PL_GAME AS B';
            $tab    = 1;
        }
        
        if ($params['orderby'] == 1) {
            $orderby = 'A.ROWTIMEUPDATE'; // 按照游戏最近玩过的时间
        } else if ($params['orderby'] == 2) {
            $orderby = 'B.G_BUYNUM';
        } else {
            $orderby = 'B.G_GAMESTAR';
        }
        $select = array(
            'A.P_GAMESCORE as scoring',
            'A.ROWTIME AS play_time',
        );
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub    = "A.STATUS = 0 AND B.G_TEMPLATE != 2  AND A.P_USERIDX = " . $params['uuid'] . " AND B.G_CLOSE = 0 AND B.STATUS = 0 ";
        if ($params['custom_game']) {
            $condition          = $condition_pub." AND A.P_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub." AND A.P_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." AND A.P_GAMEIDX = B.IDX ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub ." AND A.P_GAMEIDX = B.IDX ";
        }
        // 判断是否存在version， 拼接condition条件  
        if (!$params['version']) {
            $condition = $condition." LIMIT ".$offset . "," . $per_page;
        }
        $myplay_list     = $this->get_game_public($condition, $count_condition, $uuid, $table, $select, $per_page, $tab);
        if (!$myplay_list) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        // 我玩过的游戏，现实当前排名
        foreach ($myplay_list['list'] as $k=>$v) {
            // 获取游戏得分排序规则
            $game_info = $this->get_game_info_by_gameid($v['id']);
            // 获取当前游戏，我的得分
            $condition2 = "A.P_USERIDX = " .$uuid. " AND A.P_GAMEIDX = " . $v['id'] . " AND A.STATUS = 0 AND A.P_USERIDX = B.IDX";
            $select2 = array(
                'A.P_USERIDX AS uuid',
                'A.P_GAMESCORE AS scoring',
                'B.U_ICON AS image',
                'B.U_NICKNAME AS nickname',
                'B.U_TOTALPOINT AS integral',
                'A.ROWTIME AS play_time',
            );
            $mine = $this->get_row_array($condition2, $select2, 'pl_gamescoreusertop AS A, PL_USER AS B', false);
            if ($mine === false || !$mine) {
                log_scribe('trace', 'model', 'user_ranking' . $this->ip . ': condition：' . $condition);
                $this->error_->set_error(Err_Code::ERR_APP_CONFIG_NO_DATA);
                return false;
            }
            
            if ((int)$game_info['score_order_type'] === 0) { // 0:顺序1:倒序
                $sql = "SELECT COUNT(IDX) as orderby FROM pl_gamescoreusertop WHERE P_GAMEIDX = " . $v['id'] . " AND STATUS = 0 AND (P_GAMESCORE > ".$mine['scoring']." or (P_GAMESCORE = ".$mine['scoring']." AND UNIX_TIMESTAMP(ROWTIME) < UNIX_TIMESTAMP('".$mine['play_time']."')) )";
            } else {
                $sql = "SELECT COUNT(IDX) as orderby FROM pl_gamescoreusertop WHERE P_GAMEIDX = " . $v['id'] . " AND STATUS = 0 AND (P_GAMESCORE < ".$mine['scoring']." or (P_GAMESCORE = ".$mine['scoring']." AND UNIX_TIMESTAMP(ROWTIME) < UNIX_TIMESTAMP('".$mine['play_time']."')) )";
            }
            
            $query = $this->DB->query($sql);
            
            if ($query === false) {
                $this->error_->set_error(Err_Code::ERR_DB);
                return false;
            }
            
            $res = $query->result_array();
            if (!$res) {
                $this->error_->set_error(Err_Code::ERR_GAME_NO_PLAY);
                return false;
            }
            
            if (!$res[0]['orderby']) {
                $myplay_list['list'][$k]['rank'] = 1;// 无数据，表示第一名
            } else{
                $myplay_list['list'][$k]['rank'] = $res[0]['orderby'] + 1;
            }
        }
        
        return $myplay_list;
    }

    /**
     * 我收藏的游戏列表
     */
    public function favorite_list($params) {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        $uuid       = $params['uuid'];
        if ($params['custom_game']) {
            $table  = 'PL_GAMEFAVORITES AS A, pl_channelgame AS B';
            $tab    = 2;
        } else {
            $table  = 'PL_GAMEFAVORITES AS A, PL_GAME AS B';
            $tab    = 1;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'A.ROWTIMEUPDATE DESC';  // 按照最后修改的收藏时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'B.G_BUYNUM DESC';
        } else {
            $orderby = 'B.G_GAMESTAR DESC';
        }
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub    = "A.STATUS = 0 AND B.STATUS = 0  AND B.G_CLOSE = 0 AND A.F_USERIDX = " . $params['uuid'];
        if ($params['custom_game']) {
            $condition          = $condition_pub."  AND A.F_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby;
            $count_condition    = $condition_pub."  AND A.F_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." AND A.F_GAMEIDX = B.IDX ORDER BY " . $orderby;
            $count_condition    = $condition_pub ." AND A.F_GAMEIDX = B.IDX";
        }
        // 判断是否存在version， 拼接condition条件  
        $condition = $condition." LIMIT ".$offset.",".$per_page;
        $data      = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 2, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }

    /**
     * 我评论过的游戏列表
     */
    public function comment_game_list($params) {
        $uuid       = $params['uuid'];
        $offset     = $params['recordindex'];
        $per_page   = $params['pagesize'];
        if ($params['custom_game']) {
            $table  = 'PL_GAMECOMMENTSSTAR AS A, pl_channelgame AS B';
            $tab    = 2;
        } else {
            $table  = 'PL_GAMECOMMENTSSTAR AS A, PL_GAME AS B';
            $tab    = 1;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'A.ROWTIMEUPDATE DESC'; // 按照最后一次评论时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'B.G_BUYNUM DESC';
        } else {
            $orderby = 'B.G_GAMESTAR DESC';
        }
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub    = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND A.C_USERIDX = " . $params['uuid'];
        if ($params['custom_game']) {
            $condition          = $condition_pub."  AND A.C_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby;
            $count_condition    = $condition_pub."  AND A.C_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." AND A.C_GAMEIDX = B.IDX ORDER BY " . $orderby;
            $count_condition    = $condition_pub ." AND A.C_GAMEIDX = B.IDX";
        }
        $condition = $condition." LIMIT ".$offset.",".$per_page;
        $data            = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 2, $per_page, $tab);
        if (!$data['list']) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        // 获取我评论过的游戏，收藏总次数，评论总次数
        foreach ($data['list'] as $k=>$v) {
            // 收藏总次数
            $table_favorites = "pl_gamefavorites";
            $condition_favorites = "status = 0 and F_GAMEIDX = ".$v['id'];
            $select_favorites = array("count(idx) AS fav_num");
            $res = $this->get_row_array($condition_favorites, $select_favorites, $table_favorites);
            if (!$res['fav_num']) {
                $data['list'][$k]['fav_num'] = 0;
            } else {
                $data['list'][$k]['fav_num'] = $res['fav_num'];
            }
            // 评论总次数
            $table_comments = "pl_gamecomments";
            $condition_comments = "status = 0 and C_GAMEIDX = ".$v['id'];
            $select_comments = array("count(idx) AS comm_num");
            $res = $this->get_row_array($condition_comments, $select_comments, $table_comments);
            if (!$res['comm_num']) {
                $data['list'][$k]['comm_num'] = 0;
            } else {
                $data['list'][$k]['comm_num'] = $res['comm_num'];
            }
        }
        
        return $data;
    }

    /**
     * 热门游戏列表
     */
    public function hot_list($params) {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $type     = $params['type'];// 0：免费游戏 1：收费游戏 （默认所有的推荐游戏列表） 3：所有游戏
        $uuid     = $params['uuid'];
        if ($params['custom_game']) {
            $table  =  'pl_channelgame';
            $tab    = 2;
        } else {
            $table  =  'PL_GAME';
            $tab    = 1;
        }
        if (!$type) {
            $type = 0;
        }
        
        if ($params['orderby'] == 1) {
            $orderby = 'G_UPTIMEORDERBY';// 按照游戏上架时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'G_BUYNUM';
        } else {
            $orderby = 'G_GAMESTAR';
        }
                
        // 判断是否存在custom_game 拼接condition条件
        if ($type == 3) {
            $condition_pub    = "STATUS = 0 AND G_HOT = 1 AND  G_CLOSE = 0 AND G_TEMPLATE != 2 AND G_TEMPLATE != 4 ";
        } else {
            $condition_pub    = "STATUS = 0 AND G_HOT = 1 AND  G_CLOSE = 0 AND G_TEMPLATE != 2 AND G_TEMPLATE != 4 AND G_GAMETYPE = ".$type;
        }
        if ($params['custom_game']) {
            $condition          = $condition_pub." AND G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub." AND G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub;
        }
        // 判断是否存在version， 拼接condition条件  
        if (!$params['version']) {
            $condition = $condition." LIMIT ".$offset.",".$per_page;
        }
        $data = $this->get_game_public($condition, $count_condition,$uuid, $table, $select = 1, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_GAME_GET_FAIL);
            return false;
        }
        
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态            
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        
        return $data;
    }
    
    /**
     * 热门游戏排行（游戏总排行）
     */
    public function game_orderby($params) {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $type     = $params['type'];// 0：免费游戏 1：收费游戏 （默认所有的推荐游戏列表） 2：所有游戏
        $uuid     = $params['uuid'];
        if ($params['custom_game']) {
            $table  = 'PL_GAMEORDERBY AS A, PL_CHANNELGAME AS B';
            $tab    = 2;
        } else {
            $table  = 'PL_GAMEORDERBY AS A, PL_GAME AS B';
            $tab    = 1;
        }
        // 0:打开的次数 1：游戏评分  2 购买次数
        if ($params['orderby'] ==  4) {
            $params['orderby'] = 0;
        }
        if ($params['orderby'] ==  3) {
            $params['orderby'] = 1;
        }
        
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub    = "B.G_TEMPLATE != 2 AND B.G_GAMETYPE = ".$type." AND B.G_CLOSE = 0 AND B.STATUS = 0 AND A.STATUS = 0 AND  A.G_ORDERBYTYPE = ".$params['orderby'];
        if ($params['custom_game']) {
            $condition          = $condition_pub." AND A.G_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY A.T_ORDERBY ASC";
            $count_condition    = $condition_pub." AND A.G_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." AND A.G_GAMEIDX = B.IDX ORDER BY A.T_ORDERBY ASC";
            $count_condition    = $condition_pub ." AND A.G_GAMEIDX = B.IDX";
        }
        // 判断是否存在version， 拼接condition条件  
        if (!$params['version']) {
            $condition = $condition." LIMIT ".$offset.",".$per_page;
        }
        $select1 = array(
           'A.G_ORDERBYTYPE AS orderby_type',
           'A.T_ORDERBY AS orderby_no',
        ); 
        $data = $this->get_game_public($condition, $count_condition, $uuid, $table, $select1, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态            
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        
        return $data;
    }
    
    /**
     * 新品游戏列表
     */
    public function new_list($params) {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        $uuid       = $params['uuid'];
        $type       = $params['type']; // 0:免费 1：收费 3：所有
        if ($params['custom_game']) {
            $table  = 'pl_channelgame';
            $tab    = 2;
        } else {
            $table  = 'PL_GAME';
            $tab    = 1;
        }
        if (!$type) {
            $type = 0;
        }

        if ($params['orderby'] == 1) {
            $orderby = 'G_UPTIMEORDERBY'; // 按照游戏上架时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'G_BUYNUM';
        } else {
            $orderby = 'G_GAMESTAR';
        }
        // 判断是否存在custom_game 拼接condition条件
        if ($type == 3) {
            $condition_pub = "STATUS = 0 AND G_NEW = 1 AND G_TEMPLATE != 2 AND G_TEMPLATE != 4 AND  G_CLOSE = 0";
        } else {
            $condition_pub = "STATUS = 0 AND G_NEW = 1 AND G_TEMPLATE != 2 AND  G_TEMPLATE != 4 AND  G_CLOSE = 0 AND G_GAMETYPE = ".$type;
        }
        if ($params['custom_game']) {
            $condition          = $condition_pub." AND G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby." DESC ";
            $count_condition    = $condition_pub." AND G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." ORDER BY " . $orderby." DESC ";
            $count_condition    = $condition_pub;
        }
        // 判断是否存在version， 拼接condition条件  
        if (!$params['version']) {
            $condition = $condition." LIMIT ".$offset.",".$per_page;
        }
        
        $data      = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 1, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态  
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        
        return $data;
    }
    
    /**
     * 分类游戏列表
     */
    public function category_game_list($params) {
        $per_page = $params['pagesize'];   // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $category = $params['category'];   // 游戏分类
        $uuid     = $params['uuid'];
        $type     = $params['type']; // 0:免费 1：收费  2：所有
        if ($params['custom_game']) {
            $table  = 'pl_channelgame';
        } else {
            $table  = 'PL_GAME';
        }
        if (!$type) {
            $type   = 0;
        }

        if ($params['orderby'] == 1) {
            $orderby = 'G_UPTIMEORDERBY'; // 按照游戏上架时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'G_BUYNUM';
        } else {
            $orderby = 'G_GAMESTAR';
        }
        $select = array(
            'IDX AS id',
            'G_ICON AS logo',
            'G_NAME AS name',
            'G_INFO AS intro',
            'G_OPERATIONINFO AS guide',
            'G_GAMECATS AS category',
            'G_GAMETYPE AS type',
            'G_GAMEGOLD AS price',
            'G_GAMEGOLDCURRENT AS price_current',
            'G_FILEDIRECTORY AS game_directory',
            'G_SHARENUM AS share_num',
            'G_PLAYNUM AS play_num',
            'G_GAMESTAR AS rating',
            'G_IMGS AS screenshots_str',
            'G_GAMEFILESIZE AS size',
            'G_BUYNUM AS buy_num',
            'UNIX_TIMESTAMP(G_UPTIMEORDERBY) AS create_time',
            'G_VERSION AS g_version',
        );
        // 判断是否存在custom_game 拼接condition条件
        $condition = "STATUS = 0 AND G_CLOSE = 0 AND G_GAMETYPE = ".$type."  AND G_TEMPLATE != 2";
        if ($params['custom_game']) {
            $condition .= " AND G_CHANNELIDX = ".$params['channel_id'];
            $select[] = 'G_CHANNELIDX AS channel_id';
            $select[] = 'G_GAMEIDX AS id';
        }
        $condition .= " ORDER BY " . $orderby . " DESC";
        $game_list = $this->get_row_array($condition, $select, $table, true);
        if ($game_list === false) {
            log_scribe('trace', 'model', 'app_syndata' . $this->ip . ': condition：' . http_build_query($condition));
            $this->error_->set_error(Err_Code::ERR_GAME_GET_FAIL);
            return false;
        }
        if (empty($game_list)) {
            log_scribe('trace', 'model', 'PL_GAME(select):' . $this->ip . 'where: G_GAMETYPE=>免费游戏');
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 游戏是否已经购买
        if ($uuid) {
            $buy_sql  = "SELECT B_GAMEIDX as game_id FROM PL_GAMEBUY WHERE B_USERIDX = ".$uuid;
            $buy_query = $this->DB->query($buy_sql);
            if ($buy_query === false) {
                $this->error_->set_error(Err_Code::ERR_DB);
                return false;
            }

            if ($buy_query->num_rows() > 0) {
                $buy_ids = $buy_query->result_array();
                foreach ($game_list as $k => $v) {
                    // 添加游戏的购买状态
                    foreach ($buy_ids as $k1 => $v1) {
                        if ($v1['game_id'] == $game_list[$k]['id']) {
                            $game_list[$k]['buy_status'] = 1;
                        }
                    }
                }
            }
        }
        
        $category_list = array();
        foreach ($game_list as $k => &$v) {
            // 将category、游戏截屏变成数组
            $v['category'] = explode(',', trim($v['category'], ','));
            $screenshots_arr = explode(',', trim($v['screenshots_str'], ','));
            foreach ($screenshots_arr as $val) {
                $v['screenshots'][] = $this->passport->get('game_url').$v['game_directory'].$val;
            }
            // 拼接游戏完整路径
            $v['logo']           = $this->passport->get('game_url').$v['game_directory'].$v['logo'];
            $game_list[$k]['game_directory'] = $this->passport->get('game_url').$v['game_directory'].'play/index.html';
            $v['buy_status']     = 0;
            
            $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态  
            if ($v_info['version'] && $params['version'] >= $v_info['version']) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $category_list[] = $v;
                    }
                }
            } else {
                if (in_array($category, $v['category'])) {
                    $category_list[] = $v;
                }
            }
               
        }
        
        if (empty($category_list)) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        $data['pagecount'] = ceil(count($category_list) / $per_page);
        $count_all = count(array_slice($category_list, $offset));
        
        if ($count_all >= $per_page) {
            $data['list'] = array_slice($category_list, $offset, $per_page);
        } else {
            $data['list'] = array_slice($category_list, $offset, $count_all);
        }
        
        return $data;
    }
    
    /**
     * 下载游戏
     */
    public function download($params)
    {
        $uuid    = $params['uuid'];
        $game_id = $params['id'];
        
        $game_info = $this->chk_game_cando($game_id);
        if($game_info === false) {
            $this->error_->set_error(Err_Code::ERR_GAME_CANNOT_SHARE);
            return false;
        }
        $ist_data = array(
            'D_USERIDX'     => $uuid,
            'D_NICKNAME'    => $params['nickname'],
            'D_GAMEIDX'     => $game_id,
            'D_GAMENAME'    => $game_info['name'],
            'STATUS'        => 0,
            'ROWTIME'       => $this->zeit,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $insert = $this->DB->insert('PL_GAMEDOWNLOAD', $ist_data);
        if ($insert === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 我下载过的游戏列表
     */
    public function download_list($params)
    {
        $uuid     = $params['uuid'];
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        if ($params['custom_game']) {
            $table  = 'PL_GAMEDOWNLOAD AS A, pl_channelgame AS B';
            $tab    = 2;
        } else {
            $table  = 'PL_GAMEDOWNLOAD AS A, PL_GAME AS B';
            $tab    = 1;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'A.ROWTIME'; // 按照游戏下载时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'B.G_BUYNUM';
        } else {
            $orderby = 'B.G_GAMESTAR';
        }
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub = "A.D_USERIDX = ".$uuid." AND  B.G_CLOSE = 0 AND B.STATUS = 0 AND A.STATUS = 0";
        if ($params['custom_game']) {
            $condition          = $condition_pub."  AND A.D_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby." DESC ";
            $count_condition    = $condition_pub."  AND A.D_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." AND A.D_GAMEIDX = B.IDX AND ORDER BY " . $orderby." DESC ";
            $count_condition    = $condition_pub ." AND A.D_GAMEIDX = B.IDX";
        }
        $condition = $condition." LIMIT ".$offset.",".$per_page;
        $data      = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 2, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }
    
    /**
     * 我购买过的游戏列表
     */
    public function buy_list($params)
    {
        $uuid     = $params['uuid'];
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        if ($params['custom_game']) {
            $table  = 'pl_gamebuy AS A, pl_channelgame AS B';
            $tab    = 2;
        } else {
            $table  = 'pl_gamebuy AS A, PL_GAME AS B';
            $tab    = 1;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'A.ROWTIME'; // 按照游戏购买时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'B.G_BUYNUM';
        } else {
            $orderby = 'B.G_GAMESTAR';
        }
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub = "A.B_USERIDX = ".$uuid." AND  B.G_CLOSE = 0 AND A.STATUS = 0 AND B.STATUS = 0";
        if ($params['custom_game']) {
            $condition          = $condition_pub."  AND A.B_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby." DESC ";
            $count_condition    = $condition_pub."  AND A.B_GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." AND A.B_GAMEIDX = B.IDX ORDER BY " . $orderby." DESC ";
            $count_condition    = $condition_pub ." AND A.B_GAMEIDX = B.IDX";
        }
        $condition = $condition." LIMIT ".$offset.",".$per_page;
        $data      = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 2, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }

    /**
     * 游戏详情接口
     */
    public function detail($params) {
        $game_id    = $params['id'];
        $uuid       = $params['uuid'];
        $select     = array(
            'IDX AS id',
            'G_ICON AS logo',
            'G_NAME AS name',
            'G_INFO AS intro',
            'G_OPERATIONINFO AS guide',
            'G_GAMECATS AS category',
            'G_GAMETYPE AS type',
            'G_GAMEGOLD AS price',
            'G_GAMEGOLDCURRENT AS price_current',
            'G_FILEDIRECTORY AS game_directory',
            'G_SHARENUM AS share_num',
            'G_PLAYNUM AS play_num',
            'G_GAMESTAR AS rating',
            'G_IMGS AS screenshots_str',
            'G_GAMEFILESIZE AS size',
            'G_BUYNUM AS buy_num',
            'UNIX_TIMESTAMP(ROWTIME) AS create_time',
        );
        if ($params['custom_game']) {
            $table      = 'pl_channelgame';
            $condition  = "STATUS = 0  AND G_GAMEIDX = " . $game_id;
            $select[]   = 'G_CHANNELIDX AS channel_id';
            $select[]   = 'G_GAMEIDX AS id';
        } else {
            $table      = 'PL_GAME';
            $condition  = "STATUS = 0  AND IDX = " . $game_id;
        }
        $game_list = $this->get_row_array($condition, $select, $table, true);
        if ($game_list === false) {
            log_scribe('trace', 'model', 'get_game_detail:' . $this->ip . ': condition：' . $condition);
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        if (!$game_list) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        $game_list[0]['category'] =  explode(',', trim($game_list[0]['category'], ','));
        $screenshots_arr = explode(',', trim($game_list[0]['screenshots_str'], ','));
        foreach($screenshots_arr as $val) {
            $game_list[0]['screenshots'][] = $this->passport->get('game_url').$game_list[0]['game_directory'].$val;
        }
        $game_list[0]['logo']           = $this->passport->get('game_url').$game_list[0]['game_directory'].$game_list[0]['logo'];
        $game_list[0]['game_directory'] = $this->passport->get('game_url').$game_list[0]['game_directory'].'play/index.html';
        
        $game_info = $game_list[0];
        if ($game_info['type'] != 3) { // 3:表示不是收费游戏，判断是否购买过
            // 游戏是否已经购买
            if ($uuid) {
                $buy_sql  = "SELECT B_GAMEIDX as game_id FROM PL_GAMEBUY WHERE B_GAMEIDX = ".$game_id." AND STATUS = 0 AND B_USERIDX = ".$uuid;
                $buy_query = $this->DB->query($buy_sql);
                if ($buy_query === false) {
                    $this->error_->set_error(Err_Code::ERR_DB);
                    return false;
                }
                $num = $buy_query->num_rows();
            }
            if ($num > 0 ) {
                $game_info['buy_status'] = 1;
            } else {
                $game_info['buy_status'] = 0;
            }
        }
        
        if ($game_info['type'] == 3) { // 3:表示收费游戏，获取product_id
            $this->load->model('pay_model');
            $product_id = $this->pay_model->get_product_id_by_gameid($game_info['id']);
            if (!$product_id) {
                $game_info['product_id'] = 0;
                $game_info['buy_status'] = 0;
            } else {
                $game_info['product_id'] = $product_id;
                // 判断收费游戏是否购买
                if ($uuid) {
                    $result = $this->pay_model->get_order_info_by_productid($product_id, $uuid);
                }
                if (!$result || !$uuid) { // 未购买
                    $game_info['buy_status'] = 0;
                } else {
                    $game_info['buy_status'] = 1;
                }
            }
        }
        return $game_info;
    }
    
    /**
     * 游戏的玩家排行接口（前十名，和自己的名次）
     */
    public function user_ranking($params) {
        $game_id = $params['id'];
        $uuid    = $params['uuid'];
        $table   = 'pl_gamescoreusertop AS A, PL_USER AS B';
        
        // 查询当前游戏，得分排行，倒序，还是正序
        $game_info = $this->get_game_info_by_gameid($game_id);
        if (!$game_info) {
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        if ((int)$game_info['score_order_type'] === 0) { // 0:顺序1:倒序
            $condition = "A.P_GAMEIDX = ".$game_id." AND A.STATUS = 0 AND A.P_USERIDX = B.IDX ORDER BY A.P_GAMESCORE DESC,A.ROWTIME DESC LIMIT 10";
        } else {
            $condition = "A.P_GAMEIDX = ".$game_id." AND A.STATUS = 0 AND A.P_USERIDX = B.IDX ORDER BY A.P_GAMESCORE ASC,A.ROWTIME DESC LIMIT 10";
        }
        // 查询前10名
        $select = array(
            'A.P_USERIDX AS uuid',
            'A.P_GAMESCORE AS scoring',
            'B.U_ICON AS image',
            'B.U_NICKNAME AS nickname',
            'B.U_TOTALPOINT AS integral',
        );
        $data['list'] = $this->get_row_array($condition, $select, $table, true);
        if ($data['list'] === false) {
            log_scribe('trace', 'model', 'app_syndata' . $this->ip . ': condition：' . http_build_query($condition));
            $this->error_->set_error(Err_Code::ERR_APP_CONFIG_NO_DATA);
            return false;
        }
        
        if (empty($data['list'])) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        $mine = array();
        foreach ($data['list'] as  $k=>$v) {
            if ($v['uuid'] == $uuid) {
                $mine = $v;
                $mine['rank'] = $k+1;
            }
        }
       
       if (!$mine) { // 当我不在前10名中，查询我的名次及信息
            // 查询我的成绩 -- start
            $condition2 = "A.P_USERIDX = " .$uuid. " AND A.P_GAMEIDX = " . $game_id . " AND A.STATUS = 0 AND A.P_USERIDX = B.IDX";
            $select2 = array(
                'A.P_USERIDX AS uuid',
                'A.P_GAMESCORE AS scoring',
                'B.U_ICON AS image',
                'B.U_NICKNAME AS nickname',
                'B.U_TOTALPOINT AS integral',
                'A.ROWTIME AS play_time',
            );
            $mine = $this->get_row_array($condition2, $select2, $table, false);
            
            if ($data['mine'] === false) {
                log_scribe('trace', 'model', 'user_ranking' . $this->ip . ': condition：' . http_build_query($condition));
                $this->error_->set_error(Err_Code::ERR_APP_CONFIG_NO_DATA);
                return false;
            }

            if (!empty($mine)) {
                $mine_score = $mine['scoring'];
                if ((int)$game_info['score_order_type'] === 0) { // 0:顺序1:倒序
                    $condition3 = "P_GAMEIDX = " . $game_id . " AND STATUS = 0 AND (P_GAMESCORE > ".$mine_score ." or P_GAMESCORE = ".$mine_score." AND UNIX_TIMESTAMP(ROWTIME) < UNIX_TIMESTAMP('".$mine['play_time']."'))";
                } else {
                    $condition3 = "P_GAMEIDX = " . $game_id . " AND STATUS = 0 AND (P_GAMESCORE < ".$mine_score ." or P_GAMESCORE = ".$mine_score." AND UNIX_TIMESTAMP(ROWTIME) < UNIX_TIMESTAMP('".$mine['play_time']."'))";
                }
                
                // 统计我当前的名次
                $select3 = array(
                    "count(IDX) AS rank",
                );
                
                $mine_rank = $this->get_row_array($condition3, $select3, 'pl_gamescoreusertop');
                
                if (!$mine_rank['rank']) {
                    $mine['rank'] = 1;
                } else {
                    $mine['rank'] = $mine_rank['rank'] + 1;
                }
                
            } else {
                $this->error_->set_error(Err_Code::ERR_GAME_NO_PLAY);
                return false;
            }
            // 查询我的成绩---end
        }
        
        $data['mine'] = $mine;
        return $data;
    }

    /*
     * 统计白鹭游戏次数
     */
    public function count_play_num($params)
    {
        //获取游戏信息
        $game_info = $this->get_game_info_by_gameid($params['id']);
        if (!$game_info) {
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        //增加游玩次数
        $fields = array('G_PLAYNUM' => $game_info["play_num"]+1);
        $upt_cgame = $this->update_game_info($params['id'], $fields);
        if(!$upt_cgame)
        {
            return false;
        }
        return TRUE;
    }
    
    /**
     * 上传得分
     */
    public function upload_scoring($params) {
        $uuid           = $params['uuid'];
        $game_id        = $params['id'];
        $scoring        = $params['scoring']; // 游戏得分
        $spend_time     = $params['spend_time'];
        $update_status  = $params['update_status'];// 游戏更新类型
        $nickname       = $params['nickname'];
        
        // 1. 游戏得分--换算-->用户积分
        $game_info = $this->get_game_info_by_gameid($game_id);
        if (!$game_info) {
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        if ($params['custom_game']) {
            $game_info1 = $this->game_model->get_channel_game_info($game_id);
            $ga = $game_info1['ga'];
        } else {
            $ga = $game_info['ga'];
        }
        if (!$game_info['score_max']) {
            $this->error_->set_error(Err_Code::ERR_GAME_MAX_SCORE_NOT_0);
            return false;
        }
        $x         = $scoring / $game_info['score_max'];
        $coin      = 0;
        // 2.判断  游戏积分排序规则0:顺序1:倒序
        if ((int)$game_info['score_order_type'] === 0) {       // 游戏积分--顺序
            // 是否创游戏最高分记录，更新(游戏表)
            if ($scoring > $game_info['score_max']) {
                $fields['G_GAMESCOREMAX']       = $scoring;
                $fields['G_GAMESCOREMAXTIME']   = $spend_time;
                //创游戏最高分记录, 将 游戏得分--换算-->用户积分
                $ta     = $spend_time/600;
                $point  = round($ga * $ta * 80 * $x * $x + 1);
                
                // 创游戏最高纪录，奖励积分和金币 --- 破纪录任务
                    //@1  查询破最高记录可获得的积分和金币
                    $task_type = 'MyTopGameScore';
                    $this->load->model('task_model');
                    $task_info = $this->task_model->get_sys_task_by_type($task_type);
                    if (!empty($task_info)) {
                        $res = $this->task_model->inser_user_task_completion($uuid, $task_info);
                        if (!$res) {
                            $this->error_->set_error(Err_Code::ERR_BREACH_SCORING_TASK_TAIL);
                            return false;
                        }
                    }
                    // @2 创最高纪录---推送消息
                    // @2 创最高纪录---推送消息  ---- 优化
                       // 查询用户最好成绩 start
                       $condition = "A.P_GAMEIDX = ".$game_id." AND A.STATUS = 0 AND A.P_USERIDX = B.IDX ORDER BY A.P_GAMESCORE DESC,A.ROWTIME DESC";
                        $select = array(
                            'A.P_USERIDX AS uuid',
                            'A.P_GAMESCORE AS scoring',
                            'B.U_ICON AS image',
                            'B.U_NICKNAME AS nickname',
                            'B.U_TOTALPOINT AS integral',
                        );
                        $top_user = $this->get_row_array($condition, $select, "pl_gamescoreusertop AS A ,PL_USER AS B");
                       // 查询用户最好成绩 end 
                    if (!$top_user) {
                        $to_nickname = '系统';
                        $to_uuid = '0';
                    } else {
                        $to_nickname = $top_user['nickname'];
                        $to_uuid = $top_user['uuid'];
                    }
                    if ($to_uuid) {
                        $this->CI->tasklib->send_msg_by_top_score($to_uuid,$to_nickname,$nickname);
                    }
            } else {
                // 没有创最高分记录， 将 游戏得分--换算-->用户积分
                $ta     = $game_info['time']/600;
                $point  = round($ga * $ta * 80 * $x * $x + 1);
            }
        } else {      // 游戏积分--倒序
            // 判断是否是游戏的最高分，更新(游戏表)
            if ($scoring < $game_info['score_max']) {
                $fields['G_GAMESCOREMAX']       = $scoring;
                $fields['G_GAMESCOREMAXTIME']   = $spend_time;
                //创游戏记录, 将 游戏得分--换算-->用户积分
                $ta     = $spend_time/600;
                $point  = round($ga * $ta * 80 * $x * $x + 1);
                
                // 创游戏最高纪录，奖励积分和金币
                    //@1  查询破最高记录可获得的积分和金币
                    $task_type = 'MyTopGameScore';
                    $this->load->model('task_model');
                    $task_info = $this->task_model->get_sys_task_by_type($task_type);
                    if (!empty($task_info)) {
                         $res = $this->task_model->inser_user_task_completion($uuid, $task_info);
                         if (!$res) {
                            $this->error_->set_error(Err_Code::ERR_BREACH_SCORING_TASK_TAIL);
                            return false;
                        }
                    }
                    // @2 创最高纪录---推送消息  ---- 优化
                       // 查询用户最好成绩 start
                       $condition = "A.P_GAMEIDX = ".$game_id." AND A.STATUS = 0 AND A.P_USERIDX = B.IDX ORDER BY A.P_GAMESCORE ASC,A.ROWTIME DESC LIMIT 1";
                        $select = array(
                            'A.P_USERIDX AS uuid',
                            'A.P_GAMESCORE AS scoring',
                            'B.U_ICON AS image',
                            'B.U_NICKNAME AS nickname',
                            'B.U_TOTALPOINT AS integral',
                        );
                        $top_user = $this->get_row_array($condition, $select, "pl_gamescoreusertop AS A ,PL_USER AS B");
                       // 查询用户最好成绩 end 
                    if (!$top_user) {
                        $to_nickname = '系统';
                        $to_uuid = '0';
                    } else {
                        $to_nickname = $top_user['nickname'];
                        $to_uuid = $top_user['uuid'];
                    }
                    if ($to_uuid) {
                        $this->CI->tasklib->send_msg_by_top_score($to_uuid,$to_nickname,$nickname);
                    } 
            } else {
                // 没有创最高分记录， 将 游戏得分--换算-->用户积分
                $ta     = $game_info['time']/600;
                $point  = round($ga * $ta * 80 * $x * $x + 1);
            }       
        }
        
        // 游戏得分，与最高分相同时，判断时间
        if ($scoring == $game_info['score_max']) {
            if ($spend_time < $game_info['time']) {
                $fields['G_GAMESCOREMAXTIME']   = $spend_time;
            }
        }
        // 更新Pl_game表
        $res = $this->update_game_info($game_id, $fields);
        if(!$res) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_GAME_BUY_NUM_FAIL);
            return false;
        }
        // 判断是否需要更新update_channel_game_info()
        if ($params['custom_game']) {
            $upt_cgame = $this->update_channel_game_info($game_id, $fields);
            if (!$upt_cgame) {
                $this->error_->set_error(Err_Code::ERR_UPDATE_GAME_INFO_FAIL);
                return false;
            }
        }
        
        if ($point < 0) { // 玩家获得 游戏得分不能小于0
            $point = 0;
        }
        if ($point > 1000) {
            $point = 1000;
        }
        // 2 .将用户游戏得分 插入 “游戏得分获得记录表”
        $data1 = array(
            'P_USERIDX'    => $uuid,
            'P_NICKNAME'   => $nickname,
            'P_GAMEIDX'    => $game_id,
            'P_GAMENAME'   => $game_info['name'],
            'P_GAMESCORE'  => $scoring,
            'P_GAMESCORETIME' => $spend_time,
            'P_POINT'      => $point,
            'P_UPDATETYPE' => $update_status,
            'STATUS'       => 0,
            'ROWTIME'      => $this->zeit,
            'ROWTIMEUPDATE'=> $this->zeit,
        );
        
        $res = $this->DB->insert('PL_GAMESCOREHISTORY', $data1);
        if ($res === false) {
            log_scribe('trace', 'model', 'PL_GAMESCOREHISTORY:' . $this->ip . '  data：' . http_build_query($data1));
            $this->error_->set_error(Err_Code::ERR_GAME_SCORING_UPDATE_FAIL);
            
            return false;
        }
        
        // 3.查看 "用户游戏积分最好成绩"表，判断是更新数据, 还是插入数据
        $sql2 = "SELECT P_GAMESCORE FROM PL_GAMESCOREUSERTOP WHERE STATUS = 0 AND P_USERIDX = " . $uuid . " AND P_GAMEIDX = " . $game_id;
        $best_score = $this->DB->query($sql2);
        
        if ($best_score === false) {
            log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:' . $this->ip . '  where： S_GAMEIDX = ' . $game_id . " AND P_USERIDX = " . $uuid);
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        
        if ($best_score->num_rows() > 0) { // 更新
            $score_info = $best_score->row_array();
            
            if ((int)$game_info['score_order_type'] === 0) { // 游戏 -- 顺序
                // 判断是否是最好成绩，更新(用户最好成绩表)
                if ($scoring > $score_info['P_GAMESCORE']) {
                    $where = array(
                        'P_USERIDX' => $uuid,
                        'P_GAMEIDX' => $game_id,
                        'STATUS' => 0
                    );
                    $upd_data = array(
                        'P_GAMESCORE'   => $scoring,
                        'P_POINT'       => $point,
                        'P_GAMESCORETIME' => $spend_time,
                        'ROWTIMEUPDATE' => $this->zeit,
                    );
                    $this->DB->where($where);
                    $update = $this->DB->update('PL_GAMESCOREUSERTOP', $upd_data);

                    if ($update === false) {
                        log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:(updata)' . $this->ip . '  where： P_GAMEIDX = ' . $game_id . " AND P_USERIDX = " . $uuid);
                        $this->error_->set_error(Err_Code::ERR_GAME_SCORING_UPDATE_FAIL);

                        return false;
                    }
                } else { // 只更新玩过当前游戏的时间， 用于我最近玩得的游戏排行
                    $where = array(
                        'P_USERIDX' => $uuid,
                        'P_GAMEIDX' => $game_id,
                        'STATUS' => 0
                    );
                    $upd_data = array(
                        'ROWTIMEUPDATE' => $this->zeit,
                    );
                    $this->DB->where($where);
                    $update = $this->DB->update('PL_GAMESCOREUSERTOP', $upd_data);

                    if ($update === false) {
                        log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:(updata)' . $this->ip . '  where： P_GAMEIDX = ' . $game_id . " AND P_USERIDX = " . $uuid);
                        $this->error_->set_error(Err_Code::ERR_GAME_SCORING_UPDATE_FAIL);
                        return false;
                    }
                }
            } else {   // 游戏 -- 倒序
                // 判断是否是最好成绩，更新(用户最好成绩表)
                if ($scoring < $score_info['P_GAMESCORE']) {
                    $where = array(
                        'P_USERIDX' => $uuid,
                        'P_GAMEIDX' => $game_id,
                        'STATUS' => 0
                    );
                    $upd_data = array(
                        'P_GAMESCORE'   => $scoring,
                        'P_POINT'       => $point,
                        'P_GAMESCORETIME' => $spend_time,
                        'ROWTIMEUPDATE' => $this->zeit,
                    );
                    $this->DB->where($where);
                    $update = $this->DB->update('PL_GAMESCOREUSERTOP', $upd_data);
                    
                    if ($update === false) {
                        log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:(updata)' . $this->ip . '  where： P_GAMEIDX = ' . $game_id . " AND P_USERIDX = " . $uuid);
                        $this->error_->set_error(Err_Code::ERR_GAME_SCORING_UPDATE_FAIL);

                        return false;
                    }
                } else {
                    $where = array(
                        'P_USERIDX' => $uuid,
                        'P_GAMEIDX' => $game_id,
                        'STATUS' => 0
                    );
                    $upd_data = array(
                        'ROWTIMEUPDATE' => $this->zeit,
                    );
                    $this->DB->where($where);
                    $update = $this->DB->update('PL_GAMESCOREUSERTOP', $upd_data);

                    if ($update === false) {
                        log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:(updata)' . $this->ip . '  where： P_GAMEIDX = ' . $game_id . " AND P_USERIDX = " . $uuid);
                        $this->error_->set_error(Err_Code::ERR_GAME_SCORING_UPDATE_FAIL);
                        return false;
                    }
                }
            }  
        } else {
            // 插入数据
            $insert = $this->DB->insert('PL_GAMESCOREUSERTOP', $data1);

            if ($insert === false) {
                log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:(insert)' . $this->ip . '  data：' . http_build_query($upd_data));
                $this->error_->set_error(Err_Code::ERR_DB);
                
                return false;
            }
        }

        // 5. 更新用户信息表中的 总积分和当前积分
        $sql5 = "UPDATE PL_USER SET U_TOTALPOINT = U_TOTALPOINT + " . $point . ", U_POINT = U_POINT + " . $point . ", ROWTIMEUPDATE = '" . $this->zeit . "' WHERE IDX = " . $uuid;
        $user_update = $this->DB->query($sql5);
        if ($user_update === false) {
            log_scribe('trace', 'model', 'PL_GAMESCOREUSERTOP:(insert)' . $this->ip . '  where： S_GAMEIDX = ' . $game_id . " and S_GAMESCORE > " . $scoring);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        //6.记录用户积分变更历史
        $userinfo               = $this->utility->get_user_info($uuid);
        $userinfo['token']      = $params['token'];
        $expires                = time()+$this->passport->get('token_expire');
        $userinfo['expires']    = $expires;
        
        $integral_info = array(
            'change_integral' => $point, // 用户获得积分
            'integral'        => $userinfo['integral'], // 用户可用积分
        );
        $res = $this->user_model->record_integral_change_history($params['uuid'], $userinfo['nickname'], $integral_info);
        if (!$res) {
            return false;
        }
        
        // 判断该游戏是否是开发商上传游戏
        if ((int)$game_info['is_developer'] === 1) {
            $res = $this->developer_game_play_info($game_id, $uuid);
            if (!$res) {
                // 无玩过记录，插入
                $data_insert = array(
                    'game_id'   => $game_info['game_id'],
                    'game_name' => $game_info['game_name'],
                    'uuid'      => $uuid,
                    'nickname'  => $nickname,
                );
                $result = $this->developer_game_insert($data_insert);
                if (!$result) {
                    return false;
                }
            } else {
                $date = strtotime(date('Y-m-d', time()));
                $date1 = strtotime(date('Y-m-d', $res['rowtime']));
                // if ((int)$res['first_play'] === 1 && $date != $date1) { // 是第一次玩，昨天以前没玩过
                if ($date != $date1) { // 是第一次玩，昨天以前没玩过
                    // 更新玩过记录，改为不是第一次玩
                    $data_update = array(
                        'game_id'   => $game_info['game_id'],
                        'uuid'      => $uuid,
                    );
                    $result = $this->developer_game_update($data_update);
                    if (!$result) {
                        return false;
                    }
                }
            }
        }
        
        // 7.上传得分之后，判断用户积分是否大于等于900,推送消息
        if ($userinfo['integral'] >= 900) {
            $this->tasklib->send_msg_by_integral($uuid);
        }
        return true;
    }
    
    /**
     * 根据开发商游戏IDX，查询游戏是否被当前用户玩过
     */
    public function developer_game_play_info($game_id, $uuid)
    {
        if (!$game_id || !$uuid) {
            $this->error_->set_error(Err_Code::ERR_DEVELOPER_GAME_PLAY_RECORD_FAIL);
            return false;
        }
        $table = 'pl_opengameplaystatis';
        $condition = 'STATUS = 0 AND P_GAMEIDX = '.$game_id.' AND P_USERIDX = '.$uuid;
        $select = array(
            'IDX AS idx',
            'P_FIRSTPLAY AS first_play',
            'UNIX_TIMESTAMP(ROWTIME) AS rowtime',
            'UNIX_TIMESTAMP(ROWTIMEUPDATE) AS rowtime_update',
        );
        $res = $this->get_row_array($condition, $select, $table);
        return $res;
    }
    
    /**
     * 插入开发商游戏，被玩过的统计
     */
    public function developer_game_insert($params)
    {
        $table = 'pl_opengameplaystatis';
        $data = array(
            'P_GAMEIDX'     => $params['game_id'],
            'P_GAMENAME'    => $params['game_name'],
            'P_USERIDX'     => $params['uuid'],
            'P_NICKNAME'    => $params['nickname'],
            'P_FIRSTPLAY'   => 1, // 是第一次玩
            'STATUS'        => 0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $ist = $this->DB->insert($table, $data);
        if ($ist === FALSE) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 更新开发商游戏，被玩过的统计
     */
    public function developer_game_update($params)
    {
        $table = 'pl_opengameplaystatis';
        $condition = array(
            'P_GAMEIDX' => $params['game_id'],
            'P_USERIDX' => $params['uuid'],
        );
        $data = array(
            'P_FIRSTPLAY' => 0, // 0:不是第一次玩， 1:第一次玩
        );
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $upd = $this->DB->update($table, $data, $condition);
        if ($upd === FALSE) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 购买游戏
     */
    public function buy($params) {
        $uuid          = $params['uuid'];
        $game_id       = $params['id'];
        $price_current = $params['price_current'];
        // 判断游戏是否可购买
        $game_info = $this->chk_game_cando($game_id, 1);
        if($game_info === false) {
            // 游戏已关闭，不能购买
            $this->error_->set_error(Err_Code::ERR_GAME_CANNOT_BUY);
            return false;
        }
        //校验实际价格与用户输入价格是否一致
        if($price_current != $game_info['price_current']){
            $this->error_->set_error(Err_Code::ERR_GAME_PRICE_NOT_CONFIRM);
            return false;
        }
        if($params['coin'] < $game_info['price_current']){
            $this->error_->set_error(Err_Code::ERR_GAME_COIN_NOT_ENOUGH);
            return false;
        }
        $buy_data = array(
            'B_USERIDX'         => $uuid,
            'B_NICKNAME'        => $params['nickname'],
            'B_GAMEIDX'         => $game_id,
            'B_GAMENAME'        => $game_info['name'],
            'B_GAMEGOLD'        => $price_current,
            'B_GAMEGOLDCURRENT' => $game_info['price_current'],
            'STATUS'            => 0,
            'ROWTIME'           => $this->zeit,
            'ROWTIMEUPDATE'     => $this->zeit
        );
        $rst = $this->DB->insert('pl_gamebuy', $buy_data);
        if ($rst === false) {
            log_scribe('trace', 'model', 'pl_gamebuy:(insert)' . $this->ip. '  where：params = ' . http_build_query($buy_data));
            $this->error_->set_error(Err_Code::ERR_UPDATE_GAME_BUY_FAIL);
            return false;
        }
        $this->load->model('user_model');
        $coin = $params['coin'] - $price_current;
        // 减去用户花掉的金币
        $fields = array('U_GOLD' => $coin);
        $rst = $this->user_model->update_user_info($uuid,$fields);
        if ($rst === false) {
            log_scribe('trace', 'model', 'PL_USER:(update)' . $this->ip . '  where：uuid = ' . $uuid);
            $this->error_->set_error(Err_Code::ERR_BUY_GAME_REDUCE_COIN_FAIL);
            return false;
        }
        //记录金币消费历史
        $coin_info = array(
            'change_coin'   => $price_current,
            'coin'          => $coin,
        );
        $rst = $this->user_model->record_coin_change_history($uuid,$params['nickname'],$coin_info,1,1);
        if(!$rst) {
            return false;
        }
        // 更新游戏表中，购买的次数
        $fields = array('G_BUYNUM' => (int)$game_info['buy_num'] + 1);
        $rst    = $this->update_game_info($game_id, $fields);
        if(!$rst) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_GAME_BUY_NUM_FAIL);
            return false;
        }
        // 判断是否需要更新update_channel_game_info()
        if ($params['custom_game']) {
            $upt_cgame = $this->update_channel_game_info($game_id, $fields);
            if (!$upt_cgame) {
                $this->error_->set_error(Err_Code::ERR_UPDATE_GAME_INFO_FAIL);
                return false;
            }
        }
        // 判断游戏是否是，开发商上传游戏
        if ((int)$game_info['is_developer'] === 1) {
            // 是开发商上传游戏， 添加购买统计
            $data_inset = array(
                'game_id'   => $game_id,
                'game_name' => $game_info['name'],
                'prop_id'   => 0,
                'uuid'      => $uuid,
                'nickname'  => $params['nickname'],
                'price'     => $game_info['price_current'],
                'STATUS'    => 0,
            );
            $res = $this->developer_buy_insert($data_insert);
            if (!$res) {
                return false;
            }
        }
        return true;
    }
    
    //游戏是否购买过
    function chk_game_is_buy($uuid,$game_id){
        $select = array(
            'IDX AS buy_id',
        );
        $table = 'pl_gamebuy';
        $condition = 'B_USERIDX = '.$uuid.' AND B_GAMEIDX = '.$game_id." AND STATUS = 0";
        $data = $this->get_row_array($condition, $select, $table);
        if($data === false || empty($data)){
            return false;
        }
        return $data;
    }
    
    function developer_buy_insert($params)
    {
        $table = 'pl_opengamebuystatis';
        $data   = array(
            'B_GAMEIDX' => $params['game_id'],
            'B_GAMENAME'    => $params['game_name'],
            'B_PROPIDX'     => $params['prop_id'],
            'B_USERIDX'     => $params['uuid'],
            'B_NICKNAME'    => $params['nickname'],
            'B_PRICE'       => $params['price'],
            'STATUS'        => 0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $ist = $this->DB->insert($table, $data);
        if ($ist === FALSE) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }

    /**
     * 分享游戏
     */
    public function share($params) {
        $uuid       = $params['uuid']; // 用户id
        $game_id    = $params['id'];   // 游戏id
        $type       = $params['type']; // 游戏类型 0:免费游戏 1:金币游戏 2:制作游戏
        $channel    = $params['channel'];// 分享渠道 0：微博 1：微信
        // 判断游戏是否可分享
        if($type == 2) {
            $game_info = $this->chk_produce_game_cando($game_id);
            $_game_id = $game_info['game_id'];
            $_making_id = $game_id;
            $hat_id     = $game_info['hat_id'];
        } else {
            $game_info = $this->chk_game_cando($game_id,$type);
            $_game_id = $game_id;
            $_making_id = 0;
        }
        if($game_info === false || empty($game_info)) {
            $this->error_->set_error(Err_Code::ERR_GAME_CANNOT_SHARE);
            return false;
        }
        $share_data = array(
            'T_USERIDX'     => $uuid,
            'T_NICKNAME'    => $params['nickname'],
            'T_GAMEIDX'     => $_game_id,
            'T_GAMENAME'    => $game_info['name'],
            'T_GAMETYPE'    => $type,
            'T_MAKINGIDX'   => $_making_id,
            'T_SHARETYPE'   => $channel,
            'T_SHAREPLAYNUM' => 0,
            'STATUS'        => 0,
            'ROWTIME'       => $this->zeit,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $rst = $this->DB->insert('pl_gameshare', $share_data);
        if ($rst === false) {
            $this->error_->set_error(Err_Code::ERR_GAME_SHARE_RECORD_FAIL);
            log_scribe('trace', 'model', 'pl_gameshare:(insert)' . $this->ip);
            return false;
        }
        $share_id = $this->DB->insert_id();
        // 分享游戏之后,更新游戏表或制作游戏表中的 分享次数
        $share_num_new = (int)$game_info['share_num'] + 1;
        if ($type == 2) { 
            // 制作游戏
            $fields = array('M_SHARENUM' => $share_num_new);
            $res    = $this->update_produce_game_info($game_id, $fields);
        } else {
            $fields = array('G_SHARENUM' => $share_num_new);
            $res    = $this->update_game_info($game_id, $fields);
        }
        if ($res === false) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_GAME_SHARE_NUM_FAIL);
            return false;
        }
        $pic = "";
        // 分享成功后，通过$game_id，获取游戏对应的，游戏地址
        if ($type == 2) { // 制作游戏，查询制作表，获取对应的游戏ID，和template（制作游戏的directory可能对应的不正确） ,来拼接游戏路径
            $condition  = "STATUS = 0 AND IDX = ".$game_id;
            $select     = array('M_GAMEIDX AS game_idx', 'M_PHOTOURL AS pic');
            $table      = "pl_making";
            $res        = $this->get_row_array($condition, $select, $table);
            if (!$res['game_idx']) {
                $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
                return false;
            }
            $game_id = $res['game_idx'];
            $pic     = $res['pic'];
        }
        
        // 普通游戏，查询普通游戏表，获取游戏的游戏路径
        $condition_1 = "STATUS = 0 AND IDX = ".$game_id;
        $select_1    = array('G_TEMPLATE AS template','G_FILEDIRECTORY AS game_directory');
        $table_1     = "pl_game";
        $res_1       = $this->get_row_array($condition_1, $select_1, $table_1);
        if (!$res_1) {
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        
        if ($type == 2) { // 制作游戏
            if ($res_1['template'] == 2) {
                $game_dir = '/making'.$res_1['game_directory'];
            } else {
                $replacement  =  '/making';
                $pattern  =  '/^\/games/i';
                $game_dir = preg_replace($pattern, $replacement, $res_1['game_directory']);
            }
            // 制作游戏获取face头像
            if ($pic) {
                $pic = $this->passport->get('game_url').$pic;
            }
            // 获取帽子头像
            if ($hat_id) {
                $hat_info       = $this->hat_info($hat_id);
                if ($hat_info) {
                     $temp['small']  = $this->passport->get('game_url').$hat_info['small'];
                     $temp['medium'] = $this->passport->get('game_url').$hat_info['medium'];
                     $temp['large']  = $this->passport->get('game_url').$hat_info['large'];
                }
            }
        } else { // 普通游戏
            $game_dir = $res_1['game_directory'];
        }
        
        if ($res_1['game_directory']) {
            $game_url = $this->passport->get('game_url').$game_dir."play/index.html";
        }
        
        if ($pic) {
            return $game_url."?share_id=".$share_id."&face=".$pic."&temp=".$temp['large'];
        }
        return $game_url."?share_id=".$share_id;
    }
    //用户是否分享过该游戏
    function chk_game_share_by_uuid($params){
        $select = array(
            'IDX share_id',
        );
        $condition = array(
            'T_USERIDX' => $params['uuid'],
            'T_GAMETYPE' => $params['type'],
            'T_SHARETYPE' => $params['channel'],
        );
        if($params['type'] == 2){
           $condition['T_MAKINGIDX'] = $params['id'];
        } else {
            $condition['T_GAMEIDX'] = $params['id'];
        }
        $table = 'pl_gameshare';
        $data = $this->get_row_array($condition, $select, $table);
        if($data === false || empty($data)){
            return false;
        }
        return true;
    }
    
    /**
     * 游戏评分评论接口
     */
    public function comment($params) {
        $uuid    = $params['uuid'];
        $game_id = $params['id'];
        $content = $params['content'];
        $scoring = $params['scoring'];
       //校验游戏是否可评论
        $game_info = $this->chk_game_cando($game_id);
        if($game_info === false) {
            $this->error_->set_error(Err_Code::ERR_CANNOT_COMMENT_GAME);
            return false;
        }
        $data = array(
            'C_USERIDX'     => $uuid,
            'C_NICKNAME'    => $params['nickname'],
            'C_GAMEIDX'     => $game_id,
            'C_GAMENAME'    => $game_info['name'],
            'C_STAR'        => $scoring,
            'C_INFO'        => $content,
            'C_VER'         => $game_info['version'],
            'STATUS'        => 0,
        );
        
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        
        $query = $this->DB->insert('pl_gamecomments', $data);
        if ($query === false) {
            log_scribe('trace', 'model', 'pl_gamecomments:(insert)' . $this->ip . '  where：uuid = ' . $uuid . " game_id = " . $game_id);
            $this->error_->set_error(Err_Code::ERR_INSERT_COMMENT_FAIL);
            return false;
        }
        
        //获取该游戏用户最高评分
        $comment_star_tb = 'pl_gamecommentsstar';
        $select = array('C_STAR AS scoring_star');
        $condition = array(
            'C_GAMEIDX'     => $game_id,
            'C_USERIDX'     => $uuid,
            'STATUS'        => 0,
        );
        $score_info = $this->get_row_array($condition, $select, $comment_star_tb);
        
        if($score_info === false || empty($score_info)){
            //插入用户最高评分
            $data = array(
                'C_USERIDX'     => $uuid,
                'C_NICKNAME'    => $params['nickname'],
                'C_GAMEIDX'     => $game_id,
                'C_GAMENAME'    => $game_info['name'],
                'C_STAR'        => $scoring,
                'STATUS'        => 0,
            );
            $this->DB->set('ROWTIME', $this->zeit);
            $this->DB->set('ROWTIMEUPDATE', $this->zeit);
            $query = $this->DB->insert($comment_star_tb, $data);
            if ($query === false) {
                log_scribe('trece', 'model', 'pl_gamecommentsstar:(insert)' . $this->ip . '  where：uuid = ' . $uuid . " game_id = " . $game_id);
                $this->error_->set_error(Err_Code::ERR_UPDATE_SCORE_STAR_FAIL);
                return false;
            }
        } else {
            if($score_info['scoring_star'] < $scoring) {
                //修改用户最高评分表
                $this->DB->set('C_STAR', $scoring);
                $this->DB->set('ROWTIMEUPDATE', $this->zeit);
                $this->DB->where('C_USERIDX', $uuid);
                $this->DB->where('C_GAMEIDX', $game_id);
                $query = $this->DB->update($comment_star_tb);
                // 记录数据库错误日志
                if($query === false){
                    log_scribe('trece', 'model', 'pl_gamecommentsstar:(update)' . $this->ip . '  where：uuid = ' . $uuid . " game_id = " . $game_id);
                    $this->error_->set_error(Err_Code::ERR_UPDATE_SCORE_STAR_FAIL);
                    return false;
                }
            }
        }
        
        // 计算游戏的综合评分
        $game_score = $this->_get_game_score_by_gameid($game_id);
        if($game_score === false) return false;
        //更新游戏表中的 G_GAMESTAR
        $score_num = (int)$game_info['score_num']+1;
        $fields = array('G_GAMESTAR' => $game_score,'G_GAMESTARNUM' => $score_num);
        $rst = $this->update_game_info($game_id,$fields);
        if(!$rst) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_GAMESCORE_STAR_FAIL);
            return false;
        }
        //更新pl_channelgame表中的 G_GAMESTAR
        $rst = $this->update_channel_game_info($game_id,$fields);
        if(!$rst) {
            $this->error_->set_error(Err_Code::ERR_UPDATE_GAMESCORE_STAR_FAIL);
            return false;
        }
        return true;
    }
    
    //计算游戏综合评分
    function _get_game_score_by_gameid($game_id){
        $sql = "SELECT AVG(C_STAR) AS score_star FROM pl_gamecommentsstar WHERE C_GAMEIDX=".$game_id;
        $query = $this->DB->query($sql);
        
        // 记录数据库错误日志
        if ($query === false) {
            log_scribe('trace','model','get_user_info_by_uuid'. $this->ip.': game_id：'.$game_id);
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $score_star = 0;
        if ($query->num_rows() > 0) {
            $ret = $query->result_array();
            $score_star = $ret[0]['score_star'];
        }
        return $score_star;
    }
    
    /**
     * 获取游戏的评论列表接口
     */
    public function comment_list($params) {
        $game_id     = $params['id'];
        $recordindex = $params['recordindex'];
        $pagesize    = $params['pagesize'];
        $table       = 'pl_gamecomments';
        $select = array(
            "IDX AS id",
            "C_USERIDX AS uuid",
            "C_NICKNAME AS nickname",
            "C_GAMEIDX AS game_id",
            "C_INFO AS content",
            "C_STAR AS scoring",
            "UNIX_TIMESTAMP(ROWTIME) AS create_time",
        );
        $condition = "C_GAMEIDX = ".$game_id." AND STATUS = 0 ORDER BY ROWTIME DESC limit ".$recordindex.",".$pagesize;
        $comment_list = $this->get_row_array($condition, $select, $table, true);
        if($comment_list === false) {
            log_scribe('trace', 'model', 'get_comment_list :'.$this->ip.'  condition :'.$condition);
            $this->error_->set_error(Err_Code::ERR_GET_COMMENT_LIST_FAIL);
            return false;
        }
        if (!$comment_list) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        //获取总条数
        $count_condition = "C_GAMEIDX = ".$game_id." AND STATUS = 0";
        $pagecount = $this->get_data_num($count_condition, $table);
        if($pagecount === false){
            $this->error_->set_error(Err_Code::ERR_GET_COMMENT_COUNT_FAIL);
            return false;
        }
        $data['list'] = $comment_list;
        $data['pagecount'] = ceil($pagecount / $pagesize);
        return $data;
    }

    //校验游戏是否已收藏
    function chk_game_favorite($uuid,$game_id){
        $select = array('IDX as id');
        $condition['F_USERIDX'] = $uuid;
        $condition['F_GAMEIDX'] = $game_id;
        $condition['STATUS'] = 0;
        $data = $this->get_row_array($condition, $select, 'pl_gamefavorites');
        if($data === false || empty($data)) { 
            return false;
        }
        return $data['id'];
    }
    
    /**
     * 收藏游戏接口
     */
    public function favorite($params)
    {
        $uuid       = $params['uuid'];
        $game_id    = $params['id'];
        //获取game info
        $game_info = $this->chk_game_cando($game_id);
        if($game_info === false) {
            $this->error_->set_error(Err_Code::ERR_CANNOT_FAVORITE_GAME);
            return false;
        }
        $data = array(
            'F_USERIDX'  => $uuid,
            'F_NICKNAME' => $params['nickname'],
            'F_GAMEIDX'  => $game_id,
            'F_GAMENAME' => $game_info['name'],
            'F_GAMETYPE' => $game_info['type'],
            'STATUS'     => 0,
        );
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $query = $this->DB->insert('pl_gamefavorites', $data);
        if ($query === false) {
            log_scribe('trace', 'model', 'pl_gamefavorites:(insert)'.$this->ip. "where：game_id = ".$game_id);
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        return true;
    }
    
    /**
     * 取消收藏游戏接口
     */
    public function delete_favorite($params)
    {
        $where = array(
            'F_USERIDX' => $params['uuid'],
            'F_GAMEIDX' => $params['id']
        );
        $query = $this->DB->delete('pl_gamefavorites', $where);
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DELETE_FAVORITE_FAIL);
            log_scribe('trace', 'model', 'pl_gamefavorites:(delete)'.$this->ip. "where： uuid = ".$params['uuid']." game_id = ".$params['id']);
            return false;
        }
        return true;
    }
    
    //检查游戏是否可操作 分享/购买/收藏/评论/制作，评分 type ：1 购买
    function chk_game_cando($game_id,$type = -1){
        $table = 'pl_game';
        $select = array(
            'IDX AS id',
            'G_NAME AS name',
            'G_GAMETYPE AS type',
            'G_BUYNUM AS buy_num',
            'G_SHARENUM AS share_num',
            'G_GAMESTARNUM AS score_num',
            'G_PLAYNUM AS play_num',
            'G_SHAREPLAYNUM AS share_open_num',
            'G_GAMEGOLD AS price',
            'G_GAMEGOLDCURRENT AS price_current',
            'G_ISDEVELOPER AS is_developer',
            'G_VERSION AS version',
        );
        $condition = array(
            'IDX'           => $game_id,
            'STATUS'        => 0,
            'G_CLOSE'       => 0,
        );
        if($type != -1) {
            $condition['G_GAMETYPE'] = $type;
        }
        $data = $this->get_row_array($condition, $select, $table);
        if ($data === false || empty($data)) {
            log_scribe('trace', 'model', 'chk_game_cando:' . $this->ip . ': condition：' . http_build_query($condition));
            return false;
        }
        return $data;
    }
    /* 修改游戏信息
     * $fields = array(
     * G_GAMESTAR => ",//游戏评级
     * G_GAMESTARNUM => ",//评分次数
     * G_SHAREPLAYNUM => ",//游戏分享被打开次数
     * G_SHARENUM => ",//游戏分享次数
     * G_PLAYNUM => ",//被玩次数
     * G_BUYNUM => ",//购买次数
     * )
     */
    function update_game_info($game_id,$fields = array()){
        if (!empty($fields)) {
            foreach($fields as $key=>$val){
                $this->DB->set($key, $val);
            }
        }
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $this->DB->where('IDX', $game_id);
        $query = $this->DB->update("pl_game");
        if($query === false){
            log_scribe('trace', 'model', 'update_game_info:'.$this->ip.' where : game_id->'.$game_id .'fields：'.  http_build_query($fields));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /* 修改渠道商定制游戏信息
     * $fields = array(
     * G_GAMESTAR => ",//游戏评级
     * G_GAMESTARNUM => ",//评分次数
     * G_SHAREPLAYNUM => ",//游戏分享被打开次数
     * G_SHARENUM => ",//游戏分享次数
     * G_PLAYNUM => ",//被玩次数
     * G_BUYNUM => ",//购买次数
     * )
     */
    function update_channel_game_info($game_id,$fields = array()){
        if (!empty($fields)) {
            foreach($fields as $key=>$val){
                $this->DB->set($key, $val);
            }
        }
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $this->DB->where('G_GAMEIDX', $game_id);
        $query = $this->DB->update("pl_channelgame");
        if($query === false){
            log_scribe('trace', 'model', 'update_channel_game_info:'.$this->ip.' where : game_id->'.$game_id .'fields：'.  http_build_query($fields));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    //检查制作游戏是否可操作 分享/打开
    function chk_produce_game_cando($game_id){
        $tb_a = 'pl_making m';
        $tb_b = 'pl_game g';
        $select = array(
            'm.IDX AS id',
            'm.M_USERIDX AS uuid',
            'm.M_GAMENAME AS name',
            'm.M_GAMEIDX AS game_id',
            'm.M_SHARENUM AS share_num',
            'm.M_PLAYNUM AS play_num',
            'm.M_SHAREPLAYNUM AS share_open_num',
            'm.M_HATID AS hat_id',
        );
        $join_conditon = "g.IDX = m.M_GAMEIDX";
        $condition = "m.IDX = ".$game_id." AND m.STATUS = 0 AND g.STATUS = 0";
        $data = $this->get_composite_row_array($select,$condition,$join_conditon,$tb_a,$tb_b);
        if ($data === false || empty($data)) {
            log_scribe('trace', 'model', 'chk_produce_game_cando:' . $this->ip . ': condition：' . $condition);
            return false;
        }
        return $data;
    }
    
    /*/**修改制作游戏信息
     * $fields = array(
     * M_SHAREPLAYNUM => ",//游戏分享被打开次数
     * M_SHARENUM => ",//游戏分享次数
     * M_PLAYNUM => ",//被玩次数
     * )
     */
    function update_produce_game_info($game_id,$fields = array(),$uuid = 0){
        if (!empty($fields)) {
            foreach($fields as $key=>$val){
                $this->DB->set($key, $val);
            }
        }
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $this->DB->where(array('IDX'=>$game_id) ); // $game_id:表示making表zhong
        $query = $this->DB->update("pl_making");
        if($query === false){
            log_scribe('trace', 'model', 'update_produce_game_info:'.$this->ip.' where : game_id->'.$game_id .'fields：'.  http_build_query($fields));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /*/**修改制作游戏信息
     * $fields = array(
     * T_SHAREPLAYNUM => ",//游戏分享被玩次数
     * )
     */
    function update_share_game_info($share_id,$fields = array()){
        if (!empty($fields)) {
            foreach($fields as $key=>$val){
                $this->DB->set($key, $val);
            }
        }
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $this->DB->where('IDX', $share_id);
        $query = $this->DB->update("pl_gameshare");
        if($query === false){
            log_scribe('trace', 'model', 'update_share_game_info:'.$this->ip.' where : share_id->'.$share_id .'fields：'.  http_build_query($fields));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    //通过share_id获取用户分享信息
    function get_game_share_info($share_id,$type = -1){
        $select = array(
            'IDX share_id',
            'T_GAMEIDX AS game_id',
            'T_GAMETYPE AS type',
            'T_MAKINGIDX AS produce_game_id',
            'T_SHARETYPE AS share_type',
            'T_SHAREPLAYNUM AS share_play_num',
        );
        $table = 'pl_gameshare';
        $condition = 'IDX = '.$share_id." AND STATUS = 0";
        if($type != -1){
            $condition .= " AND T_GAMETYPE = ".$type;
        }
        $data = $this->get_row_array($condition, $select, $table);
        if($data === false || empty($data)){
            return false;
        }
        return $data;
    }
    
    //通过share_id获取用户制作游戏的图片及名称
    function get_produce_game_info_by_share_id($share_id){
        $tb_a = 'pl_making a';
        $tb_b = 'pl_gameshare b';
        $select = array(
            'a.M_PHOTOURL AS pic',
            'a.M_GAMENAME AS name',
        );
        $join_conditon = "b.T_MAKINGIDX = a.IDX";
        $condition = "a.STATUS = 0 AND b.IDX = ".$share_id;
        $data = $this->get_composite_row_array($select,$condition,$join_conditon,$tb_a,$tb_b);
        if($data === false){
            return false;
        }
        return $data;
    }
    
    //通过game_id获取用户制作游戏的图片及名称
    function get_produce_game_info_by_game_id($game_id){
        $table = 'pl_making';
        $select = array(
            'M_PHOTOURL AS pic',
            'M_GAMENAME AS name',
        );
        $condition = "STATUS = 0 AND IDX = ".$game_id;
        $data = $this->get_row_array($condition, $select, $table);
        if($data === false){
            return false;
        }
        return $data;
    }
    
    //根据目前检测游戏是否可玩
    function chk_game_by_direct($direct){
        $table = 'pl_game';
        $select = array(
            'IDX AS id',
            'G_NAME AS name',
            'G_GAMETYPE AS type',
            'G_BUYNUM AS buy_num',
            'G_SHARENUM AS share_num',
            'G_GAMESTARNUM AS score_num',
            'G_PLAYNUM AS play_num',
            'G_SHAREPLAYNUM AS share_open_num',
            'G_GAMEGOLD AS price',
            'G_GAMEGOLDCURRENT AS price_current',
        );
        $condition = array(
            'G_FILEDIRECTORY'   => $direct,
            'STATUS'            => 0,
            'G_CLOSE'           => 0,
        );
        $data = $this->get_row_array($condition, $select, $table);
        if ($data === false || empty($data)) {
            log_scribe('trace', 'model', 'chk_game_by_direct:' . $this->ip . ': condition：' . http_build_query($condition));
            return false;
        }
        return $data;
    }
    
    /**
     * 制作游戏修改上传图片
     */
    public function update_making_img($params)
    {
        $game_id = $params['id'];
        $file_name = $params['filename'];
        
        $fields = array(
            'M_PHOTOURL' => $file_name,
        );
        
        $res = $this->update_produce_game_info($game_id, $fields ,$params['uuid']);
        
        if (!$res) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 通过geme_id获取游戏信息
     */
    public function get_game_info_by_gameid($game_id)
    {
        $table = 'PL_GAME';
        $condition = "STATUS = 0 AND IDX = " . $game_id;
        $select = array(
            'IDX AS id',
            'G_ICON AS logo',
            'G_NAME AS name',
            'G_INFO AS intro',
            'G_OPERATIONINFO AS guide',
            'G_GAMECATS AS category',
            'G_GAMETYPE AS type',
            'G_GAMEGOLD AS price',
            'G_GAMEGOLDCURRENT AS price_current',
            'G_FILEDIRECTORY AS game_directory',
            'G_SCOREORDERBY AS score_order_type', // 游戏得分排序方式，0:顺序1:倒序
            'G_GAMESCOREMAX AS score_max',
            'G_GAMESCOREMAXTIME AS time',
            'G_GAMEPOINTGA AS ga',
            'G_SHARENUM AS share_num',
            'G_PLAYNUM AS play_num',
            'G_GAMESTAR AS rating',
            'G_IMGS AS screenshots_str',
            'G_GAMEFILESIZE AS size',
            'G_BUYNUM AS buy_num',
            'ROWTIME AS create_time',
            'G_VERSION AS g_version',
            'G_ISDEVELOPER AS is_developer',
        );
        $game_list = $this->get_row_array($condition, $select, $table, true);
        
        if ($game_list === false) {
            log_scribe('trace', 'model', 'get_game_detail:' . $this->ip . ': condition：' . $condition);
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        
        return $game_list[0];
    }
    
    /**
     * 通过geme_id获取游戏信息
     */
    public function get_channel_game_info($game_id)
    {
        $table = 'pl_channelgame';
        $condition = "STATUS = 0 AND G_GAMEIDX = " . $game_id;
        $select = array(
            'G_CHANNELIDX AS channel_id',
            'G_GAMEIDX AS id',
            'G_ICON AS logo',
            'G_NAME AS name',
            'G_INFO AS intro',
            'G_OPERATIONINFO AS guide',
            'G_GAMECATS AS category',
            'G_GAMETYPE AS type',
            'G_GAMEGOLD AS price',
            'G_GAMEGOLDCURRENT AS price_current',
            'G_FILEDIRECTORY AS game_directory',
            'G_SCOREORDERBY AS score_order_type', // 游戏得分排序方式，0:顺序1:倒序
            'G_GAMESCOREMAX AS score_max',
            'G_GAMESCOREMAXTIME AS time',
            'G_GAMEPOINTGA AS ga',
            'G_SHARENUM AS share_num',
            'G_PLAYNUM AS play_num',
            'G_GAMESTAR AS rating',
            'G_IMGS AS screenshots_str',
            'G_GAMEFILESIZE AS size',
            'G_BUYNUM AS buy_num',
            'ROWTIME AS create_time',
            'G_VERSION AS g_version',
            'G_ISDEVELOPER AS is_developer',
        );
        $game_info = $this->get_row_array($condition, $select, $table);
        if ($game_info === false) {
            log_scribe('trace', 'model', 'get_game_detail:' . $this->ip . ': condition：' . $condition);
            $this->error_->set_error(Err_Code::ERR_GAME_INFO_NO_EXIT);
            return false;
        }
        
        return $game_info;
    }
    
    /**
     * 为固定一个游戏，添加游戏排名
     */
    public function game_score_orderby($params)
    {
        $offset = $params['recordindex'];
        $pagesize = $params['pagesize'];
        
        $condition = "STATUS = 0 ORDER BY PL_SCORE LIMIT ".$offset.",".$pagesize;
        
        $table = "pl_game_one";
        $select = array(
            'PL_USERID AS user_id',
            'PL_NICKNAME AS nickname',
            'PL_MOBILE AS mobile',
            'PL_SCORE AS score',
            'UNIX_TIMESTAMP(PL_UPDATE) AS update_time',
        );
        $data['list'] = $this->get_row_array($condition, $select, $table, true);
        if (!$data['list']) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        $this->DB->where($condition);
        $count = $this->DB->count_all_results($table);
        $data['pagecount'] = (int)ceil($count / $pagesize);
        if (!$data['pagecount']) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }
    /*
     * 判断用户的游戏记录是否存在pl_game_one表中
     */
    public function if_exists_game_one($uuid)
    {
        $condition = "STATUS = 0 AND PL_USERID=".$uuid;
        $table   = 'pl_game_one';
        $select  = array(
            'IDX AS id',
        );
        $res = $this->get_row_array($condition, $select, $table, false);
        if (!$res) {
            return false;
        }
        
        return $res['id'];
    }
    
    /**
     * 插入pl_game_one表
     */
    public function insert_game_score($fields)
    {
        $table = 'pl_game_one';
        $data = array(
            'PL_USERID' => $fields['uuid'],
            'PL_NICKNAME' => $fields['nickname'],
            'PL_MOBILE' => $fields['mobile'],
            'PL_SCORE' => $fields['score'],
            'PL_UPDATE' => $this->zeit,
            'STATUS' => 0,
            'ROWTIME' => $this->zeit,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $res = $this->DB->insert($table, $data);
        if ($res === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 更新pl_game_one表
     */
    public function update_game_score($uuid, $fields)
    {
        $table = 'pl_game_one';
        $condition = "PL_USERID = ".$uuid." AND STATUS = 0";
        $data = array(
            'PL_SCORE' => $fields['score'],
            'PL_UPDATE' => $this->zeit,
            'ROWTIMEUPDATE' => $this->zeit,
        );
        $res = $this->DB->update($table, $data, $condition);
        if ($res === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 过关游戏列表
     */
    public function clearance_list($params) {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        $uuid       = $params['uuid'];
        if ($params['custom_game']) {
            $game_table = 'pl_channelgame';
            $tab        = 2 ;
        } else {
            $game_table = 'PL_GAME';
            $tab        = 1 ;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'G_UPTIMEORDERBY';// 按照游戏上架时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'G_BUYNUM';
        } else {
            $orderby = 'G_GAMESTAR';
        }
        
        $condition = "STATUS = 0 AND G_GAMETYPE = 2 AND G_CLOSE = 0 ORDER BY " . $orderby . " DESC limit " . $offset . "," . $per_page;
        $count_condition = "STATUS = 0 AND G_GAMETYPE = 2 AND G_CLOSE = 0"; 
        // 获取免费游戏列表
        $data = $this->get_game_public($condition, $count_condition,$uuid, $game_table, $select = 1, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        return $data;
    }
    
    // -----------------------------测试-----------------------------------
     /**
     * 插入更新游戏(数据)
     */
    public function insert_game($params)
    {
        $game_name = $params['G_NAME'];
        if (!$params['is_existst']) { // 插入
            $data1 = array(
                'G_NAME'          => $params['G_NAME'],
                'G_FILEDIRECTORY' => $params['G_FILEDIRECTORY'],
                'G_GAMETYPE'      => $params['G_GAMETYPE'],
                'G_GAMEGOLD'      => $params['G_GAMEGOLD'],
                'G_GAMEGOLDCURRENT' => $params['G_GAMEGOLDCURRENT'],
                'G_GAMEFILESIZE'    => $params['G_GAMEFILESIZE'],
                'G_HOT'             => $params['G_HOT'],
                'G_KEYS'            => $params['G_KEYS'],
                'G_HOTORDERBY'      => $params['G_HOTORDERBY'],
                'G_TEMPLATE'        => $params['G_TEMPLATE'],
                'G_MAKINGGAMEPOINT' => $params['G_MAKINGGAMEPOINT'],
                'G_NEW'             => $params['G_NEW'],
                'G_GAMECATS'        => $params['G_GAMECATS'],
                'G_PLATFORMS'       => $params['G_PLATFORMS'],
                'G_SCOREORDERBY'    => $params['G_SCOREORDERBY'],
                'G_GAMESCOREUNIT'   => $params['G_GAMESCOREUNIT'],
                'G_GAMESCOREMAX'    => $params['G_GAMESCOREMAX'],
                'G_GAMESCOREMAXTIME'=> $params['G_GAMESCOREMAXTIME'],
                'G_GAMEPOINTGA'     => $params['G_GAMEPOINTGA'],
                'G_INFO'            => $params['G_INFO'],
                'G_OPERATIONINFO'   => $params['G_OPERATIONINFO'],
                'G_CLOSE'           => $params['G_CLOSE'],
                'G_IMGS'            => $params['G_IMGS'],
                'G_ICON'            => $params['G_ICON'],
                'G_BUYNUM'          => $params['G_BUYNUM'],
                'G_PLAYNUM'         => $params['G_PLAYNUM'],
                'G_SHARENUM'        => $params['G_SHARENUM'],
                'G_SHAREPLAYNUM'    => $params['G_SHAREPLAYNUM'],
                'G_GAMESTAR'        => $params['G_GAMESTAR'],
                'G_GAMESTARNUM'     => $params['G_GAMESTARNUM'],
                'G_UPTIMEORDERBY'   => $params['G_UPTIMEORDERBY'],
                'G_VERSION'         => '',
                'G_ISDEVELOPER'     => 0,
                'G_AUDIT'           => 0,
                'STATUS'            => 0,
                'ROWTIME'           => date('Y-m-d h:i:s',time()),
                'ROWTIMEUPDATE'     => date('Y-m-d h:i:s',time()),  
            );
            $insert = $this->DB->insert('PL_GAME', $data1);
            if ($params['G_GAMETYPE'] == 3) {
                $game_id = $this->DB->insert_id();
                // 插入到商品表中
                $data_product = array(
                    'P_NAME' => $params['G_NAME'],
                    'P_TYPE' => 0,
                    'P_PRICE' => $params['G_GAMEGOLD'],
                    'P_PRICECURRENT' => $params['G_GAMEGOLDCURRENT'],
                    'P_IDX' => $game_id,
                    'STATUS'    => 0,
                    'ROWTIME'       => date('Y-m-d h:i:s',time()),
                    'ROWTIMEUPDATE' => date('Y-m-d h:i:s',time()),  
                );
                $insert2 = $this->DB->insert('pl_product', $data_product);
                if (!$insert2) {
                    return false;
                }
            }
            if ($insert) {
                return TRUE;
            }
            return false;
            
        } else { // 更新
            if ($params['is_top']) { // 破纪录
                $data1 = array(
                    'G_NAME'            => $params['G_NAME'],
                    'G_FILEDIRECTORY'   => $params['G_FILEDIRECTORY'],
                    'G_GAMETYPE'        => $params['G_GAMETYPE'],
                    'G_GAMEGOLD'        => $params['G_GAMEGOLD'],
                    'G_GAMEGOLDCURRENT' => $params['G_GAMEGOLDCURRENT'],
                    'G_GAMEFILESIZE'    => $params['G_GAMEFILESIZE'],
                    'G_HOT'             => $params['G_HOT'],
                    'G_HOTORDERBY'      => $params['G_HOTORDERBY'],
                    'G_TEMPLATE'        => $params['G_TEMPLATE'],
                    'G_MAKINGGAMEPOINT' => $params['G_MAKINGGAMEPOINT'],
                    'G_NEW'             => $params['G_NEW'],
                    'G_KEYS'           => $params['G_KEYS'],
                    'G_GAMECATS'        => $params['G_GAMECATS'],
                    'G_PLATFORMS'       => $params['G_PLATFORMS'],
                    'G_SCOREORDERBY'    => $params['G_SCOREORDERBY'],
                    'G_GAMESCOREUNIT'   => $params['G_GAMESCOREUNIT'],
                    'G_GAMESCOREMAX'    => $params['G_GAMESCOREMAX'],
                    'G_GAMESCOREMAXTIME' => $params['G_GAMESCOREMAXTIME'],
                    'G_GAMEPOINTGA'     => $params['G_GAMEPOINTGA'],
                    'G_INFO'            => $params['G_INFO'],
                    'G_OPERATIONINFO'   => $params['G_OPERATIONINFO'],
                    'G_CLOSE'           => $params['G_CLOSE'],
                    'G_IMGS'            => $params['G_IMGS'],
                    'G_ICON'            => $params['G_ICON'],
                    'G_BUYNUM'          => $params['G_BUYNUM'],
                    'G_PLAYNUM'         => $params['G_PLAYNUM'],
                    'G_SHARENUM'        => $params['G_SHARENUM'],
                    'G_SHAREPLAYNUM'    => $params['G_SHAREPLAYNUM'],
                    'G_GAMESTAR'        => $params['G_GAMESTAR'],
                    'G_GAMESTARNUM'     => $params['G_GAMESTARNUM'],
                    'G_UPTIMEORDERBY'   => $params['G_UPTIMEORDERBY'],
                    'ROWTIME'           => date('Y-m-d h:i:s',time()),
                    'ROWTIMEUPDATE'     => date('Y-m-d h:i:s',time()),  
                );
            } else {// 没破纪录
                $data1 = array(
                    'G_NAME'            => $params['G_NAME'],
                    'G_FILEDIRECTORY'   => $params['G_FILEDIRECTORY'],
                    'G_GAMETYPE'        => $params['G_GAMETYPE'],
                    'G_GAMEGOLD'        => $params['G_GAMEGOLD'],
                    'G_GAMEGOLDCURRENT' => $params['G_GAMEGOLDCURRENT'],
                    'G_GAMEFILESIZE'    => $params['G_GAMEFILESIZE'],
                    'G_HOT'             => $params['G_HOT'],
                    'G_HOTORDERBY'      => $params['G_HOTORDERBY'],
                    'G_TEMPLATE'        => $params['G_TEMPLATE'],
                    'G_MAKINGGAMEPOINT' => $params['G_MAKINGGAMEPOINT'],
                    'G_NEW'             => $params['G_NEW'],
                     'G_KEYS'           => $params['G_KEYS'],
                    'G_GAMECATS'        => $params['G_GAMECATS'],
                    'G_PLATFORMS'       => $params['G_PLATFORMS'],
                    'G_SCOREORDERBY'    => $params['G_SCOREORDERBY'],
                    'G_GAMESCOREUNIT'   => $params['G_GAMESCOREUNIT'],
                    // 'G_GAMESCOREMAX'    => $params['G_GAMESCOREMAX'],
                    // 'G_GAMESCOREMAXTIME' => $params['G_GAMESCOREMAXTIME'],
                    'G_GAMEPOINTGA'     => $params['G_GAMEPOINTGA'],
                    'G_INFO'            => $params['G_INFO'],
                    'G_OPERATIONINFO'   => $params['G_OPERATIONINFO'],
                    'G_CLOSE'           => $params['G_CLOSE'],
                    'G_IMGS'            => $params['G_IMGS'],
                    'G_ICON'            => $params['G_ICON'],
                    'G_BUYNUM'          => $params['G_BUYNUM'],
                    'G_PLAYNUM'         => $params['G_PLAYNUM'],
                    'G_SHARENUM'        => $params['G_SHARENUM'],
                    'G_SHAREPLAYNUM'    => $params['G_SHAREPLAYNUM'],
                    'G_GAMESTAR'        => $params['G_GAMESTAR'],
                    'G_GAMESTARNUM'     => $params['G_GAMESTARNUM'],
                    'G_UPTIMEORDERBY'   => $params['G_UPTIMEORDERBY'],
                    'ROWTIME'           => date('Y-m-d h:i:s',time()),
                    'ROWTIMEUPDATE'     => date('Y-m-d h:i:s',time()),  
                );
            }
            $this->DB->where('G_NAME', $game_name);
            $update = $this->DB->update('PL_GAME', $data1); 
             if ($update) {
                return TRUE;
            }
            return false;
        }
    }
    
    /**
     * game_info
     */
    public function game_info()
    {
        $condition = "STATUS = 0 AND G_CLOSE = 0 AND G_GAMETYPE != 2 AND G_GAMETYPE != 3 AND G_TEMPLATE != 2";
        $select = array('IDX AS game_id', 'G_NAME AS game_name', 'G_SCOREORDERBY AS order_type','G_GAMESCOREMAX AS max_score','G_GAMESCOREMAXTIME AS spend_time');
        $table = "pl_game";
        $game_list = $this->get_row_array($condition, $select, $table,true);
        return $game_list;
    }
    
    /**
     * 判断游戏名称，是否存在游戏表中
     */
    public function if_exists_game_name($game_name)
    {
        $sql = "SELECT IDX AS game_id,G_SCOREORDERBY AS order_type,G_GAMESCOREMAX AS game_max FROM PL_GAME WHERE G_NAME = '".$game_name."'";
        $query = $this->DB->query($sql);
        
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        
        if ($query->num_rows() <= 0) { // 不存在
            return false;
        } else { // 存在
            $res = $query->result_array();
            return $res[0];
        }     
    }
    
    /**
     * 
     * @param int $game_join_id
     * 获取开发商货币与人民币汇率（开发商货币/人民币）
     */
    public function get_open_game_info($game_join_id) {
        $table = 'pl_opengame';
        $condition = 'STATUS = 0 AND G_GAMEIDX = '.$game_join_id;
        $select = array(
            'IDX AS idx',
            'G_USERIDX AS uuid',
            'G_NICKNAME AS nickname',
            'G_GAMEIDX AS game_id',
            'G_GAMENAME AS game_name',
            'G_FILEDIRECTORY AS file_directory',
            'G_ICON AS icon',
            'G_ZIP AS zip',
            'G_RATE AS rate',
            'G_TYPE as type',
            'G_SCREEN AS screen',
            'G_ORIGINAL AS original',
            'G_DEBUGGAME AS game_debug_time',
            'G_URL AS game_url',
            'G_DEBUGURL AS debug_url',
            'G_FEETYPE AS fee_type',
            'G_AUDIT AS audit', // 审核状态
        );
        $query = $this->get_row_array($condition, $select, $table);
        
        if (!$query) {
            $this->error_->set_error(Err_Code::ERR_GAME_NOT_EXISTS);
            return false;
        }
        return $query;
    }
    
    
    /**
     * 购买道具，插入表购买道具表
     */
    public function game_propbuy($params)
    {
        if (!empty($params) && is_array($params)) {
            foreach ($params as $k=>$v) {
                if ($v) {
                    $this->DB->set($k, $v);
                }
            }
        }
        
        $this->DB->set('ROWTIME', $this->zeit);
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
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
     * 获取购买道具，订单信息
     */
    public function get_propbuy_info($prepare_id)
    {
        $condition = "IDX = ".$prepare_id." AND STATUS = 0";
        $table   = 'pl_propbuy';
        $select  = array(
            'IDX AS id',
            'P_USERIDX AS uuid',
            'P_NICKNAME AS nickname',
            'P_PROPIDX AS prop_id',
            'P_TOTALFEE AS total_fee',
            'P_TOTALGOLD AS total_gold',
            'P_GAMEJOINID AS game_join_id',
            'P_SUBJECT AS subject',
            'P_DECRIPTION AS decription',
            'P_NOTIFYURL AS notify_url',
            'P_TIMESTAMP AS timestamp',
            'P_NONCE AS nonce',
            'P_BUYSTATUS AS buy_status',
            'STATUS AS status',
        );
        $res = $this->get_row_array($condition, $select, $table, false);
        if (!$res) {
            $this->CI->error_->set_error(Err_Code::ERR_PROPBUY_SELECT_FAIL);
            return false;
        }
        
        return $res;
    }
    
    /**
     * 修改购买道具订单状态
     */
    public function update_propbuy_info($prepare_id, $buy_status)
    {
        $this->DB->set('ROWTIMEUPDATE', $this->zeit);
        $this->DB->set('P_BUYSTATUS', $buy_status);
        $this->DB->where('IDX', $prepare_id);
        $query = $this->DB->update("pl_propbuy");
        if($query === false){
            log_scribe('trace', 'model', 'update_propbuy_info:'.$this->ip.' where : IDX->'.$prepare_id .'fields：'.  http_build_query($fields));
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        return true;
    }
    
    /**
     * 精品网游列表
     */
    public function online_game_list($params) {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        
        if ($params['custom_game']) {
            $game_table = 'pl_channelgame as B,pl_opengame as A';
            $tab        = 2;
        } else {
            $game_table = 'PL_GAME AS B, pl_opengame as A';
            $tab        = 1;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'G_UPTIMEORDERBY';// 按照游戏上架时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'G_BUYNUM';
        } else {
            $orderby = 'G_GAMESTAR';
        }
        // 判断是否存在custom_game 拼接condition条件
        $select = array(
            'A.G_URL AS game_url',
            'A.G_DEBUGURL AS debug_url',
        );
        $condition_pub    = "B.STATUS = 0 AND B.G_GAMETYPE = 4 AND B.G_TEMPLATE != 2 AND B.G_CLOSE = 0"; 
        if ($params['custom_game']) {
            $condition          = $condition_pub."  B.G_GAMEIDX = A.G_GAMEIDX  AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub." AND B.G_GAMEIDX = A.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
            $select[] = 'A.G_CHANNELIDX AS channel_id';
            $select[] = 'A.G_GAMEIDX AS id';
        } else {
            $condition          = $condition_pub ." AND  B.IDX = A.G_GAMEIDX ORDER BY " . $orderby." DESC";
            $count_condition    = $condition_pub." AND B.IDX = A.G_GAMEIDX";
        }
        
        // 判断是否存在version， 拼接condition条件  
        if (!$params['version']) {
            $condition = $condition." LIMIT ".$offset.",".$per_page;
        }
        // 获取免费游戏列表
        $data = $this->get_game_public($condition, $count_condition, $params['uuid'], $game_table, $select, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态          
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                // 所有未登录的，购买状态为：为购买0
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        return $data;
    }
    
    /**
     * 小编推荐(特殊推荐)
     */
    public function special_recomment_list($params) {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $uuid     = $params['uuid'];
        $table    = 'pl_channelrecomspecial AS A, PL_GAME AS B';
        
        if ($params['version']) {
               $condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2  AND A.C_GAMEIDX = B.IDX AND A.C_CHANNELIDX = ".$params['channel_id']."  ORDER BY A.ROWTIME DESC limit " . $offset . "," . $per_page; 
               $count_condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND A.C_GAMEIDX = B.IDX AND A.C_CHANNELIDX = ".$params['channel_id']."";
        } else {
               $condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND  A.C_GAMEIDX = B.IDX AND A.C_CHANNELIDX = ".$params['channel_id']."  ORDER BY A.ROWTIME DESC limit " . $offset . "," . $per_page; 
               $count_condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND A.C_GAMEIDX = B.IDX AND A.C_CHANNELIDX = ".$params['channel_id']."";
        }
        $data = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 2);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态          
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        return $data;
    }
    
    /**
     * 小编推荐(常规)
     */
    public function recomment_list($params) {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $uuid     = $params['uuid'];
        $table    = 'pl_channelrecommended AS A, PL_GAME AS B';
        
        if ($params['version']) {
               $condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2  AND A.C_GAMEIDX = B.IDX   ORDER BY A.ROWTIMEUPDATE DESC"; 
               $count_condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND A.C_GAMEIDX = B.IDX ";
        } else {
               $condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND  A.C_GAMEIDX = B.IDX  ORDER BY A.ROWTIMEUPDATE DESC limit " . $offset . "," . $per_page; 
               $count_condition = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND A.C_GAMEIDX = B.IDX ";
        }
        $data = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 2);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态          
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data['list'] as $k => $v) {
                if ($v['g_version']) {
                    if ($params['version']== $v['g_version']) {
                        $data1[] = $v;
                    }
                }
            }
            if (empty($data1)) {
                $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                return false;
            }
            $data['pagecount'] = ceil(count($data1) / $per_page);
            $count_all = count(array_slice($data1, $offset));

            if ($count_all >= $per_page) {
                $data['list'] = array_slice($data1, $offset, $per_page);
            } else {
                $data['list'] = array_slice($data1, $offset, $count_all);
            }     
        }
        return $data;
    }
    
    /**
     * 专题列表
     * @param type $params
     * @return boolean
     */
    public function theme_list($params)
    {
        $per_page   = $params['pagesize']; // 每页显示条数
        $offset     = $params['recordindex']; // 请求开始位置
        $uuid       = $params['uuid'];
        if ($params['custom_game']) {
            $table  = 'pl_theme AS A, pl_channelgame AS B';
            $tab    = 2;
        } else {
            $table  = 'pl_theme AS A, PL_GAME AS B';
            $tab    = 1;
        }
        if ($params['orderby'] == 1) {
            $orderby = 'A.ROWTIMEUPDATE DESC';  // 按照最后修改的收藏时间排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'B.G_BUYNUM DESC';
        } else {
            $orderby = 'B.G_GAMESTAR DESC';
        }
        // 判断是否存在custom_game 拼接condition条件
        $condition_pub    = "A.THEMEMID = ".$params['id']." AND A.STATUS = 0 AND B.STATUS = 0  AND B.G_CLOSE = 0";
        if ($params['custom_game']) {
            $condition          = $condition_pub."  AND A.GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby;
            $count_condition    = $condition_pub."  AND A.GAMEIDX = B.G_GAMEIDX AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." AND A.GAMEIDX = B.IDX ORDER BY " . $orderby;
            $count_condition    = $condition_pub ." AND A.GAMEIDX = B.IDX";
        }
        // 判断是否存在version， 拼接condition条件  
        $condition = $condition." LIMIT ".$offset.",".$per_page;
        $data      = $this->get_game_public($condition, $count_condition, $uuid, $table, $select = 2, $per_page, $tab);
        if (!$data) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $data;
    }
    
    /*
     * 获取渠道商信息
     */
    public function get_channel_info($channel_id)
    {
        $table = 'pl_channel';
        $select = array(
            'IDX AS id',
            'C_USERIDX AS user_id',
            'C_NICKNAME AS nickname',
            'C_CHANNELIDX AS channel_id',
            'C_CHANNELKEY AS channel_key',
            // 'C_APPID AS app_id',
            // 'C_APPNAME AS app_name',
            'C_TYPE AS type',
            'C_ISUSER AS is_user',
            'C_FILEDIRECOTY AS file_direcoty',
            'C_ICON AS icon',
            'C_PLATFORM AS plat_form',
            'C_IOSDOWNLOAD AS ios_download',
            'C_ANDROIDDOWNLOAD AS android_download',
            'C_INFO AS info',
            'C_INTRO AS intro',
            'C_WINXIINTRO AS weixin_intro',
            'C_WINXINNO AS weixin_info',
            'C_CUSTOMGAME AS custom_game',
            'C_AUDIT AS audit',
            'STATUS AS status',
        );
        $condition = 'STATUS = 0 AND C_CHANNELIDX = '.$channel_id;
        $data = $this->get_row_array($condition, $select, $table);
        if (!$data) {
            // $this->error_->set_error(Err_Code::ERR_CHANNEL_INFO_NOT_FOUND);
            return false;
        }
        return $data;
    }
    public function insert_name($data)
    {
        return $this->DB->insert('game', $data);
    }
    
    
    /**
     * 白鹭网游同步
     */
    public function bailu_game_sync()
    {
        // 获取白鹭网游列表
        $url            = 'http://api.open.egret.com/Channel.gameList';
        $channel_id     = $this->CI->passport->get('channel');
        $para_qry       = 'app_id='.$channel_id;
        $return_content = $this->CI->utility->get($url,$para_qry);
        $bailu_list     = json_decode($return_content ,TRUE);
        if(!$bailu_list['game_list']) {
            log_message('info', "del_bailu_list:网游同步操作，白鹭平台暂无网游，将本平台所有网游同步移除");
            $this->del_bailu_list();
            return true;
        }
        $bl_list    = $bailu_list['game_list'];
        foreach ($bl_list as $v) {
            $bl_list2[$v['gameId']] = $v;
            $ids_1[$v['gameId']]    = $v['gameId'];
        }
        
        // 获取本平台网游
        $sql    = "SELECT B_GAMEID id,B_BAILUIDX AS game_id  FROM pl_bailu WHERE STATUS = 0";
        $query  = $this->DB->query($sql);
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $list = $query->result_array();
        if (!$list) {
            foreach ($ids_1 as $key=>$val) {
                $ist_data = array(
                    'G_NAME'            => $bl_list2[$val]['name'],
                    'G_FILEDIRECTORY'   => $bl_list2[$val]['url'],
                    'G_INFO'            => $bl_list2[$val]['desc'],
                    'G_ICON'            => $bl_list2[$val]['icon'],
                    'G_OPERATIONINFO'   => $bl_list2[$val]['shortDesc'],
                );
                $ist_id = $this->insert_data_pl_game($ist_data,'pl_game');
                $ist_data_2 = array(
                    'B_GAMEID'      => $ist_id,
                    'B_BAILUIDX'    => $val,
                    'STATUS'        => 0,
                    'ROWTIME'       => $this->zeit,
                    'ROWTIMEUPDATE' => $this->zeit,
                );
                $this->insert_data($ist_data_2,'pl_bailu');
            }
            log_message('info', "ist_bailu_list:网游同步操作，平台无网游，将白鹭所有网游同步至本平台");
            return true;
        }
        foreach ($list as $k=>$v) {
            $ids_2[$v['id']]  = $v['game_id']; 
        }
        // 更新数据
        $ids    = array_intersect($ids_2,$ids_1);
        if ($ids) {
            foreach ($ids as $key=>$val) {
                $upt_data[] = array(
                    'IDX'               =>$key,
                    'G_NAME'            => $bl_list2[$val]['name'],
                    'G_FILEDIRECTORY'   => $bl_list2[$val]['url'],
                    'G_ICON'            => $bl_list2[$val]['icon'],
                    'G_INFO'            => $bl_list2[$val]['desc'],
                    'G_OPERATIONINFO'   => $bl_list2[$val]['shortDesc'],
                );      
            }
            $this->update_pl_game_list($upt_data);
        }
        
        // 删除部分
        $ids    = array_diff($ids_2, $ids_1);
        if ($ids) {
            foreach ($ids as $key=>$val) {
                $upt_data[] = array(
                    'IDX'       => $key,
                    'G_CLOSE'   => 1,
                    'STATUS'    => 1,
                );
                $upt_data_2[]   = array(
                    'B_BAILUIDX'    => $val,
                    'STATUS'        => 1,
                );
            }
            $this->update_pl_game_list($upt_data,'pl_game');
            $this->update_pl_bailu_list($upt_data_2,'pl_bailu');
            log_message('info', "del_bailu_list:网游同步操作，白鹭平台部分网游被移除，则本平台网游执行同步移除");
        }
        
        // 插入部分
        $ids    = array_diff($ids_1, $ids_2);
        if ($ids) {
            foreach ($ids as $key=>$val) {
                $ist_data = array(
                    'G_NAME'            => $bl_list2[$val]['name'],
                    'G_FILEDIRECTORY'   => $bl_list2[$val]['url'],
                    'G_ICON'            => $bl_list2[$val]['icon'],
                    'G_INFO'            => $bl_list2[$val]['desc'],
                    'G_OPERATIONINFO'   => $bl_list2[$val]['shortDesc'],
                );
                $ist_id = $this->insert_data_pl_game($ist_data,'pl_game');
                $ist_data_2 = array(
                    'B_GAMEID'      => $ist_id,
                    'B_BAILUIDX'    => $val,
                    'STATUS'        => 0,
                    'ROWTIME'       => $this->zeit,
                    'ROWTIMEUPDATE' => $this->zeit,
                );
                $this->insert_data($ist_data_2,'pl_bailu');
            }
        }
        return true;
    }
    
    /**
     * 同步pl_bailu表
     */
    public function bailu_table_sync()
    {
        $sql    = "SELECT IDX AS id FROM pl_game WHERE  G_GAMETYPE = 4 AND STATUS = 0";
        $query  = $this->DB->query($sql);
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $list = $query->result_array();
        var_dump($list);exit;
    }
    
    /**
     * 白鹭平台网游数据更新， 同步更新到本平台
     * @param type $data
     * @return boolean
     */
    public function insert_data_pl_game($v,$table)
    {
        $v['G_GAMETYPE']        = 4;
        $v['G_GAMEGOLD']        = 0;
        $v['G_GAMEGOLDCURRENT']   = 0;
        $v['G_GAMEFILESIZE']    = 0;
        $v['G_HOT']   = 0;
        $v['G_HOTORDERBY']    = 0;
        $v['G_TEMPLATE']= 0;
        $v['G_MAKINGGAMEPOINT']            = 0;
        $v['G_NEW']           = 0;
        $v['G_GAMECATS']            = "";
        $v['G_PLATFORMS']            = "";
        $v['G_SCOREORDERBY']          = 0;
        $v['G_GAMESCOREUNIT']         = "";
        $v['G_GAMESCOREMAX']        = 0;
        $v['G_GAMESCOREMAXTIME']    = 0;
        $v['G_GAMEPOINTGA']        = 1;
        $v['G_KEYS']        = "";
        $v['G_CLOSE']    = 1;
        $v['G_IMGS']        = "1.png,2.png,3.png";
        $v['G_BUYNUM']        = 0;
        $v['G_PLAYNUM']    = 0;
        $v['G_SHARENUM']        = 0;
        $v['G_SHAREPLAYNUM']     = 0;
        $v['G_GAMESTAR']        = 5;
        $v['G_GAMESTARNUM']    = 4;
        $v['G_VERSION']        = "";
        $v['G_ISDEVELOPER']     = 0;
        $v['G_AUDIT']   = 0;
        $v['G_UPTIMEORDERBY']   = $this->zeit;
        $v['STATUS']            = 0;
        $v['ROWTIME']   = $this->zeit;
        $v['ROWTIMEUPDATE'] = $this->zeit;
        $this->insert_data($v,'pl_game');
        return $this->DB->insert_id();
    }
    
    /**
     * 批量更新pl_game表
     */
    public function update_pl_game_list($data)
    {
        $res = $this->update_batch($data,'IDX','pl_game');
        log_message('info', "update_bailu_list:网游同步操作，白鹭网游数据同步更新，以获取最新数据");
        return true;
    }
    
    /**
     * 批量更新pl_bailu表
     */
    public function update_pl_bailu_list($data)
    {
        $res = $this->update_batch($data,'B_BAILUIDX','pl_bailu');
        log_message('info', "update_bailu_list:网游同步操作，白鹭网游数据同步更新，以获取最新数据");
        return true;
    }
    
    /**
     * 白鹭平台网游 同步跟新到本平台
     */
    public function del_bailu_list($data = array())
    {
        // 全部移除
        if (empty($data)) {
            $fields = array('STATUS'=>1,'G_CLOSE'=>1);
            $where  = array('G_GAMETYPE'=>4,'STATUS'=>0);
            $res    = $this->CI->game_model->update_data($fields,$where,'pl_game');
            return true;
        }
        // 部分移除
        $this->CI->game_model->update_batch($data,'IDX','pl_game');
        return true;
    }

    /**
     * 插入数据
     * @param type $data
     * @param type $table
     * @return type
     */
    public function insert_data($data,$table)
    {
        $this->DB->insert($table,$data);
        return $this->DB->insert_id();
    }
    
    /**
     * 获取所有游戏列表
     */
    public function game_all_list()
    {
        $sql    = "SELECT IDX id,G_NAME name,G_GAMETYPE type,G_CLOSE close,G_VERSION version FROM pl_game WHERE  G_CLOSE = 0 AND STATUS = 0 ";
        $list   = $this->DB->query($sql);
        
        if ($list === false) {
            log_scribe('trace', 'model',   'pl_game(insert)' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_GAME_GET_FAIL);
            return false;
        }
        if ($list->num_rows() > 0) {
            $game_list = $list->result_array(); 
            
        } else {
            log_scribe('trace', 'model',   'pl_game(select)' . $this->ip . ' where : IDX->' . $params['id']);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $game_list;
    }

    /**
     * $ids 游戏ID集合
     * 获取送审游戏列表
     */
    public function game_check_list($ids)
    {
        $sql    = "SELECT IDX id,G_GAMETYPE type,G_CLOSE close,G_VERSION version FROM pl_game WHERE IDX IN (".$ids.") AND G_CLOSE = 0 AND STATUS = 0 ";
        $list   = $this->DB->query($sql);
        
        if ($list === false) {
            log_scribe('trace', 'model',   'pl_game(insert)' . $this->ip);
            $this->error_->set_error(Err_Code::ERR_GAME_GET_FAIL);
            return false;
        }
        if ($list->num_rows() > 0) {
            $game_list = $list->result_array(); 
            
        } else {
            log_scribe('trace', 'model',   'pl_game(select)' . $this->ip . ' where : IDX->' . $params['id']);
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        return $game_list;
    }
    
    /**
     * 将本地游戏标记为送审游戏（最新APP版本）
     */
    public function update_check_game($data)
    {
        return $this->DB->update_batch('pl_game', $data, 'IDX'); 
    }
    
    /**
     * 获取ios送审版本信息
     */
    public function get_ios_version()
    {
        $sql    = "SELECT IDX AS id,I_STATUS status,I_VERSION version  FROM pl_ioscheck_version WHERE  I_STATUS = 1 AND STATUS = 0";
        $query  = $this->DB->query($sql);
        if ($query === false) {
            $this->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $ret = $query->result_array();
        return $ret[0];
    }
    
}
