<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Ошибка для exeption");

use Bitrix\Main\Diag\Debug;

// Ловим ошибку и пишем в лог
try {
    // Искусственно создаем ошибку
    throw new Exception("Это тестовое исключение для домашнего задания.");
} catch (Exception $e) {
    // Записываем данные об ошибке в файл
    Debug::writeToFile(
        'Текст ошибки', // Заголовок записи
        [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 
        '/local/logs/exceptions.log' // Путь к файлу лога
    );
}
?>
<ul class="list-group">
    <li class="list-group-item">
        <a href="/local/logs/exceptions.log" target="_blank">Файл лога</a>
    </li>
</ul>
<?
// ошибка для exeption (уже обработана выше)

?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>