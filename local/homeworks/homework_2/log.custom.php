<?php
namespace App\Debug;

use Bitrix\Main\Diag\FileLogger;

class Log extends FileLogger
{
    protected function format($message, array $context = []): string
    {
        $formattedMessage = parent::format($message, $context);
        
        // Добавляем слово OTUS в начало каждой строки
        return "OTUS " . $formattedMessage;
    }
}