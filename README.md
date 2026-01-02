# PHP单页面在线聊天😋
# BY [Yuer6327](https://yuer6327.top)  
# Also see [Chatom By Mike Liu](https://github.com/michaelliunsky/Chatom)
# 基本功能：
1. 多人聊天
2. 多房间
3. 传输信息加密，基于base64+字符替换实现
4. 基于长连接读取
5. 支持昵称自定义，并使用浏览器保存。
6. 自动在程序目录创建data文件夹，存储聊天数据
7. 支持新建房间，支持自定义房间名、密码、Gravatar头像

# quick start✨
1. 环境要求：PHP 7.0+
2. 部署：将index.php文件上传至 Web 目录。
3. 权限：确保程序有权创建并修改 `data` 目录。
4. 配置：手动修改 `index.php` 顶部的以下变量：
   - `$title`: 网站标题
   - `$logoUrl`: 网站 Logo 链接
5. 建议定期清理 `data` 中的旧记录（程序内置自动清理一月前消息的功能）。  

## Demo: https://yuer6327.top/chat/index.php
