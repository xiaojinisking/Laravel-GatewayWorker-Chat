
## Laravel + workerman-GatewayWorker 聊天室

Laravel 版本5.4 GatewayWorker 版本3.x

[GatewayWorker](https://github.com/walkor/gatewayworker)是基于Workerman开发的项目框架，用于快速开发长连接应用，例如app推送服务、即时IM服务端、游戏服务端、物联网、智能家居等等。

GatewayWorker使用经典的Gateway和Worker进程模型。Gateway进程负责维持客户端连接，并转发客户端的数据给Worker进程 处理；BusinessWorker进程负责实际业务逻辑，并将结果推送给对应的客户端。Gateway服务和BusinessWorker服务可以分开部署在不同的服务器上，实现分布式集群，因为他们之间连接信息获取是通过一个registWorker服务来完成的。
Gateway进程将自己的连接信息告诉registWorker,BusinessWorker向registworker注册时就可以拿到Gateway进程信息啦，从而两者建立了联系。

GatewayWorker提供非常方便的API，可以全局广播数据、可以向某个群体广播数据、也可以向某个特定客户端推送数据。配合Workerman的定时器，也可以定时推送数据。

这里主要是整合laravel和GatewayWorker,用的是GatewayWorker的API的调用。[文档说明](http://workerman.net/gatewaydoc/work-with-other-frameworks/README.html).这里是改自[workerman-chat](https://github.com/walkor/workerman-chat)整合到laravel中。

## run

git clone https://github.com/xiaojinisking/Laravel-GatewayWorker-Chat.git

- 启动GatewayWorker

```php
cd GatewayWorker

php start.php start -d
```

- 开启laravel

```php
cd ../chat
composer install
php artisan serve
```

- 浏览器访问http://127.0.0.1:8000  即可加入聊天室

项目主要将business的 业务处理通过页面的异步请求由原来的socket连接Gateway转为ajax向laravel控制器请求，从而laravel完成了businesswork的业务处理。businesswoker只完成客户端连接过来返回client_id。

[在其他非workerman中推送消息还可以参考](http://workerman.net/gatewaydoc/advanced/push.html)
