<?php
// подключаем шапку
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

// грузим класс обработчика
\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Otus\\Debug\\ExceptionHandler' => '/local/App/Debug/ExceptionHandler.php',
]);

// вызываем метод очистки
\Otus\Debug\ExceptionHandler::clear();

// выводим сообщение об успехе
echo '<h3 style="color: green;">Лог ошибок успешно очищен.</h3>';
echo '<p>Можно закрыть эту вкладку и вернуться на главную страницу.</p>';

// завершаем работу скрипта
die;
?>