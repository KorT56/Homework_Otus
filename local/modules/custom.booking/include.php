<?php
use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
    'custom.booking',
    [
        '\Custom\Booking\BookingField' => 'lib/BookingField.php',
    ]
);

AddEventHandler('iblock', 'OnIBlockPropertyBuildList', ['\Custom\Booking\BookingField', 'GetUserTypeDescription']);