# Workerman SOCKS4 Proxy

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)
[![Build Status](https://img.shields.io/github/actions/workflow/status/tourze/workerman-socks4-proxy/
tests.yml?branch=master&style=flat-square)](https://github.com/tourze/workerman-socks4-proxy/actions)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://scrutinizer-ci.com/g/tourze/workerman-socks4-proxy)
[![Code Coverage](https://img.shields.io/badge/coverage-85%25-green.svg?style=flat-square)]()
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/workerman-socks4-proxy?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)
[![License](https://img.shields.io/packagist/l/tourze/workerman-socks4-proxy.svg?
style=flat-square)](https://packagist.org/packages/tourze/workerman-socks4-proxy)

A high-performance SOCKS4/SOCKS4a proxy server implementation based on Workerman for PHP 8.1+. Features complete protocol support, optional user authentication, asynchronous I/O, and comprehensive logging.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
  - [Basic Proxy Server](#basic-proxy-server)
  - [Proxy Server with Authentication](#proxy-server-with-authentication)
- [Protocol Support](#protocol-support)
  - [SOCKS4 Protocol](#socks4-protocol)
  - [SOCKS4a Extension](#socks4a-extension)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Testing](#testing)
- [Examples](#examples)
- [Advanced Usage](#advanced-usage)
- [Logging](#logging)
- [Security Considerations](#security-considerations)
- [License](#license)

## Features

- **Full Protocol Support**: Complete SOCKS4 and SOCKS4a protocol implementation
- **Domain Name Resolution**: SOCKS4a extension supports domain name resolution
- **User Authentication**: Optional user ID validation for access control
- **High Performance**: Asynchronous implementation using Workerman
- **PSR-3 Logging**: Comprehensive logging support with PSR-3 logger interface
- **Connection Management**: Efficient connection pooling and management
- **Simple API**: Easy-to-use configuration and setup

## Installation

Install via Composer:

```bash
composer require tourze/workerman-socks4-proxy
```

## Requirements

- PHP 8.1 or higher
- Workerman 5.1 or higher
- PSR-3 Logger implementation
- ext-sockets (recommended for optimal performance)

## Quick Start

### Basic Proxy Server

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Tourze\Workerman\PsrLogger\WorkermanLogger;
use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Worker;

// Create PSR Logger instance
$logger = new WorkermanLogger();

// Create SOCKS4 proxy server
$proxy = new SOCKS4Worker($logger, 'tcp://0.0.0.0:1080');

// Set process count
$proxy->count = 4;

// Disable authentication for basic usage
$proxy->setEnableAuthentication(false);

// Start the server
Worker::runAll();
```

### Proxy Server with Authentication

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use Tourze\Workerman\PsrLogger\WorkermanLogger;
use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Worker;

// Create PSR Logger instance
$logger = new WorkermanLogger();

// Create SOCKS4 proxy server
$proxy = new SOCKS4Worker($logger, 'tcp://0.0.0.0:1080');

// Set process count
$proxy->count = 4;

// Enable user authentication
$proxy->setEnableAuthentication(true);
$proxy->addValidUser('username1');
$proxy->addValidUser('username2');

// Start the server
Worker::runAll();
```

## Protocol Support

### SOCKS4 Protocol

Standard SOCKS4 protocol for TCP connections:
- Connect to IP addresses directly
- User ID authentication
- Connection command support

### SOCKS4a Extension

Enhanced SOCKS4a protocol features:
- Domain name resolution
- Support for hostnames instead of IP addresses
- Backward compatibility with SOCKS4

## API Reference

### SOCKS4Worker Class

#### Constructor

```php
public function __construct(LoggerInterface $logger, string $socketName = 'tcp://0.0.0.0:1080')
```

- `$logger`: PSR-3 logger instance for logging
- `$socketName`: Socket address to listen on (default: tcp://0.0.0.0:1080)

#### Methods

```php
// Authentication management
public function setEnableAuthentication(bool $enable): void
public function addValidUser(string $userId): void
public function isAuthenticationEnabled(): bool
```

## Testing

Test the proxy server with curl:

```bash
# Test HTTP connection
curl -x socks4://127.0.0.1:1080 http://www.example.com

# Test HTTPS connection
curl -x socks4://127.0.0.1:1080 https://www.example.com

# Test with authentication (username: testuser)
curl -x socks4://testuser@127.0.0.1:1080 http://www.example.com
```

## Examples

See the [examples](./examples) directory for detailed examples:

- `basic_proxy_server.php` - Basic proxy server without authentication

Run the example:

```bash
php examples/basic_proxy_server.php start
```

## Configuration

### Server Configuration

```php
// Set number of worker processes
$proxy->count = 4;

// Set server name
$proxy->name = 'MySOCKS4Proxy';

// Configure Workerman settings
Worker::$logFile = '/tmp/socks4_proxy.log';
Worker::$stdoutFile = '/tmp/socks4_stdout.log';
```

### Authentication Configuration

```php
// Enable authentication
$proxy->setEnableAuthentication(true);

// Add multiple valid users
$validUsers = ['user1', 'user2', 'user3'];
foreach ($validUsers as $user) {
    $proxy->addValidUser($user);
}
```

## Advanced Usage

### Custom Logger Implementation

You can implement your own PSR-3 logger for custom logging behavior:

```php
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class CustomLogger implements LoggerInterface
{
    public function emergency($message, array $context = []): void 
    {
        // Custom emergency logging
    }
    
    public function alert($message, array $context = []): void 
    {
        // Custom alert logging
    }
    
    // ... implement other PSR-3 methods
}

$customLogger = new CustomLogger();
$proxy = new SOCKS4Worker($customLogger, 'tcp://0.0.0.0:1080');
```

### Connection Event Handling

Monitor connection events for custom processing:

```php
$proxy = new SOCKS4Worker($logger, 'tcp://0.0.0.0:1080');

// Handle new connections
$proxy->onConnect = function($connection) {
    echo "New connection from " . $connection->getRemoteIp() . "\n";
};

// Handle connection close
$proxy->onClose = function($connection) {
    echo "Connection closed\n";
};
```

### Performance Tuning

Optimize server performance for high-load scenarios:

```php
// Increase worker processes
$proxy->count = 8;

// Set socket buffer sizes
$proxy->reusePort = true;

// Configure Workerman settings
Worker::$eventLoopClass = '\\Workerman\\Events\\Event';
Worker::$maxPackageSize = 10 * 1024 * 1024; // 10MB
```

## Logging

This package uses PSR-3 logging for comprehensive debugging and monitoring:

```php
use Tourze\Workerman\PsrLogger\WorkermanLogger;

$logger = new WorkermanLogger();
// Logger will capture:
// - Connection attempts
// - Authentication status
// - Protocol parsing
// - Error conditions
// - Performance metrics
```

## Security Considerations

- **Authentication**: Enable user authentication for production use
- **IP Filtering**: Consider implementing IP-based access controls
- **Logging**: Monitor access logs for suspicious activity
- **Resource Limits**: Configure appropriate process limits
- **Network Security**: Use firewall rules to restrict proxy access
- **User Management**: Regularly review and update valid user lists

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
