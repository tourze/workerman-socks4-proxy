<?php

namespace Tourze\Workerman\SOCKS4\Tests\Worker;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\SOCKS4\Auth\SOCKS4Auth;
use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Connection\TcpConnection;

/**
 * @internal
 */
#[CoversClass(SOCKS4Worker::class)]
final class SOCKS4WorkerTest extends TestCase
{
    private LoggerInterface&MockObject $mockLogger;

    private SOCKS4Worker $socks4Worker;

    private SOCKS4Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();
        // 模拟日志实例
        $this->mockLogger = $this->createMock(LoggerInterface::class);

        // 创建 SOCKS4Worker 实例（单元测试中可以直接实例化）
        $this->socks4Worker = new SOCKS4Worker($this->mockLogger, 'tcp://0.0.0.0:1080');

        // 获取验证实例
        $this->auth = SOCKS4Auth::getInstance();

        // 重置验证设置
        $this->auth->setEnableAuthentication(false);
        $this->auth->setValidUsers([]);
    }

    public function testConstructorSetsProtocolAndName(): void
    {
        // 验证构造函数正确设置了协议和名称
        $this->assertSame('SOCKS4Proxy', $this->socks4Worker->name);
        $this->assertStringContainsString('SOCKS4', $this->socks4Worker->protocol ?? '');
    }

    public function testAddValidUserAddsUserToAuth(): void
    {
        // 验证 addValidUser 方法将用户添加到验证器
        $this->socks4Worker->addValidUser('testuser');

        // 验证用户被正确添加
        $this->assertTrue($this->auth->isValidUser('testuser'));
    }

    public function testSetEnableAuthenticationUpdatesAuthState(): void
    {
        // 初始状态应为禁用
        $this->assertFalse($this->auth->isAuthenticationEnabled());

        // 启用验证
        $this->socks4Worker->setEnableAuthentication(true);
        $this->assertTrue($this->auth->isAuthenticationEnabled());

        // 禁用验证
        $this->socks4Worker->setEnableAuthentication(false);
        $this->assertFalse($this->auth->isAuthenticationEnabled());
    }

    public function testIsAuthenticationEnabledReturnsCurrentState(): void
    {
        // 设置状态
        $this->auth->setEnableAuthentication(true);

        // 验证 Worker 方法返回正确的状态
        $this->assertTrue($this->socks4Worker->isAuthenticationEnabled());

        // 改变状态并再次验证
        $this->auth->setEnableAuthentication(false);
        $this->assertFalse($this->socks4Worker->isAuthenticationEnabled());
    }

    public function testOnClientMessageWithEmptyDataDoesNothing(): void
    {
        // 创建模拟连接
        $mockConnection = $this->createMock(TcpConnection::class);

        // 模拟日志记录功能
        $this->mockLogger->expects($this->once())
            ->method('debug')
            ->with(self::stringContains('数据为空'))
        ;

        // 调用 onClientMessage 方法
        $this->socks4Worker->onClientMessage($mockConnection, '');
    }

    public function testOnClientMessageWithoutTargetInfoClosesConnection(): void
    {
        // 创建模拟连接
        $mockConnection = $this->getMockBuilder(TcpConnection::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        // 连接应该被关闭
        $mockConnection->expects($this->once())
            ->method('close')
        ;

        // 应该记录警告日志
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with(self::stringContains('目标IP或端口为空'))
        ;

        // 调用 onClientMessage 方法
        $this->socks4Worker->onClientMessage($mockConnection, 'some data');
    }

    public function testOnWorkerStart(): void
    {
        // 测试 onWorkerStart 方法是否正确初始化连接管道容器的日志
        // 由于 ConnectionPipeContainer::getInstance() 是单例，无法直接模拟
        // 我们通过调用方法确保没有异常抛出来验证初始化过程
        $this->expectNotToPerformAssertions();
        $this->socks4Worker->onWorkerStart();
    }
}
