<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['token_expire'] = 30*24*3600;		//用户登录态过期时间（秒）
$config['token_key']    = 'PLAYsfmepl7gXLvCu8hsoopyak'; //token加密的key
$config['sign_key']     = 'PLAYSIgn7gXLvCu8h668o8buYRd'; //sign加密的key
$config['coin_rate']    = 10;// 10金币=1元

$config['access_token_white_ip'] = array('172.18.196.35'); //校验通过的access_token使用的ip白名单

$config['login_type'] = array(0,1,2,3,4);//登录渠道 0：微信1：QQ 2：九城, 3:手机号 4.渠道商
$config['playme_os'] = array(0,1,2,3);//操作系统 手机类型 0:iphone 1:android 2:pc 3:orther

$config['score_array'] = array(1,2,3,4,5);//评分

$config['mobile_code_expire'] = 300;	//手机验证码有效时间（秒）
$config['mobile_code_period'] = 120;	//手机重新获取验证码的间隔时间（秒）
$config['mobile_code_length'] = 6;            //手机验证码字符长度
$config['interval_limit_by_ip']      = 10;    //同一IP对不同手机触发验证信息时间间隔不低于10分钟
$config['number_limit_by_mobile_ip'] = 30;    //同一IP每天对同一手机号码触发验证短信不超过3条
$config['mobile_limit_by_ip']        = 50;    //同一IP每天最多只能对3个不同手机号码进行触发验证码
$config['mobile_limit_by_mobile']    = 5;      //每天对同一手机号码触发验证短信不超过5条
 
$config['mobile_code_prefix']        =  'T_';             //手机验证码时间戳key前缀
$config['mobile_limit_prefix']       = 'MOBILE_LIMIT_';   //手机验证码发送限制key前缀

// game常量
// $config['game_url'] = "http://web.playme.the9.com/";
$config['game_url'] = "http://172.18.194.123";
// $config['game_url'] = "http://172.18.194.115";
// 渠道商URL
// $config['channel_url'] = "http://172.18.67.25/work/html5/ionic/myApp/www/index.html";
$config['channel_url'] = "http://wx.playme.the9.com/weixin/www/index.html";

// 开发者游戏测试时效
$config['game_token'] = 24*3600;		//request game_token有效时间 1天

/**
 * ftp常量
 */
$config['ftp_config'] = array('ip'=>'172.18.194.123', 'ftp_user' => 'ftp1', 'ftp_pass' => '123456');
// $config['ftp_config'] = array('ip' => '10.126.78.202', 'ftp_user' => 'www', 'ftp_pass' => 'www12#Pass&');
// $config['ftp_config'] = array('ip'=>'172.18.194.115', 'ftp_user' => 'user', 'ftp_pass' => 'The9');

/**
 * 业务常量定义
 */
require_once 'passport_consts.php';
//消息
$config['NOMAKEGAME'] = array(
    'title' => '总积分达到900',
    'content' => '你的积分已经达到[integral],赶紧去制作游戏吧',
);
$config['MAKINGPLAYNUM'] = array(
    'title' => '被打开次数',
    'content' => '您制作的游戏被打开已经超过[play_num]次',
);
$config['TOPGAMESCORE'] = array(
    'title' => '最高分',
    'content' => '[nickname]超越了你的最高分',
);
$config['NICKNAMECHAR'] = array(
    'title' => '昵称含有特殊字符',
    'content' => "您的用户名涉及符号,系统已用空格代替,您可自行前往'我的信息'进行修改",
);
$config['ILLEGALCHAR'] = array(
    'title' => '昵称涉及屏蔽字',
    'content' => '您的用户名涉及屏蔽字,系统已用*代替,您可自行前往"我的信息"进行修改',
);

/*
 * PAY 支付配置项
 */
// *******************微信配置
$config['app_id']       = 'wxcee4103498e3901a'; // 公众号
$config['mch_id']       = '1235055202'; // 商户号
$config['secret_key']   = 'wanghan123123PlayMe321321wanglei'; // 秘钥


// ******************支付宝配置
$config['alipay_config'] = array(
        'partner'       => '2088911290513654',   //这里是你在成功申请支付宝接口后获取到的PID;
        "key"           => "x9lje3n1nml2fggx9e2et7dv31g6mnc9",
        'seller_id'     => '2088911290513654',
        // 'notify_url'    => base_url()."/index/index",
        'notify_url'    =>"http://api.playme.the9.com/index/index",
        'seller_email'  => 'xiaobawang@corp.the9.com',
        'sign_type'     => strtoupper('MD5'),
        'input_charset' => strtolower('utf-8'),
        'private_key'   => 'MIICXAIBAAKBgQDDgdW520TzrApa45eHaEfUB09d/sawEyVDO/GCHqqVsr/GUJqSzTxzBANw/f9g2u20H2XoEypbGVeYJuyjmK6Dm1mEQFphUrLYkkm74jRlB/9NyWOlG+f3/hV0G9jySCmUltBgzeHt37QK33DdI2ZQ6L0KMAgQqpqPW+4RFRCcUQIDAQABAoGAFVs+dJH+Qzv82ZbY+6KpjgDKa7MkEyHURTbsF9GvwrCHAGvXpseindHHanVkizj/FFkFscc+LjtjdSxzVx+bmHXjfdwLeLBaWDy+ZH4sO7sWazf6C2DUhPjjFIWJPVOmtD2xECBBacjgBZQefcqUdMRp1SRqn2txSikQ9Q/8XK0CQQDiZkDaqTUwu5mXGLZKEjgOYpG3GQuJ9sai0wjShAWMuRH+Itfqz1Lobojzuj9gUzBkZ4VtntWEAqg1SYl1APCLAkEA3RGYl5md2mkVCFQOsVFYcl2b1pmUSD8DCKdjbuMt/yFqKKIreP3dEwcORUB4mw0kj/hYloHW2Ap0LCU49QKGEwJAXUaVp8EZCgfwoqDq0Z+p+rs/n7kw2NmUQxdBRkJgavcA47yFSte6J8sKn6f3Xn9Hq8Y+4cgT3fyeQr4WZN9LOwJAHhzKe0P6g4iyy7qfcbnR4Wos0xOCZkDnCeO7IJyjZFBJ5JUKdOWnmnLol7hLdVtZ8p5yerXe7PinkGfVlVItrwJBAIzXQn4YdctLMcJrxyYb7SuEahk94Q/3jEQKlWTX1XOToxZfBOST0/EvVrnqAi1A5G4WYe72EqzKu9of5s5JDkQ=',
        'private_key_path'        => './key/rsa_private_key.pem', // 商户的私钥（后缀是.pen）文件相对路径
        'ali_public_key_path'     => '/key/rsa_public_key.pem', // 商户的公钥（后缀是.pen）文件相对路径
        'transport'     => 'http',
    );
$config['alipay'] = array(
        //这里是卖家的支付宝账号，也就是你申请接口时注册的支付宝账号
        'seller_payno'=>'xiaobawang@corp.the9.com',
        //这里是异步通知页面url，提交到项目的Pay控制器的notifyurl方法；
        'notify_url'=>base_url().'alipay/notifyurl', 
        //这里是页面跳转通知url，提交到项目的Pay控制器的returnurl方法；
         'return_url'=>base_url().'alipay/returnurl',
        //支付成功跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参payed（已支付列表）
        'successpage'=>base_url(),
        //支付失败跳转到的页面，我这里跳转到项目的User控制器，myorder方法，并传参unpay（未支付列表）
        'errorpage'=>base_url(), 
    );


// ******************  IOS 支付环境
$config['ios_environment'] = 'sandbox'; // buy sandbox



// **********playme 专题信息********//
$config['playe_theme']  = array('id'=>1,'name'=>'烧脑游戏合集','pic'=>$config['game_url'].'/player_theme/2.png','descript'=>'各类经典烧脑游戏，挑战你的脑力极限。');

// **********白鹭 专题信息********//
$config['rmb'] = 10;// 1元 = 10金币
$config['channel'] = 21627;
$config['bailu_key'] = 'JcGPqlN2PVI5DcAVDTZ5c';
$config['buy_bailu_item'] = 'http://web.playme.the9.com/games/public/recharge_egret.html';
