<?php

// защита от неавторизованных
AddEventHandler("main", "OnProlog", "CloseAccessForUnauthorized");
function CloseAccessForUnauthorized()
{
    global $USER, $APPLICATION;
    if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
        return;
    }
    if (!$USER->IsAuthorized() && !in_array($APPLICATION->GetCurPage(), array("/auth/"))) {
        $uri = new \Bitrix\Main\Web\Uri("/auth/");
        $uri->addParams(["back_url" => $APPLICATION->GetCurPage()]);
        LocalRedirect($uri->getUri());
        die;
    }
}

// подключаю обработчик ошибок
$exceptionHandlerPath = $_SERVER['DOCUMENT_ROOT'] . '/local/App/Debug/ExceptionHandler.php';
if (file_exists($exceptionHandlerPath)) {
    require_once $exceptionHandlerPath;
}

// автозагрузка для услуги автосервиса
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

// модуль записи
if (Loader::includeModule('custom.booking')) {
    \CJSCore::Init(['custom.booking']);
}

// ? получилось сделать пока только напрямую из init (подумаю еще) 
use Bitrix\Main\EventManager;

EventManager::getInstance()->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    function (\Bitrix\Main\Event $event) {
        if ($event->getParameter('entityTypeID') != \CCrmOwnerType::Deal) {
            return;
        }

        $tabs = $event->getParameter('tabs');
        $entityId = $event->getParameter('entityID');

        // вкладка Список товаров Автозаказ
        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'custom:dealtabs.grid', 
            '', 
            ['ENTITY_ID' => $entityId]
        );
        $html1 = ob_get_clean();

        $tabs[] = [
            'id' => 'tab_dealtabs',
            'name' => 'Список товаров Автозаказ',
            'html' => $html1,
        ];

        // вкладка Гараж
        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'custom:garagtabs', 
            '', 
            ['ENTITY_ID' => $entityId]
        );
        $html2 = ob_get_clean();

        $tabs[] = [
            'id' => 'tab_garage',
            'name' => 'Гараж',
            'html' => $html2,
        ];

        return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, [
            'tabs' => $tabs,
        ]);
    }
);