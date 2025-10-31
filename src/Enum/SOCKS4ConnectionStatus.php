<?php

namespace Tourze\Workerman\SOCKS4\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 连接状态枚举
 */
enum SOCKS4ConnectionStatus: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case INITIAL = 0;
    case ESTABLISHED = 1;

    public function getLabel(): string
    {
        return match ($this) {
            self::INITIAL => '初始状态',
            self::ESTABLISHED => '已建立连接',
        };
    }
}
