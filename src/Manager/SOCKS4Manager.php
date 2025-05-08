<?php

namespace Tourze\Workerman\SOCKS4\Manager;

use Tourze\Workerman\ConnectionPipe\Pipe\TcpToTcpPipe;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4ConnectionStatus;
use WeakMap;
use Workerman\Connection\ConnectionInterface;

/**
 * SOCKS4连接管理器
 *
 * 使用WeakMap存储SOCKS4连接的状态和相关数据，避免直接在连接对象上存储
 */
class SOCKS4Manager
{
    /**
     * 存储SOCKS4连接状态
     *
     * @var WeakMap<ConnectionInterface, SOCKS4ConnectionStatus>
     */
    private static WeakMap $socks4Status;

    /**
     * 存储连接目标IP地址
     *
     * @var WeakMap<ConnectionInterface, string>
     */
    private static WeakMap $targetIp;

    /**
     * 存储连接目标端口
     *
     * @var WeakMap<ConnectionInterface, int>
     */
    private static WeakMap $targetPort;

    /**
     * 存储连接目标域名 (用于SOCKS4a)
     *
     * @var WeakMap<ConnectionInterface, string>
     */
    private static WeakMap $targetHostname;

    /**
     * 存储连接用户ID
     *
     * @var WeakMap<ConnectionInterface, string>
     */
    private static WeakMap $userId;

    /**
     * 存储连接的正向管道
     *
     * @var WeakMap<ConnectionInterface, TcpToTcpPipe>
     */
    private static WeakMap $forwardPipe;

    /**
     * 存储连接的反向管道
     *
     * @var WeakMap<ConnectionInterface, TcpToTcpPipe>
     */
    private static WeakMap $backwardPipe;

    /**
     * 存储连接的目标连接
     *
     * @var WeakMap<ConnectionInterface, ConnectionInterface>
     */
    private static WeakMap $targetConnection;

    /**
     * SOCKS版本 (对SOCKS4总是4)
     */
    private static WeakMap $socksVersion;

    /**
     * SOCKS命令 (CONNECT/BIND)
     */
    private static WeakMap $socksCommand;

    /**
     * 初始化所有WeakMap
     */
    public static function init(): void
    {
        self::$socks4Status = new WeakMap();
        self::$targetIp = new WeakMap();
        self::$targetPort = new WeakMap();
        self::$targetHostname = new WeakMap();
        self::$userId = new WeakMap();
        self::$forwardPipe = new WeakMap();
        self::$backwardPipe = new WeakMap();
        self::$targetConnection = new WeakMap();
        self::$socksVersion = new WeakMap();
        self::$socksCommand = new WeakMap();
    }

    /**
     * 设置SOCKS4连接状态
     */
    public static function setStatus(ConnectionInterface $connection, SOCKS4ConnectionStatus $status): void
    {
        self::$socks4Status[$connection] = $status;
    }

    /**
     * 获取SOCKS4连接状态
     */
    public static function getStatus(ConnectionInterface $connection): ?SOCKS4ConnectionStatus
    {
        return self::$socks4Status[$connection] ?? null;
    }

    /**
     * 设置目标IP
     */
    public static function setTargetIp(ConnectionInterface $connection, string $ip): void
    {
        self::$targetIp[$connection] = $ip;
    }

    /**
     * 获取目标IP
     */
    public static function getTargetIp(ConnectionInterface $connection): ?string
    {
        return self::$targetIp[$connection] ?? null;
    }

    /**
     * 设置目标端口
     */
    public static function setTargetPort(ConnectionInterface $connection, int $port): void
    {
        self::$targetPort[$connection] = $port;
    }

    /**
     * 获取目标端口
     */
    public static function getTargetPort(ConnectionInterface $connection): ?int
    {
        return self::$targetPort[$connection] ?? null;
    }

    /**
     * 设置目标域名 (用于SOCKS4a)
     */
    public static function setTargetHostname(ConnectionInterface $connection, string $hostname): void
    {
        self::$targetHostname[$connection] = $hostname;
    }

    /**
     * 获取目标域名
     */
    public static function getTargetHostname(ConnectionInterface $connection): ?string
    {
        return self::$targetHostname[$connection] ?? null;
    }

    /**
     * 设置用户ID
     */
    public static function setUserId(ConnectionInterface $connection, string $userId): void
    {
        self::$userId[$connection] = $userId;
    }

    /**
     * 获取用户ID
     */
    public static function getUserId(ConnectionInterface $connection): ?string
    {
        return self::$userId[$connection] ?? null;
    }

    /**
     * 设置正向管道
     */
    public static function setForwardPipe(ConnectionInterface $connection, TcpToTcpPipe $pipe): void
    {
        self::$forwardPipe[$connection] = $pipe;
    }

    /**
     * 获取正向管道
     */
    public static function getForwardPipe(ConnectionInterface $connection): ?TcpToTcpPipe
    {
        return self::$forwardPipe[$connection] ?? null;
    }

    /**
     * 设置反向管道
     */
    public static function setBackwardPipe(ConnectionInterface $connection, TcpToTcpPipe $pipe): void
    {
        self::$backwardPipe[$connection] = $pipe;
    }

    /**
     * 获取反向管道
     */
    public static function getBackwardPipe(ConnectionInterface $connection): ?TcpToTcpPipe
    {
        return self::$backwardPipe[$connection] ?? null;
    }

    /**
     * 设置目标连接
     */
    public static function setTargetConnection(ConnectionInterface $connection, ConnectionInterface $targetConnection): void
    {
        self::$targetConnection[$connection] = $targetConnection;
    }

    /**
     * 获取目标连接
     */
    public static function getTargetConnection(ConnectionInterface $connection): ?ConnectionInterface
    {
        return self::$targetConnection[$connection] ?? null;
    }

    /**
     * 设置SOCKS版本
     */
    public static function setVersion(ConnectionInterface $connection, int $version): void
    {
        self::$socksVersion[$connection] = $version;
    }

    /**
     * 获取SOCKS版本
     */
    public static function getVersion(ConnectionInterface $connection): ?int
    {
        return self::$socksVersion[$connection] ?? null;
    }

    /**
     * 设置SOCKS命令
     */
    public static function setCommand(ConnectionInterface $connection, $command): void
    {
        self::$socksCommand[$connection] = $command;
    }

    /**
     * 获取SOCKS命令
     */
    public static function getCommand(ConnectionInterface $connection)
    {
        return self::$socksCommand[$connection] ?? null;
    }

    /**
     * 清理连接相关的所有资源
     */
    public static function cleanUp(ConnectionInterface $connection): void
    {
        // 关闭并清理管道
        $forwardPipe = self::getForwardPipe($connection);
        if ($forwardPipe !== null) {
            $forwardPipe->unpipe();
        }

        $backwardPipe = self::getBackwardPipe($connection);
        if ($backwardPipe !== null) {
            $backwardPipe->unpipe();
        }

        // 关闭目标连接
        $targetConnection = self::getTargetConnection($connection);
        if ($targetConnection !== null) {
            $targetConnection->close();
        }

        // 不需要手动删除WeakMap中的项目，因为WeakMap会自动处理弱引用
    }
}

// 初始化所有WeakMap
SOCKS4Manager::init(); 