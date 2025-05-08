<?php

namespace Tourze\Workerman\SOCKS4\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * SOCKS4命令枚举
 */
enum SOCKS4Command: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case CONNECT = 0x01;
    case BIND = 0x02;

    public function getLabel(): string
    {
        return match($this) {
            self::CONNECT => '连接请求',
            self::BIND => '绑定请求',
        };
    }
}
