<?php
// Защита от неавторизованных
AddEventHandler("main", "OnProlog", "CloseAccessForUnauthorized");
function CloseAccessForUnauthorized() {
    global $USER, $APPLICATION;
    if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) return;
    if (!$USER->IsAuthorized() && !in_array($APPLICATION->GetCurPage(), array("/auth/"))) {
        $uri = new \Bitrix\Main\Web\Uri("/auth/");
        $uri->addParams(["back_url" => $APPLICATION->GetCurPage()]);
        LocalRedirect($uri->getUri());
        die;
    }
}

// Автозагрузка для uslugi
Bitrix\Main\Loader::registerAutoLoadClasses(
    null,
    ['Custom\\Uslugi\\ServiceTable' => '/local/modules/custom.uslugi/lib/ServiceTable.php']
);

use Bitrix\Main\Loader;

// Подключаем модули
if (Loader::includeModule('custom.autoorder')) {
    // Модуль настроек
}
if (Loader::includeModule('custom.dealtabs')) {
    // Модуль вкладки в CRM
}