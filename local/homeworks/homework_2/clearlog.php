<?php
// подключаем шапку
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

// грузим класс логгера
\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Otus\\Debug\\Log' => '/local/App/Debug/Log.php',
]);

// вызываем метод очистки
\Otus\Debug\Log::clear();

// выводим сообщение об успехе
echo '<h3 style="color: green;">Лог успешно очищен!</h3>';
echo '<p>Можете закрыть эту вкладку и вернуться на главную страницу.</p>';

// завершаем работу скрипта
die;
?>
