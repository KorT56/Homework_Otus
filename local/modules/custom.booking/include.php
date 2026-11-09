<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'custom.booking',
    [
        '\Custom\Booking\BookingField' => 'lib/BookingField.php',
    ]
);

AddEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\Custom\Booking\BookingField', 'GetUserTypeDescription']);

\CJSCore::RegisterExt('custom.booking', [
    'js' => '/bitrix/js/custom.booking/script.js', 
    'rel' => ['popup', 'ajax', 'date'], 
]);
?>