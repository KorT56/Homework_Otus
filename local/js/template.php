<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
?>

<div class="services-list">
    <?php if (!empty($arResult["ITEMS"])): ?>
        <?php foreach ($arResult["ITEMS"] as $arItem): ?>
            <div class="service-item" style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                
                <!-- Название услуги -->
                <h3 style="margin-top: 0; color: #333;">
                    <?= $arItem["NAME"] ?>
                </h3>

                <!-- Краткое описание (анонс) -->
                <?php if (!empty($arItem["PREVIEW_TEXT"])): ?>
                    <p style="color: #666; font-size: 14px;">
                        <?= $arItem["PREVIEW_TEXT"] ?>
                    </p>
                <?php endif; ?>

                <!-- Блок с ценой и нормо-часами -->
                <div style="background: #f9f9f9; padding: 10px; margin-bottom: 15px;">
                    <?php if (!empty($arItem["PROPERTIES"]["PRICE"]["VALUE"])): ?>
                        <p style="margin: 0 0 5px 0; font-weight: bold;">
                            Цена: <?= number_format($arItem["PROPERTIES"]["PRICE"]["VALUE"], 0, '', ' ') ?> руб.
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($arItem["PROPERTIES"]["NORMO_HOURS"]["VALUE"])): ?>
                        <p style="margin: 0; color: #555;">
                            Нормо-часы: <?= $arItem["PROPERTIES"]["NORMO_HOURS"]["VALUE"] ?> ч.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Блок с запчастями (SERVICE_PARTS) -->
                <?php 
                if (!empty($arItem["PROPERTIES"]["SERVICE_PARTS"]["VALUE"])) {
                    $partsIds = $arItem["PROPERTIES"]["SERVICE_PARTS"]["VALUE"];
                    
                    // Делаем запрос к каталогу, чтобы получить названия запчастей
                    $res = CIBlockElement::GetList(
                        [],
                        ["ID" => $partsIds, "ACTIVE" => "Y"],
                        false,
                        false,
                        ["ID", "NAME", "PROPERTY_ARTICLE"] // ARTICLE - если нужно вывести артикул
                    );
                    
                    if ($res->SelectedRowsCount() > 0) {
                        echo '<div style="margin-top: 10px;">';
                        echo '<strong>Необходимые запчасти:</strong>';
                        echo '<ul style="margin-top: 5px; padding-left: 20px; font-size: 14px;">';
                        
                        while ($part = $res->Fetch()) {
                            echo '<li>' . $part["NAME"] . '</li>';
                        }
                        
                        echo '</ul>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Услуги пока не добавлены.</p>
    <?php endif; ?>
</div>