<?php
// 使用PHP做的单页面在线聊天。
// 20250123 BY MKLIU & Yuer6327
// 基本功能：
// 1. 多人聊天
// 2. 多房间
// 3. 传输信息加密，基于base64+字符替换实现
// 4. 基于长连接读取（ngnix使用PHP sleep有问题）
// 5. 支持昵称自定义，并使用浏览器保存。
// 6. 需要在程序目录创建chat_data文件夹，用来存储历史聊天数据
// 7. 支持新建房间，自动生成密码
// 8. 支持密码保护房间
// 9. 在index.php中设置网站标题和logo

// 20260102 BY Yuer6327
$title = "chatom";
$logoUrl = "https://yuer6327.top/wp-content/uploads/2025/10/cropped-新高中头像.webp";

date_default_timezone_set("PRC");

// 自动创建数据目录 - 20260102 BY Yuer6327
$data_dir = __DIR__ . '/data';
if (!file_exists($data_dir)) {
    mkdir($data_dir, 0777, true);
}

error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(30);

$room = $_REQUEST['room'] ?? 'default';
$type = $_REQUEST['type'] ?? 'enter';
$type = strtolower($type);

// 获取所有聊天室 - 20260102 BY Yuer6327
function getChatrooms()
{
    $data_dir = __DIR__ . '/data';
    if (!file_exists($data_dir))
        return [];
    $files = glob($data_dir . '/*.txt');
    $chatrooms = [];
    if ($files) {
        foreach ($files as $file) {
            $filename = basename($file, '.txt');
            $room_data = json_decode(file_get_contents($file), true);
            $last_time = '';
            if (!empty($room_data['list'])) {
                $last_msg = end($room_data['list']);
                $last_time = $last_msg['time'];
            } else {
                $last_time = $room_data['time'];
            }
            $chatrooms[] = [
                'name' => $filename,
                'display_name' => $room_data['display_name'] ?? $filename,
                'last_time' => $last_time
            ];
        }
    }
    // 按最后活跃时间排序 - 20260102 BY Yuer6327
    usort($chatrooms, function ($a, $b) {
        return strcmp($b['last_time'], $a['last_time']);
    });
    return $chatrooms;
}

// 生成新房间 - 20260102 BY Yuer6327
function newRoom($room, $custompassword = null, $displayName = null, $icon = null)
{
    $room_file = __DIR__ . '/data/' . $room . '.txt';
    $key_list = array_merge(range(48, 57), range(65, 90), range(97, 122), [43, 47, 61]);
    $key1_list = $key_list;
    shuffle($key1_list);

    if ($room !== 'default' && !$custompassword) {
        $custompassword = generateRandomPassword();
    }

    $room_data = [
        'name' => $room,
        'display_name' => $displayName ?? $room,
        'icon' => $icon,
        'encode' => array_combine($key_list, $key1_list),
        'list' => [],
        'time' => date('Y-m-d H:i:s'),
        'password' => $room === 'default' ? null : password_hash($custompassword, PASSWORD_DEFAULT),
    ];
    file_put_contents($room_file, json_encode($room_data), LOCK_EX);
    return $custompassword;
}

// 检测密码是否正确 - 20260102 BY Yuer6327
function checkPassword()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $roominput = $_POST['room'] ?? '';
        $room_file = __DIR__ . '/data/' . $roominput . '.txt';
        if (file_exists($room_file)) {
            $room_data = json_decode(file_get_contents($room_file), true);
            $correctPassword = $room_data['password']; // 获取正确的密码哈希
            if (password_verify($password, $correctPassword)) {
                return true;
            } else {
                echo '<script>
                    alert("密码错误，请重试。");
                    window.location.reload();
                </script>';
                return false;
            }
        } else {
            echo '<script>
                alert("房间不存在，请重试。");
                window.location.reload();
            </script>';
            return false;
        }
    } else {
        echo '<div class="overlay">
    <div class="form-container">
        <form id="passwordForm" method="post" action="">
            <h2>请输入房间号和密码</h2>
            <label for="room">房间号：</label>
            <input type="text" name="room" id="userRoom" required>
            <br>
            <label for="password">密码：</label>
            <input type="password" name="password" id="userPassword" required>
            <br>
            <input type="submit" value="提交">
        </form>
    </div>
</div>
<style>
/* Fluent Design Style - 20260102 BY Yuer6327 */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: center;
    align-items: center;
}
.form-container {
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    width: 90%;
}
h2 {
    text-align: center;
    margin-bottom: 24px;
    color: #202020;
    font-weight: 600;
}
label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #666;
    font-size: 13px;
}
input[type="text"], input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    margin-bottom: 20px;
    border: 1.5px solid #e6e6e6;
    border-radius: 10px;
    font-size: 15px;
    box-sizing: border-box;
    transition: all 0.2s;
}
input:focus {
    border-color: #017E6E;
    outline: none;
    box-shadow: 0 0 0 4px rgba(1, 126, 110, 0.1);
}
input[type="submit"] {
    background-color: #017E6E;
    color: #fff;
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    font-weight: 600;
}
input[type="submit"]:hover {
    background-color: #016a5d;
    box-shadow: 0 4px 12px rgba(1, 126, 110, 0.2);
    transform: translateY(-1px);
}
</style>
';
        exit;
    }
}

// 生成随机密码 - 20260102 BY Yuer6327
function generateRandomPassword()
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $passwordgen = '';
    for ($i = 0; $i < 8; $i++) {
        $passwordgen .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $passwordgen;
}

//20250123 BY MKLIU
// 获取消息列表 - 20260102 BY Yuer6327
function getMsg($room, $last_id)
{
    $room_file = __DIR__ . '/data/' . $room . '.txt';
    $msg_list = [];

    $room_data = json_decode(file_get_contents($room_file), true);
    $list = $room_data['list'];

    // 清除一个月前消息 (2592000秒) - 20260102 BY Yuer6327
    $cur_list = [];
    $del_time = date('Y-m-d H:i:s', time() - 2592000);
    foreach ($list as $r) {
        if ($r['time'] > $del_time) {
            $cur_list[] = $r;
        }
    }

    if (count($cur_list) != count($list) && count($list) > 0) {
        $room_data['list'] = $cur_list;
        file_put_contents($room_file, json_encode($room_data), LOCK_EX);
    }

    // 查找最新消息
    foreach ($list as $r) {
        if ($r['id'] > $last_id) {
            $msg_list[] = $r;
        }
    }

    return $msg_list;
}

$room_file = __DIR__ . '/data/' . $room . '.txt';

switch ($type) {
    case 'enter':   // 进入房间
        $authenticated = false;

        // 如果房间名称为 'default'，直接通过身份验证
        if ($room === 'default') {
            $authenticated = true;
        } else {
            if (checkPassword()) {
                $authenticated = true;
            }
        }

        if ($authenticated) {
            // 密码正确或房间为 'default'，继续执行聊天功能
            break;
        } else {
            // 如果验证失败，直接退出
            exit;
        }
        break;

    // 进入房间，显示聊天窗口
    case 'get':     // 获取消息
        $last_id = $_REQUEST['last_id'];
        $msg_list = [];

        if (strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false) {
            $msg_list = getMsg($room, $last_id);
        } else {
            // nginx 使用sleep将会把整个网站卡死
            for ($i = 0; $i < 20; $i++) {
                $msg_list = getMsg($room, $last_id);

                if (!empty($msg_list)) {
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
        if (!file_exists($room_file)) {
            newRoom($room);
        }
        $room_data = json_decode(file_get_contents($room_file), true);
        $room_data['list'][] = $item;
        file_put_contents($room_file, json_encode($room_data), LOCK_EX);
        echo json_encode(['result' => 'ok']);
        break;
    case 'new':     // 新建房间 - 20260102 BY Yuer6327
        $room_name = $_REQUEST['room_name'] ?? '';
        if ($room_name !== '') {
            $room = $room_name;
        } else {
            mt_srand();
            $room = strtoupper(md5(uniqid(mt_rand(), true)));
            $room = substr($room, 0, 10);
        }
        $passwordinput = $_REQUEST['password'] ?? null;
        $displayName = $room_name ?: $room;
        $email = $_REQUEST['email'] ?? '';
        $icon = '';
        if ($email) {
            $hash = md5(strtolower(trim($email)));
            $icon = "https://gravatar.loli.net/avatar/$hash?s=100&d=identicon";
        }
        $generatedPassword = newRoom($room, $passwordinput, $displayName, $icon);
        echo '<script>alert("房间号是：' . $room . '，房间密码是：' . $generatedPassword . '，请保存好。"); window.location.href="index.php?room=' . urlencode($room) . '";</script>';
        exit;
        break;
    default:
        echo 'ERROR:no type!';
        break;
}

if ($type != 'enter') {
    exit;
}

if (!file_exists($room_file)) {
    if ($room == 'default') {
        newRoom($room);
    } else {
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
<!--html页面-->
<!-- 20260102 BY Yuer6327 -->
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title; ?></title>
    <link rel="icon" href="<?php echo $logoUrl; ?>" type="image/x-icon">
    <link href="https://lib.baomitu.com/normalize/latest/normalize.min.css" rel="stylesheet">
    <style>
        /* Fluent Design Style - 20260102 BY Yuer6327 */
        :root {
            --bg-color: #f9fafb;
            --card-bg: #ffffff;
            --text-color: #202020;
            --border-color: #e6e6e6;
            --hover-bg: #f5f5f5;
            --accent-color: #017E6E;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.05), 0 1px 4px rgba(0, 0, 0, 0.04);
            --radius-large: 12px;
            --radius-medium: 10px;
            --radius-small: 6px;
            --spacing: 20px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        header {
            padding: 40px var(--spacing);
            text-align: center;
            margin-bottom: 10px;
        }

        h1 {
            margin: 0;
            font-weight: 700;
            font-size: 32px;
            letter-spacing: -1px;
            color: var(--text-color);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            width: 92%;
            flex: 1;
        }

        .card {
            background: var(--card-bg);
            border-radius: var(--radius-large);
            padding: 30px;
            box-shadow: var(--shadow);
            margin-bottom: var(--spacing);
            border: 1px solid var(--border-color);
        }

        h2 {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: var(--spacing);
            font-weight: 600;
        }

        /* 输入框样式 */
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            height: 48px;
            /* 统一高度 */
            padding: 12px 16px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-medium);
            font-size: 15px;
            outline: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            margin-bottom: var(--spacing);
            box-sizing: border-box;
            background: #ffffff;
        }

        input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(0, 0, 0, 0.05);
        }

        /* 按钮样式 */
        button {
            background-color: #f1f1f1;
            color: var(--text-color);
            padding: 0 24px;
            height: 48px;
            /* 统一高度 */
            border: none;
            border-radius: var(--radius-medium);
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            /* 确保文字不换行 */
            flex-shrink: 0;
            /* 在flex布局中不被压缩 */
        }

        button:hover {
            background-color: #e5e5e5;
            transform: translateY(-1px);
        }

        button.primary {
            background-color: var(--accent-color);
            color: white;
        }

        button.primary:hover {
            opacity: 0.9;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* 房间列表 */
        #chatroomList {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 10px;
        }

        .room-chip {
            padding: 8px 18px;
            background: #f0f0f0;
            border-radius: 20px;
            font-size: 14px;
            color: var(--text-color);
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }

        .room-chip:hover {
            background: var(--accent-color);
            color: white;
            border-radius: var(--radius-medium);
        }

        .room-time {
            font-size: 11px;
            opacity: 0.7;
            margin-left: 8px;
            font-weight: normal;
        }

        /* 聊天部分 */
        #divList {
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            background: #fcfcfc;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-large);
            margin-top: var(--spacing);
            display: flex;
            flex-direction: column;
            /* 发送第一条消息应该从上往下排 */
        }

        .msg-item {
            margin-bottom: 16px;
            padding: 14px 18px;
            background: white;
            border-radius: var(--radius-medium);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
            border: 1px solid #f0f0f0;
            max-width: 85%;
            align-self: flex-start;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .msg-header {
            display: flex;
            align-items: center;
            font-size: 13px;
            color: #888;
            margin-bottom: 6px;
        }

        .msg-user {
            font-weight: 700;
            color: var(--accent-color);
            margin-right: 10px;
        }

        .msg-content {
            font-size: 15px;
            line-height: 1.5;
            color: #333;
            word-break: break-all;
        }

        /* 页脚 */
        footer {
            padding: 40px 24px;
            text-align: center;
            font-size: 14px;
            color: #666;
            margin-top: 40px;
        }

        footer a {
            color: var(--accent-color);
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* 布局控制 */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing);
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            margin-bottom: 8px;
            color: #666;
            font-weight: 500;
        }

        .input-flex {
            display: flex;
            gap: 12px;
            margin-bottom: 0;
            align-items: center;
        }

        .input-flex input {
            flex: 1;
            /* 输入框占据剩余空间 */
            margin-bottom: 0 !important;
        }

        @media (max-width: 600px) {
            .card {
                padding: 20px;
            }

            .msg-item {
                max-width: 95%;
            }
        }
    </style>

    <script src="https://lib.baomitu.com/jquery/3.4.1/jquery.min.js"></script>
</head>

<body>

    <header>
        <h1>
            <?php echo $title; ?>
        </h1>
    </header>

    <div class="container">
        <div class="card">
            <h2>在线房间</h2>
            <div id="chatroomList"></div>
        </div>

        <div class="card">
            <h2>创建新房间</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>聊天室名称 (选填)</label>
                    <input type="text" id="txtNewRoomName" placeholder="例如：摸鱼">
                </div>
                <div class="form-group">
                    <label>访问密码 (选填，不填自动生成)</label>
                    <input type="password" id="txtNewPassword" placeholder="房间密码">
                </div>
                <div class="form-group">
                    <label>头像邮箱 (选填，用于Gravatar)</label>
                    <input type="email" id="txtNewEmail" placeholder="you@email.com">
                </div>
            </div>
            <button class="primary" onclick="createRoom();">创建房间</button>
        </div>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>正在房间：
                    <?= htmlspecialchars($room_data['display_name'] ?? $room_data['name']) ?>
                </h2>
                <button onclick=" $('#divList').html('');">清空消息</button>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>我的昵称</label>
                    <input id="txtUser" type="text" maxlength="50" value="<?= $user ?>" />
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label>发送内容 (Enter 发送)</label>
                    <div class="input-flex">
                        <input id="txtContent" type="text" value="" maxlength="200" style="margin-bottom: 0;" />
                        <button class="primary" onclick="sendMsg();">发送</button>
                    </div>
                </div>
            </div>

            <div id="divList"></div>
        </div>
    </div>

    <footer>
        Copyright © 2026 By <a href="https://www.mkliu.top/"><strong>michaelliunsky</strong></a> & <a
            href="https://yuer6327.top/"><strong>Yuer6327</strong></a>
    </footer>

    <!-- 20260102 BY Yuer6327 -->
    <script id="worker" type="app/worker">
    var room = '<?= $room_data['name'] ?>';
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
            for (let k in res.list) {
                let r = res.list[k];
                html += '<div class="msg-item">' +
                    '<div class="msg-header"><span class="msg-user">' + r.user + '</span><span>' + r.time + '</span></div>' +
                    '<div class="msg-content">' + decodeContent(r.content) + '</div>' +
                    '</div>';
            }

            if (html) {
                $('#divList').append(html);
                $('#divList').scrollTop($('#divList')[0].scrollHeight);
            }
        };

        worker.postMessage(document.baseURI);
    </script>

    <script>
        var room = <?= json_encode($room_data) ?>;
        room['decode'] = {};
        for (let k in room.encode) {
            room['decode'][room.encode[k]] = k;
        }

        // 发送消息 - 20260102 BY Yuer6327
        var lastSendTime = 0;
        function sendMsg() {
            let user = $('#txtUser').val().trim();
            let content = $('#txtContent').val().trim();

            if (content == '') return;
            if (user == '') {
                alert('昵称不能为空');
                return;
            }

            window.localStorage.setItem('chat_nick', user);

            let curTime = new Date().getTime();
            if (curTime - lastSendTime < 300) return;
            lastSendTime = curTime;

            $.ajax({
                url: 'index.php?type=send',
                data: { room: room.name, user: user, content: encodeContent(content) },
                type: 'POST',
                dataType: 'json',
                success: function () {
                    $('#txtContent').val('');
                    $('#txtContent').focus();
                },
            });
        }

        // 消息加密 - 20260102 BY Yuer6327
        function encodeContent(content) {
            content = encodeURIComponent(content);
            content = window.btoa(content);
            let str = '';
            for (let i = 0; i < content.length; i++) {
                str += String.fromCharCode(room.encode[content.charCodeAt(i)]);
            }
            return str;
        }

        // 消息解密 - 20260102 BY Yuer6327
        function decodeContent(content) {
            let str = '';
            for (let i = 0; i < content.length; i++) {
                str += String.fromCharCode(room.decode[content.charCodeAt(i)]);
            }
            str = window.atob(str);
            str = decodeURIComponent(str);
            return str;
        }

        $(function () {
            let userName = window.localStorage.getItem('chat_nick');
            if (userName) {
                $('#txtUser').val(userName);
            }

            $('#txtContent').keydown(function (e) {
                if (e.keyCode == 13) {
                    e.preventDefault();
                    sendMsg();
                }
            });

            // 默认进入房间可以不用自动发消息
        });

        function createRoom() {
            let password = $('#txtNewPassword').val();
            let roomName = $('#txtNewRoomName').val();
            let email = $('#txtNewEmail').val();
            let url = 'index.php?type=new&password=' + encodeURIComponent(password) +
                '&room_name=' + encodeURIComponent(roomName) +
                '&email=' + encodeURIComponent(email);
            window.location.href = url;
        }

        // 呈现在线聊天室 - 20260102 BY Yuer6327
        var chatrooms = <?= json_encode($chatrooms) ?>;
        var chatroomList = $('#chatroomList');
        chatrooms.forEach(function (r) {
            let link = $('<a class="room-chip"></a>').attr('href', 'index.php?room=' + encodeURIComponent(r.name));
            link.html(r.display_name + '<span class="room-time">' + r.last_time.substring(11, 16) + '</span>');
            chatroomList.append(link);
        });
    </script>
</body>

</html>