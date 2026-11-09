<?php
// включаем вывод ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// отключаем статистику
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
// подключаем ядро
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

// отдаем json
header('Content-Type: application/json');

// проверяем модуль
if (!Loader::includeModule('iblock')) {
    // выводим ошибку
    echo json_encode(['status' => 'error', 'message' => 'Модуль iblock не подключен']);
    // завершаем
    die();
}

// получаем фио
$fio            = trim($_POST['fio'] ?? '');
// получаем время
$dateTimeRaw    = trim($_POST['time'] ?? '');
// получаем id процедуры
$procedureId    = (int)($_POST['procedureId'] ?? 0);
// получаем целевой инфоблок
$targetIblock   = (int)($_POST['targetIblock'] ?? 0);
// получаем поле фио
$propFio        = trim($_POST['propFio'] ?? '');
// получаем поле даты
$propDate       = trim($_POST['propDate'] ?? '');
// получаем поле процедуры
$propProc       = trim($_POST['propProc'] ?? '');

// проверяем обязательные поля
if (empty($fio) || empty($dateTimeRaw) || !$procedureId || !$targetIblock || !$propDate || !$propProc) {
    // выводим ошибку
    echo json_encode(['status' => 'error', 'message' => 'Недостаточно данных для записи']);
    // завершаем
    die();
}

// преобразуем время
$timestamp = strtotime($dateTimeRaw);
// проверяем формат
if (!$timestamp) {
    // выводим ошибку
    echo json_encode(['status' => 'error', 'message' => 'Неверный формат даты/времени']);
    // завершаем
    die();
}

// форматируем дату
$timeToSave = ConvertTimeStamp($timestamp, "FULL"); 

// проверяем на прошлое
if ($timestamp < time()) {
    // выводим ошибку
    echo json_encode(['status' => 'error', 'message' => 'Нельзя записаться на прошедшую дату']);
    // завершаем
    die();
}

// ищем записи на это время
$checkResult = CIBlockElement::GetList(
    array(), 
    array(
        // инфоблок
        "IBLOCK_ID" => $targetIblock,
        // процедура
        "PROPERTY_" . $propProc => $procedureId,
        // от минус 30 мин
        ">=PROPERTY_" . $propDate => ConvertTimeStamp($timestamp - 1800, "FULL"),
        // до плюс 30 мин
        "<=PROPERTY_" . $propDate => ConvertTimeStamp($timestamp + 1800, "FULL")
    ),
    false, false, array("ID")
);
// если нашли запись
if ($checkResult->Fetch()) {
    // выводим ошибку
    echo json_encode(['status' => 'error', 'message' => 'Это время уже занято, выберите другое']);
    // завершаем
    die();
}

// создаем объект
$el = new CIBlockElement;

// собираем свойства
$PROP = [$propDate => $timeToSave];
// если есть поле фио
if (!empty($propFio)) {
    // добавляем фио
    $PROP[$propFio] = $fio;
}
// добавляем процедуру
$PROP[$propProc] = $procedureId;

// собираем поля
$arFields = [
    // инфоблок
    "IBLOCK_ID"      => $targetIblock,
    // название
    "NAME"           => $fio,
    // активность
    "ACTIVE"         => "Y",
    // свойства
    "PROPERTY_VALUES"=> $PROP,
];

// добавляем элемент
if ($productId = $el->Add($arFields)) {
    // проверяем календарь
    if (Loader::includeModule('calendar')) {
        // id пользователя
        $userId = 1; 
        // id раздела
        $sectionId = 1; 
        
        // настройки события
        $eventFields = [
            // название
            'NAME' => 'Запись: ' . $fio,
            // начало
            'DATE_FROM' => ConvertTimeStamp($timestamp, "FULL"),
            // конец
            'DATE_TO' => ConvertTimeStamp($timestamp + 3600, "FULL"),
            // описание
            'DESCRIPTION' => 'Процедура ID: ' . $procedureId,
            // раздел
            'SECTION_ID' => $sectionId,
            // тип
            'CAL_TYPE' => 'user',
            // владелец
            'OWNER_ID' => $userId,
            // создатель
            'CREATED_BY' => $userId,
            // доступность
            'ACCESSIBILITY' => 'busy',
            // важность
            'IMPORTANCE' => 'normal',
        ];
        
        // сохраняем событие
        \CCalendar::SaveEvent([
            // поля
            'arFields' => $eventFields,
            // пользователь
            'userId' => $userId,
        ]);
    }

    // формируем редирект
    $redirectUrl = "/services/lists/" . $targetIblock . "/view/0/";

    // возвращаем успех
    echo json_encode(['status' => 'success', 'id' => $productId, 'redirect' => $redirectUrl, 'message' => 'Вы успешно записаны!']);
} else {
    // возвращаем ошибку
    echo json_encode(['status' => 'error', 'message' => 'Ошибка: ' . $el->LAST_ERROR]);
}

// завершаем
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
?>