# Workerman SOCKS4 Proxy

[English](README.md) | [中文](README.zh-CN.md)

基于 Workerman 实现的 SOCKS4/SOCKS4a 代理服务器。

## 特性

- 完整支持 SOCKS4 和 SOCKS4a 协议
- 支持用户身份验证
- 可配置端口和 IP 访问限制
- 高性能异步实现
- 简单易用的 API

## 安装

通过 Composer 安装:

```bash
composer require tourze/workerman-socks4-proxy
```

## 基本用法

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Worker;

// 创建SOCKS4代理服务器
$proxy = new SOCKS4Worker('tcp://0.0.0.0:1080');

// 设置进程数
$proxy->count = 4;

// 启用用户身份验证(可选)
$proxy->setEnableAuthentication(true);
$proxy->addValidUser('testuser');

// 启动服务
Worker::runAll();
```

## 示例

详细示例请查看 [examples](./examples) 目录:

- `basic_proxy_server.php` - 基本代理服务器
- `auth_proxy_server.php` - 带身份验证的代理服务器
- `multi_port_proxy_server.php` - 多端口代理服务器
- `test_proxy.php` - 测试客户端

## 测试

使用 curl 测试代理服务器:

```bash
curl -x socks4://127.0.0.1:1080 http://www.baidu.com
```

或使用提供的测试客户端:

```bash
php examples/test_proxy.php basic
```

## 许可证

MIT 许可证 