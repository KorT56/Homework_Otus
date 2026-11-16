<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;

// добавляем обработчик
EventManager::getInstance()->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    'myOnEntityDetailsTabsInitialized_DealTabs' // Уникальное имя функции
);

function myOnEntityDetailsTabsInitialized_DealTabs($event)
{
    $tabs = $event->getParameter('tabs');
    $entityID = $event->getParameter('entityID');
    $entityTypeID = $event->getParameter('entityTypeID');

    // Проверяем, что открыта карточка именно Сделки
    if ($entityTypeID == \CCrmOwnerType::Deal) {
        
        // Получаем HTML компонента
        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'custom:dealtabs.grid',
            '',
            [
                'ENTITY_ID' => $entityID,
                'ENTITY_TYPE_ID' => $entityTypeID,
            ],
            false
        );
        $content = ob_get_clean();

        // Добавляем вкладку
        $tabs[] = [
            'id'   => 'tab_custom_dealtabs',
            'name' => 'Список товаров Автозаказ',
            'html' => $content,
        ];
    }

    return new EventResult(EventResult::SUCCESS, [
        'tabs' => $tabs,
    ]);
}
