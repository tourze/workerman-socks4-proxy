<?php

namespace Tourze\Workerman\SOCKS4\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\SOCKS4\Auth\SOCKS4Auth;
use Tourze\Workerman\SOCKS4\Container;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4ConnectionStatus;
use Tourze\Workerman\SOCKS4\Manager\SOCKS4Manager;
use Tourze\Workerman\SOCKS4\Protocol\SOCKS4;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(SOCKS4::class)]
final class SOCKS4Test extends TestCase
{
    private ConnectionInterface $mockConnection;

    private LoggerInterface $mockLogger;

    protected function setUp(): void
    {
        parent::setUp();
        // 创建模拟连接对象
        $this->mockConnection = $this->createMock(ConnectionInterface::class);

        // 创建模拟日志对象并设置到容器
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        Container::setLogger($this->mockLogger);

        // 重置 SOCKS4Auth 单例以避免测试间干扰
        $auth = SOCKS4Auth::getInstance();
        $auth->setEnableAuthentication(false);
        $auth->setValidUsers([]);
    }

    public function testIsValidUserWhenAuthDisabledReturnsTrue(): void
    {
        // 当验证被禁用时，任何用户都应该有效
        $auth = SOCKS4Auth::getInstance();
        $auth->setEnableAuthentication(false);

        $this->assertTrue(SOCKS4::isValidUser('anyuser'));
        $this->assertTrue(SOCKS4::isValidUser(''));
    }

    public function testIsValidUserWithValidUserReturnsTrue(): void
    {
        // 启用验证并添加有效用户
        $auth = SOCKS4Auth::getInstance();
        $auth->setEnableAuthentication(true);
        $auth->addValidUser('testuser');

        $this->assertTrue(SOCKS4::isValidUser('testuser'));
    }

    public function testIsValidUserWithInvalidUserReturnsFalse(): void
    {
        // 启用验证后，未添加的用户应该是无效的
        $auth = SOCKS4Auth::getInstance();
        $auth->setEnableAuthentication(true);
        $auth->addValidUser('testuser');

        $this->assertFalse(SOCKS4::isValidUser('invaliduser'));
    }

    public function testEncodeWhenEstablishedReturnsData(): void
    {
        // 当连接已建立时，encode 应直接返回数据
        $data = 'test data';

        // 设置连接状态为已建立
        SOCKS4Manager::setStatus($this->mockConnection, SOCKS4ConnectionStatus::ESTABLISHED);

        // 验证 encode 直接返回数据
        $result = SOCKS4::encode($data, $this->mockConnection);
        $this->assertSame($data, $result);
    }

    public function testEncodeWhenInitialReturnsEmptyString(): void
    {
        // 当连接处于初始状态时，encode 应返回空字符串
        $data = 'test data';

        // 设置连接状态为初始状态
        SOCKS4Manager::setStatus($this->mockConnection, SOCKS4ConnectionStatus::INITIAL);

        // 验证 encode 返回空字符串
        $result = SOCKS4::encode($data, $this->mockConnection);
        $this->assertSame('', $result);
    }
}
