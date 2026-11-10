<?php
// задаем пространство имен
namespace Otus\Debug;

// подключаем базовый класс
use Bitrix\Main\Diag\ExceptionHandlerLog;

// объявляем класс обработчика
class ExceptionHandler extends ExceptionHandlerLog
{
    // путь к файлу лога
    public const LOG_FILE = '/local/logs/exceptions.log';

    // обязательный метод инициализации
    public function initialize(array $options)
    {
        // ничего не делаем
    }

    // переопределяем метод записи
    public function write($exception, $logType)
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

        // форматируем ошибку
        $text = $this->formatException($exception);

        // формируем строку (без слова exception)
        $line = 'OTUS [' . date('Y-m-d H:i:s') . '] ' . $text . PHP_EOL;

        // пишем в файл
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    // метод очистки лога
    public static function clear(): void
    {
        // собираем полный путь
        $path = $_SERVER['DOCUMENT_ROOT'] . self::LOG_FILE;
        
        // проверяем наличие файла
        if (file_exists($path)) {
            // очищаем файл
            file_put_contents($path, '');
        }
    }

    // форматируем текст ошибки
    private function formatException(\Throwable $exception): string
    {
        // возвращаем только текст ошибки, файл и строку
        return sprintf(
            '%s in %s:%d',
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        );
    }

    // возвращаем тип лога (метод обязателен)
    public static function logTypeToString($logType): string
    {
        // возвращаем пустую строку
        return '';
    }
}