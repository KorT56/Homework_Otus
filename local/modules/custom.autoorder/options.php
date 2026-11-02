<?php
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\ProductRowTable;

if (!$USER->IsAdmin()) return;

Loader::includeModule('iblock');
Loader::includeModule('catalog');
Loader::includeModule('crm');

$module_id = 'custom.autoorder';
$partsIblockId = 14; 

// сохранение настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['save_settings'])) {
    Option::set($module_id, 'DEAL_CATEGORY_ID', (int)$_POST['DEAL_CATEGORY_ID']);
    Option::set($module_id, 'ORDER_MODE', $_POST['ORDER_MODE']);
    Option::set($module_id, 'DEFAULT_QTY', (int)$_POST['DEFAULT_QTY']);
    Option::set($module_id, 'ORDER_QTYS', serialize($_POST['ORDER_QTY'] ?? []));
    echo CAdminMessage::ShowNote('Настройки успешно сохранены!');
}

// создание сделок
function createAutoorderDeal($prodId, $productName, $qty, $categoryId) {
    $deal = new \CCrmDeal(false);
    $fields = [
        'TITLE' => 'Автозаказ: ' . $productName,
        'CATEGORY_ID' => $categoryId,
        'STAGE_ID' => 'NEW',
        'COMMENTS' => 'Заявка создана модулем автозаказа.',
    ];
    $dealId = $deal->Add($fields);
    if ($dealId) {
        $productRow = [
            'OWNER_ID' => $dealId,
            'OWNER_TYPE' => 'D',
            'PRODUCT_ID' => $prodId,
            'PRODUCT_NAME' => $productName,
            'PRICE' => 0,
            'QUANTITY' => $qty,
        ];
        ProductRowTable::add($productRow);
        return $dealId;
    }
    return false;
}

// кнопка создать заявки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['create_orders'])) {
    $categoryId = (int)Option::get($module_id, 'DEAL_CATEGORY_ID', 0);
    $mode = Option::get($module_id, 'ORDER_MODE', 'zero');
    $defaultQty = (int)Option::get($module_id, 'DEFAULT_QTY', 10);
    $orderQtys = unserialize(Option::get($module_id, 'ORDER_QTYS', serialize([])));
    $createdDeals = [];
    
    if ($mode === 'zero') {
        $rsProducts = \CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => $partsIblockId, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME']);
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
            
            if ($currentStock <= 0) {
                $dealId = createAutoorderDeal($arProduct['ID'], $arProduct['NAME'], $defaultQty, $categoryId);
                if ($dealId) $createdDeals[] = ['ID' => $dealId, 'NAME' => $arProduct['NAME'], 'QTY' => $defaultQty];
            }
        }
    } elseif ($mode === 'selected') {
        foreach ($orderQtys as $prodId => $qty) {
            if ((int)$qty > 0) {
                $res = \CIBlockElement::GetByID($prodId);
                if ($arRes = $res->GetNext()) {
                    $dealId = createAutoorderDeal($prodId, $arRes['NAME'], (int)$qty, $categoryId);
                    if ($dealId) $createdDeals[] = ['ID' => $dealId, 'NAME' => $arRes['NAME'], 'QTY' => $qty];
                }
            }
        }
    }
    
    if (!empty($createdDeals)) {
        echo '<div class="adm-info-message adm-info-message-green">';
        echo '<b>Успешно созданы следующие заявки:</b><br>';
        foreach ($createdDeals as $deal) {
            echo '- <a href="/crm/deal/details/' . $deal['ID'] . '/" target="_blank">Сделка №' . $deal['ID'] . '</a> (товар: ' . $deal['NAME'] . ', кол-во: ' . $deal['QTY'] . ')<br>';
        }
        echo '</div>';
    } else {
        echo CAdminMessage::ShowNote('Заявки не созданы (нет подходящих товаров).');
    }
}

// созраненные настройки должны остаться 
$orderQtys = unserialize(Option::get($module_id, 'ORDER_QTYS', serialize([])));
$currentCategory = (int)Option::get($module_id, 'DEAL_CATEGORY_ID', 0);
$currentMode = Option::get($module_id, 'ORDER_MODE', 'zero');
$defaultQty = (int)Option::get($module_id, 'DEFAULT_QTY', 10);
$categories = DealCategory::getAll(true); 

// товары и остатки
$products = [];
$rsProducts = \CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => $partsIblockId, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME']);
while ($arProduct = $rsProducts->GetNext()) {
    $currentStock = 0;
    if (Loader::includeModule('catalog')) {
        $offers = \CCatalogSKU::getOffersList($arProduct['ID'], $partsIblockId);
        if (!empty($offers[$arProduct['ID']])) {
            $offer = reset($offers[$arProduct['ID']]);
            $resStock = \CCatalogProduct::GetList([], ['ID' => $offer['ID']], false, false, ['QUANTITY']);
            if ($arStock = $resStock->Fetch()) $currentStock = (float)$arStock['QUANTITY'];
        } else {
            $resStock = \CCatalogProduct::GetList([], ['ID' => $arProduct['ID']], false, false, ['QUANTITY']);
            if ($arStock = $resStock->Fetch()) $currentStock = (float)$arStock['QUANTITY'];
        }
    }
    $arProduct['STOCK'] = $currentStock;
    $products[] = $arProduct;
}
?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= $module_id ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>
    
    <h2>Настройки модуля "Автозаказ"</h2>

    <!-- Вкладки -->
    <div class="adm-detail-tabs-block">
        <span class="adm-detail-tab adm-detail-tab-active" onclick="showTab('settings', this)">Настройки</span>
        <span class="adm-detail-tab" onclick="showTab('instruction', this)">Инструкция</span>
    </div>

    <!-- вкладка 1: настройки -->
    <div id="tab-settings" class="adm-detail-content">
        <table class="adm-detail-content-table edit-table" style="margin-bottom: 20px;">
            <tr>
                <td width="40%">Воронка для создания сделок:</td>
                <td>
                    <select name="DEAL_CATEGORY_ID">
                        <option value="0" <?= $currentCategory == 0 ? 'selected' : '' ?>>Общая воронка</option>
                        <?php foreach ($categories as $cat): ?>
                            <?php if($cat['ID'] == 0) continue; ?>
                            <option value="<?= $cat['ID'] ?>" <?= $currentCategory == $cat['ID'] ? 'selected' : '' ?>>
                                <?= htmlspecialcharsbx($cat['NAME']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Режим создания заявок:</td>
                <td>
                    <select name="ORDER_MODE" id="order_mode_select" onchange="toggleQuantityMode()">
                        <option value="zero" <?= $currentMode == 'zero' ? 'selected' : '' ?>>По закончившимся запчастям</option>
                        <option value="selected" <?= $currentMode == 'selected' ? 'selected' : '' ?>>По выбранным запчастям и количеству</option>
                    </select>
                </td>
            </tr>
            <tr id="row_default_qty">
                <td>Количество для заявки (по умолчанию):</td>
                <td><input type="number" name="DEFAULT_QTY" value="<?= $defaultQty ?>" size="10"></td>
            </tr>
        </table>

        <div style="margin-bottom: 20px;">
            <input type="submit" name="save_settings" value="Сохранить настройки" class="adm-btn-save">
            <input type="submit" name="create_orders" value="Создать заявки" class="adm-btn" onclick="return confirm('Создать заявки по текущему режиму?');">
        </div>

        <h3>Список запчастей</h3>
        <p style="color: #666; font-size: 12px;" id="quantity_hint">Укажите количество для тех товаров, которые нужно заказать. Если выбран режим «По закончившимся», эта таблица игнорируется.</p>
        
        <table class="adm-detail-content-table edit-table">
            <tr class="heading">
                <td>Товар</td>
                <td width="100">Остаток</td>
                <td width="150" id="quantity_header">Кол-во для заявки</td>
            </tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialcharsbx($product['NAME']) ?></td>
                    <td style="color: <?= $product['STOCK'] <= 0 ? 'red' : 'green' ?>; font-weight: bold;">
                        <?= $product['STOCK'] ?> шт.
                    </td>
                    <td id="quantity_cell_<?= $product['ID'] ?>">
                        <input type="number" name="ORDER_QTY[<?= $product['ID'] ?>]" 
                               value="<?= isset($orderQtys[$product['ID']]) ? $orderQtys[$product['ID']] : 0 ?>" 
                               style="width: 80px;">
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- вкладка 2: инструкция -->
    <div id="tab-instruction" class="adm-detail-content" style="display: none;">
        <h3>Инструкция по работе с модулем (для пользователя)</h3>
        <p>Модуль «Автозаказ» предназначен для автоматизации закупок запчастей. Он позволяет формировать заявки (сделки в CRM) на основе остатков товаров на складе.</p>
        <ul> 
            <li><b>настройка воронки:</b> выберите воронку, в которой будут создаваться заявки на закупку.</li>
            <li><b>нужно выбрать режим создания заявок, по нулевым остаткам или самостоятельный выбор количетсва и товаров,</b>
            <li><b>по закончившимся запчастям:</b> модуль найдет все товары с нулевым остатком и создаст для них заявки с количеством, указанным в поле «Количество для заявки (по умолчанию)».</li>
            <li><b>по выбранным запчастям:</b> вы указываете количество в таблице для нужных товаров, и модуль создаст заявки именно на них, в этом случае количество нужно указать в таблице напротив каждого товара,</li>
            <li><b>создание заявок:</b> нажмите кнопку «Создать заявки». В случае успеха появится список созданных сделок, перейти к ним можно будет по ссылке в сообщении.</li>
        </ul>

        <hr style="margin: 20px 0;">
        
        <h3>Описание реализации</h3>
        
        <ul>
            <li>пользователь в настройках модуля выбирает сущность, в которую будет добавляться сделка,</li>
            <li>воронку CRM (общую или из списка доступных), в которую будут падать созданные заявки,</li>
            <li>режим создания заявок: «По закончившимся запчастям» или «По выбранным запчастям и количеству»,</li>
            <li>количество по умолчанию (появляется только в режиме «По закончившимся»),</li>
            <li>индивидуальное количество для каждого товара в таблице (появляется только в режиме «По выбранным»),</li>
            <li>в режиме <b>«По закончившимся»</b>: поле «Количество для заявки (по умолчанию)» отображается, в таблице товаров колонка «Кол-во для заявки» скрывается, это нужно, чтобы пользователь не пытался вручную задать количество для товаров, которые модуль находит автоматически (у которых остаток ≤ 0),</li>
            <li>в режиме <b>«По выбранным»</b>: поле «Количество по умолчанию» скрывается, в таблице товаров колонка «Кол-во для заявки» становится доступной. Это позволяет указать точное количество только для тех товаров, которые пользователь хочет заказать,</li>
            <li>сначала модуль проверяет, есть ли у товара торговые предложения (SKU) через <code>CCatalogSKU::getOffersList()</code>,</li>
            <li>если предложения есть, берется первое предложение и его остаток через <code>CCatalogProduct::GetList()</code>,</li>
            <li>если предложений нет, берется остаток самого товара,</li>
            <li>остаток выводится в таблице рядом с названием товара. Если остаток ≤ 0 — он подсвечивается красным,</li>
            <li><b>в режиме «По закончившимся»</b>: список запчастей служит только для справки (визуального контроля), модуль сам проходит по всему инфоблоку, находит товары с остатком ≤ 0 и создает для них заявки с количеством из поля «По умолчанию»,</li>
            <li><b>в режиме «По выбранным»</b>: пользователь вручную вписывает нужное количество в колонку «Кол-во для заявки» напротив тех товаров, которые нужно заказать, модуль берет только те строки, где количество > 0, и создает для них заявки,</li>
            <li><b>.description.php</b> — файл содержит массив <code>$arClass</code> с ключами <code>ID</code>, <code>NAME</code>, <code>DESCRIPTION</code>, он используется для отображения названия и описания модуля в списке установленных решений, сделан для идентификации модуля в административной панели,</li>
            <li><b>include.php</b> — файл содержит подключение автозагрузки классов модуля (через <code>Loader::registerAutoLoadClasses</code>), сделан для того, чтобы при обращении к классам модуля (например, <code>DraftTable</code>) они автоматически подгружались без явного подключения файлов,</li>
            <li><b>lib/DraftTable.php</b> — класс, описывающий ORM-сущность для работы с таблицей <code>custom_autoorder_drafts</code>, сделан для того, чтобы можно было удобно работать с записями истории заявок через методы <code>add</code>, <code>getList</code> и т.д,</li>
            <li><b>install/index.php</b> — класс <code>custom_autoorder</code>, наследующий <code>CModule</code>,</li>
            <li>метод <code>DoInstall()</code> регистрирует модуль через <code>RegisterModule()</code>, создает таблицу в базе данных через SQL-запрос <code>CREATE TABLE</code> (в методе <code>InstallDB()</code>) и регистрирует пункт меню в админке через <code>RegisterModuleDependences()</code> (обработчик <code>OnBuildGlobalMenu</code>).</li>
            <li>метод <code>DoUninstall()</code> удаляет таблицу из базы данных через <code>DROP TABLE</code> (в методе <code>UnInstallDB()</code>) и снимает регистрацию модуля через <code>UnRegisterModule()</code>.</li>
            <li>метод <code>OnBuildGlobalMenu()</code> добавляет ссылку на страницу настроек модуля в левое меню административной панели,</li>
            <li><b>options.php</b> — файл страницы настроек и логики создания заявок,</li>
            <li>подключает модули <code>iblock</code>, <code>catalog</code> и <code>crm</code> через <code>Loader::includeModule()</code>,</li>
            <li>обрабатывает POST-запрос сохранения настроек: получает выбранную воронку, режим, количество по умолчанию и индивидуальные количества, сохраняет их через <code>Option::set()</code>,</li>
            <li>обрабатывает POST-запрос создания заявок: в зависимости от выбранного режима либо ищет все товары с остатком ≤ 0, либо берет только товары с указанным количеством, для каждого товара вызывается функция <code>createAutoorderDeal()</code>,</li>
            <li>функция <code>createAutoorderDeal()</code> создает сделку через <code>CCrmDeal</code> и привязывает к ней товар через <code>ProductRowTable::add()</code>,</li>
            <li>выводит HTML-форму с вкладками «Настройки» и «Инструкция», таблицей товаров и кнопками, использует JavaScript для скрытия/показа полей в зависимости от выбранного режима,</li>
            <li>выводит список успешно созданных сделок в виде HTML-списка со ссылками на карточки в CRM (формат <code>/crm/deal/details/{ID}/</code>),</li>
            <li>для получения остатков используется модуль <code>catalog</code>: сначала проверяется наличие торговых предложений (SKU) через <code>CCatalogSKU::getOffersList()</code>, затем берется остаток из <code>CCatalogProduct::GetList()</code>,</li> 
            <li>если предложений нет — остаток берется у самого товара.</li>
     </div>
</form>

<script>
    // переключение вкладок
    function showTab(tabId, element) {
        document.querySelectorAll('.adm-detail-content').forEach(function(el) { el.style.display = 'none'; });
        document.querySelectorAll('.adm-detail-tab').forEach(function(el) { el.classList.remove('adm-detail-tab-active'); });
        
        document.getElementById('tab-' + tabId).style.display = 'block';
        element.classList.add('adm-detail-tab-active');
    }

    // управление видимостью полей в зависимости от режима
    function toggleQuantityMode() {
        var mode = document.getElementById('order_mode_select').value;
        var rowDefaultQty = document.getElementById('row_default_qty'); // строка с общим количеством
        var header = document.getElementById('quantity_header');
        var hint = document.getElementById('quantity_hint');
        var cells = document.querySelectorAll('[id^="quantity_cell_"]');

        if (mode === 'zero') {
            // режим "По нулевым": показываем общее количество, скрываем колонку в таблице
            rowDefaultQty.style.display = 'table-row';
            header.style.display = 'none';
            hint.style.display = 'none';
            cells.forEach(function(cell) { cell.style.display = 'none'; });
        } else {
            // режим "По выбранным": скрываем общее количество, показываем колонку в таблице
            rowDefaultQty.style.display = 'none';
            header.style.display = 'table-cell';
            hint.style.display = 'block';
            cells.forEach(function(cell) { cell.style.display = 'table-cell'; });
        }
    }

    // запуск при загрузке
    document.addEventListener('DOMContentLoaded', function() {
        toggleQuantityMode();
    });
</script>