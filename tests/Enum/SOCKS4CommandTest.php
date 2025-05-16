<?php

namespace Tourze\Workerman\SOCKS4\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Command;

class SOCKS4CommandTest extends TestCase
{
    public function testEnumValues()
    {
        // 测试枚举值是否符合SOCKS4协议规范
        $this->assertSame(0x01, SOCKS4Command::CONNECT->value);
        $this->assertSame(0x02, SOCKS4Command::BIND->value);
    }
    
    public function testGetLabel()
    {
        // 测试各个枚举的标签文本
        $this->assertSame('连接请求', SOCKS4Command::CONNECT->getLabel());
        $this->assertSame('绑定请求', SOCKS4Command::BIND->getLabel());
    }
    
    public function testGetCases()
    {
        // 测试获取所有枚举项目
        $items = SOCKS4Command::cases();
        
        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(SOCKS4Command::class, $items);
        $this->assertContains(SOCKS4Command::CONNECT, $items);
        $this->assertContains(SOCKS4Command::BIND, $items);
    }
    
    public function testGenOptions()
    {
        // 测试生成选项数组
        $options = SOCKS4Command::genOptions();
        
        $this->assertIsArray($options);
        $this->assertCount(2, $options);
        
        // 验证选项内容
        $foundConnect = false;
        $foundBind = false;
        
        foreach ($options as $option) {
            if ($option['value'] === 0x01) {
                $this->assertSame('连接请求', $option['label']);
                $foundConnect = true;
            }
            if ($option['value'] === 0x02) {
                $this->assertSame('绑定请求', $option['label']);
                $foundBind = true;
            }
        }
        
        $this->assertTrue($foundConnect, '未找到CONNECT选项');
        $this->assertTrue($foundBind, '未找到BIND选项');
    }
    
    public function testToSelectItem()
    {
        // 测试toSelectItem方法返回格式正确的数组
        $item = SOCKS4Command::CONNECT->toSelectItem();
        
        $this->assertIsArray($item);
        $this->assertArrayHasKey('label', $item);
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('text', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertSame('连接请求', $item['label']);
        $this->assertSame(0x01, $item['value']);
    }
} 