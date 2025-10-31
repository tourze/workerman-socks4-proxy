<?php

namespace Tourze\Workerman\SOCKS4\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4ConnectionStatus;

/**
 * @internal
 */
#[CoversClass(SOCKS4ConnectionStatus::class)]
final class SOCKS4ConnectionStatusTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        // 测试枚举值
        $this->assertSame(0, SOCKS4ConnectionStatus::INITIAL->value);
        $this->assertSame(1, SOCKS4ConnectionStatus::ESTABLISHED->value);
    }

    public function testGetLabel(): void
    {
        // 测试各个枚举的标签文本
        $this->assertSame('初始状态', SOCKS4ConnectionStatus::INITIAL->getLabel());
        $this->assertSame('已建立连接', SOCKS4ConnectionStatus::ESTABLISHED->getLabel());
    }

    public function testGetCases(): void
    {
        // 测试获取所有枚举项目
        $items = SOCKS4ConnectionStatus::cases();

        $this->assertCount(2, $items);
        $this->assertContains(SOCKS4ConnectionStatus::INITIAL, $items);
        $this->assertContains(SOCKS4ConnectionStatus::ESTABLISHED, $items);
    }

    public function testToArray(): void
    {
        // 测试toArray方法返回简化的数组格式
        $array = SOCKS4ConnectionStatus::INITIAL->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame(0, $array['value']);
        $this->assertSame('初始状态', $array['label']);

        $array = SOCKS4ConnectionStatus::ESTABLISHED->toArray();
        $this->assertSame(1, $array['value']);
        $this->assertSame('已建立连接', $array['label']);
    }
}
