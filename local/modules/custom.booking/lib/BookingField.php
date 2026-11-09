<?php
namespace Custom\Booking;

use Bitrix\Main\Localization\Loc;

// подключаем языковые файлы
Loc::loadMessages(__FILE__);

class BookingField
{
    // описание типа свойства для битрикса
    public static function GetUserTypeDescription()
    {
        return array(
            "PROPERTY_TYPE"        => "E", 
            "USER_TYPE"            => "CUSTOM_BOOKING_LINK", 
            "DESCRIPTION"          => "Кастомная ссылка для записи",
            "GetPublicViewHTML"    => array(__CLASS__, "GetPublicViewHTML"),
            "GetAdminListViewHTML" => array(__CLASS__, "GetAdminListViewHTML"),
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

            // получаем название процедуры
            $elementName = "Процедура #" . $elementId;
            $rsElement = \CIBlockElement::GetList(
                array(), 
                array("ID" => $elementId, "ACTIVE" => "Y"), 
                false, false, 
                array("NAME")
            );
            if ($arElement = $rsElement->Fetch()) {
                $elementName = htmlspecialcharsbx($arElement["NAME"]);
            }

            // id инфоблока откуда кликнули
            $sourceIblockId = (int)$arProperty['IBLOCK_ID'];

            // НАСТРОЙКИ МАРШРУТИЗАЦИИ (настраиваем один раз в коде)
            if ($sourceIblockId == 16) {
                // если кликнули из Врачей
                $targetIblockId = 26; 
                $propFio = "FIO_KLIENTA";
                $propDate = "VREMYA_ZAPISI_KLIENTA";
                $propProc = "PROTSEDURY_DLYA_KLIENTA";
            } else if ($sourceIblockId == 29) {
                // если кликнули из Мастеров (замените 29 на реальный ID, если он другой)
                $targetIblockId = 27; 
                $propFio = ""; // В 27-м инфоблоке ФИО пишется в NAME
                $propDate = "VREMYA_ZAPISI_KLIENTA";
                $propProc = "USLUGI_SERVISA";
            } else {
                // дефолтные настройки для новых списков (если появятся)
                $targetIblockId = 27; 
                $propFio = "";
                $propDate = "VREMYA_ZAPISI_KLIENTA";
                $propProc = "USLUGI_SERVISA";
            }

            // возвращаем кнопку со всеми data-атрибутами
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
        return self::GetPublicViewHTML($arProperty, $value, $strHTMLControlName);
    }

    // вывод в форме редактирования
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        $options = '';
        $rsElements = \CIBlockElement::GetList(
            array("SORT" => "ASC"), 
            array("IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"]), 
            false, false, 
            array("ID", "NAME")
        );
        
        while ($arElement = $rsElements->Fetch()) {
            $sel = ((int)$value['VALUE'] === (int)$arElement['ID']) ? 'selected' : '';
            $options .= '<option value="' . $arElement['ID'] . '" ' . $sel . '>' . htmlspecialcharsbx($arElement['NAME']) . '</option>';
        }

        return '<select name="' . $strHTMLControlName["VALUE"] . '" style="max-width:300px;">'
            . '<option value="">-- Выберите услугу --</option>'
            . $options
            . '</select>';
    }
}
?>