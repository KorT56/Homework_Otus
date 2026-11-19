<?php
// защита от прямого доступа к файлу
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// добавляем наш JS-скрипт на страницу
$asset = \Bitrix\Main\Page\Asset::getInstance();

$jsCode = <<<JS
BX.ready(function() {
    // ваш перехватчик событий
    let originalBxOnCustomEvent = BX.onCustomEvent;
    BX.onCustomEvent = function (eventObject, eventName, eventParams, secureParams)
    {
        // onMenuItemHover например выбрасывает в другом порядке
        let realEventName = BX.type.isString(eventName) ?
        eventName : BX.type.isString(eventObject) ? eventObject : null;
        
        if (realEventName) {
            console.log(
                '%c' + realEventName,
                'background: #222; color: #bada55; font-weight: bold; padding: 3px 4px;'
            );
        }
        
        console.dir({
            eventObject: eventObject,
            eventParams: eventParams,
            secureParams: secureParams
        });
        
        // возвращаем управление оригинальному обработчику
        originalBxOnCustomEvent.apply(null, arguments);
    };
});
JS;

$asset->addString('<script>' . $jsCode . '</script>');