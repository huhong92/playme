<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['mobile_key'] = 'SDK2014MOGTCD0001PASS900';

$config['sdkwap_key'] = 'SDK2014A0GCSqGSIb3DQEBAQUAA4GNA'; //wap登录的私key
$config['sdkwap_cookie_key'] = 'SDK2014xPJzyJZwGm5DJo4kr1ivaut'; //cookie的公key

/**
 * 需要进入控制流程的用户状态配置
 */
$config['user_status'] = array(
	//允许登录的用户状态
	'login' => array(
		ACCOUNT_STATUS_NONE,		//正常账号
		ACCOUNT_STATUS_SLIENCE,     //可疑账号
		ACCOUNT_STATUS_SUSPENDED,   //沉默账号
	),
	//需要洗白的用户状态
	'wash' => array(
		ACCOUNT_STATUS_SUSPENDED,	//可疑账号
		ACCOUNT_STATUS_SLIENCE,		//沉默账号
	),
);
$config['trust_url'] =  array(
	'the9.com',
	'17dou.com',
	'freerealms.com.cn',
	'red5studios.com.cn',
	'the9dev.com',
	'17doudou.com',
	'atolchina.com',
	'muxchina.com',
	'red5studios.com.sg',
	'the9edu.com',
	'17qiu.com',
	'ro2china.cn',
	'the9img.com',
	'pass9.com',
	'service.joyxy.com',
	'eafifaonline2.com',
	'huopuyun.com',
	'pass9.net',
	'epass.9ctime.com',
	'wofchina.com',
	'9c.com',
	'joyxy.com',
	'planetside2.com.cn',
	'service.wowchina.com',
	'9ctime.com',
	'firefallcn.com',
	'wowchina.com',
	'9ctime.net',
	'firefall.com.cn',
	'9iplaytv.com',
	'fmonline.net',
	'red5china.com',
	'9zwar.com',
	'red5singapore.com',
	'the9.cn',
	'fohchina.com',
	'mingchina.com.cn',
	'red5studios.cn',
);
