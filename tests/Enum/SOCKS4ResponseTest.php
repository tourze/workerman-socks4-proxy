<?php

namespace Tourze\Workerman\SOCKS4\Tests\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\SOCKS4\Enum\SOCKS4Response;

class SOCKS4ResponseTest extends TestCase
{
    public function testEnumValues()
    {
        // 测试枚举值是否符合SOCKS4协议规范
        $this->assertSame(0x5a, SOCKS4Response::GRANTED->value);
        $this->assertSame(0x5b, SOCKS4Response::REJECTED->value);
        $this->assertSame(0x5c, SOCKS4Response::IDENTD_FAILED->value);
    }
    
    public function testGetLabel()
    {
        // 测试各个枚举的标签文本
        $this->assertSame('请求被允许', SOCKS4Response::GRANTED->getLabel());
        $this->assertSame('请求被拒绝', SOCKS4Response::REJECTED->getLabel());
        $this->assertSame('认证失败', SOCKS4Response::IDENTD_FAILED->getLabel());
    }
    
    public function testGetCases()
    {
        // 测试获取所有枚举项目
        $items = SOCKS4Response::cases();
        
        $this->assertCount(3, $items);
        $this->assertContainsOnlyInstancesOf(SOCKS4Response::class, $items);
        $this->assertContains(SOCKS4Response::GRANTED, $items);
        $this->assertContains(SOCKS4Response::REJECTED, $items);
        $this->assertContains(SOCKS4Response::IDENTD_FAILED, $items);
    }
    
    public function testGenOptions()
    {
        // 测试生成选项数组
        $options = SOCKS4Response::genOptions();
        
        $this->assertIsArray($options);
        $this->assertCount(3, $options);
        
        // 验证选项内容
        $foundGranted = false;
        $foundRejected = false;
        $foundIdentdFailed = false;
        
        foreach ($options as $option) {
            if ($option['value'] === 0x5a) {
                $this->assertSame('请求被允许', $option['label']);
                $foundGranted = true;
            }
            if ($option['value'] === 0x5b) {
                $this->assertSame('请求被拒绝', $option['label']);
                $foundRejected = true;
            }
            if ($option['value'] === 0x5c) {
                $this->assertSame('认证失败', $option['label']);
                $foundIdentdFailed = true;
            }
        }
        
        $this->assertTrue($foundGranted, '未找到GRANTED选项');
        $this->assertTrue($foundRejected, '未找到REJECTED选项');
        $this->assertTrue($foundIdentdFailed, '未找到IDENTD_FAILED选项');
    }
    
    public function testToSelectItem()
    {
        // 测试toSelectItem方法返回格式正确的数组
        $item = SOCKS4Response::GRANTED->toSelectItem();
        
        $this->assertIsArray($item);
        $this->assertArrayHasKey('label', $item);
        $this->assertArrayHasKey('value', $item);
        $this->assertArrayHasKey('text', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertSame('请求被允许', $item['label']);
        $this->assertSame(0x5a, $item['value']);
    }
} 