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
                // название сделки, длинный текст
                new TextField('DEAL_NAME'),
            ];
        }
    }
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
        /* само значение — чёрное и пожирнее */
        .car-info-item .value { color: #222; font-weight: 500; }

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

<div class="cars-page">

    <?php // если при запросе машин была ошибка — показываем её красным блоком ?>
    <?php if ($queryError !== ''): ?>
        <div class="status-box err"><?= htmlspecialcharsbx($queryError) ?></div>
    <?php endif; ?>

    <?php // блок информации об автомобиле — показываем название сделки выбранной машины ?>
    <div id="carInfoBox" class="car-info-box">
        <h2>Информация об автомобиле</h2>
        <div class="car-info-item">
            <span class="label">Название сделки</span>
            <?php // сюда javascript подставит значение из data-deal выбранной строки ?>
            <span class="value" id="infoDeal">—</span>
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
                    <?php // в data-deal кладём название сделки чтобы js мог показать его при клике ?>
                    <tr data-deal="<?= htmlspecialcharsbx($car['DEAL_NAME'] ?? 'Не указана') ?>">
                        <?php // марка — берём из связанного инфоблока через runtime-поле ?>
                        <td class="cell-marka" data-label="Марка"><?= htmlspecialcharsbx($car['MARKA_NAME'] ?? '—') ?></td>
                        <?php // модель — тоже из инфоблока ?>
                        <td class="cell-model" data-label="Модель"><?= htmlspecialcharsbx($car['MODEL_NAME'] ?? '—') ?></td>
                        <?php // цвет — прямо из нашей таблицы ?>
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
        // берём из data-атрибута строки название сделки и подставляем в блок
        document.getElementById('infoDeal').textContent = row.dataset.deal || '—';

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
