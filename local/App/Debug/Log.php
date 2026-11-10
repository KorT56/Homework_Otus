<?php
// задаем пространство имен
namespace Otus\Debug;

// объявляем класс логгера
class Log
{
    // путь к файлу лога
    public const LOG_FILE = '/local/logs/log_custom.log';

    // метод записи в лог
    public static function write(string $message): void
    {
        // собираем полный путь
        $path = $_SERVER['DOCUMENT_ROOT'] . self::LOG_FILE;
        
        // получаем имя папки
        $dir = dirname($path);
        
        // проверяем наличие папки
        if (!is_dir($dir)) {
            // создаем папку
            mkdir($dir, 0775, true);
        }

        // узнаем, откуда вызван метод (файл и строка)
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $callerFile = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : 'unknown';
        $callerLine = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : '0';

        // формируем строку с OTUS, датой, сообщением, файлом и строкой
        $line = 'OTUS [' . date('Y-m-d H:i:s') . '] ' . $message . ' in ' . $callerFile . ':' . $callerLine . PHP_EOL;
        
        // проверяем, существует ли файл и пуст ли он
        $isNew = !file_exists($path) || filesize($path) === 0;
        
        // если файл новый, добавляем невидимый маркер UTF-8 (BOM)
        if ($isNew) {
            $line = "\xEF\xBB\xBF" . $line;
        }

        // пишем строку в файл
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    // метод очистки лога
    public static function clear(): void
    {
        // собираем полный путь
        $path = $_SERVER['DOCUMENT_ROOT'] . self::LOG_FILE;
        
        // проверяем наличие файла
        if (file_exists($path)) {
            // очищаем содержимое файла
            file_put_contents($path, '');
        }
    }
}