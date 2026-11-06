<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); // защита от прямого вызова

$componentId = $this->getComponent()->getName(); // имя компонента для AJAX
$signedParameters = $this->getComponent()->getSignedParameters(); // подписанные параметры для AJAX

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/grid/webform-button.css'); // стили кнопок грида
?>
<style>
.uslugi-servisa-wrap {
    margin: 20px 0;
}
.uslugi-servisa-wrap__title {
    font-size: 24px;
    margin-bottom: 15px;
}
.uslugi-servisa-wrap .uslugi-price {
    color: #d0021b;
    font-weight: bold;
}
.uslugi-servisa-wrap .uslugi-parts {
    margin: 0;
    padding-left: 15px;
    font-size: 13px;
}
#uslugi-modal {
    padding: 10px 20px;
    width: 450px;
}
#uslugi-modal .ui-form-row {
    margin-bottom: 15px;
}
#uslugi-modal .ui-form-label {
    margin-bottom: 5px;
    font-weight: bold;
    font-size: 13px;
}
#uslugi-parts-select {
    width: 100%;
    height: 100px;
    padding: 5px;
    border: 1px solid #ccc;
    border-radius: 3px;
}
.uslugi-add-btn-wrap {
    margin-bottom: 15px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.main-grid-row.uslugi-clickable {
    cursor: pointer;
}
.uslugi-tabs {
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
}
.uslugi-tab {
    display: inline-block;
    padding: 10px 20px;
    cursor: pointer;
    background: #f5f5f5;
    border: 1px solid transparent;
    border-bottom: none;
    margin-bottom: -1px;
    border-radius: 4px 4px 0 0;
}
.uslugi-tab.active {
    background: #fff;
    border-color: #ddd;
    font-weight: bold;
    color: #000;
}
.uslugi-content {
    display: none;
    padding: 15px;
    border: 1px solid #ddd;
    border-top: none;
    background: #fff;
}
.uslugi-content.active {
    display: block;
}
.uslugi-content h4 {
    margin-top: 15px;
    margin-bottom: 10px;
}
.uslugi-content ul, .uslugi-content ol {
    padding-left: 20px;
    margin-bottom: 15px;
}
</style>

<div class="uslugi-servisa-wrap">
    <h2 class="uslugi-servisa-wrap__title">Услуги автосервиса</h2>

    <!-- Вкладки -->
    <div class="uslugi-tabs">
        <span class="uslugi-tab active" onclick="uslugiServisa.showTab('tab-list', this)">Список услуг</span>
        <span class="uslugi-tab" onclick="uslugiServisa.showTab('tab-user-instruction', this)">Описание для пользователя</span>
        <span class="uslugi-tab" onclick="uslugiServisa.showTab('tab-dev-instruction', this)">Описание по реализации</span>
    </div>

    <!-- Вкладка 1: Список услуг -->
    <div id="tab-list" class="uslugi-content active">
        <div class="uslugi-add-btn-wrap">
            <button class="ui-btn ui-btn-success" onclick="uslugiServisa.addRow()">Добавить услугу</button>
            <a href="/bitrix/admin/settings.php?mid=custom.autoorder&lang=ru" class="ui-btn ui-btn-primary" target="_blank" style="text-decoration: none;">Заказать запчасти</a>
            <a href="https://cb313664.tw1.ru/crm/deal/kanban/category/2/" class="ui-btn ui-btn-light-border" target="_blank" style="text-decoration: none;">Заявки на закупки</a>
        </div>

        <?php
        $APPLICATION->IncludeComponent("bitrix:main.ui.grid", "", [ 
            "GRID_ID" => $arResult["GRID_ID"], 
            "COLUMNS" => $arResult["COLUMNS"], 
            "ROWS" => $arResult["ROWS"], 
            "TOTAL_ROWS_COUNT" => count($arResult["ROWS"]), 
            "AJAX_MODE" => "Y", 
            "AJAX_ID" => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''), 
            "AJAX_OPTION_JUMP" => "N", 
            "AJAX_OPTION_HISTORY" => "N", 
            "SHOW_ROW_CHECKBOXES" => true, 
            "SHOW_CHECK_ALL_CHECKBOXES" => true, 
            "SHOW_ROW_ACTIONS_MENU" => true, 
            "SHOW_GRID_SETTINGS_MENU" => true, 
            "SHOW_NAVIGATION_PANEL" => false, 
            "SHOW_PAGINATION" => false, 
            "SHOW_PAGESIZE" => false, 
            "SHOW_SELECTED_COUNTER" => true, 
            "SHOW_TOTAL_COUNTER" => true, 
            "ALLOW_COLUMNS_SORT" => true, 
            "ALLOW_COLUMNS_RESIZE" => true, 
            "ALLOW_HORIZONTAL_SCROLL" => true, 
            "ALLOW_SORT" => true, 
            "SHOW_ACTION_PANEL" => false, 
        ]); 
        ?>
    </div>

    <!-- Вкладка 2: Инструкция для пользователя -->
    <div id="tab-user-instruction" class="uslugi-content">
        <h3>Инструкция по работе с разделом "Услуги автосервиса"</h3>
        <p>Этот раздел позволяет управлять списком услуг, редактировать цены и привязывать запчасти. Также отсюда можно перейти к созданию заявок на закупку.</p>
        <ul>
            <li>нажмите кнопку <b>«Добавить услугу»</b>,</li>
            <li>в открывшемся окне заполните: Название, Цену и Нормо-часы,</li>
            <li>выберите нужные запчасти из списка (удерживайте <i>Ctrl</i>, чтобы выбрать несколько). В скобках указана цена и текущий остаток запчасти,</li>
            <li>нажмите <b>«Сохранить»</b>. Услуга появится в таблице,</li>
        </ul>
        <ul>
            <li>наведите курсор на нужную строку в таблице и нажмите на иконку меню (три полоски слева от названия),</li>
            <li>выберите <b>«Редактировать»</b> (откроется то же окно с заполненными данными) или <b>«Удалить»</b>,</li>
        </ul>
        <ul>
            <li><b>заказать запчасти:</b> нажмите эту кнопку, чтобы перейти в настройки модуля «Автозаказ» и сформировать заявки на пополнение склада,</li>
            <li><b>заявки на закупки:</b> нажмите эту кнопку, чтобы открыть канбан-доску сделок в CRM, где хранятся все созданные заявки,</li>
            <li>если нажать на название запчасти в таблице, откроется её карточка в CRM.</li>
        </ul>
    </div>

    <!-- Вкладка 3: Описание реализации -->
    <div id="tab-dev-instruction" class="uslugi-content">
        <h3>Описание реализации компонента и модуля</h3>
        <ul>
            <li>переход к модулю сделан по кнопке "Заказать запчасти"</li>
            <li>компонент создан для централизации работы с услугами автосервиса в одном интерфейсе. Пользователю не нужно переключаться между административной панелью и публичной частью сайта для управления услугами. Все действия (добавление, редактирование, удаление, привязка запчастей) выполняются через AJAX прямо на странице, без перезагрузки. Это экономит время и снижает риск ошибок при вводе данных.</li>
            <li><b>class.php</b> — класс компонента <code>UslugiServisaComponent</code>. Реализует интерфейс <code>Controllerable</code> для обработки AJAX-запросов. Содержит методы получения услуг, запчастей и остатков, а также экшены для сохранения, удаления и получения данных формы.</li>
            <li><b>template.php</b> — шаблон компонента. Содержит HTML-верстку (таблица, модальное окно, вкладки), CSS-стили и JavaScript-логику для работы с AJAX.</li>
            <li>пользователь нажимает кнопку «Добавить услугу». Открывается модальное окно с пустыми полями.</li>
            <li>после заполнения полей и нажатия «Сохранить» JavaScript собирает данные и отправляет AJAX-запрос через <code>BX.ajax.runComponentAction</code> с указанием <code>signedParameters</code>.</li>
            <li>на сервере срабатывает метод <code>saveRowAction</code>. Он проверяет наличие ID: если ID = 0, создается новый элемент через <code>CIBlockElement::Add</code>. Если ID > 0, элемент обновляется через <code>CIBlockElement::Update</code>.</li>
            <li>после успешного сохранения таблица перезагружается через <code>reloadTable</code>, и пользователь видит обновленные данные.</li>
            <li>пользователь выбирает «Редактировать» в меню строки. Отправляется AJAX-запрос к методу <code>getFormDataAction</code> с ID услуги.</li>
            <li>сервер возвращает данные услуги (название, цену, нормо-часы, привязанные запчасти).</li>
            <li>JavaScript заполняет этими данными модальное окно и открывает его. При сохранении используется тот же <code>saveRowAction</code>.</li>
            <li>пользователь выбирает «Удалить». Отправляется AJAX-запрос к методу <code>deleteRowAction</code> с ID услуги.</li>
            <li>сервер удаляет элемент через <code>CIBlockElement::Delete</code>.</li>
            <li>таблица перезагружается без перезагрузки страницы.</li>
            <li>услуги хранятся в инфоблоке с ID <b>25</b>, запчасти — в инфоблоке <b>14</b>. ID инфоблоков передаются жестко в JavaScript и в <code>class.php</code> (в методах <code>getAllParts</code> и <code>getPartsNames</code>).</li>
            <li>для получения цен и остатков запчастей используется модуль <code>catalog</code>. Сначала проверяется наличие торговых предложений через <code>CCatalogSKU::getOffersList</code>. Если они есть, берется цена и остаток из первого предложения через <code>CPrice::GetList</code> и <code>CCatalogProduct::GetList</code>. Если предложений нет — данные берутся из самого товара.</li>
            <li>ссылки на карточки запчастей в CRM формируются динамически по шаблону <code>/crm/catalog/{ID_ИНФОБЛОКА}/product/{ID_ТОВАРА}/</code> и подставляются в таблицу.</li>
            <li>в классе реализован интерфейс <code>Controllerable</code> и метод <code>configureActions</code>, который возвращает пустые <code>prefilters</code> для экшенов <code>saveRow</code>, <code>deleteRow</code> и <code>getFormData</code>. Это отключает стандартную проверку CSRF-токена, которая мешала при AJAX-вызовах.</li>
        </ul>
    </div>
</div>

<!-- Модальное окно (скрытое) -->
<div id="uslugi-modal" style="display:none;"> 
    <div class="ui-form-row">
        <div class="ui-form-label">Название услуги</div>
        <input type="text" id="uslugi-name" class="ui-ctl-element" style="width:100%;" />
    </div>
    <div class="ui-form-row">
        <div class="ui-form-label">Цена услуги, руб.</div>
        <input type="number" id="uslugi-price" class="ui-ctl-element" style="width:100%;" />
    </div>
    <div class="ui-form-row">
        <div class="ui-form-label">Нормо-часы</div>
        <input type="text" id="uslugi-hours" class="ui-ctl-element" style="width:100%;" />
    </div>
    <div class="ui-form-row">
        <div class="ui-form-label">Запчасти (Ctrl)</div>
        <select id="uslugi-parts-select" multiple>
            <?php foreach ($arResult['ALL_PARTS'] as $part): ?>
                <option value="<?= $part['ID'] ?>">
                    <?= htmlspecialchars($part['NAME']) ?> 
                    (<?= $part['PRICE'] ?> руб., Остаток: <?= $part['STOCK'] ?> шт.)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <input type="hidden" id="uslugi-id" value="0" />
</div>

<script>
window.uslugiServisa = { 
    gridId: '<?= CUtil::JSEscape($arResult["GRID_ID"]) ?>', 
    componentName: '<?= CUtil::JSEscape($componentId) ?>', 
    signedParameters: '<?= CUtil::JSEscape($signedParameters) ?>', 
    iblockId: 25, 
    partsIblockId: 14, 

    showTab: function(tabId, element) {
        document.querySelectorAll('.uslugi-content').forEach(function(el) { el.classList.remove('active'); });
        document.querySelectorAll('.uslugi-tab').forEach(function(el) { el.classList.remove('active'); });
        document.getElementById(tabId).classList.add('active');
        element.classList.add('active');
    },

    addRow: function() { 
        document.getElementById('uslugi-id').value = 0; 
        document.getElementById('uslugi-name').value = ''; 
        document.getElementById('uslugi-price').value = ''; 
        document.getElementById('uslugi-hours').value = ''; 
        var select = document.getElementById('uslugi-parts-select'); 
        for (var i = 0; i < select.options.length; i++) select.options[i].selected = false; 

        BX.PopupWindowManager.create("uslugi-popup-add", null, { 
            content: BX('uslugi-modal'), 
            width: 500, 
            autoHide: true, 
            titleBar: 'Добавить услугу', 
            buttons: [ 
                new BX.PopupWindowButton({ 
                    text: 'Сохранить', 
                    events: { click: function() { uslugiServisa.saveRow(); this.popupWindow.close(); } } 
                })
            ]
        }).show(); 
    },

    editRow: function(id) { 
        BX.ajax.runComponentAction(this.componentName, 'getFormData', { 
            mode: 'class', 
            signedParameters: this.signedParameters, 
            data: { id: id } 
        }).then(function(response) { 
            var data = response.data; 
            document.getElementById('uslugi-id').value = data.ID; 
            document.getElementById('uslugi-name').value = data.NAME; 
            document.getElementById('uslugi-price').value = data.PRICE; 
            document.getElementById('uslugi-hours').value = data.NORMO_HOURS; 
            var select = document.getElementById('uslugi-parts-select'); 
            for (var i = 0; i < select.options.length; i++) { 
                select.options[i].selected = data.PARTS.includes(String(select.options[i].value)); 
            }
            BX.PopupWindowManager.create("uslugi-popup-edit", null, { 
                content: BX('uslugi-modal'), 
                width: 500, 
                autoHide: true, 
                titleBar: 'Редактировать услугу', 
                buttons: [ 
                    new BX.PopupWindowButton({ 
                        text: 'Сохранить', 
                        events: { click: function() { uslugiServisa.saveRow(); this.popupWindow.close(); } } 
                    })
                ]
            }).show(); 
        });
    },

    saveRow: function() { 
        var select = document.getElementById('uslugi-parts-select'); 
        var selectedParts = []; 
        for (var i = 0; i < select.options.length; i++) { 
            if (select.options[i].selected) selectedParts.push(select.options[i].value); 
        }
        var fields = { 
            IBLOCK_ID: uslugiServisa.iblockId, 
            ID: document.getElementById('uslugi-id').value, 
            NAME: document.getElementById('uslugi-name').value, 
            PRICE: document.getElementById('uslugi-price').value, 
            NORMO_HOURS: document.getElementById('uslugi-hours').value, 
            PARTS: selectedParts 
        };
        
        BX.ajax.runComponentAction(this.componentName, 'saveRow', { 
            mode: 'class', 
            signedParameters: this.signedParameters, 
            data: { fields: fields } 
        }).then(function(response) { 
            if (response.data && response.data.status === 'success') { 
                BX.UI.Notification.Center.notify({ content: 'Сохранено' }); 
                var gridObject = BX.Main.gridManager.getById(window.uslugiServisa.gridId); 
                if (gridObject) { 
                    gridObject.instance.reloadTable('POST', { apply_filter: 'Y', clear_nav: 'Y' }); 
                } else {
                    location.reload(); 
                }
            } else { 
                var errMsg = 'Ошибка!'; 
                if (response.data && response.data.message) { 
                    errMsg = response.data.message; 
                } else if (response.errors) { 
                    errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); 
                }
                alert(errMsg); 
            }
        }).catch(function(response) { 
            var errMsg = 'Системная ошибка запроса.'; 
            if (response.errors) { 
                errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); 
            }
            alert(errMsg); 
        });
    },

    deleteRow: function(id, gridId) { 
        if (!confirm('Удалить эту услугу?')) return; 

        BX.ajax.runComponentAction(this.componentName, 'deleteRow', { 
            mode: 'class', 
            signedParameters: this.signedParameters, 
            data: { ids: [id] } 
        }).then(function(response) { 
            if (response.data && response.data.status === 'success') { 
                BX.UI.Notification.Center.notify({ content: 'Удалено' }); 
                var grid = BX.Main.gridManager.getById(gridId); 
                if (grid && grid.instance) { 
                    grid.instance.reloadTable('POST', { clear_nav: 'Y' }); 
                }
            } else { 
                var errMsg = 'Ошибка!'; 
                if (response.data && response.data.message) { 
                    errMsg = response.data.message; 
                } else if (response.errors) { 
                    errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); 
                }
                alert(errMsg); 
            }
        }).catch(function(response) { 
            var errMsg = 'Системная ошибка запроса.'; 
            if (response.errors) { 
                errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); 
            }
            alert(errMsg); 
        });
    },

    bindRowClicks: function() { 
        var grid = BX.Main.gridManager.getById(this.gridId); 
        if (!grid || !grid.instance) return; 
        var container = grid.instance.getContainer(); 
        if (!container) return; 
        var rows = container.querySelectorAll('.main-grid-row.main-grid-row-body'); 
        rows.forEach(function(row) { 
            if (row.dataset.uslugiBound === '1') return; 
            row.dataset.uslugiBound = '1'; 
            var url = row.getAttribute('data-detail-url') || row.getAttribute('data-url') || ''; 
            if (!url) return; 
            row.classList.add('uslugi-clickable'); 
            row.addEventListener('click', function(e) { 
                if (e.target.closest('input, .main-grid-cell-checkbox, .main-grid-cell-actions, a')) return; 
                window.location.href = url; 
            });
        });
    }
};

BX.ready(function() { 
    if (window.uslugiServisa && typeof uslugiServisa.bindRowClicks === 'function') { 
        uslugiServisa.bindRowClicks(); 
        BX.addCustomEvent('Grid::updated', function() { 
            uslugiServisa.bindRowClicks(); 
        });
    }
});
</script>