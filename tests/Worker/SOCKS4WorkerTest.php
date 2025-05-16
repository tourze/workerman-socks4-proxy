<?php

namespace Tourze\Workerman\SOCKS4\Tests\Worker;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\SOCKS4\Auth\SOCKS4Auth;
use Tourze\Workerman\SOCKS4\Worker\SOCKS4Worker;
use Workerman\Connection\TcpConnection;

class SOCKS4WorkerTest extends TestCase
{
    private $mockLogger;
    private $socks4Worker;
    private $auth;
    
    protected function setUp(): void
    {
        // 模拟日志实例
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        
        // 创建 SOCKS4Worker 实例
        $this->socks4Worker = new SOCKS4Worker($this->mockLogger, 'tcp://0.0.0.0:1080');
        
        // 获取验证实例
        $this->auth = SOCKS4Auth::getInstance();
        
        // 重置验证设置
        $this->auth->setEnableAuthentication(false);
        $this->auth->setValidUsers([]);
    }
    
    public function testConstructor_SetsProtocolAndName()
    {
        // 验证构造函数正确设置了协议和名称
        $this->assertSame('SOCKS4Proxy', $this->socks4Worker->name);
        $this->assertStringContainsString('SOCKS4', $this->socks4Worker->protocol);
    }
    
    public function testAddValidUser_AddsUserToAuth()
    {
        // 验证 addValidUser 方法将用户添加到验证器
        $this->socks4Worker->addValidUser('testuser');
        
        // 验证用户被正确添加
        $this->assertTrue($this->auth->isValidUser('testuser'));
    }
    
    public function testSetEnableAuthentication_UpdatesAuthState()
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
    
    public function testIsAuthenticationEnabled_ReturnsCurrentState()
    {
        // 设置状态
        $this->auth->setEnableAuthentication(true);
        
        // 验证 Worker 方法返回正确的状态
        $this->assertTrue($this->socks4Worker->isAuthenticationEnabled());
        
        // 改变状态并再次验证
        $this->auth->setEnableAuthentication(false);
        $this->assertFalse($this->socks4Worker->isAuthenticationEnabled());
    }
    
    public function testOnClientMessage_WithEmptyData_DoesNothing()
    {
        // 创建模拟连接
        $mockConnection = $this->createMock(TcpConnection::class);
        
        // 模拟日志记录功能
        $this->mockLogger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('数据为空'));
        
        // 调用 onClientMessage 方法
        $this->socks4Worker->onClientMessage($mockConnection, '');
    }
    
    public function testOnClientMessage_WithoutTargetInfo_ClosesConnection()
    {
        // 创建模拟连接
        $mockConnection = $this->getMockBuilder(TcpConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // 连接应该被关闭
        $mockConnection->expects($this->once())
            ->method('close');
        
        // 应该记录警告日志
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('目标IP或端口为空'));
        
        // 调用 onClientMessage 方法
        $this->socks4Worker->onClientMessage($mockConnection, 'some data');
    }
} 