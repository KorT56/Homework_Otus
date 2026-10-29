<?php
//  фильтр, навигация, калькулятор

// защита от прямого доступа к файлу
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// подключаем нужные классы битрикса
use Bitrix\Main\Loader;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Currency\CurrencyTable;
use Bitrix\Currency\CurrencyLangTable;

// наш класс компонента
class TableComponent extends \CBitrixComponent implements Controllerable
{
    // настраиваем ajax для калькулятора, разрешение метод calcAction
    public function configureActions()
    {
        return ['calc' => ['prefilters' => []]];
    }

    // метод для калькулятора (всегда переводит в рубли)
    public function calcAction($fromCurrency, $amount)
    {
        // проверяем, подключен ли модуль валют
        if (!Loader::includeModule('currency')) return ['error' => 'модуль не установлен'];
        
        // приводим сумму к числу, а код валюты чистим от скриптов
        $amount = (float)$amount;
        $from = htmlspecialcharsbx($fromCurrency);
        $to = 'RUB'; // всегда переводим в рубли

        // получаем курс исходной валюты
        $fromData = CurrencyTable::getList(['filter' => ['=CURRENCY' => $from], 'select' => ['AMOUNT', 'BASE']])->fetch();
        // получаем курс рубля (целевой валюты)
        $toData = CurrencyTable::getList(['filter' => ['=CURRENCY' => $to], 'select' => ['AMOUNT', 'BASE']])->fetch();

        // если исходной валюты нет в базе ошибка
        if (!$fromData) return ['error' => 'валюта не найдена'];
        
        // если валюта базовая — курс 1, иначе берем из базы, не считаем, выводим такое же число
        $fromRate = $fromData['BASE'] === 'Y' ? 1 : (float)$fromData['AMOUNT'];
        // если рубль базовый — курс 1, иначе берем из базы
        $toRate = ($toData && $toData['BASE'] !== 'Y') ? (float)$toData['AMOUNT'] : 1;

        // считаем итоговую сумму: (сумма * курс_откуда) / курс_куда
        $resultSum = ($amount * $fromRate) / $toRate;

        // форматируем число, не нужныесущности &nbsp;
        $formattedSum = CCurrencyLang::CurrencyFormat($resultSum, $to);
        $formattedSum = html_entity_decode($formattedSum, ENT_QUOTES, 'UTF-8');

        return ['success' => true, 'sum' => $formattedSum];
    }

    // если PAGE_SIZE не задан - 2)
    public function onPrepareComponentParams($arParams)
    {
        if (empty($arParams['PAGE_SIZE'])) $arParams['PAGE_SIZE'] = 2;
        return $arParams;
    }

    // главный метод, который собирает данные для страницы
    public function executeComponent()
    {
        // проверяем, установлен ли модуль валют
        if (!Loader::includeModule('currency')) {
            ShowError("модуль валют не установлен");
            return;
        }

        // получаем все валюты для выпадающего списка в калькуляторе
        $allCurrencies = [];
        $dbAll = CurrencyTable::getList(['select' => ['CURRENCY']]);
        while ($row = $dbAll->fetch()) {
            // для каждой валюты достаем русское название
            $lang = CurrencyLangTable::getList([
                'filter' => ['=CURRENCY' => $row['CURRENCY'], '=LID' => LANGUAGE_ID],
                'select' => ['FULL_NAME']
            ])->fetch();
            $row['FULL_NAME'] = $lang ? $lang['FULL_NAME'] : $row['CURRENCY'];
            $allCurrencies[] = $row;
        }
        $this->arResult['ALL_CURRENCIES'] = $allCurrencies;

        // собираем фильтр из компонента main.ui.filter
        $filterOption = new FilterOptions('currency_list_grid');
        $filterData = $filterOption->getFilter([]);
        
        // формируем условия для базы данных
        $arFilter = [];
        if (!empty($filterData['CURRENCY'])) $arFilter['=CURRENCY'] = $filterData['CURRENCY'];
        if (!empty($filterData['FULL_NAME'])) $arFilter['%FULL_NAME'] = $filterData['FULL_NAME'];
        if (!empty($filterData['BASE'])) $arFilter['=BASE'] = $filterData['BASE'];

        // настраиваем навигацию (по 2 элемента на странице)
        $nav = new PageNavigation("nav-currency");
        $nav->allowAllRecords(true)
            ->setPageSize($this->arParams['PAGE_SIZE'])
            ->initFromUri();

        // получаем данные для таблицы с учетом фильтра и навигации
        $dbResult = CurrencyTable::getList([
            'select' => ['CURRENCY', 'AMOUNT', 'AMOUNT_CNT', 'SORT', 'BASE'],
            'filter' => $arFilter,
            'order' => ['SORT' => 'ASC'],
            'count_total' => true,
            'offset' => $nav->getOffset(),
            'limit' => $nav->getLimit(),
        ]);

        // передаем навигации общее количество записей
        $nav->setRecordCount($dbResult->getCount());

        // собираем строки для шаблона
        $arRows = [];
        while ($row = $dbResult->fetch()) {
            // достаем русское название валюты
            $lang = CurrencyLangTable::getList([
                'filter' => ['=CURRENCY' => $row['CURRENCY'], '=LID' => LANGUAGE_ID],
                'select' => ['FULL_NAME']
            ])->fetch();
            
            $row['FULL_NAME'] = $lang ? $lang['FULL_NAME'] : $row['CURRENCY'];
            // преобразуем Y/N в "да"/"нет"
            $row['BASE_TEXT'] = $row['BASE'] === 'Y' ? 'да' : 'нет';
            $arRows[] = $row;
        }

        // передаем все данные в шаблон
        $this->arResult['ROWS'] = $arRows;
        $this->arResult['NAV'] = $nav;

        // вызываем шаблон
        $this->includeComponentTemplate();
    }
}