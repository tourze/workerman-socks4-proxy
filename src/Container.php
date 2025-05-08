<?php

namespace Tourze\Workerman\SOCKS4;

use Psr\Log\LoggerInterface;
use Tourze\Workerman\PsrLogger\WorkermanLogger;

class Container
{
    private static ?LoggerInterface $logger = null;

    public static function getLogger(): LoggerInterface
    {
        return self::$logger ?? new WorkermanLogger();
    }

    public static function setLogger(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }
}
