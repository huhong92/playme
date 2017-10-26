<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Social extends P_Controller {
    const PAGE_SIZE = 10;
    function __construct() {
        parent::__construct(false);
        $this->load->model('social_model');
    }
    
    /**
     * 上传好友
     */
    public function upload_friend()
    {
        $params                 = $this->get_public_params();
        $params['mobile_info']  = urldecode($this->request_param('mobile_info'));
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $params['mobile_info']  = explode(',', trim($params['mobile_info'], ","));
        if ($params['mobile_info']) { // 加载好友
            // 查看mobile,在系统中，是否注册过
            foreach ($params['mobile_info'] as $val) {
                // 过滤掉86, 取最后11位
                if (strlen($val) == 13) {
                    $val = substr($val, 2);
                }
                
                // 根据手机号查询，可以查询到多条数据
                $u_list = $this->utility->get_user_info_by_mobile($val);
                if (empty($u_list) || !$u_list) {
                    $info['mobiles'][] = $val;
                } else {
                    foreach ($u_list as $v) {
                        $info['fids'][] = $v['uuid'];
                    }
                }
            }
            $info['uuid'] = $params['uuid'];
            
            $res = $this->social_model->load_friend($info);
            $this->output_json_return();
        }
    }
    
    /**
     * 获取好友列表
     */
    public function friend_list()
    {
        $params                 = $this->get_public_params();
        $params['recordindex']  = $this->request_param('recordindex');
        $params['pagesize']     = $this->request_param('pagesize');
        
        if ($params['recordindex'] === '' || !$params['pagesize']) {
            $this->error_->set_error(Err_Code::ERR_PARA);
            $this->output_json_return();
        }
        
        // 校验sign
        if (!$this->utility->check_sign($params, $params['sign'])) {
            $this->output_json_return();
        }
        
        $data = $this->social_model->friend_list($params);
        
        if (count($data['user_idx_list']) <= 0) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            $this->output_json_return();
        }
        
        $fids = array();
        if (is_array($data['user_idx_list'])) {
            foreach ($data['user_idx_list'] as $k=>$v) {
                if ($v['user_id'] != $params['uuid']) {
                    $fids[] = $v['user_id'];
                }
                if ($v['friend_id'] != $params['uuid']) {
                    $fids[] = $v['friend_id'];
                }
            }
        }
        
        if ($fids) {
            foreach ($fids as $value) {
                $u_info = $this->utility->get_user_info_by_friend_id($value);
                if ($u_info) {
                    $friend_list['list'][] =  $u_info;
                }
            }
            $this->error_->set_error(Err_Code::ERR_OK); // 查询不到用户，或者用户已经删除后，忽略错误码
        }
        if (!$friend_list['list']) {
            $this->error_->set_error(Err_Code::ERR_DB_NO_DATA);
            $this->output_json_return();
        }
        
        $friend_list['pagecount'] =  $data['pagecount'];
        $this->output_json_return($friend_list);
    }
    
}