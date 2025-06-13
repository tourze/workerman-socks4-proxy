<?php

namespace Tourze\Workerman\SOCKS4\Worker;

use Psr\Log\LoggerInterface;
use Tourze\Workerman\ConnectionPipe\Container as ConnectionPipeContainer;
use Tourze\Workerman\ConnectionPipe\PipeFactory;
use Tourze\Workerman\SOCKS4\Auth\SOCKS4Auth;
use Tourze\Workerman\SOCKS4\Manager\SOCKS4Manager;
use Tourze\Workerman\SOCKS4\Protocol\SOCKS4;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * SOCKS4代理Worker
 */
class SOCKS4Worker extends Worker
{
    /**
     * 认证管理器
     *
     * @var SOCKS4Auth
     */
    private readonly SOCKS4Auth $auth;

    /**
     * 构造函数
     *
     * @param string $socketName 监听的协议和地址，如 tcp://0.0.0.0:1080
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        string $socketName = 'tcp://0.0.0.0:1080',
    )
    {
        // 设置协议为SOCKS4
        parent::__construct($socketName);
        $this->protocol = SOCKS4::class;

        $this->name = 'SOCKS4Proxy';

        // 获取认证管理器单例
        $this->auth = SOCKS4Auth::getInstance();

        // 设置回调
        $this->onWorkerStart = $this->onWorkerStart(...);
        $this->onMessage = $this->onClientMessage(...);
    }

    /**
     * 初始化Worker时调用
     */
    public function onWorkerStart(): void
    {
        // 不需要再手动同步认证配置到协议类
        // 因为SOCKS4Auth是单例，SOCKS4.php会自动使用相同的实例
        ConnectionPipeContainer::getInstance()->setLogger($this->logger);
    }

    /**
     * 当收到客户端消息时
     *
     * @param TcpConnection $connection 客户端连接
     * @param mixed $data 客户端数据
     */
    public function onClientMessage(TcpConnection $connection, mixed $data): void
    {
        if (empty($data)) {
            $this->logger->debug('数据为空，不处理');
            return;
        }
        //LogUtil::debug('Worker实际收到数据', $data);

        $targetConnection = SOCKS4Manager::getTargetConnection($connection);
        if ($targetConnection) {
            return;
        }

        // 检查是否有目标地址和端口
        $targetIp = SOCKS4Manager::getTargetIp($connection);
        $targetPort = SOCKS4Manager::getTargetPort($connection);

        if (empty($targetIp) || empty($targetPort)) {
            // 记录错误信息
            $this->logger->warning('SOCKS4协议错误: 目标IP或端口为空');
            // SOCKS4协议解析失败或被拒绝
            $connection->close();
            return;
        }

        // 记录目标连接信息
        $this->logger->debug('尝试连接目标: ' . $targetIp . ':' . $targetPort);

        // 创建到目标服务器的连接
        $targetConnection = new AsyncTcpConnection("tcp://{$targetIp}:{$targetPort}");
        SOCKS4Manager::setTargetConnection($connection, $targetConnection);

        // 反向转发
        $pipe2 = PipeFactory::createTcpToTcp($targetConnection, $connection);
        $pipe2->pipe();
        $targetConnection->connect();

        // 正向转发
        $pipe1 = PipeFactory::createTcpToTcp($connection, $targetConnection);
        $pipe1->pipe();
        $pipe1->forward($data);
    }

    /**
     * 添加有效用户
     *
     * @param string $userId 用户ID
     */
    public function addValidUser(string $userId): void
    {
        $this->auth->addValidUser($userId);
    }

    /**
     * 设置是否启用验证
     *
     * @param bool $enable 是否启用
     */
    public function setEnableAuthentication(bool $enable): void
    {
        $this->auth->setEnableAuthentication($enable);
    }

    /**
     * 获取是否启用验证
     */
    public function isAuthenticationEnabled(): bool
    {
        return $this->auth->isAuthenticationEnabled();
    }
}
