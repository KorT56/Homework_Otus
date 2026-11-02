<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); // защита от прямого вызова

$componentId = $this->getComponent()->getName(); // имя компонента для AJAX
$signedParameters = $this->getComponent()->getSignedParameters(); // подписанные параметры для AJAX

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/css/main/grid/webform-button.css'); // стили кнопок грида
?>
<style>
.uslugi-servisa-wrap { margin: 20px 0; } /* отступы блока */
.uslugi-servisa-wrap__title { font-size: 24px; margin-bottom: 15px; } /* заголовок */
.uslugi-servisa-wrap .uslugi-price { color: #d0021b; font-weight: bold; } /* цена красным */
.uslugi-servisa-wrap .uslugi-parts { margin: 0; padding-left: 15px; font-size: 13px; } /* список запчастей */
#uslugi-modal { padding: 10px 20px; width: 450px; } /* окно формы */
#uslugi-modal .ui-form-row { margin-bottom: 15px; } /* отступы строк формы */
#uslugi-modal .ui-form-label { margin-bottom: 5px; font-weight: bold; font-size: 13px; } /* подписи полей */
#uslugi-parts-select { width: 100%; height: 100px; padding: 5px; border: 1px solid #ccc; border-radius: 3px; } /* селект запчастей */
.uslugi-add-btn-wrap { margin-bottom: 15px; } /* отступ кнопки */
.main-grid-row.uslugi-clickable { cursor: pointer; } /* курсор на кликабельных строках */
</style>

<div class="uslugi-servisa-wrap">
    <h2 class="uslugi-servisa-wrap__title">Услуги автосервиса</h2> <!-- заголовок -->

    <div class="uslugi-add-btn-wrap">
        <button class="ui-btn ui-btn-success" onclick="uslugiServisa.addRow()">Добавить услугу</button> <!-- кнопка добавления -->
    </div>

    <?php
    $APPLICATION->IncludeComponent("bitrix:main.ui.grid", "", [ // рендерим грид (фильтр убран)
        "GRID_ID" => $arResult["GRID_ID"], // ID грида
        "COLUMNS" => $arResult["COLUMNS"], // колонки
        "ROWS" => $arResult["ROWS"], // строки
        "TOTAL_ROWS_COUNT" => count($arResult["ROWS"]), // всего строк
        "AJAX_MODE" => "Y", // включаем AJAX
        "AJAX_ID" => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''), // ID AJAX
        "AJAX_OPTION_JUMP" => "N", // без прыжка
        "AJAX_OPTION_HISTORY" => "N", // без истории
        "SHOW_ROW_CHECKBOXES" => true, // чекбоксы строк
        "SHOW_CHECK_ALL_CHECKBOXES" => true, // чекбокс "выбрать все"
        "SHOW_ROW_ACTIONS_MENU" => true, // меню действий строки
        "SHOW_GRID_SETTINGS_MENU" => true, // меню настроек грида
        "SHOW_NAVIGATION_PANEL" => false, // без панели навигации
        "SHOW_PAGINATION" => false, // без пагинации
        "SHOW_PAGESIZE" => false, // без выбора размера страницы
        "SHOW_SELECTED_COUNTER" => true, // счётчик выбранных
        "SHOW_TOTAL_COUNTER" => true, // общий счётчик
        "ALLOW_COLUMNS_SORT" => true, // сортировка колонок
        "ALLOW_COLUMNS_RESIZE" => true, // изменение ширины колонок
        "ALLOW_HORIZONTAL_SCROLL" => true, // горизонтальный скролл
        "ALLOW_SORT" => true, // сортировка
        "SHOW_ACTION_PANEL" => false, // без панели групповых действий
    ]);
    ?>
</div>

<div id="uslugi-modal" style="display:none;"> <!-- скрытая форма -->
    <div class="ui-form-row">
        <div class="ui-form-label">Название услуги</div> <!-- подпись -->
        <input type="text" id="uslugi-name" class="ui-ctl-element" style="width:100%;" /> <!-- поле названия -->
    </div>
    <div class="ui-form-row">
        <div class="ui-form-label">Цена услуги, руб.</div> <!-- подпись -->
        <input type="number" id="uslugi-price" class="ui-ctl-element" style="width:100%;" /> <!-- поле цены -->
    </div>
    <div class="ui-form-row">
        <div class="ui-form-label">Нормо-часы</div> <!-- подпись -->
        <input type="text" id="uslugi-hours" class="ui-ctl-element" style="width:100%;" /> <!-- поле нормо-часов -->
    </div>
    <div class="ui-form-row">
        <div class="ui-form-label">Запчасти (Ctrl)</div> <!-- подпись -->
        <select id="uslugi-parts-select" multiple> <!-- мультиселект запчастей -->
            <?php foreach ($arResult['ALL_PARTS'] as $part): ?> <!-- перебираем запчасти -->
                <option value="<?= $part['ID'] ?>">
                    <?= htmlspecialchars($part['NAME']) ?> 
                    (<?= $part['PRICE'] ?> руб., Остаток: <?= $part['STOCK'] ?> шт.)
                </option> <!-- опция с ценой и остатком -->
            <?php endforeach; ?>
        </select>
    </div>
    <input type="hidden" id="uslugi-id" value="0" /> <!-- скрытое поле ID -->
</div>

<script>
window.uslugiServisa = { // объект с методами
    gridId: '<?= CUtil::JSEscape($arResult["GRID_ID"]) ?>', // ID грида
    componentName: '<?= CUtil::JSEscape($componentId) ?>', // имя компонента
    signedParameters: '<?= CUtil::JSEscape($signedParameters) ?>', // подписанные параметры
    iblockId: 25, // <--- ЖЕСТКО ЗАДАЛИ ID ИНФОБЛОКА УСЛУГ (25)
    partsIblockId: 14, // <--- ЖЕСТКО ЗАДАЛИ ID ИНФОБЛОКА ЗАПЧАСТЕЙ (14)

    addRow: function() { // открыть форму добавления
        document.getElementById('uslugi-id').value = 0; // сбрасываем ID
        document.getElementById('uslugi-name').value = ''; // чистим название
        document.getElementById('uslugi-price').value = ''; // чистим цену
        document.getElementById('uslugi-hours').value = ''; // чистим нормо-часы
        var select = document.getElementById('uslugi-parts-select'); // получаем селект
        for (var i = 0; i < select.options.length; i++) select.options[i].selected = false; // снимаем выделение

        BX.PopupWindowManager.create("uslugi-popup-add", null, { // создаём попап
            content: BX('uslugi-modal'), // содержимое — форма
            width: 500, // ширина
            autoHide: true, // закрывать по клику вне
            titleBar: 'Добавить услугу', // заголовок
            buttons: [ // кнопки
                new BX.PopupWindowButton({ // кнопка "Сохранить"
                    text: 'Сохранить', // текст
                    events: { click: function() { uslugiServisa.saveRow(); this.popupWindow.close(); } } // сохранение и закрытие
                })
            ]
        }).show(); // показываем
    },

    editRow: function(id) { // открыть форму редактирования
        BX.ajax.runComponentAction(this.componentName, 'getFormData', { // AJAX-запрос данных
            mode: 'class', // режим class
            signedParameters: this.signedParameters, // подпись
            data: { id: id } // передаём ID
        }).then(function(response) { // после ответа
            var data = response.data; // данные
            document.getElementById('uslugi-id').value = data.ID; // ID
            document.getElementById('uslugi-name').value = data.NAME; // название
            document.getElementById('uslugi-price').value = data.PRICE; // цена
            document.getElementById('uslugi-hours').value = data.NORMO_HOURS; // нормо-часы
            var select = document.getElementById('uslugi-parts-select'); // селект
            for (var i = 0; i < select.options.length; i++) { // перебираем опции
                select.options[i].selected = data.PARTS.includes(String(select.options[i].value)); // выделяем нужные
            }
            BX.PopupWindowManager.create("uslugi-popup-edit", null, { // создаём попап
                content: BX('uslugi-modal'), // содержимое
                width: 500, // ширина
                autoHide: true, // закрывать по клику вне
                titleBar: 'Редактировать услугу', // заголовок
                buttons: [ // кнопки
                    new BX.PopupWindowButton({ // "Сохранить"
                        text: 'Сохранить', // текст
                        events: { click: function() { uslugiServisa.saveRow(); this.popupWindow.close(); } } // сохранение
                    })
                ]
            }).show(); // показываем
        });
    },

    saveRow: function() { // сохранить
        var select = document.getElementById('uslugi-parts-select'); // селект
        var selectedParts = []; // выбранные запчасти
        for (var i = 0; i < select.options.length; i++) { // перебираем опции
            if (select.options[i].selected) selectedParts.push(select.options[i].value); // если выбрана — добавляем
        }
        var fields = { // поля формы
            IBLOCK_ID: uslugiServisa.iblockId, // <--- ПЕРЕДАЕМ ID ИНФОБЛОКА
            ID: document.getElementById('uslugi-id').value, // ID
            NAME: document.getElementById('uslugi-name').value, // название
            PRICE: document.getElementById('uslugi-price').value, // цена
            NORMO_HOURS: document.getElementById('uslugi-hours').value, // нормо-часы
            PARTS: selectedParts // запчасти
        };
        
        BX.ajax.runComponentAction(this.componentName, 'saveRow', { // AJAX-сохранение
            mode: 'class', // режим class
            signedParameters: this.signedParameters, // подпись
            data: { fields: fields } // данные
        }).then(function(response) { // после ответа
            if (response.data && response.data.status === 'success') { // если успех
                BX.UI.Notification.Center.notify({ content: 'Сохранено' }); // уведомление
                var gridObject = BX.Main.gridManager.getById(window.uslugiServisa.gridId); // получаем грид
                if (gridObject) { // если найден
                    gridObject.instance.reloadTable('POST', { apply_filter: 'Y', clear_nav: 'Y' }); // перезагружаем
                } else {
                    location.reload(); // иначе полная перезагрузка
                }
            } else { // если ошибка
                var errMsg = 'Ошибка!'; // текст по умолчанию
                if (response.data && response.data.message) { // если сервер прислал текст
                    errMsg = response.data.message; // берём его
                } else if (response.errors) { // если это системная ошибка Битрикса
                    errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); // собираем все тексты
                }
                alert(errMsg); // ПРИНУДИТЕЛЬНО ПОКАЗЫВАЕМ ОШИБКУ
            }
        }).catch(function(response) { // при ошибке запроса
            var errMsg = 'Системная ошибка запроса.'; // текст по умолчанию
            if (response.errors) { // если Битрикс вернул массив ошибок
                errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); // склеиваем их
            }
            alert(errMsg); // показываем
        });
    },

    deleteRow: function(id, gridId) { // удалить строку
        if (!confirm('Удалить эту услугу?')) return; // подтверждение

        BX.ajax.runComponentAction(this.componentName, 'deleteRow', { // AJAX-удаление
            mode: 'class', // режим class
            signedParameters: this.signedParameters, // подпись
            data: { ids: [id] } // ID для удаления
        }).then(function(response) { // после ответа
            if (response.data && response.data.status === 'success') { // если успех
                BX.UI.Notification.Center.notify({ content: 'Удалено' }); // уведомление
                var grid = BX.Main.gridManager.getById(gridId); // получаем грид
                if (grid && grid.instance) { // если найден
                    grid.instance.reloadTable('POST', { clear_nav: 'Y' }); // перезагружаем
                }
            } else { // если ошибка
                var errMsg = 'Ошибка!'; // текст по умолчанию
                if (response.data && response.data.message) { // если сервер прислал текст
                    errMsg = response.data.message; // берём его
                } else if (response.errors) { // если это системная ошибка Битрикса
                    errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); // собираем все тексты
                }
                alert(errMsg); // ПРИНУДИТЕЛЬНО ПОКАЗЫВАЕМ ОШИБКУ
            }
        }).catch(function(response) { // при ошибке запроса
            var errMsg = 'Системная ошибка запроса.'; // текст по умолчанию
            if (response.errors) { // если Битрикс вернул массив ошибок
                errMsg = response.errors.map(function(e) { return e.message; }).join('\n'); // склеиваем их
            }
            alert(errMsg); // показываем
        });
    },

    bindRowClicks: function() { // привязка кликов по строкам
        var grid = BX.Main.gridManager.getById(this.gridId); // получаем грид
        if (!grid || !grid.instance) return; // если нет — выходим
        var container = grid.instance.getContainer(); // контейнер грида
        if (!container) return; // если нет — выходим
        var rows = container.querySelectorAll('.main-grid-row.main-grid-row-body'); // все строки
        rows.forEach(function(row) { // перебираем
            if (row.dataset.uslugiBound === '1') return; // уже привязано — пропускаем
            row.dataset.uslugiBound = '1'; // помечаем
            var url = row.getAttribute('data-detail-url') || row.getAttribute('data-url') || ''; // берём URL
            if (!url) return; // нет URL — не кликабельна
            row.classList.add('uslugi-clickable'); // добавляем класс
            row.addEventListener('click', function(e) { // вешаем клик
                if (e.target.closest('input, .main-grid-cell-checkbox, .main-grid-cell-actions, a')) return; // игнорируем чекбоксы/меню/ссылки
                window.location.href = url; // переходим
            });
        });
    }
};

BX.ready(function() { // после инициализации Bitrix
    if (window.uslugiServisa && typeof uslugiServisa.bindRowClicks === 'function') { // если объект есть
        uslugiServisa.bindRowClicks(); // привязываем клики
        BX.addCustomEvent('Grid::updated', function() { // при обновлении грида
            uslugiServisa.bindRowClicks(); // привязываем снова
        });
    }
});
</script>