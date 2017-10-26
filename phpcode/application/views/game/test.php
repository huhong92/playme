<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <title>eeee</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width">
        <script type="text/javascript" src="../../../js/playme_statistics.js"></script>
    </head>
    <body>
        <div>test</div>
        <form action="<?php echo base_url();?>/cron/game_check" method="post">
            送审版本：<input type="text" name="version" value="3.0.0"><br />
            <?php foreach ($list as $k=>$v): ?>
            id:<?php echo $v['id'] ?><input type="checkbox" name="id[]" value="<?php echo $v['id'] ?>" >name:<?php echo $v['name'] ?><br />
            <?php endforeach;?>
            <input type="submit" value="提交">
        </form>
    </body>
    
</html>



