<?php

namespace App\Core;

class Logger
{
    public static function info(string $channel, string $event, array $context = []): void
    {
        self::write($channel, $event, $context);
    }

    private static function write(string $channel, string $event, array $context = []): void
    {
        $logDirectory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true);
        }

        $logFile = $logDirectory . DIRECTORY_SEPARATOR . $channel . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $encodedContext = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        error_log(sprintf("[%s] %s %s\n", $timestamp, $event, $encodedContext), 3, $logFile);
    }
}
