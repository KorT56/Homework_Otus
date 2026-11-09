<?php
use Bitrix\Main\Loader;

// регистрируем автозагрузку классов модуля
Loader::registerAutoLoadClasses(
    'custom.booking',
    [
        // путь к классу поля
        '\Custom\Booking\BookingField' => 'lib/BookingField.php',
    ]
);

// вешаем обработчик на создание свойств инфоблока
AddEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\Custom\Booking\BookingField', 'GetUserTypeDescription']);

// регистрируем js расширение
\CJSCore::RegisterExt('custom.booking', [
    // путь к скопированному js
    'js' => '/bitrix/js/custom.booking/script.js', 
    // подключаем попапы, ajax и календарь
    'rel' => ['popup', 'ajax', 'date'], 
]);
?>