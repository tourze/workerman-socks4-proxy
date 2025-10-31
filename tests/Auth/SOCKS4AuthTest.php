<?php

namespace Tourze\Workerman\SOCKS4\Tests\Auth;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\SOCKS4\Auth\SOCKS4Auth;

/**
 * @internal
 */
#[CoversClass(SOCKS4Auth::class)]
final class SOCKS4AuthTest extends TestCase
{
    private SOCKS4Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();
        // 获取单例实例
        $this->auth = SOCKS4Auth::getInstance();

        // 重置认证配置
        $this->auth->setEnableAuthentication(false);
        $this->auth->setValidUsers([]);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        // 测试单例模式，确保每次调用 getInstance 返回相同实例
        $instance1 = SOCKS4Auth::getInstance();
        $instance2 = SOCKS4Auth::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testIsValidUserWhenAuthDisabledReturnsTrue(): void
    {
        // 当验证禁用时，任何用户都应该有效
        $this->auth->setEnableAuthentication(false);

        $this->assertTrue($this->auth->isValidUser('anyuser'));
        $this->assertTrue($this->auth->isValidUser(''));
    }

    public function testIsValidUserWithValidUserReturnsTrue(): void
    {
        // 启用验证并添加有效用户
        $this->auth->setEnableAuthentication(true);
        $this->auth->addValidUser('testuser');

        $this->assertTrue($this->auth->isValidUser('testuser'));
    }

    public function testIsValidUserWithInvalidUserReturnsFalse(): void
    {
        // 启用验证后，未添加的用户应该是无效的
        $this->auth->setEnableAuthentication(true);
        $this->auth->addValidUser('testuser');

        $this->assertFalse($this->auth->isValidUser('invaliduser'));
    }

    public function testSetEnableAuthenticationChangesAuthenticationState(): void
    {
        // 测试启用/禁用验证状态更改
        $this->auth->setEnableAuthentication(true);
        $this->assertTrue($this->auth->isAuthenticationEnabled());

        $this->auth->setEnableAuthentication(false);
        $this->assertFalse($this->auth->isAuthenticationEnabled());
    }

    public function testAddValidUserAutoEnablesAuthentication(): void
    {
        // 添加有效用户时应自动启用验证
        $this->auth->setEnableAuthentication(false);
        $this->assertFalse($this->auth->isAuthenticationEnabled());

        $this->auth->addValidUser('newuser');

        // 验证应该自动启用
        $this->assertTrue($this->auth->isAuthenticationEnabled());
        // 用户应该已添加到有效列表
        $this->assertTrue($this->auth->isValidUser('newuser'));
    }

    public function testSetValidUsersReplacesAllUsers(): void
    {
        // 先添加一个用户
        $this->auth->addValidUser('user1');

        // 设置新的用户列表，替换现有列表
        $newUsers = ['user2' => true, 'user3' => true];
        $this->auth->setValidUsers($newUsers);

        // 验证原始用户不再有效
        $this->assertFalse($this->auth->isValidUser('user1'));

        // 验证新用户有效
        $this->assertTrue($this->auth->isValidUser('user2'));
        $this->assertTrue($this->auth->isValidUser('user3'));
    }

    public function testGetValidUsersReturnsUsersList(): void
    {
        // 添加几个有效用户
        $this->auth->addValidUser('user1');
        $this->auth->addValidUser('user2');

        // 获取有效用户列表
        $users = $this->auth->getValidUsers();

        // 验证返回的列表包含添加的用户
        $this->assertArrayHasKey('user1', $users);
        $this->assertArrayHasKey('user2', $users);
        $this->assertTrue($users['user1']);
        $this->assertTrue($users['user2']);
    }
}
