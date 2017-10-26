<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Err_Code {
    const ERR_OK =   '0000'; //成功
    //1001  --- 1099  系统错误
    const ERR_PARAM_SIGN =   '1001'; //签名认证不通过
    const ERR_DB =   '1002'; //服务器异常
    const ERR_PARA =   '1003'; //参数错误
    const ERR_FILE =   '1004'; //操作文件失败
    const ERR_DB_NO_DATA = "1005";//没有可操作的数据
    const ERR_LOGIN_TOKEN_FAIL =   '1006'; //令牌认证失败，请重新登录
    const ERR_LOGIN_TOKEN_EXPIRE = '1007'; //令牌已过期，请重新登录
    const ERR_MC_FAIL = '1008'; //mc异常
    const ERR_TOKEN_EMPTY   = '1009';// token不能为空
    const ERR_UNKOWNL =   '1099'; //未知错误
    




    //1101  --- 1999  用户信息错误
    const ERR_APP_CONFIG_NO_DATA = '1101';//未查询到应用数据
    const ERR_NICKNAME_NOT_NULL = '1102';//昵称不能为空
    const ERR_USER_INFO_NO_DATA = '1103';//未查询到用户数据
    const ERR_MOBILE_FORMAT = '1111';//手机号格式不正确，请填写正确的手机号
    const ERR_MOBILE_LIMIT_MOBILE_IP = '1112'; //很抱歉，同一IP每天对同一手机号码触发验证短信不超过3条
    const ERR_MOBILE_LIMIT_IP = '1113'; //很抱歉，同一IP每天最多只能对3个不同手机号码进行触发验证码
    const ERR_MOBILE_LIMIT_MOBILE = '1114'; //很抱歉，每天对同一手机号码触发验证短信不超过5条
    const ERR_MOBILE_SEND_QUICK = '1115'; //很抱歉，您的手机号码短消息发送过快，请稍候重试。
    const ERR_MOBILE_CODE_TIMES = '1116'; //获取验证码太过频繁，请在%s秒后重试
    const ERR_SEND_MSG_FAIl = '1117';//短信发送失败
    const ERR_MOBILE_NO_DATA = '1201';//手机号不能为空
    const ERR_PWD_FORMAT = '1202';//您输入的密码格式不正确，请输入6～15位的密码
    const ERR_REG_NICKNAME_FORMAT = '1203';//昵称格式不正确，长度为2～10位
    const ERR_VERIFYCODE_NO_DATA = "1204";//验证码不能为空
    const ERR_MOBILE_VERIFY_CODE_LOSE = '1205';//手机验证码已失效
    const ERR_MOBILE_VERIFY_CODE_FAIl = '1206';//手机验证码验证失败
    const ERR_REG_ACOUUNT_IS_EXIT = '1207';//该手机号已注册，请重新输入手机号
    const ERR_LOGIN_ACCOUNT_FAIL = '1208';//账号名或密码不正确。请重新登录
    const ERR_CHANNEL_FAIL = '1209';//渠道（channel）不正确
    const ERR_OS_FAIL = '1210';//操作系统参数错误
    const ERR_INSERT_USER_INFO_FAIL = '1211';//插入用户信息失败
    const ERR_INSERT_USER_DEVICE_FAIL = '1212';//记录用户登录设备失败
    const ERR_INSERT_USER_LOGINLOG_FAIL = '1213';//记录用户登录日志失败
    const ERR_REG_NICKNAME_IS_EXIT = '1214';//昵称重复，请重新填写
    const ERR_UPDATE_USERINFO_FAIL = '1215';//用户信息修改失败
    const ONLY_FIND_MOBILE_PWD = '1216'; // 只有手机号注册账户才能找回密码
    const ACCOUNT_NOT_FOUND = '1217'; // 该账号不存在
    const TIMES_2_PWD_DIFF = '1218';// 2次密码输入不一致
    const UPDATE_PWD_FAIL = '1219';// 密码修改失败
    
    const ERR_FEEDBACK_CONTENT_LEN = '1301';//反馈内容不能为空，且最多50个字
    const ERR_INSERT_USER_FEEDBACK_FAIL = '1302';//反馈信息提交失败
    //
    const ERR_INSERT_GOLD_HISTORY_FAIL = '1401';//金币变更记录历史失败
    const ERR_INSERT_INTEGRAL_HISTORY_FAIL = '1402';//积分变更记录历史失败
    
    const ERR_GET_APP_SOURCE_FAIL = '1501';// 用户注册时，APP来源获取失败
    const ERR_RE_BIND_MOBILE_FAIL = '1502';// 用户完善个人信息是，不能重新绑定手机号
    const ERR_LOGIN_TYPE_FAIL = '1503';//登录方式不正确
    
    //
    //
    // 2000 --- 2099  游戏信息错误
    const ERR_MAKINGGAME_DELETE_FAIL = '2000';// 制作游戏删除失败
    const ERR_GAME_PRICE_NOT_CONFIRM = '2001';//您提交的购买价格与商品实际价格不符
    const ERR_GAME_COIN_NOT_ENOUGH = '2002';//您的余额不足，不能购买该游戏金币不足
    const ERR_GAME_CANNOT_BUY = '2003';//该游戏不存在或已下架
    const ERR_UPDATE_GAME_BUY_FAIL = '2004'; // 游戏购买失败
    const ERR_UPDATE_GAME_BUY_NUM_FAIL = '2005'; // 游戏购买次数次数更新失败
    const ERR_BUY_GAME_REDUCE_COIN_FAIL = '2006';//扣除余额失败
    const ERR_MAKING_GAME_FAIL = '2007';//制作游戏失败
    
    
    const ERR_USER_SHARE_IS_EXIT = '2008'; //您已经分享过该游戏了
    const ERR_GAME_CANNOT_SHARE = '2009';//该游戏不存在或已下架
    const ERR_UPDATE_GAME_SHARE_NUM_FAIL = '2010'; //游戏分享次数更新失败
    const ERR_GAME_SHARE_RECORD_FAIL = '2011';//游戏分享记录失败
    
    const ERR_GAME_GET_FAIL = '2012'; // 游戏获取失败
    const ERR_GAME_IS_DOWNLOAD = '2013';//游戏已经下载过了
    
    const ERR_GAME_INFO_NO_EXIT = '2014';//该游戏不存在
    const ERR_COMMENT_CONTENT_LEN = '2015';//评论内容太长，请重新输入
    const ERR_COMMENT_SCORE_LIMIT = '2016';//评分只能1～5分
    const ERR_CANNOT_COMMENT_GAME = '2017';//该游戏不能进行评论评分
    const ERR_INSERT_COMMENT_FAIL = '2018';//评论失败
    const ERR_UPDATE_SCORE_STAR_FAIL = '2019';//修改最高评分失败
    const ERR_GET_COMMENT_LIST_FAIL = '2020';//获取评论列表失败
    const ERR_GET_COMMENT_COUNT_FAIL = '2021';//获取评论总条数失败
    const ERR_UPDATE_GAMESCORE_STAR_FAIL = '2022';//修改游戏综合评分失败
    
    const ERR_CANNOT_FAVORITE_GAME = '2023';//该游戏不存在或下架
    const ERR_GAME_IS_FAVORITE = '2024';//该游戏已收藏
    const ERR_INSERT_FAVORITE_FAIL = '2025';//加入收藏失败
    const ERR_DELETE_FAVORITE_FAIL = '2026';//取消收藏失败
    const ERR_UPDATE_GAME_INFO_FAIL = '2027'; // 游戏信息更新失败
    
    const ERR_USER_INFO_UPDATE = '2041';//用户信息修改失败
    const ERR_FILE_UPLOAD_FAIL = '2042';//文件上传失败
    const ERR_GAME_POINT_NOT_ENOUGH = '2043';//积分不足
    const ERR_FTP_CONNECT_FAIL = '2044';// FTP连接失败
    
    const ERR_GAME_SCORING_UPDATE_FAIL = '2045';// 游戏得分修改失败
    const ERR_IMAGE_FORMAT_INCORRECT = '2046';// 图片格式不正确   
    const ERR_UPLOAD_IMAGE_IS_NULL = '2047';// 上传图片不能为空   
    const ERR_UPDATE_MAKING_IMAGE_FAIL = '2048';// 制作图片更新失败  
    const ERR_GAME_NO_PLAY = '2049'; // 你还没玩过这个游戏
    
    const ERR_GAME_SCORE_SAVE_FAIL = '2060'; // 游戏得分保存失败pl_game_one
    const ERR_GAME_MAX_SCORE_NOT_0 = '2061'; //  '2061'=> '游戏最高得分不能为0',
    const ERR_DEVELOPER_GAME_PLAY_RECORD_FAIL = '2062'; // 开发商游戏玩过统计失败
    
    const ERR_BAILU_PAY_CALL_BACK_URL = '2081'; // 获取白鹭游戏支付回调地址失败
    const ERR_BAILU_PAY_ORDER_FAILURE = '2082'; // 白鹭支付订单已过期
    
    //搜索模块 3001-----3099
    const ERR_SEARCH_KEYWORDS_NONE = '3001';//搜索关键字不能为空
    const ERR_SEARCH_LIST_NO_DATA = '3002';//没有查询到数据
    const ERR_KEYWORDS_RANK_NO_DATA = '3005';//没有关键字排行数据
    
    //活动模块 4001-----4099
    const ERR_GET_LACTIVETY_LIST_FAIL = '4001';//获取活动列表失败
    const ERR_GET_ACTIVITY_COUNT_FAIL = '4002';//获取活动总条数失败
    const ERR_GET_BANNER_LIST_FAIL = '4003';//获取banner列表失败
    const ERR_GET_BANNER_COUNT_FAIL = '4004';//获取banner总条数失败
    const ERR_LACTIVETY_DETAIL_NO_DATA = '4005';//没有查询到详情数据
    
     //统计等api模块 5001-----5099
    const ERR_STATICTISE_SHAREPLAY_PV_FAIL = '5001';//统计分享被玩PV失败
    const ERR_STATICTISE_PRODUCE_PV_FAIL = '5002';//统计制作游戏分享被打开PV，被玩pv失败
    const ERR_STATICTISE_GAME_PV_FAIL = '5003';//统计游戏分享被打开PV，被玩pv失败
    const ERR_STATICTISE_SHARE_IS_NOT_EXIT = '5004';//分享内容不存在
    const ERR_PRODUCE_GAME_SHAER_NO_EXIT = '5005';//未获取到用户上传照片及游戏名
 
    // 上传数据
    const ERR_GAME_FILE_NOT_FOUND = '6000'; // 服务器没有找到该游戏，请先上传游戏
    //任务上领取积分和金币----6100-6199
    const ERR_GET_POINT_COIN_FAIL = '6100';// 没有金币或积分可领取
    const ERR_TASK_NOT_FOUND = '6101';// 任务没有找到
    const ERR_GET_AWARD_FAIL = '6102';// 奖励领取失败
    const ERR_GET_AWARD_FINISH = '6103';// 您已经领取过该任务的奖励
    const ERR_WITHOUT_COMPLETE_TASK = '6104';// 您没有完成该任务
    const ERR_FREE_TASK_TAIL = '6105';// 免费游戏任务获取失败
    const ERR_BUY_COIN_TASK_TAIL = '6106';// 购买金币游戏任务获取失败
    const ERR_DOWNLOAD_COIN_TASK_TAIL = '6107';// 下载金币游戏任务获取失败
    const ERR_MAKING_TASK_TAIL = '6108';// 制作游戏任务获取失败
    const ERR_FIRST_SHARE_TASK_TAIL = '6109';// 首次分享任务获取失败
    const ERR_FIRST_COMMENT_TASK_TAIL = '6110';// 首次评论任务获取失败
    const ERR_MAKING_PLAY_TASK_TAIL = '6111';// 制作被打开的次数任务获取失败
    const ERR_FULL_USER_INFO_TASK_TAIL = '6112';// 完善信息任务获取失败
    const ERR_BREACH_SCORING_TASK_TAIL = '6113';// 突破记录任务获取失败

    const ERR_TASK_IS_NOT_EXIT ='6201';//该任务不存在'
    const ERR_INSERT_TASK_COMPLETE_FAIL = '6202';//插入任务完成记录失败'
    const ERR_GET_APP_COMMENT_URL_FAIL = '6203';// '6203' => 'APP评论地址获取失败',
    
    //消息推送 7001-7099
    const INSERT_ERR_PUSH_TASK_FAIL = '7001';//插入消息推送任务记录失败
    const INSERT_ERR_SEND_MSG_FAIL = '7002';//插入消息推送记录失败
    
    // 信箱 8001 - 8099
    const ERR_UPDATE_MAILBOX_STATUS_FAIL = '8001';// 信箱已读未读状态更新失败
    const ERR_DELETE_FAIL_BY_NOT_READ = '8002';// 信箱未读，删除失败
    const ERR_MAILBOX_DELETE_FAIL = '8003'; // 信箱删除失败
    
    // 非法字符
    const ERR_ILLEGAL_CHAR = '9001';// '包含非法字符',
    
    // 商城 2101 --- 2199 
    const ERR_WINXIN_PAY_FAIL = '2101'; // '2101' => '微信支付，通信失败',
    const ERR_PRODUCT_ORDER_INSERT_FAIL = '2102'; // 商品订单生成失败
    const ERR_PRODUCT_ORDER_UPDATE_FAIL = '2103'; // 商品订单更新失败
    const ERR_WX_PREPAY_ORDER_FAIL = '2104'; // 微信预下订单失败
    const ERR_INSERT_RECHARGE_HISTORY_FAIL = '2105'; // 记录用户充值失败
    const ERR_WITHOUT_RECHARGE_INFO = '2106'; // 暂时没有充值包数据
    const ERR_ZFB_PAY_FAIL = '2107'; // 支付宝支付失败
    
    // 社交 2201 --- 2299
    const ERR_FRIEND_LOAD_FAIL = '2201'; // 好友添加失败
    const ERR_LINKMAN_RECORD_FAIL = '2202'; // 通讯录好友记录失败
    const ERR_FRIEND_NOT_EXISTS_FAIL = '2203'; // 改好友已经不存在
    
    // 其他 2301 --- 2399
    const ERR_GET_VISITOR_STATUS_FAIL = '2301'; // 获取游客打开关闭状态失败
    
    // 开发商接入游戏
    const ERR_JOIN_GAME_RATE_FAIL = '2401'; // 接入游戏时，未配置汇率
    const ERR_PROPBUY_INSERT_FAIL = '2402'; // 预购买道具，订单生成失败
    const ERR_PROPBUY_SELECT_FAIL = '2403';// 购买道具查询失败
    const ERR_PROP_BUY_FAIL = '2404'; // 道具购买失败
    const ERR_GAME_JOIN_ID_FAIL = '2405';// 游戏接入号错误
    const ERR_CHANNEL_INFO_NOT_FOUND = '2406'; // 渠道商信息获取失败
    const ERR_DEVELOPER_DEBUG_GAME_LINK_EXPIRE = '2407'; // 开发者接入游戏debug测试链接失效
    const ERR_GAME_NOT_EXISTS = '2408'; // 开发者上传游戏不存在
    const ERR_QUALIFICATE_INFO_GET_FAIL = '2409'; // 开发者资质信息获取失败
    const ERR_DEVELOPERID_ERR_FAIL = '2410'; // 开发者ID填写错误
    
}