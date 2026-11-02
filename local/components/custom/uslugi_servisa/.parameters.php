<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); // защита от прямого вызова

use Bitrix\Main\Loader; // подключаем лоадер
if (!Loader::includeModule('iblock')) return; // если нет модуля инфоблоков - выходим

$arComponentParameters = [ // массив с настройками компонента
    "PARAMETERS" => [ // группа параметров
        "IBLOCK_ID" => [ // параметр "ID инфоблока с услугами"
            "PARENT" => "BASE", // в какой вкладке показывать
            "NAME" => "Инфоблок услуг", // подпись поля в настройках
            "TYPE" => "LIST", // тип поля - выпадающий список
            "VALUES" => [], // заполним ниже
            "REFRESH" => "Y", // обновлять настройки при выборе
        ],
        "PARTS_IBLOCK_ID" => [ // параметр "ID инфоблока с запчастями"
            "PARENT" => "BASE", // вкладка
            "NAME" => "Инфоблок запчастей", // подпись
            "TYPE" => "LIST", // выпадающий список
            "VALUES" => [], // заполним ниже
        ],
        "DETAIL_URL" => [ // параметр "URL детальной страницы"
            "PARENT" => "BASE", // вкладка
            "NAME" => "URL детальной страницы (шаблон)", // подпись
            "TYPE" => "STRING", // строковое поле
            "DEFAULT" => "/services/#ID#/", // значение по умолчанию
        ],
        "GRID_ID" => [ // ID грида
            "PARENT" => "BASE", // вкладка
            "NAME" => "ID грида (уникальный)", // подпись
            "TYPE" => "STRING", // строка
            "DEFAULT" => "uslugi_servisa_grid", // по умолчанию
        ],
    ],
];

// Получаем список инфоблоков для выпадающих списков
$rsIBlocks = CIBlock::GetList(['NAME' => 'ASC'], ['ACTIVE' => 'Y']); // запрос всех активных инфоблоков
while ($arIBlock = $rsIBlocks->Fetch()) { // перебираем их
    $arComponentParameters["PARAMETERS"]["IBLOCK_ID"]["VALUES"][$arIBlock["ID"]] = $arIBlock["NAME"]; // заполняем список для услуг
    $arComponentParameters["PARAMETERS"]["PARTS_IBLOCK_ID"]["VALUES"][$arIBlock["ID"]] = $arIBlock["NAME"]; // заполняем список для запчастей
}