<?php

namespace Tourze\Workerman\SOCKS4\Protocol;

use Tourze\Workerman\PsrLogger\LogUtil;
use Tourze\Workerman\SOCKS4\Auth\SOCKS4Auth;
use Tourze\Workerman\SOCKS4\Container;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Command;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4ConnectionStatus;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Response;
use Tourze\Workerman\SOCKS4\Manager\SOCKS4Manager;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\ProtocolInterface;

/**
 * SOCKS4 协议实现 (同时支持SOCKS4a扩展)
 *
 * SOCKS4 协议格式:
 * +----+----+----+----+----+----+----+----+----+----+....+----+
 * | VN | CD | DSTPORT |      DSTIP        | USERID       |NULL|
 * +----+----+----+----+----+----+----+----+----+----+....+----+
 *    1    1      2              4           variable       1
 *
 * SOCKS4a 扩展格式:
 * +----+----+----+----+----+----+----+----+----+----+....+----+----+....+----+
 * | VN | CD | DSTPORT |      DSTIP        | USERID       |NULL| HOSTNAME |NULL|
 * +----+----+----+----+----+----+----+----+----+----+....+----+----+....+----+
 *    1    1      2              4           variable       1    variable    1
 *
 * 当 DSTIP 的前三个字节为 0，第四个字节不为 0（如 0.0.0.x），表示这是 SOCKS4a 请求
 * 此时 HOSTNAME 字段包含目标主机的域名，服务器需要解析此域名
 *
 * VN: 版本号，SOCKS4为0x04
 * CD: 命令码，1=CONNECT, 2=BIND
 * DSTPORT: 目标端口（网络字节序）
 * DSTIP: 目标IP地址 (对于SOCKS4a请求，此处为 0.0.0.x，其中 x 不为 0)
 * USERID: 用户ID字符串
 * NULL: 结束符 0x00
 * HOSTNAME: 仅SOCKS4a请求中出现，目标主机的域名
 *
 * 服务器响应格式:
 * +----+----+----+----+----+----+----+----+
 * | VN | CD | DSTPORT |      DSTIP        |
 * +----+----+----+----+----+----+----+----+
 *    1    1      2              4
 *
 * VN: 版本号，总是0x00
 * CD: 结果码，90=granted, 91=rejected
 */
class SOCKS4 implements ProtocolInterface
{
    /**
     * 检查用户是否有效
     *
     * @param string $userId 用户ID
     * @return bool
     */
    public static function isValidUser(string $userId): bool
    {
        return SOCKS4Auth::getInstance()->isValidUser($userId);
    }

    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        // 调试日志
        LogUtil::debug("SOCKS4::input", $buffer);

        // 获取当前连接状态
        $status = SOCKS4Manager::getStatus($connection);
        if ($status === null) {
            $status = SOCKS4ConnectionStatus::INITIAL;
            SOCKS4Manager::setStatus($connection, $status);
        }

        // 如果连接已建立，直接返回所有数据长度（传递所有数据）
        if ($status === SOCKS4ConnectionStatus::ESTABLISHED) {
            return strlen($buffer);
        }

        // 初始化状态，处理SOCKS4/4a协议请求
        $length = strlen($buffer);

        // SOCKS4/4a请求最短为9字节 (VN+CD+DSTPORT+DSTIP+NULL)
        if ($length < 9) {
            Container::getLogger()->debug("SOCKS4::input - 数据不完整，长度小于9字节: " . $length);
            return 0; // 数据不完整，等待更多数据
        }

        // 获取版本号
        $version = ord($buffer[0]);
        if ($version !== 0x04) {
            Container::getLogger()->debug("SOCKS4::input - 非SOCKS4协议，版本号: " . $version);
            return -1; // 非SOCKS4/4a协议，关闭连接
        }

        // 查找USERID字段结尾的NULL字节
        $firstNullPos = strpos($buffer, "\0", 8);
        if ($firstNullPos === false) {
            // 如果缓冲区大于256字节但仍找不到NULL字节，可能是格式错误
            if ($length > 256) {
                Container::getLogger()->debug("SOCKS4::input - 找不到USERID结束的NULL字节，数据可能格式错误");
                return -1;
            }
            Container::getLogger()->debug("SOCKS4::input - 等待更多数据以找到USERID结尾");
            return 0; // 继续等待更多数据
        }

        // 检查是否是SOCKS4a请求（IP地址前三个字节为0，第四个字节不为0）
        $possibleSocks4a = substr($buffer, 4, 3) === "\x00\x00\x00" && ord($buffer[7]) !== 0;

        if ($possibleSocks4a) {
            // 对于SOCKS4a，我们需要找到域名后的第二个NULL字节
            $secondNullPos = strpos($buffer, "\0", $firstNullPos + 1);
            if ($secondNullPos === false) {
                // 如果缓冲区大于1024字节但仍找不到第二个NULL字节，可能是格式错误
                if ($length > 1024) {
                    return -1;
                }
                return 0; // 继续等待更多数据
            }

            // 完整的SOCKS4a请求
            return $secondNullPos + 1;
        }

        // 标准SOCKS4请求
        return $firstNullPos + 1;
    }

    public static function decode(string $buffer, ConnectionInterface $connection): mixed
    {
        // 获取当前连接状态
        $status = SOCKS4Manager::getStatus($connection) ?? SOCKS4ConnectionStatus::INITIAL;

        // 如果连接已建立，直接返回数据（透明传输）
        if ($status === SOCKS4ConnectionStatus::ESTABLISHED) {
            return $buffer;
        }

        // 调试日志
        LogUtil::debug("SOCKS4::decode - buffer length: " . strlen($buffer), $buffer);

        // 解析SOCKS4/4a请求
        $version = ord($buffer[0]);
        $command = ord($buffer[1]);
        $port = unpack('n', substr($buffer, 2, 2))[1];

        // 获取IP地址部分
        $ipBytes = substr($buffer, 4, 4);

        // 检查是否是SOCKS4a请求
        $isSocks4a = $ipBytes[0] === "\x00" && $ipBytes[1] === "\x00" && $ipBytes[2] === "\x00" && $ipBytes[3] !== "\x00";

        // 提取USERID
        $firstNullPos = strpos($buffer, "\0", 8);
        $userId = '';
        if ($firstNullPos > 8) {
            $userId = substr($buffer, 8, $firstNullPos - 8);
        }

        Container::getLogger()->debug("SOCKS4::decode - 请求解析: version={$version}, command={$command}, port={$port}, userId={$userId}, isSocks4a=" . ($isSocks4a ? 'true' : 'false'));

        // 对于SOCKS4a，提取和解析域名
        $hostname = '';
        $ip = '';

        if ($isSocks4a) {
            // 提取域名
            $secondNullPos = strpos($buffer, "\0", $firstNullPos + 1);
            if ($secondNullPos > $firstNullPos + 1) {
                $hostname = substr($buffer, $firstNullPos + 1, $secondNullPos - $firstNullPos - 1);

                // 尝试解析域名到IP地址
                try {
                    $resolvedIp = gethostbyname($hostname);
                    // 检查返回值是否为IP地址（如果解析失败，gethostbyname返回原始主机名）
                    if ($resolvedIp !== $hostname) {
                        $ip = $resolvedIp;
                    }
                } catch (\Throwable $e) {
                    // 域名解析失败，IP保持为空
                    Container::getLogger()->debug("SOCKS4::decode - 域名解析失败: " . $e->getMessage());
                }
            }
            
            Container::getLogger()->debug("SOCKS4::decode - SOCKS4a: hostname={$hostname}, resolved_ip={$ip}");
        } else {
            // 标准SOCKS4请求，直接使用提供的IP
            $ip = inet_ntop($ipBytes);
            Container::getLogger()->debug("SOCKS4::decode - SOCKS4: ip={$ip}");
        }

        // 储存请求信息在连接对象上
        SOCKS4Manager::setVersion($connection, $version);
        SOCKS4Manager::setCommand($connection, $command);
        SOCKS4Manager::setTargetIp($connection, $ip);
        SOCKS4Manager::setTargetPort($connection, $port);
        SOCKS4Manager::setUserId($connection, $userId);
        if ($isSocks4a) {
            SOCKS4Manager::setTargetHostname($connection, $hostname);
        }

        // 验证用户
        if (!self::isValidUser($userId)) {
            // 如果有提供 userId 但验证失败
            Container::getLogger()->debug("SOCKS4::decode - 用户验证失败: userId={$userId}");
            
            if (!empty($userId)) {
                // 构造拒绝响应
                $response = self::buildResponse(SOCKS4Response::REJECTED, $port, $ip ?: "0.0.0.0");
                $connection->send($response, true);
            } else {
                // 如果没有提供 userId，返回 IDENTD_FAILED（92）
                $response = self::buildResponse(SOCKS4Response::IDENTD_FAILED, $port, $ip ?: "0.0.0.0");
                $connection->send($response,  true);
            }
            return null;
        }

        // 对于SOCKS4a，如果域名解析失败
        if ($isSocks4a && empty($ip)) {
            Container::getLogger()->debug("SOCKS4::decode - SOCKS4a域名解析失败: hostname={$hostname}");
            
            // 构造拒绝响应
            $response = self::buildResponse(SOCKS4Response::REJECTED, $port, "0.0.0.0");
            $connection->send($response,  true);
            return null;
        }

        // 处理不支持的命令
        if ($command != SOCKS4Command::CONNECT->value) {
            Container::getLogger()->debug("SOCKS4::decode - 不支持的命令: command={$command}");

            // BIND 等命令目前不支持
            $response = self::buildResponse(SOCKS4Response::REJECTED, $port, $ip ?: "0.0.0.0");
            $connection->send($response,  true);
            return null;
        }

        Container::getLogger()->debug("SOCKS4::decode - 用户验证通过: userId={$userId}");
        $response = self::buildResponse(SOCKS4Response::GRANTED, $port, $ip ?: "0.0.0.0");
        $connection->send($response,  true);
        SOCKS4Manager::setStatus($connection, SOCKS4ConnectionStatus::ESTABLISHED);

        // 对于协议本身的解析，我们永不返回的
        return null;
    }

    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        // 获取当前连接状态
        $status = SOCKS4Manager::getStatus($connection) ?? SOCKS4ConnectionStatus::INITIAL;

        // 如果连接已建立，直接返回数据（透明传输）
        if ($status === SOCKS4ConnectionStatus::ESTABLISHED) {
            return $data;
        }

        // 初始阶段应该不会直接发送数据
        return '';
    }

    /**
     * 构建SOCKS4/4a响应
     *
     * @param SOCKS4Response $status 状态码
     * @param int $port 端口
     * @param string $ip IP地址
     * @return string 响应数据
     */
    private static function buildResponse(SOCKS4Response $status, int $port, string $ip): string
    {
        return pack('C', 0) . // VN: 0x00
               pack('C', $status->value) . // CD: 状态码
               pack('n', $port) . // 目标端口（网络字节序）
               inet_pton($ip); // 目标IP（4字节）
    }
}
