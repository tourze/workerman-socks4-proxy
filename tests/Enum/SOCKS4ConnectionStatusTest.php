<?php

namespace Tourze\Workerman\SOCKS4\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4ConnectionStatus;

class SOCKS4ConnectionStatusTest extends TestCase
{
    public function testEnumValues()
    {
        // 测试枚举值
        $this->assertSame(0, SOCKS4ConnectionStatus::INITIAL->value);
        $this->assertSame(1, SOCKS4ConnectionStatus::ESTABLISHED->value);
    }
    
    public function testGetLabel()
    {
        // 测试各个枚举的标签文本
        $this->assertSame('初始状态', SOCKS4ConnectionStatus::INITIAL->getLabel());
        $this->assertSame('已建立连接', SOCKS4ConnectionStatus::ESTABLISHED->getLabel());
    }
    
    public function testGetCases()
    {
        // 测试获取所有枚举项目
        $items = SOCKS4ConnectionStatus::cases();
        
        $this->assertCount(2, $items);
        $this->assertContainsOnlyInstancesOf(SOCKS4ConnectionStatus::class, $items);
        $this->assertContains(SOCKS4ConnectionStatus::INITIAL, $items);
        $this->assertContains(SOCKS4ConnectionStatus::ESTABLISHED, $items);
    }
    
    public function testGenOptions()
    {
        // 测试生成选项数组
        $options = SOCKS4ConnectionStatus::genOptions();
        $this->assertCount(2, $options);
        
        // 验证选项内容
        $foundInitial = false;
        $foundEstablished = false;
        
        foreach ($options as $option) {
            if ($option['value'] === 0) {
                $this->assertSame('初始状态', $option['label']);
                $foundInitial = true;
            }
            if ($option['value'] === 1) {
                $this->assertSame('已建立连接', $option['label']);
                $foundEstablished = true;
            }
        }
        
        $this->assertTrue($foundInitial, '未找到INITIAL选项');
        $this->assertTrue($foundEstablished, '未找到ESTABLISHED选项');
    }
    
    public function testToSelectItem()
    {
        // 测试toSelectItem方法返回格式正确的数组
        $item = SOCKS4ConnectionStatus::INITIAL->toSelectItem();
        $this->assertArrayHasKey('label', $item);
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('text', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertSame('初始状态', $item['label']);
        $this->assertSame(0, $item['value']);
    }
} 