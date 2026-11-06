<?php
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader;

header('Content-Type: application/json');

if (!Loader::includeModule('iblock')) {
    echo json_encode(['status' => 'error', 'message' => 'Модуль iblock не подключен']);
    die();
}

$fio         = trim($_POST['fio'] ?? '');
$time        = trim($_POST['time'] ?? '');
$procedureId = (int)($_POST['procedureId'] ?? 0);

if (empty($fio) || empty($time)) {
    echo json_encode(['status' => 'error', 'message' => 'Заполните ФИО и время']);
    die();
}

$el = new CIBlockElement;

$PROP = [
    "FIO_KLIENTA"             => $fio,
    "VREMYA_ZAPISI_KLIENTA"   => $time,
];

// Если ID процедуры известен (из админки), добавляем привязку
if ($procedureId > 0) {
    $PROP["PROTSEDURY_DLYA_KLIENTA"] = $procedureId;
}

$arFields = [
    "IBLOCK_ID"      => 26, // ID инфоблока "Бронирование"
    "NAME"           => "Запись: " . $fio . " на " . $time,
    "ACTIVE"         => "Y",
    "PROPERTY_VALUES"=> $PROP,
];

if ($productId = $el->Add($arFields)) {
    echo json_encode(['status' => 'success', 'id' => $productId]);
} else {
    echo json_encode(['status' => 'error', 'message' => $el->LAST_ERROR]);
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");