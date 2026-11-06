<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>

<div style="margin: 20px 0; padding: 15px; background: #fff; border-radius: 5px;">
    
    <!-- Блок с описанием -->
    <div style="background: #f8f9fa; border: 1px solid #e9ecef; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; margin-bottom: 10px; font-size: 18px;">Описание для пользователя</h3>
        <ul style="margin: 0 0 20px 20px; padding: 0; font-size: 14px; line-height: 1.6;">
            <li>Вкладка отображает текущие остатки товаров (инфоблок 14) и историю созданных заявок.</li>
            <li>Для создания новых заявок на закупку используйте кнопку «Перейти в Автозаказ».</li>
            <li>Для перехода к конкретной сделке нажмите на её название в нижней таблице.</li>
        </ul>

        <h3 style="margin-top: 20px; margin-bottom: 10px; font-size: 18px;">Описание по реализации</h3>
        <ul style="margin: 0 0 0 20px; padding: 0; font-size: 14px; line-height: 1.6; color: #555;">
            <li>Вкладка добавляется в карточку Сделки через событие <code>onEntityDetailsTabsInitialized</code> (модуль <code>custom.dealtabs</code>).</li>
            <li>Таблица товаров формируется компонентом <code>custom:dealtabs.grid</code> на основе данных инфоблока 14 (с учетом торговых предложений).</li>
            <li>История заявок получается через API CRM (<code>DealTable</code>) с фильтром по названию «Автозаказ:».</li>
            <li>Вывод таблиц реализован через стандартный HTML, без использования грида.</li>
        </ul>
    </div>

    <!-- Кнопка перехода -->
    <div style="margin-bottom: 20px;">
        <a href="/bitrix/admin/settings.php?mid=custom.autoorder&lang=ru" class="ui-btn ui-btn-primary" target="_blank" style="text-decoration: none;">
            Перейти в Автозаказ (Добавить товары)
        </a>
    </div>

    <!-- Таблица 1: Товары с остатками -->
    <h3 style="margin-bottom: 15px;">Товары с остатками</h3>
    <table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                <th style="padding: 10px; text-align: left; width: 50px;">ID</th>
                <th style="padding: 10px; text-align: left;">Название товара</th>
                <th style="padding: 10px; text-align: left; width: 100px;">Остаток</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($arResult['PRODUCTS'] as $product): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 10px;"><?= $product['ID'] ?></td>
                    <td style="padding: 10px;"><?= $product['NAME'] ?></td>
                    <td style="padding: 10px; font-weight: bold; color: <?= $product['STOCK'] <= 0 ? 'red' : 'green' ?>;">
                        <?= $product['STOCK'] ?> шт.
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 20px 0;">

    <!-- Таблица 2: Сделки Автозаказа -->
    <h3 style="margin-bottom: 15px;">История заявок (Сделки Автозаказ)</h3>
    <table class="table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                <th style="padding: 10px; text-align: left; width: 50px;">ID</th>
                <th style="padding: 10px; text-align: left;">Сделка</th>
                <th style="padding: 10px; text-align: left;">Товар</th>
                <th style="padding: 10px; text-align: left; width: 80px;">Кол-во</th>
                <th style="padding: 10px; text-align: left; width: 120px;">Дата</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($arResult['DEALS'])): ?>
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center; color: #999;">
                        Сделки автозаказа не найдены.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($arResult['DEALS'] as $deal): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 10px;"><?= $deal['ID'] ?></td>
                        <td style="padding: 10px;">
                            <a href="/crm/deal/details/<?= $deal['ID'] ?>/" target="_blank" style="color: #2067b0; text-decoration: none;">
                                <?= $deal['TITLE'] ?>
                            </a>
                        </td>
                        <td style="padding: 10px;"><?= $deal['PRODUCT_NAME'] ?></td>
                        <td style="padding: 10px;"><?= $deal['QUANTITY'] ?></td>
                        <td style="padding: 10px; color: #666;"><?= $deal['DATE_CREATE'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>