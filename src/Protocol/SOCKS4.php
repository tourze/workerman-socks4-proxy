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
 *    1    1      2              4           可变长度       1
 *
 * SOCKS4a 扩展格式:
 * +----+----+----+----+----+----+----+----+----+----+....+----+----+....+----+
 * | VN | CD | DSTPORT |      DSTIP        | USERID       |NULL| HOSTNAME |NULL|
 * +----+----+----+----+----+----+----+----+----+----+....+----+----+....+----+
 *    1    1      2              4           可变长度       1    可变长度    1
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
     */
    public static function isValidUser(string $userId): bool
    {
        return SOCKS4Auth::getInstance()->isValidUser($userId);
    }

    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        LogUtil::debug('SOCKS4::input', $buffer);

        $status = self::initializeConnectionStatus($connection);

        if (SOCKS4ConnectionStatus::ESTABLISHED === $status) {
            return strlen($buffer);
        }

        return self::validateAndParseRequest($buffer);
    }

    public static function decode(string $buffer, ConnectionInterface $connection): mixed
    {
        $status = SOCKS4Manager::getStatus($connection) ?? SOCKS4ConnectionStatus::INITIAL;

        if (SOCKS4ConnectionStatus::ESTABLISHED === $status) {
            return $buffer;
        }

        LogUtil::debug('SOCKS4::decode - buffer length: ' . strlen($buffer), $buffer);

        $requestData = self::parseRequestData($buffer);
        $addressInfo = self::resolveAddress($requestData, $buffer);

        self::storeConnectionInfo($connection, $requestData, $addressInfo);

        return self::processRequest($connection, $requestData, $addressInfo);
    }

    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        // 获取当前连接状态
        $status = SOCKS4Manager::getStatus($connection) ?? SOCKS4ConnectionStatus::INITIAL;

        // 如果连接已建立，直接返回数据（透明传输）
        if (SOCKS4ConnectionStatus::ESTABLISHED === $status) {
            return $data;
        }

        // 初始阶段应该不会直接发送数据
        return '';
    }

    /**
     * 初始化连接状态
     */
    private static function initializeConnectionStatus(ConnectionInterface $connection): SOCKS4ConnectionStatus
    {
        $status = SOCKS4Manager::getStatus($connection);
        if (null === $status) {
            $status = SOCKS4ConnectionStatus::INITIAL;
            SOCKS4Manager::setStatus($connection, $status);
        }

        return $status;
    }

    /**
     * 验证和解析请求
     */
    private static function validateAndParseRequest(string $buffer): int
    {
        $length = strlen($buffer);

        if ($length < 9) {
            Container::getLogger()->debug('SOCKS4::input - 数据不完整，长度小于9字节: ' . $length);

            return 0;
        }

        $version = ord($buffer[0]);
        if (0x04 !== $version) {
            Container::getLogger()->debug('SOCKS4::input - 非SOCKS4协议，版本号: ' . $version);

            return -1;
        }

        return self::findRequestEndPosition($buffer, $length);
    }

    /**
     * 查找请求结束位置
     */
    private static function findRequestEndPosition(string $buffer, int $length): int
    {
        $firstNullPos = strpos($buffer, "\0", 8);
        if (false === $firstNullPos) {
            if ($length > 256) {
                Container::getLogger()->debug('SOCKS4::input - 找不到USERID结束的NULL字节，数据可能格式错误');

                return -1;
            }
            Container::getLogger()->debug('SOCKS4::input - 等待更多数据以找到USERID结尾');

            return 0;
        }

        $possibleSocks4a = "\x00\x00\x00" === substr($buffer, 4, 3) && 0 !== ord($buffer[7]);

        if ($possibleSocks4a) {
            return self::handleSocks4aRequest($buffer, $firstNullPos, $length);
        }

        return $firstNullPos + 1;
    }

    /**
     * 处理SOCKS4a请求
     */
    private static function handleSocks4aRequest(string $buffer, int $firstNullPos, int $length): int
    {
        $secondNullPos = strpos($buffer, "\0", $firstNullPos + 1);
        if (false === $secondNullPos) {
            if ($length > 1024) {
                return -1;
            }

            return 0;
        }

        return $secondNullPos + 1;
    }

    /**
     * 解析请求数据
     *
     * @return array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false}
     */
    private static function parseRequestData(string $buffer): array
    {
        $version = ord($buffer[0]);
        $command = ord($buffer[1]);
        $portData = unpack('n', substr($buffer, 2, 2));
        $port = false !== $portData ? $portData[1] : 0;
        $ipBytes = substr($buffer, 4, 4);
        $isSocks4a = "\x00" === $ipBytes[0] && "\x00" === $ipBytes[1] && "\x00" === $ipBytes[2] && "\x00" !== $ipBytes[3];

        $firstNullPos = strpos($buffer, "\0", 8);
        $userId = $firstNullPos > 8 ? substr($buffer, 8, $firstNullPos - 8) : '';

        Container::getLogger()->debug("SOCKS4::decode - 请求解析: version={$version}, command={$command}, port={$port}, userId={$userId}, isSocks4a=" . ($isSocks4a ? 'true' : 'false'));

        return [
            'version' => $version,
            'command' => $command,
            'port' => $port,
            'ipBytes' => $ipBytes,
            'isSocks4a' => $isSocks4a,
            'userId' => $userId,
            'firstNullPos' => $firstNullPos,
        ];
    }

    /**
     * 解析地址信息
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     * @return array{hostname: string, ip: string}
     */
    private static function resolveAddress(array $requestData, string $buffer): array
    {
        $hostname = '';
        $ip = '';

        if ($requestData['isSocks4a']) {
            $addressInfo = self::resolveSocks4aAddress($requestData, $buffer);
            $hostname = $addressInfo['hostname'];
            $ip = $addressInfo['ip'];
        } else {
            $ipResult = inet_ntop($requestData['ipBytes']);
            $ip = false !== $ipResult ? $ipResult : '0.0.0.0';
            Container::getLogger()->debug("SOCKS4::decode - SOCKS4: ip={$ip}");
        }

        return ['hostname' => $hostname, 'ip' => $ip];
    }

    /**
     * 解析SOCKS4a地址
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     * @return array{hostname: string, ip: string}
     */
    private static function resolveSocks4aAddress(array $requestData, string $buffer): array
    {
        $firstNullPos = $requestData['firstNullPos'];
        if (false === $firstNullPos) {
            return ['hostname' => '', 'ip' => ''];
        }
        $secondNullPos = strpos($buffer, "\0", $firstNullPos + 1);
        $hostname = '';
        $ip = '';

        if (false !== $secondNullPos && $secondNullPos > $firstNullPos + 1) {
            $hostname = substr($buffer, $firstNullPos + 1, $secondNullPos - $firstNullPos - 1);

            try {
                $resolvedIp = gethostbyname($hostname);
                if ($resolvedIp !== $hostname) {
                    $ip = $resolvedIp;
                }
            } catch (\Throwable $e) {
                Container::getLogger()->debug('SOCKS4::decode - 域名解析失败: ' . $e->getMessage());
            }
        }

        Container::getLogger()->debug("SOCKS4::decode - SOCKS4a: hostname={$hostname}, resolved_ip={$ip}");

        return ['hostname' => $hostname, 'ip' => $ip];
    }

    /**
     * 存储连接信息
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     * @param array{hostname: string, ip: string} $addressInfo
     */
    private static function storeConnectionInfo(ConnectionInterface $connection, array $requestData, array $addressInfo): void
    {
        SOCKS4Manager::setVersion($connection, $requestData['version']);
        SOCKS4Manager::setCommand($connection, $requestData['command']);
        SOCKS4Manager::setTargetIp($connection, $addressInfo['ip']);
        SOCKS4Manager::setTargetPort($connection, $requestData['port']);
        SOCKS4Manager::setUserId($connection, $requestData['userId']);

        if ($requestData['isSocks4a']) {
            SOCKS4Manager::setTargetHostname($connection, $addressInfo['hostname']);
        }
    }

    /**
     * 处理请求
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     * @param array{hostname: string, ip: string} $addressInfo
     */
    private static function processRequest(ConnectionInterface $connection, array $requestData, array $addressInfo): mixed
    {
        if (!self::isValidUser($requestData['userId'])) {
            return self::handleInvalidUser($connection, $requestData, $addressInfo);
        }

        if ($requestData['isSocks4a'] && ('' === $addressInfo['ip'] || null === $addressInfo['ip'])) {
            return self::handleDnsResolutionFailure($connection, $requestData);
        }

        if ($requestData['command'] !== SOCKS4Command::CONNECT->value) {
            return self::handleUnsupportedCommand($connection, $requestData, $addressInfo);
        }

        return self::handleSuccessfulRequest($connection, $requestData, $addressInfo);
    }

    /**
     * 处理无效用户
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     * @param array{hostname: string, ip: string} $addressInfo
     */
    private static function handleInvalidUser(ConnectionInterface $connection, array $requestData, array $addressInfo): mixed
    {
        Container::getLogger()->debug("SOCKS4::decode - 用户验证失败: userId={$requestData['userId']}");

        $responseType = ('' === $requestData['userId'] || null === $requestData['userId']) ? SOCKS4Response::IDENTD_FAILED : SOCKS4Response::REJECTED;
        $ip = ('' !== $addressInfo['ip'] && null !== $addressInfo['ip']) ? $addressInfo['ip'] : '0.0.0.0';
        $response = self::buildResponse($responseType, $requestData['port'], $ip);
        $connection->send($response, true);

        return null;
    }

    /**
     * 处理DNS解析失败
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     */
    private static function handleDnsResolutionFailure(ConnectionInterface $connection, array $requestData): mixed
    {
        Container::getLogger()->debug('SOCKS4::decode - SOCKS4a域名解析失败');

        $response = self::buildResponse(SOCKS4Response::REJECTED, $requestData['port'], '0.0.0.0');
        $connection->send($response, true);

        return null;
    }

    /**
     * 处理不支持的命令
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     * @param array{hostname: string, ip: string} $addressInfo
     */
    private static function handleUnsupportedCommand(ConnectionInterface $connection, array $requestData, array $addressInfo): mixed
    {
        Container::getLogger()->debug("SOCKS4::decode - 不支持的命令: command={$requestData['command']}");

        $ip = ('' !== $addressInfo['ip'] && null !== $addressInfo['ip']) ? $addressInfo['ip'] : '0.0.0.0';
        $response = self::buildResponse(SOCKS4Response::REJECTED, $requestData['port'], $ip);
        $connection->send($response, true);

        return null;
    }

    /**
     * 处理成功请求
     *
     * @param array{version: int, command: int, port: int, ipBytes: string, isSocks4a: bool, userId: string, firstNullPos: int|false} $requestData
     * @param array{hostname: string, ip: string} $addressInfo
     */
    private static function handleSuccessfulRequest(ConnectionInterface $connection, array $requestData, array $addressInfo): mixed
    {
        Container::getLogger()->debug("SOCKS4::decode - 用户验证通过: userId={$requestData['userId']}");

        $ip = ('' !== $addressInfo['ip'] && null !== $addressInfo['ip']) ? $addressInfo['ip'] : '0.0.0.0';
        $response = self::buildResponse(SOCKS4Response::GRANTED, $requestData['port'], $ip);
        $connection->send($response, true);
        SOCKS4Manager::setStatus($connection, SOCKS4ConnectionStatus::ESTABLISHED);

        return null;
    }

    /**
     * 构建SOCKS4/4a响应
     *
     * @param SOCKS4Response $status 状态码
     * @param int            $port   端口
     * @param string         $ip     IP地址
     *
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
