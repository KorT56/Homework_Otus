<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Добавление в лог");

// Подключаем классы
use App\Debug\Log;

// ВАЖНО: нужно указать полный путь к файлу лога на сервере
$logPath = $_SERVER["DOCUMENT_ROOT"] . "/local/homeworks/homework_2/custom.log";
$logger = new Log($logPath);

// Пишем сообщение
$logger->info('Открыта страница writelog.php');

?>
    <ul class="list-group">
        <li class="list-group-item">
            <a href="/local/homeworks/homework_2/custom.log" target="_blank">Файл лога</a>,
            в лог добавлена запись 'Открыта страница writelog.php'
        </li>
    </ul>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>