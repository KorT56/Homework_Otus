<?php
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;

EventManager::getInstance()->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    'myOnEntityDetailsTabsInitialized'
);

function myOnEntityDetailsTabsInitialized($event)
{
    $tabs = $event->getParameter('tabs');
    $entityID = $event->getParameter('entityID');
    $entityTypeID = $event->getParameter('entityTypeID');

    // Проверяем, что открыта карточка именно Сделки (Deal)
    if ($entityTypeID == \CCrmOwnerType::Deal) {
        
        // Получаем HTML компонента
        $content = getMyTabContent($entityID, $entityTypeID);

        // Добавляем вкладку с ключом 'html' (как в статье)
        $tabs[] = [
            'id'   => 'tab_custom_dealtabs',
            'name' => 'Список товаров Автозаказ',
            'html' => $content, // <--- ВАЖНО: ключ 'html', а не 'content'
        ];
    }

    return new EventResult(EventResult::SUCCESS, [
        'tabs' => $tabs,
    ]);
}

function getMyTabContent($entityID, $entityTypeID)
{
    global $APPLICATION;
    ob_start();
    
    $APPLICATION->IncludeComponent(
        'custom:dealtabs.grid',
        '',
        [
            'ENTITY_ID' => $entityID,
            'ENTITY_TYPE_ID' => $entityTypeID,
        ],
        false
    );
    
    return ob_get_clean();
}