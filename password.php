<?php
//20250123 BY MKLIU
// 密码输入页面

date_default_timezone_set("PRC");
error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(30);

$room = $_REQUEST['room'] ?? 'default';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_REQUEST['password'] ?? null;
    if ($password) {
        header('Location: index.php?type=enter&room=' . $room . '&password=' . urlencode($password));
        exit;
    } else {
        echo '请输入密码！';
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="renderer" content="webkit">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>输入密码</title>
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
    margin-top: 50px;
}

/* 主体容器 */
.divMain {
    max-width: 400px;
    margin: 20px auto;
    background: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    line-height: 1.6;
}

/* 输入框样式 */
input[type="password"] {
    width: calc(100% - 20px);
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
</style>
</head>
<body>
    
<h1>请输入聊天室密码</h1>
<div class="divMain">
    <form action="password.php" method="post">
        <input type="hidden" name="room" value="<?=$room?>">
        <label for="password">密码：</label>
        <input type="password" name="password" id="password" maxlength="50" required />
        <button type="submit">进入聊天室</button>
    </form>
</div>

<div align="center">
    Copyright © 2025 Yuer6327
</div>
</body>
</html>
