<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

// Путь к файлу лога (замени на свой, если он лежит в другой папке)
$logFile = $_SERVER["DOCUMENT_ROOT"] . "/local/homeworks/homework_2/custom.log";

// Проверяем, существует ли файл, и удаляем его
if (file_exists($logFile)) {
    unlink($logFile); 
}

// Перенаправляем пользователя обратно на страницу с домашками
LocalRedirect('/local/homeworks/homework_2/');

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>
