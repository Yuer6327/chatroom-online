# 使用PHP做的单页面在线聊天。
# 20250123 BY MKLIU/MODIFIED BY YUER6327
# 基本功能：
1. 多人聊天
2. 多房间
3. 传输信息加密，基于base64+字符替换实现
4. 基于长连接读取（ngnix使用PHP sleep有问题）
5. 支持昵称自定义，并使用浏览器保存。
6. 需要在程序目录创建chat_data文件夹，用来存储历史聊天数据

！！！IMPORTANT：首次使用请手动删除default.txt中的密码！！！