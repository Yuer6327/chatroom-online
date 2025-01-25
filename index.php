<?php
//使用PHP做的单页面在线聊天。
//20250123 BY MKLIU
//基本功能：
//1. 多人聊天
//2. 多房间
//3. 传输信息加密，基于base64+字符替换实现
//4. 基于长连接读取（ngnix使用PHP sleep有问题）
//5. 支持昵称自定义，并使用浏览器保存。
//6. 需要在程序目录创建chat_data文件夹，用来存储历史聊天数据

//20250123 BY MKLIU
// 系统入口
date_default_timezone_set("PRC");
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(30);

$room = $_REQUEST['room'] ?? 'default';
$type = $_REQUEST['type'] ?? 'enter';
$type = strtolower($type);

//20250123 BY MKLIU
// 获取所有聊天室
function getChatrooms() {
    $files = glob('./chat_data/*.txt');
    $chatrooms = [];
    foreach ($files as $file) {
        $filename = basename($file, '.txt');
        $chatrooms[] = $filename;
    }
    return $chatrooms;
}

//20250123 BY MKLIU
// 创建新房间
function newRoom($room, $password = null)
{
    $room_file = './chat_data/' . $room . '.txt';
    $key_list = array_merge(range(48, 57), range(65, 90), range(97, 122), [43, 47, 61]);
    $key1_list = $key_list;
    shuffle($key1_list);

    if (!$password) {
        $password = generateRandomPassword();
    }

    $room_data = [
        'name'   => $room,
        'encode' => array_combine($key_list, $key1_list),
        'list'   => [],
        'time'   => date('Y-m-d H:i:s'),
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ];
    file_put_contents($room_file, json_encode($room_data));
}

function generateRandomPassword() {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $password = '';
    for ($i = 0; $i < 8; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

//20250123 BY MKLIU
// 获取消息列表
function getMsg($room, $last_id)
{
    $room_file = './chat_data/' . $room . '.txt';
    $msg_list = [];

    $room_data = json_decode(file_get_contents($room_file), true);
    $list = $room_data['list'];

    // 清除一周前消息
    $cur_list = [];
    $del_time = date('Y-m-d H:i:s', time() - 604800);
    foreach ($list as $r)
    {
        if ($r['time'] > $del_time)
        {
            $cur_list[] = $r;
        }
    }

    if (count($cur_list) != count($list) && count($list) > 0)
    {
        $room_data['list'] = $cur_list;
        file_put_contents($room_file, json_encode($room_data));
    }

    // 查找最新消息
    foreach ($list as $r)
    {
        if ($r['id'] > $last_id)
        {
            $msg_list[] = $r;
        }
    }

    return $msg_list;
}

$room_file = './chat_data/' . $room . '.txt';

switch ($type)
{
    case 'enter':   // 进入房间
        $room_data = json_decode(file_get_contents($room_file), true);
        if ($room_data['password']) {
            $password = $_REQUEST['password'] ?? null;
            if (!$password || !password_verify($password, $room_data['password'])) {
                echo 'ERROR: Invalid password!';
                exit;
            }
        }
        break;
    case 'get':     // 获取消息
        $last_id = $_REQUEST['last_id'];
        $msg_list = [];

        if (strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false)
        {
            $msg_list = getMsg($room, $last_id);
        }
        else
        {
            // nginx 使用sleep将会把整个网站卡死
            for ($i=0; $i<20; $i++)
            {
                $msg_list = getMsg($room, $last_id);
                
                if (!empty($msg_list))
                {
                    break;
                }
    
                usleep(500000);
            }
        }

        echo json_encode(['result' => 'ok', 'list' => $msg_list]);

        break;
    case 'send':    // 发送消息
        $item = [
            'id' => round(microtime(true) * 1000),
            'user' => $_REQUEST['user'],
            'content' => $_REQUEST['content'],
            'time' => date('Y-m-d H:i:s'),
        ];
        $room_data = json_decode(file_get_contents($room_file), true);
        $room_data['list'][] = $item;
        file_put_contents($room_file, json_encode($room_data));
        echo json_encode(['result' => 'ok']);
        break;
    case 'new':     // 新建房间
        mt_srand();
        $room = strtoupper(md5(uniqid(mt_rand(), true)));
        $room = substr($room, 0, 10);
        $password = $_REQUEST['password'] ?? null;
        newRoom($room, $password);
        header('Location:index.php?room=' . $room . "&password=" . $password);
        break;
    default:
        echo 'ERROR:no type!';
        break;
}

if ($type != 'enter')
{
    exit;
}

if (!file_exists($room_file))
{
    if ($room == 'default')
    {
        newRoom($room);
    }
    else
    {
        echo 'ERROR:room not exists!';
        exit;
    }
}

$room_data = json_decode(file_get_contents($room_file), true);
unset($room_data['list']);

$user = 'User' . str_pad((time() % 99 + 1), 2, '0', STR_PAD_LEFT);

$chatrooms = getChatrooms(); // 获取所有聊天室

?>

<!--html页面-->
<!--20250123 BY MKLIU-->
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="renderer" content="webkit">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Yuer6327的聊天室</title>
<link rel="icon" href="https://yuer6327.42web.io/wp-content/uploads/2025/01/高中头像.png" type="image/png">
<link href="https://lib.baomitu.com/normalize/latest/normalize.min.css" rel="stylesheet">
<style>
/* css style */
body{
    padding:0 10px;
}
.divMain{
    font-size:14px;
    line-height: 2;
}

#divList span{
    color:gray;
}
body {
    margin: 0;
    padding: 0;
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f9;
    color: #333;
}

/* 主标题样式 */
h1 {
    text-align: center;
    font-size: 2.5em;
    color: #444;
    margin-top: 0px;
}

/* 在线聊天室列表 */
#chatroomList {
    max-width: 800px;
    margin: 20px auto;
    background: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    line-height: 1.6;
}

/* 主体容器 */
.divMain {
    max-width: 800px;
    margin: 20px auto;
    background: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    line-height: 1.6;
}

/* 输入框样式 */
input[type="text"], input[type="password"] {
    width: calc(100% - 120px);
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    box-sizing: border-box;
}

/* 按钮样式 */
button {
    background-color: #007BFF;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

/* 链接样式 */
a {
    color: #007BFF;
    text-decoration: none;
    font-size: 14px;
    margin-left: 10px;
}

a:hover {
    text-decoration: underline;
}

/* 消息列表 */
#divList {
    margin-top: 20px;
    padding: 10px;
    border-top: 1px solid #ddd;
    background-color: rgba(249, 249, 249, 0.8); /* 浅灰色半透明背景 */
}

#divList div {
    margin-bottom: 10px;
    padding: 8px;
    background: #ffffff; /* 消息背景为白色 */
    border: 1px solid #e3e3e3;
    border-radius: 4px;
}

#divList span {
    color: #888;
    font-size: 12px;
    margin-right: 10px;
}

/* 消息用户名加粗 */
#divList b {
    font-weight: bold;
    color: #333;
}

/* 响应式支持 */
@media (max-width: 600px) {
    .divMain {
        padding: 15px;
    }

    input[type="text"] {
        width: calc(100% - 90px);
    }

    button {
        padding: 8px 15px;
        font-size: 14px;
    }
}
</style>

<script src="https://lib.baomitu.com/jquery/3.4.1/jquery.min.js"></script>
</head>
<body>
    
<h1>Yuer6327的聊天室</h1>
<h2 align="center">在线房间</h2>
<div id="chatroomList"></div>
<div class="divMain">
昵称：<input id="txtUser" type="text" maxlength="50" value="<?=$user?>" />
<button onclick="$('#divList').html('');">清空</button>
<br>
内容：<input id="txtContent" type="text" value="" maxlength="100" style="width: 300px;" />
<button onclick="sendMsg();">发送</button>
<br>
<label for="password">密码：</label>
<input type="password" id="txtPassword" maxlength="50" />
<button onclick="createRoom();">新房间</button>
<label for="generatePassword">
    <input type="checkbox" id="generatePassword" />
    自动生成随机密码
</label>

<hr>
<div id="divList"></div>
</div>
<!--20250123 BY MKLIU-->
<!--使用worker获取消息数据，注意ngnix会阻塞整个进程-->
<script id="worker" type="app/worker">
    var room = '<?=$room_data['name']?>';
    var isBusy = false;
    var lastId = -1;

    var urlBase = '';
    addEventListener('message', function (evt) {
        urlBase = evt.data;
    }, false);
    setInterval(function(){
        if (isBusy) return;
        isBusy = true;

        let url = new URL( 'index.php?type=get&room=' + room + '&last_id=' + lastId, urlBase );
        fetch(url)
        .then(res=>res.json())
        .then(function(res){
            isBusy = false;
            if (res.list.length > 0)
            {
                lastId = res.list[res.list.length-1].id;
            }
            self.postMessage(res);
        })
        .catch(function(err){
            isBusy = false;
        });
    }, 1000);
</script>
<script>
    var blob = new Blob([document.querySelector('#worker').textContent]);
    var url = window.URL.createObjectURL(blob);
    var worker = new Worker(url);

    worker.onmessage = function (e) {
        let res = e.data;
        let html = '';
        for (let k in res.list)
        {
            let r = res.list[k];
            html = '<div><span>' + r.time + '</span> <b>' + r.user + ':</b>   ' + decodeContent(r.content) + '</div>' + html;
        }

        $('#divList').prepend(html);
    };

    worker.postMessage(document.baseURI);
</script>

<script>
var room = <?=json_encode($room_data)?>;
room['decode'] = {};
for (let k in room.encode)
{
    room['decode'][room.encode[k]] = k;
}

//20250123 BY MKLIU
// 发送消息
var lastSendTime = 0;
function sendMsg()
{
    let user = $('#txtUser').val().trim();
    let content = $('#txtContent').val().trim();

    if (content == '')
    {
        return;
    }

    if (user == '')
    {
        alert('昵称不能为空');
        return;
    }

    window.localStorage.setItem('r_' + room.name, user);
    
    // 限制0.3秒内仅允许发送1条消息
    let curTime = new Date().getTime();
    if (curTime - lastSendTime < 300)
    {
        return;
    }
    lastSendTime = curTime;

    $.ajax({
        url:'index.php?type=send',
        data:{room:room.name, user:user, content:encodeContent(content)},
        type:'POST',
        dataType:'json',
        success:function(){
            $('#txtContent').val('');
            $('#txtContent').focus();
        },
    });
}

//20250123 BY MKLIU
// 消息加密
function encodeContent(content)
{
    content = encodeURIComponent(content);
    content = window.btoa(content);

    let str = '';
    for (let i=0; i<content.length; i++)
    {
        str += String.fromCharCode(room.encode[content.charCodeAt(i)]);
    }

    return str;
}

//20250123 BY MKLIU
// 消息解密
function decodeContent(content)
{
    let str = '';
    for (let i=0; i<content.length; i++)
    {
        str += String.fromCharCode(room.decode[content.charCodeAt(i)]);
    }

    str = window.atob(str);
    str = decodeURIComponent(str);

    return str;
}

$(function(){
    let userName = window.localStorage.getItem('r_' + room.name);
    if (userName)
    {
        $('#txtUser').val(userName);
    }

    $('#txtContent').keydown(function(e){
        if(e.keyCode==13){
            event.preventDefault();
            sendMsg();
        }
    });

    $('#txtContent').val('🥳 我来了!');
    sendMsg();
});

function createRoom() {
    let password = document.getElementById('txtPassword').value;
    let generatePassword = document.getElementById('generatePassword').checked;

    if (generatePassword) {
        password = generateRandomPassword();
    }

    window.location.href = 'index.php?type=new&password=' + encodeURIComponent(password);
}

function generateRandomPassword() {
    let chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let password = '';
    for (let i = 0; i < 8; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return password;
}

var chatrooms = <?= json_encode($chatrooms) ?>;
var chatroomList = document.getElementById('chatroomList');
chatrooms.forEach(function(room) {
    var roomLink = document.createElement('a');
    roomLink.href = 'index.php?room=' + room;
    roomLink.textContent = room;
    chatroomList.appendChild(roomLink);
    chatroomList.appendChild(document.createElement('br'));
});
</script>
<div  align="center">
    Copyright © 2025 Yuer6327
</div>
</body>
</html>