/***************************common js******************/
function ajax(options) {
    options = options || {};
    options.type = (options.type || "GET").toUpperCase();
    options.dataType = options.dataType || "json";
    var params = formatParams(options.data);

    //创建 - 非IE6 - 第一步
    if (window.XMLHttpRequest) {
        var xhr = new XMLHttpRequest();
    } else { //IE6及其以下版本浏览器
        var xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }
    //接收 - 第三步
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            var status = xhr.status;
            if (status >= 200 && status < 300) {
                options.success && options.success(xhr.responseText, xhr.responseXML);
            } else {
                options.fail && options.fail(status);
            }
        }
    }
    //连接 和 发送 - 第二步
    if (options.type == "GET") {
        xhr.open("GET", options.url + "?" + params, true);
        xhr.send(null);
    } else if (options.type == "POST") {
        xhr.open("POST", options.url, true);
        //设置表单提交时的内容类型
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send(params);
    }
}
//格式化参数
function formatParams(data) {
    var arr = [];
    for (var name in data) {
        arr.push(encodeURIComponent(name) + "=" + encodeURIComponent(data[name]));
    }
    arr.push(("v=" + Math.random()).replace("."));
    return arr.join("&");
}

/*********************/
var static_url = 'http://172.18.194.92:8080/'
var playmeJS = {
    statistics_game_pv : function(){
        var _url = static_url+"api";
        var _load_url = document.location.href;
        ajax({
            url: _url,              //请求地址
            type: "GET",                       //请求方式
            data: {method:'statistics_pv',url: _load_url },        //请求参数
            dataType: "json",
            success: function (response, xml) {
                // 成功
                var _obj =(new Function("","return "+response))();
                if(_obj.c == "0000") {
                    throw new Error("统计"+_load_url+"成功");
                } else {
                    throw new Error("code:"+_obj.c+"   msg:"+_obj.m);
                }
            },
            fail: function (status) {
                //失败
                throw new Error("系统错误"+status);
            }
        });
    },
    get_produce_game_info: function(){
        var _url = static_url+"api";
        var _load_url = document.location.href;
        ajax({
            url: _url,              //请求地址
            type: "GET",                       //请求方式
            data: {method:'get_produce_info',url: _load_url },        //请求参数
            dataType: "json",
            success: function (response,xml) {     
                // 成功 
                var _obj =(new Function("","return "+response))();
                if(_obj.c == "0000") {
                    // 加载图片dom
                    var gamePic = document.createElement("span");
                    var txt = document.createTextNode(_obj.data.pic);
                    gamePic.appendChild(txt);
                    gamePic.setAttribute("id", "gamePic");
                    gamePic.setAttribute("style", "display:none");
                    document.body.appendChild(gamePic);
                    //加载名称dom
                    var gameName = document.createElement("span");
                    var txt_name = document.createTextNode(_obj.data.name);
                    gameName.appendChild(txt_name);
                    gameName.setAttribute("id", "gameName");
                    gameName.setAttribute("style", "display:none");
                    document.body.appendChild(gameName);
                } else {
                    throw new Error("code:"+_obj.c+"   msg:"+_obj.m);
                }
            },
            fail: function (status) {
                //失败
                throw new Error("系统错误"+status);
            }
        });
    }
}

window.onload = function () {
    playmeJS.statistics_game_pv();
    playmeJS.get_produce_game_info();
}