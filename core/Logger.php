<?php

namespace Core;

class Logger
{
    private static $logFile = __DIR__ . '/../storage/logs/app.log';

    public static function log($level, $message, $context = [])
    {
        $date = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[{$date}] {$level}: {$message} {$contextStr}" . PHP_EOL;

        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(self::$logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }

    public static function warning($message, $context = [])
    {
        self::log('WARNING', $message, $context);
    }

    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }

    public static function debug($message, $context = [])
    {
        if (getenv('APP_DEBUG') === 'true') {
            self::log('DEBUG', $message, $context);
        }
    }
}