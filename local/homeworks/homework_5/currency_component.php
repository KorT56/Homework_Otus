<?php
// вызываем компонент и передаем параметры

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("курсы валют");

$APPLICATION->IncludeComponent(
    "table_component",
    "",
    ["PAGE_SIZE" => "2"] // по 2 элемента
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>