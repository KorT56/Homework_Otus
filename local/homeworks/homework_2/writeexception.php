<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Ошибка для exeption");

use App\Debug\Log;
use App\Debug\Exception;

// Создаем логгер для исключений
$logger = new Log($_SERVER["DOCUMENT_ROOT"] . "/local/homeworks/homework_2/exceptions.log");

try {
    // Выбрасываем наше кастомное исключение
    throw new Exception("Тестовое исключение для проверки логирования");
} catch (Exception $e) {
    // Логируем ошибку
    $logger->error("Перехвачено исключение: " . $e->getMessage() . " в файле " . $e->getFile() . " на строке " . $e->getLine());
}
?>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/local/homeworks/homework_2/exceptions.log" target="_blank">Файл лога</a>
    </li>
</ul>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
