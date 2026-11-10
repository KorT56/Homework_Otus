<?php
// возвращаем массив с настройками
return [
    'exception_handling' => [
        'value' => [
            // выключаем вывод ошибок на экран
            'debug' => false, 
            'handled_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED,
            'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED,
            'ignore_silence' => false,
            'assertion_throws_exception' => true,
            'assertion_error_type' => 256,
            'log' => [
                'settings' => [
                    // ВАЖНО: указываем полный путь от корня сайта
                    'file' => '/local/logs/exceptions.log', 
                    'log_size' => 1000000,
                ],
                // ВАЖНО: указываем класс именно ExceptionHandler, а не Log!
                'class_name' => \Otus\Debug\ExceptionHandler::class,
                'extension' => 'Otus\\Debug',
                // это можно оставить как есть
                'required_file' => 'lib/App/Debug/ExceptionHandler.php', 
            ],
        ],
        'readonly' => false,
    ],
];
