<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("Добавление в лог");

Bitrix/Main/Diag/Debug:: writeToFile($_SERVER varName '$_SERVER', fileName: 'local/homeworks/homework_2/writelog.php');

?>
    <ul class="list-group">
        <li class="list-group-item">
            <a href="/local/logs/log_custom.log">Файл лога</a>,
            в лог добавленно 'Открыта страница writelog.php'
        </li>
    </ul>
<?
// ТУТ ДОБАВИТЬ СВОЮ ФУНКЦИЮ ДОБАВЛЕНИЯ В ЛОГ

?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>