<!DOCTYPE html>
<html>
    <head>
        <title>注册/登录界面</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width">
        <script type="text/javascript" src="<?php echo base_url() ?>js/playme_statistics.js"></script>
    </head>
    <body>
        <div>test login</div>   
        <form action="<?php echo base_url()?>index?method=register" method="post">
            app_id:    <input type="text" name="app_id" value="1"><br />
            device_id: <input type="text" name="device_id" value="1"><br />
            channel:      <input type="text" name="channel" value="0"><br />
            source： <input type="text" name="source" value="0"><br />
            version： <input type="text" name="version" value="1.0.1"><br />
                      os： <input type="text" name="os" value="0"><br />
<!--     user_id： <input type="text" name="user_id" value=""><br />
            nickname： <input type="text" name="nickname" value=""><br />
            gender： <input type="text" name="gender" value="女"><br />
            province： <input type="text" name="province" value="上海"><br />-->
            account： <input type="text" name="account" value="15210047119"><br />
            pass： <input type="text" name="password" value="123456"><br />
            verify_code： <input type="text" name="verify_code" value=""><br />
            nickname<input type="text" name="nickname" value="你好"><br />
            sign<input type="text" name="sign" value=""><br />
            <input type="submit" value="提交">
        </form>
    </body>
</html>