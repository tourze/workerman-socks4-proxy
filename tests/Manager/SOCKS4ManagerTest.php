<?php

namespace Tourze\Workerman\SOCKS4\Tests\Manager;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Command;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4ConnectionStatus;
use Tourze\Workerman\SOCKS4\Manager\SOCKS4Manager;
use Workerman\Connection\ConnectionInterface;

/**
 * @internal
 */
#[CoversClass(SOCKS4Manager::class)]
final class SOCKS4ManagerTest extends TestCase
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
        parent::setUp();
        // 创建连接相关的模拟对象
        $this->mockConnection = $this->createMock(ConnectionInterface::class);
        $this->mockTargetConnection = $this->createMock(ConnectionInterface::class);

        /*
         * 使用具体类 TcpToTcpPipe 而不是接口的原因说明：
         * 1. 为什么必须使用具体类：SOCKS4Manager 内部直接实例化和操作 TcpToTcpPipe 类的特定方法
         * 2. 合理性分析：TcpToTcpPipe 是专门用于 SOCKS4 代理中 TCP 连接转发的核心组件，其具体实现对测试是必要的
         * 3. 替代方案评估：虽然可以使用 ConnectionPipeInterface 接口，但会失去对 TcpToTcpPipe 特有方法的测试覆盖
         */
        $this->mockForwardPipe = $this->createMock(TcpToTcpPipe::class);

        /*
         * 使用具体类 TcpToTcpPipe 创建反向管道 Mock 的原因说明：
         * 1. 为什么必须使用具体类：反向管道需要支持 SOCKS4 协议的双向数据转发，具体类提供必要的方法实现
         * 2. 合理性分析：测试管道的设置和获取功能时，需要验证具体类型的正确性和一致性
         * 3. 替代方案评估：接口无法提供 SOCKS4 反向转发所需的特定行为测试
         */
        $this->mockBackwardPipe = $this->createMock(TcpToTcpPipe::class);
    }

    public function testSetAndGetStatus(): void
    {
        // 测试设置和获取连接状态
        SOCKS4Manager::setStatus($this->mockConnection, SOCKS4ConnectionStatus::INITIAL);
        $this->assertSame(SOCKS4ConnectionStatus::INITIAL, SOCKS4Manager::getStatus($this->mockConnection));

        SOCKS4Manager::setStatus($this->mockConnection, SOCKS4ConnectionStatus::ESTABLISHED);
        $this->assertSame(SOCKS4ConnectionStatus::ESTABLISHED, SOCKS4Manager::getStatus($this->mockConnection));
    }

    public function testSetAndGetTargetIp(): void
    {
        // 测试设置和获取目标IP
        $ip = '127.0.0.1';
        SOCKS4Manager::setTargetIp($this->mockConnection, $ip);
        $this->assertSame($ip, SOCKS4Manager::getTargetIp($this->mockConnection));

        // 测试不同连接对象的隔离性
        $anotherConnection = $this->createMock(ConnectionInterface::class);
        $this->assertNull(SOCKS4Manager::getTargetIp($anotherConnection));
    }

    public function testSetAndGetTargetPort(): void
    {
        // 测试设置和获取目标端口
        $port = 8080;
        SOCKS4Manager::setTargetPort($this->mockConnection, $port);
        $this->assertSame($port, SOCKS4Manager::getTargetPort($this->mockConnection));
    }

    public function testSetAndGetTargetHostname(): void
    {
        // 测试设置和获取目标主机名
        $hostname = 'example.com';
        SOCKS4Manager::setTargetHostname($this->mockConnection, $hostname);
        $this->assertSame($hostname, SOCKS4Manager::getTargetHostname($this->mockConnection));
    }

    public function testSetAndGetUserId(): void
    {
        // 测试设置和获取用户ID
        $userId = 'testuser';
        SOCKS4Manager::setUserId($this->mockConnection, $userId);
        $this->assertSame($userId, SOCKS4Manager::getUserId($this->mockConnection));
    }

    public function testSetAndGetForwardPipe(): void
    {
        // 测试设置和获取正向管道
        SOCKS4Manager::setForwardPipe($this->mockConnection, $this->mockForwardPipe);
        $this->assertSame($this->mockForwardPipe, SOCKS4Manager::getForwardPipe($this->mockConnection));
    }

    public function testSetAndGetBackwardPipe(): void
    {
        // 测试设置和获取反向管道
        SOCKS4Manager::setBackwardPipe($this->mockConnection, $this->mockBackwardPipe);
        $this->assertSame($this->mockBackwardPipe, SOCKS4Manager::getBackwardPipe($this->mockConnection));
    }

    public function testSetAndGetTargetConnection(): void
    {
        // 测试设置和获取目标连接
        SOCKS4Manager::setTargetConnection($this->mockConnection, $this->mockTargetConnection);
        $this->assertSame($this->mockTargetConnection, SOCKS4Manager::getTargetConnection($this->mockConnection));
    }

    public function testSetAndGetVersion(): void
    {
        // 测试设置和获取SOCKS版本
        $version = 4;
        SOCKS4Manager::setVersion($this->mockConnection, $version);
        $this->assertSame($version, SOCKS4Manager::getVersion($this->mockConnection));
    }

    public function testSetAndGetCommand(): void
    {
        // 测试设置和获取SOCKS命令
        $command = SOCKS4Command::CONNECT->value;
        SOCKS4Manager::setCommand($this->mockConnection, $command);
        $this->assertSame($command, SOCKS4Manager::getCommand($this->mockConnection));
    }

    public function testCleanUp(): void
    {
        /*
         * 使用具体类 TcpToTcpPipe 而不是接口的原因说明：
         * 1. 为什么必须使用具体类：该测试需要验证 unpipe() 方法的调用，这是 TcpToTcpPipe 的特定行为
         * 2. 合理性分析：测试清理逻辑时需要确保管道正确关闭，具体类的方法调用是测试的核心要素
         * 3. 替代方案评估：使用接口会丢失对具体清理行为的验证，降低测试的有效性
         */
        $mockForwardPipe = $this->createMock(TcpToTcpPipe::class);
        $mockForwardPipe->expects($this->once())
            ->method('unpipe')
        ;

        /*
         * 使用具体类 TcpToTcpPipe 而不是接口的原因说明：
         * 1. 为什么必须使用具体类：测试需要验证反向管道的 unpipe() 方法调用，这是具体类的特定行为
         * 2. 合理性分析：在清理过程中，反向管道的正确断开是关键功能，需要测试具体的断开行为
         * 3. 替代方案评估：使用接口无法测试具体的管道断开逻辑，影响测试的准确性和完整性
         */
        $mockBackwardPipe = $this->createMock(TcpToTcpPipe::class);
        $mockBackwardPipe->expects($this->once())
            ->method('unpipe')
        ;

        $mockTargetConnection = $this->createMock(ConnectionInterface::class);
        $mockTargetConnection->expects($this->once())
            ->method('close')
        ;

        // 设置管道和目标连接
        SOCKS4Manager::setForwardPipe($this->mockConnection, $mockForwardPipe);
        SOCKS4Manager::setBackwardPipe($this->mockConnection, $mockBackwardPipe);
        SOCKS4Manager::setTargetConnection($this->mockConnection, $mockTargetConnection);

        // 执行清理
        SOCKS4Manager::cleanUp($this->mockConnection);
    }

    public function testGetNonExistentValues(): void
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
