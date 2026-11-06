<?php
// local/modules/custom.booking/lib/BookingField.php

namespace Custom\Booking;

use CIBlockElement;

class BookingField
{
    // Описание типа свойства
    public static function GetUserTypeDescription()
    {
        return [
            "PROPERTY_TYPE"        => "S", // Строка, но мы переопределим вывод
            "USER_TYPE"            => "CUSTOM_BOOKING", // Уникальный код свойства
            "DESCRIPTION"          => "Кнопка записи (Кастом)",
            "GetAdminListViewHTML" => [__CLASS__, "GetAdminListViewHTML"],
            "GetPropertyFieldHtml" => [__CLASS__, "GetPropertyFieldHtml"],
        ];
    }

    // Вывод в списке элементов (в админке и публичке)
    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        $procedureId = (int)$value['VALUE'];
        
        if ($procedureId > 0) {
            // Получаем название процедуры
            $procedureName = 'Процедура #' . $procedureId;
            $res = CIBlockElement::GetByID($procedureId);
            if ($arRes = $res->GetNext()) {
                $procedureName = $arRes['NAME'];
            }

            // Выводим ссылку. Добавляем data-атрибуты для JS
            return '<a href="javascript:void(0)" 
                       class="custom-booking-link" 
                       data-procedure-id="' . $procedureId . '"
                       data-procedure-name="' . htmlspecialcharsbx($procedureName) . '">
                       📝 Записать: ' . htmlspecialcharsbx($procedureName) . '
                    </a>';
        }
        return '—';
    }

    // Форма редактирования (стандартный селект, чтобы можно было изменить привязку вручную)
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        // Оставляем стандартный вывод привязки к элементу
        return '<input type="text" name="' . $strHTMLControlName["VALUE"] . '" value="' . htmlspecialcharsbx($value["VALUE"]) . '" size="5"> (ID процедуры)';
    }
}