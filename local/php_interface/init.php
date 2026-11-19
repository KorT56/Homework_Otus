<?php
// защита от гостей
AddEventHandler("main", "OnProlog", "CloseAccessForUnauthorized");
function CloseAccessForUnauthorized()
{
    // доступ к переменным
    global $USER, $APPLICATION;
    // проверка админки
    if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
        return;
    }
    // проверка авторизации
    if (!$USER->IsAuthorized() && !in_array($APPLICATION->GetCurPage(), array("/auth/"))) {
        // создаем ссылку
        $uri = new \Bitrix\Main\Web\Uri("/auth/");
        // добавляем возврат
        $uri->addParams(["back_url" => $APPLICATION->GetCurPage()]);
        // перенаправляем гостя
        LocalRedirect($uri->getUri());
        // останавливаем скрипт
        die;
    }
}

// подключаем обработчик ошибок
$exceptionHandlerPath = $_SERVER['DOCUMENT_ROOT'] . '/local/App/Debug/ExceptionHandler.php';
// проверяем файл
if (file_exists($exceptionHandlerPath)) {
    // грузим файл
    require_once $exceptionHandlerPath;
}

// автозагрузка утилит
\Bitrix\Main\Loader::registerAutoLoadClasses(
    null,
    ['Custom\\Uslugi\\ServiceTable' => '/local/modules/custom.uslugi/lib/ServiceTable.php']
);

// подключение модулей
use Bitrix\Main\Loader;

// модуль заказов автозаказ
if (Loader::includeModule('custom.autoorder')) {
    // настройки заказов
}

// модуль вкладок в сделках
if (Loader::includeModule('custom.dealtabs')) {
    // вкладки в crm
}

// модуль записи бронирование
if (Loader::includeModule('custom.booking')) {
    // грузим его скрипты
    \CJSCore::Init(['custom.booking']);
}

// подключаем перехватчик (ОТКЛЮЧЕНО ДЛЯ ОТЛАДКИ)
// if (file_exists(__DIR__ . '/event_new.php')) {
//     require_once __DIR__ . '/event_new.php';
// }

// грузим  скрипт время
\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'main',
    'OnProlog',
    function () {
        // получаем объект
        $asset = \Bitrix\Main\Page\Asset::getInstance();
        // подключаем js
        $asset->addString('<script src="/local/additional/main.js?v=' . time() . '"></script>');
        // подключаем css
        $asset->addString('<link rel="stylesheet" href="/local/additional/main.css?v=' . time() . '">');
    }
);