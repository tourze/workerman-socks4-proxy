<?php

namespace Tourze\Workerman\SOCKS4\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Command;

/**
 * @internal
 */
#[CoversClass(SOCKS4Command::class)]
final class SOCKS4CommandTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        // 测试枚举值是否符合SOCKS4协议规范
        $this->assertSame(0x01, SOCKS4Command::CONNECT->value);
        $this->assertSame(0x02, SOCKS4Command::BIND->value);
    }

    public function testGetLabel(): void
    {
        // 测试各个枚举的标签文本
        $this->assertSame('连接请求', SOCKS4Command::CONNECT->getLabel());
        $this->assertSame('绑定请求', SOCKS4Command::BIND->getLabel());
    }

    public function testGetCases(): void
    {
        // 测试获取所有枚举项目
        $items = SOCKS4Command::cases();

        $this->assertCount(2, $items);
        $this->assertContains(SOCKS4Command::CONNECT, $items);
        $this->assertContains(SOCKS4Command::BIND, $items);
    }

    public function testToArray(): void
    {
        // 测试toArray方法返回简化的数组格式
        $array = SOCKS4Command::CONNECT->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame(0x01, $array['value']);
        $this->assertSame('连接请求', $array['label']);

        $array = SOCKS4Command::BIND->toArray();
        $this->assertSame(0x02, $array['value']);
        $this->assertSame('绑定请求', $array['label']);
    }
}
