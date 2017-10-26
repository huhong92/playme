<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
    
class Search_Model extends MY_Model {
    
    public function __construct() {
        parent::__construct(true);
        // 默认返回成功结果
        $this->CI->error_->set_error(Err_Code::ERR_OK);
    }

    //搜索列表
    function get_search_list($params) {
        $like_string = "%" . $params['keywords'] . "%";
        
        $select = array(
            'IDX AS id',
            'G_ICON AS logo',
            'G_NAME AS name',
            'G_INFO AS intro',
            'G_OPERATIONINFO AS guide',
            'G_GAMECATS AS category',
            'G_FILEDIRECTORY AS game_directory',
            'G_GAMETYPE AS type',
            'G_GAMEGOLD AS price',
            'G_GAMEGOLDCURRENT AS price_current',
            'G_SHARENUM AS share_num',
            'G_PLAYNUM AS play_num',
            'G_GAMESTAR AS rating',
            'G_IMGS AS screenshots_str',
            'G_GAMEFILESIZE AS size',
            'G_BUYNUM AS buy_num',
            'G_KEYS AS tag',
            'UNIX_TIMESTAMP(ROWTIME) AS create_time',
            'G_UPTIMEORDERBY as order_by',
            'G_VERSION AS g_version',
        );
        if ($params['custom_game']) {
            $table = "pl_channelgame";
            $select[] = 'B.G_CHANNELIDX AS channel_id';
            $select[] = 'B.G_GAMEIDX AS id';
        } else {
            $table = "pl_game";
        }
        $condition = "CONCAT(G_NAME,G_KEYS) LIKE '" . $like_string . "' AND G_CLOSE = 0 AND  STATUS = 0 AND G_TEMPLATE != 2";
        $search_list = $this->get_row_array($condition, $select, $table, true);
        if ($search_list === false) {
            log_scribe('trace', 'model', 'get_comment_list :' . $this->ip . '  condition :' . $condition);
            $this->CI->error_->set_error(Err_Code::ERR_SEARCH_LIST_NO_DATA);
            return false;
        }
        $target_list = array();
        if (!empty($search_list)) {
            // 游戏版本好过滤
            $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态          
            if ($v_info['version'] && $params['version'] >= $v_info['version']) {
                foreach ($search_list as $key => &$val) {
                    if ($val['g_version']) {
                        if ($params['version']== $v['g_version']) {
                            $val['category'] = explode(',', trim($val['category'], ','));
                            $screenshots_arr = explode(',', trim($val['screenshots_str'],','));
                            foreach ($screenshots_arr as $v1) {
                                $val['screenshots'][] = $this->passport->get('game_url').$val['game_directory'].$v1;
                            }
                            
                            $val['logo']            = $this->passport->get('game_url').$val['game_directory'].$val['logo'];
                            $val['game_directory']  = $this->passport->get('game_url').$val['game_directory'].'play/index.html';
                            $data1[] = $val;
                        }
                    }
                }
                
                if (empty($data1)) {
                    $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
                    return false;
                }
            } else {
                foreach ($search_list as $key => &$val) {
                    $val['category'] = explode(',', trim($val['category'], ','));
                    $screenshots_arr = explode(',', trim($val['screenshots_str'],','));
                    foreach ($screenshots_arr as $v1) {
                        $val['screenshots'][] = $this->passport->get('game_url').$val['game_directory'].$v1;
                    }
                    $val['logo']            = $this->passport->get('game_url').$val['game_directory'].$val['logo'];
                    $val['game_directory']  = $this->passport->get('game_url').$val['game_directory'].'play/index.html';
                    $val['buy_status']      = 0;
                    $data1[] = $val;
                }
            }
            //排序
            $target_list_all = $this->_org_game_search_list($data1,$params['keywords']);
            $target_list = array_slice($target_list_all, $params['recordindex'], $params['pagesize']);
        }
        if (empty($target_list)) {
            log_scribe('trace', 'model', 'PL_GAME(select):' . $this->ip . 'where: G_GAMETYPE=>免费游戏');
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 游戏是否已经购买
        if ($params['uuid']) {
            $buy_sql  = "SELECT B_GAMEIDX as game_id FROM PL_GAMEBUY WHERE B_USERIDX = ".$params['uuid'];
            $buy_query = $this->DB->query($buy_sql);
            if ($buy_query === false) {
                $this->error_->set_error(Err_Code::ERR_DB);
                return false;
            }
            if ($buy_query->num_rows() > 0) {
                $buy_ids = $buy_query->result_array();
                    foreach ($target_list as $k => $v) {
                        // 添加游戏的购买状态
                        foreach ($buy_ids as $k1 => $v1) {
                            if ($v1['game_id'] == $target_list[$k]['id']) {
                               $target_list[$k]['buy_status'] = 1;
                            }
                        }
                    }
            }
        }
        $data['list'] = $target_list;
        
        //获取总条数
        $data['pagecount'] = ceil(count($target_list_all) / $params['pagesize']);
        return $data;
    }
    
    //搜索list排序
    function _org_game_search_list($org_array,$keywords) {
        //配置优先级，使用数字越大的越优先
        //数字相等表示优先级相同，混在一起进行play_num排序
        define('MATCH_NONE', 1);
        define('MATCH_PART', 2);
        define('MATCH_ALL', 3);
        //下标第一级是name的匹配，-4
        global $config_priority;
        $config_priority = array();
        $config_priority[MATCH_NONE][MATCH_NONE] = 0;
        $config_priority[MATCH_NONE][MATCH_PART] = 10;
        $config_priority[MATCH_NONE][MATCH_ALL] = 20;

        $config_priority[MATCH_PART][MATCH_NONE] = 30;
        $config_priority[MATCH_PART][MATCH_PART] = 40;
        $config_priority[MATCH_PART][MATCH_ALL] = 50;

        //精确命中名字，未命中tag
        $config_priority[MATCH_ALL][MATCH_NONE] = 60;
        //精确命中名字，部分命中tag
        $config_priority[MATCH_ALL][MATCH_PART] = 70;
        //精确命中名字，精确命中tag
        $config_priority[MATCH_ALL][MATCH_ALL] = 80;
        
        //先将游戏分为命中名字组和命中标签组,命中又分为部分命中和全部命中
        $target_all = array();
        foreach ($org_array as $item) {
            $item['match_name'] = MATCH_NONE;
            $item['match_tag'] = MATCH_NONE;
            
            if ($item['name'] == $keywords) {
                $item['match_name'] = MATCH_ALL;
            } elseif ($keywords != "" && strpos($item['name'], $keywords) !== false) {
                $item['match_name'] = MATCH_PART;
            }

            $tag = explode(",", $item['tag']);
            $flag = false;
            foreach ($tag as $this_tag) {
                if ($this_tag == $keywords) {
                    $flag = true;
                    $item['match_tag'] = MATCH_ALL;
                    //只有完全命中才break，完全命中无需检查后面部分命中了
                    break;
                }
                if ($keywords != "" && strpos($this_tag, $keywords) !== false) {
                    $flag = true;
                    $item['match_tag'] = MATCH_PART;
                }
            }
            if ($flag == false) {
                //除非$keywords为空,或者输入数组是自己手写
                $item['match_tag'] = MATCH_NONE;
            }
            $target_all[] = $item;
        }
        function mysort($a, $b) {
            global $config_priority;
            $priority_a = $config_priority[$a['match_name']][$a['match_tag']];
            $priority_b = $config_priority[$b['match_name']][$b['match_tag']];
            if ($priority_a != $priority_b) {

                return ($priority_a < $priority_b ) ? 1 : -1;
            }
            if ($a['play_num'] == $b['play_num']) {
                //相等时候，如果需要更多判断，写在这里，例如
                if ($a['order_by'] == $b['order_by']) {
                    return 0;
                } else {
                    return ($a['order_by'] > $b['order_by']) ? 1 : -1;
                }
            } else {
                return ($a['play_num'] < $b['play_num']) ? 1 : -1;
            }
        }

        usort($target_all, "mysort");
        foreach ($target_all as $key => $item) {
            unset($target_all[$key]['match_name']);
            unset($target_all[$key]['match_tag']);
            unset($target_all[$key]['order_by']);
        }
        return $target_all;
    }

    //搜索关键字推荐
    function get_keywords_list($params) {
        $like_string = "%". $params['keywords'] . "%";
        if ($params['custom_game']) {
            $sql = "SELECT G_NAME AS keywords,G_VERSION AS g_version FROM pl_channelgame WHERE G_NAME like '" . $like_string . "' AND G_CLOSE = 0 AND STATUS = 0 AND G_TEMPLATE != 2 AND G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $sql = "SELECT G_NAME AS keywords,G_VERSION AS g_version FROM pl_game WHERE G_NAME like '" . $like_string . "' AND G_CLOSE = 0 AND STATUS = 0 AND G_TEMPLATE != 2";
        }
        $query = $this->DB->query($sql);
        // 记录数据库错误日志
        if ($query === false) {
            log_scribe('trace', 'model', 'get_keywords_list' . $this->ip . ': keywords：' . $keywords);
            $this->CI->error_->set_error(Err_Code::ERR_DB);
            return false;
        }
        $data  = array();
        $data1 = array();
        if ($query->num_rows() > 0) {
            $data = $query->result_array();
        }
        if (!empty($data)) {
            $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态          
            if ($v_info['version'] && $params['version'] >= $v_info['version']) {
                foreach ($data as $k=>$v) {
                    if ($v['g_version']) {
                        if ($params['version']== $v['g_version']) {
                            $data1[] = $v;
                        }
                    }
                }
                return $data1;
            }
        }
        return $data;
    }

    //搜索关键字排行
    function get_keywords_ranking($params) {
        $select = array(
            'IDX AS id',
            'H_ORDERBY AS order_id',
            'H_KEY AS keywords',
            'H_LIFT AS rank',
            'H_VERSION AS g_version'
        );
        $condition = array('STATUS' => 0);
        $data = $this->get_order_row_array($condition, $select, 'pl_hotkey', 'H_ORDERBY', 'ASC');
        if ($data === false) {
            log_scribe('trace', 'model', 'get_keywords_ranking :' . $this->ip . '  where :' . $condition);
            $this->CI->error_->set_error(Err_Code::ERR_KEYWORDS_RANK_NO_DATA);
            return false;
        }
        if (empty($data)) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            return false;
        }
        
        // 判断是否需要根据app版本号，获取不同的游戏列表
        $v_info = $this->CI->utility->check_ios_version();// ios当前使送审状态          
        if ($v_info['version'] && $params['version'] >= $v_info['version']) {
            foreach ($data as $k => $v) {
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
            return $data1;   
        }
        return $data;
    }
    
    /**
     * 热门游戏推荐
     */
    public function hotrecommended_list($params) {
        $per_page = $params['pagesize']; // 每页显示条数
        $offset   = $params['recordindex']; // 请求开始位置
        $type     = $params['type'];// 0：免费游戏 1：收费游戏 2:全部
        $uuid     = $params['uuid'];
        if ($params['custom_game']) {
            $table  = 'PL_HOTRECOMMENDED AS A, pl_channelgame AS B';
            $tab    = 2;
        } else {
            $table  = 'PL_HOTRECOMMENDED AS A, PL_GAME AS B';
            $tab    = 1;
        }
        
        if ($params['orderby'] == 5) {
            $orderby = 'A.T_ORDERBY ASC'; // 按照排序
        } else if ($params['orderby'] == 2) {
            $orderby = 'B.G_BUYNUM DESC';
        } else {
            $orderby = 'B.G_GAMESTAR DESC';
        }
        // 判断是否存在custom_game 拼接condition条件
        if ($type == 2 || !$type) {
            $condition_pub    = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND A.T_GAMEIDX = B.IDX "; 
        } else {
            $condition_pub    = "A.STATUS = 0 AND B.STATUS = 0 AND B.G_CLOSE = 0 AND B.G_TEMPLATE != 2 AND A.T_GAMEIDX = B.IDX AND B.G_GAMETYPE = ".$type; 
        }
        if ($params['custom_game']) {
            $condition          = $condition_pub." AND B.G_CHANNELIDX = ".$params['channel_id']." ORDER BY " . $orderby;
            $count_condition    = $condition_pub." AND B.G_CHANNELIDX = ".$params['channel_id'];
        } else {
            $condition          = $condition_pub ." ORDER BY " . $orderby;
            $count_condition    = $condition_pub;
        }
        
        // 判断是否存在version， 拼接condition条件  
        if (!$params['version']) {
            $condition = $condition." LIMIT ".$offset.",".$per_page;
        }
        $this->load->model('game_model');
        $data = $this->game_model->get_game_public($condition, $count_condition, $uuid, $table, $select = 2);
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
    
}
