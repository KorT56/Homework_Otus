<?
// подключаем шапку битрикса чтобы страница была в общем шаблоне портала
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

// задаём заголовок страницы который увидит пользователь в браузере и в хлебных крошках
$APPLICATION->SetTitle("Автомобили");

// подключаем нужные классы битрикса через use чтобы не писать длинные пути каждый раз
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Iblock\ElementTable;

// проверяем что модуль инфоблоков установлен, иначе дальше работать нет смысла
if (!Loader::includeModule('iblock')) {
    echo 'Модуль iblock не подключён';
    return;
}

// объявляем класс-обёртку над нашей таблицей model_avto_klient_info чтобы работать через orm а не через сырые sql-запросы
if (!class_exists('AvtoKlientInfoTable')) {
    class AvtoKlientInfoTable extends DataManager
    {
        // говорим битриксу с какой именно таблицей в базе работает этот класс
        public static function getTableName()
        {
            return 'model_avto_klient_info';
        }

        // описываем все поля таблицы чтобы orm знал их типы и умел с ними работать
        public static function getMap()
        {
            return [
                // id — первичный ключ, автозаполняется при добавлении записи
                new IntegerField('ID', ['primary' => true, 'autocomplete' => true]),
                // гос номер автомобиля, строка
                new StringField('NOMER_AVTOMOBILYA'),
                // пробег в километрах, целое число
                new IntegerField('PROBEG_AVTOMOBILYA'),
                // id элемента инфоблока с маркой (BMW, LADA и т.д.)
                new IntegerField('MARKA_AVTOMOBILYA_MODEL'),
                // id элемента инфоблока с названием модели (Civic, Granta и т.д.)
                new IntegerField('MODEL_AVTOMOBILYA_ID'),
                // id элемента инфоблока с годом выпуска
                new IntegerField('GOD_AVTOMOBILYA'),
                // цвет кузова, строка
                new StringField('COLOR'),
                // фио клиента, строка
                new StringField('CLIENT_NAME'),
                // названия сделок — храним как json-массив, чтобы можно было вывести несколько
                new TextField('DEAL_NAME'),
            ];
        }
    }
}

// константы с id инфоблоков чтобы не писать числа по коду
if (!defined('IBLOCK_MODELS'))      define('IBLOCK_MODELS', 19);
if (!defined('IBLOCK_MODEL_NAMES')) define('IBLOCK_MODEL_NAMES', 20);
if (!defined('IBLOCK_YEARS'))       define('IBLOCK_YEARS', 21);

// синхронизируем нашу таблицу с инфоблоком 20 — берём все элементы инфоблока и раскладываем их по полям таблицы
// если элемент новый — добавляем строку, если уже есть — обновляем поля
// это делается при каждом открытии страницы чтобы новые элементы автоматически появлялись в таблице
$syncAdded   = 0;
$syncUpdated = 0;
$syncError   = '';

try {
    // тянем из инфоблока только базовые поля — свойства будем читать отдельно через GetProperty
    // это нужно потому что GetList может отдавать только первое значение множественного свойства
    $rsElements = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => IBLOCK_MODEL_NAMES, 'ACTIVE' => 'Y'],
        false,
        false,
        [
            'ID',
            'NAME',
            'PROPERTY_NOMER_AVTOMOBILYA',
            'PROPERTY_PROBEG_AVTOMOBILYA',
            'PROPERTY_MARKA_AVTOMOBILYA_MODEL',
            'PROPERTY_GOD_AVTOMOBILYA',
            'PROPERTY_TSVET_AVTOMOBILYA',
            'PROPERTY_PRIVYAZKA_K_KLIENTU_IZ_MODELI',
        ]
    );

    // собираем все элементы инфоблока в массив и заодно копим id сделок чтобы потом одним запросом получить их названия
    $elements   = [];
    $allDealIds = [];
    while ($el = $rsElements->Fetch()) {
        $elements[] = $el;

        // читаем все значения свойства PRIVYAZKA_K_SDELKAM_IZ_MODELI через GetProperty
        // так мы гарантированно получим все привязанные сделки, а не только первую
        $modelId = (int)$el['ID'];
        $rsProp  = CIBlockElement::GetProperty(
            IBLOCK_MODEL_NAMES,
            $modelId,
            [],
            ['CODE' => 'PRIVYAZKA_K_SDELKAM_IZ_MODELI']
        );
        while ($prop = $rsProp->Fetch()) {
            // значение может быть строкой или массивом — приводим к id
            $v = $prop['VALUE'];
            if (is_array($v)) {
                foreach ($v as $one) {
                    $id = (int)$one;
                    if ($id > 0) $allDealIds[$id] = $id;
                }
            } else {
                $id = (int)$v;
                if ($id > 0) $allDealIds[$id] = $id;
            }
        }
    }

    // одним запросом получаем названия всех сделок из crm чтобы не дёргать базу по каждой отдельно
    $dealTitles = [];
    if (!empty($allDealIds) && Loader::includeModule('crm')) {
        $rsDeals = \Bitrix\Crm\DealTable::getList([
            'select' => ['ID', 'TITLE'],
            'filter' => ['@ID' => array_values($allDealIds)],
        ]);
        while ($deal = $rsDeals->fetch()) {
            $dealTitles[(int)$deal['ID']] = (string)$deal['TITLE'];
        }
    }

    // теперь проходим по собранным элементам и пишем их в таблицу
    foreach ($elements as $el) {
        // id элемента инфоблока — это и есть id модели в нашей таблице
        $modelId = (int)$el['ID'];

        // ищем строку в таблице по id модели — вдруг она уже есть
        $existing = AvtoKlientInfoTable::query()
            ->setSelect(['ID'])
            ->where('MODEL_AVTOMOBILYA_ID', $modelId)
            ->setLimit(1)
            ->exec()
            ->fetch();

        // собираем массив названий сделок — снова через GetProperty чтобы получить все значения
        $dealNames = [];
        $rsProp    = CIBlockElement::GetProperty(
            IBLOCK_MODEL_NAMES,
            $modelId,
            [],
            ['CODE' => 'PRIVYAZKA_K_SDELKAM_IZ_MODELI']
        );
        while ($prop = $rsProp->Fetch()) {
            $v = $prop['VALUE'];
            // значение может быть одиночным или массивом
            $ids = is_array($v) ? $v : [$v];
            foreach ($ids as $one) {
                $id = (int)$one;
                if ($id <= 0) continue;
                // если название нашли — пишем его, если сделки нет в crm — помечаем что она удалена
                if (isset($dealTitles[$id])) {
                    $dealNames[] = $dealTitles[$id];
                } else {
                    $dealNames[] = 'Сделка удалена (ID ' . $id . ')';
                }
            }
        }

        // сохраняем как json-массив, чтобы потом на странице развернуть обратно и вывести все названия
        // если сделок нет — пишем пустую строку
        $dealNameJson = !empty($dealNames) ? json_encode($dealNames, JSON_UNESCAPED_UNICODE) : '';

        // собираем поля которые будем писать в таблицу из свойств элемента инфоблока
        // если свойства нет — поле останется пустым (null)
        $fields = [
            'MODEL_AVTOMOBILYA_ID'    => $modelId,
            'NOMER_AVTOMOBILYA'       => $el['PROPERTY_NOMER_AVTOMOBILYA_VALUE']       ?? null,
            'PROBEG_AVTOMOBILYA'      => isset($el['PROPERTY_PROBEG_AVTOMOBILYA_VALUE'])      ? (int)$el['PROPERTY_PROBEG_AVTOMOBILYA_VALUE']      : null,
            'MARKA_AVTOMOBILYA_MODEL' => isset($el['PROPERTY_MARKA_AVTOMOBILYA_MODEL_VALUE']) ? (int)$el['PROPERTY_MARKA_AVTOMOBILYA_MODEL_VALUE'] : null,
            'GOD_AVTOMOBILYA'         => isset($el['PROPERTY_GOD_AVTOMOBILYA_VALUE'])         ? (int)$el['PROPERTY_GOD_AVTOMOBILYA_VALUE']         : null,
            'COLOR'                   => $el['PROPERTY_TSVET_AVTOMOBILYA_VALUE']               ?? null,
            'CLIENT_NAME'             => $el['PROPERTY_PRIVYAZKA_K_KLIENTU_IZ_MODELI_VALUE']   ?? null,
            'DEAL_NAME'               => $dealNameJson,
        ];

        if ($existing) {
            // строка уже есть — просто перезаписываем поля, чтобы данные в таблице совпадали с инфоблоком и crm
            AvtoKlientInfoTable::update((int)$existing['ID'], $fields);
            $syncUpdated++;
        } else {
            // строки ещё нет — создаём новую с этим id модели
            AvtoKlientInfoTable::add($fields);
            $syncAdded++;
        }
    }
} catch (\Throwable $e) {
    // если что-то пошло не так — сохраняем текст ошибки чтобы показать её пользователю
    $syncError = 'Ошибка синхронизации: ' . $e->getMessage();
}

// массив куда сложим все машины из базы для вывода в таблицу
$cars = [];
// сюда запишем текст ошибки если запрос машин упадёт
$queryError = '';

try {
    // строим orm-запрос к нашей таблице
    $query = AvtoKlientInfoTable::query()
        // выбираем только те поля которые реально нужны для таблицы и инфоблока
        ->setSelect([
            'ID',
            'NOMER_AVTOMOBILYA',
            'COLOR',
            'DEAL_NAME',
            // через runtime-поля достаём названия из связанных инфоблоков вместо id
            'MARKA_NAME' => 'MARKA_ELEMENT.NAME',
            'MODEL_NAME' => 'MODEL_ELEMENT.NAME',
            'GOD_NAME'   => 'GOD_ELEMENT.NAME',
        ])
        // связываем поле марки с элементами инфоблока по id
        ->registerRuntimeField(
            new Reference('MARKA_ELEMENT', ElementTable::class,
                Join::on('this.MARKA_AVTOMOBILYA_MODEL', 'ref.ID'))
        )
        // связываем поле модели с элементами инфоблока по id
        ->registerRuntimeField(
            new Reference('MODEL_ELEMENT', ElementTable::class,
                Join::on('this.MODEL_AVTOMOBILYA_ID', 'ref.ID'))
        )
        // связываем поле года с элементами инфоблока по id
        ->registerRuntimeField(
            new Reference('GOD_ELEMENT', ElementTable::class,
                Join::on('this.GOD_AVTOMOBILYA', 'ref.ID'))
        );

    // выполняем запрос
    $result = $query->exec();
    // перебираем все строки результата и складываем в массив
    while ($row = $result->fetch()) {
        $cars[] = $row;
    }
} catch (\Throwable $e) {
    // если что-то пошло не так — сохраняем текст ошибки чтобы показать пользователю
    $queryError = 'Ошибка запроса: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автомобили</title>
    <style>
        /* считаем ширину и отступы внутри элементов а не снаружи — так проще верстать */
        * { box-sizing: border-box; }

        /* общий стиль страницы — шрифт, цвет, отступы, фон */
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #fafbfc;
        }

        /* ограничиваем ширину контента и центрируем его по горизонтали */
        .cars-page { max-width: 1200px; margin: 0 auto; }

        /* коробка для показа ошибок */
        .status-box {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 1.5;
        }
        /* красный вариант для ошибок */
        .status-box.err { background: #fdecea; color: #a01919; border: 1px solid #f0b0aa; }
        /* синий вариант для информационных сообщений */
        .status-box.info { background: #eaf2fb; color: #1f4d80; border: 1px solid #b7d3ee; }

        /* главный заголовок страницы */
        .cars-page h1 {
            color: #2c3e50;
            font-size: clamp(20px, 3vw, 32px);
            font-weight: 500;
            margin: 0 0 24px;
            text-align: center;
        }

        /* блок с информацией об автомобиле — по умолчанию скрыт, показывается при клике */
        .car-info-box {
            background: #f0f8ff;
            border: 2px solid #4682b4;
            border-radius: 10px;
            padding: clamp(14px, 2.5vw, 22px) clamp(16px, 3vw, 28px);
            margin-bottom: 24px;
            display: none;
            animation: fadeIn .25s ease;
        }
        /* класс который добавляется через js чтобы блок появился */
        .car-info-box.visible { display: block; }

        /* плавное появление блока */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* заголовок внутри информационного блока */
        .car-info-box h2 {
            margin: 0 0 14px;
            color: #2c3e50;
            font-size: clamp(16px, 2.2vw, 20px);
            font-weight: 600;
        }

        /* одна пара «подпись + значение» в инфоблоке */
        .car-info-item { font-size: clamp(14px, 1.6vw, 16px); line-height: 1.4; }
        /* мелкая серая подпись над значением */
        .car-info-item .label {
            display: block;
            font-size: clamp(11px, 1.2vw, 12px);
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #7a828b;
            margin-bottom: 3px;
        }
        /* контейнер со значениями — если сделок несколько они выводятся списком */
        .car-info-item .value { color: #222; font-weight: 500; }

        /* каждая сделка в списке — отдельная строка с небольшим отступом снизу */
        .deal-line {
            padding: 4px 0;
            border-bottom: 1px dashed #cfe0ef;
        }
        /* у последней сделки в списке убираем разделитель */
        .deal-line:last-child { border-bottom: none; }
        /* номер сделки в списке — серый и поменьше */
        .deal-line .deal-num {
            color: #7a828b;
            font-weight: 400;
            margin-right: 6px;
        }
        /* если сделок нет — серый курсив */
        .deal-empty { color: #999; font-style: italic; }

        /* обёртка чтобы таблица могла скроллиться по горизонтали на узких экранах */
        .car-table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        /* сама таблица — растягиваем на всю ширину и убираем двойные рамки */
        .car-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
            background: #fff;
        }
        /* ячейки и заголовки — отступы и тонкая нижняя линия */
        .car-table th,
        .car-table td {
            border-bottom: 1px solid #e3e8ee;
            padding: clamp(10px, 1.5vw, 14px) clamp(12px, 1.8vw, 18px);
            text-align: left;
            font-size: clamp(13px, 1.4vw, 15px);
        }
        /* шапка таблицы — голубоватый фон и потолще нижняя граница */
        .car-table thead th {
            background-color: #d0e4f5;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #b0c4de;
            white-space: nowrap;
        }
        /* строки таблицы — курсор-рука и плавная смена фона при наведении */
        .car-table tbody tr { cursor: pointer; transition: background-color .15s; }
        .car-table tbody tr:hover { background-color: #f5faff; }
        /* активная строка — та на которую кликнули */
        .car-table tbody tr.active { background-color: #d0e4f5; }
        /* у последней строки убираем нижнюю границу чтобы не дублировалась с рамкой */
        .car-table tbody tr:last-child td { border-bottom: none; }

        /* гос номер — моноширинный шрифт чтобы цифры смотрелись ровно */
        .car-table .cell-nomer  { font-family: Consolas, Monaco, monospace; font-weight: 600; }
        /* марка — потемнее и пожирнее остальных */
        .car-table .cell-marka  { color: #2c3e50; font-weight: 500; }
        /* модель и цвет — серо-синие, менее акцентные */
        .car-table .cell-model  { color: #4a5a70; }
        .car-table .cell-color  { color: #4a5a70; }

        /* строка «нет данных» — по центру и курсивом */
        .car-table .no-data td {
            text-align: center;
            color: #999;
            font-style: italic;
        }

        /* мобильная версия — на узких экранах превращаем таблицу в карточки */
        @media (max-width: 640px) {
            body { padding: 12px; }
            .car-table-wrap { overflow-x: visible; box-shadow: none; }
            .car-table { min-width: 0; border-collapse: separate; border-spacing: 0; }
            /* прячем шапку — она уже не нужна в карточном режиме */
            .car-table thead { display: none; }
            /* все элементы таблицы становятся блочными */
            .car-table, .car-table tbody, .car-table tr, .car-table td {
                display: block; width: 100%;
            }
            /* каждая строка превращается в карточку с рамкой и тенью */
            .car-table tbody tr {
                background: #fff;
                border: 1px solid #e3e8ee;
                border-radius: 8px;
                padding: 12px 14px;
                margin-bottom: 10px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            }
            /* активная карточка — с синей рамкой */
            .car-table tbody tr.active { border-color: #4682b4; background: #f0f8ff; }
            /* ячейки внутри карточки — подпись слева, значение справа */
            .car-table td {
                border: none;
                padding: 4px 0;
                display: flex;
                justify-content: space-between;
                align-items: baseline;
                gap: 12px;
                font-size: 14px;
                text-align: right;
            }
            /* подпись берём из атрибута data-label который расставлен в html */
            .car-table td::before {
                content: attr(data-label);
                font-size: 12px;
                text-transform: uppercase;
                letter-spacing: .4px;
                color: #8a94a0;
                font-weight: 500;
                text-align: left;
                flex-shrink: 0;
            }
            /* блок «нет данных» в мобильной версии — по центру */
            .car-table .no-data { display: block; padding: 20px 0; text-align: center; }
            .car-table .no-data td { display: block; text-align: center; }
            .car-table .no-data td::before { content: none; }
        }

        /* если пользователь в системе попросил уменьшить анимацию — отключаем появление */
        @media (prefers-reduced-motion: reduce) {
            .car-info-box { animation: none; }
        }
    </style>
</head>
<body>
   <p>
Страница расположена по адресу: https://cb313664.tw1.ru/local/homeworks/homework_4/index_6.php В левом меню пункт: "Сделки для автомобилей/Модель данных для таблицы БД"

Для работы, нужно нажать на любую строку в Таблице, появится блок, в нем отобразится название Сделки. Сделки добавлены в таблице заранее. При добавлении новых Сделок, если не обновить Список 20, то они не отобразятся пока. Нужно подумать.

Таблица со столбцами: Марка, Модель, Цвет, Год, Гос. номер. Информационный блок над таблицей с полем «Название сделки» — изначально скрыт. Открывается после клика на строку таблицы При клике по строке — блок появляется и показывает сделку этой машины. ID: 19 Марки ID: 20 Полная информация о модели ID: 21 Годы выпуска

NOMER_AVTOMOBILYA Гос. номер машины MARKA_AVTOMOBILYA_MODEL ID элемента инфоблока «марки» MODEL_AVTOMOBILYA_ID ID элемента инфоблока «модели» GOD_AVTOMOBILYA ID элемента инфоблока «годы» COLOR Цвет DEAL_NAME Название сделки PRIVYAZKA_K_SDELKAM_IZ_MODELI - ID сделка

Таблица Нужно отдельно загрузить значения в таблицу model_avto_klient_info

IntegerField — цисло (ID, пробег, ID инфоблоков). StringField — строка (гос. номер, цвет, клиент). TextField — название сделки (название сделки). Получаю из таблицы model_avto_klient_info, в которые они ранее были подгружены.

$cars = [] - массив для машин, он перебирается для подстановки в визуальную часть

Создается связь между таблицей и инфоблоком через Join::on(). Используется для подтягивания названий марок, моделей и годов.

AvtoKlientInfoTable::query() Создаёт пример запроса ->setSelect([...]) Указывает, какие поля выбрать ->registerRuntimeField(...) подключает связь полей, которые существуют временно. ->where(...) фильтрации ->setLimit(1) Ограничение количества записей ->fetch() Забирает одну строку результата ->fetchAll() Забирает сразу все строки

AvtoKlientInfoTable::add, AvtoKlientInfoTable::update - добавляет обновляет позиции.

$APPLICATION->SetTitle() Устанавливает заголовок страницы require(header.php) Подключает шапку сайта require(footer.php) Подключает подвал сайта

Подключается модуль инфоблоков и шапка Битрикс. Проверяется, есть ли таблица model_avto_klient_info в базе. Если нет — создаётся. Если есть, но не хватает полей — они добавляются. Для Таблицы model_avto_klient_info берется информация из ID: 20 Полная информация о модели. Таблица обновляется по этому списку. То есть, если в Списке, нет информации о сделке в поле PRIVYAZKA_K_SDELKAM_IZ_MODELI, то сделка не отобразиться в форме. Скорей всего позже мы узнаем, как их синхронизировать, чтоб руками не добавлять Сделки в список. Через ORM делается один запрос к таблице, в котором Join-ит три списка, они подтягивают названия марок, моделей и годов из инфоблоков. Все машины сохраняются в массив $cars. HTML-шаблон перебирает массив, собирает в таблице. обработчик клика на каждую строку. При клике — название сделки из data-deal подставляется в информационный блок, блок становится видимым.
    </p>

<div class="cars-page">

    <?php // если при синхронизации с инфоблоком была ошибка — показываем её ?>
    <?php if ($syncError !== ''): ?>
        <div class="status-box err"><?= htmlspecialcharsbx($syncError) ?></div>
    <?php endif; ?>

    <?php // если синхронизация что-то добавила или обновила — показываем короткое сообщение ?>
    <?php if ($syncAdded > 0 || $syncUpdated > 0): ?>
        <div class="status-box info">
            Синхронизация с каталогом: добавлено <b><?= $syncAdded ?></b>,
            обновлено <b><?= $syncUpdated ?></b>.
        </div>
    <?php endif; ?>

    <?php // если при запросе машин была ошибка — показываем её красным блоком ?>
    <?php if ($queryError !== ''): ?>
        <div class="status-box err"><?= htmlspecialcharsbx($queryError) ?></div>
    <?php endif; ?>

    <?php // блок информации об автомобиле — показываем названия сделок выбранной машины ?>
    <div id="carInfoBox" class="car-info-box">
        <h2>Информация об автомобиле</h2>
        <div class="car-info-item">
            <span class="label">Названия сделок</span>
            <?php // сюда javascript подставит названия из data-deal выбранной строки — каждое отдельной строкой ?>
            <div class="value" id="infoDeal"></div>
        </div>
    </div>

    <?php // главный заголовок страницы ?>
    <h1>Автомобили</h1>

    <?php // таблица со списком всех машин из нашей таблицы ?>
    <div class="car-table-wrap">
        <table class="car-table">
            <thead>
                <tr>
                    <th>Марка</th>
                    <th>Модель</th>
                    <th>Цвет</th>
                    <th>Год</th>
                    <th>Гос. номер</th>
                </tr>
            </thead>
            <tbody>
                <?php // перебираем все машины и выводим по одной строке на каждую ?>
                <?php foreach ($cars as $car): ?>
                    <?php
                        // разворачиваем json-массив названий сделок обратно в массив
                        // если строка пустая или битая — получим пустой массив
                        $dealList = [];
                        if (!empty($car['DEAL_NAME'])) {
                            $decoded = json_decode($car['DEAL_NAME'], true);
                            if (is_array($decoded)) {
                                $dealList = $decoded;
                            }
                        }
                        // для data-атрибута ещё раз кодируем в json — чтобы js на клиенте разобрал массив
                        $dealJsonAttr = htmlspecialcharsbx(json_encode($dealList, JSON_UNESCAPED_UNICODE));
                    ?>
                    <?php // в data-deal кладём json-массив названий сделок чтобы js мог вывести их при клике ?>
                    <tr data-deal="<?= $dealJsonAttr ?>">
                        <?php // марка — берём из связанного инфоблока через runtime-поле ?>
                        <td class="cell-marka" data-label="Марка"><?= htmlspecialcharsbx($car['MARKA_NAME'] ?? '—') ?></td>
                        <?php // модель — тоже из инфоблока ?>
                        <td class="cell-model" data-label="Модель"><?= htmlspecialcharsbx($car['MODEL_NAME'] ?? '—') ?></td>
                        <?php // цвет — из нашей таблицы (свойство TSVET_AVTOMOBILYA) ?>
                        <td class="cell-color" data-label="Цвет"><?= htmlspecialcharsbx($car['COLOR'] ?? '—') ?></td>
                        <?php // год — название элемента из инфоблока лет ?>
                        <td class="cell-god"   data-label="Год"><?= htmlspecialcharsbx($car['GOD_NAME'] ?? '—') ?></td>
                        <?php // гос номер — из нашей таблицы ?>
                        <td class="cell-nomer" data-label="Гос. номер"><?= htmlspecialcharsbx($car['NOMER_AVTOMOBILYA'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php // если машин нет вообще — показываем одну строку с сообщением ?>
                <?php if (empty($cars)): ?>
                    <tr class="no-data"><td colspan="5">Нет данных для отображения</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
// оборачиваем всё в самовызывающуюся функцию чтобы не мусорить в глобальной области
(function () {
    'use strict';

    // функция показывает информацию о выбранной машине в верхнем блоке
    function showCarInfo(row) {
        // берём из data-атрибута json-массив названий сделок и превращаем обратно в массив
        let deals = [];
        try {
            deals = JSON.parse(row.dataset.deal || '[]');
        } catch (e) {
            deals = [];
        }
        // на всякий случай приводим к массиву — если пришло что-то другое
        if (!Array.isArray(deals)) deals = [];

        // очищаем контейнер и наполняем его заново
        const container = document.getElementById('infoDeal');
        container.innerHTML = '';

        if (deals.length === 0) {
            // если сделок нет — пишем прочерк серым курсивом
            const empty = document.createElement('span');
            empty.className = 'deal-empty';
            empty.textContent = '—';
            container.appendChild(empty);
        } else {
            // если сделки есть — выводим каждую отдельной строкой с порядковым номером
            deals.forEach(function (title, index) {
                const line = document.createElement('div');
                line.className = 'deal-line';

                // номер сделки в списке — маленький серый
                const num = document.createElement('span');
                num.className = 'deal-num';
                num.textContent = (index + 1) + '.';

                // сам текст названия сделки
                const text = document.createElement('span');
                text.textContent = title || '—';

                line.appendChild(num);
                line.appendChild(text);
                container.appendChild(line);
            });
        }

        // делаем блок видимым — добавляем класс visible
        const box = document.getElementById('carInfoBox');
        box.classList.add('visible');

        // снимаем подсветку со всех строк таблицы
        document.querySelectorAll('.car-table tbody tr').forEach(function (tr) {
            tr.classList.remove('active');
        });
        // подсвечиваем ту строку по которой кликнули
        row.classList.add('active');

        // плавно прокручиваем страницу к информационному блоку
        box.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ждём когда дом загрузится, чтобы все элементы точно были на месте
    document.addEventListener('DOMContentLoaded', function () {
        // находим все строки машин — у них есть атрибут data-deal
        document.querySelectorAll('.car-table tbody tr[data-deal]').forEach(function (tr) {
            // на каждую строку вешаем обработчик клика
            tr.addEventListener('click', function (e) {
                // если кликнули по ссылке внутри — не мешаем, пусть работает переход
                if (e.target.closest('a')) return;
                // иначе показываем информацию об этой машине
                showCarInfo(this);
            });
        });
    });
})();
</script>

<? // подключаем подвал битрикса чтобы закрыть html и подгрузить нужные скрипты ?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>