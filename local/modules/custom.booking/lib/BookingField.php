<?php
namespace Custom\Booking;

use Bitrix\Main\Localization\Loc;

// подключаем языковые файлы
Loc::loadMessages(__FILE__);

// класс кастомного свойства
class BookingField
{
    // описание типа свойства для битрикса
    public static function GetUserTypeDescription()
    {
        return array(
            // базовый тип - привязка к элементу
            "PROPERTY_TYPE"        => "E", 
            // уникальный код типа
            "USER_TYPE"            => "CUSTOM_BOOKING_LINK", 
            // название в админке
            "DESCRIPTION"          => "Кастомная ссылка для записи",
            // метод вывода на сайте
            "GetPublicViewHTML"    => array(__CLASS__, "GetPublicViewHTML"),
            // метод вывода в списке админки
            "GetAdminListViewHTML" => array(__CLASS__, "GetAdminListViewHTML"),
            // метод вывода в форме редактирования
            "GetPropertyFieldHtml" => array(__CLASS__, "GetPropertyFieldHtml"),
        );
    }

    // вывод на публичной части
    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        // если значение заполнено
        if (!empty($value['VALUE'])) {
            // id процедуры
            $elementId = (int)$value['VALUE'];
            
            global $APPLICATION;
            // подключаем наш js
            $APPLICATION->AddHeadScript('/bitrix/js/custom.booking/script.js');
            // инициализируем ядро попапов, ajax и календаря
            \CJSCore::Init(['popup', 'ajax', 'date']);

            // дефолтное название
            $elementName = "Процедура #" . $elementId;
            // получаем элемент инфоблока
            $rsElement = \CIBlockElement::GetList(
                array(), 
                array("ID" => $elementId, "ACTIVE" => "Y"), 
                false, false, 
                array("NAME")
            );
            // если нашли - берем название
            if ($arElement = $rsElement->Fetch()) {
                $elementName = htmlspecialcharsbx($arElement["NAME"]);
            }

            // id инфоблока откуда кликнули
            $sourceIblockId = (int)$arProperty['IBLOCK_ID'];

            // настройки для списка врачи
            if ($sourceIblockId == 16) {
                // целевой инфоблок
                $targetIblockId = 26; 
                // поле фио
                $propFio = "FIO_KLIENTA";
                // поле даты
                $propDate = "VREMYA_ZAPISI_KLIENTA";
                // поле процедуры
                $propProc = "PROTSEDURY_DLYA_KLIENTA";
            // настройки для списка мастера (услуги)
            } else if ($sourceIblockId == 29) {
                // целевой инфоблок
                $targetIblockId = 27; 
                // поле фио (пишется в название)
                $propFio = ""; 
                // поле даты
                $propDate = "VREMYA_ZAPISI_KLIENTA";
                // поле процедуры
                $propProc = "USLUGI_SERVISA";
            // дефолтные настройки для новых списков
            } else {
                // целевой инфоблок
                $targetIblockId = 27; 
                // поле фио
                $propFio = "";
                // поле даты
                $propDate = "VREMYA_ZAPISI_KLIENTA";
                // поле процедуры
                $propProc = "USLUGI_SERVISA";
            }

            // возвращаем кнопку с данными
            return '<span class="custom-booking-link ui-btn ui-btn-sm ui-btn-success" 
                style="cursor:pointer; margin: 2px 5px 2px 0; display:inline-block;" 
                data-procedure-id="' . $elementId . '" 
                data-target-iblock="' . $targetIblockId . '" 
                data-prop-fio="' . $propFio . '" 
                data-prop-date="' . $propDate . '" 
                data-prop-proc="' . $propProc . '">Записаться: ' . $elementName . '</span>';
        }
        // если пусто - ничего не выводим
        return '';
    }

    // вывод в списке админки
    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        // используем тот же метод
        return self::GetPublicViewHTML($arProperty, $value, $strHTMLControlName);
    }

    // вывод в форме редактирования
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        // переменная для опций
        $options = '';
        // получаем элементы привязанного инфоблока
        $rsElements = \CIBlockElement::GetList(
            array("SORT" => "ASC"), 
            array("IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"]), 
            false, false, 
            array("ID", "NAME")
        );
        
        // формируем опции
        while ($arElement = $rsElements->Fetch()) {
            // проверяем выбран ли элемент
            $sel = ((int)$value['VALUE'] === (int)$arElement['ID']) ? 'selected' : '';
            // добавляем опцию
            $options .= '<option value="' . $arElement['ID'] . '" ' . $sel . '>' . htmlspecialcharsbx($arElement['NAME']) . '</option>';
        }

        // возвращаем выпадающий список
        return '<select name="' . $strHTMLControlName["VALUE"] . '" style="max-width:300px;">'
            . '<option value="">-- Выберите услугу --</option>'
            . $options
            . '</select>';
    }
}
?>