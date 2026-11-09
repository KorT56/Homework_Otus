<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

header('Content-Type: application/json');

if (!Loader::includeModule('iblock')) {
    echo json_encode(['status' => 'error', 'message' => 'Модуль iblock не подключен']);
    die();
}

// получаем данные из запроса
$fio            = trim($_POST['fio'] ?? '');
$dateTimeRaw    = trim($_POST['time'] ?? '');
$procedureId    = (int)($_POST['procedureId'] ?? 0);
$targetIblock   = (int)($_POST['targetIblock'] ?? 0);
$propFio        = trim($_POST['propFio'] ?? '');
$propDate       = trim($_POST['propDate'] ?? '');
$propProc       = trim($_POST['propProc'] ?? '');

if (empty($fio) || empty($dateTimeRaw) || !$procedureId || !$targetIblock || !$propDate || !$propProc) {
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных для записи']);
    die();
}

$timestamp = strtotime($dateTimeRaw);
if (!$timestamp) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный формат даты/времени']);
    die();
}

$timeToSave = ConvertTimeStamp($timestamp, "FULL"); 

if ($timestamp < time()) {
    echo json_encode(['status' => 'error', 'message' => 'Нельзя записаться на прошедшую дату']);
    die();
}

// проверка на занятое время (динамическая)
$checkResult = CIBlockElement::GetList(
    array(), 
    array(
        "IBLOCK_ID" => $targetIblock,
        "PROPERTY_" . $propProc => $procedureId,
        ">=PROPERTY_" . $propDate => ConvertTimeStamp($timestamp - 1800, "FULL"),
        "<=PROPERTY_" . $propDate => ConvertTimeStamp($timestamp + 1800, "FULL")
    ),
    false, false, array("ID")
);
if ($checkResult->Fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Это время уже занято, выберите другое']);
    die();
}

// создаем объект элемента
$el = new CIBlockElement;

// собираем свойства динамически
$PROP = [$propDate => $timeToSave];
if (!empty($propFio)) {
    $PROP[$propFio] = $fio;
}
$PROP[$propProc] = $procedureId;

// собираем поля
$arFields = [
    "IBLOCK_ID"      => $targetIblock,
    "NAME"           => $fio,
    "ACTIVE"         => "Y",
    "PROPERTY_VALUES"=> $PROP,
];

if ($productId = $el->Add($arFields)) {
    // создаем событие в календаре
    if (Loader::includeModule('calendar')) {
        $userId = 1; 
        $sectionId = 1; 
        
        $eventFields = [
            'NAME' => 'Запись: ' . $fio,
            'DATE_FROM' => ConvertTimeStamp($timestamp, "FULL"),
            'DATE_TO' => ConvertTimeStamp($timestamp + 3600, "FULL"),
            'DESCRIPTION' => 'Процедура ID: ' . $procedureId,
            'SECTION_ID' => $sectionId,
            'CAL_TYPE' => 'user',
            'OWNER_ID' => $userId,
            'CREATED_BY' => $userId,
            'ACCESSIBILITY' => 'busy',
            'IMPORTANCE' => 'normal',
        ];
        
        \CCalendar::SaveEvent([
            'arFields' => $eventFields,
            'userId' => $userId,
        ]);
    }

    // формируем ссылку на список для редиректа
    $redirectUrl = "/services/lists/" . $targetIblock . "/view/0/";

    echo json_encode(['status' => 'success', 'id' => $productId, 'redirect' => $redirectUrl, 'message' => 'Вы успешно записаны!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка: ' . $el->LAST_ERROR]);
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
?>