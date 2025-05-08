# Workerman SOCKS4 Proxy

[English](README.md) | [中文](README.zh-CN.md)

A SOCKS4/SOCKS4a proxy server implementation based on Workerman.

## Features

- Full support for SOCKS4 and SOCKS4a protocols
- User authentication support
- Configurable port and IP access restrictions
- High-performance asynchronous implementation
- Simple and easy-to-use API

## Installation

Install via Composer:

```bash
composer require tourze/workerman-socks4-proxy
```

## Basic Usage

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Worker;

// Create SOCKS4 proxy server
$proxy = new SOCKS4Worker('tcp://0.0.0.0:1080');

// Set process count
$proxy->count = 4;

// Enable user authentication (optional)
$proxy->setEnableAuthentication(true);
$proxy->addValidUser('testuser');

// Start the server
Worker::runAll();
```

## Examples

See the [examples](./examples) directory for detailed examples:

- `basic_proxy_server.php` - Basic proxy server
- `auth_proxy_server.php` - Proxy server with authentication
- `multi_port_proxy_server.php` - Multi-port proxy server
- `test_proxy.php` - Test client

## Testing

Test the proxy server with curl:

```bash
curl -x socks4://127.0.0.1:1080 http://www.baidu.com
```

Or use the provided test client:

```bash
php examples/test_proxy.php basic
```

## License

MIT License
