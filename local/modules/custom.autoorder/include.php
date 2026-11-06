<?php
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;

// Регистрируем обработчик события
EventManager::getInstance()->addEventHandler(
    'crm',
    'onEntityDetailsTabsInitialized',
    ['CustomAutoorderTabs', 'onEntityDetailsTabsInitialized']
);

class CustomAutoorderTabs
{
    public static function onEntityDetailsTabsInitialized(\Bitrix\Main\Event $event)
    {
        $tabs = $event->getParameter('tabs');
        
        $tabs[] = [
            'id'    => 'tab_custom_drafts',
            'name'  => 'Автозаказ',
            'title' => 'История автозаказов',
            'content' => self::getTabContent(
                $event->getParameter('entityID'), 
                $event->getParameter('entityTypeID')
            ),
        ];
        
        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }

    private static function getTabContent($entityId, $entityTypeId)
    {
        global $APPLICATION;
        ob_start();
        
        $APPLICATION->IncludeComponent(
            'custom:autoorder.grid',
            '',
            [
                'ENTITY_ID' => $entityId,
                'ENTITY_TYPE_ID' => $entityTypeId,
                'CACHE_TYPE' => 'N',
            ],
            false
        );
        
        return ob_get_clean();
    }
}