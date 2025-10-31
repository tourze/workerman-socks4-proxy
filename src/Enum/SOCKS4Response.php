<?php

namespace Tourze\Workerman\SOCKS4\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * SOCKS4响应码枚举
 */
enum SOCKS4Response: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case GRANTED = 0x5A; // 90
    case REJECTED = 0x5B; // 91
    case IDENTD_FAILED = 0x5C; // 92

    public function getLabel(): string
    {
        return match ($this) {
            self::GRANTED => '请求被允许',
            self::REJECTED => '请求被拒绝',
            self::IDENTD_FAILED => '认证失败',
        };
    }
}
