<?php

// защита от неавторизованных
AddEventHandler("main", "OnProlog", "CloseAccessForUnauthorized");
function CloseAccessForUnauthorized()
{
    global $USER, $APPLICATION;
    // пропускаем админку
    if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
        return;
    }
    // редирект на авторизацию
    if (!$USER->IsAuthorized() && !in_array($APPLICATION->GetCurPage(), array("/auth/"))) {
        $uri = new \Bitrix\Main\Web\Uri("/auth/");
        $uri->addParams(["back_url" => $APPLICATION->GetCurPage()]);
        LocalRedirect($uri->getUri());
        die;
    }
}

// подключаю обработчик ошибок
$exceptionHandlerPath = $_SERVER['DOCUMENT_ROOT'] . '/local/App/Debug/ExceptionHandler.php';
// проверяем наличие файла
if (file_exists($exceptionHandlerPath)) {
    // грузим файл
    require_once $exceptionHandlerPath;
}

// автозагрузка для uslugi
Bitrix\Main\Loader::registerAutoLoadClasses(
    null,
    ['Custom\\Uslugi\\ServiceTable' => '/local/modules/custom.uslugi/lib/ServiceTable.php']
);

// подключение модулей
use Bitrix\Main\Loader;

// модуль авто-заказа
if (Loader::includeModule('custom.autoorder')) {
    // настройки автозаказа
}

// модуль вкладки CRM
if (Loader::includeModule('custom.dealtabs')) {
    // вкладка в CRM
}

// модуль записи
if (Loader::includeModule('custom.booking')) {
    // модуль подключен
    
    // грузим JS
    \CJSCore::Init(['custom.booking']);
}