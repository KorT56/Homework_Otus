<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension;

class CustomAutoorderGridComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        // Принудительно подключаем стили и скрипты для Грида и модальных окон
        Extension::load("ui.grid");
        Extension::load("ui.buttons");
        Extension::load("ui.dialogs.messagebox");

        // ТЕСТОВЫЕ ДАННЫЕ (одна строка) для проверки
        $this->arResult['ROWS'] = [
            [
                'data' => [
                    'ID' => 1,
                    'DEAL_NAME' => 'ТЕСТОВАЯ СДЕЛКА (если видите это - компонент работает)',
                    'PRODUCT_NAME' => 'Тестовый товар',
                    'QUANTITY' => 5,
                ]
            ]
        ];
        
        $this->arResult['COLUMNS'] = [
            ['id' => 'ID', 'name' => 'ID', 'default' => true],
            ['id' => 'DEAL_NAME', 'name' => 'Сделка', 'default' => true],
            ['id' => 'PRODUCT_NAME', 'name' => 'Товар', 'default' => true],
            ['id' => 'QUANTITY', 'name' => 'Кол-во', 'default' => true],
        ];
        
        $this->arResult['GRID_ID'] = 'custom_autoorder_grid';
        
        $this->includeComponentTemplate();
    }
}