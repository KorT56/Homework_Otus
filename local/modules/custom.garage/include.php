<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;

// добавляем обработчик (с другим уникальным именем функции!)
EventManager::getInstance()->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    'myOnEntityDetailsTabsInitialized_Garage' // Уникальное имя функции
);

function myOnEntityDetailsTabsInitialized_Garage($event)
{
    $tabs = $event->getParameter('tabs');
    $entityID = $event->getParameter('entityID');
    $entityTypeID = $event->getParameter('entityTypeID');

    // Проверяем, что открыта карточка Сделки
    if ($entityTypeID == \CCrmOwnerType::Deal) {
        
        // Получаем HTML компонента
        ob_start();
        $GLOBALS['APPLICATION']->IncludeComponent(
            'custom:garagtabs',
            '',
            ['ENTITY_ID' => $entityID],
            false
        );
        $html = ob_get_clean();

        // Добавляем вкладку
        $tabs[] = [
            'id'   => 'garage_tab',
            'name' => 'Гараж',
            'html' => $html,
        ];
    }

    return new EventResult(EventResult::SUCCESS, [
        'tabs' => $tabs,
    ]);
}