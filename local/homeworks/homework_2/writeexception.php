<? 

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
// задаем заголовок
$APPLICATION->SetTitle("Ошибка для exeption");
?>
    <!-- выводим список -->
    <ul class="list-group">
        <li class="list-group-item">
            <!-- ссылка на лог -->
            <a href="/local/logs/exceptions.log">Файл лога</a>
        </li>
    </ul>
<?
// кидаем ошибку
throw new \RuntimeException('OTUS Страница открыта - writeexception.php: ' . date('Y-m-d H:i:s'));
?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>