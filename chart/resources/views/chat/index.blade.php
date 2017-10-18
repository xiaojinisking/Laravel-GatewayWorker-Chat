<html><head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>workerman-chat PHP聊天室 Websocket(HTLM5/Flash)+PHP多进程socket实时推送技术</title>
    <link href="/asset/css/bootstrap.min.css" rel="stylesheet">
    <link href="/asset/css/style.css" rel="stylesheet">

    <script type="text/javascript" src="/asset/js/swfobject.js"></script>
    <script type="text/javascript" src="/asset/js/web_socket.js"></script>
    <script type="text/javascript" src="/asset/js/jquery.min.js"></script>

    <script type="text/javascript">
        if (typeof console == "undefined") {    this.console = { log: function (msg) {  } };}
        // 如果浏览器不支持websocket，会使用这个flash自动模拟websocket协议，此过程对开发者透明
        WEB_SOCKET_SWF_LOCATION = "/swf/WebSocketMain.swf";
        // 开启flash的websocket debug
        WEB_SOCKET_DEBUG = true;
        var ws, name, client_list={};

        // 连接服务端
        function connect() {
            // 创建websocket
            ws = new WebSocket("ws://"+document.domain+":8282");
            // 当socket连接打开时，输入用户名
            ws.onopen = onopen;
            // 当有消息时根据消息类型显示不同信息
            ws.onmessage = onmessage;
            ws.onclose = function() {
                console.log("连接关闭，定时重连");
                connect();
            };
            ws.onerror = function() {
                console.log("出现错误");
            };
        }

        // 连接建立时发送登录信息
        function onopen()
        {

            var login_data = '{"type":"onconnect"}'

            console.log("websocket握手成功");

            ws.send(login_data);
        }

        // 服务端发来消息时
        function onmessage(e)
        {
            console.log(e.data);
            var data = JSON.parse(e.data);
            var room_id="<?php echo isset($_GET['room_id']) ? $_GET['room_id'] : 1?>";
            switch(data['type']){
                case 'init':
                    $.post('./chat/bind', {client_id: data.client_id,room_id:room_id}, function(data){

                    }, 'json');
                    break;
                // 服务端ping客户端
                case 'ping':
                    ws.send('{"type":"pong"}');
                    break;;
                // 登录 更新用户列表
                case 'login':
                    //{"type":"login","client_id":xxx,"client_name":"xxx","client_list":"[...]","time":"xxx"}
                    say(data['client_id'], data['client_name'],  data['client_name']+' 加入了聊天室', data['time']);
                    if(data['client_list'])
                    {
                        client_list = data['client_list'];
                    }
                    else
                    {
                        client_list[data['client_id']] = data['client_name'];
                    }
                    flush_client_list();
                    console.log(data['client_name']+"登录成功");
                    break;
                // 发言
                case 'say':
                    //{"type":"say","from_client_id":xxx,"to_client_id":"all/client_id","content":"xxx","time":"xxx"}
                    say(data['from_client_id'], data['from_client_name'], data['content'], data['time']);
                    break;
                // 用户退出 更新用户列表
                case 'logout':
                    //{"type":"logout","client_id":xxx,"time":"xxx"}
                    say(data['from_client_id'], data['from_client_name'], data['from_client_name']+' 退出了', data['time']);
                    delete client_list[data['from_client_id']];
                    flush_client_list();
            }
        }



        // 提交对话
        function onSubmit() {
            var input = document.getElementById("textarea");
            var to_client_id = $("#client_list option:selected").attr("value");
            var to_client_name = $("#client_list option:selected").text();
            $.post('/chat/say',{"type":"say","to_client_id":to_client_id,"to_client_name":to_client_name,"content":input.value.replace(/"/g, '\\"').replace(/\n/g,'\\n').replace(/\r/g, '\\r')},function(){

            },'json');
            input.value = "";
            input.focus();
        }

        // 刷新用户列表框
        function flush_client_list(){
            var userlist_window = $("#userlist");
            var client_list_slelect = $("#client_list");
            userlist_window.empty();
            client_list_slelect.empty();
            userlist_window.append('<h4>在线用户</h4><ul>');
            client_list_slelect.append('<option value="all" id="cli_all">所有人</option>');
            for(var p in client_list){
                userlist_window.append('<li id="'+p+'">'+client_list[p]+'</li>');
                client_list_slelect.append('<option value="'+p+'">'+client_list[p]+'</option>');
            }
            $("#client_list").val(select_client_id);
            userlist_window.append('</ul>');
        }

        // 发言
        function say(from_client_id, from_client_name, content, time){
            $("#dialog").append('<div class="speech_item"><img src="http://lorempixel.com/38/38/?'+from_client_id+'" class="user_icon" /> '+from_client_name+' <br> '+time+'<div style="clear:both;"></div><p class="triangle-isosceles top">'+content+'</p> </div>');
        }

        $(function(){
            select_client_id = 'all';
            $("#client_list").change(function(){
                select_client_id = $("#client_list option:selected").attr("value");
            });
        });
    </script>
</head>
<body onload="connect();">
<div class="container">
    <div class="row clearfix">
        <div class="col-md-1 column">
        </div>
        <div class="col-md-6 column">
            <div class="thumbnail">
                <div class="caption" id="dialog"></div>
            </div>
            <form onsubmit="onSubmit(); return false;">
                <select style="margin-bottom:8px" id="client_list">
                    <option value="all">所有人</option>
                </select>
                <textarea class="textarea thumbnail" id="textarea"></textarea>
                <div class="say-btn"><input type="submit" class="btn btn-default" value="发表" /></div>
            </form>
            <div>
                &nbsp;&nbsp;&nbsp;&nbsp;<b>房间列表:</b>（当前在&nbsp;房间<?php echo isset($_GET['room_id'])&&intval($_GET['room_id'])>0 ? intval($_GET['room_id']):1; ?>）<br>
                &nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=1">房间1</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=2">房间2</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=3">房间3</a>&nbsp;&nbsp;&nbsp;&nbsp;<a href="/?room_id=4">房间4</a>
                <br><br>
            </div>
            <p class="cp">PHP多进程+Websocket(HTML5/Flash)+PHP Socket实时推送技术&nbsp;&nbsp;&nbsp;&nbsp;Powered by <a href="http://www.workerman.net/workerman-chat" target="_blank">workerman-chat</a></p>
        </div>
        <div class="col-md-3 column">
            <div class="thumbnail">
                <div class="caption" id="userlist"></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
