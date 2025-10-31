<?php

namespace Tourze\Workerman\SOCKS4\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\PsrLogger\WorkermanLogger;
use Tourze\Workerman\SOCKS4\Container;

/**
 * @internal
 */
#[CoversClass(Container::class)]
final class ContainerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 重置静态属性以避免测试之间相互影响
        Container::setLogger(new WorkermanLogger());
    }

    public function testGetLoggerDefaultInstance(): void
    {
        // 测试获取默认日志记录器
        $logger = Container::getLogger();
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertInstanceOf(WorkermanLogger::class, $logger);
    }

    public function testSetAndGetLogger(): void
    {
        // 创建自定义日志记录器
        $mockLogger = $this->createMock(LoggerInterface::class);

        // 设置自定义日志记录器
        Container::setLogger($mockLogger);

        // 验证获取的是我们设置的日志记录器
        $this->assertSame($mockLogger, Container::getLogger());
    }
}
