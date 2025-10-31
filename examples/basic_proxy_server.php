<?php

/**
 * 基本SOCKS4代理服务器示例
 *
 * 启动命令:
 * php basic_proxy_server.php start
 *
 * 测试命令:
 * # 使用curl通过SOCKS4代理访问目标网站
 * curl -x socks4://127.0.0.1:31080 http://www.baidu.com
 *
 * # 通过SOCKS4代理访问HTTPS网站
 * curl -x socks4://127.0.0.1:31080 https://www.baidu.com
 *
 * # 设置超时时间
 * curl -x socks4://127.0.0.1:31080 --connect-timeout 10 http://www.baidu.com
 */

/*
 * 注意：WorkermanLogger 只能在 Workerman 环境中使用
 * 只有在 Worker::runAll() 执行后才会记录日志
 */

// 如果指定的路径不存在，则尝试使用项目根目录下的 autoload.php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../../../vendor/autoload.php';
}

use Tourze\Workerman\PsrLogger\WorkermanLogger;
use Tourze\Workerman\SOCKS4\Container;
use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Worker;

// 创建PSR Logger实例
$logger = new WorkermanLogger();
Container::setLogger($logger);

// 设置运行参数
Worker::$stdoutFile = '/tmp/socks4_proxy.log'; // 标准输出日志
Worker::$logFile = '/tmp/socks4_proxy_workerman.log'; // Workerman日志

// 创建SOCKS4代理服务器，监听31080端口
$proxyWorker = new SOCKS4Worker($logger, 'tcp://0.0.0.0:31080');
// 设置进程数量（可根据需要调整）
$proxyWorker->count = 1;
// 禁用身份验证（这是一个简单示例）
$proxyWorker->setEnableAuthentication(false);

// 输出启动信息
echo "SOCKS4代理服务器正在启动...\n";
echo "监听地址: tcp://0.0.0.0:31080\n";
echo "使用测试命令: curl -x socks4://127.0.0.1:31080 http://www.baidu.com\n\n";

// 记录初始化日志（这里不会立即记录，直到 Worker::runAll() 执行后）
$logger->info('SOCKS4代理服务器初始化完成');

// 启动worker
Worker::runAll();
