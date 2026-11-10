<? 
// подключаем шапку
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
?>
<?php
// задаем заголовок
$APPLICATION->SetTitle("Добавление в лог");

//  класс
\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Otus\\Debug\\Log' => '/local/App/Debug/Log.php',
]);

// пишем в лог (дату и OTUS класс добавит сам)
\Otus\Debug\Log::write('Тестовая ошибка writelog.php');
?>
    <!-- выводим список -->
    <ul class="list-group">
        <li class="list-group-item">
            <!-- ссылка на лог -->
            <a href="/local/logs/log_custom.log">Файл лога</a>,
            <!-- текст сообщения -->
            в лог добавлена запись 'Тестовая ошибка writelog.php'
        </li>
    </ul>
<!-- подключаем подвал -->
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>