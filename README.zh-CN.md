# Workerman SOCKS4 Proxy

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/workerman-socks4-proxy/
tests.yml?branch=master&style=flat-square)](https://github.com/tourze/workerman-socks4-proxy/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://scrutinizer-ci.com/g/tourze/workerman-socks4-proxy)
[![代码覆盖率](https://img.shields.io/badge/coverage-85%25-green.svg?style=flat-square)]()
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/workerman-socks4-proxy?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)
[![License](https://img.shields.io/packagist/l/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)

基于 Workerman 实现的高性能 SOCKS4/SOCKS4a 代理服务器，支持 PHP 8.1+。具备完整协议支持、可选用户认证、异步 I/O 和全面日志记录功能。

## 目录

- [特性](#特性)
- [安装](#安装)
- [系统要求](#系统要求)
- [快速开始](#快速开始)
  - [基本代理服务器](#基本代理服务器)
  - [带身份验证的代理服务器](#带身份验证的代理服务器)
- [协议支持](#协议支持)
  - [SOCKS4 协议](#socks4-协议)
  - [SOCKS4a 扩展](#socks4a-扩展)
- [API 参考](#api-参考)
- [配置](#配置)
- [测试](#测试)
- [示例](#示例)
- [高级用法](#高级用法)
- [日志记录](#日志记录)
- [安全考虑](#安全考虑)
- [许可证](#许可证)

## 特性

- **完整协议支持**: 完整的 SOCKS4 和 SOCKS4a 协议实现
- **域名解析**: SOCKS4a 扩展支持域名解析
- **用户身份验证**: 可选的用户 ID 验证以进行访问控制
- **高性能**: 基于 Workerman 的异步实现
- **PSR-3 日志**: 全面的日志支持，使用 PSR-3 日志接口
- **连接管理**: 高效的连接池和管理
- **简单 API**: 易于使用的配置和设置

## 安装

通过 Composer 安装:

```bash
composer require tourze/workerman-socks4-proxy
```

## 系统要求

- PHP 8.1 或更高版本
- Workerman 5.1 或更高版本
- PSR-3 日志实现
- ext-sockets 扩展（推荐，以获得最佳性能）

## 快速开始

### 基本代理服务器

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Tourze\Workerman\PsrLogger\WorkermanLogger;
use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Worker;

// 创建 PSR Logger 实例
$logger = new WorkermanLogger();

// 创建 SOCKS4 代理服务器
$proxy = new SOCKS4Worker($logger, 'tcp://0.0.0.0:1080');

// 设置进程数
$proxy->count = 4;

// 禁用身份验证（基本用法）
$proxy->setEnableAuthentication(false);

// 启动服务器
Worker::runAll();
```

### 带身份验证的代理服务器

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Tourze\Workerman\PsrLogger\WorkermanLogger;
use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Worker;

// 创建 PSR Logger 实例
$logger = new WorkermanLogger();

// 创建 SOCKS4 代理服务器
$proxy = new SOCKS4Worker($logger, 'tcp://0.0.0.0:1080');

// 设置进程数
$proxy->count = 4;

// 启用用户身份验证
$proxy->setEnableAuthentication(true);
$proxy->addValidUser('用户名1');
$proxy->addValidUser('用户名2');

// 启动服务器
Worker::runAll();
```

## 协议支持

### SOCKS4 协议

标准 SOCKS4 协议用于 TCP 连接：
- 直接连接到 IP 地址
- 用户 ID 身份验证
- 连接命令支持

### SOCKS4a 扩展

增强的 SOCKS4a 协议特性：
- 域名解析
- 支持主机名而非 IP 地址
- 向后兼容 SOCKS4

## API 参考

### SOCKS4Worker 类

#### 构造函数

```php
public function __construct(LoggerInterface $logger, string $socketName = 'tcp://0.0.0.0:1080')
```

- `$logger`: PSR-3 日志实例用于日志记录
- `$socketName`: 监听的套接字地址（默认：tcp://0.0.0.0:1080）

#### 方法

```php
// 身份验证管理
public function setEnableAuthentication(bool $enable): void
public function addValidUser(string $userId): void
public function isAuthenticationEnabled(): bool
```

## 测试

使用 curl 测试代理服务器:

```bash
# 测试 HTTP 连接
curl -x socks4://127.0.0.1:1080 http://www.example.com

# 测试 HTTPS 连接
curl -x socks4://127.0.0.1:1080 https://www.example.com

# 使用身份验证测试（用户名：testuser）
curl -x socks4://testuser@127.0.0.1:1080 http://www.example.com
```

## 示例

详细示例请查看 [examples](./examples) 目录:

- `basic_proxy_server.php` - 基本代理服务器，无身份验证

运行示例：

```bash
php examples/basic_proxy_server.php start
```

## 配置

### 服务器配置

```php
// 设置工作进程数
$proxy->count = 4;

// 设置服务器名称
$proxy->name = 'MySOCKS4Proxy';

// 配置 Workerman 设置
Worker::$logFile = '/tmp/socks4_proxy.log';
Worker::$stdoutFile = '/tmp/socks4_stdout.log';
```

### 身份验证配置

```php
// 启用身份验证
$proxy->setEnableAuthentication(true);

// 添加多个有效用户
$validUsers = ['用户1', '用户2', '用户3'];
foreach ($validUsers as $user) {
    $proxy->addValidUser($user);
}
```

## 高级用法

### 自定义日志实现

你可以实现自己的 PSR-3 日志器来实现自定义日志行为：

```php
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class CustomLogger implements LoggerInterface
{
    public function emergency($message, array $context = []): void 
    {
        // 自定义紧急日志
    }
    
    public function alert($message, array $context = []): void 
    {
        // 自定义警报日志
    }
    
    // ... 实现其他 PSR-3 方法
}

$customLogger = new CustomLogger();
$proxy = new SOCKS4Worker($customLogger, 'tcp://0.0.0.0:1080');
```

### 连接事件处理

监控连接事件以进行自定义处理：

```php
$proxy = new SOCKS4Worker($logger, 'tcp://0.0.0.0:1080');

// 处理新连接
$proxy->onConnect = function($connection) {
    echo "来自 " . $connection->getRemoteIp() . " 的新连接\n";
};

// 处理连接关闭
$proxy->onClose = function($connection) {
    echo "连接已关闭\n";
};
```

### 性能调优

针对高负载场景优化服务器性能：

```php
// 增加工作进程数
$proxy->count = 8;

// 设置套接字缓冲区大小
$proxy->reusePort = true;

// 配置 Workerman 设置
Worker::$eventLoopClass = '\\Workerman\\Events\\Event';
Worker::$maxPackageSize = 10 * 1024 * 1024; // 10MB
```

## 日志记录

此包使用 PSR-3 日志记录进行全面的调试和监控：

```php
use Tourze\Workerman\PsrLogger\WorkermanLogger;

$logger = new WorkermanLogger();
// 日志记录器将捕获：
// - 连接尝试
// - 身份验证状态
// - 协议解析
// - 错误条件
// - 性能指标
```

## 安全考虑

- **身份验证**: 在生产环境中启用用户身份验证
- **IP 过滤**: 考虑实现基于 IP 的访问控制
- **日志记录**: 监控访问日志以发现可疑活动
- **资源限制**: 配置适当的进程限制
- **网络安全**: 使用防火墙规则限制代理访问
- **用户管理**: 定期审查和更新有效用户列表

## 许可证

MIT 许可证。详情请参阅 [许可证文件](LICENSE)。 