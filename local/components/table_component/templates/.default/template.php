<?php
// шаблон: фильтр, калькулятор, таблица

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// подключаем стили и скрипты битрикса
\Bitrix\Main\UI\Extension::load(['ui.buttons', 'ui.forms', 'main.ui.grid', 'main.ui.filter']);

$gridId = 'currency_list_grid';
$filterId = 'currency_list_grid';

// колонки таблицы
$columns = [
    ['id' => 'CURRENCY', 'name' => 'код', 'sort' => 'CURRENCY', 'default' => true],
    ['id' => 'FULL_NAME', 'name' => 'название', 'sort' => 'FULL_NAME', 'default' => true],
    ['id' => 'AMOUNT_CNT', 'name' => 'номинал', 'sort' => 'AMOUNT_CNT', 'default' => true],
    ['id' => 'AMOUNT', 'name' => 'курс', 'sort' => 'AMOUNT', 'default' => true],
    ['id' => 'BASE_TEXT', 'name' => 'базовая', 'sort' => 'BASE', 'default' => true],
];

// собираем строки для таблицы
$gridRows = [];
foreach ($arResult['ROWS'] as $row) {
    $gridRows[] = ['id' => $row['CURRENCY'], 'data' => $row, 'actions' => []];
}
?>

<!-- блок с описанием работы приложения -->
<div style="padding: 20px; background: #eef2f5; border-radius: 8px; margin-bottom: 30px; border: 1px solid #d0d7de; font-size: 14px;">
    
    <!-- простое описание -->
    <h4 style="margin-top: 0; margin-bottom: 10px;">Описние компанента</h4>
    <ul style="margin: 0 0 20px 0; padding-left: 20px; line-height: 1.6; color: #333;">
        <li><strong>калькулятор:</strong> выберите валюту и введите сумму. Система автоматически пересчитает её в российские рубли по актуальному курсу,</li>
        <li><strong>фильтр:</strong> используйте панель над таблицей, чтобы найти нужную валюту по коду, названию или статусу базовой валюты,</li>
        <li><strong>таблица:</strong> показывает список валют. На странице отображается по 2 записи, переключаться между страницами можно с помощью кнопок внизу таблицы,</li>
        <li><strong>курс:</strong> все значения в таблице и калькуляторе указаны по отношению к российскому рублю (RUB).</li>
    </ul>

    <!-- техническое описание -->
    <h4 style="margin-top: 0; margin-bottom: 10px;"> Описание по реализации </h4>
    <ul style="margin: 0; padding-left: 20px; line-height: 1.6; color: #555; font-size: 13px;">
        <li><strong>структура компонента:</strong> папка <code>/local/components/table_component/</code> содержит файлы <code>class.php</code> (логика), <code>.description.php</code> (название в редакторе), <code>.parameters.php</code> (настройки) и папку <code>templates/.default/template.php</code> (шаблон),</li>
        <li><strong>классы:</strong> используется <code>Bitrix\Currency\CurrencyTable</code> для получения валют из базы и <code>Bitrix\Currency\CurrencyLangTable</code> для локализованных названий,</li>
        <li><strong>калькулятор, обновляется только цифра:</strong> реализован через интерфейс <code>Controllerable</code> и метод <code>calcAction</code>. Обновляется через <code>BX.ajax.runComponentAction</code>, чтоб не обновлять стрницу при каждом нажтии на кнопку "Рассчитать",</li>
        <li><strong>фильтр и таблица:</strong> компоненты <code>bitrix:main.ui.filter</code> и <code>bitrix:main.ui.grid</code> связаны через одинаковый идентификатор <code>currency_list_grid</code>,</li>
        <li><strong>параметры:</strong> для фильтра — <code>FILTER_ID</code>, <code>GRID_ID</code>, <code>FILTER</code>; для таблицы — <code>GRID_ID</code>, <code>COLUMNS</code>, <code>ROWS</code>; для навигации — <code>NAV_OBJECT</code>,</li>
        <li><strong>постраничная навигация:</strong> реализована через <code>Bitrix\Main\UI\PageNavigation</code> с размером страницы 2, передаётся в таблицу как <code>NAV_OBJECT</code>.</li>
    </ul>
</div>

<!-- фильтр -->
<?php
// выводим компонент фильтра
$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => $filterId,
    'GRID_ID' => $gridId,
    'FILTER' => [
        ['id' => 'CURRENCY', 'name' => 'код'],
        ['id' => 'FULL_NAME', 'name' => 'название'],
        ['id' => 'BASE', 'name' => 'базовая', 'type' => 'list', 'items' => ['' => 'любая', 'Y' => 'да', 'N' => 'нет']],
    ],
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true,
]);
?>

<!-- калькулятор -->
<div style="padding: 20px; background: #f8f9fa; border-radius: 8px; margin-bottom: 30px; border: 1px solid #dee2e6;">
    <h3 style="margin-top: 0;">конвертер валют</h3>
    <!-- подпись, что считаем в рубли -->
    <p style="color: #666; font-size: 0.9em; margin-top: -10px;">Курс указан к российскому рублю (RUB)</p>
    
    <div style="display: flex; gap: 15px; align-items: flex-end;">
        <div>
            <label for="calc-from">валюта:</label><br>
            <select id="calc-from" class="ui-ctl-element">
                <?php foreach ($arResult['ALL_CURRENCIES'] as $curr): ?>
                    <option value="<?= $curr['CURRENCY'] ?>"><?= $curr['FULL_NAME'] ?> (<?= $curr['CURRENCY'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="calc-amount">сумма:</label><br>
            <input type="number" id="calc-amount" class="ui-ctl-element" value="1" step="0.01">
        </div>
        <div>
            <button id="calc-btn" class="ui-btn ui-btn-primary">рассчитать</button>
        </div>
        <div>
            <strong>результат в рублях: <span id="calc-result" style="color: green; font-size: 1.2em;">0</span></strong>
        </div>
    </div>
</div>

<!-- таблица -->
<div class="currency-table-wrapper">
    <h3>список валют</h3>
    <?php
    // выводим саму таблицу
    $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
        'GRID_ID' => $gridId,
        'COLUMNS' => $columns,
        'ROWS' => $gridRows,
        'SHOW_ROW_CHECKBOXES' => false,
        'AJAX_MODE' => 'Y',
        'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
        'AJAX_OPTION_JUMP' => 'N',
        'ALLOW_SORT' => true,
        'ALLOW_COLUMNS_SORT' => true,
        'SHOW_PAGESIZE' => false,
        'SHOW_GRID_SETTINGS_MENU' => true,
        'ALLOW_COLUMNS_RESIZE' => true,
        'ALLOW_HORIZONTAL_SCROLL' => true,
        'ALLOW_PIN_HEADER' => true,
    ], false);

    // выводим навигацию по страницам
    $APPLICATION->IncludeComponent('bitrix:main.pagenavigation', '', [
        'NAV_OBJECT' => $arResult['NAV'],
        'SEF_MODE' => 'N',
    ], false);
    ?>
</div>

<!-- js для калькулятора -->
<script>
    // ждем, пока страница загрузится, чтобы найти все элементы
    document.addEventListener('DOMContentLoaded', function() {
        
        // находим кнопку, выпадающий список, поле ввода и место для результата
        const btn = document.getElementById('calc-btn');
        const fromSelect = document.getElementById('calc-from');
        const amountInput = document.getElementById('calc-amount');
        const resultSpan = document.getElementById('calc-result');

        // когда нажимают на кнопку "рассчитать"
        btn.addEventListener('click', function() {
            
            // берем выбранную валюту и введенную сумму
            const fromCurrency = fromSelect.value;
            const amount = amountInput.value;
            
            // показываем, что идет загрузка
            resultSpan.innerText = 'загрузка...';

            // отправляем ajax запрос в наш компонент (в метод calcAction)
            BX.ajax.runComponentAction('table_component', 'calc', {
                mode: 'class',
                data: { 
                    fromCurrency: fromCurrency, 
                    amount: amount 
                }
            }).then(function(response) {
                // если сервер ответил успешно — показываем результат
                if (response.data.success) {
                    resultSpan.innerText = response.data.sum;
                } else {
                    // если ошибка — показываем текст ошибки
                    resultSpan.innerText = 'ошибка: ' + response.data.error;
                }
            }).catch(function() {
                // если запрос вообще не прошел (например, упал сервер)
                resultSpan.innerText = 'ошибка запроса';
            });
        });
    });
</script>