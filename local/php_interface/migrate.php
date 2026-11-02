<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
global $USER;
if (!$USER->IsAdmin()) die("Доступ запрещен");

Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Custom\\Uslugi\\ServiceTable' => '/local/modules/custom.uslugi/lib/ServiceTable.php',
]);

use Custom\Uslugi\ServiceTable;
use Bitrix\Main\Loader;

echo "<h1>Миграция услуг (ИБ 25)</h1>";

ServiceTable::ensureTableExists();

if (Loader::includeModule('iblock')) {
    $iblockId = 25; // Тот самый ID
    
    // Запрашиваем только нужные свойства
    $rsElements = CIBlockElement::GetList(
        ["SORT" => "ASC"],
        ["IBLOCK_ID" => $iblockId, "ACTIVE" => "Y"],
        false,
        false,
        ["ID", "NAME", "PROPERTY_PRICE", "PROPERTY_NORMO_HOURS"]
    );

    $count = 0;
    while ($arItem = $rsElements->GetNext()) {
        $exists = ServiceTable::getList([
            'filter' => ['=NAME' => $arItem['NAME']],
            'limit' => 1
        ])->fetch();

        if (!$exists) {
            $result = ServiceTable::add([
                'NAME' => $arItem['NAME'],
                'PRICE' => $arItem['PROPERTY_PRICE_VALUE'] ?: null,
                'NORMO_HOURS' => $arItem['PROPERTY_NORMO_HOURS_VALUE'] ?: null,
                'DATE_CREATED' => new \Bitrix\Main\Type\DateTime(),
            ]);
            
            if ($result->isSuccess()) {
                $count++;
                echo "Добавлено: " . htmlspecialchars($arItem['NAME']) . "<br>";
            } else {
                echo "Ошибка '" . htmlspecialchars($arItem['NAME']) . "': " . implode(', ', $result->getErrorMessages()) . "<br>";
            }
        }
    }
    echo "<br><b>Готово! Успешно перенесено новых услуг: " . $count . "</b>";
}
?>