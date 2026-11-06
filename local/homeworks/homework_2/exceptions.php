
<?php
require_once __DIR__ . '/logger.php';

try {
    // Искусственно вызываем ошибку для демонстрации
    throw new Exception("Тестовое исключение: что-то пошло не так");
} catch (Exception $e) {
    // Ловим ошибку и пишем в отдельный лог
    writeToLog("Ошибка: " . $e->getMessage() . " в файле " . $e->getFile() . " на строке " . $e->getLine(), 'exceptions.log');
    echo "Записано в лог! <a href='exceptions.php'>Посмотреть</a>";
}