<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); // защита от прямого вызова
use Bitrix\Main\Loader; // подключаем Loader для модулей
use Bitrix\Main\Engine\Contract\Controllerable; // подключаем интерфейс для AJAX

class UslugiServisaComponent extends CBitrixComponent implements Controllerable // объявляем класс и реализуем интерфейс
{
    // Этот метод принудительно подгружает модуль iblock и catalog для всех запросов (включая AJAX)
    public function onPrepareComponentParams($arParams) 
    {
        Loader::includeModule('iblock'); // подключаем модуль инфоблоков
        Loader::includeModule('catalog'); // подключаем модуль каталога (для цен)
        $GLOBALS['USLUGI_IBLOCK_ID'] = (int)$arParams['IBLOCK_ID']; // сохраняем ID услуг глобально
        return $arParams; // возвращаем параметры
    }

    private function includeModules() // метод подключения модулей
    {
        if (!Loader::includeModule('iblock')) { // проверяем модуль iblock
            throw new \Bitrix\Main\SystemException('Модуль iblock не установлен'); // ошибка если нет модуля
        }
        Loader::includeModule('catalog'); // подключаем каталог для цен
    }

    public function configureActions() // обязательный метод для Controllerable
    {
        return [
            'saveRow' => ['prefilters' => []], // отключаем проверки CSRF для стабильности
            'deleteRow' => ['prefilters' => []], // отключаем проверки CSRF
            'getFormData' => ['prefilters' => []], // отключаем проверки CSRF
        ];
    }

    public function executeComponent() // главный метод, вызывается Bitrix
    {
        try { // ловим исключения
            $this->includeModules(); // подключаем модули
            $this->prepareParams(); // готовим параметры
            $this->prepareResult(); // готовим данные для шаблона
            $this->includeComponentTemplate(); // рендерим шаблон
        } catch (\Exception $e) { // если ошибка
            ShowError($e->getMessage()); // показываем её
        }
    }

    private function prepareParams() // проверка и дефолты параметров
    {
        if (empty($this->arParams['IBLOCK_ID'])) { // IBLOCK_ID обязателен
            throw new \Bitrix\Main\SystemException('Не указан IBLOCK_ID'); // ошибка если пусто
        }
        if (empty($this->arParams['PARTS_IBLOCK_ID'])) { // если не задан инфоблок запчастей
            $this->arParams['PARTS_IBLOCK_ID'] = $this->arParams['IBLOCK_ID']; // берём тот же
        }
        if (empty($this->arParams['DETAIL_URL'])) { // если не задан URL детальной
            $this->arParams['DETAIL_URL'] = '/services/#ID#/'; // дефолт с подстановкой ID
        }
        $this->arParams['GRID_ID'] = $this->arParams['GRID_ID'] ?: 'uslugi_servisa_grid'; // дефолт GRID_ID
    }

    private function prepareResult() // наполняем arResult
    {
        $this->arResult['GRID_ID'] = $this->arParams['GRID_ID']; // прокидываем GRID_ID

        $this->arResult['COLUMNS'] = [ // колонки грида
            ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true], // колонка ID
            ['id' => 'NAME', 'name' => 'Название услуги', 'sort' => 'NAME', 'default' => true], // колонка названия
            ['id' => 'PRICE', 'name' => 'Цена, руб.', 'sort' => 'PRICE', 'default' => true], // колонка цены
            ['id' => 'NORMO_HOURS', 'name' => 'Нормо-часы', 'sort' => 'NORMO_HOURS', 'default' => true], // колонка нормо-часов
            ['id' => 'PARTS', 'name' => 'Запчасти', 'sort' => 'PARTS', 'default' => true], // колонка запчастей
        ];

        $this->arResult['ROWS'] = $this->getRows(); // строки грида
        $this->arResult['ALL_PARTS'] = $this->getAllParts(); // все запчасти для селекта
    }

    private function getRows() // формируем строки грида
    {
        $rows = []; // массив строк
        $items = $this->getServices(); // получаем уникальные услуги

        foreach ($items as $item) { // перебираем услуги
            // Отдельно запрашиваем запчасти именно для этой услуги
            $partsIds = [];
            $rsProps = \CIBlockElement::GetProperty(
                $this->arParams['IBLOCK_ID'], 
                $item['ID'], 
                [], 
                ['CODE' => 'SERVICE_PARTS']
            );
            while ($prop = $rsProps->Fetch()) {
                if (!empty($prop['VALUE'])) {
                    $partsIds[] = (int)$prop['VALUE'];
                }
            }

            $partsData = $this->getPartsNames($partsIds); // получаем данные запчастей
            $partsHtml = ''; // HTML для ячейки
            if ($partsData) { // если есть запчасти
                $partsHtml = '<ul class="uslugi-parts">'; // открываем список
                foreach ($partsData as $part) { // перебираем данные
                    // Формируем строку: Ссылка на товар (Цена руб., Остаток: X шт.)
                    $partsHtml .= '<li><a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID='.$this->arParams['PARTS_IBLOCK_ID'].'&type=catalog&ID='.$part['ID'].'" target="_blank">' 
                                . htmlspecialcharsbx($part['NAME']) . '</a>' 
                                . ' — <b>' . $part['PRICE'] . ' руб.</b>' 
                                . ' (Остаток: ' . $part['STOCK'] . ' шт.)</li>'; 
                }
                $partsHtml .= '</ul>'; // закрываем список
            }

            $detailUrl = str_replace('#ID#', $item['ID'], $this->arParams['DETAIL_URL']); // URL детальной страницы

            $rows[] = [ // одна строка грида
                'id' => $item['ID'], // ID строки
                'data' => [ // данные ячеек
                    'ID' => $item['ID'], // ID
                    'NAME' => htmlspecialcharsbx($item['NAME']), // название
                    'PRICE' => '<span class="uslugi-price">' . (float)$item['PROPERTY_PRICE_VALUE'] . '</span>', // цена
                    'NORMO_HOURS' => htmlspecialcharsbx($item['PROPERTY_NORMO_HOURS_VALUE']), // нормо-часы
                    'PARTS' => $partsHtml, // запчасти HTML
                    'DETAIL_URL' => $detailUrl, // URL для клика по строке
                ],
                'actions' => [ // меню действий строки
                    [
                        'text' => 'Редактировать', // пункт меню
                        'onclick' => 'uslugiServisa.editRow(' . (int)$item['ID'] . ');', // вызов JS
                    ],
                    [
                        'text' => 'Удалить', // пункт меню
                        'onclick' => 'uslugiServisa.deleteRow(' . (int)$item['ID'] . ', \'' . CUtil::JSEscape($this->arParams['GRID_ID']) . '\');', // вызов JS
                    ],
                ],
                'attrs' => [ // HTML-атрибуты строки
                    'data-detail-url' => $detailUrl, // data-detail-url для клика
                ],
            ];
        }

        return $rows; // возвращаем строки
    }

    private function getServices() // получаем услуги из инфоблока (ТОЛЬКО уникальные строки)
    {
        $items = []; // массив услуг
        $rs = \CIBlockElement::GetList( // запрос к инфоблоку
            ['ID' => 'ASC'], // сортировка
            ['IBLOCK_ID' => (int)$this->arParams['IBLOCK_ID'], 'ACTIVE' => 'Y'], // фильтр
            false, false, // без группировки и постранички
            ['ID', 'NAME', 'PROPERTY_PRICE', 'PROPERTY_NORMO_HOURS'] // поля
        );
        while ($item = $rs->GetNext()) { // перебираем результат
            $items[] = $item; // добавляем в массив
        }
        return $items; // возвращаем
    }

    private function getAllParts() // получаем все запчасти для формы
    {
        $parts = []; // массив запчастей
        // Запрашиваем элементы из инфоблока запчастей
        $rs = \CIBlockElement::GetList( 
            ['NAME' => 'ASC'], 
            ['IBLOCK_ID' => (int)$this->arParams['PARTS_IBLOCK_ID'], 'ACTIVE' => 'Y'], 
            false, false, 
            ['ID', 'NAME', 'IBLOCK_ID'] 
        );
        
        while ($item = $rs->GetNext()) { 
            $price = 0;
            $stock = 0;
            
            // Получаем торговые предложения (вариации) для этого товара
            if (\Bitrix\Main\Loader::includeModule('catalog')) {
                $offers = \CCatalogSKU::getOffersList($item['ID'], $this->arParams['PARTS_IBLOCK_ID']);
                if (!empty($offers[$item['ID']])) {
                    $offer = reset($offers[$item['ID']]); // берем первое предложение
                    $offerId = $offer['ID'];
                    
                    // Цена предложения
                    $resPrice = \CPrice::GetList([], ['PRODUCT_ID' => $offerId]);
                    if ($arPrice = $resPrice->Fetch()) {
                        $price = (float)$arPrice['PRICE'];
                    }
                    
                    // Остаток предложения
                    $resStock = \CCatalogProduct::GetList([], ['ID' => $offerId], false, false, ['QUANTITY']);
                    if ($arStock = $resStock->Fetch()) {
                        $stock = (float)$arStock['QUANTITY'];
                    }
                }
            }
            
            $parts[] = [ // одна запчасть
                'ID' => $item['ID'], // ID
                'NAME' => $item['NAME'], // название
                'PRICE' => $price, // цена
                'STOCK' => $stock, // остаток
            ];
        }
        return $parts; // возвращаем
    }

    private function getPartsNames(array $ids) // получаем данные запчастей по ID
    {
        $names = []; // массив данных
        if (!$ids) return $names; // если пусто — выходим
        
        $rs = \CIBlockElement::GetList( // запрос
            ['NAME' => 'ASC'], // сортировка
            ['ID' => $ids], // фильтр по ID
            false, false, // без группировки и постранички
            ['ID', 'NAME', 'IBLOCK_ID'] // поля
        );
        
        while ($item = $rs->GetNext()) { // перебираем
            $price = 0;
            $stock = 0;
            
            // Получаем торговые предложения (вариации) для этого товара
            if (\Bitrix\Main\Loader::includeModule('catalog')) {
                $offers = \CCatalogSKU::getOffersList($item['ID'], $this->arParams['PARTS_IBLOCK_ID']);
                if (!empty($offers[$item['ID']])) {
                    $offer = reset($offers[$item['ID']]); // берем первое предложение
                    $offerId = $offer['ID'];
                    
                    // Цена предложения
                    $resPrice = \CPrice::GetList([], ['PRODUCT_ID' => $offerId]);
                    if ($arPrice = $resPrice->Fetch()) {
                        $price = (float)$arPrice['PRICE'];
                    }
                    
                    // Остаток предложения
                    $resStock = \CCatalogProduct::GetList([], ['ID' => $offerId], false, false, ['QUANTITY']);
                    if ($arStock = $resStock->Fetch()) {
                        $stock = (float)$arStock['QUANTITY'];
                    }
                }
            }

            $names[$item['ID']] = [ // сохраняем массив с данными
                'ID' => $item['ID'],
                'NAME' => $item['NAME'],
                'PRICE' => $price,
                'STOCK' => $stock,
            ]; 
        }
        return $names; // возвращаем
    }

    public function getFormDataAction($id) // AJAX: данные для формы редактирования
    {
        $id = (int)$id; // приводим ID к int
        $item = \CIBlockElement::GetList( // запрос
            [], // без сортировки
            ['ID' => $id, 'IBLOCK_ID' => (int)$this->arParams['IBLOCK_ID']], // фильтр
            false, false, // без группировки и постранички
            ['ID', 'NAME', 'PROPERTY_PRICE', 'PROPERTY_NORMO_HOURS', 'PROPERTY_SERVICE_PARTS'] // поля
        )->GetNext(); // берём одну запись

        if (!$item) { // если не найдено
            return ['status' => 'error', 'message' => 'Услуга не найдена']; // ошибка
        }

        $parts = $item['PROPERTY_SERVICE_PARTS_VALUE'] ?? []; // ID запчастей
        if (!is_array($parts)) $parts = $parts ? [$parts] : []; // приводим к массиву

        return [ // возвращаем данные формы
            'ID' => $item['ID'], // ID
            'NAME' => $item['NAME'], // название
            'PRICE' => $item['PROPERTY_PRICE_VALUE'], // цена
            'NORMO_HOURS' => $item['PROPERTY_NORMO_HOURS_VALUE'], // нормо-часы
            'PARTS' => array_map('strval', array_filter(array_map('intval', $parts))), // запчасти строками
        ];
    }

    public function saveRowAction($fields) // AJAX: сохранение услуги
    {
        // Берем ID из переданных данных, если пусто - из глобальной переменной
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0); 
        if ($iblockId <= 0) {
            $iblockId = $GLOBALS['USLUGI_IBLOCK_ID'];
        }
        
        $id = (int)($fields['ID'] ?? 0); // ID (0 — новая)
        $name = trim($fields['NAME'] ?? ''); // название
        $price = (float)($fields['PRICE'] ?? 0); // цена
        $hours = trim($fields['NORMO_HOURS'] ?? ''); // нормо-часы
        $parts = array_map('intval', $fields['PARTS'] ?? []); // запчасти

        if ($name === '') { // название обязательно
            return ['status' => 'error', 'message' => 'Не указано название услуги']; // ошибка
        }

        $el = new \CIBlockElement(); // объект для работы с элементами
        $propValues = [ // значения свойств
            'PRICE' => $price, // цена
            'NORMO_HOURS' => $hours, // нормо-часы
            'SERVICE_PARTS' => $parts, // запчасти
        ];

        if ($id > 0) { // если редактируем
            $res = $el->Update($id, ['NAME' => $name, 'PROPERTY_VALUES' => $propValues]); // обновляем
            if (!$res) { // если ошибка
                return ['status' => 'error', 'message' => $el->LAST_ERROR]; // возвращаем текст
            }
        } else { // если добавляем
            $id = $el->Add([ // добавляем элемент
                'IBLOCK_ID' => $iblockId, // ИСПОЛЬЗУЕМ ID
                'NAME' => $name, // название
                'ACTIVE' => 'Y', // активен
                'PROPERTY_VALUES' => $propValues, // свойства
            ]);
            if (!$id) { // если ошибка
                return ['status' => 'error', 'message' => $el->LAST_ERROR]; // возвращаем текст
            }
        }

        return ['status' => 'success', 'id' => $id]; // успех
    }

    public function deleteRowAction($ids) // AJAX: удаление услуг
    {
        $ids = array_map('intval', (array)$ids); // приводим к массиву int
        $ids = array_filter($ids); // убираем нули
        if (!$ids) { // если пусто
            return ['status' => 'error', 'message' => 'Не переданы ID']; // ошибка
        }

        foreach ($ids as $id) { // перебираем ID
            \CIBlockElement::Delete($id); // удаляем элемент
        }

        return ['status' => 'success']; // успех
    }
}