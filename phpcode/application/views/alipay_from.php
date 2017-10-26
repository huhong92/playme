<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>支付宝即时到帐接口 For CodeIgniter</title>
</head>

<body>
<h1>支付宝即时到帐接口 For CodeIgniter</h1>
<form action="<?php echo base_url()?>index.php/alipay/doalipay" method="post">
  <label>渠道ID</label>
  <input name="channel_id" type="text" value="1111"/><br />
  <label>商品ID</label>
  <input name="product_id" type="text" value="1"/><br />
  <label>用户ID</label>
  <input name="uuid" type="text" value="15425"/><br />
  
  <label>token</label>
  <input name="token" type="text" value="12123123123123"/><br />
  <label>sign</label>
  <input name="sign" type="text" value="12123123123123"/><br />
  <label>method</label>
  <input name="method" type="text" value="doalipay"/><br />
  <label>订单名称</label>
  <input name="order_name" type="text" value="订单1"/>
  <span>订单名称，显示在支付宝收银台里的"商品名称"里，显示在支付宝的交易管理的"商品名称"的列表里。</span><br />
    
  <label>支付方式</label>
  <input name="pay_method" type="text" value="1" />
  <span>0：微信支付 1：支付宝支付</span><br />
  <input name="" type="submit" value="确认订单" />
</form>
</body>
</html>
