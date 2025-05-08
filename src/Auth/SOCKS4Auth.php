<?php

namespace Tourze\Workerman\SOCKS4\Auth;

/**
 * SOCKS4认证管理器
 *
 * 负责用户认证相关的逻辑，在协议层和Worker层之间共享
 */
class SOCKS4Auth
{
    /**
     * 单例实例
     */
    private static ?self $instance = null;

    /**
     * 有效用户列表
     *
     * @var array<string, bool>
     */
    private array $validUsers = [];

    /**
     * 是否启用用户验证
     *
     * @var bool
     */
    private bool $enableAuthentication = false;

    /**
     * 私有构造函数，实现单例模式
     */
    private function __construct()
    {
    }

    /**
     * 获取实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 添加有效用户
     */
    public function addValidUser(string $userId): void
    {
        $this->validUsers[$userId] = true;
        $this->enableAuthentication = true;
    }

    /**
     * 检查用户是否有效
     */
    public function isValidUser(string $userId): bool
    {
        // 如果未启用验证，所有用户都有效
        if (!$this->enableAuthentication) {
            return true;
        }

        return isset($this->validUsers[$userId]) && $this->validUsers[$userId] === true;
    }

    /**
     * 设置是否启用验证
     */
    public function setEnableAuthentication(bool $enable): void
    {
        $this->enableAuthentication = $enable;
    }

    /**
     * 获取是否启用验证
     */
    public function isAuthenticationEnabled(): bool
    {
        return $this->enableAuthentication;
    }

    /**
     * 设置所有有效用户
     */
    public function setValidUsers(array $users): void
    {
        $this->validUsers = $users;
    }

    /**
     * 获取所有有效用户
     */
    public function getValidUsers(): array
    {
        return $this->validUsers;
    }
}
