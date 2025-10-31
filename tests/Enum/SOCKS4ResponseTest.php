<?php

namespace Tourze\Workerman\SOCKS4\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Response;

/**
 * @internal
 */
#[CoversClass(SOCKS4Response::class)]
final class SOCKS4ResponseTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        // 测试枚举值是否符合SOCKS4协议规范
        $this->assertSame(0x5A, SOCKS4Response::GRANTED->value);
        $this->assertSame(0x5B, SOCKS4Response::REJECTED->value);
        $this->assertSame(0x5C, SOCKS4Response::IDENTD_FAILED->value);
    }

    public function testGetLabel(): void
    {
        // 测试各个枚举的标签文本
        $this->assertSame('请求被允许', SOCKS4Response::GRANTED->getLabel());
        $this->assertSame('请求被拒绝', SOCKS4Response::REJECTED->getLabel());
        $this->assertSame('认证失败', SOCKS4Response::IDENTD_FAILED->getLabel());
    }

    public function testGetCases(): void
    {
        // 测试获取所有枚举项目
        $items = SOCKS4Response::cases();

        $this->assertCount(3, $items);
        $this->assertContains(SOCKS4Response::GRANTED, $items);
        $this->assertContains(SOCKS4Response::REJECTED, $items);
        $this->assertContains(SOCKS4Response::IDENTD_FAILED, $items);
    }

    public function testToArray(): void
    {
        // 测试toArray方法返回简化的数组格式
        $array = SOCKS4Response::GRANTED->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame(0x5A, $array['value']);
        $this->assertSame('请求被允许', $array['label']);

        $array = SOCKS4Response::REJECTED->toArray();
        $this->assertSame(0x5B, $array['value']);
        $this->assertSame('请求被拒绝', $array['label']);

        $array = SOCKS4Response::IDENTD_FAILED->toArray();
        $this->assertSame(0x5C, $array['value']);
        $this->assertSame('认证失败', $array['label']);
    }
}
