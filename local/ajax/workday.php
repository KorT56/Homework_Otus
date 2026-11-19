<?php
// грузим ядро
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

// отдаем json
header('Content-Type: application/json');

// доступ к юзеру
global $USER;

// проверка входа
if (!$USER->IsAuthorized()) {
    // ответ гостю
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован']);
    // стоп скрипт
    die();
}

try {
    // грузим модуль про время
    if (!\Bitrix\Main\Loader::includeModule('timeman')) {
        // ошибка модуля
        throw new Exception('Модуль timeman не установлен');
    }

    // id человека, который начинает время
    $userId = $USER->GetID();
    // карточка юзера
    $tmUser = new CTimeManUser($userId);

    // открыть или продолжить 
    if ($tmUser->OpenDay() || $tmUser->ReopenDay(true)) {
        // ответ успех
        echo json_encode(['status' => 'success', 'message' => 'Рабочий день запущен']);
    } else {
        // ответ провал
        echo json_encode(['status' => 'error', 'message' => 'Методы OpenDay/ReopenDay вернули false (возможно, день уже открыт)']);
    }
    
} catch (Throwable $e) {
    // если ошибка
    echo json_encode(['status' => 'error', 'message' => 'PHP Ошибка: ' . $e->getMessage()]);
}