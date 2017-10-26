<?php
$params = $_REQUEST;
while (list($key, $val) = each($params)) {
            if ($key == "sign" || ($val === "")){
                continue;
            } else {
                $para[$key] = $params[$key];
            }
        }
        //对数组进行字母排序
        ksort($para);
        reset($para);
        while(list($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        $sign_key = 'PLAYSIgn7gXLvCu8h668o8buYRd';
        $arg .= "key=".$sign_key;
        
        $sign = md5($arg);
        echo $sign;
?>