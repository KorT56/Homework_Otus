<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $arResult['GRID_ID'],
    'COLUMNS' => $arResult['COLUMNS'],
    'ROWS' => $arResult['ROWS'],
    'TOTAL_ROWS_COUNT' => count($arResult['ROWS']),
    'SHOW_ROW_CHECKBOXES' => false,
    'AJAX_MODE' => 'N',
]);