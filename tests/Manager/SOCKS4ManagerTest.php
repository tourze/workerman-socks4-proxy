<?php

namespace Tourze\Workerman\SOCKS4\Tests\Manager;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Command;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4ConnectionStatus;
use Tourze\Workerman\SOCKS4\Manager\SOCKS4Manager;
use Workerman\Connection\ConnectionInterface;

class SOCKS4ManagerTest extends TestCase
{
    /**
     * @var ConnectionInterface
     */
    private $mockConnection;
    
    /**
     * @var ConnectionInterface
     */
    private $mockTargetConnection;
    
    /**
     * @var TcpToTcpPipe
     */
    private $mockForwardPipe;
    
    /**
     * @var TcpToTcpPipe
     */
    private $mockBackwardPipe;
    
    protected function setUp(): void
    {
        // 创建连接相关的模拟对象
        $this->mockConnection = $this->createMock(ConnectionInterface::class);
        $this->mockTargetConnection = $this->createMock(ConnectionInterface::class);
        $this->mockForwardPipe = $this->createMock(TcpToTcpPipe::class);
        $this->mockBackwardPipe = $this->createMock(TcpToTcpPipe::class);
    }
    
    public function testSetAndGetStatus()
    {
        // 测试设置和获取连接状态
        SOCKS4Manager::setStatus($this->mockConnection, SOCKS4ConnectionStatus::INITIAL);
        $this->assertSame(SOCKS4ConnectionStatus::INITIAL, SOCKS4Manager::getStatus($this->mockConnection));
        
        SOCKS4Manager::setStatus($this->mockConnection, SOCKS4ConnectionStatus::ESTABLISHED);
        $this->assertSame(SOCKS4ConnectionStatus::ESTABLISHED, SOCKS4Manager::getStatus($this->mockConnection));
    }
    
    public function testSetAndGetTargetIp()
    {
        // 测试设置和获取目标IP
        $ip = '127.0.0.1';
        SOCKS4Manager::setTargetIp($this->mockConnection, $ip);
        $this->assertSame($ip, SOCKS4Manager::getTargetIp($this->mockConnection));
        
        // 测试不同连接对象的隔离性
        $anotherConnection = $this->createMock(ConnectionInterface::class);
        $this->assertNull(SOCKS4Manager::getTargetIp($anotherConnection));
    }
    
    public function testSetAndGetTargetPort()
    {
        // 测试设置和获取目标端口
        $port = 8080;
        SOCKS4Manager::setTargetPort($this->mockConnection, $port);
        $this->assertSame($port, SOCKS4Manager::getTargetPort($this->mockConnection));
    }
    
    public function testSetAndGetTargetHostname()
    {
        // 测试设置和获取目标主机名
        $hostname = 'example.com';
        SOCKS4Manager::setTargetHostname($this->mockConnection, $hostname);
        $this->assertSame($hostname, SOCKS4Manager::getTargetHostname($this->mockConnection));
    }
    
    public function testSetAndGetUserId()
    {
        // 测试设置和获取用户ID
        $userId = 'testuser';
        SOCKS4Manager::setUserId($this->mockConnection, $userId);
        $this->assertSame($userId, SOCKS4Manager::getUserId($this->mockConnection));
    }
    
    public function testSetAndGetForwardPipe()
    {
        // 测试设置和获取正向管道
        SOCKS4Manager::setForwardPipe($this->mockConnection, $this->mockForwardPipe);
        $this->assertSame($this->mockForwardPipe, SOCKS4Manager::getForwardPipe($this->mockConnection));
    }
    
    public function testSetAndGetBackwardPipe()
    {
        // 测试设置和获取反向管道
        SOCKS4Manager::setBackwardPipe($this->mockConnection, $this->mockBackwardPipe);
        $this->assertSame($this->mockBackwardPipe, SOCKS4Manager::getBackwardPipe($this->mockConnection));
    }
    
    public function testSetAndGetTargetConnection()
    {
        // 测试设置和获取目标连接
        SOCKS4Manager::setTargetConnection($this->mockConnection, $this->mockTargetConnection);
        $this->assertSame($this->mockTargetConnection, SOCKS4Manager::getTargetConnection($this->mockConnection));
    }
    
    public function testSetAndGetVersion()
    {
        // 测试设置和获取SOCKS版本
        $version = 4;
        SOCKS4Manager::setVersion($this->mockConnection, $version);
        $this->assertSame($version, SOCKS4Manager::getVersion($this->mockConnection));
    }
    
    public function testSetAndGetCommand()
    {
        // 测试设置和获取SOCKS命令
        $command = SOCKS4Command::CONNECT->value;
        SOCKS4Manager::setCommand($this->mockConnection, $command);
        $this->assertSame($command, SOCKS4Manager::getCommand($this->mockConnection));
    }
    
    public function testCleanUp()
    {
        // 模拟管道和目标连接
        $mockForwardPipe = $this->createMock(TcpToTcpPipe::class);
        $mockForwardPipe->expects($this->once())
            ->method('unpipe');
            
        $mockBackwardPipe = $this->createMock(TcpToTcpPipe::class);
        $mockBackwardPipe->expects($this->once())
            ->method('unpipe');
            
        $mockTargetConnection = $this->createMock(ConnectionInterface::class);
        $mockTargetConnection->expects($this->once())
            ->method('close');
        
        // 设置管道和目标连接
        SOCKS4Manager::setForwardPipe($this->mockConnection, $mockForwardPipe);
        SOCKS4Manager::setBackwardPipe($this->mockConnection, $mockBackwardPipe);
        SOCKS4Manager::setTargetConnection($this->mockConnection, $mockTargetConnection);
        
        // 执行清理
        SOCKS4Manager::cleanUp($this->mockConnection);
    }
    
    public function testGetNonExistentValues()
    {
        // 测试获取不存在的值时返回null
        $newConnection = $this->createMock(ConnectionInterface::class);
        
        $this->assertNull(SOCKS4Manager::getStatus($newConnection));
        $this->assertNull(SOCKS4Manager::getTargetIp($newConnection));
        $this->assertNull(SOCKS4Manager::getTargetPort($newConnection));
        $this->assertNull(SOCKS4Manager::getTargetHostname($newConnection));
        $this->assertNull(SOCKS4Manager::getUserId($newConnection));
        $this->assertNull(SOCKS4Manager::getForwardPipe($newConnection));
        $this->assertNull(SOCKS4Manager::getBackwardPipe($newConnection));
        $this->assertNull(SOCKS4Manager::getTargetConnection($newConnection));
        $this->assertNull(SOCKS4Manager::getVersion($newConnection));
        $this->assertNull(SOCKS4Manager::getCommand($newConnection));
    }
} 