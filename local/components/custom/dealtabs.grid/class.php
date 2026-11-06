<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class CustomDealTabsGridComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        Loader::includeModule('crm'); // Подключаем CRM для получения сделок

        // --- 1. ПОЛУЧАЕМ ТОВАРЫ С ОСТАТКАМИ ---
        $products = [];
        $partsIblockId = 14;

        $rsProducts = \CIBlockElement::GetList(
            ['NAME' => 'ASC'], 
            ['IBLOCK_ID' => $partsIblockId, 'ACTIVE' => 'Y'], 
            false, false, ['ID', 'NAME']
        );

        while ($arProduct = $rsProducts->GetNext()) {
            $currentStock = 0;
            $offers = \CCatalogSKU::getOffersList($arProduct['ID'], $partsIblockId);
            if (!empty($offers[$arProduct['ID']])) {
                $offer = reset($offers[$arProduct['ID']]);
                $resStock = \CCatalogProduct::GetList([], ['ID' => $offer['ID']], false, false, ['QUANTITY']);
                if ($arStock = $resStock->Fetch()) $currentStock = (float)$arStock['QUANTITY'];
            } else {
                $resStock = \CCatalogProduct::GetList([], ['ID' => $arProduct['ID']], false, false, ['QUANTITY']);
                if ($arStock = $resStock->Fetch()) $currentStock = (float)$arStock['QUANTITY'];
            }

            $products[] = [
                'ID' => $arProduct['ID'],
                'NAME' => htmlspecialcharsbx($arProduct['NAME']),
                'STOCK' => $currentStock,
            ];
        }

        $this->arResult['PRODUCTS'] = $products;

        // --- 2. ПОЛУЧАЕМ СДЕЛКИ АВТОЗАКАЗА ---
        $deals = [];
        
        // Ищем сделки, в названии которых есть "Автозаказ:"
        $res = \Bitrix\Crm\DealTable::getList([
            'select' => ['ID', 'TITLE', 'DATE_CREATE'],
            'filter' => ['%TITLE' => 'Автозаказ:'],
            'order'  => ['ID' => 'DESC'],
            'limit'  => 50
        ]);

        while ($deal = $res->fetch()) {
            $productName = '—';
            $qty = 0;
            
            // Получаем товар из сделки
            $productRows = \Bitrix\Crm\ProductRowTable::getList([
                'filter' => ['=OWNER_ID' => $deal['ID'], '=OWNER_TYPE' => 'D'],
                'select' => ['PRODUCT_NAME', 'QUANTITY']
            ])->fetchAll();

            if (!empty($productRows)) {
                $productName = $productRows[0]['PRODUCT_NAME'];
                $qty = (float)$productRows[0]['QUANTITY'];
            }

            $deals[] = [
                'ID' => $deal['ID'],
                'TITLE' => htmlspecialcharsbx($deal['TITLE']),
                'PRODUCT_NAME' => htmlspecialcharsbx($productName),
                'QUANTITY' => $qty,
                'DATE_CREATE' => $deal['DATE_CREATE']->toString(),
            ];
        }

        $this->arResult['DEALS'] = $deals;

        $this->includeComponentTemplate();
    }
}