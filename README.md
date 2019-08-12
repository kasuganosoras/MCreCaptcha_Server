# MCreCaptcha_Server
Minecraft reCaptcha v2 插件，重置版，防止假人压测，本仓库为服务端

## 运行方法
首先需要安装好 PHP 7.X + Swoole，如果没有安装可以用下面这个命令快捷安装
```bash
# 安装 PHP 7.3.8
curl https://tql.ink/php.sh | bash -
# 安装 Swoole 4.4.3
curl https://tql.ink/swoole.sh | bash -
```
然后直接用 PHP 运行即可：
```
php server.php
```
推荐使用 Screen 守护进程：
```
screen -S recaptcha
php server.php
# 按下 Ctrl + A + D
```

## 开源协议
本项目使用 GPL v3 协议开源
