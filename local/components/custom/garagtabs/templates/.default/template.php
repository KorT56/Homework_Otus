<?php
// защита от прямого вызова
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// ссылки

// получаем название элемента без ссылки
function getIblockElementName($id) {
    if (empty($id)) return '';
    $res = CIBlockElement::GetByID($id);
    if ($arRes = $res->GetNext()) return $arRes['NAME'];
    return $id;
}

// получаем ссылку на элемент гаража
function getGarageElementLink($id) {
    if (empty($id)) return '';
    $name = getIblockElementName($id);
    $url = "/services/lists/18/element/0/" . $id . "/?list_section_id=";
    return '<a href="' . $url . '" target="_blank" style="color: #007bff; text-decoration: underline;">' . $name . '</a>';
}

// получаем ссылки на сделки (выводим все, если их несколько)
function getCrmDealLinks($dealIds) {
    if (empty($dealIds)) return '';
    $links = [];
    foreach ($dealIds as $dealId) {
        $dealId = (int)$dealId;
        if ($dealId > 0) {
            $title = \CCrmOwnerType::GetCaption(\CCrmOwnerType::Deal, $dealId);
            $url = "/crm/deal/details/" . $dealId . "/";
            $links[] = '<a href="' . $url . '" target="_blank" style="color: #007bff; text-decoration: underline;">' . $title . '</a>';
        }
    }
    return implode('<br>', $links); 
}

if (!empty($arResult['ERROR'])): ?>
    <div style="color: red; padding: 10px; background: #ffebee;"><?= $arResult['ERROR'] ?></div>
    <?php return; ?>
<?php endif; ?>

<!-- Стили для наших табов (вкладок) -->
<style>
.garage-tabs {
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
}
.garage-tab {
    display: inline-block;
    padding: 10px 20px;
    cursor: pointer;
    background: #f5f5f5;
    border: 1px solid transparent;
    border-bottom: none;
    margin-bottom: -1px;
    border-radius: 4px 4px 0 0;
    font-size: 14px;
}
.garage-tab.active {
    background: #fff;
    border-color: #ddd;
    font-weight: bold;
    color: #000;
}
.garage-content {
    display: none;
    padding: 15px;
    border: 1px solid #ddd;
    border-top: none;
    background: #fff;
}
.garage-content.active {
    display: block;
}
</style>

<!-- Разметка табов -->
<div class="garage-tabs">
    <span class="garage-tab active" onclick="garageTabs.showTab('garage-tab-list', this)">Список автомобилей</span>
    <span class="garage-tab" onclick="garageTabs.showTab('garage-tab-user', this)">Описание для пользователя</span>
    <span class="garage-tab" onclick="garageTabs.showTab('garage-tab-dev', this)">Описание по реализации</span>
</div>

<!-- Содержимое вкладки 1: Таблица -->
<div id="garage-tab-list" class="garage-content active">
    
    <!-- блок отладки -->
    <div style="background: #ffe; padding: 10px; margin-bottom: 10px; border: 1px solid #cc0; font-size: 12px;">
        <strong>ID контакта:</strong> <?= $arResult['CONTACT_ID'] ?? '0' ?> | 
        <strong>Найдено машин:</strong> <?= count($arResult['CARS']) ?> |
        <strong>Машина в сделке:</strong> <?= implode(', ', $arResult['DEAL_CAR_IDS'] ?? []) ?>
    </div>

    <!-- Таблица -->
    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse; text-align: left; margin-top: 15px;">
        <thead>
            <tr style="background: #eef2f5;">
                <th style="padding: 8px;">Марка</th>
                <th style="padding: 8px;">Модель</th>
                <th style="padding: 8px;">Номер</th>
                <th style="padding: 8px;">Пробег, км</th>
                <th style="padding: 8px;">Год</th>
                <th style="padding: 8px;">Цвет</th>
                <th style="padding: 8px;">Привязка к сделкам</th>
                <th style="padding: 8px;">Привязка к гаражу</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($arResult['CARS'])): ?>
                <tr><td colspan="8" style="padding: 8px; text-align: center; color: #888;">Нет данных по автомобилям</td></tr>
            <?php else: ?>
                <?php foreach ($arResult['CARS'] as $car): $props = $car['PROPS']; ?>
                    <tr>
                        <!-- марка, модель, номер, пробег, год, цвет (без ссылок) -->
                        <td style="padding: 8px;"><?= getIblockElementName($props['MARKA_AVTOMOBILYA_MODEL']['VALUE']) ?></td>
                        <td style="padding: 8px;"><?= getIblockElementName($props['MODEL_AVTOMABILYA']['VALUE']) ?></td>
                        <td style="padding: 8px;"><?= htmlspecialchars($props['NOMER_AVTOMOBILYA']['VALUE'] ?? '') ?></td>
                        <td style="padding: 8px;"><?= htmlspecialchars($props['PROBEG_AVTOMOBILYA']['VALUE'] ?? '') ?></td>
                        <td style="padding: 8px;"><?= getIblockElementName($props['GOD_AVTOMOBILYA']['VALUE']) ?></td>
                        <td style="padding: 8px;"><?= getIblockElementName($props['TSVET_AVTOMOBILYA']['VALUE']) ?></td>
                        
                        <!-- выводим все сделки по машине (может быть несколько) -->
                        <td style="padding: 8px;"><?= getCrmDealLinks($car['DEALS']) ?></td>
                        
                        <!-- выводим ссылку на гараж -->
                        <td style="padding: 8px;"><?= getGarageElementLink($props['NOVOE_POLE']['VALUE']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- кнопка перехода на страницу -->
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
        <a href="https://cb313664.tw1.ru/local/homeworks/homework_4/index_6.php?clear_cache=Y" 
           target="_blank"
           style="display: inline-block; padding: 12px 20px; background: #2fc7f7; color: #ffffff; text-decoration: none; font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; text-transform: uppercase; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
            Перейти к сделкам всех клиентов 
        </a>
    </div>
</div>

<!-- Описание для пользователя -->
<div id="garage-tab-user" class="garage-content">
    <h3>Описание для пользователя</h3>
    <ul style="font-size: 14px; line-height: 1.6;">
        <li>в карточке сделки появилась новая вкладка «Гараж», где собрана вся информация об автомобилях клиента;</li>
        <li>данные на вкладке показываются только по тому контакту, который выбран в текущей сделке;</li>
        <li>таблица выводит марку, модель, номер, пробег, год и цвет каждого автомобиля;</li>
        <li>в колонке «Привязка к сделкам» отображаются все сделки, в которых участвовал этот автомобиль (если сделок несколько, они выводятся списком);</li>
        <li>в колонке «Привязка к гаражу» есть активная ссылка на карточку элемента в списке гаража;</li>
        <li>внизу таблицы есть кнопка для быстрого перехода на страницу автозаказа, где можно добавить товары.</li>
    </ul>
</div>

<!-- Описание по реализации -->
<div id="garage-tab-dev" class="garage-content">
    <h3>Описание по реализации</h3>
    <ul style="font-size: 14px; line-height: 1.6;">
        <li><b>local/modules/custom.garage/include.php</b> — в этом файле мы подписываемся на событие onEntityDetailsTabsInitialized и добавляем новую вкладку «Гараж» в карточку сделки;</li>
        <li><b>local/components/custom/garagtabs/class.php</b> — здесь находится логика компонента: он получает ID контакта из сделки и собирает список всех привязанных к нему машин из инфоблока 20;</li>
        <li>в этом же файле (class.php) происходит поиск всех сделок, в которых участвовала каждая конкретная машина (проверка по полю «Марка и Модель автомобиля»);</li>
        <li><b>local/components/custom/garagtabs/templates/.default/template.php</b> — этот файл отвечает за визуальный вывод, он превращает сырые ID из базы данных в читаемые названия и рисует HTML-таблицу;</li>
        <li>в шаблоне также формируются ссылки на карточки сделок в CRM и на элементы гаража в публичной части;</li>
        <li>в самом низу шаблона добавлена кнопка «Перейти к подробной информации», которая ведет на страницу просмотра сделок для всех клиентов.</li>
    </ul>
</div>

<!-- Скрипт для переключения табов -->
<script>
window.garageTabs = {
    showTab: function(tabId, element) {
        // скрываем все вкладки
        document.querySelectorAll('.garage-content').forEach(function(el) { 
            el.classList.remove('active'); 
        });
        document.querySelectorAll('.garage-tab').forEach(function(el) { 
            el.classList.remove('active'); 
        });
        
        // показываем нужную
        document.getElementById(tabId).classList.add('active');
        element.classList.add('active');
    }
};
</script>