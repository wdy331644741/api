git仓库
```
git@192.168.20.240:omg/api.git
```

在项目根路径安装php包管理器composer

```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('SHA384', 'composer-setup.php') === 'bf16ac69bd8b807bc6e4499b28968ee87456e29a3894767b60c2d4dafa3d10d045ffef2aeb2e78827fa5f024fbe93ca2') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

安装依赖(项目root路径)
```
php composer.phar  install --no-dev
```

设置项目环境变量(项目root路径)配置.env内mysql redis 配置
```
cp .env.example  .env
```

迁移数据库

```
php artisan migrate
```
安装进程监控supervisor

ubuntu
```
apt-get install supervisor
```
centos 
```
yum install supervisor
```

启动supervisor server

```
/etc/init.d/supervisor start
```

拷贝监控配置到supervisor

```
cp [project_path]/bin/supervisor_queue.conf /etc/supervisor/conf.d/.
```

设置监控配置路径

```
vim /etc/supervisor/conf.d/supervisor_queue.conf
```
change [project_path] to real path

连接到supervisor
```
supervisorctl 
>help  //查看帮助
>reread  //从新加载配置
>start omgquque  //启动任务
>status  //查看状态
```
