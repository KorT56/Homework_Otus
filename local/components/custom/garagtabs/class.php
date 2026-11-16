<?php
// защита от прямого вызова
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class GaragtabsComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        Loader::includeModule('crm');
        Loader::includeModule('iblock');

        $dealId = $this->arParams['ENTITY_ID'] ?? 0;
        if (!$dealId) return;

        // 1. Получаем контакт сделки
        $contactId = $this->getDealContactId($dealId);

        if (!$contactId) {
            $this->arResult['ERROR'] = 'К сделке не привязан контакт.';
            $this->includeComponentTemplate();
            return;
        }

        // 2. Получаем ID машины из текущей сделки
        $currentDealCarIds = $this->getDealCarIds($dealId);

        // сохраняем для отладки
        $this->arResult['CONTACT_ID'] = $contactId;
        $this->arResult['DEAL_CAR_IDS'] = $currentDealCarIds;

        // 3. Загружаем машины клиента и собираем для них сделки
        $this->arResult['CARS'] = $this->getFilteredCars($contactId);

        // 4. Загружаем гараж (в шаблоне не выводим, но оставляем)
        $this->arResult['GARAGE'] = $this->getFilteredElements(18, 'PRIVYAZKA_K_KONTAKTU_IZ_GARAZHA', $contactId);

        $this->includeComponentTemplate();
    }

    // Получаем контакт из сделки
    private function getDealContactId($dealId)
    {
        if (class_exists('\Bitrix\Crm\Binding\DealTable')) {
            $contactIds = \Bitrix\Crm\Binding\DealTable::getContactIds($dealId);
            if (!empty($contactIds)) return (int)$contactIds[0];
        }
        global $DB;
        $res = $DB->Query("SELECT CONTACT_ID FROM b_crm_deal_contact WHERE DEAL_ID = " . (int)$dealId . " LIMIT 1");
        if ($arRes = $res->Fetch()) return (int)$arRes['CONTACT_ID'];
        return 0;
    }

    // Получаем ID машины из сделки (по вашему полю)
    private function getDealCarIds($dealId)
    {
        $carIds = [];
        $dbRes = \CCrmDeal::GetListEx([], ['ID' => $dealId], false, false, ['*', 'UF_*']);
        if ($arDeal = $dbRes->Fetch()) {
            $fieldValue = $arDeal['UF_CRM_1791212104'] ?? null; 
            if (!empty($fieldValue)) {
                $carIds = is_array($fieldValue) ? $fieldValue : [$fieldValue];
            }
        }
        return $carIds;
    }

    // Получаем машины, привязанные к контакту, и ищем для них сделки
    private function getFilteredCars($contactId)
    {
        $elements = [];
        $res = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['IBLOCK_ID' => 20, 'ACTIVE' => 'Y'],
        ]);

        while ($item = $res->fetch()) {
            $props = $this->getElementProps(20, $item['ID']);
            
            $linkedContact = $props['PRIVYAZKA_K_KLIENTU_IZ_MODELI']['VALUE'] ?? '';
            if (is_array($linkedContact)) $linkedContact = reset($linkedContact);

            if ((int)$linkedContact === (int)$contactId) {
                // Ищем ВСЕ сделки для этой машины
                $deals = $this->getDealsForCar($item['ID']);
                
                $elements[] = [
                    'FIELDS' => $item, 
                    'PROPS' => $props,
                    'DEALS' => $deals
                ];
            }
        }
        return $elements;
    }

    // Ищем все сделки, в которых выбрана эта машина
    private function getDealsForCar($carId)
    {
        $dealIds = [];
        
        // Используем ваш код поля: UF_CRM_1791212104
        $res = \CCrmDeal::GetListEx(
            [], 
            ['UF_CRM_1791212104' => $carId, 'CHECK_PERMISSIONS' => 'N'], 
            false, false, ['ID']
        );
        
        while ($arDeal = $res->Fetch()) {
            $dealIds[] = $arDeal['ID'];
        }
        
        return $dealIds;
    }

    // Для гаража (оставил как было)
    private function getFilteredElements($iblockId, $propertyCode, $contactId)
    {
        $elements = [];
        $res = \Bitrix\Iblock\ElementTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y'],
        ]);

        while ($item = $res->fetch()) {
            $props = $this->getElementProps($iblockId, $item['ID']);
            $linkedValue = $props[$propertyCode]['VALUE'] ?? '';
            if (is_array($linkedValue)) $linkedValue = reset($linkedValue);
            if ((int)$linkedValue === (int)$contactId) {
                $elements[] = ['FIELDS' => $item, 'PROPS' => $props];
            }
        }
        return $elements;
    }

    // Вспомогательная функция получения свойств
    private function getElementProps($iblockId, $elementId)
    {
        $props = [];
        $dbProps = \CIBlockElement::GetProperty($iblockId, $elementId, [], false, []);
        while ($arProp = $dbProps->Fetch()) {
            $props[$arProp['CODE']] = $arProp;
        }
        return $props;
    }
}